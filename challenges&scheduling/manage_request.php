<?php
// When the Host clicks 'APPROVE'
if (isset($_POST['approve_match'])) {
    $match_id = $_POST['match_id'];
    $res_id = $_POST['reservation_id'];

    $conn->begin_transaction();
    try {
        // 1. Set match request to approved
        $stmt1 = $conn->prepare("UPDATE match_requests SET status = 'approved' WHERE id = ?");
        $stmt1->bind_param("i", $match_id);
        $stmt1->execute();

        // 2. IMPORTANT: Set reservation to 'matched' so it disappears from matchmaking
        $stmt2 = $conn->prepare("UPDATE reservations SET status = 'matched' WHERE id = ?");
        $stmt2->bind_param("i", $res_id);
        $stmt2->execute();

        // 3. (Optional) Reject all other pending challenges for this specific reservation
        $stmt3 = $conn->prepare("UPDATE match_requests SET status = 'rejected' WHERE reservation_id = ? AND status = 'pending'");
        $stmt3->bind_param("i", $res_id);
        $stmt3->execute();

        $conn->commit();
        echo "Match Confirmed! It is no longer visible to others.";
    } catch (Exception $e) {
        $conn->rollback();
        echo "Error: " . $e->getMessage();
    }
}
?>