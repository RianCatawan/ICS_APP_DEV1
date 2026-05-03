<?php
session_start();
require_once __DIR__ . '/../database_config/db.php';

// Security Check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /ICS_APP_DEV1/authentication/login.php");
    exit();
}

// Fetch matches with a clean Join
$sql = "SELECT mr.id, t1.team_name AS team1, t2.team_name AS team2,
               r.reservation_date, r.selected_time, mr.status, mr.home_score, mr.away_score,
               mr.final_status
        FROM match_requests mr
        JOIN reservations r ON mr.reservation_id = r.id
        JOIN teams t1 ON r.team_id = t1.id
        JOIN teams t2 ON mr.challenger_team_id = t2.id
        ORDER BY r.reservation_date DESC";

$result     = $conn->query($sql);
$total      = $result ? $result->num_rows : 0;

// Status counts
$counts = ['confirmed' => 0, 'pending' => 0, 'rejected' => 0];
$rows   = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
        $fs = strtolower($row['final_status'] ?? $row['status'] ?? 'pending');
        if (isset($counts[$fs])) $counts[$fs]++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Match Schedule | NBSC Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
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

.sb-logo { padding: 22px 18px 16px; border-bottom: 1px solid var(--border); }
.sb-mark {
    width: 38px; height: 38px;
    background: var(--gold); border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-family: 'Barlow Condensed', sans-serif;
    font-weight: 900; font-size: 15px; color: var(--navy);
    margin-bottom: 9px;
}
.sb-title {
    font-family: 'Barlow Condensed', sans-serif;
    font-weight: 900; font-size: 14px; color: var(--text);
    text-transform: uppercase; letter-spacing: .05em;
}
.sb-sub { font-size: 10px; color: var(--muted); letter-spacing: .07em; text-transform: uppercase; margin-top: 2px; }

.sb-nav { padding: 10px; flex: 1; }
.sb-section {
    font-size: 9px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .11em; color: var(--muted); padding: 12px 8px 4px;
}
.nav-item {
    display: flex; align-items: center; gap: 9px;
    padding: 9px 10px; border-radius: 8px;
    color: var(--muted); text-decoration: none;
    font-weight: 600; font-size: 12px;
    transition: .15s; margin-bottom: 2px;
}
.nav-item:hover { background: rgba(255,255,255,.05); color: var(--text); }
.nav-item.active { background: rgba(245,168,0,.12); color: var(--gold); }
.nav-item .ni { font-size: 14px; width: 18px; text-align: center; }

.sb-foot { padding: 12px 10px; border-top: 1px solid var(--border); }
.admin-chip {
    display: flex; align-items: center; gap: 9px;
    padding: 9px 10px; background: rgba(255,255,255,.04); border-radius: 8px;
}
.admin-av {
    width: 30px; height: 30px;
    background: linear-gradient(135deg, var(--gold), var(--teal));
    border-radius: 7px;
    display: flex; align-items: center; justify-content: center;
    font-weight: 900; font-size: 11px; color: var(--navy); flex-shrink: 0;
}

/* ─── MAIN ─────────────────────────── */
#main { margin-left: var(--sidebar); flex: 1; display: flex; flex-direction: column; min-width: 0; }

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
    font-weight: 900; font-size: 17px; color: var(--text); letter-spacing: .03em;
}
.tb-breadcrumb {
    font-size: 10px; color: var(--muted); margin-top: 2px;
    display: flex; align-items: center; gap: 5px;
}
.tb-breadcrumb a { color: var(--muted); text-decoration: none; }
.tb-breadcrumb a:hover { color: var(--gold); }
.tb-breadcrumb .sep { opacity: .4; }
.tb-right { display: flex; align-items: center; gap: 10px; }
.tb-btn {
    background: rgba(255,255,255,.06);
    border: 1px solid var(--border);
    color: var(--text); border-radius: 7px; padding: 6px 12px;
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

/* ─── SUMMARY CARDS ─────────────────────────── */
.summary-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
    margin-bottom: 18px;
}
.sum-card {
    background: var(--navy2);
    border: 1px solid var(--border);
    border-top: 3px solid transparent;
    border-radius: var(--radius);
    padding: 14px 16px;
    display: flex; align-items: center; gap: 12px;
    transition: .2s;
}
.sum-card:hover { transform: translateY(-2px); }
.sum-card.total  { border-top-color: var(--gold); }
.sum-card.conf   { border-top-color: var(--teal); }
.sum-card.pend   { border-top-color: var(--gold); }
.sum-card.rej    { border-top-color: var(--rose); }
.sum-icon {
    width: 36px; height: 36px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 16px; flex-shrink: 0;
}
.sum-card.total .sum-icon { background: rgba(245,168,0,.1); color: var(--gold); }
.sum-card.conf  .sum-icon { background: rgba(0,201,167,.1); color: var(--teal); }
.sum-card.pend  .sum-icon { background: rgba(245,168,0,.1); color: var(--gold2); }
.sum-card.rej   .sum-icon { background: rgba(255,77,109,.1); color: var(--rose); }
.sum-val {
    font-family: 'Barlow Condensed', sans-serif;
    font-weight: 900; font-size: 24px; color: var(--text); line-height: 1;
}
.sum-lbl { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .09em; color: var(--muted); margin-top: 2px; }

