<?php
session_start();
include "db.php";

$username = $_SESSION['username'];

// Insert Logout Log
$conn->query("INSERT INTO user_logs (username, action)
              VALUES ('$username', 'LOGOUT')");

session_destroy();
header("Location: index.php");
exit();
?>
