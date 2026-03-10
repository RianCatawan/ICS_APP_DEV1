<?php
session_start();
include "db.php";

if(!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin'){
    header("Location: index.php");
    exit();
}

$teams = $conn->query("SELECT * FROM userteams ORDER BY id ASC");
?>

<!DOCTYPE html>
<html>
<head>
<title>View All Teams | HoopMatch</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body{
    background:#0f172a;
    color:white;
    font-family:Arial;
    margin:0;
    padding:40px;
}

.page-title{
    font-size:28px;
    margin-bottom:30px;
}

.back-btn{
    display:inline-block;
    margin-bottom:20px;
    padding:10px 18px;
    background:#38bdf8;
    color:white;
    border-radius:8px;
    text-decoration:none;
    font-weight:bold;
}

.back-btn:hover{
    background:#22c55e;
    color:white;
}

.table-box{
    background:#1e293b;
    padding:20px;
    border-radius:10px;
}

table{
    width:100%;
    border-collapse:collapse;
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

<a href="admin.php" class="back-btn">← Back to Dashboard</a>

<h2 class="page-title">All Teams</h2>

<div class="table-box">
<table class="table table-dark table-striped">
<tr>
<th>ID</th>
<th>Team Name</th>
</tr>

<?php
if($teams->num_rows > 0){
    while($row = $teams->fetch_assoc()){
        echo "<tr>
            <td>{$row['id']}</td>
            <td>{$row['team_name']}</td>
        </tr>";
    }
}else{
    echo "<tr><td colspan='2'>No teams found</td></tr>";
}
?>
</table>
</div>

</body>
</html>