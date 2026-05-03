<?php
session_start();
require_once __DIR__ . '/../database_config/db.php';

$query = "SELECT
            mr.id AS match_id,
            mr.home_score, mr.away_score, mr.winner_id,
            mr.final_status,
            r.team_id AS home_team_id,
            mr.challenger_team_id AS away_team_id,
            t1.team_name AS home_name,
            t2.team_name AS away_name,
            t1.team_photo AS home_photo,
            t2.team_photo AS away_photo,
            r.reservation_date
          FROM match_requests mr
          JOIN reservations r ON mr.reservation_id = r.id
          JOIN teams t1 ON r.team_id = t1.id
          JOIN teams t2 ON mr.challenger_team_id = t2.id
          WHERE mr.final_status = 'confirmed'
          ORDER BY mr.id DESC";

$result = $conn->query($query);
$rows   = [];
$total_matches = 0; $total_wins_home = 0; $draws = 0;
if ($result) {
    while ($r = $result->fetch_assoc()) {
        $rows[] = $r;
        $total_matches++;
        if ($r['winner_id'] == 0) $draws++;
    }
}

// Check if admin (to show sidebar) or player (back to profile)
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$back_link = $is_admin ? '/ICS_APP_DEV1/dashboard_and_admin/admin.php' : '../userManagement/profile.php';

function getPhoto($file) {
    if (empty($file)) return null;
    return '../uploads/' . $file;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Battle History | NBSC</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@700;800;900&family=Barlow:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
    --navy:#060D1A; --navy2:#0D1F38; --navy3:#152844;
    --gold:#F5A800; --gold2:#FBBF24;
    --teal:#00C9A7; --rose:#FF4D6D; --sky:#38BDF8;
    --lime:#84CC16; --violet:#A78BFA;
    --text:#E2EAF4; --muted:#5A7A9F;
    --border:rgba(255,255,255,0.07);
    --sidebar:220px; --radius:10px;
}
html,body { height:100%; background:var(--navy); color:var(--text); font-family:'Barlow',sans-serif; font-size:13px; overflow-x:hidden; }
.shell { display:flex; min-height:100vh; }

/* SIDEBAR */
#sidebar { width:var(--sidebar); flex-shrink:0; background:var(--navy2); border-right:1px solid var(--border); display:flex; flex-direction:column; position:fixed; top:0; left:0; height:100vh; z-index:100; overflow-y:auto; }
.sb-logo { padding:22px 18px 16px; border-bottom:1px solid var(--border); }
.sb-mark { width:38px; height:38px; background:var(--gold); border-radius:8px; display:flex; align-items:center; justify-content:center; font-family:'Barlow Condensed',sans-serif; font-weight:900; font-size:15px; color:var(--navy); margin-bottom:9px; }
.sb-title { font-family:'Barlow Condensed',sans-serif; font-weight:900; font-size:14px; color:var(--text); text-transform:uppercase; letter-spacing:.05em; }
.sb-sub { font-size:10px; color:var(--muted); letter-spacing:.07em; text-transform:uppercase; margin-top:2px; }
.sb-nav { padding:10px; flex:1; }
.sb-section { font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:.11em; color:var(--muted); padding:12px 8px 4px; }
.nav-item { display:flex; align-items:center; gap:9px; padding:9px 10px; border-radius:8px; color:var(--muted); text-decoration:none; font-weight:600; font-size:12px; transition:.15s; margin-bottom:2px; }
.nav-item:hover { background:rgba(255,255,255,.05); color:var(--text); }
.nav-item.active { background:rgba(245,168,0,.12); color:var(--gold); }
.nav-item .ni { font-size:14px; width:18px; text-align:center; }
.sb-foot { padding:12px 10px; border-top:1px solid var(--border); }
.admin-chip { display:flex; align-items:center; gap:9px; padding:9px 10px; background:rgba(255,255,255,.04); border-radius:8px; }
.admin-av { width:30px; height:30px; background:linear-gradient(135deg,var(--gold),var(--teal)); border-radius:7px; display:flex; align-items:center; justify-content:center; font-weight:900; font-size:11px; color:var(--navy); flex-shrink:0; }

