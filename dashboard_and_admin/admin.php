<?php
session_start();
require_once __DIR__ . '/../database_config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /authentication/login.php");
    exit();
}

// ── KPI STATS ──
$total_players = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='player'")->fetch_assoc()['c'] ?? 0;
$total_teams   = $conn->query("SELECT COUNT(*) as c FROM teams")->fetch_assoc()['c'] ?? 0;
$total_matches = $conn->query("SELECT COUNT(*) as c FROM match_requests")->fetch_assoc()['c'] ?? 0;
$confirmed_matches = $conn->query("SELECT COUNT(*) as c FROM match_requests WHERE final_status='confirmed'")->fetch_assoc()['c'] ?? 0;
$pending_reqs  = $conn->query("SELECT COUNT(*) as c FROM match_requests WHERE final_status='pending'")->fetch_assoc()['c'] ?? 0;
$total_reservations = $conn->query("SELECT COUNT(*) as c FROM reservations")->fetch_assoc()['c'] ?? 0;

// ── MONTHLY MATCH ACTIVITY (last 6 months) ──
$monthly_data = [];
for ($i = 5; $i >= 0; $i--) {
    $month_start = date('Y-m-01', strtotime("-$i months"));
    $month_end   = date('Y-m-t', strtotime("-$i months"));
    $label       = date('M y', strtotime("-$i months"));
    $res = $conn->query("SELECT COUNT(*) as c FROM match_requests mr
        JOIN reservations r ON mr.reservation_id = r.id
        WHERE r.reservation_date BETWEEN '$month_start' AND '$month_end'");
    $monthly_data[$label] = $res->fetch_assoc()['c'] ?? 0;
}

// ── WIN RATE BY TEAM (top 6) ──
$team_stats = [];
$ts_res = $conn->query("
    SELECT t.team_name,
           COUNT(mr.id) as played,
           SUM(mr.winner_id = t.id) as wins
    FROM teams t
    LEFT JOIN match_requests mr ON (
        mr.final_status = 'confirmed' AND (
            EXISTS(SELECT 1 FROM reservations r WHERE r.id = mr.reservation_id AND r.team_id = t.id)
            OR mr.challenger_team_id = t.id
        )
    )
    GROUP BY t.id, t.team_name
    HAVING played > 0
    ORDER BY wins DESC
    LIMIT 6
");
if ($ts_res) {
    while ($row = $ts_res->fetch_assoc()) {
        $row['win_pct'] = $row['played'] > 0 ? round($row['wins'] / $row['played'] * 100) : 0;
        $team_stats[] = $row;
    }
}

// ── RECENT MATCHES ──
$recent_matches = $conn->query("
    SELECT mr.id, mr.home_score, mr.away_score, mr.final_status, mr.winner_id,
           t1.team_name as home_n, t2.team_name as away_n,
           r.reservation_date
    FROM match_requests mr
    JOIN reservations r ON mr.reservation_id = r.id
    JOIN teams t1 ON r.team_id = t1.id
    JOIN teams t2 ON mr.challenger_team_id = t2.id
    ORDER BY mr.id DESC
    LIMIT 8
");

// ── RECENT PLAYERS ──
$recent_players = $conn->query("
    SELECT u.username, p.full_name, p.course, p.active_team_id, t.team_name
    FROM users u
    LEFT JOIN players p ON u.username = p.student_id
    LEFT JOIN teams t ON p.active_team_id = t.id
    WHERE u.role = 'player'
    ORDER BY u.id DESC
    LIMIT 6
");

// ── MATCH STATUS BREAKDOWN ──
$status_res = $conn->query("SELECT final_status, COUNT(*) as c FROM match_requests GROUP BY final_status");
$status_data = ['pending'=>0,'confirmed'=>0,'rejected'=>0];
if ($status_res) while ($row = $status_res->fetch_assoc()) $status_data[$row['final_status']] = $row['c'];

// ── RESERVATIONS PER MONTH ──
$res_monthly = [];
for ($i = 5; $i >= 0; $i--) {
    $ms = date('Y-m-01', strtotime("-$i months"));
    $me = date('Y-m-t',  strtotime("-$i months"));
    $lb = date('M y',    strtotime("-$i months"));
    $r  = $conn->query("SELECT COUNT(*) as c FROM reservations WHERE reservation_date BETWEEN '$ms' AND '$me'");
    $res_monthly[$lb] = $r->fetch_assoc()['c'] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>NBSC Admin — Command Center</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Mono:wght@400;500&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
    --ink:      #060D1A;
    --ink-mid:  #0D1F38;
    --ink-soft: #152844;
    --gold:     #F5A800;
    --gold-dim: #B87A00;
    --teal:     #00C9A7;
    --rose:     #FF4D6D;
    --sky:      #38BDF8;
    --text:     #E2EAF4;
    --muted:    #5A7A9F;
    --border:   rgba(255,255,255,0.07);
    --sidebar:  240px;
    --radius:   14px;
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
    background: var(--ink);
    color: var(--text);
    font-family: 'Manrope', sans-serif;
    font-size: 14px;
    min-height: 100vh;
    overflow-x: hidden;
}

/* ── SIDEBAR ── */
#sidebar {
    position: fixed;
    top: 0; left: 0;
    width: var(--sidebar);
    height: 100vh;
    background: var(--ink-mid);
    border-right: 1px solid var(--border);
    display: flex;
    flex-direction: column;
    z-index: 100;
    overflow: hidden;
}

.sidebar-logo {
    padding: 28px 24px 20px;
    border-bottom: 1px solid var(--border);
}

.logo-mark {
    width: 42px; height: 42px;
    background: var(--gold);
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-family: 'Syne', sans-serif;
    font-weight: 800;
    font-size: 1.1rem;
    color: var(--ink);
    margin-bottom: 10px;
}

.logo-title {
    font-family: 'Syne', sans-serif;
    font-weight: 800;
    font-size: 1rem;
    color: var(--text);
    letter-spacing: 0.05em;
    text-transform: uppercase;
}

.logo-sub {
    font-size: 0.65rem;
    color: var(--muted);
    letter-spacing: 0.08em;
    text-transform: uppercase;
    margin-top: 2px;
}

.sidebar-nav { padding: 16px 12px; flex: 1; }

.nav-section-label {
    font-size: 0.58rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    color: var(--muted);
    padding: 14px 12px 6px;
}

.nav-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border-radius: 9px;
    color: var(--muted);
    text-decoration: none;
    font-weight: 600;
    font-size: 0.82rem;
    transition: 0.18s;
    margin-bottom: 2px;
}

.nav-item:hover { background: rgba(255,255,255,0.05); color: var(--text); }
.nav-item.active { background: rgba(245,168,0,0.12); color: var(--gold); }
.nav-item.active .nav-icon { color: var(--gold); }
.nav-item .nav-icon { font-size: 1rem; width: 20px; text-align: center; }

.sidebar-footer {
    padding: 16px 12px;
    border-top: 1px solid var(--border);
}

.admin-chip {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    background: rgba(255,255,255,0.04);
    border-radius: 9px;
}

.admin-avatar {
    width: 32px; height: 32px;
    background: linear-gradient(135deg, var(--gold), var(--teal));
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-weight: 800;
    font-size: 0.75rem;
    color: var(--ink);
    flex-shrink: 0;
}

/* ── MAIN CONTENT ── */
#main {
    margin-left: var(--sidebar);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

/* ── TOP BAR ── */
.topbar {
    padding: 18px 32px;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: var(--ink-mid);
    position: sticky;
    top: 0;
    z-index: 50;
}

.page-title {
    font-family: 'Syne', sans-serif;
    font-weight: 800;
    font-size: 1.2rem;
    color: var(--text);
}

.topbar-right {
    display: flex;
    align-items: center;
    gap: 14px;
}

.live-badge {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.7rem;
    font-weight: 700;
    color: var(--teal);
    text-transform: uppercase;
    letter-spacing: 0.08em;
}

.live-dot {
    width: 7px; height: 7px;
    border-radius: 50%;
    background: var(--teal);
    animation: pulse 1.5s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.4; transform: scale(0.8); }
}

.topbar-btn {
    background: rgba(255,255,255,0.06);
    border: 1px solid var(--border);
    color: var(--text);
    border-radius: 8px;
    padding: 7px 14px;
    font-size: 0.75rem;
    font-weight: 700;
    text-decoration: none;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    transition: 0.2s;
}

.topbar-btn:hover { background: rgba(255,255,255,0.1); color: var(--gold); }
.topbar-btn.gold { background: var(--gold); color: var(--ink); border-color: var(--gold); }
.topbar-btn.gold:hover { background: #e09800; color: var(--ink); }

/* ── CONTENT BODY ── */
.content-body { padding: 28px 32px; flex: 1; }

/* ── KPI CARDS ── */
.kpi-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 14px;
    margin-bottom: 24px;
}

.kpi-card {
    background: var(--ink-mid);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 20px 16px;
    position: relative;
    overflow: hidden;
    transition: 0.2s;
}

.kpi-card:hover { border-color: rgba(255,255,255,0.14); transform: translateY(-2px); }

.kpi-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
}

