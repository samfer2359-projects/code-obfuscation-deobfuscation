<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';

// VALIDATION

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['error' => 'POST only']));
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['error' => 'Login required']));
}

$original = trim($_POST['original_code'] ?? '');
$lang     = strtolower(trim($_POST['language'] ?? ''));
$password = trim($_POST['password'] ?? '');

if ($original === '' || $password === '') {
    exit(json_encode(['error' => 'Missing parameters']));
}

// SUPPORTED LANGUAGES

$keywords = match ($lang) {
    'js', 'javascript' => [
        'function','return','if','else','for','while','var','let','const',
        'class','new','this','switch','case','break','continue','console','log'
    ],
    'python' => [
        'def','return','if','else','elif','for','while','import','from',
        'class','print','None','True','False','and','or','not','in'
    ],
    'c' => [
        'int','char','float','double','void','return','if','else','for',
        'while','static','struct','typedef','include','define','printf'
    ],
    default => exit(json_encode(['error' => 'Unsupported language']))
};

$user_id = (int)$_SESSION['user_id'];

//  CREATE CODE RECORD
$res = pg_query_params(
    $conn,
    "INSERT INTO codesnippet (user_id, original_code, language)
     VALUES ($1, '', $2) RETURNING code_id",
    [$user_id, $lang]
);
$row = pg_fetch_assoc($res);
$code_id = (int)$row['code_id'];

//  KEY DERIVATION
$salt = hash('sha256', 'obf-salt-' . $code_id, true);
$key  = hash_pbkdf2('sha256', $password, $salt, 150000, 32, true);
$token_hash = password_hash($password, PASSWORD_ARGON2ID);

//  STRING SHIELDING

$code = preg_replace_callback(
    '/(["\'])(?:\\\\.|(?!\1).)*\1/s',
    fn($m) => '__STR__' . base64_encode(substr($m[0],1,-1)) . '__',
    $original
);

//IDENTIFIER OBFUSCATION

$map = [];
$run = bin2hex(random_bytes(4));

preg_match_all('/\b[a-zA-Z_][a-zA-Z0-9_]*\b/', $code, $matches);

foreach (array_unique($matches[0]) as $id) {
    if (in_array($id, $keywords, true)) continue;
    if (str_starts_with($id, '__STR__')) continue;

    $obf = '_v' . substr(hash('sha1', $id . $run), 0, 10);
    $map[$obf] = $id;
    $code = preg_replace('/\b' . preg_quote($id, '/') . '\b/', $obf, $code);
}

//  DUMMY FUNCTIONS (MARKED)

function injectDummyFunctions(string $code, string $lang): array {
    $names = [];
    $count = random_int(2,4);

    for ($i=0;$i<$count;$i++) {
        $name = '_d' . bin2hex(random_bytes(3));
        $names[] = $name;

        if ($lang === 'python') {
            $code .= "\n#<DUMMY_START:$name>\n";
            $code .= "def $name():\n    return 0\n";
            $code .= "#<DUMMY_END:$name>\n";
        }
        elseif ($lang === 'c') {
            $code .= "\n/*<DUMMY_START:$name>*/\n";
            $code .= "int $name(){ return 0; }\n";
            $code .= "/*<DUMMY_END:$name>*/\n";
        }
        else { // JS
            $code .= "\n//<DUMMY_START:$name>\n";
            $code .= "function $name(){ return 0; }\n";
            $code .= "//<DUMMY_END:$name>\n";
        }
    }

    return [$code, $names];
}

[$code, $dummyNames] = injectDummyFunctions($code, $lang);

// CONTROL FLOW WRAPPER (MARKED)

function wrapWithControlFlow(string $code, string $lang): string {
    $n = random_int(1000,9999);

    if ($lang === 'python') {
        return "#<CF_START>\n"
             . "_cf = $n\n"
             . "while _cf == $n:\n"
             . "    _cf = $n + 1\n"
             . "#<CF_END>\n"
             . $code;
    }

    if ($lang === 'c') {
        return "/*<CF_START>*/\n"
             . "int _cf = $n;\n"
             . "while(_cf == $n){ _cf = $n + 1; }\n"
             . "/*<CF_END>*/\n"
             . $code;
    }

    // JS
    return "//<CF_START>\n"
         . "var _cf = $n;\n"
         . "while(_cf === $n){ _cf = $n + 1; }\n"
         . "//<CF_END>\n"
         . $code;
}

$code = wrapWithControlFlow($code, $lang);



$payload = json_encode([
    'code' => $code,
    'map'  => $map,
    'lang' => $lang
]);

$iv  = random_bytes(12);
$tag = '';
$cipher = openssl_encrypt($payload, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
$encrypted = base64_encode($iv . $tag . $cipher);

pg_query_params(
    $conn,
    "INSERT INTO obfuscation (obj_key, code_id, obfuscated_code, method_used)
     VALUES ($1, $2, $3, $4)",
    [$token_hash, $code_id, $encrypted, 'advanced-reversible-safe']
);

echo json_encode([
    'success' => true,
    'code_id' => $code_id,
    'obfuscated_code' => $code
]);