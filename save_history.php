<?php
session_start();
include "db.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $match_id = $_POST['match_id'];
    $home_score = (int)$_POST['home_final_score'];
    $away_score = (int)$_POST['away_final_score'];
    $user_sid = $_SESSION['username'];

    // 1. Determine the Winner
    $winner_id = 0; // Default for Draw
    if ($home_score > $away_score) {
        // Fetch the home_team_id
        $stmt = $conn->prepare("SELECT r.team_id FROM match_requests mr JOIN reservations r ON mr.reservation_id = r.id WHERE mr.id = ?");
        $stmt->bind_param("i", $match_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $winner_id = $res['team_id'];
    } elseif ($away_score > $home_score) {
        // Fetch the challenger_team_id
        $stmt = $conn->prepare("SELECT challenger_team_id FROM match_requests WHERE id = ?");
        $stmt->bind_param("i", $match_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $winner_id = $res['challenger_team_id'];
    }

    // 2. Update match_requests with final scores and set status to 'completed'
    // We use the 'status' column to move it out of the Live list
    $update = $conn->prepare("UPDATE match_requests SET 
                                home_score = ?, 
                                away_score = ?, 
                                winner_id = ?, 
                                status = 'completed' 
                              WHERE id = ?");
    $update->bind_param("iiii", $home_score, $away_score, $winner_id, $match_id);
    
    if ($update->execute()) {
        // 3. Optional: Mark the reservation as 'closed' so the court is free
        $res_update = $conn->prepare("UPDATE reservations SET status = 'finished' 
                                      WHERE id = (SELECT reservation_id FROM match_requests WHERE id = ?)");
        $res_update->bind_param("i", $match_id);
        $res_update->execute();

        header("Location: profile.php?sid=" . $user_sid . "&msg=Match Saved Successfully");
    } else {
        echo "Error saving match: " . $conn->error;
    }
}
?>