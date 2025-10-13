<?php
// api/obfuscate.php
// Improved, cleaned-up version of the obfuscator you provided.
// - clearer structure and comments
// - safer DB handling (uses pg_query_params correctly and checks errors)
// - masks string literals before doing naïve identifier replacement (reduces accidental renames in strings)
// - more robust input validation
// - retained original features: minify, renaming (js/py/c), dummy insertion, run-id marker, base64 encoding
// Note: This remains a simple, heuristic obfuscator. It does not perform full parsing for each language.

declare(strict_types=1);

session_start();

header('Content-Type: application/json; charset=utf-8');

// --- configuration / dependencies ---
ini_set('display_errors', '0');
error_reporting(0);

$db_path = __DIR__ . '/db.php';
if (!file_exists($db_path)) {
    http_response_code(500);
    echo json_encode(['error' => 'Server configuration error: DB file missing']);
    exit;
}
include_once $db_path;

// --- helper responders ---
function json_err(int $code, string $message, array $extra = []): void {
    http_response_code($code);
    echo json_encode(array_merge(['error' => $message], $extra));
    exit;
}

function json_ok(array $payload): void {
    echo json_encode($payload);
    exit;
}

// --- request checks ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_err(405, 'Use POST');
}

if (empty($_SESSION['user_id']) || !is_int($_SESSION['user_id']) && !ctype_digit((string)$_SESSION['user_id'])) {
    json_err(401, 'Login required');
}

$original_code = isset($_POST['original_code']) ? (string) $_POST['original_code'] : '';
$language = isset($_POST['language']) ? strtolower(trim((string) $_POST['language'])) : '';

if ($original_code === '' || $language === '') {
    json_err(400, 'Provide original_code and language');
}

// restrict supported languages to known set
$supported = ['js' => 'js', 'javascript' => 'js', 'py' => 'py', 'python' => 'py', 'c' => 'c', 'cpp' => 'c', 'c++' => 'c'];
if (!isset($supported[$language])) {
    json_err(400, 'Unsupported language');
}
$language = $supported[$language];

// --- randomness / run metadata ---
try {
    $run_id = substr(bin2hex(random_bytes(3)), 0, 6);
} catch (Exception $e) {
    json_err(500, 'Randomness failure');
}
$do_rename = (bool) random_int(0, 1);
$do_dummy  = (bool) random_int(0, 1);
$do_runmark = true;

// ----------------- utility functions -----------------

/**
 * Light minification: remove block and line comments and collapse multiple blank lines.
 * Keeps string literals intact (strings are masked/restored when renaming is applied).
 */
function light_minify(string $code): string {
    // remove block comments (/* ... */)
    $code = preg_replace('#/\*.*?\*/#s', '', $code);
    // remove line comments (//...) but avoid removing shebang lines (rare here)
    $code = preg_replace('#//.*$#m', '', $code);
    // collapse 2+ newlines into single newline
    $code = preg_replace("/\n{2,}/", "\n", $code);
    return trim($code);
}

/**
 * Generate a name with run suffix
 */
function mkname(string $base, string $run_id): string {
    return $base . '_' . $run_id;
}

/**
 * Mask string literals so subsequent regex replacements won't touch them.
 * Supports single-quoted, double-quoted, and (for JS) backtick template literals.
 * Returns [$masked_code, $map] where $map is array of placeholder => original string.
 */
function mask_strings(string $code): array {
    $map = [];
    $index = 0;

    // pattern matches single, double, and backtick strings (basic support, handles escapes)
    $pattern = '/' .
        '(\'(?:\\\\.|[^\\\\\'])*\')' . '|' .
        '("(?:\\\\.|[^\\\\"])*")' . '|' .
        '(`(?:\\\\.|[^\\\\`])*`)' .
        '/s';

    $masked = preg_replace_callback($pattern, function($m) use (&$map, &$index) {
        $orig = $m[0];
        $key = "__STR_PLACEHOLDER_{$index}__";
        $map[$key] = $orig;
        $index++;
        return $key;
    }, $code);

    return [$masked, $map];
}

/**
 * Restore masked strings from a map
 */