/* ─── TABLE PANEL ─────────────────────────── */
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
    text-transform: uppercase; letter-spacing: .07em; color: var(--text);
}
.pm { font-size: 10px; color: var(--muted); }

/* ─── FILTER BAR ─────────────────────────── */
.filter-bar {
    display: flex; align-items: center; gap: 8px;
    padding: 10px 16px;
    border-bottom: 1px solid var(--border);
    background: rgba(255,255,255,.02);
    flex-wrap: wrap;
}
.filter-btn {
    font-size: 10px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .06em;
    padding: 4px 12px; border-radius: 99px;
    border: 1px solid var(--border);
    background: transparent; color: var(--muted);
    cursor: pointer; transition: .15s;
}
.filter-btn:hover { color: var(--text); border-color: rgba(255,255,255,.15); }
.filter-btn.active { background: rgba(245,168,0,.12); color: var(--gold); border-color: rgba(245,168,0,.3); }
.search-input {
    margin-left: auto;
    background: rgba(255,255,255,.05);
    border: 1px solid var(--border);
    border-radius: 7px;
    padding: 5px 12px 5px 30px;
    color: var(--text);
    font-size: 11px; font-family: 'Barlow', sans-serif;
    outline: none; width: 180px;
    transition: border-color .2s;
    position: relative;
}
.search-input:focus { border-color: rgba(245,168,0,.4); }
.search-wrap { position: relative; }
.search-wrap i {
    position: absolute; left: 9px; top: 50%;
    transform: translateY(-50%);
    color: var(--muted); font-size: 12px;
    pointer-events: none;
}

/* ─── MATCH TABLE ─────────────────────────── */
.match-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
.match-table th {
    font-size: 9px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .1em;
    color: var(--muted); padding: 10px 16px;
    text-align: left; border-bottom: 1px solid var(--border);
    background: rgba(255,255,255,.02);
}
.match-table td {
    padding: 11px 16px;
    border-bottom: 1px solid rgba(255,255,255,.04);
    font-size: 12px; vertical-align: middle;
}
.match-table tr:last-child td { border-bottom: none; }
.match-table tbody tr { transition: background .12s; }
.match-table tbody tr:hover { background: rgba(255,255,255,.025); }

.match-id {
    font-family: 'Barlow Condensed', sans-serif;
    font-weight: 800; color: var(--muted); font-size: 12px;
}

