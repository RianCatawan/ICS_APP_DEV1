<?php
session_start();
require_once __DIR__ . '/../database_config/db.php';

if(!isset($_SESSION['username']) || $_SESSION['role'] != 'user'){
    header("Location: index.php");
    exit();
}
?>

<h2>Welcome User: <?php echo $_SESSION['username']; ?></h2>

<a href="logout.php">Logout</a>
