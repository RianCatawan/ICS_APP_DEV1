<?php
session_start();
include "db.php";

if(!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin'){
    header("Location: index.php");
    exit();
}

$id = $_GET['id'] ?? null;

if($id){
    $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
    $stmt->bind_param("i",$id);
    $stmt->execute();
    $stmt->close();
}

header("Location: manage_users.php");
exit();
?>