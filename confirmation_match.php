<?php
session_start();
include "db.php";

$req_id = $_GET['id'] ?? '';
$current_user_sid = $_SESSION['username'] ?? '';

if (empty($req_id) || empty($current_user_sid)) {
    die("Unauthorized access.");
}

$query = "SELECT mr.*, 
          t1.team_name as home_team, t1.created_by as home_owner,
          t2.team_name as challenger_team, t2.created_by as challenger_owner,
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

if (!$match) die("Match not found.");

if (isset($_POST['click_approve'])) {
    if ($current_user_sid == $match['home_owner']) {
        $conn->query("UPDATE match_requests SET home_approved = 1 WHERE id = $req_id");
        $match['home_approved'] = 1;
    } elseif ($current_user_sid == $match['challenger_owner']) {
        $conn->query("UPDATE match_requests SET challenger_approved = 1 WHERE id = $req_id");
        $match['challenger_approved'] = 1;
    }

    if ($match['home_approved'] && $match['challenger_approved']) {
        header("Location: match_control.php?match_id=" . $req_id);
    } else {
        header("Location: confirmation_match.php?id=" . $req_id);
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Confirmation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #0d47a1; color: white; text-align: center; padding-top: 50px; }
        .box { 
            background: rgba(0,0,0,0.4); 
            border: 2px solid #FFD700; 
            border-radius: 20px; 
            padding: 40px; 
            backdrop-filter: blur(10px); 
            position: relative; /* Needed for absolute positioning of the back button */
        }
        .vs { font-size: 3rem; font-weight: bold; color: #FFD700; }
        
        /* BACK BUTTON STYLE */
        .back-btn {
            position: absolute;
            top: 20px;
            left: 20px;
            text-decoration: none;
            color: #FFD700;
            font-weight: bold;
            font-size: 0.9rem;
            border: 1px solid #FFD700;
            padding: 5px 15px;
            border-radius: 5px;
            transition: 0.3s;
        }
        .back-btn:hover {
            background: #FFD700;
            color: #000;
        }
    </style>
</head>
<body>

<div class="container box">
    <a href="profile.php?sid=<?php echo $current_user_sid; ?>" class="back-btn">
        <i class="bi bi-arrow-left"></i> BACK
    </a>

    <h2 class="text-warning">MATCH CONFIRMATION</h2>
    <div class="row my-5 align-items-center">
        <div class="col-5">
            <h3><?php echo htmlspecialchars($match['home_team']); ?></h3>
            <?php echo $match['home_approved'] ? '<span class="text-success">✅ READY</span>' : '<span class="text-white-50">⏳ PENDING</span>'; ?>
        </div>
        <div class="col-2 vs">VS</div>
        <div class="col-5">
            <h3><?php echo htmlspecialchars($match['challenger_team']); ?></h3>
            <?php echo $match['challenger_approved'] ? '<span class="text-success">✅ READY</span>' : '<span class="text-white-50">⏳ PENDING</span>'; ?>
        </div>
    </div>
    
    <div class="mb-4">
        <p class="mb-1 text-white-50">Schedule:</p>
        <h5><?php echo date('M d, Y', strtotime($match['reservation_date'])); ?> @ <?php echo $match['selected_time']; ?></h5>
    </div>

    <form method="POST">
        <button type="submit" name="click_approve" class="btn btn-warning btn-lg fw-bold px-5">APPROVE MATCH</button>
    </form>
</div>

</body>
</html>