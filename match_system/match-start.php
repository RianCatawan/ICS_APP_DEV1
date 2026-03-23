<?php
session_start();
include "db.php";

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

body{
    margin:0;
    font-family: Arial, sans-serif;
    background:#000;
    color:white;
}

/* ORANGE TOP BACKGROUND */
.top-bg{
    position:absolute;
    top:0;
    left:0;
    width:100%;
    height:300px;
    background:#F57C00;
    z-index:-5;
}

/* S CURVE */
.curve{
    position:absolute;
    bottom:-1px;
    width:100%;
}

/* NAVBAR */
.navbar{
    padding:20px 40px;
}

.navbar-brand{
    color:white;
    font-weight:bold;
    font-size:24px;
    text-decoration:none;
}

/* BACK BUTTON (TOP RIGHT) */
.back-btn{
    position:absolute;
    top:25px;
    right:40px;
    padding:8px 18px;
    border-radius:8px;
    border:2px solid #ffffff;
    color:#ffffff;
    text-decoration:none;
    font-weight:bold;
   
}

.back-btn:hover{
    background:#F57C00;
    color:black;
}

/* CENTER CONTAINER */
.match-container{
    margin-top:210px;
    background:#111;
    padding:60px;
    border-radius:12px;
    max-width:450px;
    margin-left:auto;
    margin-right:auto;
    text-align:center;
    box-shadow:0 10px 25px rgba(0,0,0,0.6);
}

/* BUTTON */
.match-btn{
    padding:20px 60px;
    font-size:26px;
    font-weight:bold;
    border:none;
    border-radius:12px;
    background:#F57C00;
    color:black;
    cursor:pointer;
    transition:0.3s;
}

.match-btn:hover{
    background:#ff8c00;
    transform:scale(1.05);
}

</style>
</head>

<body>

<div class="top-bg">

<!-- S CURVE DIVIDER -->
<svg class="curve" viewBox="0 0 1440 120" preserveAspectRatio="none">
<path fill="#000"
d="M0,80 
C300,120 500,20 900,60
C1200,90 1300,10 1440,0
L1440,120
L0,120 Z">
</path>
</svg>

</div>

<div class="navbar">
<a class="navbar-brand" href="index.php"> HoopMatch</a>
</div>

<!-- BACK BUTTON -->
<a href="index.php" class="back-btn">⬅ Back</a>

<div class="match-container">

<form action="createteam.php" method="POST">
<button type="submit" class="match-btn">
Match Start
</button>
</form>

</div>

</body>
</html>