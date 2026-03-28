<?php
session_start();
include(__DIR__ . '/../database_config/db.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Get the data from the form
    $match_id = $_POST['match_id'];
    $h_score = (int)$_POST['h_score'];
    $a_score = (int)$_POST['a_score'];

    // 2. Determine who the winner is before updating
    // We need to fetch the actual Team IDs from the database first
    $get_teams = "SELECT r.team_id AS h_id, mr.challenger_team_id AS a_id 
                  FROM match_requests mr 
                  JOIN reservations r ON mr.reservation_id = r.id 
                  WHERE mr.id = ?";
    $stmt = $conn->prepare($get_teams);
    $stmt->bind_param("i", $match_id);
    $stmt->execute();
    $teams = $stmt->get_result()->fetch_assoc();

    $winner_id = 0; // Default to 0 for a Draw
    if ($h_score > $a_score) {
        $winner_id = $teams['h_id']; // Home Team Wins
    } elseif ($a_score > $h_score) {
        $winner_id = $teams['a_id']; // Away Team Wins
    }

    // 3. THE CRITICAL UPDATE:
    // We update home_score, away_score, winner_id AND set final_status to 'confirmed'
    $update_sql = "UPDATE match_requests SET 
                    home_score = ?, 
                    away_score = ?, 
                    winner_id = ?, 
                    final_status = 'confirmed' 
                   WHERE id = ?";

    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("iiii", $h_score, $a_score, $winner_id, $match_id);

    if ($update_stmt->execute()) {
        // Success! Send the user to the history page to see the result
        header("Location: battle_history.php");
        exit();
    } else {
        echo "Database Error: " . $conn->error;
    }
} else {
    // If someone tries to access this file directly, send them back
    header("Location: admin.php");
    exit();
}
?>