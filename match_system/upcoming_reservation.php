<?php
session_start();
include(__DIR__ . '/../database&config/db.php');

$sid = $_SESSION['username'] ?? '';
if (!$sid) die("You must be logged in to view this page.");

date_default_timezone_set('Asia/Manila');
$today = date('Y-m-d');

// The logic: 
// Priority 0 = Today's date AND NOT confirmed (The 'START GAME' matches)
// Priority 1 = Future date
// Priority 2 = Past date OR already confirmed ('EXPIRED' or 'BATTLE DONE')
$res_query = $conn->prepare("
    SELECT mr.id AS match_id, mr.final_status, t1.team_name AS home_team, t2.team_name AS challenger_team,
           r.reservation_date, r.selected_time,
           CASE 
                WHEN r.reservation_date = ? AND (mr.final_status IS NULL OR mr.final_status != 'confirmed') THEN 0
                WHEN r.reservation_date > ? THEN 1
                ELSE 2
           END AS priority
    FROM match_requests mr
    JOIN reservations r ON mr.reservation_id = r.id
    JOIN teams t1 ON r.team_id = t1.id
    JOIN teams t2 ON mr.challenger_team_id = t2.id
    WHERE (t1.created_by = ? OR t2.created_by = ?)
      AND mr.home_approved = 1
      AND mr.challenger_approved = 1
    ORDER BY priority ASC, r.reservation_date ASC, r.selected_time ASC
");

$res_query->bind_param("ssss", $today, $today, $sid, $sid);
$res_query->execute();
$reservations = $res_query->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Match Schedule | NBSC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap');
        :root { --brand-primary: #0A192F; --brand-accent: #FFB800; --bg-body: #F4F7FA; }
        
        body { background: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; }
        .nb-header { background: var(--brand-primary); padding: 15px 30px; border-bottom: 4px solid var(--brand-accent); color: white; display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        
        .match-card { background: white; border-radius: 15px; border-left: 6px solid #dee2e6; padding: 22px; margin-bottom: 18px; transition: 0.3s; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
        
        /* Highlight for the Startable match */
        .match-ready { 
            border-left-color: #198754; 
            background: #f0fff4; 
            box-shadow: 0 8px 20px rgba(25, 135, 84, 0.12);
            transform: scale(1.01);
        }

        .match-dimmed { opacity: 0.65; filter: grayscale(0.5); }

        .vs-badge { background: var(--brand-accent); color: var(--brand-primary); font-weight: 800; padding: 2px 8px; border-radius: 4px; font-size: 0.7rem; margin: 0 10px; vertical-align: middle; }
        
        .btn-start { 
            background: var(--brand-primary); 
            color: var(--brand-accent); 
            font-weight: 800; 
            border: none; 
            padding: 10px 24px; 
            border-radius: 10px; 
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .btn-start:hover { background: #1a2e4d; transform: translateY(-2px); color: white; }
    </style>
</head>
<body>

<header class="nb-header">
    <h4 class="m-0 text-warning fw-bold"><i class="bi bi-calendar-event-fill me-2"></i> SCHEDULE</h4>
    <a href="/ICS_APP_DEV1/userManagement/profile.php" class="btn btn-sm btn-outline-light px-3">BACK</a>
</header>

<div class="container pb-5">
    <?php if ($reservations): ?>
        <?php foreach($reservations as $res): 
            $res_date = date('Y-m-d', strtotime($res['reservation_date']));
            $is_finished = ($res['final_status'] === 'confirmed');
            $is_today = ($today == $res_date);
            $is_expired = ($today > $res_date && !$is_finished);
            
            // Logic for the Start button match
            $can_start = ($is_today && !$is_finished);
        ?>
            <div class="match-card <?= $can_start ? 'match-ready' : (($is_finished || $is_expired) ? 'match-dimmed' : '') ?>">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold m-0 text-dark">
                            <?= strtoupper(htmlspecialchars($res['home_team'])) ?> 
                            <span class="vs-badge">VS</span> 
                            <?= strtoupper(htmlspecialchars($res['challenger_team'])) ?>
                        </h5>
                        <div class="mt-2 text-muted small fw-medium">
                            <span class="me-3"><i class="bi bi-calendar3 me-1"></i> <?= date('F j, Y', strtotime($res['reservation_date'])) ?></span>
                            <span><i class="bi bi-clock me-1"></i> <?= $res['selected_time'] ?></span>
                        </div>
                    </div>
                    
                    <div>
                        <?php if($is_finished): ?>
                            <span class="badge bg-secondary p-2 px-3 rounded-pill"><i class="bi bi-check-all me-1"></i> BATTLE DONE</span>
                        <?php elseif($is_expired): ?>
                            <span class="badge bg-danger p-2 px-3 rounded-pill"><i class="bi bi-clock-history me-1"></i> EXPIRED</span>
                        <?php elseif($is_today): ?>
                            <form action="/ICS_APP_DEV1/match_system/match_control.php" method="POST">
                                <input type="hidden" name="match_id" value="<?= $res['match_id'] ?>">
                                <button type="submit" class="btn btn-start shadow-sm">
                                    <i class="bi bi-play-btn-fill"></i> START GAME
                                </button>
                            </form>
                        <?php else: ?>
                            <span class="badge bg-light text-dark border p-2 px-3 rounded-pill"><i class="bi bi-lock-fill me-1"></i> UPCOMING</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="text-center py-5">
            <i class="bi bi-calendar-x display-1 text-muted"></i>
            <p class="mt-3 text-muted">No scheduled matches found.</p>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>