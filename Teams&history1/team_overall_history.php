<?php
session_start();
require_once __DIR__ . '/../database_config/db.php';

$team_id = $_GET['team_id'] ?? null;
if (!$team_id) die("No team specified.");

$stmt = $conn->prepare("SELECT * FROM teams WHERE id = ?");
$stmt->bind_param("i", $team_id);
$stmt->execute();
$team = $stmt->get_result()->fetch_assoc();
if (!$team) die("Team not found.");

// All confirmed matches for this team
$stmt = $conn->prepare("
    SELECT mr.home_score, mr.away_score, mr.winner_id,
           t1.id as home_id, t1.team_name as home_n,
           t2.id as away_id, t2.team_name as away_n,
           r.reservation_date
    FROM match_requests mr
    JOIN reservations r ON mr.reservation_id = r.id
    JOIN teams t1 ON r.team_id = t1.id
    JOIN teams t2 ON mr.challenger_team_id = t2.id
    WHERE mr.final_status = 'confirmed'
      AND (t1.id = ? OR t2.id = ?)
    ORDER BY r.reservation_date DESC
");
$stmt->bind_param("ii", $team_id, $team_id);
$stmt->execute();
$matches = $stmt->get_result();

$wins = $losses = $draws = $pf = $pa = 0;
$match_list = [];
while ($m = $matches->fetch_assoc()) {
    $is_home = ($m['home_id'] == $team_id);
    $my_score  = $is_home ? $m['home_score'] : $m['away_score'];
    $opp_score = $is_home ? $m['away_score'] : $m['home_score'];
    $opp_name  = $is_home ? $m['away_n'] : $m['home_n'];
    $pf += $my_score; $pa += $opp_score;
    if ($m['winner_id'] == 0)       $draws++;
    elseif ($m['winner_id'] == $team_id) $wins++;
    else                            $losses++;
    $match_list[] = compact('my_score','opp_score','opp_name','m');
}
$total = $wins + $losses + $draws;
$win_pct = $total > 0 ? round(($wins / $total) * 100) : 0;
$avg_pf  = $total > 0 ? round($pf / $total, 1) : 0;
$avg_pa  = $total > 0 ? round($pa / $total, 1) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($team['team_name']); ?> History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@600;800&family=Plus+Jakarta+Sans:wght@400;700&display=swap');
        :root { --primary:#0A192F; --accent:#FFB800; --bg:#F4F7FA; }
        body { background:var(--bg); font-family:'Plus Jakarta Sans',sans-serif; color:var(--primary); padding-bottom:60px; }
        .nb-header { background:var(--primary); padding:30px; border-radius:20px; border-bottom:5px solid var(--accent); color:white; margin:20px 0 30px; }
        .stat-card { background:white; border:3px solid var(--primary); border-radius:16px; padding:24px; text-align:center; }
        .stat-num  { font-family:'Outfit'; font-weight:900; font-size:2.8rem; color:var(--primary); line-height:1; }
        .stat-num.win  { color:#16a34a; }
        .stat-num.loss { color:#dc2626; }
        .stat-num.draw { color:#d97706; }
        .stat-label { font-size:0.72rem; font-weight:700; text-transform:uppercase; letter-spacing:1px; color:#64748b; margin-top:4px; }
        .match-row { background:white; border:2px solid #e2e8f0; border-radius:12px; padding:14px 18px; margin-bottom:10px; display:flex; justify-content:space-between; align-items:center; }
        .match-row.win  { border-left:5px solid #16a34a; }
        .match-row.loss { border-left:5px solid #dc2626; }
        .match-row.draw { border-left:5px solid #d97706; }
        .result-tag { font-family:'Outfit'; font-weight:800; font-size:0.7rem; padding:3px 10px; border-radius:20px; text-transform:uppercase; }
        .result-tag.win  { background:#dcfce7; color:#16a34a; }
        .result-tag.loss { background:#fee2e2; color:#dc2626; }
        .result-tag.draw { background:#fef3c7; color:#d97706; }
        .chart-box { background:white; border:3px solid var(--primary); border-radius:16px; padding:24px; }
        .win-bar-wrap { background:#e2e8f0; border-radius:20px; height:12px; overflow:hidden; margin-top:6px; }
        .win-bar-fill { height:100%; border-radius:20px; background:linear-gradient(90deg,#FFB800,#16a34a); transition:width 1s ease; }
        .btn-back { background:transparent; color:white; border:2px solid rgba(255,255,255,0.3); font-weight:700; border-radius:50px; padding:8px 20px; text-decoration:none; }
        .btn-back:hover { background:var(--accent); border-color:var(--accent); color:var(--primary); }
    </style>
</head>
<body>
<div class="container">
    <div class="nb-header d-flex justify-content-between align-items-center">
        <div>
            <small style="color:var(--accent);" class="fw-bold text-uppercase">Team Record</small>
            <h1 class="m-0"><?= strtoupper(htmlspecialchars($team['team_name'])); ?></h1>
            <p class="mb-0 opacity-75 small"><?= htmlspecialchars($team['game_type'] ?? ''); ?> &bull; <?= $total; ?> games played</p>
        </div>
        <a href="../userManagement/profile.php" class="btn-back">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>

    <!-- WIN % BAR -->
    <div class="chart-box mb-4">
        <div class="d-flex justify-content-between align-items-center mb-1">
            <span class="fw-bold">Win Rate</span>
            <span class="fw-800" style="font-family:Outfit;font-size:1.4rem;color:var(--primary);"><?= $win_pct; ?>%</span>
        </div>
        <div class="win-bar-wrap"><div class="win-bar-fill" style="width:<?= $win_pct; ?>%"></div></div>
        <div class="d-flex justify-content-between mt-2" style="font-size:0.75rem;color:#64748b;">
            <span><?= $wins; ?> W &nbsp; <?= $draws; ?> D &nbsp; <?= $losses; ?> L</span>
            <span>Avg PF: <?= $avg_pf; ?> &nbsp;|&nbsp; Avg PA: <?= $avg_pa; ?></span>
        </div>
    </div>

    <!-- STAT CARDS -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-num win"><?= $wins; ?></div>
                <div class="stat-label">Wins</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-num loss"><?= $losses; ?></div>
                <div class="stat-label">Losses</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-num draw"><?= $draws; ?></div>
                <div class="stat-label">Draws</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-num"><?= $total; ?></div>
                <div class="stat-label">Played</div>
            </div>
        </div>
    </div>

    <!-- CHARTS -->
    <div class="row g-3 mb-4">
        <div class="col-md-5">
            <div class="chart-box h-100">
                <h6 class="fw-800 text-uppercase small text-muted mb-3">W / D / L Breakdown</h6>
                <canvas id="doughnutChart" height="180"></canvas>
            </div>
        </div>
        <div class="col-md-7">
            <div class="chart-box h-100">
                <h6 class="fw-800 text-uppercase small text-muted mb-3">Points Per Game</h6>
                <canvas id="lineChart" height="180"></canvas>
            </div>
        </div>
    </div>

    <!-- MATCH LOG -->
    <h5 class="fw-800 mb-3">Match Log</h5>
    <?php if (empty($match_list)): ?>
        <p class="text-muted">No confirmed matches yet.</p>
    <?php else: ?>
        <?php foreach ($match_list as $entry):
            $my = $entry['my_score']; $op = $entry['opp_score'];
            $res = ($entry['m']['winner_id'] == 0) ? 'draw' : (($entry['m']['winner_id'] == $team_id) ? 'win' : 'loss');
        ?>
        <div class="match-row <?= $res; ?>">
            <div>
                <span class="fw-bold"><?= htmlspecialchars($entry['opp_name']); ?></span>
                <div class="small text-muted"><?= date('M d, Y', strtotime($entry['m']['reservation_date'])); ?></div>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="fw-800" style="font-family:Outfit;font-size:1.2rem;"><?= $my; ?> – <?= $op; ?></span>
                <span class="result-tag <?= $res; ?>"><?= strtoupper($res); ?></span>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
// Doughnut
new Chart(document.getElementById('doughnutChart'), {
    type: 'doughnut',
    data: {
        labels: ['Wins','Draws','Losses'],
        datasets: [{ data: [<?= $wins; ?>,<?= $draws; ?>,<?= $losses; ?>], backgroundColor: ['#16a34a','#d97706','#dc2626'], borderWidth: 0 }]
    },
    options: { plugins: { legend: { position: 'bottom' } }, cutout: '65%' }
});

// Line — points for/against per game
const labels = <?= json_encode(array_map(fn($e) => date('M d', strtotime($e['m']['reservation_date'])), array_reverse($match_list))); ?>;
const pf     = <?= json_encode(array_map(fn($e) => $e['my_score'],  array_reverse($match_list))); ?>;
const pa     = <?= json_encode(array_map(fn($e) => $e['opp_score'], array_reverse($match_list))); ?>;

new Chart(document.getElementById('lineChart'), {
    type: 'line',
    data: {
        labels,
        datasets: [
            { label: 'Points For',     data: pf, borderColor: '#FFB800', backgroundColor: 'rgba(255,184,0,0.1)', tension: 0.4, fill: true, pointBackgroundColor: '#FFB800' },
            { label: 'Points Against', data: pa, borderColor: '#dc2626', backgroundColor: 'rgba(220,38,38,0.07)', tension: 0.4, fill: true, pointBackgroundColor: '#dc2626' }
        ]
    },
    options: { plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true } } }
});
</script>
</body>
</html>