<?php

/*This will enter the user details in the database*/

/*Websites refered to geeksfoegeeks: URL: https://www.geeksforgeeks.org/php/php-tutorial/  , w3schools: URL: https://www.w3schools.com/php/  , tutorialspoint: URL: https://www.tutorialspoint.com/postgresql/postgresql_php.htm */

include('db.php');

// Check if the form was submitted 
if ($_SERVER['REQUEST_METHOD'] == 'POST')
{
     // Get form data and sanitize inputs
    
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);


/* Validate email format  website:https://www.w3schools.com/php/func_filter_var.asp */
     if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location:signup.html?error=invalid_email");
    exit;
}


/*website: https://www.w3schools.com/php/func_filter_var.asp */
$password_hash = password_hash($password, PASSWORD_BCRYPT);

// Prepare SQL query to insert data into the database (use placeholders for security)
    $sql = "INSERT INTO users (email, username, password_hash) VALUES ($1, $2, $3)";
// Prepare the query and execute it
    $result = pg_query_params($conn, $sql, array($email, $username, $password_hash));

//check if execution was successful
if(!$result)
{
   // echo pg_last_error($conn);
    header("Location: signup.html?error=insert_failed");
        exit;
}

pg_close($conn);
session_start();
$_SESSION['user_id'] = pg_last_oid($result); // optional if you want the DB id
$_SESSION['username'] = $username;
$_SESSION['email'] = $email;
header("Location: login.html");  // Redirect to welcome.html
}
else {
    // Redirect if the request method is not POST
    header("Location: signup.html?error=request");
    exit;
}

?>

