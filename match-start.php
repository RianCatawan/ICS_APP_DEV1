<?php
session_start();
include "db.php";

/* ===============================
   OPTIONAL: CHECK LOGIN
   (Remove this if not needed)
================================*/
if (!isset($_SESSION['user_id'])) {
    die("Access denied. Please login first.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>HoopMatch | Match Start</title>

<style>
body {
    margin: 0;
    padding: 0;
    background: #0f172a;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    font-family: Arial, sans-serif;
}

/* Center Button */
.match-btn {
    padding: 25px 60px;
    font-size: 28px;
    font-weight: bold;
    border: none;
    border-radius: 20px;
    background: #38bdf8;
    color: #020617;
    cursor: pointer;
    transition: 0.3s ease;
    box-shadow: 0 0 20px rgba(56,189,248,0.4);
}

.match-btn:hover {
    background: #22c55e;
    color: white;
    transform: scale(1.05);
    box-shadow: 0 0 25px rgba(34,197,94,0.6);
}
</style>
</head>
<body>

<form action="createteam.php" method="POST">
    <button type="submit" class="match-btn">
        Match Start
    </button>
</form>

</body>
</html>