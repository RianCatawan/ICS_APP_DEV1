<?php
session_start();
require_once __DIR__ . '/../database_config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /authentication/login.php");
    exit();
}

// ── KPI STATS ──
$total_players      = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='player'")->fetch_assoc()['c'] ?? 0;
$total_teams        = $conn->query("SELECT COUNT(*) as c FROM teams")->fetch_assoc()['c'] ?? 0;
$total_matches      = $conn->query("SELECT COUNT(*) as c FROM match_requests")->fetch_assoc()['c'] ?? 0;
$confirmed_matches  = $conn->query("SELECT COUNT(*) as c FROM match_requests WHERE final_status='confirmed'")->fetch_assoc()['c'] ?? 0;
$pending_reqs       = $conn->query("SELECT COUNT(*) as c FROM match_requests WHERE final_status='pending'")->fetch_assoc()['c'] ?? 0;
$total_reservations = $conn->query("SELECT COUNT(*) as c FROM reservations")->fetch_assoc()['c'] ?? 0;

// ── MONTHLY MATCH ACTIVITY (last 6 months) ──
$monthly_labels = [];
$monthly_values = [];
for ($i = 5; $i >= 0; $i--) {
    $month_start = date('Y-m-01', strtotime("-$i months"));
    $month_end   = date('Y-m-t',  strtotime("-$i months"));
    $label       = date('M y',    strtotime("-$i months"));
    $res = $conn->query("SELECT COUNT(*) as c FROM match_requests mr
        JOIN reservations r ON mr.reservation_id = r.id
        WHERE r.reservation_date BETWEEN '$month_start' AND '$month_end'");
    $monthly_labels[] = $label;
    $monthly_values[] = (int)($res->fetch_assoc()['c'] ?? 0);
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
$status_res  = $conn->query("SELECT final_status, COUNT(*) as c FROM match_requests GROUP BY final_status");
$status_data = ['pending' => 0, 'confirmed' => 0, 'rejected' => 0];
if ($status_res) while ($row = $status_res->fetch_assoc()) $status_data[$row['final_status']] = $row['c'];

// ── RESERVATIONS PER MONTH ──
$res_labels = [];
$res_values = [];
for ($i = 5; $i >= 0; $i--) {
    $ms = date('Y-m-01', strtotime("-$i months"));
    $me = date('Y-m-t',  strtotime("-$i months"));
    $lb = date('M y',    strtotime("-$i months"));
    $r  = $conn->query("SELECT COUNT(*) as c FROM reservations WHERE reservation_date BETWEEN '$ms' AND '$me'");
    $res_labels[] = $lb;
    $res_values[] = (int)($r->fetch_assoc()['c'] ?? 0);
}

$comp_rate = $total_matches > 0 ? round($confirmed_matches / $total_matches * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>NBSC Admin — Command Center</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@700;800;900&family=Barlow:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
    --navy:    #060D1A;
    --navy2:   #0D1F38;
    --navy3:   #152844;
    --gold:    #F5A800;
    --gold2:   #FBBF24;
    --teal:    #00C9A7;
    --rose:    #FF4D6D;
    --sky:     #38BDF8;
    --lime:    #84CC16;
    --violet:  #A78BFA;
    --text:    #E2EAF4;
    --muted:   #5A7A9F;
    --border:  rgba(255,255,255,0.07);
    --sidebar: 220px;
    --radius:  10px;
}

html, body {
    height: 100%;
    background: var(--navy);
    color: var(--text);
    font-family: 'Barlow', sans-serif;
    font-size: 13px;
    overflow-x: hidden;
}

/* ─── LAYOUT ─────────────────────────── */
.shell { display: flex; min-height: 100vh; }

/* ─── SIDEBAR ─────────────────────────── */
#sidebar {
    width: var(--sidebar);
    flex-shrink: 0;
    background: var(--navy2);
    border-right: 1px solid var(--border);
    display: flex;
    flex-direction: column;
    position: fixed;
    top: 0; left: 0;
    height: 100vh;
    z-index: 100;
    overflow-y: auto;
}

.sb-logo {
    padding: 22px 18px 16px;
    border-bottom: 1px solid var(--border);
}
.sb-mark {
    width: 38px; height: 38px;
    background: var(--gold);
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-family: 'Barlow Condensed', sans-serif;
    font-weight: 900; font-size: 15px;
    color: var(--navy);
    margin-bottom: 9px;
}
.sb-title {
    font-family: 'Barlow Condensed', sans-serif;
    font-weight: 900; font-size: 14px;
    color: var(--text);
    text-transform: uppercase; letter-spacing: .05em;
}
.sb-sub { font-size: 10px; color: var(--muted); letter-spacing: .07em; text-transform: uppercase; margin-top: 2px; }

.sb-nav { padding: 10px 10px; flex: 1; }

.sb-section {
    font-size: 9px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .11em;
    color: var(--muted);
    padding: 12px 8px 4px;
}

.nav-item {
    display: flex; align-items: center; gap: 9px;
    padding: 9px 10px;
    border-radius: 8px;
    color: var(--muted);
    text-decoration: none;
    font-weight: 600; font-size: 12px;
    transition: .15s;
    margin-bottom: 2px;
}
.nav-item:hover { background: rgba(255,255,255,.05); color: var(--text); }
.nav-item.active { background: rgba(245,168,0,.12); color: var(--gold); }
.nav-item .ni { font-size: 14px; width: 18px; text-align: center; }

.sb-foot {
    padding: 12px 10px;
    border-top: 1px solid var(--border);
}
.admin-chip {
    display: flex; align-items: center; gap: 9px;
    padding: 9px 10px;
    background: rgba(255,255,255,.04);
    border-radius: 8px;
}
.admin-av {
    width: 30px; height: 30px;
    background: linear-gradient(135deg, var(--gold), var(--teal));
    border-radius: 7px;
    display: flex; align-items: center; justify-content: center;
    font-weight: 900; font-size: 11px; color: var(--navy);
    flex-shrink: 0;
}

/* ─── MAIN ─────────────────────────── */
#main {
    margin-left: var(--sidebar);
    flex: 1;
    display: flex;
    flex-direction: column;
    min-width: 0;
}

