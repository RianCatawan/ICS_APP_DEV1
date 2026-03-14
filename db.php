<?php
// db.php - Database connection for HoopMatch

$servername = "localhost";  // Usually localhost on XAMPP
$username = "root";         // Default XAMPP username
$password = "";             // Default XAMPP password is empty
$dbname = "university_hoops";  // Make sure you created this database

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>