.kpi-card.gold::before  { background: var(--gold); }
.kpi-card.teal::before  { background: var(--teal); }
.kpi-card.rose::before  { background: var(--rose); }
.kpi-card.sky::before   { background: var(--sky); }
.kpi-card.lime::before  { background: #84CC16; }
.kpi-card.violet::before { background: #A78BFA; }

.kpi-icon {
    font-size: 1.3rem;
    margin-bottom: 12px;
    opacity: 0.7;
}

.kpi-card.gold .kpi-icon  { color: var(--gold); }
.kpi-card.teal .kpi-icon  { color: var(--teal); }
.kpi-card.rose .kpi-icon  { color: var(--rose); }
.kpi-card.sky .kpi-icon   { color: var(--sky); }
.kpi-card.lime .kpi-icon  { color: #84CC16; }
.kpi-card.violet .kpi-icon { color: #A78BFA; }

.kpi-num {
    font-family: 'Syne', sans-serif;
    font-weight: 800;
    font-size: 2rem;
    color: var(--text);
    line-height: 1;
    margin-bottom: 4px;
}

.kpi-label {
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: var(--muted);
}

/* ── PANELS ── */
.panel {
    background: var(--ink-mid);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    overflow: hidden;
}

.panel-head {
    padding: 16px 20px;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.panel-title {
    font-family: 'Syne', sans-serif;
    font-weight: 800;
    font-size: 0.82rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--text);
}

.panel-body { padding: 20px; }

/* ── MATCH TABLE ── */
.match-table { width: 100%; border-collapse: collapse; }
.match-table th {
    font-size: 0.6rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: var(--muted);
    padding: 0 0 10px;
    text-align: left;
    border-bottom: 1px solid var(--border);
}
.match-table td {
    padding: 11px 0;
    border-bottom: 1px solid rgba(255,255,255,0.04);
    font-size: 0.8rem;
    vertical-align: middle;
}
.match-table tr:last-child td { border-bottom: none; }

.status-pill {
    font-size: 0.6rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    padding: 3px 9px;
    border-radius: 20px;
}
.status-pill.confirmed { background: rgba(0,201,167,0.12); color: var(--teal); }
.status-pill.pending   { background: rgba(245,168,0,0.12);  color: var(--gold); }
.status-pill.rejected  { background: rgba(255,77,109,0.12); color: var(--rose); }

/* ── TEAM WIN-RATE BARS ── */
.team-bar-row { margin-bottom: 14px; }
.team-bar-label {
    display: flex;
    justify-content: space-between;
    margin-bottom: 5px;
    font-size: 0.78rem;
    font-weight: 600;
}
.team-bar-track {
    background: rgba(255,255,255,0.06);
    border-radius: 20px;
    height: 8px;
    overflow: hidden;
}
.team-bar-fill {
    height: 100%;
    border-radius: 20px;
    background: linear-gradient(90deg, var(--gold), var(--teal));
    transition: width 1.4s cubic-bezier(.4,0,.2,1);
}

/* ── PLAYER LIST ── */
.player-row {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 0;
    border-bottom: 1px solid rgba(255,255,255,0.04);
}
.player-row:last-child { border-bottom: none; }

.player-avatar {
    width: 36px; height: 36px;
    border-radius: 9px;
    background: linear-gradient(135deg, var(--ink-soft), var(--ink));
    border: 1px solid var(--border);
    display: flex; align-items: center; justify-content: center;
    font-weight: 800;
    font-size: 0.8rem;
    color: var(--gold);
    flex-shrink: 0;
    text-transform: uppercase;
}

.player-name { font-weight: 700; font-size: 0.82rem; }
.player-meta { font-size: 0.68rem; color: var(--muted); }

.team-tag {
    margin-left: auto;
    font-size: 0.6rem;
    font-weight: 700;
    padding: 3px 9px;
    border-radius: 20px;
    background: rgba(56,189,248,0.1);
    color: var(--sky);
    text-transform: uppercase;
    flex-shrink: 0;
}

/* ── STATUS RING ── */
.status-ring-wrap {
    display: flex;
    align-items: center;
    gap: 20px;
}

.ring-legend { flex: 1; }
.legend-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 6px 0;
    font-size: 0.78rem;
    border-bottom: 1px solid rgba(255,255,255,0.04);
}
.legend-item:last-child { border-bottom: none; }
.legend-dot {
    width: 9px; height: 9px;
    border-radius: 50%;
    margin-right: 8px;
    flex-shrink: 0;
}

/* ── SYSTEM STATUS ── */
.sys-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 11px 0;
    border-bottom: 1px solid rgba(255,255,255,0.04);
    font-size: 0.8rem;
}
.sys-row:last-child { border-bottom: none; }

.sys-indicator {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.72rem;
    font-weight: 700;
}

.sys-dot {
    width: 7px; height: 7px;
    border-radius: 50%;
}

/* ── QUICK ACTIONS ── */
.quick-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

.quick-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px 16px;
    background: rgba(255,255,255,0.04);
    border: 1px solid var(--border);
    border-radius: 10px;
    text-decoration: none;
    color: var(--text);
    font-weight: 700;
    font-size: 0.78rem;
    transition: 0.2s;
}

.quick-btn:hover {
    background: rgba(245,168,0,0.08);
    border-color: rgba(245,168,0,0.3);
    color: var(--gold);
}

.quick-btn-icon {
    width: 34px; height: 34px;
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}

/* ── MONO NUMBERS ── */
.mono { font-family: 'DM Mono', monospace; }

/* ── SCROLLBAR ── */
::-webkit-scrollbar { width: 4px; height: 4px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: var(--ink-soft); border-radius: 10px; }

/* ── ANIMATIONS ── */
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(12px); }
    to   { opacity: 1; transform: translateY(0); }
}

