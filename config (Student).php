<?php
// Include configuration for database connection


// Database connection
$servername = "localhost";
$username = "root"; // Replace with your database username
$password = ""; // Replace with your database password
$dbname = "CRS"; // Replace with your actual database name

$conn = new mysqli($servername, $username, $password, $dbname);
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Student') {
    header("Location: /CRS_V1.0/index.php"); // Redirect to login if unauthorized
    exit();
}


// Retrieve the student ID from the session
$student_id = $_SESSION['user_id'];
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}