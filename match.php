<?php
include "db.php";

$result = $conn->query("SELECT team_name FROM userteams");
$result2 = $conn->query("SELECT team_name FROM userteams");
?>

<!DOCTYPE html>
<html>
<head>
<title>Matchmaking | HoopMatch</title>

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

/* CURVE */
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
}

.back-btn:hover{
background:#F57C00;
color:black;
}

/* MATCH AREA */
.match-container{
margin-top:250px;
display:flex;
justify-content:center;
align-items:center;
gap:60px;
}

/* TEAM BOX */
.team-box{
background:#111;
width:240px;
height:240px;
border-radius:12px;
display:flex;
flex-direction:column;
justify-content:center;
align-items:center;
gap:15px;
box-shadow:0 10px 20px rgba(0,0,0,0.6);
}

.team-box h3{
color:#F57C00;
}

/* SELECT */
select{
padding:10px;
border-radius:8px;
border:2px solid #F57C00;
background:#000;
color:white;
font-size:15px;
}

/* VS SECTION */
.vs-section{
display:flex;
flex-direction:column;
align-items:center;
gap:20px;
}

.vs{
font-size:50px;
font-weight:bold;
color:#F57C00;
}

/* START BUTTON */
.start-btn{
padding:14px 30px;
border:none;
border-radius:8px;
background:#F57C00;
color:black;
font-weight:bold;
font-size:16px;
cursor:pointer;
}

.start-btn:hover{
background:#ff8c00;
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

<a href="match-start.php" class="back-btn">← Back</a>

<form action="matchstart.php" method="POST">

<div class="match-container">

<!-- TEAM 1 -->
<div class="team-box">
<h3>Team 1</h3>

<select name="team1" required>
<option value="">Select Team</option>

<?php while($row = $result->fetch_assoc()) { ?>

<option value="<?php echo $row['team_name']; ?>">
<?php echo $row['team_name']; ?>
</option>

<?php } ?>

</select>

</div>

<!-- VS -->
<div class="vs-section">
<div class="vs">VS</div>

<button type="submit" class="start-btn">
Start Match
</button>

</div>

<!-- TEAM 2 -->
<div class="team-box">
<h3>Team 2</h3>

<select name="team2" required>
<option value="">Select Team</option>

<?php while($row = $result2->fetch_assoc()) { ?>

<option value="<?php echo $row['team_name']; ?>">
<?php echo $row['team_name']; ?>
</option>

<?php } ?>

</select>

</div>

</div>

</form>

</body>
</html>