.kpi-card { animation: fadeUp 0.5s ease both; }
.kpi-card:nth-child(1) { animation-delay: 0.05s; }
.kpi-card:nth-child(2) { animation-delay: 0.10s; }
.kpi-card:nth-child(3) { animation-delay: 0.15s; }
.kpi-card:nth-child(4) { animation-delay: 0.20s; }
.kpi-card:nth-child(5) { animation-delay: 0.25s; }
.kpi-card:nth-child(6) { animation-delay: 0.30s; }
</style>
</head>
<body>

<!-- ══════════════ SIDEBAR ══════════════ -->
<div id="sidebar">
    <div class="sidebar-logo">
        <div class="logo-mark">NB</div>
        <div class="logo-title">NBSC Admin</div>
        <div class="logo-sub">Command Center</div>
    </div>

    <div class="sidebar-nav">
        <div class="nav-section-label">Main</div>
        <a class="nav-item active" href="admin.php">
            <span class="nav-icon"><i class="bi bi-grid-fill"></i></span> Overview
        </a>
        <a class="nav-item" href="/ICS_APP_DEV1/userManagement/view_teams.php">
            <span class="nav-icon"><i class="bi bi-shield-fill"></i></span> Teams
        </a>
        <a class="nav-item" href="/ICS_APP_DEV1/match_system/matches.php">
            <span class="nav-icon"><i class="bi bi-trophy-fill"></i></span> Matches
        </a>
        <a class="nav-item" href="/ICS_APP_DEV1/userManagement/manage_users.php">
            <span class="nav-icon"><i class="bi bi-people-fill"></i></span> Players
        </a>

        <div class="nav-section-label">Management</div>
        <a class="nav-item" href="/ICS_APP_DEV1/dashboard_and_admin/dashboardmanager.php">
            <span class="nav-icon"><i class="bi bi-cpu-fill"></i></span> Task Manager
        </a>
        <a class="nav-item" href="/ICS_APP_DEV1/userManagement/schedule.php">
            <span class="nav-icon"><i class="bi bi-calendar3"></i></span> Schedule
        </a>
        <a class="nav-item" href="/ICS_APP_DEV1/Teams%26history1/battle_history.php">
            <span class="nav-icon"><i class="bi bi-bar-chart-fill"></i></span> Battle History
        </a>

        <div class="nav-section-label">System</div>
        <a class="nav-item" href="/ICS_APP_DEV1/index.php">
            <span class="nav-icon"><i class="bi bi-house-fill"></i></span> Homepage
        </a>
        <a class="nav-item" href="/ICS_APP_DEV1/authentication/logout.php" style="color:#FF4D6D;">
            <span class="nav-icon"><i class="bi bi-box-arrow-left"></i></span> Sign Out
        </a>
    </div>

    <div class="sidebar-footer">
        <div class="admin-chip">
            <div class="admin-avatar"><?= strtoupper(substr($_SESSION['username'], 0, 2)); ?></div>
            <div>
                <div style="font-weight:700;font-size:.78rem;"><?= htmlspecialchars($_SESSION['username']); ?></div>
                <div style="font-size:.62rem;color:var(--muted);">Administrator</div>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════ MAIN ══════════════ -->
