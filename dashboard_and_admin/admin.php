<?php
session_start();
include "db.php";

// Security Check
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Fetch Data for the Overviews
$total_players = $conn->query("SELECT COUNT(*) as total FROM players")->fetch_assoc()['total'] ?? 0;
$total_teams = $conn->query("SELECT COUNT(*) as total FROM teams")->fetch_assoc()['total'] ?? 0;
$total_matches = $conn->query("SELECT COUNT(*) as total FROM match_requests")->fetch_assoc()['total'] ?? 0;
$pending_reqs = $conn->query("SELECT COUNT(*) as total FROM match_requests WHERE status='pending'")->fetch_assoc()['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Main Control Center | NBSC Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary-blue: #0d47a1;
            --sidebar-width: 260px;
        }
        body { background-color: #f4f7f6; font-family: 'Inter', sans-serif; overflow-x: hidden; }
        
        /* SIDEBAR */
        #sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            background: var(--primary-blue);
            color: white;
            transition: 0.3s;
        }
        .nav-link { color: rgba(255,255,255,0.7); padding: 15px 25px; font-weight: 500; }
        .nav-link:hover, .nav-link.active { color: white; background: rgba(255,255,255,0.1); }
        
        /* MAIN CONTENT */
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

        /* THE MANAGER CONNECT BUTTON */
        .manager-link-btn {
            background: #FFD700;
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
            transform: scale(1.05);
            background: #000;
            color: #FFD700;
        }
    </style>
</head>
<body>

<div id="sidebar">
    <div class="p-4 text-center">
        <h4 class="fw-bold mb-0">NBSC ADMIN</h4>
        <small class="opacity-50">System Dashboard</small>
    </div>
    <nav class="nav flex-column mt-4">
        <a class="nav-link active" href="#"><i class="bi bi-speedometer2 me-2"></i> Overview</a>
        <a class="nav-link" href="view_teams.php"><i class="bi bi-people me-2"></i> Teams</a>
        <a class="nav-link" href="matches.php"><i class="bi bi-trophy me-2"></i> Matches</a>
        <a class="nav-link" href="manage_users.php"><i class="bi bi-person-gear me-2"></i> User Settings</a>
        <hr class="mx-3 opacity-25">
        <a class="nav-link text-danger" href="index.php"><i class="bi bi-box-arrow-left me-2"></i> Sign Out</a>
    </nav>
</div>

<div id="content">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h2 class="fw-bold">System Summary</h2>
            <p class="text-muted">Welcome back, Admin. Here is what's happening today.</p>
        </div>
        
        <a href="dashboardmanager.php" class="manager-link-btn text-decoration-none">
            <i class="bi bi-cpu-fill me-2"></i> Admin Task Manager
        </a>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="icon-box bg-light-blue"><i class="bi bi-person-fill"></i></div>
                <div>
                    <h3 class="fw-bold mb-0"><?php echo $total_players; ?></h3>
                    <small class="text-muted">Total Players</small>
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
                    <small class="text-muted">All Matches</small>
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
                        <button class="btn btn-outline-primary w-100 py-3 rounded-4">
                            <i class="bi bi-plus-circle d-block mb-2 fs-4"></i> Add New Team
                        </button>
                    </div>
                    <div class="col-6">
                        <button class="btn btn-outline-success w-100 py-3 rounded-4">
                            <i class="bi bi-calendar-check d-block mb-2 fs-4"></i> Schedule Match
                        </button>
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
                    <span>Server Load</span>
                    <span class="text-info small">24%</span>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Active Sessions</span>
                    <span class="text-white small"><?php echo rand(1, 5); ?> Admin(s)</span>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>