<?php

// Database connection parameters
$host = " ";    // host ip address
$posrt = "5432";   // default postgresql port
$dbname = " ";    // name of database
$user = "postgres";   
$password = " ";    // postgresql password

// Create connection
$conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");

// check if connection failed
if(!$conn)
{
    echo "Error : Unable to connect to the database.";
    exit;
}

// if connection is successful, the connection object (link) is established
echo "Connected to the database successfully!";

?>