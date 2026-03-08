<?php
session_start();
include "db.php";

if(!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin'){
    header("Location: index.php");
    exit();
}

# COUNT USERS
$user_count = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];

# COUNT TEAMS
$team_count = $conn->query("SELECT COUNT(*) as total FROM userteams")->fetch_assoc()['total'];

# COUNT MATCHES
$match_count = $conn->query("SELECT COUNT(*) as total FROM matches")->fetch_assoc()['total'];

# ACTIVE USERS
$active_users = $conn->query("SELECT COUNT(DISTINCT user_id) as total FROM user_logs WHERE action='LOGIN'")
->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html>
<head>
<title>Admin Dashboard</title>

<style>

body{
background:#0f172a;
font-family:Arial;
color:white;
margin:0;
padding:40px;
}

.dashboard-title{
font-size:28px;
margin-bottom:30px;
}

.cards{
display:flex;
gap:20px;
margin-bottom:40px;
}

.card{
flex:1;
padding:25px;
border-radius:10px;
color:white;
font-weight:bold;
text-align:center;
}

.blue{background:#2563eb;}
.green{background:#16a34a;}
.orange{background:#f59e0b;}
.red{background:#ef4444;}

.table-box{
background:#1e293b;
padding:20px;
border-radius:10px;
}

a{
display:inline-block;
margin:10px;
padding:10px 20px;
background:#38bdf8;
border-radius:8px;
text-decoration:none;
color:white;
}

a:hover{
background:#22c55e;
}

table{
width:100%;
margin-top:20px;
border-collapse:collapse;
}

th,td{
padding:10px;
border-bottom:1px solid #334155;
}

</style>
</head>

<body>

<h2 class="dashboard-title">ADMIN DASHBOARD</h2>

<div class="cards">

<div class="card blue">
Total Users<br>
<h1><?php echo $user_count; ?></h1>
</div>

<div class="card green">
Total Teams<br>
<h1><?php echo $team_count; ?></h1>
</div>

<div class="card orange">
Total Matches<br>
<h1><?php echo $match_count; ?></h1>
</div>

<div class="card red">
Active Users<br>
<h1><?php echo $active_users; ?></h1>
</div>

</div>

<div class="table-box">

<h3>Recent Matches</h3>

<table>

<tr>
<th>ID</th>
<th>Team 1</th>
<th>Team 2</th>
<th>Date</th>
</tr>

<?php
$matches = $conn->query("
SELECT m.*, t1.team_name as team1, t2.team_name as team2
FROM matches m
LEFT JOIN userteams t1 ON m.team1_id = t1.id
LEFT JOIN userteams t2 ON m.team2_id = t2.id
ORDER BY m.id DESC
LIMIT 5
");

while($row = $matches->fetch_assoc()){
echo "<tr>
<td>{$row['id']}</td>
<td>{$row['team1']}</td>
<td>{$row['team2']}</td>
<td>{$row['match_time']}</td>
</tr>";
}
?>
</table>

</div>
<br>

<a href="manage_users.php">Manage Users</a>
<a href="matches.php">View Matches</a>
<a href="user_logs.php">User Logs</a>
<a href="logout.php">Logout</a>

</body>
</html>