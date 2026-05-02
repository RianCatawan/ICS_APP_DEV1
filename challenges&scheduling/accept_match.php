<?php
session_start();
require_once __DIR__ . '/../database_config/db.php';

if (!isset($_SESSION['username'])) { die("Log in first."); }

$current_user = $_SESSION['username'];
$request_id   = isset($_GET['id']) ? intval($_GET['id']) : 0;
$action       = isset($_GET['action']) ? $_GET['action'] : '';

// 1. Fetch match details
$stmt = $conn->prepare("SELECT mr.*, t1.created_by as home_owner, t2.created_by as away_owner 
                        FROM match_requests mr
                        JOIN reservations r ON mr.reservation_id = r.id
                        JOIN teams t1 ON r.team_id = t1.id
                        JOIN teams t2 ON mr.challenger_team_id = t2.id
                        WHERE mr.id = ?");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$match = $stmt->get_result()->fetch_assoc();

if (!$match) { die("Match not found."); }

// 2. Handle Actions
if ($action === 'decline') {
    $conn->query("UPDATE match_requests SET status = 'rejected' WHERE id = $request_id");
    header("Location: ../userManagement/profile.php");
} elseif ($action === 'accept') {
    if ($current_user === $match['home_owner']) {
        $conn->query("UPDATE match_requests SET home_approved = 1 WHERE id = $request_id");
    }
    if ($current_user === $match['away_owner']) {
        $conn->query("UPDATE match_requests SET challenger_approved = 1 WHERE id = $request_id");
    }
    // Redirect to your VS Arena page
    header("Location: ../challenges&scheduling/confirmation_match.php?id=$request_id");
}
exit();