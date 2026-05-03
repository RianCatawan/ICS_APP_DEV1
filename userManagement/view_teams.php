<?php
session_start();
include(__DIR__ . '/../database_config/db.php');

// Security Check: Only 'admin' role can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /ICS_APP_DEV1/authentication/login.php");
    exit();
}

// Handle Team Deletion
if (isset($_GET['delete_id'])) {
    $del_id = intval($_GET['delete_id']);
    $conn->query("DELETE FROM teams WHERE id = $del_id");
    header("Location: view_teams.php?msg=Team Deleted Successfully");
    exit();
}

// Fetch all teams
$query  = "SELECT * FROM teams ORDER BY created_at DESC";
$result = $conn->query($query);
$total  = $result->num_rows;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Teams | NBSC Admin</title>
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

.sb-nav { padding: 10px; flex: 1; }

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

.count-pill {
    background: var(--navy3);
    border: 1px solid var(--border);
    border-radius: 7px;
    padding: 6px 14px;
    display: flex; align-items: center; gap-6px;
    font-size: 11px; color: var(--muted);
}
.count-pill strong { color: var(--gold); font-family: 'Barlow Condensed', sans-serif; font-size: 16px; font-weight: 900; margin-left: 5px; }

/* ─── CONTENT ─────────────────────────── */
.content { padding: 20px 24px 40px; flex: 1; }

/* ─── ALERT ─────────────────────────── */
.alert-success-custom {
    background: rgba(0,201,167,.1);
    border: 1px solid rgba(0,201,167,.25);
    border-left: 4px solid var(--teal);
    color: var(--teal);
    border-radius: var(--radius);
    padding: 12px 16px;
    margin-bottom: 18px;
    font-size: 12px; font-weight: 600;
    display: flex; align-items: center; gap: 8px;
}

/* ─── TEAMS GRID ─────────────────────────── */
.teams-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
    gap: 14px;
}

/* ─── TEAM CARD ─────────────────────────── */
.team-card {
    background: var(--navy2);
    border: 1px solid var(--border);
    border-top: 3px solid var(--gold);
    border-radius: var(--radius);
    padding: 22px 16px 16px;
    text-align: center;
    display: flex; flex-direction: column; align-items: center;
    transition: transform .2s ease, box-shadow .2s ease, border-color .2s;
    position: relative;
    overflow: hidden;
    animation: fadeUp .45s ease both;
}
.team-card::before {
    content: '';
    position: absolute; top: 0; right: 0;
    width: 60px; height: 60px;
    background: radial-gradient(circle at top right, rgba(245,168,0,.07), transparent 70%);
}
.team-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 28px rgba(0,0,0,.25);
    border-color: rgba(245,168,0,.5);
}

@keyframes fadeUp { from{opacity:0;transform:translateY(12px)} to{opacity:1;transform:translateY(0)} }

