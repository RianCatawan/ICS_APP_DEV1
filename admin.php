<?php
session_start();

if(!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin'){
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Admin Dashboard</title>
<style>
body {
    background: #0f172a;
    color: white;
    font-family: Arial;
    text-align: center;
    padding: 50px;
}
.box {
    background: #1e293b;
    padding: 30px;
    border-radius: 12px;
    display: inline-block;
}
a {
    display: inline-block;
    margin-top: 20px;
    padding: 10px 20px;
    background: #ef4444;
    color: white;
    text-decoration: none;
    border-radius: 8px;
}
</style>
</head>
<body>

<div class="box">
    <h1>👑 Admin Dashboard</h1>
    <p>Welcome, <?php echo $_SESSION['username']; ?>!</p>
    <a href="logout.php">Logout</a>
</div>

</body>
</html>