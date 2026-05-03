<?php
session_start();
require_once __DIR__ . '/../database_config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /ICS_APP_DEV1/authentication/login.php");
    exit();
}

// Fetch all reservations with team info
$sql = "SELECT r.id, r.reservation_date, r.selected_time, r.status,
               t.team_name, t.team_photo, t.id AS team_id,
               u.username AS reserved_by
        FROM reservations r
        JOIN teams t ON r.team_id = t.id
        LEFT JOIN users u ON r.user_id = u.id
        ORDER BY r.reservation_date ASC, r.selected_time ASC";
$result = $conn->query($sql);
$rows   = [];
if ($result) while ($r = $result->fetch_assoc()) $rows[] = $r;

// Count by status
$total    = count($rows);
$upcoming = count(array_filter($rows, fn($r) => strtotime($r['reservation_date']) >= strtotime(date('Y-m-d'))));
$past     = $total - $upcoming;

// Group by month
$grouped = [];
foreach ($rows as $row) {
    $key = date('F Y', strtotime($row['reservation_date']));
    $grouped[$key][] = $row;
}

function getPhoto($f) {
    return (!empty($f)) ? '../uploads/' . $f : null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Schedule | NBSC Admin</title>
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
.tb-btn { background:rgba(255,255,255,.06); border:1px solid var(--border); color:var(--text); border-radius:7px; padding:6px 12px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; text-decoration:none; display:inline-flex; align-items:center; gap:5px; transition:.2s; cursor:pointer; font-family:'Barlow',sans-serif; }
.tb-btn:hover { background:rgba(255,255,255,.1); color:var(--gold); }
.tb-btn.gold { background:var(--gold); color:var(--navy); border-color:var(--gold); }
.tb-btn.gold:hover { background:var(--gold2); color:var(--navy); }

.content { padding:20px 24px 40px; flex:1; }

/* SUMMARY */
.sum-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:10px; margin-bottom:18px; }
.sc { background:var(--navy2); border:1px solid var(--border); border-top:3px solid transparent; border-radius:var(--radius); padding:14px 16px; display:flex; align-items:center; gap:12px; transition:.2s; }
.sc:hover { transform:translateY(-2px); }
.sc.t { border-top-color:var(--gold); }
.sc.u { border-top-color:var(--teal); }
.sc.p { border-top-color:var(--muted); }
.sc-icon { width:36px; height:36px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:16px; flex-shrink:0; }
.sc.t .sc-icon { background:rgba(245,168,0,.1); color:var(--gold); }
.sc.u .sc-icon { background:rgba(0,201,167,.1); color:var(--teal); }
.sc.p .sc-icon { background:rgba(90,122,159,.1); color:var(--muted); }
.sc-val { font-family:'Barlow Condensed',sans-serif; font-weight:900; font-size:24px; color:var(--text); line-height:1; }
.sc-lbl { font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:.09em; color:var(--muted); margin-top:2px; }

/* FILTER BAR */
.filter-bar { display:flex; align-items:center; gap:8px; margin-bottom:16px; flex-wrap:wrap; }
.filter-btn { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; padding:5px 14px; border-radius:99px; border:1px solid var(--border); background:transparent; color:var(--muted); cursor:pointer; transition:.15s; font-family:'Barlow',sans-serif; }
.filter-btn:hover { color:var(--text); border-color:rgba(255,255,255,.15); }
.filter-btn.active { background:rgba(245,168,0,.12); color:var(--gold); border-color:rgba(245,168,0,.3); }
.search-wrap { margin-left:auto; position:relative; }
.search-wrap i { position:absolute; left:9px; top:50%; transform:translateY(-50%); color:var(--muted); font-size:12px; pointer-events:none; }
.search-input { background:rgba(255,255,255,.05); border:1px solid var(--border); border-radius:7px; padding:5px 12px 5px 30px; color:var(--text); font-size:11px; font-family:'Barlow',sans-serif; outline:none; width:190px; transition:border-color .2s; }
.search-input:focus { border-color:rgba(245,168,0,.4); }
.search-input::placeholder { color:var(--muted); }

/* MONTH GROUP */
.month-group { margin-bottom:22px; }
.month-label { font-family:'Barlow Condensed',sans-serif; font-weight:900; font-size:13px; text-transform:uppercase; letter-spacing:.1em; color:var(--muted); margin-bottom:10px; display:flex; align-items:center; gap:8px; }
.month-label::after { content:''; flex:1; height:1px; background:var(--border); }

/* SCHEDULE GRID */
.sched-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(240px,1fr)); gap:10px; }

