<?php
session_start();
require_once __DIR__ . '/../database_config/db.php';

$sid = $_SESSION['username'] ?? '';
if (!$sid) die("You must be logged in to view this page.");

date_default_timezone_set('Asia/Manila');
$today = date('Y-m-d');
$now   = date('H:i:s');

// ── AUTO-EXPIRE: mark matches as expired if time passed 20 minutes ago ──
// We check today's unstarted matches and see if selected_time + 20min < now
$expire_stmt = $conn->prepare("
    UPDATE match_requests mr
    JOIN reservations r ON mr.reservation_id = r.id
    SET mr.final_status = 'expired'
    WHERE r.reservation_date = ?
      AND mr.final_status IS NULL OR mr.final_status NOT IN ('confirmed', 'expired')
      AND ADDTIME(r.selected_time, '00:20:00') < ?
");
$expire_stmt->bind_param("ss", $today, $now);
$expire_stmt->execute();

// Also expire ALL past-date matches that were never confirmed
$expire_past = $conn->prepare("
    UPDATE match_requests mr
    JOIN reservations r ON mr.reservation_id = r.id
    SET mr.final_status = 'expired'
    WHERE r.reservation_date < ?
      AND (mr.final_status IS NULL OR mr.final_status NOT IN ('confirmed', 'expired'))
");
$expire_past->bind_param("s", $today);
$expire_past->execute();

// ── FETCH RESERVATIONS ──
$res_query = $conn->prepare("
    SELECT mr.id AS match_id, mr.final_status,
           t1.team_name AS home_team, t2.team_name AS challenger_team,
           r.reservation_date, r.selected_time,
           -- is it past 20 min grace on today?
           (
               r.reservation_date = ? 
               AND ADDTIME(r.selected_time, '00:20:00') < ?
               AND mr.final_status NOT IN ('confirmed', 'expired')
           ) AS time_expired,
           CASE
               WHEN r.reservation_date = ? 
                    AND (mr.final_status IS NULL OR mr.final_status NOT IN ('confirmed','expired'))
                    AND ADDTIME(r.selected_time, '00:20:00') >= ? THEN 0
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
$res_query->bind_param("sssssss", $today, $now, $today, $now, $today, $sid, $sid);
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
        .match-ready  { border-left-color: #198754; background: #f0fff4; box-shadow: 0 8px 20px rgba(25,135,84,0.12); transform: scale(1.01); }
        .match-dimmed { opacity: 0.65; filter: grayscale(0.5); }

        .vs-badge { background: var(--brand-accent); color: var(--brand-primary); font-weight: 800; padding: 2px 8px; border-radius: 4px; font-size: 0.7rem; margin: 0 10px; vertical-align: middle; }

        .btn-start { background: var(--brand-primary); color: var(--brand-accent); font-weight: 800; border: none; padding: 10px 24px; border-radius: 10px; display: flex; align-items: center; gap: 8px; }
        .btn-start:hover { background: #1a2e4d; transform: translateY(-2px); color: white; }

        /* countdown timer */
        .countdown { font-size: .72rem; font-weight: 700; color: #d97706; margin-top: 6px; }
        .countdown.urgent { color: #dc2626; animation: blink .9s step-end infinite; }
        @keyframes blink { 50% { opacity: .4; } }
    </style>
</head>
<body>

<header class="nb-header">
    <h4 class="m-0 text-warning fw-bold"><i class="bi bi-calendar-event-fill me-2"></i> SCHEDULE</h4>
    <a href="../userManagement/profile.php" class="btn btn-sm btn-outline-light px-3">BACK</a>
</header>

<div class="container pb-5">
    <?php if ($reservations): ?>
        <?php foreach($reservations as $res):
            $res_date   = date('Y-m-d', strtotime($res['reservation_date']));
            $is_finished = ($res['final_status'] === 'confirmed');
            $is_expired  = ($res['final_status'] === 'expired') || $res['time_expired'];
            $is_today    = ($today === $res_date);
            $can_start   = ($is_today && !$is_finished && !$is_expired);

            // Calculate seconds remaining until 20-min grace ends (for JS countdown)
            $match_datetime  = $res_date . ' ' . $res['selected_time'];
            $deadline_ts     = strtotime($match_datetime) + (20 * 60);
            $seconds_left    = $deadline_ts - time();
        ?>
        <div class="match-card <?= $can_start ? 'match-ready' : (($is_finished || $is_expired || $today > $res_date) ? 'match-dimmed' : '') ?>">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold m-0 text-dark">
                        <?= strtoupper(htmlspecialchars($res['home_team'])) ?>
                        <span class="vs-badge">VS</span>
                        <?= strtoupper(htmlspecialchars($res['challenger_team'])) ?>
                    </h5>
                    <div class="mt-2 text-muted small fw-medium">
                        <span class="me-3"><i class="bi bi-calendar3 me-1"></i><?= date('F j, Y', strtotime($res['reservation_date'])) ?></span>
                        <span><i class="bi bi-clock me-1"></i><?= $res['selected_time'] ?></span>
                    </div>

                    <?php if ($can_start && $seconds_left > 0): ?>
                    <!-- Live countdown shown only on startable matches -->
                    <div class="countdown <?= $seconds_left < 120 ? 'urgent' : '' ?>" id="cd_<?= $res['match_id'] ?>"
                         data-deadline="<?= $deadline_ts ?>">
                        <i class="bi bi-hourglass-split me-1"></i>
                        Expires in <span class="cd-label">--:--</span>
                    </div>
                    <?php endif; ?>
                </div>

                <div>
                    <?php if ($is_finished): ?>
                        <span class="badge bg-secondary p-2 px-3 rounded-pill"><i class="bi bi-check-all me-1"></i> BATTLE DONE</span>
                    <?php elseif ($is_expired): ?>
                        <span class="badge bg-danger p-2 px-3 rounded-pill"><i class="bi bi-clock-history me-1"></i> EXPIRED</span>
                    <?php elseif ($can_start): ?>
                        <form action="../match_system/match_control.php" method="POST">
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
<script>
// Live countdown timers — auto-reload page when deadline hits
document.querySelectorAll('[id^="cd_"]').forEach(el => {
    const deadline = parseInt(el.dataset.deadline) * 1000;
    const label    = el.querySelector('.cd-label');

    function tick() {
        const left = Math.floor((deadline - Date.now()) / 1000);
        if (left <= 0) {
            // Grace period ended — reload so PHP re-evaluates expiry
            location.reload();
            return;
        }
        const m = String(Math.floor(left / 60)).padStart(2, '0');
        const s = String(left % 60).padStart(2, '0');
        label.textContent = `${m}:${s}`;
        if (left < 120) el.classList.add('urgent');
    }

    tick();
    setInterval(tick, 1000);
});
</script>
</body>
</html>