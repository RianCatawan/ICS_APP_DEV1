<?php
session_start();
include(__DIR__ . '/../database&config/db.php');

$match_id = $_POST['match_id'] ?? null;
$home_score = $_POST['h_score'] ?? 0;
$away_score = $_POST['a_score'] ?? 0;

if (!$match_id) {
    die("Invalid match.");
}

// Get team IDs
$q = $conn->prepare("
    SELECT r.team_id AS home_id, mr.challenger_team_id AS away_id
    FROM match_requests mr
    JOIN reservations r ON mr.reservation_id = r.id
    WHERE mr.id = ?
");
$q->bind_param("i", $match_id);
$q->execute();
$data = $q->get_result()->fetch_assoc();

$winner_id = 0;

if ($home_score > $away_score) {
    $winner_id = $data['home_id'];
} elseif ($away_score > $home_score) {
    $winner_id = $data['away_id'];
}

// Update match
$update = $conn->prepare("
    UPDATE match_requests 
    SET home_score=?, away_score=?, winner_id=?, final_status='confirmed'
    WHERE id=?
");
$update->bind_param("iiii", $home_score, $away_score, $winner_id, $match_id);
$update->execute();

// Redirect to history
header("Location: /ICS_APP_DEV1/Teams&history1/battle_history.php");
exit();