.versus-cell { display: flex; align-items: center; gap: 6px; }
.t-name { font-weight: 700; color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.vs-pill {
    background: var(--navy3); color: var(--sky);
    font-family: 'Barlow Condensed', sans-serif;
    font-weight: 800; font-size: 9px; letter-spacing: .08em;
    padding: 2px 7px; border-radius: 99px; flex-shrink: 0;
}

.date-main { font-weight: 700; color: var(--text); font-size: 11px; }
.date-time { font-size: 10px; color: var(--muted); margin-top: 1px; }

.score-box {
    font-family: 'Barlow Condensed', sans-serif;
    font-weight: 900; font-size: 15px; color: var(--text);
    background: var(--navy3);
    padding: 3px 10px; border-radius: 6px;
    display: inline-block; letter-spacing: .04em;
}
.score-box.no-score { color: var(--muted); font-size: 12px; font-weight: 400; background: transparent; }

.status-pill {
    font-size: 9px; font-weight: 800;
    text-transform: uppercase; letter-spacing: .06em;
    padding: 3px 9px; border-radius: 99px;
    white-space: nowrap;
}
.status-pill.confirmed { background: rgba(0,201,167,.12); color: var(--teal); }
.status-pill.pending   { background: rgba(245,168,0,.12);  color: var(--gold); }
.status-pill.rejected  { background: rgba(255,77,109,.12); color: var(--rose); }
.status-pill.accepted  { background: rgba(132,204,22,.12); color: var(--lime); }
.status-pill.finished  { background: rgba(56,189,248,.12); color: var(--sky); }

/* ─── EMPTY STATE ─────────────────────────── */
.empty-state {
    text-align: center; padding: 60px 20px; color: var(--muted);
}
.empty-state .icon { font-size: 40px; opacity: .25; display: block; margin-bottom: 12px; }
.empty-state h4 {
    font-family: 'Barlow Condensed', sans-serif;
    font-weight: 800; font-size: 16px; color: var(--muted); letter-spacing: .04em;
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
            <a class="nav-item" href="/ICS_APP_DEV1/dashboard_and_admin/admin.php">
                <i class="bi bi-grid-fill ni"></i> Overview
            </a>
            <a class="nav-item" href="/ICS_APP_DEV1/userManagement/view_teams.php">
                <i class="bi bi-shield-fill ni"></i> Teams
            </a>
            <a class="nav-item active" href="matches.php">
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
                <div class="page-title">Match Schedule</div>
                <div class="tb-breadcrumb">
                    <a href="/ICS_APP_DEV1/dashboard_and_admin/admin.php">Admin</a>
                    <span class="sep">›</span>
                    <span style="color:var(--gold)">Matches</span>
                </div>
            </div>
            <div class="tb-right">
                <a href="/ICS_APP_DEV1/dashboard_and_admin/admin.php" class="tb-btn">
                    <i class="bi bi-arrow-left"></i> Dashboard
                </a>
            </div>
        </div>

        <!-- CONTENT -->
        <div class="content">

            <!-- SUMMARY CARDS -->
            <div class="summary-grid">
                <div class="sum-card total">
                    <div class="sum-icon"><i class="bi bi-flag-fill"></i></div>
                    <div>
                        <div class="sum-val"><?= $total; ?></div>
                        <div class="sum-lbl">Total Matches</div>
                    </div>
                </div>
                <div class="sum-card conf">
                    <div class="sum-icon"><i class="bi bi-check-circle-fill"></i></div>
                    <div>
                        <div class="sum-val"><?= $counts['confirmed']; ?></div>
                        <div class="sum-lbl">Confirmed</div>
                    </div>
                </div>
                <div class="sum-card pend">
                    <div class="sum-icon"><i class="bi bi-hourglass-split"></i></div>
                    <div>
                        <div class="sum-val"><?= $counts['pending']; ?></div>
                        <div class="sum-lbl">Pending</div>
                    </div>
                </div>
                <div class="sum-card rej">
                    <div class="sum-icon"><i class="bi bi-x-circle-fill"></i></div>
                    <div>
                        <div class="sum-val"><?= $counts['rejected']; ?></div>
                        <div class="sum-lbl">Rejected</div>
                    </div>
                </div>
            </div>

            <!-- TABLE PANEL -->
            <div class="panel">
                <div class="ph">
                    <span class="pt">Complete Match Schedule</span>
                    <span class="pm"><?= $total; ?> total records</span>
                </div>

                <!-- FILTER BAR -->
                <div class="filter-bar">
                    <button class="filter-btn active" onclick="filterTable('all', this)">All</button>
                    <button class="filter-btn" onclick="filterTable('confirmed', this)">Confirmed</button>
                    <button class="filter-btn" onclick="filterTable('pending', this)">Pending</button>
                    <button class="filter-btn" onclick="filterTable('rejected', this)">Rejected</button>
                    <div class="search-wrap">
                        <i class="bi bi-search"></i>
                        <input type="text" class="search-input" id="searchInput" placeholder="Search teams..." oninput="searchTable(this.value)">
                    </div>
                </div>

                <!-- TABLE -->
                <div style="overflow-x:auto">
                    <table class="match-table" id="matchTable">
                        <thead>
                            <tr>
                                <th style="width:80px">ID</th>
                                <th style="width:30%">Versus</th>
                                <th style="width:18%">Date & Time</th>
                                <th style="width:14%">Score</th>
                                <th style="width:12%">Status</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody">
                            <?php if (!empty($rows)): ?>
                                <?php foreach ($rows as $row):
                                    $fs = strtolower($row['final_status'] ?? $row['status'] ?? 'pending');
                                    $has_score = ($fs === 'confirmed' && ($row['home_score'] !== null));
                                ?>
                                <tr data-status="<?= $fs; ?>" data-teams="<?= strtolower(htmlspecialchars($row['team1'] . ' ' . $row['team2'])); ?>">
                                    <td><span class="match-id">#<?= $row['id']; ?></span></td>
                                    <td>
                                        <div class="versus-cell">
                                            <span class="t-name"><?= htmlspecialchars($row['team1']); ?></span>
                                            <span class="vs-pill">VS</span>
                                            <span class="t-name"><?= htmlspecialchars($row['team2']); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="date-main"><?= date('M d, Y', strtotime($row['reservation_date'])); ?></div>
                                        <div class="date-time"><?= htmlspecialchars($row['selected_time'] ?? '—'); ?></div>
                                    </td>
                                    <td>
                                        <?php if ($has_score): ?>
                                            <span class="score-box"><?= $row['home_score']; ?> – <?= $row['away_score']; ?></span>
                                        <?php else: ?>
                                            <span class="score-box no-score">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-pill <?= $fs; ?>"><?= ucfirst($fs); ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5">
                                        <div class="empty-state">
                                            <span class="icon"><i class="bi bi-calendar-x"></i></span>
                                            <h4>No Matches Found</h4>
                                            <p style="font-size:11px;margin-top:5px">No matches in the schedule yet.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div><!-- /panel -->

        </div><!-- /content -->
    </div><!-- /main -->
</div><!-- /shell -->

<script>
// Filter by status
function filterTable(status, btn) {
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    const rows = document.querySelectorAll('#tableBody tr[data-status]');
    rows.forEach(row => {
        row.style.display = (status === 'all' || row.dataset.status === status) ? '' : 'none';
    });
}

// Search by team name
function searchTable(val) {
    const q = val.toLowerCase().trim();
    document.querySelectorAll('#tableBody tr[data-teams]').forEach(row => {
        row.style.display = row.dataset.teams.includes(q) ? '' : 'none';
    });
}
</script>

</body>
</html>