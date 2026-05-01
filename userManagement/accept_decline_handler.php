<?php
session_start();
require_once __DIR__ . '/../database_config/db.php';

// 1. Basic Security Checks
if (!isset($_SESSION['username'])) {
    die("Please log in first.");
}

$current_user = $_SESSION['username'];
$request_id   = isset($_GET['id']) ? intval($_GET['id']) : 0;
$action       = isset($_GET['action']) ? $_GET['action'] : '';

if ($request_id <= 0 || !in_array($action, ['accept', 'decline'])) {
    die("Invalid request parameters.");
}

// 2. Fetch the match request details to see who is the owner
$query = "
    SELECT mr.*, t1.created_by as home_owner, t2.created_by as away_owner 
    FROM match_requests mr
    JOIN reservations r ON mr.reservation_id = r.id
    JOIN teams t1 ON r.team_id = t1.id
    JOIN teams t2 ON mr.challenger_team_id = t2.id
    WHERE mr.id = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $request_id);
$stmt->execute();
$match = $stmt->get_result()->fetch_assoc();

if (!$match) {
    die("Match request not found.");
}

// 3. Handle DECLINE Action
if ($action === 'decline') {
    // Check if the user is either the Home or Away owner
    if ($current_user === $match['home_owner'] || $current_user === $match['away_owner']) {
        $update = $conn->prepare("UPDATE match_requests SET status = 'rejected' WHERE id = ?");
        $update->bind_param("i", $request_id);
        $update->execute();
        echo "<script>alert('Match request declined.'); window.location.href='/basketball/userManagement/profile.php';</script>";
    } else {
        die("Unauthorized action.");
    }
    exit();
}

// 4. Handle ACCEPT Action
if ($action === 'accept') {
    if ($current_user === $match['home_owner']) {
        // Logged-in user is the Home Team owner
        $update = $conn->prepare("UPDATE match_requests SET home_approved = 1 WHERE id = ?");
    } elseif ($current_user === $match['away_owner']) {
        // Logged-in user is the Challenger/Away Team owner
        $update = $conn->prepare("UPDATE match_requests SET challenger_approved = 1 WHERE id = ?");
    } else {
        die("Unauthorized action.");
    }
    
    if ($update->execute()) {
        // Check if BOTH have now approved to set status to 'confirmed'
        // We re-fetch or check current values
        $check = $conn->prepare("SELECT home_approved, challenger_approved FROM match_requests WHERE id = ?");
        $check->bind_param("i", $request_id);
        $check->execute();
        $res = $check->get_result()->fetch_assoc();
        
        if ($res['home_approved'] == 1 && $res['challenger_approved'] == 1) {
            $status_upd = $conn->prepare("UPDATE match_requests SET status = 'confirmed' WHERE id = ?");
            $status_upd->bind_param("i", $request_id);
            $status_upd->execute();
            echo "<script>alert('Match Confirmed! Both teams have accepted.');</script>";
        } else {
            echo "<script>alert('Approval saved. Waiting for the other team.');</script>";
        }
    }
    
    echo "<script>window.location.href='/basketball/userManagement/profile.php';</script>";
}
?>