<?php
// api/obfuscate.php - simple obfuscator; dummy function inserted at random position

session_start();
ini_set('display_errors', '0');
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');
include_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Use POST']);
    exit;
}
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Login required']);
    exit;
}

$original_code = isset($_POST['original_code']) ? (string) $_POST['original_code'] : '';
$language = isset($_POST['language']) ? strtolower(trim((string) $_POST['language'])) : '';
if ($original_code === '' || $language === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Provide original_code and language']);
    exit;
}

// run randomness (in-memory only)
$run_id = substr(bin2hex(random_bytes(3)), 0, 6);
$do_rename = (bool) random_int(0, 1);
$do_dummy  = (bool) random_int(0, 1);
$do_runmark = true;

// helpers
function light_minify($code) {
    $code = preg_replace('#/\*.*?\*/#s', '', $code);
    $code = preg_replace('#//.*$#m', '', $code);
    $code = preg_replace("/\n{2,}/", "\n", $code);
    return trim($code);
}
function mkname($base, $run_id) {
    return $base . '_' . $run_id;
}
function word_replace_all($code, $old, $new) {
    return preg_replace('/\b' . preg_quote($old, '/') . '\b/', $new, $code);
}

// renamers
function rename_js_vars($code, $run_id, &$methods) {
    if (preg_match_all('/\b(?:var|let|const)\s+([A-Za-z_]\w*)/m', $code, $matches)) {
        $names = array_unique($matches[1]);
        foreach ($names as $name) $code = word_replace_all($code, $name, mkname($name, $run_id));
        $methods[] = 'rename-js';
    }
    return $code;
}
function rename_py_vars($code, $run_id, &$methods) {
    $lines = explode("\n", $code);
    foreach ($lines as $i => $ln) {
        if (preg_match('/^\s*([A-Za-z_]\w*)\s*=/', $ln, $m)) {
            $old = $m[1]; $new = mkname($old, $run_id);
            $lines[$i] = preg_replace('/^(\s*)' . preg_quote($old, '/') . '\b/', '$1' . $new, $lines[$i], 1);
            $code = implode("\n", $lines);
            $code = word_replace_all($code, $old, $new);
        }
    }
    $methods[] = 'rename-py';
    return $code;
}
function rename_c_vars($code, $run_id, &$methods) {
    if (preg_match_all('/\b(?:int|char|float|double|long|short|unsigned)\s+([A-Za-z_]\w*)\b/', $code, $matches)) {
        $names = array_unique($matches[1]);
        foreach ($names as $name) $code = word_replace_all($code, $name, mkname($name, $run_id));
        $methods[] = 'rename-c';
    }
    return $code;
}

// dummy function blocks
function dummy_js_block($run_id) {
    return "/* DUMMY_FUNC_{$run_id} */\nif (0) { function __dummy_{$run_id}(){ return 42; } }\n";
}
function dummy_py_block($run_id) {
    return "# DUMMY_FUNC_{$run_id}\nif False:\n    def __dummy_{$run_id}():\n        return 42\n";
}
function dummy_c_block($run_id) {
    return "/* DUMMY_FUNC_{$run_id} */\n#if 0\nint __dummy_{$run_id}() { return 42; }\n#endif\n";
}

// insert block at position: 'top', 'middle', 'bottom'
function insert_block_at_position($code, $block, $position) {
    $lines = explode("\n", $code);
    if ($position === 'top') {
        array_unshift($lines, $block);
        return implode("\n", $lines);
    } elseif ($position === 'bottom') {
        $lines[] = $block;
        return implode("\n", $lines);
    } else { // middle
        $half = (int) floor(count($lines) / 2);
        $top = array_slice($lines, 0, $half);
        $bottom = array_slice($lines, $half);
        $new = array_merge($top, array($block), $bottom);
        return implode("\n", $new);
    }
}

// store original snippet (DB unchanged)
$user_id = (int) $_SESSION['user_id'];
$insert_sql = "INSERT INTO codesnippet (user_id, original_code, language) VALUES ($1, $2, $3) RETURNING code_id";
$ins = pg_query_params($conn, $insert_sql, array($user_id, $original_code, $language));
if ($ins === false) {
    http_response_code(500);
    echo json_encode(['error' => 'DB error inserting codesnippet', 'details' => pg_last_error($conn)]);
    exit;
}
$row = pg_fetch_assoc($ins);
$code_id = (int) $row['code_id'];

// pipeline
$methods = [];
$code = light_minify($original_code);
$methods[] = 'minify';

// renaming
if (($language === 'js' || $language === 'javascript') && $do_rename) {
    $code = rename_js_vars($code, $run_id, $methods);
} elseif (($language === 'py' || $language === 'python') && $do_rename) {
    $code = rename_py_vars($code, $run_id, $methods);
} elseif (in_array($language, ['c', 'cpp', 'c++'], true) && $do_rename) {
    $code = rename_c_vars($code, $run_id, $methods);
}

