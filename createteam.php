<?php
session_start();
include "db.php";

if(!isset($_SESSION['user_id'])){
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if(isset($_POST['create'])){

    $team_name = $_POST['team_name'];
    $player_name = $_POST['player_name'];
    $game_type = $_POST['game_type'];

    $stmt = $conn->prepare("INSERT INTO userteams (user_id, team_name, player_name, game_type)
                            VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $team_name, $player_name, $game_type);
    $stmt->execute();
    $stmt->close();

    header("Location: selectcourt.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Create Team</title>
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
input, select{
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
color:white;
}
</style>
</head>

<body>

<div class="card">

<h2>Create Team</h2>

<form method="POST">

<input type="text" name="team_name" placeholder="Team Name" required>

<input type="text" name="player_name" placeholder="Player Name" required>
                                                                                                                                            
<select name="game_type" required>
<option value="">Select Game Type</option>
<option value="1v1">1v1</option>
<option value="2v2">2v2</option>
<option value="3v3">3v3</option>
<option value="5v5">5v5</option>
</select>

<button type="submit" name="create">Proceed</button>

</form>

</div>

</body>
</html>