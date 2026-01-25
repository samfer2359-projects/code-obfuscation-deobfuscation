<?php

// Authentication flow implemented using PHP sessions and PostgreSQL (pg_query_params)


session_start(); // Initialize session for authentication state

include('db.php'); // db.php should set $conn with pg_connect(...)

// Handle login form submission only
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Get form input values
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Check if both fields are provided
    if (empty($username) || empty($password)) {
       // echo "Please provide both username and password.";
       header("Location: login.html?error=invalid_credentials");
        exit;
    }

    // Fetch user by username or email (authentication lookup)
    $query = "SELECT user_id, username, email, password_hash FROM users WHERE username = $1 OR email = $1 LIMIT 1";
    $result = pg_query_params($conn, $query, array($username));

    if ($result === false) {
       // echo "Database query error.";
        exit;
    }

    // Check if the user exists in the database
    if (pg_num_rows($result) > 0) {
        // Fetch user details from the database
        $user = pg_fetch_assoc($result);

        // Verify submitted password against stored password hash
        if (password_verify($password, $user['password_hash'])) {
            
            // Persist authenticated user data in session
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];

            // Optionally: Store the current time or last login time in session
            $_SESSION['logged_in_at'] = date('Y-m-d H:i:s');

            // Audit log: record successful login
            $action = 'login';
            $status = 'successful';
            $log_query = "INSERT INTO session_log (user_id, action, status, log_time) 
                          VALUES ($1, $2, $3, now())";
            pg_query_params($conn, $log_query, array($user['user_id'], $action, $status));

       
          

            // Redirect to index.html or any other page
            header("Location: welcome.php");
            exit;
        } else {
            // Incorrect password
            header("Location: login.html?error=invalid_credentials");
    exit;
        }
    } else {
        // User not found
       header("Location: login.html?error=invalid_credentials");
    exit;
    }
} else {
    // If the form was not submitted via POST method
    header("Location: login.html?error=request");
    exit;
}




?>
