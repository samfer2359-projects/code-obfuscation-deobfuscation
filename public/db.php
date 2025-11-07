<?php

// Website reffered to while creating this php file: URL: https://www.tutorialspoint.com/postgresql/postgresql_php.htm

// Database connection parameters
$host = "localhost";    // host ip address
$port = "5432";   // default postgresql port
$dbname = "codecryptix";    // name of database
$user = "postgres";   
$password = "esha06";    // postgresql password

// Create connection
$conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");

// check if connection failed
if(!$conn)
{
   // echo "Error : Unable to connect to the database.";
    exit;
}

// if connection is successful, the connection object (link) is established
// echo "Connected to the database successfully!";

?>