/* SCHEDULE CARD */
.sched-card {
    background:var(--navy2);
    border:1px solid var(--border);
    border-left:4px solid var(--gold);
    border-radius:var(--radius);
    padding:14px;
    display:flex; flex-direction:column; gap:10px;
    transition:box-shadow .2s, transform .2s;
    animation:fadeUp .4s ease both;
    position:relative; overflow:hidden;
}
.sched-card:hover { box-shadow:0 6px 20px rgba(0,0,0,.2); transform:translateY(-2px); }
.sched-card.past { border-left-color:var(--muted); opacity:.7; }
.sched-card.today { border-left-color:var(--teal); }
.sched-card::before { content:''; position:absolute; top:0; right:0; width:50px; height:50px; background:radial-gradient(circle at top right,rgba(245,168,0,.06),transparent 70%); }

@keyframes fadeUp { from{opacity:0;transform:translateY(8px)} to{opacity:1;transform:translateY(0)} }

.sc-head { display:flex; align-items:center; justify-content:space-between; }
.sc-date-main { font-family:'Barlow Condensed',sans-serif; font-weight:900; font-size:22px; color:var(--text); line-height:1; }
.sc-date-day  { font-size:10px; color:var(--muted); margin-top:1px; }
.time-pill { background:var(--navy3); color:var(--sky); font-family:'Barlow Condensed',sans-serif; font-weight:800; font-size:11px; padding:3px 9px; border-radius:6px; letter-spacing:.04em; }

