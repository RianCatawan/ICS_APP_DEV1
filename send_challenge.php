<?php
session_start();
include "db.php";

if(isset($_POST['challenge'])) {
    $res_id = $_POST['res_id'];
    $username = $_SESSION['username'];

    // Get the user's active team to send the challenge
    $stmt = $conn->prepare("SELECT active_team_id FROM players WHERE student_id = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $player = $stmt->get_result()->fetch_assoc();
    
    $my_team = $player['active_team_id'];

    if($my_team) {
        $send = $conn->prepare("INSERT INTO match_requests (reservation_id, challenger_team_id) VALUES (?, ?)");
        $send->bind_param("ii", $res_id, $my_team);
        $send->execute();
        echo "<script>alert('Challenge Sent!'); window.location.href='matchmaking.php';</script>";
    } else {
        echo "<script>alert('Please select an active team in your profile first!'); window.location.href='profile.php?sid=$username';</script>";
    }
}