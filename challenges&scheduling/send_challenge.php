<?php
session_start();
include(__DIR__ . '/../database_config/db.php');

if (!isset($_SESSION['username'])) {
    header("Location: /basketball/authentication/login.php");
    exit();
}

$username = $_SESSION['username'];
$res_id   = isset($_GET['res_id']) ? intval($_GET['res_id']) : 0;

// GET the challenger team from the URL instead of the database profile
$my_team  = isset($_GET['challenger_id']) ? intval($_GET['challenger_id']) : 0;

if ($res_id <= 0 || $my_team <= 0) {
    echo "<script>alert('Error: No team selected. Please select a team from your profile.'); window.location.href='/basketball/userManagement/profile.php';</script>";
    exit();
}

// 1. Prevent challenging your own reservation
$own_check = $conn->prepare("SELECT t.created_by FROM reservations r JOIN teams t ON r.team_id = t.id WHERE r.id = ?");
$own_check->bind_param("i", $res_id);
$own_check->execute();
$res_owner = $own_check->get_result()->fetch_assoc();

if ($res_owner['created_by'] === $username) {
    echo "<script>alert('You cannot challenge your own reservation!'); window.location.href='matchmaking.php';</script>";
    exit();
}

// 2. Prevent duplicate challenge
$dup_check = $conn->prepare("SELECT id FROM match_requests WHERE reservation_id = ? AND challenger_team_id = ? AND status != 'rejected'");
$dup_check->bind_param("ii", $res_id, $my_team);
$dup_check->execute();
if ($dup_check->get_result()->num_rows > 0) {
    echo "<script>alert('You already sent a challenge for this reservation!'); window.location.href='matchmaking.php';</script>";
    exit();
}

// 3. Insert the challenge
$send = $conn->prepare("INSERT INTO match_requests (reservation_id, challenger_team_id, status, home_approved, challenger_approved) VALUES (?, ?, 'pending', 0, 0)");
$send->bind_param("ii", $res_id, $my_team);

if ($send->execute()) {
    echo "<script>alert('⚡ Challenge Sent! Waiting for the opponent to accept.'); window.location.href='matchmaking.php';</script>";
} else {
    echo "<script>alert('Error sending challenge.'); window.location.href='matchmaking.php';</script>";
}
?>