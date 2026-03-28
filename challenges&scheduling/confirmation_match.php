<?php
session_start();
include(__DIR__ . '/../database&config/db.php');

$req_id = $_GET['id'] ?? '';
$current_user_sid = $_SESSION['username'] ?? '';

if (empty($req_id) || empty($current_user_sid)) {
    die("Unauthorized access.");
}

// ================= FETCH MATCH =================
$query = "SELECT mr.*, 
          t1.team_name as home_team, t1.created_by as home_owner, t1.team_photo as home_photo,
          t2.team_name as challenger_team, t2.created_by as challenger_owner, t2.team_photo as challenger_photo,
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

// ================= APPROVE LOGIC =================
if (isset($_POST['click_approve'])) {
    if ($current_user_sid == $match['home_owner']) {
        $conn->query("UPDATE match_requests SET home_approved = 1 WHERE id = $req_id");
        $match['home_approved'] = 1;
    } 
    elseif ($current_user_sid == $match['challenger_owner']) {
        $conn->query("UPDATE match_requests SET challenger_approved = 1 WHERE id = $req_id");
        $match['challenger_approved'] = 1;
    }

    // Check if both approved AFTER the update
    if ($match['home_approved'] && $match['challenger_approved']) {
        header("Location: confirmation_match.php?id=$req_id&done=1");
    } else {
        header("Location: confirmation_match.php?id=$req_id");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Match Confirmation | NBSC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800&family=Plus+Jakarta+Sans:wght@400;500;700&display=swap');

        :root {
            --brand-primary: #0A192F;    
            --brand-accent: #FFB800;     
            --brand-success: #00E676;
            --bg-body: #06101f;          
            --surface-card: rgba(255, 255, 255, 0.03);     
            --radius-lg: 24px;
        }

        body {
            background-color: var(--bg-body);
            background-image: radial-gradient(circle at 50% 50%, #102a4d 0%, #06101f 100%);
            color: white;
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .confirmation-box {
            background: var(--surface-card);
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-radius: var(--radius-lg);
            padding: 60px 40px;
            width: 100%;
            max-width: 900px;
            text-align: center;
            position: relative;
            box-shadow: 0 25px 50px rgba(0,0,0,0.5);
        }

        .back-btn {
            position: absolute;
            top: 30px;
            left: 30px;
            color: rgba(255,255,255,0.6);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: 0.3s;
        }

        .back-btn:hover { color: var(--brand-accent); }

        .vs-badge {
            font-family: 'Outfit', sans-serif;
            font-size: 3.5rem;
            font-weight: 900;
            color: var(--brand-accent);
            text-shadow: 0 0 20px rgba(255, 184, 0, 0.4);
            margin: 0 20px;
        }

        .team-display h3 {
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            font-size: 1.8rem;
            margin-bottom: 10px;
        }

        .status-pill {
            display: inline-block;
            padding: 6px 20px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .status-ready { background: rgba(0, 230, 118, 0.15); color: var(--brand-success); border: 1px solid var(--brand-success); }
        .status-pending { background: rgba(255, 255, 255, 0.05); color: rgba(255, 255, 255, 0.4); border: 1px solid rgba(255, 255, 255, 0.1); }

        .schedule-info {
            background: rgba(255, 255, 255, 0.05);
            padding: 20px;
            border-radius: 16px;
            margin: 40px auto;
            max-width: 400px;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .btn-approve {
            background: var(--brand-accent);
            color: var(--brand-primary);
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            padding: 18px 60px;
            border-radius: 12px;
            border: none;
            font-size: 1.1rem;
            transition: 0.3s;
            box-shadow: 0 10px 20px rgba(255, 184, 0, 0.2);
        }

        .btn-approve:hover:not(:disabled) {
            transform: translateY(-3px);
            background: white;
            box-shadow: 0 15px 30px rgba(255, 255, 255, 0.2);
        }

        .btn-approve:disabled {
            background: var(--brand-success);
            color: var(--brand-primary);
            opacity: 1;
        }
    </style>
</head>
<body>

<div class="confirmation-box">
    <a href="/ICS_APP_DEV1/userManagement/profile.php?sid=<?= $current_user_sid ?>" class="back-btn">
        <i class="bi bi-arrow-left"></i> BACK TO PROFILE
    </a>

    <?php if(isset($_GET['done']) && $_GET['done'] == 1): ?>
    <div class="alert alert-success bg-success border-0 text-white mb-4">
        <i class="bi bi-check-circle-fill me-2"></i> Match fully approved and scheduled!
    </div>
    <?php endif; ?>

    <h6 class="text-white-50 mb-5" style="letter-spacing: 4px; font-weight: 700;">MATCH AUTHORIZATION</h6>

    <div class="row align-items-center">
        <div class="col-md-5 team-display">
            <h3><?= htmlspecialchars($match['home_team']); ?></h3>
            <?php if($match['home_approved']): ?>
                <div class="status-pill status-ready"><i class="bi bi-check-circle"></i> READY</div>
            <?php else: ?>
                <div class="status-pill status-pending"><i class="bi bi-clock"></i> PENDING</div>
            <?php endif; ?>
        </div>

        <div class="col-md-2">
            <div class="vs-badge">VS</div>
        </div>

        <div class="col-md-5 team-display">
            <h3><?= htmlspecialchars($match['challenger_team']); ?></h3>
            <?php if($match['challenger_approved']): ?>
                <div class="status-pill status-ready"><i class="bi bi-check-circle"></i> READY</div>
            <?php else: ?>
                <div class="status-pill status-pending"><i class="bi bi-clock"></i> PENDING</div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="schedule-info">
        <small class="d-block text-white-50 mb-2" style="font-weight: 700; font-size: 0.7rem; text-transform: uppercase;">Final Schedule</small>
        <h5 class="m-0" style="font-weight: 800; color: var(--brand-accent);">
            <i class="bi bi-calendar-check me-2"></i> <?= date('M d, Y', strtotime($match['reservation_date'])); ?> 
            <span class="mx-2 text-white-50">|</span> 
            <i class="bi bi-clock me-2"></i> <?= $match['selected_time']; ?>
        </h5>
    </div>

    <?php 
        // Check if current user has already approved
        $has_approved = ($current_user_sid == $match['home_owner'] && $match['home_approved']) || 
                       ($current_user_sid == $match['challenger_owner'] && $match['challenger_approved']);
    ?>

    <form method="POST">
        <?php if($has_approved): ?>
            <button type="button" class="btn btn-approve" disabled>
                <i class="bi bi-check-lg"></i> YOU HAVE APPROVED
            </button>
            <p class="text-white-50 mt-3 small">Waiting for the opponent to confirm...</p>
        <?php else: ?>
            <button type="submit" name="click_approve" class="btn btn-approve">
                APPROVE THIS MATCH
            </button>
        <?php endif; ?>
    </form>
</div>

</body>
</html>