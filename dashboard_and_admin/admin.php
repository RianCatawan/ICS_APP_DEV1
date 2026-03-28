<?php
session_start();
include(__DIR__ . '/../database&config/db.php');

// Security Check: Only allow 'admin' role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /authentication/login.php");
    exit();
}

// ── FETCH DATA FOR OVERVIEWS ──
// Total Players (Users table)
$total_players = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='player'")->fetch_assoc()['total'] ?? 0;

// Total Teams
$total_teams = $conn->query("SELECT COUNT(*) as total FROM teams")->fetch_assoc()['total'] ?? 0;

// Total Matches (All requests)
$total_matches = $conn->query("SELECT COUNT(*) as total FROM match_requests")->fetch_assoc()['total'] ?? 0;

// Pending Actions (Requests where status is still 'pending')
$pending_reqs = $conn->query("SELECT COUNT(*) as total FROM match_requests WHERE final_status='pending'")->fetch_assoc()['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Main Control Center | NBSC Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

        :root {
            --primary-blue: #071A42;
            --sidebar-width: 260px;
            --accent-gold: #FFD700;
        }
        
        body { background-color: #f4f7f6; font-family: 'Inter', sans-serif; overflow-x: hidden; }
        
        /* ── SIDEBAR ── */
        #sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            background: var(--primary-blue);
            color: white;
            transition: 0.3s;
            border-right: 4px solid var(--accent-gold);
            z-index: 1000;
        }
        .nav-link { color: rgba(255,255,255,0.7); padding: 15px 25px; font-weight: 500; transition: 0.2s; }
        .nav-link:hover, .nav-link.active { color: white; background: rgba(255,255,255,0.1); }
        .nav-link.active { border-left: 4px solid var(--accent-gold); }
        
        /* ── MAIN CONTENT ── */
        #content {
            margin-left: var(--sidebar-width);
            padding: 40px;
            width: calc(100% - var(--sidebar-width));
        }

        .stat-card {
            border: none;
            border-radius: 12px;
            padding: 25px;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            height: 100%;
        }
        .icon-box {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-right: 20px;
        }
        .bg-light-blue { background: #e3f2fd; color: #1976d2; }
        .bg-light-orange { background: #fff3e0; color: #f57c00; }
        .bg-light-green { background: #e8f5e9; color: #388e3c; }

        /* ── MANAGER LINK BUTTON ── */
        .manager-link-btn {
            background: var(--accent-gold);
            color: #000;
            border: none;
            padding: 12px 25px;
            border-radius: 10px;
            font-weight: 800;
            text-transform: uppercase;
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
            transition: 0.3s;
        }
        .manager-link-btn:hover {
            transform: translateY(-2px);
            background: #000;
            color: var(--accent-gold);
        }

        .quick-btn {
            transition: 0.3s;
            border-width: 2px;
        }
        .quick-btn:hover { transform: translateY(-3px); }
    </style>
</head>
<body>

<div id="sidebar">
    <div class="p-4 text-center">
        <i class="bi bi-dribbble text-warning fs-1"></i>
        <h4 class="fw-bold mb-0 mt-2">NBSC ADMIN</h4>
        <small class="opacity-50">Basketball Control Center</small>
    </div>
    <nav class="nav flex-column mt-4">
        <a class="nav-link active" href="#"><i class="bi bi-speedometer2 me-2"></i> Overview</a>
        <a class="nav-link" href="/userManagement/view_teams.php"><i class="bi bi-people me-2"></i> Teams</a>
        <a class="nav-link" href="/match_system/matches.php"><i class="bi bi-trophy me-2"></i> Matches</a>
        <a class="nav-link" href="/userManagement/manage_users.php"><i class="bi bi-person-gear me-2"></i> User Settings</a>
        <hr class="mx-3 opacity-25">
        <a class="nav-link text-danger" href="/authentication/logout.php"><i class="bi bi-box-arrow-left me-2"></i> Sign Out</a>
    </nav>
</div>

<div id="content">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h2 class="fw-bold">System Summary</h2>
            <p class="text-muted">Hello, <strong><?php echo $_SESSION['username']; ?></strong>. Here is the court activity.</p>
        </div>
        
        <a href="/dashboard_and_admin/dashboardmanager.php" class="manager-link-btn text-decoration-none">
            <i class="bi bi-cpu-fill me-2"></i> Admin Task Manager
        </a>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="icon-box bg-light-blue"><i class="bi bi-person-fill"></i></div>
                <div>
                    <h3 class="fw-bold mb-0"><?php echo $total_players; ?></h3>
                    <small class="text-muted">Players</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="icon-box bg-light-green"><i class="bi bi-shield-check"></i></div>
                <div>
                    <h3 class="fw-bold mb-0"><?php echo $total_teams; ?></h3>
                    <small class="text-muted">Active Teams</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="icon-box bg-light-orange"><i class="bi bi-flag-fill"></i></div>
                <div>
                    <h3 class="fw-bold mb-0"><?php echo $total_matches; ?></h3>
                    <small class="text-muted">Total Matches</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card" style="border: 1px solid #ffc107;">
                <div class="icon-box bg-warning text-dark"><i class="bi bi-exclamation-triangle"></i></div>
                <div>
                    <h3 class="fw-bold mb-0"><?php echo $pending_reqs; ?></h3>
                    <small class="text-muted">Pending Actions</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm p-4 mb-4" style="border-radius: 15px;">
                <h5 class="fw-bold mb-4">Quick Operations</h5>
                <div class="row g-3">
                    <div class="col-6">
                        <a href="/userManagement/add_team.php" class="btn btn-outline-primary w-100 py-3 rounded-4 quick-btn text-decoration-none">
                            <i class="bi bi-plus-circle d-block mb-2 fs-4"></i> Create New Team
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="/userManagement/schedule.php" class="btn btn-outline-success w-100 py-3 rounded-4 quick-btn text-decoration-none">
                            <i class="bi bi-calendar-check d-block mb-2 fs-4"></i> Manage Schedule
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 bg-dark text-white" style="border-radius: 15px;">
                <h5 class="fw-bold text-warning">System Status</h5>
                <hr class="opacity-25">
                <div class="d-flex justify-content-between mb-2">
                    <span>Database</span>
                    <span class="text-success small">● Connected</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Live Match Sync</span>
                    <span class="text-info small">Active</span>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Admin Session</span>
                    <span class="text-white small">Expires in 2h</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>