/* ─── TOPBAR ─────────────────────────── */
.topbar {
    padding: 14px 24px;
    border-bottom: 1px solid var(--border);
    background: var(--navy2);
    display: flex; align-items: center; justify-content: space-between;
    position: sticky; top: 0; z-index: 50;
}
.page-title {
    font-family: 'Barlow Condensed', sans-serif;
    font-weight: 900; font-size: 17px;
    color: var(--text); letter-spacing: .03em;
}
.tb-date { font-size: 10px; color: var(--muted); margin-top: 1px; }
.tb-right { display: flex; align-items: center; gap: 10px; }

.live-badge {
    display: flex; align-items: center; gap: 5px;
    font-size: 10px; font-weight: 700;
    color: var(--teal); text-transform: uppercase; letter-spacing: .08em;
}
.live-dot {
    width: 6px; height: 6px; border-radius: 50%;
    background: var(--teal);
    animation: pulse 1.5s infinite;
}
@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.3} }

.tb-btn {
    background: rgba(255,255,255,.06);
    border: 1px solid var(--border);
    color: var(--text);
    border-radius: 7px; padding: 6px 12px;
    font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .04em;
    text-decoration: none; display: inline-flex; align-items: center; gap: 5px;
    transition: .2s; cursor: pointer;
}
.tb-btn:hover { background: rgba(255,255,255,.1); color: var(--gold); }
.tb-btn.gold { background: var(--gold); color: var(--navy); border-color: var(--gold); }
.tb-btn.gold:hover { background: var(--gold2); color: var(--navy); }

/* ─── CONTENT ─────────────────────────── */
.content { padding: 20px 24px 40px; flex: 1; }

/* ─── KPI GRID ─────────────────────────── */
.kpi-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 10px;
    margin-bottom: 14px;
}
.kpi {
    background: var(--navy2);
    border: 1px solid var(--border);
    border-top: 3px solid transparent;
    border-radius: var(--radius);
    padding: 16px 14px;
    transition: .2s;
    animation: fadeUp .5s ease both;
}
.kpi:hover { transform: translateY(-2px); border-color: rgba(255,255,255,.12); }
.kpi:nth-child(1){animation-delay:.05s} .kpi:nth-child(2){animation-delay:.10s}
.kpi:nth-child(3){animation-delay:.15s} .kpi:nth-child(4){animation-delay:.20s}
.kpi:nth-child(5){animation-delay:.25s} .kpi:nth-child(6){animation-delay:.30s}
@keyframes fadeUp { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:translateY(0)} }

