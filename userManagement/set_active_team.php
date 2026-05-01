<?php
session_start();
require_once __DIR__ . '/../database_config/db.php';

$team_id = $_GET['team_id'] ?? 0;
$sid = $_SESSION['username'] ?? '';

if ($team_id > 0 && !empty($sid)) {
    // Update the player's active team in the database
    $stmt = $conn->prepare("UPDATE players SET active_team_id = ? WHERE student_id = ?");
    $stmt->bind_param("is", $team_id, $sid);
    $stmt->execute();
}

// Redirect to the matchmaking page
header("Location: /basketball/challenges&scheduling/matchmaking.php");
exit();