<div id="main">

    <!-- TOP BAR -->
    <div class="topbar">
        <div>
            <div class="page-title">System Overview</div>
            <div style="font-size:.7rem;color:var(--muted);margin-top:2px;"><?= date('l, F j, Y'); ?></div>
        </div>
        <div class="topbar-right">
            <div class="live-badge"><div class="live-dot"></div> Live</div>
            <a href="/ICS_APP_DEV1/dashboard_and_admin/dashboardmanager.php" class="topbar-btn gold">
                <i class="bi bi-cpu-fill me-1"></i> Task Manager
            </a>
            <a href="/ICS_APP_DEV1/index.php" class="topbar-btn">
                <i class="bi bi-house me-1"></i> Homepage
            </a>
        </div>
    </div>

    <div class="content-body">

        <!-- KPI ROW -->
        <div class="kpi-grid">
            <div class="kpi-card gold">
                <div class="kpi-icon"><i class="bi bi-person-fill"></i></div>
                <div class="kpi-num mono"><?= $total_players; ?></div>
                <div class="kpi-label">Players</div>
            </div>
            <div class="kpi-card teal">
                <div class="kpi-icon"><i class="bi bi-shield-fill"></i></div>
                <div class="kpi-num mono"><?= $total_teams; ?></div>
                <div class="kpi-label">Teams</div>
            </div>
            <div class="kpi-card sky">
                <div class="kpi-icon"><i class="bi bi-flag-fill"></i></div>
                <div class="kpi-num mono"><?= $total_matches; ?></div>
                <div class="kpi-label">Total Matches</div>
            </div>
            <div class="kpi-card lime">
                <div class="kpi-icon"><i class="bi bi-check-circle-fill"></i></div>
                <div class="kpi-num mono"><?= $confirmed_matches; ?></div>
                <div class="kpi-label">Confirmed</div>
            </div>
            <div class="kpi-card rose">
                <div class="kpi-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
                <div class="kpi-num mono"><?= $pending_reqs; ?></div>
                <div class="kpi-label">Pending</div>
            </div>
            <div class="kpi-card violet">
                <div class="kpi-icon"><i class="bi bi-calendar-check-fill"></i></div>
                <div class="kpi-num mono"><?= $total_reservations; ?></div>
                <div class="kpi-label">Reservations</div>
            </div>
        </div>

        <!-- ROW 2: Charts -->
        <div class="row g-3 mb-3">

            <!-- Monthly Activity Line -->
            <div class="col-md-5">
                <div class="panel h-100">
                    <div class="panel-head">
                        <div class="panel-title">Match Activity</div>
                        <span style="font-size:.65rem;color:var(--muted);">Last 6 months</span>
                    </div>
                    <div class="panel-body">
                        <canvas id="activityChart" height="180"></canvas>
                    </div>
                </div>
            </div>

            <!-- Status Doughnut -->
            <div class="col-md-3">
                <div class="panel h-100">
                    <div class="panel-head">
                        <div class="panel-title">Match Status</div>
                    </div>
                    <div class="panel-body">
                        <canvas id="statusChart" height="140" style="max-width:140px;margin:0 auto;display:block;"></canvas>
                        <div class="mt-3">
                            <div class="legend-item">
                                <div style="display:flex;align-items:center;">
                                    <div class="legend-dot" style="background:var(--teal);"></div>
                                    Confirmed
                                </div>
                                <span class="mono" style="font-size:.78rem;"><?= $status_data['confirmed']; ?></span>
                            </div>
                            <div class="legend-item">
                                <div style="display:flex;align-items:center;">
                                    <div class="legend-dot" style="background:var(--gold);"></div>
                                    Pending
                                </div>
                                <span class="mono" style="font-size:.78rem;"><?= $status_data['pending']; ?></span>
                            </div>
                            <div class="legend-item">
                                <div style="display:flex;align-items:center;">
                                    <div class="legend-dot" style="background:var(--rose);"></div>
                                    Rejected
                                </div>
                                <span class="mono" style="font-size:.78rem;"><?= $status_data['rejected']; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reservations bar -->
            <div class="col-md-4">
                <div class="panel h-100">
                    <div class="panel-head">
                        <div class="panel-title">Reservations / Month</div>
                    </div>
                    <div class="panel-body">
                        <canvas id="reservationChart" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- ROW 3: Team win rates + Recent Matches -->
        <div class="row g-3 mb-3">

            <!-- Team Win Rate -->
            <div class="col-md-4">
                <div class="panel h-100">
                    <div class="panel-head">
                        <div class="panel-title">Team Win Rates</div>
                        <span style="font-size:.65rem;color:var(--muted);">Top performers</span>
                    </div>
                    <div class="panel-body">
                        <?php if (empty($team_stats)): ?>
                            <p style="color:var(--muted);font-size:.8rem;">No confirmed match data yet.</p>
                        <?php else: ?>
                            <?php foreach ($team_stats as $ts): ?>
                            <div class="team-bar-row">
                                <div class="team-bar-label">
                                    <span><?= htmlspecialchars($ts['team_name']); ?></span>
                                    <span class="mono" style="color:var(--gold);"><?= $ts['win_pct']; ?>%</span>
                                </div>
                                <div class="team-bar-track">
                                    <div class="team-bar-fill" data-width="<?= $ts['win_pct']; ?>" style="width:0%"></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Matches -->
            <div class="col-md-8">
                <div class="panel h-100">
                    <div class="panel-head">
                        <div class="panel-title">Recent Matches</div>
                        <a href="/ICS_APP_DEV1/match_system/matches.php" style="font-size:.68rem;color:var(--gold);text-decoration:none;font-weight:700;">View All →</a>
                    </div>
                    <div class="panel-body" style="padding:0 20px;">
                        <table class="match-table">
                            <thead>
                                <tr>
                                    <th>Home</th>
                                    <th>Score</th>
                                    <th>Away</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($recent_matches && $recent_matches->num_rows > 0):
                                    while ($rm = $recent_matches->fetch_assoc()): ?>
                                <tr>
                                    <td style="font-weight:700;"><?= htmlspecialchars($rm['home_n']); ?></td>
                                    <td>
                                        <?php if ($rm['final_status'] === 'confirmed'): ?>
                                            <span class="mono" style="color:var(--text);font-weight:700;"><?= $rm['home_score']; ?> – <?= $rm['away_score']; ?></span>
                                        <?php else: ?>
                                            <span style="color:var(--muted);">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-weight:700;"><?= htmlspecialchars($rm['away_n']); ?></td>
                                    <td style="color:var(--muted);"><?= date('M d', strtotime($rm['reservation_date'])); ?></td>
                                    <td><span class="status-pill <?= $rm['final_status']; ?>"><?= ucfirst($rm['final_status']); ?></span></td>
                                </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                <tr><td colspan="5" style="color:var(--muted);padding:20px 0;">No matches found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ROW 4: Players + Quick Actions + System Status -->
        <div class="row g-3">

            <!-- Recent Players -->
            <div class="col-md-4">
                <div class="panel h-100">
                    <div class="panel-head">
                        <div class="panel-title">Recent Players</div>
                        <a href="/ICS_APP_DEV1/userManagement/manage_users.php" style="font-size:.68rem;color:var(--gold);text-decoration:none;font-weight:700;">Manage →</a>
                    </div>
                    <div class="panel-body">
                        <?php if ($recent_players && $recent_players->num_rows > 0):
                            while ($rp = $recent_players->fetch_assoc()): ?>
                        <div class="player-row">
                            <div class="player-avatar"><?= strtoupper(substr($rp['full_name'] ?? $rp['username'], 0, 2)); ?></div>
                            <div>
                                <div class="player-name"><?= htmlspecialchars($rp['full_name'] ?? $rp['username']); ?></div>
                                <div class="player-meta"><?= htmlspecialchars($rp['course'] ?? 'No course'); ?></div>
                            </div>
                            <?php if ($rp['team_name']): ?>
                                <div class="team-tag"><?= htmlspecialchars($rp['team_name']); ?></div>
                            <?php endif; ?>
                        </div>
                        <?php endwhile; else: ?>
                            <p style="color:var(--muted);font-size:.8rem;">No players found.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="col-md-4">
                <div class="panel h-100">
                    <div class="panel-head">
                        <div class="panel-title">Quick Actions</div>
                    </div>
                    <div class="panel-body">
                        <div class="quick-grid">
                            <a href="/ICS_APP_DEV1/userManagement/add_team.php" class="quick-btn">
                                <div class="quick-btn-icon" style="background:rgba(245,168,0,0.1);color:var(--gold);">
                                    <i class="bi bi-plus-circle-fill"></i>
                                </div>
                                New Team
                            </a>
                            <a href="/ICS_APP_DEV1/userManagement/schedule.php" class="quick-btn">
                                <div class="quick-btn-icon" style="background:rgba(0,201,167,0.1);color:var(--teal);">
                                    <i class="bi bi-calendar-check-fill"></i>
                                </div>
                                Schedule
                            </a>
                            <a href="/ICS_APP_DEV1/userManagement/view_teams.php" class="quick-btn">
                                <div class="quick-btn-icon" style="background:rgba(56,189,248,0.1);color:var(--sky);">
                                    <i class="bi bi-shield-fill"></i>
                                </div>
                                View Teams
                            </a>
                            <a href="/ICS_APP_DEV1/match_system/matches.php" class="quick-btn">
                                <div class="quick-btn-icon" style="background:rgba(255,77,109,0.1);color:var(--rose);">
                                    <i class="bi bi-trophy-fill"></i>
                                </div>
                                Matches
                            </a>
                            <a href="/ICS_APP_DEV1/Teams%26history1/battle_history.php" class="quick-btn">
                                <div class="quick-btn-icon" style="background:rgba(132,204,22,0.1);color:#84CC16;">
                                    <i class="bi bi-bar-chart-fill"></i>
                                </div>
                                History
                            </a>
                            <a href="/ICS_APP_DEV1/userManagement/manage_users.php" class="quick-btn">
                                <div class="quick-btn-icon" style="background:rgba(167,139,250,0.1);color:#A78BFA;">
                                    <i class="bi bi-person-gear-fill"></i>
                                </div>
                                Users
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Status -->
            <div class="col-md-4">
                <div class="panel h-100">
                    <div class="panel-head">
                        <div class="panel-title">System Status</div>
                    </div>
                    <div class="panel-body">
                        <div class="sys-row">
                            <span>Database</span>
                            <div class="sys-indicator" style="color:var(--teal);">
                                <div class="sys-dot" style="background:var(--teal);"></div> Connected
                            </div>
                        </div>
                        <div class="sys-row">
                            <span>Match Sync</span>
                            <div class="sys-indicator" style="color:var(--sky);">
                                <div class="sys-dot" style="background:var(--sky);animation:pulse 1.5s infinite;"></div> Active
                            </div>
                        </div>
                        <div class="sys-row">
                            <span>Admin Session</span>
                            <div class="sys-indicator" style="color:var(--gold);">
                                <div class="sys-dot" style="background:var(--gold);"></div> Expires 2h
                            </div>
                        </div>
                        <div class="sys-row">
                            <span>Server</span>
                            <div class="sys-indicator" style="color:var(--teal);">
                                <div class="sys-dot" style="background:var(--teal);"></div> Apache/PHP
                            </div>
                        </div>
                        <div class="sys-row">
                            <span>PHP Version</span>
                            <span class="mono" style="color:var(--muted);font-size:.72rem;"><?= phpversion(); ?></span>
                        </div>
                        <div class="sys-row">
                            <span>Current Time</span>
                            <span class="mono" style="color:var(--muted);font-size:.72rem;" id="liveClock"></span>
                        </div>
                        <hr style="border-color:var(--border);margin:14px 0;">
                        <div style="font-size:.7rem;color:var(--muted);margin-bottom:8px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;">Completion Rate</div>
                        <?php
                            $comp_rate = $total_matches > 0 ? round($confirmed_matches / $total_matches * 100) : 0;
                        ?>
                        <div style="display:flex;justify-content:space-between;font-size:.78rem;margin-bottom:5px;">
                            <span>Matches Finished</span>
                            <span class="mono" style="color:var(--gold);"><?= $comp_rate; ?>%</span>
                        </div>
                        <div style="background:rgba(255,255,255,0.06);border-radius:20px;height:7px;overflow:hidden;">
                            <div style="height:100%;width:<?= $comp_rate; ?>%;background:linear-gradient(90deg,var(--gold),var(--teal));border-radius:20px;transition:width 1.4s ease;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div><!-- /content-body -->
