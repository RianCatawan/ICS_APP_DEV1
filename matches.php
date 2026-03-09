<?php
include "db.php";
?>

<!DOCTYPE html>
<html>
<head>
<title>All Matches | HoopMatch</title>
<style>
body{
    background:#0f172a; /* dashboard guide background */
    font-family:Arial;
    color:white;
    margin:0;
    padding:40px;
    position:relative;
}

/* PAGE TITLE */
.page-title{
    font-size:28px;
    margin-bottom:30px;
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
    border:2px solid #38bdf8;
    text-decoration:none;
    font-weight:bold;
}

.back-btn:hover{
    background:#38bdf8;
    color:black;
}

/* TABLE BOX */
.table-box{
    background:#1e293b;
    padding:20px;
    border-radius:10px;
}

/* TABLE STYLING */
table{
    width:100%;
    border-collapse:collapse;
    margin-top:10px;
}

th, td{
    padding:10px;
    border-bottom:1px solid #334155;
    text-align:left;
}

th{
    font-weight:bold;
}

</style>
</head>
<body>

<!-- BACK BUTTON -->
<a href="admin.php" class="back-btn">← Back</a>

<h2 class="page-title">All Matches</h2>

<div class="table-box">
<table>
<tr>
<th>ID</th>
<th>Team 1</th>
<th>Team 2</th>
<th>Date</th>
<th>Status</th>
</tr>

<?php
$sql = "
SELECT m.id, t1.team_name AS team1, t2.team_name AS team2, m.match_time, m.status
FROM matches m
LEFT JOIN userteams t1 ON m.team1_id = t1.id
LEFT JOIN userteams t2 ON m.team2_id = t2.id
ORDER BY m.match_time DESC
";

$result = $conn->query($sql);

while($row = $result->fetch_assoc()){
    echo "<tr>
    <td>{$row['id']}</td>
    <td>{$row['team1']}</td>
    <td>{$row['team2']}</td>
    <td>{$row['match_time']}</td>
    <td>{$row['status']}</td>
    </tr>";
}
?>
</table>
</div>

</body>
</html>