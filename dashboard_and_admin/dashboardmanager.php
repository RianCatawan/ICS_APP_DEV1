<?php
session_start();
require_once __DIR__ . '/../database_config/db.php';

if (!isset($_SESSION['username'])) {
    header("Location: /ICS_APP_DEV1/authentication/login.php");
    exit();
}

$team_count  = $conn->query("SELECT COUNT(*) as total FROM teams")->fetch_assoc()['total'] ?? 0;
$match_count = $conn->query("SELECT COUNT(*) as total FROM match_requests WHERE status='pending'")->fetch_assoc()['total'] ?? 0;

$log_query = "SELECT * FROM user_logs ORDER BY login_time DESC LIMIT 10";
$user_logs = $conn->query($log_query);
$logs = [];
if ($user_logs) while ($l = $user_logs->fetch_assoc()) $logs[] = $l;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Task Manager | NBSC Admin</title>
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
.tb-sub { font-size:10px; color:var(--muted); margin-top:2px; }
.tb-right { display:flex; align-items:center; gap:10px; }
.tb-btn { background:rgba(255,255,255,.06); border:1px solid var(--border); color:var(--text); border-radius:7px; padding:6px 12px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; text-decoration:none; display:inline-flex; align-items:center; gap:5px; transition:.2s; }
.tb-btn:hover { background:rgba(255,255,255,.1); color:var(--gold); }
.tb-btn.rose { background:rgba(255,77,109,.1); border-color:rgba(255,77,109,.25); color:var(--rose); }
.tb-btn.rose:hover { background:rgba(255,77,109,.2); }

.content { padding:20px 24px 40px; flex:1; }

/* PANEL */
.panel { background:var(--navy2); border:1px solid var(--border); border-radius:var(--radius); overflow:hidden; }
.ph { padding:12px 16px; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; }
.pt { font-family:'Barlow Condensed',sans-serif; font-weight:800; font-size:12px; text-transform:uppercase; letter-spacing:.07em; color:var(--text); }
.ph a { font-size:10px; color:var(--gold); text-decoration:none; font-weight:700; }
.ph a:hover { color:var(--gold2); }
.pb { padding:16px; }

/* STAT CARDS */
.stat-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:14px; }
.stat-card { background:var(--navy2); border:1px solid var(--border); border-top:3px solid transparent; border-radius:var(--radius); padding:20px; text-align:center; transition:.2s; }
.stat-card:hover { transform:translateY(-2px); }
.stat-card.teams { border-top-color:var(--gold); }
.stat-card.matches { border-top-color:var(--rose); }
.stat-num { font-family:'Barlow Condensed',sans-serif; font-weight:900; font-size:44px; color:var(--text); line-height:1; margin-bottom:4px; }
.stat-card.teams .stat-num { color:var(--gold); }
.stat-card.matches .stat-num { color:var(--rose); }
.stat-lbl { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.09em; color:var(--muted); margin-bottom:14px; }
.stat-link { display:inline-flex; align-items:center; justify-content:center; gap:6px; width:100%; background:rgba(255,255,255,.05); border:1px solid var(--border); border-radius:7px; padding:8px; color:var(--text); font-size:11px; font-weight:700; text-decoration:none; text-transform:uppercase; letter-spacing:.05em; transition:.18s; }
.stat-card.teams   .stat-link:hover { background:rgba(245,168,0,.1); color:var(--gold); border-color:rgba(245,168,0,.3); }
.stat-card.matches .stat-link:hover { background:rgba(255,77,109,.1); color:var(--rose); border-color:rgba(255,77,109,.3); }

/* TASK ITEMS */
.task-item { display:flex; align-items:center; justify-content:space-between; padding:12px 14px; background:rgba(255,255,255,.03); border:1px solid var(--border); border-left:3px solid var(--gold); border-radius:8px; margin-bottom:8px; transition:.15s; }
.task-item:last-child { margin-bottom:0; }
.task-item:hover { background:rgba(245,168,0,.04); border-color:rgba(245,168,0,.2); }
.task-title { font-weight:700; font-size:12px; color:var(--text); }
.task-meta  { font-size:10px; color:var(--muted); margin-top:2px; }
.task-btn { width:32px; height:32px; border-radius:7px; border:1px solid var(--border); background:rgba(255,255,255,.05); color:var(--text); display:flex; align-items:center; justify-content:center; font-size:14px; text-decoration:none; transition:.15s; flex-shrink:0; cursor:pointer; }
.task-btn:hover { background:rgba(245,168,0,.12); color:var(--gold); border-color:rgba(245,168,0,.3); }

