<?php
// api/deobfuscate.php
session_start();
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');

include_once __DIR__ . '/db.php'; // expects $conn

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Invalid request method. Use POST.']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'You must be logged in to deobfuscate code.']);
    exit;
}

// read password provided by user (used as decryption key)
$password = isset($_POST['password']) ? trim($_POST['password']) : '';
if ($password === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Password is required.']);
    exit;
}


// Find record by verifying token hash
$q = "SELECT o.obj_key, o.code_id, o.method_used, c.original_code
      FROM obfuscation o
      JOIN codesnippet c ON o.code_id = c.code_id";
$res = pg_query($conn, $q);


$found = false;
$record = null;

if ($res && pg_num_rows($res) > 0) {
    while ($r = pg_fetch_assoc($res)) {
        // verify provided password against stored hash (obj_key)
        if (password_verify($password, $r['obj_key'])) {
            $found = true;
            $record = $r;
            break;
        }
    }
}



// ensure we found a matching record
if (!$found || !$record) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid or incorrect password.']);
    exit;
}

// set variables from the found record
$obj_key = isset($record['obj_key']) ? $record['obj_key'] : null;
$code_id = isset($record['code_id']) ? (int)$record['code_id'] : null;
$encoded_original = isset($record['original_code']) ? $record['original_code'] : null;
$method_used = isset($record['method_used']) ? $record['method_used'] : null;

if (empty($obj_key) || $encoded_original === null) {
    http_response_code(500);
    echo json_encode(['error'=>'Server error: record incomplete']);
    exit;
}

// decrypt original_code (was stored as base64(iv . raw_ciphertext))
$decoded = base64_decode($encoded_original);
$iv_length = openssl_cipher_iv_length('AES-128-CTR');
if ($decoded === false || strlen($decoded) <= $iv_length) {
    http_response_code(500);
    echo json_encode(['error'=>'Server error: corrupted encrypted data']);
    exit;
}
$iv = substr($decoded, 0, $iv_length);
$ciphertext = substr($decoded, $iv_length);

// use the provided password as the decryption key (must match encryption key used earlier)
$decrypted_code = openssl_decrypt($ciphertext, 'AES-128-CTR', $password, OPENSSL_RAW_DATA, $iv);

if ($decrypted_code === false) {
    http_response_code(403);
    echo json_encode(['error' => 'Decryption failed. Incorrect password or corrupted data.']);
    exit;
}




// Record the action
$insert_deob_sql = "INSERT INTO deobfuscation (obj_key, deobfuscated_code) VALUES ($1, $2)
                    ON CONFLICT (obj_key) DO NOTHING";

pg_query_params($conn, $insert_deob_sql, [$obj_key, $decrypted_code]);



@pg_query_params($conn,
    "INSERT INTO session_log (user_id, action, status) VALUES ($1, $2, $3)",
    [$_SESSION['user_id'], 'deobfuscate', 'successful']
);

echo json_encode([
    'success' => true,
    'code_id' => $code_id,
    'method_used' => $method_used,
    'deobfuscated_code' => $decrypted_code
]);


exit;
?>
