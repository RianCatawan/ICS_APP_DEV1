<?php
session_start();
require_once __DIR__ . '/../database_config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /authentication/login.php");
    exit();
}

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create'])) {
    $team_name = trim($_POST['team_name'] ?? '');
    $game_type = trim($_POST['game_type'] ?? '');
    $creator   = $_SESSION['username'];

    if ($team_name === '' || $game_type === '') {
        $error = 'Team name and game type are required.';
    } else {
        // Handle photo upload
        $team_photo_name = '';
        if (isset($_FILES['team_photo']) && $_FILES['team_photo']['error'] === 0) {
            $target_dir = __DIR__ . '/../uploads/';
            if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }
            $ext             = pathinfo($_FILES['team_photo']['name'], PATHINFO_EXTENSION);
            $team_photo_name = 'team_' . time() . '_' . rand(100, 999) . '.' . $ext;
            move_uploaded_file($_FILES['team_photo']['tmp_name'], $target_dir . $team_photo_name);
        }

        $stmt = $conn->prepare("INSERT INTO teams (team_name, game_type, created_by, team_photo) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $team_name, $game_type, $creator, $team_photo_name);

        if ($stmt->execute()) {
            $team_id = $conn->insert_id;

            // Register players
            $names   = $_POST['player_name'] ?? [];
            $ages    = $_POST['age']         ?? [];
            $heights = $_POST['height']      ?? [];
            $roles   = $_POST['role']        ?? [];

            if (!empty($names)) {
                $sp = $conn->prepare("INSERT INTO team_players (team_id, player_name, age, height, role) VALUES (?, ?, ?, ?, ?)");
                for ($i = 0; $i < count($names); $i++) {
                    if (trim($names[$i]) === '') continue;
                    $sp->bind_param("isiss", $team_id, $names[$i], $ages[$i], $heights[$i], $roles[$i]);
                    $sp->execute();
                }
            }

            $success = "Team <strong>" . htmlspecialchars($team_name) . "</strong> created successfully!";
        } else {
            $error = 'Database error — could not create team.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>NBSC Admin — Add Team</title>
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

/* ─── SIDEBAR ─── */
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
.sb-title { font-family: 'Barlow Condensed', sans-serif; font-weight: 900; font-size: 14px; color: var(--text); text-transform: uppercase; letter-spacing: .05em; }
.sb-sub   { font-size: 10px; color: var(--muted); letter-spacing: .07em; text-transform: uppercase; margin-top: 2px; }
.sb-nav   { padding: 10px 10px; flex: 1; }
.sb-section { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .11em; color: var(--muted); padding: 12px 8px 4px; }
.nav-item {
    display: flex; align-items: center; gap: 9px;
    padding: 9px 10px; border-radius: 8px;
    color: var(--muted); text-decoration: none;
    font-weight: 600; font-size: 12px;
    transition: .15s; margin-bottom: 2px;
}
.nav-item:hover  { background: rgba(255,255,255,.05); color: var(--text); }
.nav-item.active { background: rgba(245,168,0,.12); color: var(--gold); }
.nav-item .ni    { font-size: 14px; width: 18px; text-align: center; }
.sb-foot { padding: 12px 10px; border-top: 1px solid var(--border); }
.admin-chip { display: flex; align-items: center; gap: 9px; padding: 9px 10px; background: rgba(255,255,255,.04); border-radius: 8px; }
.admin-av {
    width: 30px; height: 30px;
    background: linear-gradient(135deg, var(--gold), var(--teal));
    border-radius: 7px;
    display: flex; align-items: center; justify-content: center;
    font-weight: 900; font-size: 11px; color: var(--navy); flex-shrink: 0;
}

/* ─── MAIN ─── */
#main { margin-left: var(--sidebar); flex: 1; display: flex; flex-direction: column; min-width: 0; }

/* ─── TOPBAR ─── */
.topbar {
    padding: 14px 24px;
    border-bottom: 1px solid var(--border);
    background: var(--navy2);
    display: flex; align-items: center; justify-content: space-between;
    position: sticky; top: 0; z-index: 50;
}
.page-title { font-family: 'Barlow Condensed', sans-serif; font-weight: 900; font-size: 17px; color: var(--text); letter-spacing: .03em; }
.tb-date    { font-size: 10px; color: var(--muted); margin-top: 1px; }
.tb-right   { display: flex; align-items: center; gap: 10px; }
.tb-btn {
    background: rgba(255,255,255,.06);
    border: 1px solid var(--border);
    color: var(--text); border-radius: 7px;
    padding: 6px 12px; font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .04em;
    text-decoration: none; display: inline-flex; align-items: center; gap: 5px;
    transition: .2s; cursor: pointer;
}
.tb-btn:hover         { background: rgba(255,255,255,.1); color: var(--gold); }
.tb-btn.gold          { background: var(--gold); color: var(--navy); border-color: var(--gold); }
.tb-btn.gold:hover    { background: var(--gold2); }

/* ─── CONTENT ─── */
.content { padding: 24px 24px 60px; flex: 1; }

/* ─── BREADCRUMB ─── */
.breadcrumb-bar { display: flex; align-items: center; gap: 8px; margin-bottom: 20px; }
.breadcrumb-bar a { font-size: 11px; color: var(--muted); text-decoration: none; font-weight: 600; }
.breadcrumb-bar a:hover { color: var(--gold); }
.breadcrumb-bar span { font-size: 11px; color: var(--muted); }
.breadcrumb-bar .current { font-size: 11px; color: var(--text); font-weight: 700; }

/* ─── ALERT ─── */
.alert {
    padding: 12px 16px; border-radius: var(--radius);
    font-size: 12px; font-weight: 600;
    margin-bottom: 20px;
    display: flex; align-items: center; gap: 10px;
    animation: fadeUp .4s ease both;
}
.alert-success { background: rgba(0,201,167,.1); border: 1px solid rgba(0,201,167,.25); color: var(--teal); }
.alert-error   { background: rgba(255,77,109,.1); border: 1px solid rgba(255,77,109,.25); color: var(--rose); }
@keyframes fadeUp { from { opacity:0; transform: translateY(8px); } to { opacity:1; transform: translateY(0); } }

/* ─── SECTION HEADER ─── */
.section-hd {
    display: flex; align-items: center; gap: 10px;
    margin-bottom: 14px;
}
.section-hd-line {
    flex: 1; height: 1px; background: var(--border);
}
.section-hd-text {
    font-family: 'Barlow Condensed', sans-serif;
    font-weight: 900; font-size: 11px;
    text-transform: uppercase; letter-spacing: .1em;
    color: var(--muted);
    white-space: nowrap;
}
.section-badge {
    background: rgba(245,168,0,.1); color: var(--gold);
    font-size: 9px; font-weight: 800;
    text-transform: uppercase; letter-spacing: .08em;
    padding: 2px 8px; border-radius: 99px;
    border: 1px solid rgba(245,168,0,.2);
}

/* ─── PANEL ─── */
.panel {
    background: var(--navy2);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    overflow: hidden;
    margin-bottom: 14px;
    animation: fadeUp .5s ease both;
}
.panel:nth-child(2) { animation-delay: .07s; }
.panel:nth-child(3) { animation-delay: .14s; }
.ph {
    padding: 12px 18px;
    border-bottom: 1px solid var(--border);
    display: flex; align-items: center; gap: 10px;
}
.ph-icon {
    width: 28px; height: 28px; border-radius: 7px;
    display: flex; align-items: center; justify-content: center;
    font-size: 13px; flex-shrink: 0;
}
.pt { font-family: 'Barlow Condensed', sans-serif; font-weight: 800; font-size: 13px; text-transform: uppercase; letter-spacing: .06em; color: var(--text); }
.pm { font-size: 10px; color: var(--muted); margin-left: auto; }
.pb { padding: 18px; }

/* ─── FORM ELEMENTS ─── */
.form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.form-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; }
.form-group   { display: flex; flex-direction: column; gap: 6px; }

label {
    font-size: 9px; font-weight: 800;
    text-transform: uppercase; letter-spacing: .1em;
    color: var(--muted);
}

.form-control, .form-select {
    background: var(--navy3);
    border: 1px solid var(--border);
    border-radius: 8px;
    color: var(--text);
    padding: 10px 12px;
    font-family: 'Barlow', sans-serif;
    font-size: 12px;
    font-weight: 500;
    transition: .2s;
    outline: none;
    width: 100%;
}
.form-control::placeholder { color: var(--muted); }
.form-control:focus, .form-select:focus {
    border-color: rgba(245,168,0,.5);
    background: rgba(245,168,0,.03);
    box-shadow: 0 0 0 3px rgba(245,168,0,.08);
}
.form-select option { background: var(--navy2); color: var(--text); }

/* File upload zone */
.upload-zone {
    background: var(--navy3);
    border: 1.5px dashed rgba(245,168,0,.3);
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    cursor: pointer;
    transition: .2s;
    position: relative;
}
.upload-zone:hover { border-color: var(--gold); background: rgba(245,168,0,.03); }
.upload-zone input { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%; }
.upload-icon { font-size: 22px; color: var(--gold); margin-bottom: 6px; }
.upload-text { font-size: 11px; font-weight: 600; color: var(--text); }
.upload-hint { font-size: 9px; color: var(--muted); margin-top: 2px; }
#preview-wrap { margin-top: 10px; display: none; }
#preview-wrap img { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; border: 2px solid var(--gold); }

/* Game type pills */
.type-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px; }
.type-pill { display: none; }
.type-label {
    display: flex; flex-direction: column; align-items: center; gap: 4px;
    padding: 12px 8px;
    background: var(--navy3);
    border: 1.5px solid var(--border);
    border-radius: 8px;
    cursor: pointer;
    transition: .2s;
    text-align: center;
}
.type-label:hover { border-color: rgba(245,168,0,.3); }
.type-pill:checked + .type-label {
    background: rgba(245,168,0,.1);
    border-color: var(--gold);
    color: var(--gold);
}
.type-icon { font-size: 18px; color: var(--muted); }
.type-pill:checked + .type-label .type-icon { color: var(--gold); }
.type-name {
    font-family: 'Barlow Condensed', sans-serif;
    font-weight: 800; font-size: 13px; letter-spacing: .04em;
}
.type-desc { font-size: 9px; color: var(--muted); }
.type-pill:checked + .type-label .type-desc { color: rgba(245,168,0,.6); }

