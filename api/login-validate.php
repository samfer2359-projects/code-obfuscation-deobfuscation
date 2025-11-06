
<?php


session_start(); // Start the session to track login state

include('db.php'); // db.php should set $conn with pg_connect(...)

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Get form input values
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Check if both fields are provided
    if (empty($username) || empty($password)) {
       // echo "Please provide both username and password.";
       header("Location: ../public/login.html?error=invalid_credentials");
        exit;
    }

    // Query the database to check if the user exists
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

        // Check if the password is correct (password_hash must be stored with password_hash())
        if (password_verify($password, $user['password_hash'])) {
            // Login successful: Store user info in session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];

            // Optionally: Store the current time or last login time in session
            $_SESSION['logged_in_at'] = date('Y-m-d H:i:s');

            // Log the successful login attempt (optional)
            $action = 'login';
            $status = 'successful';
            $log_query = "INSERT INTO session_log (user_id, action, status, log_time) 
                          VALUES ($1, $2, $3, now())";
            pg_query_params($conn, $log_query, array($user['user_id'], $action, $status));

            echo "<script>
localStorage.setItem('username', '" . addslashes($user['username']) . "');
window.location.href = '../public/welcome.html';
</script>";
exit;

            // Redirect to index.html or any other page
            header("Location: ../public/welcome.html");
            exit;
        } else {
            // Incorrect password
            // echo "Invalid password.";
            header("Location: ../public/login.html?error=invalid_credentials");
    exit;
        }
    } else {
        // User not found
       // echo "No user found with that username or email.";
       header("Location: ../public/login.html?error=invalid_credentials");
    exit;
    }
} else {
    // If the form was not submitted via POST method
   // echo "Invalid request method.";
    header("Location: ../public/login.html?error=request");
    exit;
}




?>