// dummy insertion at random position if chosen
if ($do_dummy) {
    $positions = ['top','middle','bottom'];
    $pos = $positions[random_int(0, 2)];
    if ($language === 'js' || $language === 'javascript') {
        $block = dummy_js_block($run_id);
        $code = insert_block_at_position($code, $block, $pos);
        $methods[] = 'dummy-js-' . $pos;
    } elseif ($language === 'py' || $language === 'python') {
        $block = dummy_py_block($run_id);
        $code = insert_block_at_position($code, $block, $pos);
        $methods[] = 'dummy-py-' . $pos;
    } elseif (in_array($language, ['c', 'cpp', 'c++'], true)) {
        $block = dummy_c_block($run_id);
        $code = insert_block_at_position($code, $block, $pos);
        $methods[] = 'dummy-c-' . $pos;
    } else {
        $block = "/* DUMMY_FUNC_{$run_id} */\n";
        $code = insert_block_at_position($code, $block, $pos);
        $methods[] = 'dummy-generic-' . $pos;
    }
}

// always append run marker at bottom
if ($do_runmark) {
    $code .= "\n\n/*RUN_{$run_id}*/\n";
    $methods[] = 'run-id';
}

// generate a random access token (returned to user) and store its hash as obj_key
$plain_token = bin2hex(random_bytes(8)); // 16-hex chars (~128 bits)
$token_hash = password_hash($plain_token, PASSWORD_DEFAULT); // store hash in DB


//encrypt, encode & store
//$obf_blob = base64_encode($code);
// Encrypts and then coverts to base 64
//encrypt, include iv & base64 encode for safe text storage
$iv_length = openssl_cipher_iv_length('AES-128-CTR');
$iv = openssl_random_pseudo_bytes($iv_length);

// get raw ciphertext
$cipher_raw = openssl_encrypt($code, 'AES-128-CTR', 'TestKeyEncrypt16', OPENSSL_RAW_DATA, $iv);

// store iv + cipher as base64 so it is UTF-8 safe
$obf_blob = base64_encode($iv . $cipher_raw);

$methods[] = 'aes-128-ctr';
$methods[] = 'base64';
$method_used = implode(';', $methods);


// If an obfuscation for this code_id already exists, return its obj_key and obfuscated blob instead
$check_sql = "SELECT obj_key, obfuscated_code, method_used FROM obfuscation WHERE code_id = $1 LIMIT 1";
$check = @pg_query_params($conn, $check_sql, array($code_id));
if ($check === false) {
    // DB error while checking
    http_response_code(500);
    echo json_encode(['error' => 'DB error checking existing obfuscation', 'details' => pg_last_error($conn)]);
    exit;
}
if (pg_num_rows($check) > 0) {
    $existing = pg_fetch_assoc($check);
    // return existing obj_key and blob to user (do not create a new token)
    echo json_encode([
        'success' => true,
        'obj_key' => $existing['obj_key'],
        'access_token' => null,
        'code_id' => $code_id,
        'run_id' => $run_id,
        'method_used' => $existing['method_used'],
        'obfuscated_code' => $existing['obfuscated_code'],
        'note' => 'Existing obfuscation for this code_id returned (no new token created).'
    ]);
    exit;
}

// Insert new obfuscation (no existing record)
$insert_obf_sql = "INSERT INTO obfuscation (obj_key, code_id, obfuscated_code, method_used) VALUES ($1, $2, $3, $4) RETURNING obj_key, timestamp";
$res2 = @pg_query_params($conn, $insert_obf_sql, array($token_hash, $code_id, $obf_blob, $method_used));
if ($res2 === false) {
    // log and return DB error
    @file_put_contents(__DIR__ . '/debug.log', date('c') . " - INSERT obfuscation failed: " . pg_last_error($conn) . PHP_EOL, FILE_APPEND | LOCK_EX);
    http_response_code(500);
    echo json_encode(['error' => 'DB error inserting obfuscation', 'details' => pg_last_error($conn)]);
    exit;
}
$ob_row = pg_fetch_assoc($res2);
$obj_key = isset($ob_row['obj_key']) ? $ob_row['obj_key'] : null;

// defensive: ensure obj_key and plain token exist before continuing
if (empty($obj_key) || empty($plain_token)) {
    @file_put_contents(__DIR__ . '/debug.log', date('c') . " - Missing obj_key or token after insert. obj_key=" . var_export($obj_key, true) . " token=" . var_export($plain_token, true) . PHP_EOL, FILE_APPEND | LOCK_EX);
    http_response_code(500);
    echo json_encode(['error' => 'Server error: token/key missing after insert', 'details' => pg_last_error($conn)]);
    exit;
}



@pg_query_params($conn, "INSERT INTO session_log (user_id, action, status) VALUES ($1, $2, $3)", array($user_id, 'obfuscate', 'successful'));

echo json_encode([
    'success' => true,
    'access_token' => $plain_token,   // the plain token user must keep safe
    'code_id' => $code_id,
    'run_id' => $run_id,
    'method_used' => $method_used,
    'obfuscated_code' => $obf_blob
]);

exit;

?>
