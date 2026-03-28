<?php
session_start();
include(__DIR__ . '/../database&config/db.php');

// Security Check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /authentication/login.php");
    exit();
}

// Fetch matches with a clean Join
$sql = "SELECT mr.id, t1.team_name AS team1, t2.team_name AS team2, 
               r.reservation_date, r.selected_time, mr.status, mr.home_score, mr.away_score
        FROM match_requests mr
        JOIN reservations r ON mr.reservation_id = r.id
        JOIN teams t1 ON r.team_id = t1.id
        JOIN teams t2 ON mr.challenger_team_id = t2.id
        ORDER BY r.reservation_date DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Match Schedule | NBSC Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        :root {
            --primary-blue: #071A42;
            --sidebar-width: 260px;
            --accent-gold: #FFD700;
        }
        
        body { 
            background-color: #f4f7f6; 
            font-family: 'Inter', system-ui, sans-serif; 
            margin: 0;
        }

        /* ── SIDEBAR (Stable Fix) ── */
        #sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            background: var(--primary-blue);
            border-right: 4px solid var(--accent-gold);
            z-index: 1000;
            color: white;
        }

        #main-content {
            margin-left: var(--sidebar-width);
            padding: 40px;
            width: calc(100% - var(--sidebar-width));
            min-height: 100vh;
        }

        .nav-link { 
            color: rgba(255,255,255,0.7); 
            padding: 15px 25px; 
            text-decoration: none; 
            display: block; 
            transition: 0.2s;
        }
        .nav-link:hover, .nav-link.active { 
            color: white; 
            background: rgba(255,255,255,0.1); 
        }
        .nav-link.active { border-left: 4px solid var(--accent-gold); }

        /* ── TABLE DESIGN ── */
        .table-container {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            border: none;
            transform: translateZ(0); /* Anti-flicker */
        }

        .table thead th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            color: var(--primary-blue);
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.85rem;
            padding: 15px;
        }

        .table tbody td {
            padding: 15px;
            vertical-align: middle;
            border-bottom: 1px solid #f0f0f0;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 800;
            letter-spacing: 0.5px;
        }
        
        /* Dynamic Status Colors */
        .status-pending { background: #fff3e0; color: #ef6c00; }
        .status-accepted { background: #e8f5e9; color: #2e7d32; }
        .status-finished { background: #e3f2fd; color: #1565c0; }

        .score-box {
            font-weight: 800;
            color: var(--primary-blue);
            background: #f0f2f5;
            padding: 4px 10px;
            border-radius: 8px;
            display: inline-block;
        }

        .vs-text {
            color: #adb5bd;
            font-weight: 400;
            margin: 0 5px;
        }
    </style>
</head>
<body>

<aside id="sidebar">
    <div class="p-4 text-center">
        <i class="bi bi-dribbble text-warning" style="font-size: 2rem;"></i>
        <h5 class="fw-bold mb-0 mt-2">NBSC ADMIN</h5>
    </div>
    <nav class="mt-2">
        <a class="nav-link" href="/ICS_APP_DEV1/dashboard_and_admin/admin.php"><i class="bi bi-speedometer2 me-2"></i> Overview</a>
        <a class="nav-link" href="/ICS_APP_DEV1/userManagement/view_teams.php"><i class="bi bi-people me-2"></i> Teams</a>
        <a class="nav-link active" href="#"><i class="bi bi-trophy me-2"></i> Matches</a>
        <a class="nav-link" href="/ICS_APP_DEV1/userManagement/manage_users.php"><i class="bi bi-person-gear me-2"></i> Settings</a>
        <hr class="mx-3 opacity-25">
        <a class="nav-link text-danger" href="/ICS_APP_DEV1/authentication/logout.php"><i class="bi bi-box-arrow-left me-2"></i> Logout</a>
    </nav>
</aside>

<main id="main-content">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1 small">
                        <li class="breadcrumb-item"><a href="/ICS_APP_DEV1/dashboard_and_admin/admin.php" class="text-muted">Admin</a></li>
                        <li class="breadcrumb-item active text-primary">Match History</li>
                    </ol>
                </nav>
                <h2 class="fw-bold mb-0">Complete Match Schedule</h2>
            </div>
            <a href="/dashboard_and_admin/admin.php" class="btn btn-outline-dark btn-sm rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i> Dashboard
            </a>
        </div>

        <div class="table-container">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Match ID</th>
                            <th>Versus</th>
                            <th>Schedule Date & Time</th>
                            <th>Final Score</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()): 
                            $status_class = "status-" . strtolower($row['status']);
                        ?>
                        <tr>
                            <td class="text-muted fw-bold">#<?= $row['id'] ?></td>
                            <td>
                                <span class="fw-bold"><?= htmlspecialchars($row['team1']) ?></span>
                                <span class="vs-text">vs</span>
                                <span class="fw-bold"><?= htmlspecialchars($row['team2']) ?></span>
                            </td>
                            <td>
                                <div class="small fw-bold text-dark"><?= date('M d, Y', strtotime($row['reservation_date'])) ?></div>
                                <div class="small text-muted"><?= $row['selected_time'] ?></div>
                            </td>
                            <td>
                                <div class="score-box">
                                    <?= $row['home_score'] ?> - <?= $row['away_score'] ?>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="status-badge <?= $status_class ?>">
                                    <?= strtoupper($row['status']) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        
                        <?php if($result->num_rows == 0): ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-calendar-x d-block fs-1 mb-2"></i>
                                No matches found in the schedule.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>