</div><!-- /main -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ── LIVE CLOCK ──
function updateClock() {
    const el = document.getElementById('liveClock');
    if (el) el.textContent = new Date().toLocaleTimeString('en-PH', { hour:'2-digit', minute:'2-digit', second:'2-digit' });
}
setInterval(updateClock, 1000);
updateClock();

// ── CHART DEFAULTS ──
Chart.defaults.color = '#5A7A9F';
Chart.defaults.font.family = 'Manrope';
Chart.defaults.font.size = 11;

const gridColor = 'rgba(255,255,255,0.05)';

// ── ACTIVITY LINE CHART ──
new Chart(document.getElementById('activityChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode(array_keys($monthly_data)); ?>,
        datasets: [{
            label: 'Matches',
            data: <?= json_encode(array_values($monthly_data)); ?>,
            borderColor: '#F5A800',
            backgroundColor: 'rgba(245,168,0,0.08)',
            tension: 0.45,
            fill: true,
            pointBackgroundColor: '#F5A800',
            pointRadius: 5,
            pointHoverRadius: 7,
            borderWidth: 2.5,
        }]
    },
    options: {
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { color: gridColor }, ticks: { color: '#5A7A9F' } },
            y: { grid: { color: gridColor }, ticks: { color: '#5A7A9F', stepSize: 1 }, beginAtZero: true }
        }
    }
});

