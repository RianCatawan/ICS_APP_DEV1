<?php
include "db.php";

$result = $conn->query("SELECT team_name FROM userteams");
?>

<form action="matchstart.php" method="POST">

<label>Team 1</label>
<select name="team1">
<?php while($row = $result->fetch_assoc()) { ?>
<option value="<?php echo $row['team_name']; ?>">
<?php echo $row['team_name']; ?>
</option>
<?php } ?>
</select>

<br><br>

<label>Team 2</label>
<select name="team2">
<?php
$result2 = $conn->query("SELECT team_name FROM userteams");
while($row = $result2->fetch_assoc()) { ?>
<option value="<?php echo $row['team_name']; ?>">
<?php echo $row['team_name']; ?>
</option>
<?php } ?>
</select>

<br><br>

<button type="submit">Start Match</button>

</form>
<!DOCTYPE html>
<html>
<head>
<title>Matchmaking</title>

<style>

body{
background:#0f172a;
color:white;
font-family:Arial;
display:flex;
flex-direction:column;
align-items:center;
justify-content:center;
height:100vh;
}

.container{
display:flex;
gap:40px;
align-items:center;
}

.box{
background:#020617;
width:220px;
height:220px;
border-radius:15px;
display:flex;
flex-direction:column;
justify-content:center;
align-items:center;
gap:15px;
}

select{
padding:8px;
border-radius:10px;
border:2px solid #38bdf8;
background:#0f172a;
color:white;
}

.vs{
font-size:48px;
font-weight:bold;
color:#22c55e;
}

button{
margin-top:30px;
padding:12px 30px;
border:none;
border-radius:10px;
background:#38bdf8;
font-weight:bold;
cursor:pointer;
}

button:hover{
background:#22c55e;
}

</style>

</head>

<body>



</body>
</html>