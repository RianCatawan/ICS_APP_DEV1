<?php
session_start();
include "db.php";

if(!isset($_SESSION['user_id'])){
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = ""; // To display error messages

if(isset($_POST['create'])){

    $team_name = trim($_POST['team_name']);
    $player_name = trim($_POST['player_name']);
    $game_type = $_POST['game_type'];

    // Check if the team name already exists
    $check = $conn->prepare("SELECT id FROM userteams WHERE team_name = ?");
    $check->bind_param("s", $team_name);
    $check->execute();
    $check->store_result();

    if($check->num_rows > 0){
        $error = "Team name already exists. Please choose another name.";
    } else {
        // Insert the new team
        $stmt = $conn->prepare("INSERT INTO userteams (user_id, team_name, player_name, game_type) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $user_id, $team_name, $player_name, $game_type);
        if($stmt->execute()){
            header("Location: selectcourt.php");
            exit();
        } else {
            $error = "Error creating team: " . $stmt->error;
        }
        $stmt->close();
    }

    $check->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>HoopMatch | Create Team</title>
<style>
body{
margin:0;
font-family:Arial, sans-serif;
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

/* BACK BUTTON */
.back-btn{
position:absolute;
top:25px;
right:40px;
padding:8px 18px;
border-radius:8px;
border:2px solid #F57C00;
color:#F57C00;
text-decoration:none;
font-weight:bold;
transition:0.3s;
}

.back-btn:hover{
background:#F57C00;
color:black;
}

.card{
margin-top:170px;
background:#111;
padding:55px;
border-radius:12px;
width:480px;
margin-left:auto;
margin-right:auto;
display:flex;
flex-direction:column;
gap:25px;
box-shadow:0 10px 25px rgba(0,0,0,0.6);
text-align:center;
}

/* INPUTS */
input, select{
padding:15px;
border-radius:10px;
border:2px solid #F57C00;
background:#000;
color:white;
font-size:16px;
}

/* BUTTON */
button{
padding:18px;
border:none;
border-radius:10px;
background:#F57C00;
font-weight:bold;
font-size:19px;
cursor:pointer;
transition:0.3s;
width:100%;
}

button:hover{
background:#ff8c00;
transform:scale(1.05);
}

/* ERROR MESSAGE */
.error-msg{
color:#ff6b6b;
font-weight:bold;
}
</style>
</head>
<body>

<div class="top-bg">
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
<a class="navbar-brand" href="index.php">HoopMatch</a>
</div>

<a href="matchstart.php" class="back-btn">⬅ Back</a>

<div class="card">

<h2>Create Team</h2>

<?php if($error) { echo "<div class='error-msg'>$error</div>"; } ?>

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

<button type="submit" name="create">Create Team</button>
<br><br>
<div style="margin-top:10px;">
    <a href="match.php" style="text-decoration:none; color:blue; font-weight:bold;">
        Already have a Team?
    </a>
</form>

</div>

</body>
</html>