/* MAIN */
#main { margin-left:var(--sidebar); flex:1; display:flex; flex-direction:column; min-width:0; }
.topbar { padding:14px 24px; border-bottom:1px solid var(--border); background:var(--navy2); display:flex; align-items:center; justify-content:space-between; position:sticky; top:0; z-index:50; }
.page-title { font-family:'Barlow Condensed',sans-serif; font-weight:900; font-size:17px; color:var(--text); letter-spacing:.03em; }
.tb-breadcrumb { font-size:10px; color:var(--muted); margin-top:2px; display:flex; align-items:center; gap:5px; }
.tb-breadcrumb a { color:var(--muted); text-decoration:none; }
.tb-breadcrumb a:hover { color:var(--gold); }
.tb-right { display:flex; align-items:center; gap:10px; }
.tb-btn { background:rgba(255,255,255,.06); border:1px solid var(--border); color:var(--text); border-radius:7px; padding:6px 12px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; text-decoration:none; display:inline-flex; align-items:center; gap:5px; transition:.2s; }
.tb-btn:hover { background:rgba(255,255,255,.1); color:var(--gold); }

.content { padding:20px 24px 40px; flex:1; }

/* STAT STRIP */
.stat-strip { display:grid; grid-template-columns:repeat(3,1fr); gap:10px; margin-bottom:20px; }
.sc { background:var(--navy2); border:1px solid var(--border); border-top:3px solid transparent; border-radius:var(--radius); padding:14px 16px; display:flex; align-items:center; gap:12px; }
.sc.t { border-top-color:var(--gold); }
.sc.c { border-top-color:var(--teal); }
.sc.d { border-top-color:var(--sky); }
.sc-icon { width:36px; height:36px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:16px; flex-shrink:0; }
.sc.t .sc-icon { background:rgba(245,168,0,.1); color:var(--gold); }
.sc.c .sc-icon { background:rgba(0,201,167,.1); color:var(--teal); }
.sc.d .sc-icon { background:rgba(56,189,248,.1); color:var(--sky); }
.sc-val { font-family:'Barlow Condensed',sans-serif; font-weight:900; font-size:24px; color:var(--text); line-height:1; }
.sc-lbl { font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:.09em; color:var(--muted); margin-top:2px; }

/* MATCH CARDS */
.match-list { display:flex; flex-direction:column; gap:12px; }

.match-card {
    background:var(--navy2);
    border:1px solid var(--border);
    border-left:4px solid var(--gold);
    border-radius:var(--radius);
    padding:20px;
    position:relative;
    overflow:hidden;
    transition:box-shadow .2s, transform .2s;
    animation:fadeUp .4s ease both;
}
.match-card:hover { box-shadow:0 6px 24px rgba(0,0,0,.2); transform:translateY(-2px); }
.match-card::before { content:''; position:absolute; top:0; right:0; width:80px; height:80px; background:radial-gradient(circle at top right,rgba(245,168,0,.06),transparent 70%); }

@keyframes fadeUp { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:translateY(0)} }

.mc-top { display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; }
.mc-id { font-family:'Barlow Condensed',sans-serif; font-weight:800; font-size:11px; color:var(--muted); letter-spacing:.06em; }
.mc-date { font-size:10px; color:var(--muted); display:flex; align-items:center; gap:4px; }

.mc-body { display:grid; grid-template-columns:1fr auto 1fr; align-items:center; gap:16px; }

.team-side { text-align:center; }
.team-photo-wrap { width:56px; height:56px; border-radius:50%; border:2.5px solid var(--border); overflow:hidden; margin:0 auto 8px; background:var(--navy3); display:flex; align-items:center; justify-content:center; }
.team-photo-wrap img { width:100%; height:100%; object-fit:cover; }
.team-initials { font-family:'Barlow Condensed',sans-serif; font-weight:900; font-size:18px; color:var(--gold); }
.team-photo-wrap.winner { border-color:var(--gold); box-shadow:0 0 0 3px rgba(245,168,0,.2); }

.winner-tag { display:inline-block; font-size:9px; font-weight:800; text-transform:uppercase; letter-spacing:.07em; padding:2px 8px; border-radius:99px; background:rgba(245,168,0,.12); color:var(--gold); margin-bottom:5px; }
.draw-tag   { display:inline-block; font-size:9px; font-weight:800; text-transform:uppercase; letter-spacing:.07em; padding:2px 8px; border-radius:99px; background:rgba(56,189,248,.12); color:var(--sky); margin-bottom:5px; }
.empty-tag  { display:inline-block; height:20px; margin-bottom:5px; }