.kpi.gold  { border-top-color: var(--gold); }
.kpi.teal  { border-top-color: var(--teal); }
.kpi.sky   { border-top-color: var(--sky); }
.kpi.lime  { border-top-color: var(--lime); }
.kpi.rose  { border-top-color: var(--rose); }
.kpi.violet{ border-top-color: var(--violet); }

.kpi-icon { font-size: 18px; margin-bottom: 10px; opacity: .75; }
.kpi.gold  .kpi-icon { color: var(--gold); }
.kpi.teal  .kpi-icon { color: var(--teal); }
.kpi.sky   .kpi-icon { color: var(--sky); }
.kpi.lime  .kpi-icon { color: var(--lime); }
.kpi.rose  .kpi-icon { color: var(--rose); }
.kpi.violet.kpi-icon { color: var(--violet); }

.kpi-num {
    font-family: 'Barlow Condensed', sans-serif;
    font-weight: 900; font-size: 28px;
    color: var(--text); line-height: 1; margin-bottom: 3px;
}
.kpi-lbl {
    font-size: 9px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .1em;
    color: var(--muted);
}

/* ─── PANEL ─────────────────────────── */
.panel {
    background: var(--navy2);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    overflow: hidden;
}
.ph {
    padding: 12px 16px;
    border-bottom: 1px solid var(--border);
    display: flex; align-items: center; justify-content: space-between;
}
.pt {
    font-family: 'Barlow Condensed', sans-serif;
    font-weight: 800; font-size: 12px;
    text-transform: uppercase; letter-spacing: .07em;
    color: var(--text);
}
.pm { font-size: 10px; color: var(--muted); }
.ph a { font-size: 10px; color: var(--gold); text-decoration: none; font-weight: 700; }
.ph a:hover { color: var(--gold2); }
.pb { padding: 14px 16px; }

/* ─── GRID ROWS ─────────────────────────── */
.row2 { display: grid; grid-template-columns: 5fr 3fr 4fr; gap: 10px; margin-bottom: 10px; }
.row3 { display: grid; grid-template-columns: 4fr 8fr; gap: 10px; margin-bottom: 10px; }
.row4 { display: grid; grid-template-columns: 4fr 4fr 4fr; gap: 10px; }

/* ─── MATCH TABLE ─────────────────────────── */
.match-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
.match-table th {
    font-size: 9px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .1em;
    color: var(--muted); padding: 0 0 9px;
    text-align: left; border-bottom: 1px solid var(--border);
}
.match-table td {
    padding: 9px 0;
    border-bottom: 1px solid rgba(255,255,255,.04);
    font-size: 11px; vertical-align: middle;
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.match-table tr:last-child td { border-bottom: none; }
.match-table td.fw { font-weight: 700; color: var(--text); }
.match-table td.mt { color: var(--muted); }

.status-pill {
    font-size: 9px; font-weight: 800;
    text-transform: uppercase; letter-spacing: .05em;
    padding: 2px 8px; border-radius: 99px;
}
.status-pill.confirmed { background: rgba(0,201,167,.12); color: var(--teal); }
.status-pill.pending   { background: rgba(245,168,0,.12);  color: var(--gold); }
.status-pill.rejected  { background: rgba(255,77,109,.12); color: var(--rose); }

/* ─── WIN RATE BARS ─────────────────────────── */
.bar-row { margin-bottom: 11px; }
.bar-label {
    display: flex; justify-content: space-between;
    margin-bottom: 5px; font-size: 11px; font-weight: 600; color: var(--text);
}
.bar-pct {
    font-family: 'Barlow Condensed', sans-serif;
    font-weight: 800; color: var(--gold);
}
.bar-track {
    background: rgba(255,255,255,.06);
    border-radius: 99px; height: 7px; overflow: hidden;
}
.bar-fill {
    height: 100%; border-radius: 99px;
    background: linear-gradient(90deg, var(--gold), var(--teal));
    width: 0;
    transition: width 1.3s cubic-bezier(.4,0,.2,1);
}

/* ─── PLAYER LIST ─────────────────────────── */
.player-row {
    display: flex; align-items: center; gap: 10px;
    padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,.04);
}
.player-row:last-child { border-bottom: none; }
.player-av {
    width: 34px; height: 34px; border-radius: 8px;
    background: var(--navy3); border: 1px solid var(--border);
    display: flex; align-items: center; justify-content: center;
    font-weight: 800; font-size: 11px; color: var(--gold);
    flex-shrink: 0; text-transform: uppercase;
}
.pname { font-weight: 700; font-size: 11px; color: var(--text); }
.pmeta { font-size: 9px; color: var(--muted); }
.team-tag {
    margin-left: auto; flex-shrink: 0;
    font-size: 9px; font-weight: 700; text-transform: uppercase;
    padding: 2px 7px; border-radius: 99px;
    background: rgba(56,189,248,.1); color: var(--sky);
}

