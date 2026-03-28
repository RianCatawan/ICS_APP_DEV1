<?php
// db.php - Database connection for HoopMatch

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "basketball_matchmaker";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>