<?php
session_start();
include(__DIR__ . '/../database&config/db.php');

$sid = $_SESSION['username'] ?? '';
if (empty($sid)) { 
    die("You must be logged in to view your profile."); 
}

date_default_timezone_set('Asia/Manila');

// --- Fetch Player Info ---
$stmt = $conn->prepare("SELECT * FROM players WHERE student_id = ?");
$stmt->bind_param("s", $sid);
$stmt->execute();
$player = $stmt->get_result()->fetch_assoc();

// --- Fetch User's Teams ---
$team_stmt = $conn->prepare("SELECT * FROM teams WHERE created_by = ?");
$team_stmt->bind_param("s", $sid);
$team_stmt->execute();
$teams_result = $team_stmt->get_result();

// --- Fetch Pending/Approved Matches ---
$pending_matches = [];
$done_matches = [];

$status_query = $conn->prepare("
    SELECT mr.*, t1.team_name as home_n, t2.team_name as away_n, 
           t1.created_by as home_owner, t2.created_by as away_owner,
           r.reservation_date
    FROM match_requests mr
    JOIN reservations r ON mr.reservation_id = r.id
    JOIN teams t1 ON r.team_id = t1.id
    JOIN teams t2 ON mr.challenger_team_id = t2.id
    WHERE (t1.created_by = ? OR t2.created_by = ?)
");
$status_query->bind_param("ss", $sid, $sid);
$status_query->execute();
$status_results = $status_query->get_result();

while($m = $status_results->fetch_assoc()) {
    $is_pending = ($sid == $m['home_owner'] && $m['home_approved'] == 0) || ($sid == $m['away_owner'] && $m['challenger_approved'] == 0);
    $is_done = ($m['home_approved'] == 1 && $m['challenger_approved'] == 1);

    if ($is_pending) { $pending_matches[] = $m; } 
    elseif ($is_done) { $done_matches[] = $m; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile | <?= htmlspecialchars($sid); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@600;800&family=Plus+Jakarta+Sans:wght@400;700&display=swap');

        :root {
            --brand-primary: #0A192F;    
            --brand-accent: #FFB800;     
            --bg-body: #F4F7FA;          
            --border-bold: 3px solid #0A192F;
        }

        body { background-color: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; color: var(--brand-primary); padding-bottom: 50px; }

        .nb-header {
            background: var(--brand-primary);
            padding: 30px;
            border-radius: 20px;
            border-bottom: 5px solid var(--brand-accent);
            color: white;
            margin: 20px 0 30px 0;
            box-shadow: 0 10px 30px rgba(10, 25, 47, 0.15);
        }

        .upcoming-highlight-card {
            background: var(--brand-primary);
            border: 3px solid var(--brand-accent);
            border-radius: 16px;
            padding: 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            text-decoration: none;
            transition: 0.3s ease;
            margin-bottom: 30px;
            color: white;
        }
        .upcoming-highlight-card:hover { transform: translateY(-5px); box-shadow: 0 12px 25px rgba(255, 184, 0, 0.2); color: white; }

        .status-panel { background: white; border: var(--border-bold); border-radius: 16px; padding: 20px; margin-bottom: 20px; }
        .match-item { padding: 12px; border-radius: 10px; margin-bottom: 10px; border: 2px solid #E2E8F0; font-weight: 700; font-size: 0.85rem; display: flex; justify-content: space-between; align-items: center; }
        .match-item.needs-approval { border-color: var(--brand-accent); background: #FFFBEB; }

        .team-grid-card { background: white; border: var(--border-bold); border-radius: 16px; padding: 20px; transition: 0.2s; position: relative; height: 100%; }
        .active-tag { position: absolute; top: 0; right: 0; background: #3182CE; color: white; padding: 4px 12px; font-size: 0.65rem; font-weight: 800; border-bottom-left-radius: 10px; }
        
        /* ── BUTTON STYLES ── */
        .btn-action-group { display: flex; gap: 8px; flex-wrap: wrap; }
        
        .btn-book { background: var(--brand-primary); color: var(--brand-accent); border: 2px solid var(--brand-primary); font-family: 'Outfit'; font-weight: 800; text-transform: uppercase; font-size: 0.7rem; padding: 8px 12px; border-radius: 8px; text-decoration: none; flex-grow: 1; text-align: center; }
        .btn-book:hover { background: var(--brand-accent); color: var(--brand-primary); border-color: var(--brand-accent); }

        .btn-find { background: transparent; color: var(--brand-primary); border: 2px solid var(--brand-primary); font-family: 'Outfit'; font-weight: 800; text-transform: uppercase; font-size: 0.7rem; padding: 8px 12px; border-radius: 8px; text-decoration: none; flex-grow: 1; text-align: center; }
        .btn-find:hover { background: #f0f4f8; }

        .btn-edit-pill { background: transparent; color: #64748b; border: 2px solid #e2e8f0; font-family: 'Outfit'; font-weight: 800; text-transform: uppercase; font-size: 0.7rem; padding: 8px 15px; border-radius: 8px; text-decoration: none; }
        .btn-edit-pill:hover { background: #f8fafc; border-color: #cbd5e1; color: var(--brand-primary); }

    </style>
</head>
<body>

<div class="container">
    <div class="nb-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <small class="fw-bold text-uppercase" style="color: var(--brand-accent);">Player Profile</small>
                <h1 class="m-0"><?= strtoupper($player['full_name'] ?? 'IAN'); ?></h1>
                <p class="mb-0 opacity-75 small"><?= htmlspecialchars($sid); ?> | <?= $player['course'] ?? 'No Course Listed'; ?></p>
            </div>
            <div>
                <a href="/dashboard_and_admin/index.php" class="btn btn-outline-light btn-sm fw-bold me-2 px-3 rounded-pill">HOME</a>
                <a href="/authentication/logout.php" class="btn btn-danger btn-sm fw-bold px-3 rounded-pill">LOGOUT</a>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="status-panel">
                <h5 class="fw-800 text-uppercase small mb-3 text-muted">Action Required</h5>
                <?php foreach($pending_matches as $pm): 
                    $needs_my_approval = ($sid == $pm['home_owner'] && !$pm['home_approved']) || ($sid == $pm['away_owner'] && !$pm['challenger_approved']);
                ?>
                    <div class="match-item <?= $needs_my_approval ? 'needs-approval' : ''; ?>">
                        <div><?= $pm['home_n']; ?> <span class="opacity-50">vs</span> <?= $pm['away_n']; ?></div>
                        <?php if($needs_my_approval): ?>
                            <a href="/challenges&scheduling/confirmation_match.php?id=<?= $pm['id']; ?>" class="btn btn-warning btn-sm fw-bold py-0" style="font-size: 0.65rem;">APPROVE</a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; if(empty($pending_matches)) echo "<p class='small text-muted'>No pending actions.</p>"; ?>
            </div>

            <div class="status-panel">
                <h5 class="fw-800 text-uppercase small mb-3 text-muted">Confirmed Games</h5>
                <?php foreach($done_matches as $dm): ?>
                    <div class="match-item border-success bg-light">
                        <span><?= $dm['home_n']; ?> vs <?= $dm['away_n']; ?></span>
                        <span class="badge bg-success" style="font-size: 0.6rem;"><?= date('M d', strtotime($dm['reservation_date'])); ?></span>
                    </div>
                <?php endforeach; if(empty($done_matches)) echo "<p class='small text-muted'>No games confirmed.</p>"; ?>
            </div>
        </div>

        <div class="col-lg-8">
            <a href="/match_system/upcoming_reservation.php" class="upcoming-highlight-card">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-calendar-check-fill fs-2" style="color: var(--brand-accent);"></i>
                    <div>
                        <h4 class="m-0 fw-800">UPCOMING RESERVATIONS</h4>
                        <p class="m-0 small opacity-75">Track your scheduled court times and match schedules</p>
                    </div>
                </div>
                <i class="bi bi-chevron-right fs-4"></i>
            </a>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="fw-800 m-0">YOUR MANAGED TEAMS</h4>
                <a href="/Teams&history1/createteam.php" class="btn btn-warning btn-sm fw-bold shadow-sm rounded-pill px-3">NEW TEAM</a>
            </div>

            <div class="row g-3">
                <?php if ($teams_result->num_rows > 0): ?>
                    <?php while($team = $teams_result->fetch_assoc()): 
                        $activeId = $player['active_team_id'] ?? null;
                        $isActive = ($activeId == $team['id']); ?>
                        <div class="col-md-6">
                            <div class="team-grid-card <?= $isActive ? 'active' : ''; ?>">
                                <?php if($isActive): ?><div class="active-tag">ACTIVE</div><?php endif; ?>
                                <h5 class="fw-bold mb-1"><?= strtoupper($team['team_name']); ?></h5>
                                <p class="small text-muted mb-3"><?= $team['game_type']; ?> Squad</p>
                                
                                <div class="btn-action-group">
                                    <a href="/challenges&scheduling/selectdatetime.php?team_id=<?= $team['id']; ?>" class="btn-book">
                                        <i class="bi bi-calendar-plus me-1"></i> BOOK
                                    </a>
                                    
                                    <a href="/challenges&scheduling/matchmaking.php?team_id=<?= $team['id']; ?>" class="btn-find">
                                        <i class="bi bi-search me-1"></i> FIND MATCH
                                    </a>

                                    <a href="/Teams&history1/editteam.php?id=<?= $team['id']; ?>" class="btn-edit-pill">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

</body>
</html>