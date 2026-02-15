<?php
session_start();

if(!isset($_SESSION['username']) || $_SESSION['role'] != 'user'){
    header("Location: index.php");
    exit();
}
?>

<h2>Welcome User: <?php echo $_SESSION['username']; ?></h2>

<a href="logout.php">Logout</a>
