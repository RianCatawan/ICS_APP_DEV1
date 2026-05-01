<?php
session_start();
include(__DIR__ . '/../database_config/db.php');

if (!isset($_SESSION['username'])) {
    header("Location: /basketball/authentication/login.php");
    exit();
}

$username = $_SESSION['username'];
$res_id   = isset($_GET['res_id']) ? intval($_GET['res_id']) : 0;

// NEW LOGIC: Get team ID from URL or Session, NOT the database table
$my_team = isset($_GET['challenger_team_id']) ? intval($_GET['challenger_team_id']) : ($_SESSION['selected_team_id'] ?? 0);

if ($res_id <= 0) {
    echo "<script>alert('Invalid reservation.'); window.location.href='matchmaking.php';</script>";
    exit();
}

if ($my_team <= 0) {
    echo "<script>alert('Please go back to your profile and click Find Match on a team.'); window.location.href='/basketball/userManagement/profile.php';</script>";
    exit();
}

// --- Rest of your code (Owner check, Duplicate check, Insert) stays the same ---

// Prevent challenging your own reservation
$own_check = $conn->prepare("SELECT t.created_by FROM reservations r JOIN teams t ON r.team_id = t.id WHERE r.id = ?");
$own_check->bind_param("i", $res_id);
$own_check->execute();
$res_owner = $own_check->get_result()->fetch_assoc();

if ($res_owner['created_by'] === $username) {
    echo "<script>alert('You cannot challenge your own reservation!'); window.location.href='matchmaking.php';</script>";
    exit();
}

// ... (Continue with your INSERT query using $my_team)