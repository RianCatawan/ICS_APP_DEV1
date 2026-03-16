<?php
session_start();
include "db.php";

$req_id = $_GET['id'] ?? '';

// Fetch all match data: Teams, Reservation Info, and Approval Status
$query = "SELECT mr.*, 
          t1.team_name as home_team, t1.id as home_id,
          t2.team_name as challenger_team, t2.id as challenger_id,
          r.reservation_date, r.selected_time
          FROM match_requests mr
          JOIN reservations r ON mr.reservation_id = r.id
          JOIN teams t1 ON r.team_id = t1.id
          JOIN teams t2 ON mr.challenger_team_id = t2.id
          WHERE mr.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $req_id);
$stmt->execute();
$match = $stmt->get_result()->fetch_assoc();

if (!$match) { die("Match not found."); }

// Handle the "Approve" button click
if (isset($_POST['click_approve'])) {
    $user_team = $_POST['user_team_id'];
    
    if ($user_team == $match['home_id']) {
        $update = $conn->prepare("UPDATE match_requests SET home_approved = 1 WHERE id = ?");
    } else {
        $update = $conn->prepare("UPDATE match_requests SET challenger_approved = 1 WHERE id = ?");
    }
    $update->bind_param("i", $req_id);
    $update->execute();
    header("Refresh:0");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Match Confirmation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #0d47a1; color: white; padding-top: 50px; font-family: 'Segoe UI', sans-serif; }
        .match-box { background: rgba(0,0,0,0.5); border: 2px solid #FFD700; border-radius: 20px; padding: 40px; text-align: center; }
        .vs-divider { font-size: 3rem; font-weight: bold; color: #FFD700; }
        .ready-badge { background: #28a745; color: white; padding: 5px 15px; border-radius: 20px; font-size: 0.8rem; }
        .waiting-badge { background: #dc3545; color: white; padding: 5px 15px; border-radius: 20px; font-size: 0.8rem; }
    </style>
</head>
<body>

<div class="container match-box">
    <h1 class="mb-4">FINAL CONFIRMATION</h1>
    <h4 class="text-warning"><?php echo $match['reservation_date']; ?> @ <?php echo $match['selected_time']; ?></h4>
    <hr style="border-color: rgba(255,255,255,0.2)">

    <div class="row align-items-center my-5">
        <div class="col-md-5">
            <h2 class="display-6"><?php echo strtoupper($match['home_team']); ?></h2>
            <?php echo $match['home_approved'] ? '<span class="ready-badge">READY</span>' : '<span class="waiting-badge">NOT READY</span>'; ?>
        </div>

        <div class="col-md-2 vs-divider">VS</div>

        <div class="col-md-5">
            <h2 class="display-6"><?php echo strtoupper($match['challenger_team']); ?></h2>
            <?php echo $match['challenger_approved'] ? '<span class="ready-badge">READY</span>' : '<span class="waiting-badge">NOT READY</span>'; ?>
        </div>
    </div>

    <?php 
    // Logic to show the button only to the correct user
    $current_user_sid = $_SESSION['username'];
    $check_player = $conn->prepare("SELECT active_team_id FROM players WHERE student_id = ?");
    $check_player->bind_param("s", $current_user_sid);
    $check_player->execute();
    $p_data = $check_player->get_result()->fetch_assoc();
    $active_id = $p_data['active_team_id'];

    if ($active_id == $match['home_id'] || $active_id == $match['challenger_id']): 
        $already_approved = ($active_id == $match['home_id'] && $match['home_approved']) || ($active_id == $match['challenger_id'] && $match['challenger_approved']);
    ?>
        <?php if (!$already_approved): ?>
            <form method="POST">
                <input type="hidden" name="user_team_id" value="<?php echo $active_id; ?>">
                <button type="submit" name="click_approve" class="btn btn-warning btn-lg px-5 fw-bold">APPROVE MATCH</button>
            </form>
        <?php else: ?>
            <button class="btn btn-success btn-lg disabled">YOU ARE READY</button>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($match['home_approved'] && $match['challenger_approved']): ?>
        <div class="mt-5 p-4 border border-success rounded bg-success bg-opacity-10">
            <h3>🔥 MATCH IS LIVE!</h3>
            <p>Both teams have confirmed. Go to the court and play!</p>
            <a href="matchmaking.php" class="btn btn-light mt-2">Back to Dashboard</a>
        </div>
    <?php endif; ?>
</div>

</body>
</html>