// ── STATUS DOUGHNUT ──
new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: ['Confirmed','Pending','Rejected'],
        datasets: [{
            data: [<?= $status_data['confirmed']; ?>, <?= $status_data['pending']; ?>, <?= $status_data['rejected']; ?>],
            backgroundColor: ['#00C9A7','#F5A800','#FF4D6D'],
            borderWidth: 0,
            hoverOffset: 6,
        }]
    },
    options: {
        cutout: '68%',
        plugins: { legend: { display: false }, tooltip: { callbacks: {
            label: ctx => ` ${ctx.label}: ${ctx.raw}`
        }}}
    }
});

// ── RESERVATIONS BAR ──
new Chart(document.getElementById('reservationChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_keys($res_monthly)); ?>,
        datasets: [{
            label: 'Reservations',
            data: <?= json_encode(array_values($res_monthly)); ?>,
            backgroundColor: 'rgba(56,189,248,0.25)',
            borderColor: '#38BDF8',
            borderWidth: 2,
            borderRadius: 6,
        }]
    },
    options: {
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { color: gridColor }, ticks: { color: '#5A7A9F' } },
            y: { grid: { color: gridColor }, ticks: { color: '#5A7A9F', stepSize: 1 }, beginAtZero: true }
        }
    }
});

// ── ANIMATE TEAM BARS ──
setTimeout(() => {
    document.querySelectorAll('.team-bar-fill').forEach(el => {
        el.style.width = el.dataset.width + '%';
    });
}, 300);
</script>
</body>
</html>