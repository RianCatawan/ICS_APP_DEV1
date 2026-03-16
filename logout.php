<?php
session_start();
include "db.php";

if (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];
    $action = "User Logged Out";
    
    // 1. Store in User Logs
    // Note: Ensure you have a table named 'user_logs' with columns: username, action, timestamp
    $log_stmt = $conn->prepare("INSERT INTO user_logs (username, action, log_time) VALUES (?, ?, NOW())");
    $log_stmt->bind_param("ss", $username, $action);
    $log_stmt->execute();
}

// 2. Clear all session data
$_SESSION = array();

// 3. Destroy the session
session_destroy();

// 4. Redirect to login or home page
header("Location: index.php"); 
exit();
?>