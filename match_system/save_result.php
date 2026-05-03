<?php
session_start();
require_once __DIR__ . '/../database_config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $match_id = intval($_POST['match_id']);
    $h_score  = intval($_POST['h_score']);
    $a_score  = intval($_POST['a_score']);
    $h_id     = intval($_POST['h_id']);
    $a_id     = intval($_POST['a_id']);

    $winner_id = ($h_score > $a_score) ? $h_id : (($a_score > $h_score) ? $a_id : 0);

    $stmt = $conn->prepare("UPDATE match_requests 
                            SET home_score = ?, away_score = ?, winner_id = ?, final_status = 'confirmed' 
                            WHERE id = ?");
    $stmt->bind_param("iiii", $h_score, $a_score, $winner_id, $match_id);

    if ($stmt->execute()) {
        // Redirect to Battle History
        header("Location: ../Teams&history1/battle_history.php");
    } else {
        echo "Error: " . $conn->error;
    }
    exit();
}