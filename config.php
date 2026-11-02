<?php
// Include configuration for database connection


$servername = "localhost";
$username = "root"; //  Database username
$password = ""; //      Database password
$dbname = "CRS"; //     Database name

$conn = new mysqli($servername, $username, $password, $dbname);


// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}