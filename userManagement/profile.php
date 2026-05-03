<?php
session_start();
require_once __DIR__ . '/../database_config/db.php';

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
$teams_array  = $teams_result->fetch_all(MYSQLI_ASSOC);
$my_team_ids  = array_column($teams_array, 'id');

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
    AND mr.status != 'rejected'
");
$status_query->bind_param("ss", $sid, $sid);
$status_query->execute();
$status_results = $status_query->get_result();

while($m = $status_results->fetch_assoc()) {
    $is_pending = ($sid == $m['home_owner'] && $m['home_approved'] == 0) || ($sid == $m['away_owner'] && $m['challenger_approved'] == 0);
    $is_done    = ($m['home_approved'] == 1 && $m['challenger_approved'] == 1);
    if ($is_pending) $pending_matches[] = $m;
    elseif ($is_done) $done_matches[] = $m;
}

// --- ANALYTICS: Pull all confirmed matches for this player's teams ---
$wins = $losses = $draws = $total_pf = $total_pa = 0;
$all_confirmed = [];
$monthly = [];

if (!empty($my_team_ids)) {
    $ph    = implode(',', array_fill(0, count($my_team_ids), '?'));
    $types = str_repeat('i', count($my_team_ids) * 2);
    $vals  = array_merge($my_team_ids, $my_team_ids);

    $astmt = $conn->prepare("
        SELECT mr.home_score, mr.away_score, mr.winner_id,
               t1.id as home_id, t2.id as away_id,
               t1.team_name as home_n, t2.team_name as away_n,
               r.reservation_date
        FROM match_requests mr
        JOIN reservations r ON mr.reservation_id = r.id
        JOIN teams t1 ON r.team_id = t1.id
        JOIN teams t2 ON mr.challenger_team_id = t2.id
        WHERE mr.final_status = 'confirmed'
          AND (t1.id IN ($ph) OR t2.id IN ($ph))
        ORDER BY r.reservation_date ASC
    ");
    $astmt->bind_param($types, ...$vals);
    $astmt->execute();
    $ares = $astmt->get_result();

    while ($row = $ares->fetch_assoc()) {
        $my_id    = in_array($row['home_id'], $my_team_ids) ? $row['home_id'] : $row['away_id'];
        $is_home  = ($row['home_id'] == $my_id);
        $my_score = $is_home ? $row['home_score'] : $row['away_score'];
        $op_score = $is_home ? $row['away_score']  : $row['home_score'];
        $total_pf += $my_score; $total_pa += $op_score;

        if ($row['winner_id'] == 0)          $draws++;
        elseif ($row['winner_id'] == $my_id) $wins++;
        else                                 $losses++;

        $mo = date('M y', strtotime($row['reservation_date']));
        if (!isset($monthly[$mo])) $monthly[$mo] = ['w'=>0,'l'=>0,'d'=>0];
        if ($row['winner_id'] == 0)          $monthly[$mo]['d']++;
        elseif ($row['winner_id'] == $my_id) $monthly[$mo]['w']++;
        else                                 $monthly[$mo]['l']++;

        $all_confirmed[] = [
            'my_score' => $my_score,
            'op_score' => $op_score,
            'winner_id'=> $row['winner_id'],
            'my_id'    => $my_id,
            'date'     => $row['reservation_date'],
            'opp'      => $is_home ? $row['away_n'] : $row['home_n'],
        ];
    }
}

$total_games = $wins + $losses + $draws;
$win_pct  = $total_games > 0 ? round($wins / $total_games * 100) : 0;
$avg_pf   = $total_games > 0 ? round($total_pf / $total_games, 1) : 0;
$avg_pa   = $total_games > 0 ? round($total_pa / $total_games, 1) : 0;
$pt_diff  = round($avg_pf - $avg_pa, 1);
$activity = min(100, ($total_games * 8) + ($wins * 5) + (count($my_team_ids) * 10));

// Current streak
$streak = 0; $streak_type = 'W';
foreach (array_reverse($all_confirmed) as $e) {
    $r = ($e['winner_id'] == 0) ? 'D' : (($e['winner_id'] == $e['my_id']) ? 'W' : 'L');
    if ($streak === 0) { $streak_type = $r; $streak = 1; }
    elseif ($r === $streak_type) $streak++;
    else break;
}

// Performance grade
$grade = 'F';
if ($win_pct >= 80) $grade = 'S';
elseif ($win_pct >= 65) $grade = 'A';
elseif ($win_pct >= 50) $grade = 'B';
elseif ($win_pct >= 35) $grade = 'C';
elseif ($total_games > 0) $grade = 'D';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile | <?= htmlspecialchars($sid); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@600;800;900&family=Plus+Jakarta+Sans:wght@400;700&display=swap');

        :root {
            --brand-primary: #0A192F;
            --brand-accent:  #FFB800;
            --bg-body:       #F4F7FA;
            --border-bold:   3px solid #0A192F;
            --success:       #16a34a;
            --danger:        #dc2626;
            --warn:          #d97706;
        }

        body { background-color: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; color: var(--brand-primary); padding-bottom: 60px; }

        .nb-header { background: var(--brand-primary); padding: 30px; border-radius: 20px; border-bottom: 5px solid var(--brand-accent); color: white; margin: 20px 0 30px; box-shadow: 0 10px 30px rgba(10,25,47,.15); }

        .upcoming-highlight-card { background: var(--brand-primary); border: 3px solid var(--brand-accent); border-radius: 16px; padding: 25px; display: flex; align-items: center; justify-content: space-between; text-decoration: none; transition: .3s; margin-bottom: 30px; color: white; }
        .upcoming-highlight-card:hover { transform: translateY(-5px); box-shadow: 0 12px 25px rgba(255,184,0,.2); color: white; }

        .status-panel { background: white; border: var(--border-bold); border-radius: 16px; padding: 20px; margin-bottom: 20px; }
        .match-item { padding: 12px; border-radius: 10px; margin-bottom: 10px; border: 2px solid #E2E8F0; font-weight: 700; font-size: .85rem; display: flex; justify-content: space-between; align-items: center; }
        .match-item.needs-approval { border-color: var(--brand-accent); background: #FFFBEB; }

        .team-grid-card { background: white; border: var(--border-bold); border-radius: 16px; padding: 20px; transition: .2s; position: relative; height: 100%; }
        .active-tag { position: absolute; top: 0; right: 0; background: #3182CE; color: white; padding: 4px 12px; font-size: .65rem; font-weight: 800; border-bottom-left-radius: 10px; }

        .btn-action-group { display: flex; gap: 8px; flex-wrap: wrap; }
        .btn-book { background: var(--brand-primary); color: var(--brand-accent); border: 2px solid var(--brand-primary); font-family: 'Outfit'; font-weight: 800; text-transform: uppercase; font-size: .7rem; padding: 8px 12px; border-radius: 8px; text-decoration: none; flex-grow: 1; text-align: center; }
        .btn-book:hover { background: var(--brand-accent); color: var(--brand-primary); border-color: var(--brand-accent); }
        .btn-find { background: transparent; color: var(--brand-primary); border: 2px solid var(--brand-primary); font-family: 'Outfit'; font-weight: 800; text-transform: uppercase; font-size: .7rem; padding: 8px 12px; border-radius: 8px; text-decoration: none; flex-grow: 1; text-align: center; }
        .btn-find:hover { background: #f0f4f8; }
        .btn-history { background: transparent; color: var(--brand-primary); border: 2px solid #94a3b8; font-family: 'Outfit'; font-weight: 800; text-transform: uppercase; font-size: .7rem; padding: 8px 12px; border-radius: 8px; text-decoration: none; flex-grow: 1; text-align: center; }
        .btn-history:hover { background: #f8fafc; border-color: var(--brand-primary); }

        /* ── ANALYTICS SECTION ── */
        .analytics-wrap { margin-top: 40px; }
        .analytics-title { font-family: 'Outfit'; font-weight: 900; font-size: 1rem; text-transform: uppercase; letter-spacing: 2px; color: var(--brand-primary); border-left: 5px solid var(--brand-accent); padding-left: 12px; margin-bottom: 20px; }

        .stat-chip { background: white; border: var(--border-bold); border-radius: 14px; padding: 18px 14px; text-align: center; height: 100%; }
        .stat-chip .num { font-family: 'Outfit'; font-weight: 900; font-size: 2.2rem; line-height: 1; }
        .stat-chip .lbl { font-size: .62rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #64748b; margin-top: 3px; }

        .grade-box { background: var(--brand-primary); border-radius: 16px; padding: 20px; text-align: center; border: 3px solid var(--brand-accent); }
        .grade-letter { font-family: 'Outfit'; font-weight: 900; font-size: 5rem; color: var(--brand-accent); line-height: 1; }
        .grade-label { font-size: .65rem; font-weight: 700; text-transform: uppercase; letter-spacing: 2px; color: rgba(255,255,255,.5); }

        .insight-card { background: white; border: 2px solid #e2e8f0; border-radius: 12px; padding: 14px 16px; font-size: .82rem; }
        .insight-card .i-icon { font-size: 1.3rem; margin-bottom: 4px; }
        .insight-card .i-title { font-weight: 800; font-size: .7rem; text-transform: uppercase; color: #64748b; letter-spacing: 1px; }
        .insight-card .i-val { font-family: 'Outfit'; font-weight: 900; font-size: 1.5rem; color: var(--brand-primary); }

        .win-bar-wrap { background: #e2e8f0; border-radius: 20px; height: 10px; overflow: hidden; margin-top: 5px; }
        .win-bar-fill { height: 100%; border-radius: 20px; background: linear-gradient(90deg, var(--brand-accent), var(--success)); transition: width 1.4s ease; }

        .streak-pill { display: inline-flex; align-items: center; gap: 6px; padding: 7px 18px; border-radius: 40px; font-family: 'Outfit'; font-weight: 800; font-size: 1rem; }
        .streak-pill.W { background: #dcfce7; color: var(--success); border: 2px solid var(--success); }
        .streak-pill.L { background: #fee2e2; color: var(--danger); border: 2px solid var(--danger); }
        .streak-pill.D { background: #fef3c7; color: var(--warn);   border: 2px solid var(--warn); }

        .chart-panel { background: white; border: var(--border-bold); border-radius: 16px; padding: 20px; }
        .chart-panel-title { font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; color: #94a3b8; margin-bottom: 14px; }

        .recent-result-row { display: flex; justify-content: space-between; align-items: center; padding: 9px 0; border-bottom: 1px solid #f1f5f9; font-size: .82rem; }
        .recent-result-row:last-child { border-bottom: none; }
        .res-badge { font-size: .6rem; font-weight: 800; padding: 2px 9px; border-radius: 20px; text-transform: uppercase; }
        .res-badge.W { background: #dcfce7; color: var(--success); }
        .res-badge.L { background: #fee2e2; color: var(--danger); }
        .res-badge.D { background: #fef3c7; color: var(--warn); }
    </style>
</head>
<body>
<div class="container">

    <!-- HEADER -->
    <div class="nb-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <small class="fw-bold text-uppercase" style="color:var(--brand-accent);">Player Profile</small>
                <h1 class="m-0"><?= strtoupper($player['full_name'] ?? 'Player'); ?></h1>
                <p class="mb-0 opacity-75 small"><?= htmlspecialchars($sid); ?> | <?= $player['course'] ?? 'No Course Listed'; ?></p>
            </div>
            <div>
                <a href="../index.php" class="btn btn-outline-light btn-sm fw-bold me-2 px-3 rounded-pill">HOME</a>
                <a href="../authentication/logout.php" class="btn btn-danger btn-sm fw-bold px-3 rounded-pill">LOGOUT</a>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- LEFT PANEL -->
        <!-- LEFT PANEL -->
        <div class="col-lg-4">
            <div class="status-panel">
                <h5 class="fw-800 text-uppercase small mb-3 text-muted">Action Required</h5>
                <?php foreach($pending_matches as $pm):
                    $needs_my_approval = ($sid == $pm['home_owner'] && !$pm['home_approved']) || ($sid == $pm['away_owner'] && !$pm['challenger_approved']);
                ?>
                    <div class="match-item <?= $needs_my_approval ? 'needs-approval' : ''; ?>">
                        <div><?= htmlspecialchars($pm['home_n']); ?> <span class="opacity-50">vs</span> <?= htmlspecialchars($pm['away_n']); ?></div>
                        <?php if($needs_my_approval): ?>
                            <div class="d-flex gap-1">
                                <a href="../challenges&scheduling/accept_match.php?id=<?= $pm['id']; ?>&action=accept"  class="btn btn-success btn-sm fw-bold py-0" style="font-size:.65rem;">ACCEPT</a>
                                <a href="../challenges&scheduling/accept_match.php?id=<?= $pm['id']; ?>&action=decline" class="btn btn-danger  btn-sm fw-bold py-0" style="font-size:.65rem;">DECLINE</a>
                            </div>
                        <?php else: ?>
                            <span class="badge bg-secondary" style="font-size:.6rem;">WAITING...</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; if(empty($pending_matches)) echo "<p class='small text-muted'>No pending actions.</p>"; ?>
            </div>

            <div class="status-panel">
                <h5 class="fw-800 text-uppercase small mb-3 text-muted">Confirmed Games</h5>
                <style>
                    #confirmedScroll::-webkit-scrollbar { width: 4px; }
                    #confirmedScroll::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 10px; }
                    #confirmedScroll::-webkit-scrollbar-thumb { background: #0A192F; border-radius: 10px; }
                </style>
                <div id="confirmedScroll" style="max-height:220px; overflow-y:auto; padding-right:4px;">
                    <?php foreach($done_matches as $dm): ?>
                        <div class="match-item border-success bg-light">
                            <span><?= htmlspecialchars($dm['home_n']); ?> vs <?= htmlspecialchars($dm['away_n']); ?></span>
                            <span class="badge bg-success" style="font-size:.6rem;"><?= date('M d', strtotime($dm['reservation_date'])); ?></span>
                        </div>
                    <?php endforeach; ?>
                    <?php if(empty($done_matches)) echo "<p class='small text-muted'>No games confirmed.</p>"; ?>
                </div>
            </div>
        </div><!-- END LEFT PANEL -->
        <!-- RIGHT PANEL -->
        <div class="col-lg-8">
            <a href="../match_system/upcoming_reservation.php" class="upcoming-highlight-card">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-calendar-check-fill fs-2" style="color:var(--brand-accent);"></i>
                    <div>
                        <h4 class="m-0 fw-800">UPCOMING RESERVATIONS</h4>
                        <p class="m-0 small opacity-75">Track your scheduled court times and match schedules</p>
                    </div>
                </div>
                <i class="bi bi-chevron-right fs-4"></i>
            </a>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="fw-800 m-0">YOUR MANAGED TEAMS</h4>
                <a href="../Teams%26history1/createteam.php" class="btn btn-warning btn-sm fw-bold shadow-sm rounded-pill px-3">NEW TEAM</a>
            </div>

            <div class="row g-3">
                <?php foreach ($teams_array as $team):
                    $activeId = $player['active_team_id'] ?? null;
                    $isActive = ($activeId == $team['id']); ?>
                    <div class="col-md-6">
                        <div class="team-grid-card">
                            <?php if($isActive): ?><div class="active-tag">ACTIVE</div><?php endif; ?>
                            <h5 class="fw-bold mb-1"><?= strtoupper($team['team_name']); ?></h5>
                            <p class="small text-muted mb-3"><?= $team['game_type']; ?> Squad</p>
                            <div class="btn-action-group">
                                <a href="../challenges&scheduling/selectdatetime.php?team_id=<?= $team['id']; ?>" class="btn-book">
                                    <i class="bi bi-calendar-plus me-1"></i> BOOK
                                </a>
                                <a href="../challenges&scheduling/matchmaking.php?team_id=<?= $team['id']; ?>" class="btn-find">
                                    <i class="bi bi-search me-1"></i> FIND MATCH
                                </a>
                                <a href="../Teams&history1/team_overall_history.php?team_id=<?= $team['id']; ?>" class="btn-history">
                                    <i class="bi bi-bar-chart-fill me-1"></i> HISTORY
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($teams_array)): ?>
                    <p class="text-muted small">No teams yet. Create one!</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════ -->
    <!--               ANALYTICS SECTION                   -->
    <!-- ══════════════════════════════════════════════════ -->
    <div class="analytics-wrap">
        <div class="analytics-title"><i class="bi bi-bar-chart-fill me-2"></i>Performance Analytics</div>

        <?php if ($total_games === 0): ?>
            <div class="text-center py-5" style="background:white;border:var(--border-bold);border-radius:16px;">
                <i class="bi bi-trophy display-3 text-muted"></i>
                <h5 class="mt-3 fw-bold">No Match Data Yet</h5>
                <p class="text-muted">Complete some confirmed matches to see your performance stats here.</p>
            </div>
        <?php else: ?>

        <!-- ROW 1: Grade + KPIs + Streak -->
        <div class="row g-3 mb-3">

            <!-- Performance Grade -->
            <div class="col-6 col-md-2">
                <div class="grade-box h-100 d-flex flex-col align-items-center justify-content-center" style="display:flex;flex-direction:column;">
                    <div class="grade-label mb-1">Grade</div>
                    <div class="grade-letter"><?= $grade; ?></div>
                    <div class="grade-label mt-1"><?= $win_pct; ?>% WR</div>
                </div>
            </div>

            <!-- KPI chips -->
            <div class="col-6 col-md-2">
                <div class="stat-chip h-100">
                    <div class="num" style="color:var(--brand-primary);"><?= $total_games; ?></div>
                    <div class="lbl">Games</div>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <div class="stat-chip h-100">
                    <div class="num" style="color:var(--success);"><?= $wins; ?></div>
                    <div class="lbl">Wins</div>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <div class="stat-chip h-100">
                    <div class="num" style="color:var(--danger);"><?= $losses; ?></div>
                    <div class="lbl">Losses</div>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <div class="stat-chip h-100">
                    <div class="num" style="color:var(--warn);"><?= $draws; ?></div>
                    <div class="lbl">Draws</div>
                </div>
            </div>
            <div class="col-6 col-md-2">
                <div class="stat-chip h-100">
                    <div class="num" style="color:<?= $pt_diff >= 0 ? 'var(--success)' : 'var(--danger)'; ?>;">
                        <?= ($pt_diff >= 0 ? '+' : '') . $pt_diff; ?>
                    </div>
                    <div class="lbl">Pt Diff</div>
                </div>
            </div>
        </div>

        <!-- ROW 2: Win Rate bar + Insight tiles -->
        <div class="row g-3 mb-3">
            <div class="col-md-5">
                <div class="chart-panel h-100">
                    <div class="chart-panel-title">Win Rate</div>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="fw-bold" style="font-family:Outfit;font-size:1.6rem;"><?= $win_pct; ?>%</span>
                        <div class="text-end small text-muted"><?= $wins; ?>W / <?= $draws; ?>D / <?= $losses; ?>L</div>
                    </div>
                    <div class="win-bar-wrap mb-4">
                        <div class="win-bar-fill" id="winBar" style="width:0%"></div>
                    </div>

                    <div class="chart-panel-title mt-2">Current Streak</div>
                    <div>
                        <?php if ($streak > 0): ?>
                        <span class="streak-pill <?= $streak_type; ?>">
                            <i class="bi bi-<?= $streak_type === 'W' ? 'fire' : ($streak_type === 'L' ? 'graph-down-arrow' : 'dash-circle'); ?>"></i>
                            <?= $streak; ?> <?= $streak_type === 'W' ? 'Win' : ($streak_type === 'L' ? 'Loss' : 'Draw'); ?><?= $streak > 1 ? ' Streak' : ''; ?>
                        </span>
                        <?php endif; ?>
                    </div>

                    <div class="row g-2 mt-3">
                        <div class="col-6">
                            <div class="insight-card text-center">
                                <div class="i-icon">🏀</div>
                                <div class="i-title">Avg Scored</div>
                                <div class="i-val"><?= $avg_pf; ?></div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="insight-card text-center">
                                <div class="i-icon">🛡️</div>
                                <div class="i-title">Avg Conceded</div>
                                <div class="i-val" style="color:var(--danger);"><?= $avg_pa; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Doughnut -->
            <div class="col-md-3">
                <div class="chart-panel h-100">
                    <div class="chart-panel-title">Result Split</div>
                    <canvas id="doughnut" height="170"></canvas>
                </div>
            </div>

            <!-- Recent 5 results -->
            <div class="col-md-4">
                <div class="chart-panel h-100">
                    <div class="chart-panel-title">Recent Results</div>
                    <?php foreach (array_slice(array_reverse($all_confirmed), 0, 5) as $e):
                        $r = ($e['winner_id'] == 0) ? 'D' : (($e['winner_id'] == $e['my_id']) ? 'W' : 'L');
                    ?>
                    <div class="recent-result-row">
                        <div>
                            <div class="fw-bold" style="font-size:.82rem;">vs <?= htmlspecialchars($e['opp']); ?></div>
                            <div class="text-muted" style="font-size:.7rem;"><?= date('M d, Y', strtotime($e['date'])); ?></div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="fw-800" style="font-family:Outfit;"><?= $e['my_score']; ?>–<?= $e['op_score']; ?></span>
                            <span class="res-badge <?= $r; ?>"><?= $r; ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- ROW 3: Monthly bar + Points line -->
        <div class="row g-3">
            <div class="col-md-6">
                <div class="chart-panel">
                    <div class="chart-panel-title">Monthly Wins vs Losses</div>
                    <canvas id="monthlyBar" height="160"></canvas>
                </div>
            </div>
            <div class="col-md-6">
                <div class="chart-panel">
                    <div class="chart-panel-title">Points For vs Against (per game)</div>
                    <canvas id="pointsLine" height="160"></canvas>
                </div>
            </div>
        </div>

        <?php endif; ?>
    </div><!-- /analytics-wrap -->
</div><!-- /container -->

<script>
// Animate win bar
setTimeout(() => {
    const bar = document.getElementById('winBar');
    if (bar) bar.style.width = '<?= $win_pct; ?>%';
}, 200);

<?php if ($total_games > 0): ?>

// Doughnut
new Chart(document.getElementById('doughnut'), {
    type: 'doughnut',
    data: {
        labels: ['Wins','Draws','Losses'],
        datasets: [{ data: [<?= $wins; ?>,<?= $draws; ?>,<?= $losses; ?>], backgroundColor: ['#16a34a','#d97706','#dc2626'], borderWidth: 0 }]
    },
    options: { cutout: '62%', plugins: { legend: { position: 'bottom', labels: { font: { size: 11 } } } } }
});

// Monthly bar
const months = <?= json_encode(array_keys($monthly)); ?>;
const mW = <?= json_encode(array_column($monthly, 'w')); ?>;
const mL = <?= json_encode(array_column($monthly, 'l')); ?>;
new Chart(document.getElementById('monthlyBar'), {
    type: 'bar',
    data: {
        labels: months,
        datasets: [
            { label: 'Wins',   data: mW, backgroundColor: '#16a34a', borderRadius: 6 },
            { label: 'Losses', data: mL, backgroundColor: '#dc2626', borderRadius: 6 }
        ]
    },
    options: { plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
});

// Points line
const gLabels = <?= json_encode(array_map(fn($e) => date('M d', strtotime($e['date'])), $all_confirmed)); ?>;
const pfData  = <?= json_encode(array_column($all_confirmed, 'my_score')); ?>;
const paData  = <?= json_encode(array_column($all_confirmed, 'op_score')); ?>;
new Chart(document.getElementById('pointsLine'), {
    type: 'line',
    data: {
        labels: gLabels,
        datasets: [
            { label: 'Points For',     data: pfData, borderColor: '#FFB800', backgroundColor: 'rgba(255,184,0,0.1)', tension: 0.4, fill: true, pointBackgroundColor: '#FFB800', pointRadius: 4 },
            { label: 'Points Against', data: paData, borderColor: '#dc2626', backgroundColor: 'rgba(220,38,38,0.07)', tension: 0.4, fill: true, pointBackgroundColor: '#dc2626', pointRadius: 4 }
        ]
    },
    options: { plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true } } }
});

<?php endif; ?>
</script>
</body>
</html>