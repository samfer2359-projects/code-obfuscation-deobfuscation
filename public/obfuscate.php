<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';

// Reversible code obfuscation endpoint with per-user encryption and session-based access control


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

//Reserved keywords to exclude from obfuscation
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

//Create code record and generate unique code_id
$res = pg_query_params(
    $conn,
    "INSERT INTO codesnippet (user_id, original_code, language)
     VALUES ($1, '', $2) RETURNING code_id",
    [$user_id, $lang]
);
$row = pg_fetch_assoc($res);
$code_id = (int)$row['code_id'];

//Derive encryption key and password verifier
$salt = hash('sha256', 'obf-salt-' . $code_id, true);
$key  = hash_pbkdf2('sha256', $password, $salt, 150000, 32, true);
$token_hash = password_hash($password, PASSWORD_ARGON2ID);

//Protect string literals to prevent accidental obfuscation
$code = preg_replace_callback(
    '/(["\'])(?:\\\\.|(?!\1).)*\1/s',
    function ($m) {
        $str = substr($m[0], 1, -1);
        return '__STR__' . base64_encode($str) . '__';
    },
    $original
);

//Deterministic identifier obfuscation with reversible mapping
$map = [];
$run = bin2hex(random_bytes(4));

preg_match_all('/\b[a-zA-Z_][a-zA-Z0-9_]*\b/', $code, $matches);

foreach (array_unique($matches[0]) as $id) {
    if (in_array($id, $keywords, true)) continue;
    if (str_starts_with($id, '__STR__')) continue;

    if (!isset($map[$id])) {
        $obf = '_v' . substr(hash('sha1', $id . $run), 0, 10);
        $map[$obf] = $id;
        $code = preg_replace('/\b' . preg_quote($id, '/') . '\b/', $obf, $code);
    }
}


$payload = json_encode([
    'code' => $code,
    'map'  => $map,
    'lang' => $lang
]);

//Encrypt payload using AES-256-GCM 
$iv  = random_bytes(12);
$tag = '';
$cipher = openssl_encrypt($payload, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
$encrypted = base64_encode($iv . $tag . $cipher);

//Persist encrypted obfuscation result
pg_query_params(
    $conn,
    "INSERT INTO obfuscation (obj_key, code_id, obfuscated_code, method_used)
     VALUES ($1, $2, $3, $4)",
    [$token_hash, $code_id, $encrypted, 'js|python|c-reversible']
);

echo json_encode([
    'success' => true,
    'code_id' => $code_id,
    'obfuscated_code' => $code
]);
