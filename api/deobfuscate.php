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

$access_token = isset($_POST['access_token']) ? trim($_POST['access_token']) : '';
if ($access_token === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Access token is required.']);
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
        if (password_verify($access_token, $r['obj_key'])) {
            $found = true;
            $record = $r;
            break;
        }
    }
}


// ensure we found a matching record
if (!$found || !$record) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid or incorrect access token.']);
    exit;
}

// set variables from the found record
$obj_key = isset($record['obj_key']) ? $record['obj_key'] : null;
$code_id = isset($record['code_id']) ? (int)$record['code_id'] : null;
$original_code = isset($record['original_code']) ? $record['original_code'] : null;
$method_used = isset($record['method_used']) ? $record['method_used'] : null;

if (empty($obj_key)) {
    http_response_code(500);
    echo json_encode(['error'=>'Server error: obj_key missing']);
    exit;
}



// Record the action
$insert_deob_sql = "INSERT INTO deobfuscation (obj_key, deobfuscated_code) VALUES ($1, $2)
                    ON CONFLICT (obj_key) DO NOTHING";
pg_query_params($conn, $insert_deob_sql, [$obj_key, $original_code]);


@pg_query_params($conn,
    "INSERT INTO session_log (user_id, action, status) VALUES ($1, $2, $3)",
    [$_SESSION['user_id'], 'deobfuscate', 'successful']
);

echo json_encode([
    'success' => true,
    'obj_key' => $obj_key,
    'code_id' => $code_id,
    'method_used' => $method_used,
    'deobfuscated_code' => $original_code
]);

exit;
?>