/* ─── QUICK ACTIONS ─────────────────────────── */
.quick-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.quick-btn {
    display: flex; align-items: center; gap: 9px;
    padding: 11px 12px;
    background: rgba(255,255,255,.04);
    border: 1px solid var(--border);
    border-radius: 8px; text-decoration: none;
    color: var(--text); font-weight: 700; font-size: 11px;
    transition: .18s; cursor: pointer;
}
.quick-btn:hover {
    background: rgba(245,168,0,.08);
    border-color: rgba(245,168,0,.28);
    color: var(--gold);
}
.qi {
    width: 30px; height: 30px; border-radius: 7px;
    display: flex; align-items: center; justify-content: center;
    font-size: 15px; flex-shrink: 0;
}

/* ─── SYSTEM STATUS ─────────────────────────── */
.sys-row {
    display: flex; align-items: center; justify-content: space-between;
    padding: 9px 0; border-bottom: 1px solid rgba(255,255,255,.04); font-size: 11px;
}
.sys-row:last-child { border-bottom: none; }
.sys-ind { display: flex; align-items: center; gap: 5px; font-size: 10px; font-weight: 700; }
.sys-dot { width: 6px; height: 6px; border-radius: 50%; }

/* ─── CHART LEGEND ─────────────────────────── */
.lgnd { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 6px; }
.lgnd-item { display: flex; align-items: center; gap: 4px; font-size: 10px; color: var(--muted); }
.lgnd-sq { width: 8px; height: 8px; border-radius: 2px; flex-shrink: 0; }

/* ─── DONUT LEGEND ─────────────────────────── */
.dl-item {
    display: flex; justify-content: space-between; align-items: center;
    font-size: 10px; padding: 4px 0;
    border-bottom: 1px solid rgba(255,255,255,.04);
}
.dl-item:last-child { border-bottom: none; }
.dl-dot { width: 7px; height: 7px; border-radius: 50%; display: inline-block; margin-right: 5px; }

/* ─── COMPLETION BAR ─────────────────────────── */
.comp-bar-track {
    background: rgba(255,255,255,.06); border-radius: 99px;
    height: 6px; overflow: hidden; margin-top: 5px;
}
.comp-bar-fill {
    height: 100%; border-radius: 99px;
    background: linear-gradient(90deg, var(--gold), var(--teal));
    transition: width 1.3s ease;
}

/* ─── SCROLLBAR ─────────────────────────── */
::-webkit-scrollbar { width: 4px; height: 4px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: var(--navy3); border-radius: 10px; }
</style>
</head>
<body>