/* Size picker (5v5 only) */
.size-picker { display: none; margin-top: 14px; }
.size-pills  { display: flex; gap: 8px; flex-wrap: wrap; }
.size-pill-inp { display: none; }
.size-pill-lbl {
    padding: 7px 16px;
    background: var(--navy3);
    border: 1.5px solid var(--border);
    border-radius: 99px;
    font-size: 11px; font-weight: 700;
    cursor: pointer; transition: .2s;
    color: var(--muted);
}
.size-pill-inp:checked + .size-pill-lbl { background: rgba(0,201,167,.1); border-color: var(--teal); color: var(--teal); }

/* Roster grid */
.roster-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap: 12px; }
.player-card {
    background: var(--navy3);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 14px;
    transition: .2s;
    animation: fadeUp .4s ease both;
}
.player-card:hover { border-color: rgba(245,168,0,.2); }
.player-card.is-sub { border-style: dashed; border-color: rgba(0,201,167,.2); }
.player-card.placeholder-card { opacity: .35; pointer-events: none; }
.pc-head {
    display: flex; align-items: center; gap: 8px;
    margin-bottom: 12px; padding-bottom: 10px;
    border-bottom: 1px solid var(--border);
}
.pc-num {
    width: 24px; height: 24px; border-radius: 6px;
    display: flex; align-items: center; justify-content: center;
    font-family: 'Barlow Condensed', sans-serif;
    font-weight: 900; font-size: 12px; flex-shrink: 0;
}
.pc-num.main { background: rgba(245,168,0,.15); color: var(--gold); }
.pc-num.sub  { background: rgba(0,201,167,.15); color: var(--teal); }
.pc-lbl {
    font-family: 'Barlow Condensed', sans-serif;
    font-weight: 800; font-size: 12px;
    text-transform: uppercase; letter-spacing: .05em;
}
.pc-lbl.sub { color: var(--teal); }
.pc-field { margin-bottom: 8px; }
.pc-field:last-child { margin-bottom: 0; }
.pc-row { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 8px; }

