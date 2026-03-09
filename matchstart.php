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
    background:#000; /* fallback black */
    height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    color:white;
    position:relative;
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

/* CURVE DIVIDER */
.curve{
    position:absolute;
    bottom:-1px;
    width:100%;
}

/* BACK BUTTON */
.back-btn{
    position:absolute;
    top:20px;
    left:20px;
    background:#111;
    color:white;
    padding:10px 18px;
    border-radius:8px;
    border:2px solid #F57C00;
    text-decoration:none;
    font-weight:bold;
    z-index:10;
}

.back-btn:hover{
    background:#F57C00;
    color:black;
}

/* MATCH CARD */
.match-card{
    background:#111;
    padding:40px;
    border-radius:15px;
    text-align:center;
    width:500px;
    box-shadow:0 0 25px rgba(0,0,0,0.6);
}

/* TITLE */
.title{
    font-size:40px;
    margin-bottom:30px;
    color:#ff7b00;
}

/* TEAMS */
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

/* BUTTON */
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

/* MESSAGE */
.message{
    margin-top:20px;
    color:#22c55e;
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

<!-- BACK BUTTON -->
<a href="match-start.php" class="back-btn">← Back</a>

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