<?php
// db.php - Database connection for HoopMatch

$servername = "localhost";
$user = "root";
$password = "";
$dbname = "university_hoops";

// Create connection
$conn = new mysqli($servername, $user, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>