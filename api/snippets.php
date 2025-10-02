<?php
// submit_code.php
session_start();
include __DIR__ . '/db.php'; // adjust path if db.php is elsewhere

// Simple check: require login
if (!isset($_SESSION['user_id'])) {
    // Not logged in — stop and show a message (user must login first)
    echo "You must be logged in to submit code.";
    exit;
}

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "Invalid request method.";
    exit;
}

// Get and validate inputs
$original_code = isset($_POST['original_code']) ? trim($_POST['original_code']) : '';
$language = isset($_POST['language']) ? trim($_POST['language']) : '';

if ($original_code === '' || $language === '') {
    echo "Please provide code and language.";
    exit;
}

// Prepare and execute insert
$user_id = (int) $_SESSION['user_id'];

$query = "INSERT INTO codesnippet (user_id, original_code, language) VALUES ($1, $2, $3)";
$result = pg_query_params($conn, $query, array($user_id, $original_code, $language));

if ($result === false) {
    // Show DB error for debugging (remove/replace in production)
    echo "Database error: " . pg_last_error($conn);
    exit;
}

// Success — optionally redirect back to obfuscate page or a success page
// Use a short message then redirect
echo "Code saved successfully.";

// Redirect back to public/obfuscator.html 
 header("Location: ../public/obfuscator.html");
exit;
?>
