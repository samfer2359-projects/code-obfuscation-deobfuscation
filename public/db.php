<?php


// Reference: PostgreSQL PHP connection approach adapted from Tutorialspoint
// https://www.tutorialspoint.com/postgresql/postgresql_php.htm



// Database connection parameters
$host = "localhost";    // database host
$port = "5432";   // default postgresql port
$dbname = "codecryptix";    // name of database
$user = "postgres";   
$password = "root";    // database password

// Create connection
$conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");

// check if connection failed
if(!$conn)
{
   // Exit if the connection fails";
    exit;
}

// Connection established successfully

?>