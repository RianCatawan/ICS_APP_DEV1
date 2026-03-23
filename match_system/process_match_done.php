<?php
session_start(); // Good practice to include
include "db.php";

if (isset($_POST['done_match'])) {
    // Sanitize and cast inputs
    $match_id = (int)$_POST['match_id'];
    $home_score = (int)$_POST['home_score'];
    $away_score = (int)$_POST['away_score'];

    // 1. Fetch team IDs
    $stmt = $conn->prepare("
        SELECT r.team_id as home_id, mr.challenger_team_id as away_id 
        FROM match_requests mr 
        JOIN reservations r ON mr.reservation_id = r.id 
        WHERE mr.id = ?
    ");
    $stmt->bind_param("i", $match_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $ids = $result->fetch_assoc();

    // Check if match exists before proceeding
    if (!$ids) {
        die("Error: Match record not found in database.");
    }

    // 2. Logic to decide winner_id
    $winner_id = 0; // 0 represents a Draw
    if ($home_score > $away_score) {
        $winner_id = $ids['home_id'];
    } elseif ($away_score > $home_score) {
        $winner_id = $ids['away_id'];
    }

    // 3. Update the match to 'completed' status
    $update = $conn->prepare("
        UPDATE match_requests 
        SET home_score = ?, 
            away_score = ?, 
            winner_id = ?, 
            status = 'completed' 
        WHERE id = ?
    ");
    $update->bind_param("iiii", $home_score, $away_score, $winner_id, $match_id);
    
    if ($update->execute()) {
        header("Location: index.php?msg=match_saved");
        exit();
    } else {
        // More descriptive error for debugging
        die("Critical Database Error: " . $conn->error);
    }
} else {
    // Redirect if they try to access this file directly without POST
    header("Location: index.php");
    exit();
}
?>