.team-logo-wrap {
    width: 72px; height: 72px;
    border-radius: 50%;
    border: 3px solid rgba(245,168,0,.35);
    overflow: hidden;
    margin-bottom: 12px;
    background: var(--navy3);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.team-logo-wrap img {
    width: 100%; height: 100%; object-fit: cover;
}
.team-logo-placeholder {
    font-family: 'Barlow Condensed', sans-serif;
    font-weight: 900; font-size: 20px;
    color: var(--gold);
    text-transform: uppercase;
}

.game-badge {
    display: inline-block;
    font-size: 9px; font-weight: 800;
    text-transform: uppercase; letter-spacing: .08em;
    padding: 2px 9px; border-radius: 99px;
    background: rgba(56,189,248,.1);
    color: var(--sky);
    margin-bottom: 8px;
}

.team-name {
    font-family: 'Barlow Condensed', sans-serif;
    font-weight: 900; font-size: 15px;
    color: var(--text);
    text-transform: uppercase; letter-spacing: .04em;
    margin-bottom: 4px;
    word-break: break-word;
}
.team-coach {
    font-size: 10px; color: var(--muted);
    margin-bottom: 0;
}
.team-coach strong { color: var(--text); }

.card-divider {
    width: 100%; height: 1px;
    background: var(--border);
    margin: 14px 0 12px;
}

.delete-btn {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: 10px; font-weight: 800;
    text-transform: uppercase; letter-spacing: .06em;
    color: var(--rose);
    text-decoration: none;
    padding: 5px 12px; border-radius: 6px;
    border: 1px solid rgba(255,77,109,.2);
    background: rgba(255,77,109,.06);
    transition: .18s;
}
.delete-btn:hover {
    background: rgba(255,77,109,.15);
    border-color: rgba(255,77,109,.45);
    color: var(--rose);
}

/* ─── EMPTY STATE ─────────────────────────── */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: var(--muted);
}
.empty-state .icon {
    font-size: 48px;
    opacity: .3;
    margin-bottom: 14px;
    display: block;
}
.empty-state h4 {
    font-family: 'Barlow Condensed', sans-serif;
    font-weight: 800; font-size: 18px;
    color: var(--muted); letter-spacing: .04em;
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
            <a class="nav-item active" href="view_teams.php">
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
                <div class="page-title">Manage Teams</div>
                <div class="tb-breadcrumb">
                    <a href="/ICS_APP_DEV1/dashboard_and_admin/admin.php">Admin</a>
                    <span class="sep">›</span>
                    <span style="color:var(--gold)">Teams</span>
                </div>
            </div>
            <div class="tb-right">
                <div class="count-pill">
                    Total Teams: <strong><?= $total; ?></strong>
                </div>
                <a href="add_team.php" class="tb-btn gold">
                    <i class="bi bi-plus-circle-fill"></i> Add Team
                </a>
                <a href="/ICS_APP_DEV1/dashboard_and_admin/admin.php" class="tb-btn">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
            </div>
        </div>

        <!-- CONTENT -->
        <div class="content">

            <?php if (isset($_GET['msg'])): ?>
                <div class="alert-success-custom">
                    <i class="bi bi-check-circle-fill"></i>
                    <?= htmlspecialchars($_GET['msg']); ?>
                </div>
            <?php endif; ?>

            <?php if ($total > 0): ?>
            <div class="teams-grid">
                <?php
                $delay = 0;
                while ($team = $result->fetch_assoc()):
                    $photo = !empty($team['team_photo'])
                        ? '../uploads/' . $team['team_photo']
                        : null;
                    $initials = strtoupper(substr($team['team_name'], 0, 2));
                    $delay += 40;
                ?>
                <div class="team-card" style="animation-delay:<?= $delay; ?>ms">

                    <div class="team-logo-wrap">
                        <?php if ($photo): ?>
                            <img src="<?= htmlspecialchars($photo); ?>"
                                 alt="<?= htmlspecialchars($team['team_name']); ?>"
                                 onerror="this.parentElement.innerHTML='<span class=\'team-logo-placeholder\'><?= $initials; ?></span>'">
                        <?php else: ?>
                            <span class="team-logo-placeholder"><?= $initials; ?></span>
                        <?php endif; ?>
                    </div>

                    <span class="game-badge"><?= htmlspecialchars($team['game_type'] ?? 'Basketball'); ?></span>

                    <div class="team-name"><?= htmlspecialchars($team['team_name']); ?></div>
                    <div class="team-coach">
                        Coach: <strong><?= htmlspecialchars($team['created_by']); ?></strong>
                    </div>

                    <div class="card-divider"></div>

                    <a href="view_teams.php?delete_id=<?= $team['id']; ?>"
                       class="delete-btn"
                       onclick="return confirm('Delete team: <?= addslashes($team['team_name']); ?>? This cannot be undone.')">
                        <i class="bi bi-trash3"></i> Delete Squad
                    </a>
                </div>
                <?php endwhile; ?>
            </div>

            <?php else: ?>
            <div class="empty-state">
                <span class="icon"><i class="bi bi-shield-x"></i></span>
                <h4>No Teams Registered</h4>
                <p style="font-size:12px;margin-top:6px">Add a team to get started.</p>
                <a href="add_team.php" class="tb-btn gold" style="margin-top:18px;display:inline-flex">
                    <i class="bi bi-plus-circle-fill"></i> Add First Team
                </a>
            </div>
            <?php endif; ?>

        </div><!-- /content -->
    </div><!-- /main -->
</div><!-- /shell -->

</body>
</html>