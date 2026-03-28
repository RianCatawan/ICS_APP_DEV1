<?php
session_start();
include(__DIR__ . '/../database&config/db.php');

// Check if user is logged in
if (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];
    $action = "User Logged Out";

    // Insert log (NO ERROR NOW)
    $stmt = $conn->prepare("INSERT INTO user_logs (username, action) VALUES (?, ?)");
    $stmt->bind_param("ss", $username, $action);
    $stmt->execute();
}

// Clear session
$_SESSION = [];
session_destroy();

// Redirect to login page
header("Location: /ICS_APP_DEV1/index.php");
exit();
?>