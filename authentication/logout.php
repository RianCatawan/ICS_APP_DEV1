<?php
session_start();
require_once __DIR__ . '/../database_config/db.php';

// Check if user is logged in to log the logout action
if (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];
    $action = "Logged Out";

    // Use a try-catch so if the table structure is slightly different, 
    // it doesn't block the user from logging out.
    try {
        // Updated to use the 'id' if possible, or matches your table schema
        $log_stmt = $conn->prepare("INSERT INTO user_logs (user_id, action) SELECT id, ? FROM users WHERE username = ?");
        $log_stmt->bind_param("ss", $action, $username);
        $log_stmt->execute();
    } catch (Exception $e) {
        // Log failed, but we continue with logout anyway
    }
}

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy session
session_destroy();

// THE FIX: Redirect specifically to the basketball folder index
header("Location: ../ICS_APP_DEV1/index.php");
exit();
?>