<?php
// api/deobfuscate.php
session_start();
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

// The API accepts either:
// - obj_id (integer) — primary key in obfuscation
// - obfuscated_code (base64 string) — the blob returned earlier
$input_obj_id = isset($_POST['obj_id']) ? trim((string)$_POST['obj_id']) : '';
$obfuscated_code = isset($_POST['obfuscated_code']) ? trim((string)$_POST['obfuscated_code']) : '';

if ($input_obj_id === '' && $obfuscated_code === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Provide either obj_id or obfuscated_code.']);
    exit;
}

$obj_id = null;
$code_id = null;
$original_code = null;
$method_used = null;

// If obj_id supplied, fetch direct
if ($input_obj_id !== '') {
    $q = "SELECT o.obj_id, o.obfuscated_code, o.method_used, c.code_id, c.original_code
          FROM obfuscation o
          JOIN codesnippet c ON o.code_id = c.code_id
          WHERE o.obj_id = $1
          LIMIT 1";
    $res = pg_query_params($conn, $q, [$input_obj_id]);
    if ($res && pg_num_rows($res) > 0) {
        $r = pg_fetch_assoc($res);
        $obj_id = (int)$r['obj_id'];
        $code_id = (int)$r['code_id'];
        $original_code = $r['original_code'];
        $method_used = $r['method_used'];
    }
} else {
    // Find by obfuscated_code
    $q = "SELECT o.obj_id, o.obfuscated_code, o.method_used, c.code_id, c.original_code
          FROM obfuscation o
          JOIN codesnippet c ON o.code_id = c.code_id
          WHERE o.obfuscated_code = $1
          LIMIT 1";
    $res = pg_query_params($conn, $q, [$obfuscated_code]);
    if ($res && pg_num_rows($res) > 0) {
        $r = pg_fetch_assoc($res);
        $obj_id = (int)$r['obj_id'];
        $code_id = (int)$r['code_id'];
        $original_code = $r['original_code'];
        $method_used = $r['method_used'];
    }
}

if ($obj_id === null) {
    http_response_code(404);
    echo json_encode(['error' => 'Obfuscated record not found.']);
    exit;
}

// Insert deobfuscation record (if not already present)
// deobfuscation has UNIQUE(obj_id) per your schema, so attempt insert and ignore duplicate errors
$insert_deob_sql = "INSERT INTO deobfuscation (obj_id, deobfuscated_code) VALUES ($1, $2)";
$ins = pg_query_params($conn, $insert_deob_sql, [$obj_id, $original_code]);

// Log session action
$action = 'deobfuscate';
$status = ($ins !== false) ? 'successful' : 'duplicate_or_db_error';
$log_sql = "INSERT INTO session_log (user_id, action, status) VALUES ($1, $2, $3)";
@pg_query_params($conn, $log_sql, [$_SESSION['user_id'], $action, $status]);

// Return original_code
echo json_encode([
    'success' => true,
    'obj_id' => $obj_id,
    'code_id' => $code_id,
    'method_used' => $method_used,
    'deobfuscated_code' => $original_code
]);
exit;
