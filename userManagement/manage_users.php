<?php
session_start();
require_once __DIR__ . '/../database_config/db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /ICS_APP_DEV1/authentication/login.php");
    exit();
}

// Handle Add User
if (isset($_POST['add_user'])) {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role     = $_POST['role'];
    $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $password, $role);
    if ($stmt->execute()) {
        header("Location: manage_users.php?msg=User Created Successfully");
    } else {
        header("Location: manage_users.php?err=Registration Failed");
    }
    exit();
}

$result = $conn->query("SELECT id, username, role, created_at FROM users ORDER BY id ASC");
$total  = $result ? $result->num_rows : 0;
$rows   = [];
if ($result) while ($r = $result->fetch_assoc()) $rows[] = $r;

$admins  = count(array_filter($rows, fn($r) => $r['role'] === 'admin'));
$players = count(array_filter($rows, fn($r) => $r['role'] !== 'admin'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>User Management | NBSC Admin</title>
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
.sum-card { background:var(--navy2); border:1px solid var(--border); border-top:3px solid transparent; border-radius:var(--radius); padding:14px 16px; display:flex; align-items:center; gap:12px; transition:.2s; }
.sum-card:hover { transform:translateY(-2px); }
.sum-card.total { border-top-color:var(--gold); }
.sum-card.adm   { border-top-color:var(--rose); }
.sum-card.ply   { border-top-color:var(--sky); }
.sum-icon { width:36px; height:36px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:16px; flex-shrink:0; }
.sum-card.total .sum-icon { background:rgba(245,168,0,.1); color:var(--gold); }
.sum-card.adm   .sum-icon { background:rgba(255,77,109,.1); color:var(--rose); }
.sum-card.ply   .sum-icon { background:rgba(56,189,248,.1); color:var(--sky); }
.sum-val { font-family:'Barlow Condensed',sans-serif; font-weight:900; font-size:24px; color:var(--text); line-height:1; }
.sum-lbl { font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:.09em; color:var(--muted); margin-top:2px; }

/* FORM PANEL */
.panel { background:var(--navy2); border:1px solid var(--border); border-radius:var(--radius); overflow:hidden; margin-bottom:14px; }
.ph { padding:12px 16px; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; }
.pt { font-family:'Barlow Condensed',sans-serif; font-weight:800; font-size:12px; text-transform:uppercase; letter-spacing:.07em; color:var(--text); }
.pb { padding:16px; }

/* FORM CONTROLS */
.form-grid { display:grid; grid-template-columns:1fr 1fr 1fr 1fr; gap:10px; align-items:end; }
.field-label { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.08em; color:var(--muted); margin-bottom:5px; display:block; }
.field-input, .field-select {
    width:100%; background:var(--navy3); border:1px solid var(--border);
    border-radius:7px; padding:8px 12px; color:var(--text);
    font-size:12px; font-family:'Barlow',sans-serif; outline:none; transition:border-color .2s;
}
.field-input:focus, .field-select:focus { border-color:rgba(245,168,0,.4); }
.field-input::placeholder { color:var(--muted); }
.field-select option { background:var(--navy2); }
.submit-btn { width:100%; background:var(--gold); color:var(--navy); border:none; border-radius:7px; padding:9px; font-family:'Barlow Condensed',sans-serif; font-weight:900; font-size:13px; text-transform:uppercase; letter-spacing:.06em; cursor:pointer; transition:.2s; display:flex; align-items:center; justify-content:center; gap:6px; }
.submit-btn:hover { background:var(--gold2); }

/* ALERTS */
.alert-ok  { background:rgba(0,201,167,.1); border:1px solid rgba(0,201,167,.25); border-left:4px solid var(--teal); color:var(--teal); border-radius:var(--radius); padding:10px 14px; margin-bottom:12px; font-size:12px; font-weight:600; display:flex; align-items:center; gap:8px; }
.alert-err { background:rgba(255,77,109,.1); border:1px solid rgba(255,77,109,.25); border-left:4px solid var(--rose); color:var(--rose); border-radius:var(--radius); padding:10px 14px; margin-bottom:12px; font-size:12px; font-weight:600; display:flex; align-items:center; gap:8px; }

/* TABLE */
.ut { width:100%; border-collapse:collapse; }
.ut th { font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:.1em; color:var(--muted); padding:10px 16px; text-align:left; border-bottom:1px solid var(--border); background:rgba(255,255,255,.02); }
.ut td { padding:11px 16px; border-bottom:1px solid rgba(255,255,255,.04); font-size:12px; vertical-align:middle; }
.ut tr:last-child td { border-bottom:none; }
.ut tbody tr { transition:background .12s; }
.ut tbody tr:hover { background:rgba(255,255,255,.025); }

.uid { font-family:'Barlow Condensed',sans-serif; font-weight:800; color:var(--muted); }
.user-cell { display:flex; align-items:center; gap:10px; }
.uav { width:30px; height:30px; border-radius:7px; background:var(--navy3); border:1px solid var(--border); display:flex; align-items:center; justify-content:center; font-family:'Barlow Condensed',sans-serif; font-weight:800; font-size:11px; color:var(--gold); flex-shrink:0; text-transform:uppercase; }
.uname { font-weight:700; color:var(--text); }

.role-pill { font-size:9px; font-weight:800; text-transform:uppercase; letter-spacing:.06em; padding:3px 9px; border-radius:99px; }
.role-pill.admin  { background:rgba(255,77,109,.12); color:var(--rose); }
.role-pill.player { background:rgba(56,189,248,.12); color:var(--sky); }
.role-pill.user   { background:rgba(56,189,248,.12); color:var(--sky); }

.del-btn { display:inline-flex; align-items:center; gap:4px; font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.05em; color:var(--rose); text-decoration:none; padding:4px 10px; border-radius:6px; border:1px solid rgba(255,77,109,.2); background:rgba(255,77,109,.06); transition:.18s; }
.del-btn:hover { background:rgba(255,77,109,.15); border-color:rgba(255,77,109,.4); color:var(--rose); }
.protected { font-size:10px; color:var(--muted); font-style:italic; }

.search-wrap { position:relative; }
.search-wrap i { position:absolute; left:9px; top:50%; transform:translateY(-50%); color:var(--muted); font-size:12px; pointer-events:none; }
.search-input { background:rgba(255,255,255,.05); border:1px solid var(--border); border-radius:7px; padding:5px 12px 5px 30px; color:var(--text); font-size:11px; font-family:'Barlow',sans-serif; outline:none; width:180px; transition:border-color .2s; }
.search-input:focus { border-color:rgba(245,168,0,.4); }

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
      <a class="nav-item active" href="manage_users.php"><i class="bi bi-people-fill ni"></i> Players</a>
      <div class="sb-section">Management</div>
      <a class="nav-item" href="/ICS_APP_DEV1/dashboard_and_admin/dashboardmanager.php"><i class="bi bi-cpu-fill ni"></i> Task Manager</a>
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
        <div class="page-title">User Management</div>
        <div class="tb-breadcrumb">
          <a href="/ICS_APP_DEV1/dashboard_and_admin/admin.php">Admin</a>
          <span style="opacity:.4">›</span>
          <span style="color:var(--gold)">Users</span>
        </div>
      </div>
      <div class="tb-right">
        <a href="/ICS_APP_DEV1/dashboard_and_admin/admin.php" class="tb-btn"><i class="bi bi-arrow-left"></i> Back</a>
      </div>
    </div>

    <div class="content">

      <!-- SUMMARY -->
      <div class="sum-grid">
        <div class="sum-card total">
          <div class="sum-icon"><i class="bi bi-people-fill"></i></div>
          <div><div class="sum-val"><?= $total; ?></div><div class="sum-lbl">Total Accounts</div></div>
        </div>
        <div class="sum-card adm">
          <div class="sum-icon"><i class="bi bi-shield-lock-fill"></i></div>
          <div><div class="sum-val"><?= $admins; ?></div><div class="sum-lbl">Administrators</div></div>
        </div>
        <div class="sum-card ply">
          <div class="sum-icon"><i class="bi bi-person-fill"></i></div>
          <div><div class="sum-val"><?= $players; ?></div><div class="sum-lbl">Players / Users</div></div>
        </div>
      </div>

      <!-- ALERTS -->
      <?php if (isset($_GET['msg'])): ?>
        <div class="alert-ok"><i class="bi bi-check-circle-fill"></i><?= htmlspecialchars($_GET['msg']); ?></div>
      <?php endif; ?>
      <?php if (isset($_GET['err'])): ?>
        <div class="alert-err"><i class="bi bi-x-circle-fill"></i><?= htmlspecialchars($_GET['err']); ?></div>
      <?php endif; ?>

      <!-- ADD USER FORM -->
      <div class="panel">
        <div class="ph">
          <span class="pt"><i class="bi bi-person-plus-fill" style="margin-right:6px;color:var(--gold)"></i>Register New Account</span>
        </div>
        <div class="pb">
          <form method="POST">
            <div class="form-grid">
              <div>
                <label class="field-label">Username</label>
                <input type="text" name="username" class="field-input" placeholder="Enter username" required>
              </div>
              <div>
                <label class="field-label">Temporary Password</label>
                <input type="password" name="password" class="field-input" placeholder="••••••••" required>
              </div>
              <div>
                <label class="field-label">Account Role</label>
                <select name="role" class="field-select" required>
                  <option value="player">Player / User</option>
                  <option value="admin">Administrator</option>
                </select>
              </div>
              <div>
                <button type="submit" name="add_user" class="submit-btn">
                  <i class="bi bi-check-lg"></i> Create Account
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>

      <!-- USERS TABLE -->
      <div class="panel">
        <div class="ph">
          <span class="pt">All Accounts</span>
          <div class="search-wrap">
            <i class="bi bi-search"></i>
            <input type="text" class="search-input" placeholder="Search users..." oninput="searchUsers(this.value)">
          </div>
        </div>
        <div style="overflow-x:auto">
          <table class="ut" id="userTable">
            <thead>
              <tr>
                <th style="width:70px">ID</th>
                <th>Username</th>
                <th style="width:120px">Role</th>
                <th style="width:140px">Created</th>
                <th style="width:120px;text-align:right">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rows as $row): ?>
              <tr data-name="<?= strtolower(htmlspecialchars($row['username'])); ?>">
                <td><span class="uid">#<?= $row['id']; ?></span></td>
                <td>
                  <div class="user-cell">
                    <div class="uav"><?= strtoupper(substr($row['username'], 0, 2)); ?></div>
                    <span class="uname"><?= htmlspecialchars($row['username']); ?></span>
                  </div>
                </td>
                <td><span class="role-pill <?= $row['role']; ?>"><?= ucfirst($row['role']); ?></span></td>
                <td style="color:var(--muted);font-size:11px"><?= date('M d, Y', strtotime($row['created_at'])); ?></td>
                <td style="text-align:right">
                  <?php if ($row['username'] !== 'admin'): ?>
                    <a href="delete_user.php?id=<?= $row['id']; ?>" class="del-btn"
                       onclick="return confirm('Permanently delete this account?')">
                      <i class="bi bi-trash3"></i> Remove
                    </a>
                  <?php else: ?>
                    <span class="protected">System Protected</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
              <?php if (empty($rows)): ?>
              <tr><td colspan="5" style="text-align:center;padding:30px;color:var(--muted)">No accounts found.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</div>
<script>
function searchUsers(val) {
    const q = val.toLowerCase().trim();
    document.querySelectorAll('#userTable tbody tr[data-name]').forEach(r => {
        r.style.display = r.dataset.name.includes(q) ? '' : 'none';
    });
}
</script>
</body>
</html>