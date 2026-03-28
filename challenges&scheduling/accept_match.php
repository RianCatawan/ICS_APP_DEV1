<?php
session_start();
include(__DIR__ . '/../database_config/db.php');

$request_id = $_GET['id'] ?? '';
$action = $_GET['action'] ?? '';

if (empty($request_id)) {
    die("Request ID missing.");
}

if ($action === 'accept') {
    // 1. Update request to 'accepted'
    $stmt = $conn->prepare("UPDATE match_requests SET status = 'accepted' WHERE id = ?");
    $stmt->bind_param("i", $request_id);
    
    if ($stmt->execute()) {
        // 2. Redirect to the confirmation page
        header("Location: confirmation_match.php?id=" . $request_id);
        exit();
    }
} elseif ($action === 'decline') {
    $stmt = $conn->prepare("UPDATE match_requests SET status = 'declined' WHERE id = ?");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    header("Location: profile.php?sid=" . $_SESSION['username']);
    exit();
}
?>