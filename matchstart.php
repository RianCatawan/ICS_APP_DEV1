<?php

$team1 = $_POST['team1'];
$team2 = $_POST['team2'];

if($team1 == $team2){
    echo "Error: You cannot match the same team.";
    exit();
}

?>

<!DOCTYPE html>
<html>
<head>
<title>Match Start | HoopMatch</title>

<style>

body{
    margin:0;
    padding:0;
    font-family: Arial;
    background: linear-gradient(135deg,#0f2027,#203a43,#2c5364);
    height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    color:white;
}

.match-card{
    background:#111;
    padding:40px;
    border-radius:15px;
    text-align:center;
    width:500px;
    box-shadow:0 0 25px rgba(0,0,0,0.6);
}

.title{
    font-size:40px;
    margin-bottom:30px;
    color:#ff7b00;
}

.teams{
    display:flex;
    justify-content:space-between;
    align-items:center;
}

.team{
    font-size:28px;
    font-weight:bold;
    width:40%;
}

.vs{
    font-size:30px;
    font-weight:bold;
    color:#ff7b00;
}

.start-btn{
    margin-top:30px;
    padding:12px 25px;
    border:none;
    background:#ff7b00;
    color:white;
    font-size:16px;
    border-radius:8px;
    cursor:pointer;
}

.start-btn:hover{
    background:#ff9d3f;
}

</style>

</head>

<body>

<div class="match-card">

<div class="title">🏀 MATCH START</div>

<div class="teams">

<div class="team">
<?php echo htmlspecialchars($team1); ?>
</div>

<div class="vs">VS</div>

<div class="team">
<?php echo htmlspecialchars($team2); ?>
</div>

</div>

<form action="" method="POST">

<input type="hidden" name="team1" value="<?php echo $team1; ?>">
<input type="hidden" name="team2" value="<?php echo $team2; ?>">

<button class="start-btn">Game Ready</button>

</form>

</div>

</body>
</html>