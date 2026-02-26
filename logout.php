<?php
session_start();
include "db.php";

if(isset($_SESSION['user_id'])){

    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("INSERT INTO user_logs (user_id, action)
                            VALUES (?, 'LOGOUT')");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
}

session_destroy();
header("Location: index.php");
exit();
?>