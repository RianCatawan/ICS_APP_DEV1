<?php
session_start();
include(__DIR__ . '/../database_config/db.php');

if (isset($_POST['match_id']) && isset($_POST['reservation_id'])) {
    $match_id = $_POST['match_id'];
    $res_id = $_POST['reservation_id'];

    $conn->begin_transaction();
    try {
        // 1. Approve this challenger
        $stmt1 = $conn->prepare("UPDATE match_requests SET home_approved = 1, challenger_approved = 1 WHERE id = ?");
        $stmt1->bind_param("i", $match_id);
        $stmt1->execute();

        // 2. SET RESERVATION TO MATCHED
        $stmt2 = $conn->prepare("UPDATE reservations SET status = 'matched' WHERE id = ?");
        $stmt2->bind_param("i", $res_id);
        $stmt2->execute();

        // 3. Reject other challengers for the same booking
        $stmt3 = $conn->prepare("UPDATE match_requests SET home_approved = 0 WHERE reservation_id = ? AND id != ?");
        $stmt3->bind_param("ii", $res_id, $match_id);
        $stmt3->execute();

        $conn->commit();
        echo "<script>alert('Match Confirmed!'); window.location.href='/basketball/userManagement/profile.php';</script>";
    } catch (Exception $e) {
        $conn->rollback();
        echo "Error: " . $e->getMessage();
    }
} else {
    header("Location: /basketball/userManagement/profile.php");
}
?>