<?php
// db.php - Database connection for HoopMatch

$servername = "localhost";
$username = "u442411629_dev_basketball";
$password = "3@1>Pb(bp9_X";
$dbname = "u442411629_basketball";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>