/* Submit button */
.submit-wrap { margin-top: 20px; }
.btn-submit {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    width: 100%;
    background: var(--gold);
    color: var(--navy);
    border: none; border-radius: var(--radius);
    padding: 14px;
    font-family: 'Barlow Condensed', sans-serif;
    font-weight: 900; font-size: 15px;
    text-transform: uppercase; letter-spacing: .08em;
    cursor: pointer; transition: .2s;
}
.btn-submit:hover { background: var(--gold2); transform: translateY(-1px); box-shadow: 0 6px 20px rgba(245,168,0,.2); }
.btn-submit:active { transform: translateY(0); }

.cancel-link {
    display: flex; align-items: center; justify-content: center;
    margin-top: 10px;
    font-size: 11px; font-weight: 700;
    color: var(--muted); text-decoration: none;
    transition: .15s;
}
.cancel-link:hover { color: var(--rose); }

/* Placeholder rows */
.ph-row { height: 38px; background: rgba(255,255,255,.03); border-radius: 7px; margin-bottom: 8px; }

/* Scrollbar */
::-webkit-scrollbar { width: 4px; }
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
            <a class="nav-item active" href="/ICS_APP_DEV1/userManagement/view_teams.php">
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
                <div class="page-title">Add New Team</div>
                <div class="tb-date"><?= date('l, F j, Y'); ?></div>
            </div>
            <div class="tb-right">
                <a href="/ICS_APP_DEV1/userManagement/view_teams.php" class="tb-btn">
                    <i class="bi bi-shield-fill"></i> All Teams
                </a>
                <a href="/ICS_APP_DEV1/dashboard_and_admin/admin.php" class="tb-btn gold">
                    <i class="bi bi-grid-fill"></i> Dashboard
                </a>
            </div>
        </div>

        <!-- CONTENT -->
        <div class="content">

            <!-- Breadcrumb -->
            <div class="breadcrumb-bar">
                <a href="/ICS_APP_DEV1/dashboard_and_admin/admin.php"><i class="bi bi-grid-fill"></i> Overview</a>
                <span>/</span>
                <a href="/ICS_APP_DEV1/userManagement/view_teams.php">Teams</a>
                <span>/</span>
                <span class="current">Add Team</span>
            </div>

            <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle-fill"></i>
                <?= $success; ?>
                <a href="/ICS_APP_DEV1/userManagement/view_teams.php" style="margin-left:auto;color:var(--teal);font-weight:700;text-decoration:none;font-size:11px">View Teams →</a>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <?= htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" id="teamForm">

                <!-- PANEL 1: Team Info -->
                <div class="panel">
                    <div class="ph">
                        <div class="ph-icon" style="background:rgba(245,168,0,.1);">
                            <i class="bi bi-shield-fill" style="color:var(--gold)"></i>
                        </div>
                        <span class="pt">Team Information</span>
                        <span class="pm">Basic details</span>
                    </div>
                    <div class="pb">
                        <div class="form-grid-3" style="margin-bottom:18px;">
                            <!-- Team Name -->
                            <div class="form-group" style="grid-column: span 2;">
                                <label for="team_name">Team Name</label>
                                <input type="text" id="team_name" name="team_name" class="form-control"
                                       placeholder="e.g. NBSC Tigers" required
                                       value="<?= htmlspecialchars($_POST['team_name'] ?? ''); ?>">
                            </div>
                            <!-- Color Accent (cosmetic for now) -->
                            <div class="form-group">
                                <label>Team Color</label>
                                <input type="color" name="team_color" class="form-control" value="#F5A800"
                                       style="height:42px;padding:4px 8px;cursor:pointer;">
                            </div>
                        </div>

                        <!-- Photo Upload -->
                        <div class="form-group" style="margin-bottom:20px;">
                            <label>Team Logo / Photo</label>
                            <div class="upload-zone" id="uploadZone">
                                <input type="file" name="team_photo" accept="image/*" id="photoInput">
                                <div class="upload-icon"><i class="bi bi-cloud-arrow-up-fill"></i></div>
                                <div class="upload-text">Click or drag to upload team logo</div>
                                <div class="upload-hint">PNG, JPG, WEBP — recommended 400×400px</div>
                            </div>
                            <div id="preview-wrap">
                                <img id="photoPreview" src="" alt="Preview">
                                <span id="preview-name" style="font-size:10px;color:var(--muted);margin-left:10px;vertical-align:middle;"></span>
                            </div>
                        </div>

                        <!-- Game Type -->
                        <div class="form-group">
                            <label>Game Type</label>
                            <div class="type-grid">
                                <?php
                                $types = [
                                    '1v1' => ['icon' => 'bi-person-fill',         'desc' => '+1 Sub'],
                                    '2v2' => ['icon' => 'bi-people-fill',          'desc' => '+1 Sub'],
                                    '3v3' => ['icon' => 'bi-people-fill',          'desc' => '+1 Sub'],
                                    '4v4' => ['icon' => 'bi-person-lines-fill',    'desc' => '+1 Sub'],
                                    '5v5' => ['icon' => 'bi-grid-3x3-gap-fill',   'desc' => 'Full Roster'],
                                ];
                                $selected_type = $_POST['game_type'] ?? '';
                                foreach ($types as $val => $t):
                                ?>
                                <div>
                                    <input class="type-pill" type="radio" name="game_type" id="gt_<?= $val; ?>"
                                           value="<?= $val; ?>" onchange="onTypeChange()"
                                           <?= $selected_type === $val ? 'checked' : ''; ?> required>
                                    <label class="type-label" for="gt_<?= $val; ?>">
                                        <i class="<?= $t['icon']; ?> type-icon"></i>
                                        <span class="type-name"><?= $val; ?></span>
                                        <span class="type-desc"><?= $t['desc']; ?></span>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- 5v5 Size Picker -->
                            <div class="size-picker" id="sizePicker">
                                <label style="margin-bottom:8px;display:block;">Roster Size</label>
                                <div class="size-pills">
                                    <?php foreach ([5, 10, 15] as $sz): ?>
                                    <input class="size-pill-inp" type="radio" name="roster_size" id="sz_<?= $sz; ?>"
                                           value="<?= $sz; ?>" onchange="buildRoster()" <?= $sz === 5 ? 'checked' : ''; ?>>
                                    <label class="size-pill-lbl" for="sz_<?= $sz; ?>"><?= $sz; ?> Players</label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PANEL 2: Roster -->
                <div class="panel">
                    <div class="ph">
                        <div class="ph-icon" style="background:rgba(56,189,248,.1);">
                            <i class="bi bi-people-fill" style="color:var(--sky)"></i>
                        </div>
                        <span class="pt">Team Roster</span>
                        <span class="pm" id="rosterCount">Select a game type above</span>
                    </div>
                    <div class="pb">
                        <div class="roster-grid" id="rosterGrid">
                            <!-- Placeholder cards -->
                            <?php for ($i = 1; $i <= 4; $i++): ?>
                            <div class="player-card placeholder-card">
                                <div class="pc-head">
                                    <div class="pc-num main"><?= $i; ?></div>
                                    <span class="pc-lbl" style="color:var(--muted)">Player <?= $i; ?></span>
                                </div>
                                <div class="ph-row"></div>
                                <div class="ph-row"></div>
                                <div class="ph-row" style="height:28px;margin:0"></div>
                            </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="submit-wrap">
                    <button type="submit" name="create" class="btn-submit">
                        <i class="bi bi-check2-circle"></i>
                        Confirm & Create Team
                    </button>
                    <a href="/ICS_APP_DEV1/userManagement/view_teams.php" class="cancel-link">
                        <i class="bi bi-x-circle" style="margin-right:4px"></i> Cancel
                    </a>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
