<?php
session_start();
include "db.php"; // Connect to database

$team1_name = $_POST['team1'] ?? '';
$team2_name = $_POST['team2'] ?? '';
$message = '';

if($team1_name == $team2_name && !empty($team1_name)){
    $message = "Error: You cannot match the same team.";
}

// Handle Game Ready submission
if(isset($_POST['game_ready']) && empty($message)){

    // Get the team IDs from userteams table
    $stmt1 = $conn->prepare("SELECT id FROM userteams WHERE team_name=? LIMIT 1");
    $stmt1->bind_param("s", $team1_name);
    $stmt1->execute();
    $result1 = $stmt1->get_result();
    $team1 = $result1->fetch_assoc();
    $team1_id = $team1['id'];
    $stmt1->close();

    $stmt2 = $conn->prepare("SELECT id FROM userteams WHERE team_name=? LIMIT 1");
    $stmt2->bind_param("s", $team2_name);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    $team2 = $result2->fetch_assoc();
    $team2_id = $team2['id'];
    $stmt2->close();

    // Insert into matches using team IDs
    $insert = $conn->prepare("INSERT INTO matches (team1_id, team2_id, status, match_time) VALUES (?, ?, 'ready', NOW())");
    $insert->bind_param("ii", $team1_id, $team2_id);

    if($insert->execute()){
        $message = "Match successfully recorded!";
    } else {
        $message = "Error recording match: " . $insert->error;
    }

    $insert->close();
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

.message{
    margin-top:20px;
    color:#22c55e;
    font-weight:bold;
}
</style>
</head>
<body>

<div class="match-card">

<div class="title">🏀 MATCH START</div>

<div class="teams">
<div class="team"><?php echo htmlspecialchars($team1_name); ?></div>
<div class="vs">VS</div>
<div class="team"><?php echo htmlspecialchars($team2_name); ?></div>
</div>

<?php if(!empty($message)){ echo "<div class='message'>$message</div>"; } ?>

<form action="" method="POST">
<input type="hidden" name="team1" value="<?php echo htmlspecialchars($team1_name); ?>">
<input type="hidden" name="team2" value="<?php echo htmlspecialchars($team2_name); ?>">
<button type="submit" name="game_ready" class="start-btn">Game Ready</button>
</form>

</div>

</body>
</html>