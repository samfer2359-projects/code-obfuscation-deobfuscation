<?php

/*
  Deobfuscation API Endpoint
  --------------------------
  Authenticated endpoint that retrieves the latest obfuscated code for the logged-in user, verifies the decryption password, decrypts the payload,and restores the original source code.
*/


session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';


// Validate request method and session authentication
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['error' => 'POST only']));
}
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['error' => 'Login required']));
}

$password = trim($_POST['password'] ?? '');
if ($password === '') {
    exit(json_encode(['error' => 'Missing password']));
}

/* Fetch latest obfuscated code owned by the authenticated user */
$res = pg_query_params(
    $conn,
    "SELECT o.code_id, o.obj_key, o.obfuscated_code
     FROM obfuscation o
     JOIN codesnippet c ON c.code_id = o.code_id
     WHERE c.user_id = $1
     ORDER BY o.timestamp DESC
     LIMIT 1",
    [$_SESSION['user_id']]
);


$row = pg_fetch_assoc($res);

//Verify user-supplied password against stored obfuscation key
if (!$row || !password_verify($password, $row['obj_key'])) {
    http_response_code(403);
    exit(json_encode(['error' => 'Invalid password']));
}

$code_id = (int)$row['code_id'];

/* Derive AES-256 key using PBKDF2 with a per-code salt */
// PBKDF2 mitigates brute-force attacks on user passwords
$salt = hash('sha256', 'obf-salt-' . $code_id, true);
$key  = hash_pbkdf2('sha256', $password, $salt, 150000, 32, true);

/* Decrypt obfuscated payload using AES-256-GCM */
$raw = base64_decode($row['obfuscated_code'], true);
$iv  = substr($raw, 0, 12);
$tag = substr($raw, 12, 16);
$ct  = substr($raw, 28);

$json = openssl_decrypt($ct, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
if ($json === false) {
    exit(json_encode(['error' => 'Decryption failed']));
}

$data = json_decode($json, true);
$code = $data['code'];
$map  = $data['map'];

/* Restore original identifiers */
uksort($map, fn($a, $b) => strlen($b) <=> strlen($a));
foreach ($map as $obf => $orig) {
    $code = preg_replace('/\b' . preg_quote($obf, '/') . '\b/', $orig, $code);
}

/* Restore string literals preserved during obfuscation */
$code = preg_replace_callback(
    '/__STR__(.*?)__/',
    fn($m) => '"' . base64_decode($m[1]) . '"',
    $code
);

echo json_encode([
    'success' => true,
    'deobfuscated_code' => $code
]);