/* LOG TABLE */
.lt { width:100%; border-collapse:collapse; }
.lt th { font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:.1em; color:var(--muted); padding:10px 16px; text-align:left; border-bottom:1px solid var(--border); background:rgba(255,255,255,.02); }
.lt td { padding:10px 16px; border-bottom:1px solid rgba(255,255,255,.04); font-size:12px; vertical-align:middle; }
.lt tr:last-child td { border-bottom:none; }
.lt tbody tr:hover { background:rgba(255,255,255,.02); }
.log-user { display:flex; align-items:center; gap:8px; }
.log-av { width:28px; height:28px; border-radius:6px; background:var(--navy3); border:1px solid var(--border); display:flex; align-items:center; justify-content:center; font-family:'Barlow Condensed',sans-serif; font-weight:800; font-size:10px; color:var(--gold); flex-shrink:0; text-transform:uppercase; }
.login-badge { font-size:9px; font-weight:800; text-transform:uppercase; letter-spacing:.06em; padding:2px 8px; border-radius:99px; background:rgba(0,201,167,.12); color:var(--teal); }

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
      <a class="nav-item active" href="dashboardmanager.php"><i class="bi bi-cpu-fill ni"></i> Task Manager</a>
      <a class="nav-item" href="/ICS_APP_DEV1/userManagement/schedule.php"><i class="bi bi-calendar3 ni"></i> Schedule</a>
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
        <div class="page-title">Task Manager</div>
        <div class="tb-sub">NBSC Basketball Management System</div>
      </div>
      <div class="tb-right">
        <a href="/ICS_APP_DEV1/dashboard_and_admin/admin.php" class="tb-btn rose">
          <i class="bi bi-arrow-left"></i> Back
        </a>
      </div>
    </div>

    <div class="content">
      <div style="display:grid;grid-template-columns:1fr 1fr 1.5fr;gap:14px;margin-bottom:14px;align-items:start">

        <!-- TEAMS STAT -->
        <div class="stat-card teams">
          <div class="stat-num"><?= $team_count; ?></div>
          <div class="stat-lbl">Total Teams</div>
          <a href="/ICS_APP_DEV1/userManagement/view_teams.php" class="stat-link">
            <i class="bi bi-shield-fill"></i> Manage Teams
          </a>
        </div>

        <!-- MATCHES STAT -->
        <div class="stat-card matches">
          <div class="stat-num"><?= $match_count; ?></div>
          <div class="stat-lbl">Pending Matches</div>
          <a href="/ICS_APP_DEV1/match_system/matches.php" class="stat-link">
            <i class="bi bi-trophy-fill"></i> Review Requests
          </a>
        </div>

        <!-- TASK LIST -->
        <div class="panel">
          <div class="ph"><span class="pt"><i class="bi bi-list-task" style="margin-right:5px;color:var(--gold)"></i>Task List</span></div>
          <div class="pb">
            <div class="task-item">
              <div>
                <div class="task-title">User Access</div>
                <div class="task-meta">Manage player & admin accounts</div>
              </div>
              <a href="/ICS_APP_DEV1/userManagement/manage_users.php" class="task-btn">
                <i class="bi bi-people-fill"></i>
              </a>
            </div>
            <div class="task-item">
              <div>
                <div class="task-title">Schedule Matches</div>
                <div class="task-meta">View & manage reservations</div>
              </div>
              <a href="/ICS_APP_DEV1/userManagement/schedule.php" class="task-btn">
                <i class="bi bi-calendar3"></i>
              </a>
            </div>
            <div class="task-item">
              <div>
                <div class="task-title">Battle History</div>
                <div class="task-meta">Review confirmed match results</div>
              </div>
              <a href="/ICS_APP_DEV1/Teams%26history1/battle_history.php" class="task-btn">
                <i class="bi bi-bar-chart-fill"></i>
              </a>
            </div>
            <div class="task-item">
              <div>
                <div class="task-title">Maintenance</div>
                <div class="task-meta">Clear old cache & logs</div>
              </div>
              <button class="task-btn"><i class="bi bi-gear-fill"></i></button>
            </div>
          </div>
        </div>

      </div>

      <!-- ACTIVITY LOGS -->
      <div class="panel">
        <div class="ph">
          <span class="pt"><i class="bi bi-clock-history" style="margin-right:5px;color:var(--gold)"></i>Recent Activity Logs</span>
          <span style="font-size:10px;color:var(--muted)">Last 10 sessions</span>
        </div>
        <div style="overflow-x:auto">
          <table class="lt">
            <thead>
              <tr>
                <th>Student / User</th>
                <th style="width:120px">Action</th>
                <th style="width:200px">Timestamp</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($logs)): ?>
                <?php foreach ($logs as $log):
                    $uid = $log['student_id'] ?? $log['username'] ?? $log['user_id'] ?? 'Unknown';
                    $init = strtoupper(substr($uid, 0, 2));
                ?>
                <tr>
                  <td>
                    <div class="log-user">
                      <div class="log-av"><?= $init; ?></div>
                      <span style="font-weight:700;color:var(--text)"><?= htmlspecialchars($uid); ?></span>
                    </div>
                  </td>
                  <td><span class="login-badge">Login</span></td>
                  <td style="color:var(--muted);font-size:11px"><?= date('M d, Y | h:i A', strtotime($log['login_time'])); ?></td>
                </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr><td colspan="3" style="text-align:center;padding:30px;color:var(--muted)">No activity logs recorded yet.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</div>
</body>
</html>