<?php
session_start();
include "db.php";

$user_id = $_SESSION['user_id'];

if(isset($_POST['proceed'])){

$court = $_POST['court'];

$stmt = $conn->prepare("INSERT INTO usercourts (user_id, court)
VALUES (?, ?)
ON DUPLICATE KEY UPDATE court=?");

$stmt->bind_param("iss",$user_id,$court,$court);
$stmt->execute();
$stmt->close();

header("Location: match.php");
exit();
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Select Court</title>

<style>

body{
background:#0f172a;
color:white;
font-family:Arial;
display:flex;
justify-content:center;
align-items:center;
height:100vh;
}

.card{
background:#020617;
padding:30px;
border-radius:15px;
width:400px;
display:flex;
flex-direction:column;
gap:15px;
}

select{
padding:10px;
border-radius:10px;
border:2px solid #38bdf8;
background:#0f172a;
color:white;
}

button{
padding:12px;
border:none;
border-radius:10px;
background:#38bdf8;
font-weight:bold;
cursor:pointer;
}

button:hover{
background:#22c55e;
}

.map{
height:200px;
background:#111827;
border-radius:10px;
display:flex;
justify-content:center;
align-items:center;
}

</style>

</head>

<body>

<div class="card">

<h2>Select Court</h2>

<div class="map">MAP HERE</div>

<form method="POST">

<select name="court" required>

<option value="">Select Court</option>

<option value="Court 1">Court 1</option>

<option value="Court 2">Court 2</option>

<option value="Court 3">Court 3</option>

</select>

<button name="proceed">Proceed</button>

</form>

</div>

</body>
</html>