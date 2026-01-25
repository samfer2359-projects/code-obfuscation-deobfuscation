<?php

//Signup handler: stores user in 'users' table after validating input

/* Refferences: 
   1. GeeksforGeeks PHP tutorial: https://www.geeksforgeeks.org/php/php-tutorial/  
   2. W3Schools PHP filter functions: https://www.w3schools.com/php/func_filter_var.asp  
   3. Tutorialspoint PostgreSQL with PHP: https://www.tutorialspoint.com/postgresql/postgresql_php.htm 
*/

session_start();
include('db.php');


if ($_SERVER['REQUEST_METHOD'] == 'POST')
{
     
    
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);



     if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location:signup.html?error=invalid_email");
    exit;
}


//Hash password securely before storing
$password_hash = password_hash($password, PASSWORD_BCRYPT);


    $sql = "INSERT INTO users (email, username, password_hash) VALUES ($1, $2, $3) RETURNING user_id";
    $result = pg_query_params($conn, $sql, array($email, $username, $password_hash));



if(!$result)
{
   
    header("Location: signup.html?error=insert_failed");
        exit;
}


$user_id = pg_fetch_result($result, 0, 'user_id');

$_SESSION['user_id'] = $user_id;
$_SESSION['username'] = $username;
$_SESSION['email'] = $email;

pg_close($conn);

header("Location: login.html");  // Redirect to login page after signup
}
else {
    
    header("Location: signup.html?error=request");
    exit;
}

?>