function restore_strings(string $code, array $map): string {
    if (empty($map)) return $code;
    return str_replace(array_keys($map), array_values($map), $code);
}

/**
 * Replace whole-word occurrences of $old with $new using word boundaries.
 * This is still heuristic: it won't fully understand language syntax, but masking strings first reduces risk.
 * Uses \b so IDs in identifiers are matched as whole words.
 */
function word_replace_all(string $code, string $old, string $new): string {
    // use delimiter-safe escaping
    $pat = '/\b' . preg_quote($old, '/') . '\b/';
    return preg_replace($pat, $new, $code);
}

// ----------------- renamers (basic heuristics) -----------------

/**
 * Rename JS variables declared with var/let/const and function names from simple function declarations.
 * Masks strings first to avoid accidental replacements inside string literals.
 */
function rename_js_vars(string $code, string $run_id, array &$methods): string {
    [$masked, $map] = mask_strings($code);

    // collect declared identifiers from var|let|const and function declarations
    $names = [];
    if (preg_match_all('/\b(?:var|let|const)\s+([A-Za-z_]\w*)/m', $masked, $m1)) {
        $names = array_merge($names, $m1[1]);
    }
    if (preg_match_all('/\bfunction\s+([A-Za-z_]\w*)\s*\(/m', $masked, $m2)) {
        $names = array_merge($names, $m2[1]);
    }

    $names = array_unique($names);
    foreach ($names as $name) {
        $masked = word_replace_all($masked, $name, mkname($name, $run_id));
    }

    $methods[] = 'rename-js';
    // restore strings and return
    return restore_strings($masked, $map);
}

/**
 * Rename Python top-level assignments (simple heuristic).
 */
function rename_py_vars(string $code, string $run_id, array &$methods): string {
    [$masked, $map] = mask_strings($code);

    // find simple assignments like: name = ...
    if (preg_match_all('/^\s*([A-Za-z_]\w*)\s*=/m', $masked, $m)) {
        $names = array_unique($m[1]);
        foreach ($names as $name) {
            $masked = word_replace_all($masked, $name, mkname($name, $run_id));
        }
        $methods[] = 'rename-py';
    }

    return restore_strings($masked, $map);
}

/**
 * Rename simple C-like variable declarations (int, char, float, etc.) and function names.
 */
function rename_c_vars(string $code, string $run_id, array &$methods): string {
    [$masked, $map] = mask_strings($code);

    if (preg_match_all('/\b(?:int|char|float|double|long|short|unsigned)\s+([A-Za-z_]\w*)\b/', $masked, $m)) {
        $names = array_unique($m[1]);
        foreach ($names as $name) {
            $masked = word_replace_all($masked, $name, mkname($name, $run_id));
        }
        $methods[] = 'rename-c';
    }

    // also try to catch simple function declarations
    if (preg_match_all('/\b([A-Za-z_]\w*)\s*\([^;{]*\)\s*{/', $masked, $m2)) {
        $fnames = array_unique($m2[1]);
        foreach ($fnames as $fname) {
            // skip common keywords like if/for/while which can match
            if (in_array($fname, ['if','for','while','switch','return','sizeof','case'], true)) continue;
            $masked = word_replace_all($masked, $fname, mkname($fname, $run_id));
        }
    }

    if (isset($methods) && is_array($methods)) $methods[] = 'rename-c';
    return restore_strings($masked, $map);
}

// ----------------- dummy blocks -----------------

function dummy_js_block(string $run_id): string {
    return "/* DUMMY_FUNC_{$run_id} */\nif (0) { function __dummy_{$run_id}(){ return 42; } }\n";
}
function dummy_py_block(string $run_id): string {
    return "# DUMMY_FUNC_{$run_id}\nif False:\n    def __dummy_{$run_id}():\n        return 42\n";
}
function dummy_c_block(string $run_id): string {
    return "/* DUMMY_FUNC_{$run_id} */\n#if 0\nint __dummy_{$run_id}() { return 42; }\n#endif\n";
}