// ── Photo preview ──
document.getElementById('photoInput').addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;
    const wrap = document.getElementById('preview-wrap');
    const img  = document.getElementById('photoPreview');
    const nm   = document.getElementById('preview-name');
    const url  = URL.createObjectURL(file);
    img.src    = url;
    nm.textContent = file.name;
    wrap.style.display = 'block';
});

// ── Game type change ──
function onTypeChange() {
    const type = document.querySelector('input[name="game_type"]:checked')?.value;
    const picker = document.getElementById('sizePicker');
    picker.style.display = type === '5v5' ? 'block' : 'none';
    buildRoster();
}

// ── Roster size map ──
function playerCount() {
    const type = document.querySelector('input[name="game_type"]:checked')?.value;
    if (!type) return 0;
    if (type === '1v1') return 2;
    if (type === '2v2') return 3;
    if (type === '3v3') return 4;
    if (type === '4v4') return 5;
    if (type === '5v5') {
        return parseInt(document.querySelector('input[name="roster_size"]:checked')?.value || 5);
    }
    return 0;
}

function buildRoster() {
    const type  = document.querySelector('input[name="game_type"]:checked')?.value;
    const count = playerCount();
    const grid  = document.getElementById('rosterGrid');
    const lbl   = document.getElementById('rosterCount');

    if (!type || count === 0) {
        lbl.textContent = 'Select a game type above';
        return;
    }

    lbl.textContent = `${count} slot${count > 1 ? 's' : ''} — 1 sub included`;

    grid.innerHTML = '';

    for (let i = 1; i <= count; i++) {
        const isSub = (type !== '5v5') && (i === count);
        const card  = document.createElement('div');
        card.className = 'player-card' + (isSub ? ' is-sub' : '');
        card.style.animationDelay = (i * 0.04) + 's';

        card.innerHTML = `
            <div class="pc-head">
                <div class="pc-num ${isSub ? 'sub' : 'main'}">${isSub ? '<i class="bi bi-arrow-repeat" style="font-size:10px"></i>' : i}</div>
                <span class="pc-lbl ${isSub ? 'sub' : ''}">${isSub ? 'Sub Player' : 'Player ' + i}</span>
            </div>
            <div class="pc-field">
                <label>Full Name</label>
                <input type="text" name="player_name[]" class="form-control" placeholder="e.g. Juan dela Cruz" required>
            </div>
            <div class="pc-row">
                <div>
                    <label>Age</label>
                    <input type="number" name="age[]" class="form-control" placeholder="20" min="10" max="60" required>
                </div>
                <div>
                    <label>Height</label>
                    <input type="text" name="height[]" class="form-control" placeholder="5'10" required>
                </div>
            </div>
            <div class="pc-field">
                <label>Role / Position</label>
                <input type="text" name="role[]" class="form-control" placeholder="e.g. Point Guard" required>
            </div>
        `;
        grid.appendChild(card);
    }
}

// Init on page load if type was pre-selected
window.addEventListener('DOMContentLoaded', () => {
    if (document.querySelector('input[name="game_type"]:checked')) {
        onTypeChange();
    }
});
</script>
</body>
</html>