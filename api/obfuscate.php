<?php
// api/obfuscate.php
session_start();

// make sure no stray output or PHP warnings are sent
ini_set('display_errors', '0');
error_reporting(0);

header('Content-Type: application/json; charset=utf-8');

include_once __DIR__ . '/db.php'; // must set $conn; ensure db.php does not echo anything

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Invalid request method. Use POST.']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'You must be logged in to obfuscate code.']);
    exit;
}

$original_code = isset($_POST['original_code']) ? (string)$_POST['original_code'] : '';
$language = isset($_POST['language']) ? trim((string)$_POST['language']) : '';

if ($original_code === '' || $language === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Please provide original_code and language.']);
    exit;
}

// Light-safe minify: remove C-style comments and collapse blank lines (very conservative)
function light_minify($code) {
    $code = preg_replace('#/\*.*?\*/#s', '', $code); // remove block comments
    $code = preg_replace('#//.*$#m', '', $code);     // remove line comments
    $code = preg_replace("/\n{2,}/", "\n", $code);
    return trim($code);
}

$user_id = (int) $_SESSION['user_id'];

// Insert codesnippet (do this here to avoid including snippets.php that might echo/redirect)
$insert_sql = "INSERT INTO codesnippet (user_id, original_code, language) VALUES ($1, $2, $3) RETURNING code_id";
$ins = pg_query_params($conn, $insert_sql, array($user_id, $original_code, $language));
if ($ins === false) {
    http_response_code(500);
    echo json_encode(['error' => 'DB error inserting codesnippet', 'details' => pg_last_error($conn)]);
    exit;
}
$row = pg_fetch_assoc($ins);
$code_id = (int)$row['code_id'];

// Create obfuscation: base64 of minified source
$minified = light_minify($original_code);
$obfuscated_blob = base64_encode($minified);
$method_used = 'base64-lightminify';

$insert_obf_sql = "INSERT INTO obfuscation (code_id, obfuscated_code, method_used) VALUES ($1, $2, $3) RETURNING obj_id, timestamp";
$res2 = pg_query_params($conn, $insert_obf_sql, array($code_id, $obfuscated_blob, $method_used));
if ($res2 === false) {
    http_response_code(500);
    echo json_encode(['error' => 'DB error inserting obfuscation', 'details' => pg_last_error($conn)]);
    exit;
}
$ob_row = pg_fetch_assoc($res2);
$obj_id = (int)$ob_row['obj_id'];

// Log (best-effort)
@pg_query_params($conn, "INSERT INTO session_log (user_id, action, status) VALUES ($1, $2, $3)", array($user_id, 'obfuscate', 'successful'));

// Always return clean JSON
echo json_encode([
    'success' => true,
    'obj_id' => $obj_id,
    'code_id' => $code_id,
    'method_used' => $method_used,
    'obfuscated_code' => $obfuscated_blob
]);
exit;
?>