<div class="shell">

    <!-- ══ SIDEBAR ══ -->
    <div id="sidebar">
        <div class="sb-logo">
            <div class="sb-mark">NB</div>
            <div class="sb-title">NBSC Admin</div>
            <div class="sb-sub">Command Center</div>
        </div>

        <div class="sb-nav">
            <div class="sb-section">Main</div>
            <a class="nav-item active" href="admin.php">
                <i class="bi bi-grid-fill ni"></i> Overview
            </a>
            <a class="nav-item" href="/ICS_APP_DEV1/userManagement/view_teams.php">
                <i class="bi bi-shield-fill ni"></i> Teams
            </a>
            <a class="nav-item" href="/ICS_APP_DEV1/match_system/matches.php">
                <i class="bi bi-trophy-fill ni"></i> Matches
            </a>
            <a class="nav-item" href="/ICS_APP_DEV1/userManagement/manage_users.php">
                <i class="bi bi-people-fill ni"></i> Players
            </a>

            <div class="sb-section">Management</div>
            <a class="nav-item" href="/ICS_APP_DEV1/dashboard_and_admin/dashboardmanager.php">
                <i class="bi bi-cpu-fill ni"></i> Task Manager
            </a>
            <a class="nav-item" href="/ICS_APP_DEV1/userManagement/schedule.php">
                <i class="bi bi-calendar3 ni"></i> Schedule
            </a>
            <a class="nav-item" href="/ICS_APP_DEV1/Teams%26history1/battle_history.php">
                <i class="bi bi-bar-chart-fill ni"></i> Battle History
            </a>

            <div class="sb-section">System</div>
            <a class="nav-item" href="/ICS_APP_DEV1/index.php">
                <i class="bi bi-house-fill ni"></i> Homepage
            </a>
            <a class="nav-item" href="/ICS_APP_DEV1/authentication/logout.php" style="color:var(--rose);">
                <i class="bi bi-box-arrow-left ni"></i> Sign Out
            </a>
        </div>

        <div class="sb-foot">
            <div class="admin-chip">
                <div class="admin-av"><?= strtoupper(substr($_SESSION['username'], 0, 2)); ?></div>
                <div>
                    <div style="font-weight:700;font-size:11px;color:var(--text)"><?= htmlspecialchars($_SESSION['username']); ?></div>
                    <div style="font-size:9px;color:var(--muted)">Administrator</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ MAIN ══ -->
    <div id="main">

        <!-- TOPBAR -->
        <div class="topbar">
            <div>
                <div class="page-title">System Overview</div>
                <div class="tb-date"><?= date('l, F j, Y'); ?></div>
            </div>
            <div class="tb-right">
                <div class="live-badge"><div class="live-dot"></div> Live</div>
                <a href="/ICS_APP_DEV1/dashboard_and_admin/dashboardmanager.php" class="tb-btn gold">
                    <i class="bi bi-cpu-fill"></i> Task Manager
                </a>
                <a href="/ICS_APP_DEV1/index.php" class="tb-btn">
                    <i class="bi bi-house"></i> Homepage
                </a>
            </div>
        </div>

        <!-- CONTENT -->
        <div class="content">

            <!-- KPI CARDS -->
            <div class="kpi-grid">
                <div class="kpi gold">
                    <div class="kpi-icon"><i class="bi bi-person-fill" style="color:var(--gold)"></i></div>
                    <div class="kpi-num"><?= $total_players; ?></div>
                    <div class="kpi-lbl">Players</div>
                </div>
                <div class="kpi teal">
                    <div class="kpi-icon"><i class="bi bi-shield-fill" style="color:var(--teal)"></i></div>
                    <div class="kpi-num"><?= $total_teams; ?></div>
                    <div class="kpi-lbl">Teams</div>
                </div>
                <div class="kpi sky">
                    <div class="kpi-icon"><i class="bi bi-flag-fill" style="color:var(--sky)"></i></div>
                    <div class="kpi-num"><?= $total_matches; ?></div>
                    <div class="kpi-lbl">Total Matches</div>
                </div>
                <div class="kpi lime">
                    <div class="kpi-icon"><i class="bi bi-check-circle-fill" style="color:var(--lime)"></i></div>
                    <div class="kpi-num"><?= $confirmed_matches; ?></div>
                    <div class="kpi-lbl">Confirmed</div>
                </div>
                <div class="kpi rose">
                    <div class="kpi-icon"><i class="bi bi-exclamation-triangle-fill" style="color:var(--rose)"></i></div>
                    <div class="kpi-num"><?= $pending_reqs; ?></div>
                    <div class="kpi-lbl">Pending</div>
                </div>
                <div class="kpi violet">
                    <div class="kpi-icon"><i class="bi bi-calendar-check-fill" style="color:var(--violet)"></i></div>
                    <div class="kpi-num"><?= $total_reservations; ?></div>
                    <div class="kpi-lbl">Reservations</div>
                </div>
            </div>

            <!-- ROW 2: Charts -->
            <div class="row2">

                <!-- Activity Line -->
                <div class="panel">
                    <div class="ph">
                        <span class="pt">Match Activity</span>
                        <span class="pm">Last 6 months</span>
                    </div>
                    <div class="pb">
                        <div class="lgnd">
                            <span class="lgnd-item"><span class="lgnd-sq" style="background:var(--gold)"></span>Matches</span>
                        </div>
                        <div style="position:relative;height:150px">
                            <canvas id="activityChart" role="img" aria-label="Line chart of match activity over the last 6 months">Monthly match activity data.</canvas>
                        </div>
                    </div>
                </div>

                <!-- Status Donut -->
                <div class="panel">
                    <div class="ph"><span class="pt">Match Status</span></div>
                    <div class="pb">
                        <div style="position:relative;height:110px;width:110px;margin:0 auto 12px">
                            <canvas id="statusChart" role="img" aria-label="Doughnut chart showing match status breakdown: confirmed, pending, and rejected counts">Match status breakdown by count.</canvas>
                        </div>
                        <div>
                            <div class="dl-item">
                                <span><span class="dl-dot" style="background:var(--teal)"></span>Confirmed</span>
                                <span style="font-weight:700;color:var(--text)"><?= $status_data['confirmed']; ?></span>
                            </div>
                            <div class="dl-item">
                                <span><span class="dl-dot" style="background:var(--gold)"></span>Pending</span>
                                <span style="font-weight:700;color:var(--text)"><?= $status_data['pending']; ?></span>
                            </div>
                            <div class="dl-item">
                                <span><span class="dl-dot" style="background:var(--rose)"></span>Rejected</span>
                                <span style="font-weight:700;color:var(--text)"><?= $status_data['rejected']; ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reservations Bar -->
                <div class="panel">
                    <div class="ph"><span class="pt">Reservations / Month</span></div>
                    <div class="pb">
                        <div class="lgnd">
                            <span class="lgnd-item"><span class="lgnd-sq" style="background:var(--sky)"></span>Reservations</span>
                        </div>
                        <div style="position:relative;height:150px">
                            <canvas id="reservationChart" role="img" aria-label="Bar chart of reservations per month over the last 6 months">Monthly reservation data.</canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ROW 3: Win Rates + Recent Matches -->
            <div class="row3">

                <!-- Win Rate Bars -->
                <div class="panel">
                    <div class="ph">
                        <span class="pt">Team Win Rates</span>
                        <span class="pm">Top performers</span>
                    </div>
                    <div class="pb">
                        <?php if (empty($team_stats)): ?>
                            <p style="color:var(--muted);font-size:11px">No confirmed match data yet.</p>
                        <?php else: ?>
                            <?php foreach ($team_stats as $ts): ?>
                            <div class="bar-row">
                                <div class="bar-label">
                                    <span><?= htmlspecialchars($ts['team_name']); ?></span>
                                    <span class="bar-pct"><?= $ts['win_pct']; ?>%</span>
                                </div>
                                <div class="bar-track">
                                    <div class="bar-fill" data-width="<?= $ts['win_pct']; ?>"></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Matches Table -->
                <div class="panel">
                    <div class="ph">
                        <span class="pt">Recent Matches</span>
                        <a href="/ICS_APP_DEV1/match_system/matches.php">View All →</a>
                    </div>
                    <div style="padding:0 16px">
                        <table class="match-table">
                            <thead>
                                <tr>
                                    <th style="width:25%">Home</th>
                                    <th style="width:17%">Score</th>
                                    <th style="width:25%">Away</th>
                                    <th style="width:17%">Date</th>
                                    <th style="width:16%">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($recent_matches && $recent_matches->num_rows > 0):
                                    while ($rm = $recent_matches->fetch_assoc()): ?>
                                <tr>
                                    <td class="fw"><?= htmlspecialchars($rm['home_n']); ?></td>
                                    <td class="fw">
                                        <?php if ($rm['final_status'] === 'confirmed'): ?>
                                            <?= $rm['home_score']; ?> – <?= $rm['away_score']; ?>
                                        <?php else: ?>
                                            <span style="color:var(--muted)">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fw"><?= htmlspecialchars($rm['away_n']); ?></td>
                                    <td class="mt"><?= date('M d', strtotime($rm['reservation_date'])); ?></td>
                                    <td><span class="status-pill <?= $rm['final_status']; ?>"><?= ucfirst($rm['final_status']); ?></span></td>
                                </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                <tr><td colspan="5" style="color:var(--muted);padding:16px 0">No matches found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ROW 4: Players + Quick Actions + System Status -->
            <div class="row4">

                <!-- Recent Players -->
                <div class="panel">
                    <div class="ph">
                        <span class="pt">Recent Players</span>
                        <a href="/ICS_APP_DEV1/userManagement/manage_users.php">Manage →</a>
                    </div>
                    <div class="pb">
                        <?php if ($recent_players && $recent_players->num_rows > 0):
                            while ($rp = $recent_players->fetch_assoc()):
                                $initials = strtoupper(substr($rp['full_name'] ?? $rp['username'], 0, 2));
                        ?>
                        <div class="player-row">
                            <div class="player-av"><?= $initials; ?></div>
                            <div>
                                <div class="pname"><?= htmlspecialchars($rp['full_name'] ?? $rp['username']); ?></div>
                                <div class="pmeta"><?= htmlspecialchars($rp['course'] ?? 'No course'); ?></div>
                            </div>
                            <?php if ($rp['team_name']): ?>
                                <div class="team-tag"><?= htmlspecialchars($rp['team_name']); ?></div>
                            <?php endif; ?>
                        </div>
                        <?php endwhile; else: ?>
                            <p style="color:var(--muted);font-size:11px">No players found.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="panel">
                    <div class="ph"><span class="pt">Quick Actions</span></div>
                    <div class="pb">
                        <div class="quick-grid">
                            <a href="/ICS_APP_DEV1/userManagement/add_team.php" class="quick-btn">
                                <div class="qi" style="background:rgba(245,168,0,.1);color:var(--gold)"><i class="bi bi-plus-circle-fill"></i></div>
                                New Team
                            </a>
                            <a href="/ICS_APP_DEV1/userManagement/schedule.php" class="quick-btn">
                                <div class="qi" style="background:rgba(0,201,167,.1);color:var(--teal)"><i class="bi bi-calendar-check-fill"></i></div>
                                Schedule
                            </a>
                            <a href="/ICS_APP_DEV1/userManagement/view_teams.php" class="quick-btn">
                                <div class="qi" style="background:rgba(56,189,248,.1);color:var(--sky)"><i class="bi bi-shield-fill"></i></div>
                                View Teams
                            </a>
                            <a href="/ICS_APP_DEV1/match_system/matches.php" class="quick-btn">
                                <div class="qi" style="background:rgba(255,77,109,.1);color:var(--rose)"><i class="bi bi-trophy-fill"></i></div>
                                Matches
                            </a>
                            <a href="/ICS_APP_DEV1/Teams%26history1/battle_history.php" class="quick-btn">
                                <div class="qi" style="background:rgba(132,204,22,.1);color:var(--lime)"><i class="bi bi-bar-chart-fill"></i></div>
                                History
                            </a>
                            <a href="/ICS_APP_DEV1/userManagement/manage_users.php" class="quick-btn">
                                <div class="qi" style="background:rgba(167,139,250,.1);color:var(--violet)"><i class="bi bi-person-gear-fill"></i></div>
                                Users
                            </a>
                        </div>
                    </div>
                </div>

                <!-- System Status -->
                <div class="panel">
                    <div class="ph"><span class="pt">System Status</span></div>
                    <div class="pb">
                        <div class="sys-row">
                            <span>Database</span>
                            <div class="sys-ind" style="color:var(--teal)">
                                <div class="sys-dot" style="background:var(--teal)"></div> Connected
                            </div>
                        </div>
                        <div class="sys-row">
                            <span>Match Sync</span>
                            <div class="sys-ind" style="color:var(--sky)">
                                <div class="sys-dot" style="background:var(--sky);animation:pulse 1.5s infinite"></div> Active
                            </div>
                        </div>
                        <div class="sys-row">
                            <span>Admin Session</span>
                            <div class="sys-ind" style="color:var(--gold)">
                                <div class="sys-dot" style="background:var(--gold)"></div> Expires 2h
                            </div>
                        </div>
                        <div class="sys-row">
                            <span>Server</span>
                            <div class="sys-ind" style="color:var(--teal)">
                                <div class="sys-dot" style="background:var(--teal)"></div> Apache/PHP
                            </div>
                        </div>
                        <div class="sys-row">
                            <span>PHP Version</span>
                            <span style="color:var(--muted);font-size:10px"><?= phpversion(); ?></span>
                        </div>
                        <div class="sys-row">
                            <span>Current Time</span>
                            <span style="color:var(--muted);font-size:10px" id="liveClock"></span>
                        </div>
                        <hr style="border:none;border-top:1px solid var(--border);margin:12px 0">
                        <div style="font-size:9px;color:var(--muted);margin-bottom:6px;font-weight:700;text-transform:uppercase;letter-spacing:.08em">Completion Rate</div>
                        <div style="display:flex;justify-content:space-between;font-size:11px;margin-bottom:4px">
                            <span>Matches Finished</span>
                            <span style="color:var(--gold);font-weight:700"><?= $comp_rate; ?>%</span>
                        </div>
                        <div class="comp-bar-track">
                            <div class="comp-bar-fill" style="width:<?= $comp_rate; ?>%"></div>
                        </div>
                    </div>
                </div>

            </div>
        </div><!-- /content -->
    </div><!-- /main -->
