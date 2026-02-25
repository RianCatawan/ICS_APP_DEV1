<?php
session_start();
include "db.php";

// Check if user is logged in
if(isset($_SESSION['username'])){

    $username = $_SESSION['username'];

    // Use prepared statement to safely log logout
    $stmt = $conn->prepare("INSERT INTO user_logs (username, action) VALUES (?, ?)");
    $action = 'LOGOUT';
    $stmt->bind_param("ss", $username, $action);
    $stmt->execute();
    $stmt->close();
}

// Destroy session and redirect
session_destroy();
header("Location: index.php");
exit();
?>