.mc-team-name { font-family:'Barlow Condensed',sans-serif; font-weight:900; font-size:15px; color:var(--text); text-transform:uppercase; letter-spacing:.04em; }
.mc-team-id   { font-size:10px; color:var(--muted); margin-top:2px; }

.score-center { text-align:center; }
.score-num { font-family:'Barlow Condensed',sans-serif; font-weight:900; font-size:32px; color:var(--text); line-height:1; letter-spacing:.02em; display:block; }
.score-sep { color:var(--gold); margin:0 4px; }
.vs-pill { display:inline-block; background:var(--navy3); color:var(--sky); font-family:'Barlow Condensed',sans-serif; font-weight:800; font-size:10px; letter-spacing:.08em; padding:3px 10px; border-radius:99px; margin-top:6px; }

/* EMPTY */
.empty-state { text-align:center; padding:60px; color:var(--muted); }
.empty-state .icon { font-size:48px; opacity:.2; display:block; margin-bottom:14px; }
.empty-state h4 { font-family:'Barlow Condensed',sans-serif; font-weight:800; font-size:18px; }

::-webkit-scrollbar { width:4px; height:4px; }
::-webkit-scrollbar-track { background:transparent; }
::-webkit-scrollbar-thumb { background:var(--navy3); border-radius:10px; }
</style>
</head>
<body>
<div class="shell">

  <!-- SIDEBAR -->
  <div id="sidebar">
    <div class="sb-logo">
      <div class="sb-mark">NB</div>
      <div class="sb-title">NBSC Admin</div>
      <div class="sb-sub">Command Center</div>
    </div>
    <div class="sb-nav">
      <div class="sb-section">Main</div>
      <a class="nav-item" href="/ICS_APP_DEV1/dashboard_and_admin/admin.php"><i class="bi bi-grid-fill ni"></i> Overview</a>
      <a class="nav-item" href="/ICS_APP_DEV1/userManagement/view_teams.php"><i class="bi bi-shield-fill ni"></i> Teams</a>
      <a class="nav-item" href="/ICS_APP_DEV1/match_system/matches.php"><i class="bi bi-trophy-fill ni"></i> Matches</a>
      <a class="nav-item" href="/ICS_APP_DEV1/userManagement/manage_users.php"><i class="bi bi-people-fill ni"></i> Players</a>
      <div class="sb-section">Management</div>
      <a class="nav-item" href="/ICS_APP_DEV1/dashboard_and_admin/dashboardmanager.php"><i class="bi bi-cpu-fill ni"></i> Task Manager</a>
      <a class="nav-item" href="/ICS_APP_DEV1/userManagement/schedule.php"><i class="bi bi-calendar3 ni"></i> Schedule</a>
      <a class="nav-item active" href="battle_history.php"><i class="bi bi-bar-chart-fill ni"></i> Battle History</a>
      <div class="sb-section">System</div>
      <a class="nav-item" href="/ICS_APP_DEV1/index.php"><i class="bi bi-house-fill ni"></i> Homepage</a>
      <a class="nav-item" href="/ICS_APP_DEV1/authentication/logout.php" style="color:var(--rose)"><i class="bi bi-box-arrow-left ni"></i> Sign Out</a>
    </div>
    <div class="sb-foot">
      <div class="admin-chip">
        <div class="admin-av"><?= strtoupper(substr($_SESSION['username'] ?? 'AD', 0, 2)); ?></div>
        <div>
          <div style="font-weight:700;font-size:11px;color:var(--text)"><?= htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></div>
          <div style="font-size:9px;color:var(--muted)"><?= $is_admin ? 'Administrator' : 'Player'; ?></div>
        </div>
      </div>
    </div>
  </div>

  <!-- MAIN -->
  <div id="main">
    <div class="topbar">
      <div>
        <div class="page-title">Battle History</div>
        <div class="tb-breadcrumb">
          <span style="color:var(--muted)">Competition Records</span>
          <span style="opacity:.4">›</span>
          <span style="color:var(--gold)">All Confirmed Matches</span>
        </div>
      </div>
      <div class="tb-right">
        <a href="<?= $back_link; ?>" class="tb-btn"><i class="bi bi-arrow-left"></i> Back</a>
      </div>
    </div>

    <div class="content">

      <!-- STAT STRIP -->
      <div class="stat-strip">
        <div class="sc t">
          <div class="sc-icon"><i class="bi bi-trophy-fill"></i></div>
          <div><div class="sc-val"><?= $total_matches; ?></div><div class="sc-lbl">Total Battles</div></div>
        </div>
        <div class="sc c">
          <div class="sc-icon"><i class="bi bi-check-circle-fill"></i></div>
          <div><div class="sc-val"><?= $total_matches - $draws; ?></div><div class="sc-lbl">Decisive Wins</div></div>
        </div>
        <div class="sc d">
          <div class="sc-icon"><i class="bi bi-dash-circle-fill"></i></div>
          <div><div class="sc-val"><?= $draws; ?></div><div class="sc-lbl">Draws</div></div>
        </div>
      </div>

      <!-- MATCH CARDS -->
      <?php if (!empty($rows)): ?>
      <div class="match-list">
        <?php foreach ($rows as $i => $row):
            $home_wins = ($row['winner_id'] != 0 && $row['winner_id'] == $row['home_team_id']);
            $away_wins = ($row['winner_id'] != 0 && $row['winner_id'] == $row['away_team_id']);
            $is_draw   = ($row['winner_id'] == 0);
            $h_photo   = getPhoto($row['home_photo']);
            $a_photo   = getPhoto($row['away_photo']);
            $h_init    = strtoupper(substr($row['home_name'], 0, 2));
            $a_init    = strtoupper(substr($row['away_name'], 0, 2));
        ?>
        <div class="match-card" style="animation-delay:<?= $i * 40; ?>ms">
          <div class="mc-top">
            <span class="mc-id">MATCH #<?= $row['match_id']; ?></span>
            <span class="mc-date"><i class="bi bi-calendar3"></i> <?= date('M d, Y', strtotime($row['reservation_date'])); ?></span>
          </div>
          <div class="mc-body">

            <!-- HOME TEAM -->
            <div class="team-side">
              <?php if ($home_wins): ?><span class="winner-tag"><i class="bi bi-trophy-fill"></i> Winner</span>
              <?php elseif ($is_draw): ?><span class="draw-tag">Draw</span>
              <?php else: ?><span class="empty-tag"></span><?php endif; ?>
              <div class="team-photo-wrap <?= $home_wins ? 'winner' : ''; ?>">
                <?php if ($h_photo): ?>
                  <img src="<?= htmlspecialchars($h_photo); ?>" onerror="this.parentElement.innerHTML='<span class=\'team-initials\'><?= $h_init; ?></span>'">
                <?php else: ?>
                  <span class="team-initials"><?= $h_init; ?></span>
                <?php endif; ?>
              </div>
              <div class="mc-team-name"><?= htmlspecialchars($row['home_name']); ?></div>
              <div class="mc-team-id">ID: #<?= $row['home_team_id']; ?></div>
            </div>

            <!-- SCORE -->
            <div class="score-center">
              <span class="score-num">
                <?= $row['home_score']; ?><span class="score-sep">:</span><?= $row['away_score']; ?>
              </span>
              <span class="vs-pill">FINAL</span>
            </div>

            <!-- AWAY TEAM -->
            <div class="team-side">
              <?php if ($away_wins): ?><span class="winner-tag"><i class="bi bi-trophy-fill"></i> Winner</span>
              <?php elseif ($is_draw): ?><span class="draw-tag">Draw</span>
              <?php else: ?><span class="empty-tag"></span><?php endif; ?>
              <div class="team-photo-wrap <?= $away_wins ? 'winner' : ''; ?>">
                <?php if ($a_photo): ?>
                  <img src="<?= htmlspecialchars($a_photo); ?>" onerror="this.parentElement.innerHTML='<span class=\'team-initials\'><?= $a_init; ?></span>'">
                <?php else: ?>
                  <span class="team-initials"><?= $a_init; ?></span>
                <?php endif; ?>
              </div>
              <div class="mc-team-name"><?= htmlspecialchars($row['away_name']); ?></div>
              <div class="mc-team-id">ID: #<?= $row['away_team_id']; ?></div>
            </div>

          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <div class="empty-state">
        <span class="icon"><i class="bi bi-shield-exclamation"></i></span>
        <h4>No Battle Records Yet</h4>
        <p style="font-size:12px;margin-top:6px">Matches will appear here once they are confirmed.</p>
      </div>
      <?php endif; ?>

    </div>
  </div>
</div>
</body>
</html>