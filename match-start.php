<?php
session_start();
include "db.php"; // Database connection file

if(!isset($_SESSION['username'])){
    header("Location: start-match.php");
    exit;
}

$user = $_SESSION['username'];

// Insert user if not exists
$stmt = $conn->prepare("INSERT IGNORE INTO users (username) VALUES (?)");
$stmt->bind_param("s", $user);
$stmt->execute();
$stmt->close();

// Get user ID
$result = $conn->query("SELECT id FROM users WHERE username='$user'");
$row = $result->fetch_assoc();
$user_id = $row['id'];

// Handle team creation
if(isset($_POST['createTeam'])){
    $team_name = trim($_POST['teamName']);
    $team_captain = trim($_POST['teamCaptain']);

    $stmt = $conn->prepare("INSERT INTO teams (user_id, team_name, team_captain) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $team_name, $team_captain);
    $stmt->execute();
    $stmt->close();

    header("Location: select-court.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Create Team</title>
<style>
body { background:#0f172a; color:#e5e7eb; font-family: Arial; display:flex; justify-content:center; align-items:center; height:100vh;}
.team-card { background:#020617; border-radius:16px; padding:25px; width:400px; display:flex; flex-direction:column; gap:20px; }
input { padding:12px; border-radius:12px; border:2px solid #38bdf8; background:#0f172a; color:#e5e7eb;}
button { padding:12px; border:none; border-radius:12px; font-weight:bold; background:#38bdf8; color:#020617; cursor:pointer;}
button:hover{background:#22c55e;color:#fff;}
</style>
</head>
<body>
<div class="team-card">
    <h2>Create Team</h2>
    <form method="POST">
        <input type="text" name="teamName" placeholder="Team Name" required>
        <input type="text" name="teamCaptain" placeholder="Team Captain" required>
        <button type="submit" name="createTeam">Create Team</button>
    </form>
</div>
</body>
</html>