.sc-team { display:flex; align-items:center; gap:10px; }
.team-av { width:38px; height:38px; border-radius:50%; border:2px solid var(--border); overflow:hidden; background:var(--navy3); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.team-av img { width:100%; height:100%; object-fit:cover; }
.team-av-init { font-family:'Barlow Condensed',sans-serif; font-weight:900; font-size:14px; color:var(--gold); }
.team-av-name { font-family:'Barlow Condensed',sans-serif; font-weight:800; font-size:13px; color:var(--text); text-transform:uppercase; letter-spacing:.03em; }
.team-av-by { font-size:10px; color:var(--muted); }

.sc-foot { display:flex; align-items:center; justify-content:space-between; padding-top:8px; border-top:1px solid var(--border); }
.today-tag { font-size:9px; font-weight:800; text-transform:uppercase; letter-spacing:.07em; padding:2px 8px; border-radius:99px; background:rgba(0,201,167,.12); color:var(--teal); }
.past-tag  { font-size:9px; font-weight:800; text-transform:uppercase; letter-spacing:.07em; padding:2px 8px; border-radius:99px; background:rgba(90,122,159,.12); color:var(--muted); }
.up-tag    { font-size:9px; font-weight:800; text-transform:uppercase; letter-spacing:.07em; padding:2px 8px; border-radius:99px; background:rgba(245,168,0,.12); color:var(--gold); }

/* EMPTY */
.empty-state { text-align:center; padding:60px; color:var(--muted); }
.empty-state .icon { font-size:44px; opacity:.2; display:block; margin-bottom:14px; }
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
      <a class="nav-item active" href="schedule.php"><i class="bi bi-calendar3 ni"></i> Schedule</a>
      <a class="nav-item" href="/ICS_APP_DEV1/Teams%26history1/battle_history.php"><i class="bi bi-bar-chart-fill ni"></i> Battle History</a>
      <div class="sb-section">System</div>
      <a class="nav-item" href="/ICS_APP_DEV1/index.php"><i class="bi bi-house-fill ni"></i> Homepage</a>
      <a class="nav-item" href="/ICS_APP_DEV1/authentication/logout.php" style="color:var(--rose)"><i class="bi bi-box-arrow-left ni"></i> Sign Out</a>
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

  <!-- MAIN -->
  <div id="main">
    <div class="topbar">
      <div>
        <div class="page-title">Match Schedule</div>
        <div class="tb-breadcrumb">
          <a href="/ICS_APP_DEV1/dashboard_and_admin/admin.php">Admin</a>
          <span style="opacity:.4">›</span>
          <span style="color:var(--gold)">Schedule</span>
        </div>
      </div>
      <div class="tb-right">
        <a href="/ICS_APP_DEV1/dashboard_and_admin/admin.php" class="tb-btn">
          <i class="bi bi-arrow-left"></i> Back
        </a>
      </div>
    </div>

    <div class="content">

      <!-- SUMMARY -->
      <div class="sum-grid">
        <div class="sc t">
          <div class="sc-icon"><i class="bi bi-calendar-fill"></i></div>
          <div><div class="sc-val"><?= $total; ?></div><div class="sc-lbl">Total Reservations</div></div>
        </div>
        <div class="sc u">
          <div class="sc-icon"><i class="bi bi-calendar-check-fill"></i></div>
          <div><div class="sc-val"><?= $upcoming; ?></div><div class="sc-lbl">Upcoming</div></div>
        </div>
        <div class="sc p">
          <div class="sc-icon"><i class="bi bi-calendar-x-fill"></i></div>
          <div><div class="sc-val"><?= $past; ?></div><div class="sc-lbl">Past</div></div>
        </div>
      </div>

      <!-- FILTER BAR -->
      <div class="filter-bar">
        <button class="filter-btn active" onclick="filterSched('all',this)">All</button>
        <button class="filter-btn" onclick="filterSched('upcoming',this)">Upcoming</button>
        <button class="filter-btn" onclick="filterSched('today',this)">Today</button>
        <button class="filter-btn" onclick="filterSched('past',this)">Past</button>
        <div class="search-wrap">
          <i class="bi bi-search"></i>
          <input type="text" class="search-input" placeholder="Search team..." oninput="searchSched(this.value)">
        </div>
      </div>

      <!-- SCHEDULE GROUPED BY MONTH -->
      <?php if (!empty($grouped)):
        $today_str = date('Y-m-d');
        $delay = 0;
        foreach ($grouped as $month => $entries): ?>
        <div class="month-group" data-month-group>
          <div class="month-label"><?= $month; ?></div>
          <div class="sched-grid">
            <?php foreach ($entries as $r):
                $r_date   = $r['reservation_date'];
                $is_today = ($r_date === $today_str);
                $is_past  = (strtotime($r_date) < strtotime($today_str));
                $cardClass = $is_today ? 'today' : ($is_past ? 'past' : '');
                $typeKey   = $is_today ? 'today' : ($is_past ? 'past' : 'upcoming');
                $photo     = getPhoto($r['team_photo']);
                $initials  = strtoupper(substr($r['team_name'], 0, 2));
                $delay += 40;
            ?>
            <div class="sched-card <?= $cardClass; ?>" style="animation-delay:<?= $delay; ?>ms"
                 data-type="<?= $typeKey; ?>"
                 data-team="<?= strtolower(htmlspecialchars($r['team_name'])); ?>">

              <div class="sc-head">
                <div>
                  <div class="sc-date-main"><?= date('d', strtotime($r_date)); ?></div>
                  <div class="sc-date-day"><?= date('D, M Y', strtotime($r_date)); ?></div>
                </div>
                <span class="time-pill"><?= htmlspecialchars($r['selected_time'] ?? '—'); ?></span>
              </div>

              <div class="sc-team">
                <div class="team-av">
                  <?php if ($photo): ?>
                    <img src="<?= htmlspecialchars($photo); ?>"
                         onerror="this.parentElement.innerHTML='<span class=\'team-av-init\'><?= $initials; ?></span>'">
                  <?php else: ?>
                    <span class="team-av-init"><?= $initials; ?></span>
                  <?php endif; ?>
                </div>
                <div>
                  <div class="team-av-name"><?= htmlspecialchars($r['team_name']); ?></div>
                  <div class="team-av-by">By: <?= htmlspecialchars($r['reserved_by'] ?? 'Unknown'); ?></div>
                </div>
              </div>

              <div class="sc-foot">
                <span style="font-size:10px;color:var(--muted)">ID #<?= $r['id']; ?></span>
                <?php if ($is_today): ?>
                  <span class="today-tag"><i class="bi bi-circle-fill" style="font-size:6px"></i> Today</span>
                <?php elseif ($is_past): ?>
                  <span class="past-tag">Past</span>
                <?php else: ?>
                  <span class="up-tag">Upcoming</span>
                <?php endif; ?>
              </div>

            </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>

      <?php else: ?>
      <div class="empty-state">
        <span class="icon"><i class="bi bi-calendar-x"></i></span>
        <h4>No Reservations Found</h4>
        <p style="font-size:12px;margin-top:6px">Reservations will appear here once teams book a slot.</p>
      </div>
      <?php endif; ?>

    </div>
  </div>
</div>

<script>
function filterSched(type, btn) {
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('.sched-card[data-type]').forEach(c => {
        c.style.display = (type === 'all' || c.dataset.type === type) ? '' : 'none';
    });
    // Hide empty month groups
    document.querySelectorAll('[data-month-group]').forEach(g => {
        const visible = [...g.querySelectorAll('.sched-card')].some(c => c.style.display !== 'none');
        g.style.display = visible ? '' : 'none';
    });
}
function searchSched(val) {
    const q = val.toLowerCase().trim();
    document.querySelectorAll('.sched-card[data-team]').forEach(c => {
        c.style.display = c.dataset.team.includes(q) ? '' : 'none';
    });
    document.querySelectorAll('[data-month-group]').forEach(g => {
        const visible = [...g.querySelectorAll('.sched-card')].some(c => c.style.display !== 'none');
        g.style.display = visible ? '' : 'none';
    });
}
</script>
</body>
</html>