<?php

/*This will enter the user details in the database*/

/*Websites refered to geeksfoegeeks: URL: https://www.geeksforgeeks.org/php/php-tutorial/  , w3schools: URL: https://www.w3schools.com/php/  , tutorialspoint: URL: https://www.tutorialspoint.com/postgresql/postgresql_php.htm */

include('db.php');

// Check if the form was submitted 
if ($_SERVER['REQUEST_METHOD'] == 'POST')
{
   
    echo "Form data received: <br>";  
    echo "Full Name: " . $_POST['fullname'] . "<br>";
    echo "Email: " . $_POST['email'] . "<br>";
    echo "Username: " . $_POST['username'] . "<br>";
    echo "Password: " . $_POST['password'] . "<br>";

     // Get form data and sanitize inputs
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
}

/* Validate email format  website:https://www.w3schools.com/php/func_filter_var.asp */
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Invalid email format.";
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
    echo pg_last_error($conn);
}
else
{
    echo "Records created successfully ";
}

pg_close($conn);

?>