</div><!-- /shell -->

<script>
// Live clock
function updateClock() {
    const el = document.getElementById('liveClock');
    if (el) el.textContent = new Date().toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
}
setInterval(updateClock, 1000);
updateClock();

// Chart defaults
Chart.defaults.color = '#5A7A9F';
Chart.defaults.font.family = 'Barlow';
Chart.defaults.font.size = 10;
const gridColor = 'rgba(255,255,255,0.05)';

// Activity line chart
new Chart(document.getElementById('activityChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($monthly_labels); ?>,
        datasets: [{
            label: 'Matches',
            data: <?= json_encode($monthly_values); ?>,
            borderColor: '#F5A800',
            backgroundColor: 'rgba(245,168,0,0.08)',
            tension: 0.42,
            fill: true,
            pointBackgroundColor: '#F5A800',
            pointRadius: 4,
            pointHoverRadius: 6,
            borderWidth: 2,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { color: gridColor }, ticks: { color: '#5A7A9F' } },
            y: { grid: { color: gridColor }, ticks: { color: '#5A7A9F', stepSize: 1 }, beginAtZero: true }
        }
    }
});

// Status doughnut
new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: ['Confirmed', 'Pending', 'Rejected'],
        datasets: [{
            data: [<?= $status_data['confirmed']; ?>, <?= $status_data['pending']; ?>, <?= $status_data['rejected']; ?>],
            backgroundColor: ['#00C9A7', '#F5A800', '#FF4D6D'],
            borderWidth: 0,
            hoverOffset: 5,
        }]
    },
    options: {
        cutout: '68%',
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${ctx.raw}` } }
        }
    }
});

// Reservations bar chart
new Chart(document.getElementById('reservationChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($res_labels); ?>,
        datasets: [{
            label: 'Reservations',
            data: <?= json_encode($res_values); ?>,
            backgroundColor: 'rgba(56,189,248,0.18)',
            borderColor: '#38BDF8',
            borderWidth: 1.5,
            borderRadius: 5,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { color: gridColor }, ticks: { color: '#5A7A9F' } },
            y: { grid: { color: gridColor }, ticks: { color: '#5A7A9F', stepSize: 1 }, beginAtZero: true }
        }
    }
});

// Animate team win rate bars
setTimeout(() => {
    document.querySelectorAll('.bar-fill').forEach(el => {
        el.style.width = el.dataset.width + '%';
    });
}, 400);
</script>

</body>
</html>