function insert_block_at_position(string $code, string $block, string $position): string {
    $lines = preg_split("/\r\n|\n|\r/", $code);
    if ($position === 'top') {
        array_unshift($lines, $block);
    } elseif ($position === 'bottom') {
        $lines[] = $block;
    } else { // middle
        $half = (int) floor(count($lines) / 2);
        $top = array_slice($lines, 0, $half);
        $bottom = array_slice($lines, $half);
        $lines = array_merge($top, [$block], $bottom);
    }
    return implode("\n", $lines);
}

// ----------------- pipeline -----------------

$methods = [];
$code = $original_code;

// minify step
$code = light_minify($code);
$methods[] = 'minify';

// renaming step (if enabled and supported)
if ($do_rename) {
    switch ($language) {
        case 'js':
            $code = rename_js_vars($code, $run_id, $methods);
            break;
        case 'py':
            $code = rename_py_vars($code, $run_id, $methods);
            break;
        case 'c':
            $code = rename_c_vars($code, $run_id, $methods);
            break;
    }
}

// dummy insertion (if enabled)
if ($do_dummy) {
    $positions = ['top', 'middle', 'bottom'];
    try {
        $pos = $positions[random_int(0, count($positions) - 1)];
    } catch (Exception $e) {
        $pos = 'bottom';
    }

    if ($language === 'js') {
        $block = dummy_js_block($run_id);
        $code = insert_block_at_position($code, $block, $pos);
        $methods[] = 'dummy-js-' . $pos;
    } elseif ($language === 'py') {
        $block = dummy_py_block($run_id);
        $code = insert_block_at_position($code, $block, $pos);
        $methods[] = 'dummy-py-' . $pos;
    } elseif ($language === 'c') {
        $block = dummy_c_block($run_id);
        $code = insert_block_at_position($code, $block, $pos);
        $methods[] = 'dummy-c-' . $pos;
    } else {
        $block = "/* DUMMY_FUNC_{$run_id} */\n";
        $code = insert_block_at_position($code, $block, $pos);
        $methods[] = 'dummy-generic-' . $pos;
    }
}

// always append a run marker if requested
if ($do_runmark) {
    $code .= "\n\n/*RUN_{$run_id}*/\n";
    $methods[] = 'run-id';
}

// encode and prepare DB storage
$obf_blob = base64_encode($code);
$methods[] = 'base64';
$method_used = implode(';', $methods);

// --- persist original snippet and obfuscation metadata in DB ---
// user_id safe-cast
$user_id = (int) $_SESSION['user_id'];

// insert original snippet
$insert_sql = 'INSERT INTO codesnippet (user_id, original_code, language) VALUES ($1, $2, $3) RETURNING code_id';
$ins = pg_query_params($conn, $insert_sql, [$user_id, $original_code, $language]);
if ($ins === false) {
    json_err(500, 'DB error inserting codesnippet', ['details' => pg_last_error($conn)]);
}
$row = pg_fetch_assoc($ins);
if (!$row || !isset($row['code_id'])) {
    json_err(500, 'DB insert failure for codesnippet', ['details' => pg_last_error($conn)]);
}
$code_id = (int) $row['code_id'];

// insert obfuscated blob
$insert_obf_sql = 'INSERT INTO obfuscation (code_id, obfuscated_code, method_used) VALUES ($1, $2, $3) RETURNING obj_id, timestamp';
$res2 = pg_query_params($conn, $insert_obf_sql, [$code_id, $obf_blob, $method_used]);
if ($res2 === false) {
    json_err(500, 'DB error inserting obfuscation', ['details' => pg_last_error($conn)]);
}
$ob_row = pg_fetch_assoc($res2);
if (!$ob_row || !isset($ob_row['obj_id'])) {
    json_err(500, 'DB insert failure for obfuscation', ['details' => pg_last_error($conn)]);
}
$obj_id = (int) $ob_row['obj_id'];

// log session action (best-effort, ignore failures)
@pg_query_params($conn, 'INSERT INTO session_log (user_id, action, status) VALUES ($1, $2, $3)', [$user_id, 'obfuscate', 'successful']);

// --- response ---
json_ok([
    'success' => true,
    'obj_id' => $obj_id,
    'code_id' => $code_id,
    'run_id' => $run_id,
    'method_used' => $method_used,
    'obfuscated_code' => $obf_blob
]);
