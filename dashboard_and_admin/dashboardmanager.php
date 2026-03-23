<?php
session_start();
include "db.php";

// 1. Security Check (Adjust 'role' or 'username' based on your session logic)
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// 2. Fetch Stats for Dashboard
$team_count = $conn->query("SELECT COUNT(*) as total FROM teams")->fetch_assoc()['total'] ?? 0;
$match_count = $conn->query("SELECT COUNT(*) as total FROM match_requests WHERE status='pending'")->fetch_assoc()['total'] ?? 0;

// 3. Fetch Recent Logs
$log_query = "SELECT * FROM user_logs ORDER BY login_time DESC LIMIT 10";
$user_logs = $conn->query($log_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Manager | NBSC Basketball</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        /* WHITE THEME STYLING */
        body { 
            background-color: #f8f9fa; 
            color: #212529; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 30px;
        }

        .admin-card {
            background: #ffffff;
            border: none;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            height: 100%;
            transition: transform 0.2s;
        }

        .admin-card:hover {
            transform: translateY(-5px);
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 800;
            color: #0d47a1; /* Dark Blue for contrast on white */
        }

        .task-item {
            background: #f1f3f5;
            border-left: 5px solid #0d47a1;
            margin-bottom: 15px;
            padding: 15px;
            border-radius: 0 10px 10px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn-main {
            background-color: #0d47a1;
            color: white;
            font-weight: 600;
            border-radius: 8px;
            transition: 0.3s;
        }

        .btn-main:hover {
            background-color: #08306b;
            color: #FFD700;
        }

        /* LOG TABLE STYLING */
        .table-container {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            margin-top: 40px;
        }

        .table thead th {
            background-color: #0d47a1;
            color: white !important;
            border: none;
        }

        .table tbody td {
            vertical-align: middle;
            color: #333 !important; /* Forces text to be visible on white */
        }

        .badge-login {
            background-color: #28a745;
            color: white;
            padding: 5px 12px;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h1 class="fw-bold mb-0 text-dark">
                <i class="bi bi-person-badge-fill text-primary"></i> Admin Manager
            </h1>
            <p class="text-muted">NBSC Basketball Management System</p>
        </div>
        <a href="admin.php" class="btn btn-outline-danger px-4 fw-bold">Back</a>
    </div>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="admin-card text-center">
                <h6 class="text-uppercase fw-bold text-muted">Total Teams</h6>
                <div class="stat-number"><?php echo $team_count; ?></div>
                <a href="view_teams.php" class="btn btn-main mt-3 w-100">Manage Teams</a>
            </div>
        </div>

        <div class="col-md-4">
            <div class="admin-card text-center">
                <h6 class="text-uppercase fw-bold text-muted">Pending Matches</h6>
                <div class="stat-number"><?php echo $match_count; ?></div>
                <a href="view_matches.php" class="btn btn-main mt-3 w-100">Review Requests</a>
            </div>
        </div>

        <div class="col-md-4">
            <div class="admin-card">
                <h5 class="mb-4 fw-bold"><i class="bi bi-list-task"></i> Task List</h5>
                
                <div class="task-item">
                    <div>
                        <div class="fw-bold">User Access</div>
                        <small class="text-muted">Manage accounts</small>
                    </div>
                    <a href="manage_users.php" class="btn btn-sm btn-main"><i class="bi bi-people"></i></a>
                </div>

                <div class="task-item">
                    <div>
                        <div class="fw-bold">Maintenance</div>
                        <small class="text-muted">Clear old cache</small>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-gear"></i></button>
                </div>
            </div>
        </div>
    </div>

    <div class="table-container">
        <h4 class="mb-4 fw-bold text-dark"><i class="bi bi-clock-history"></i> Recent Activity Logs</h4>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Student ID</th>
                        <th>Action</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if ($user_logs && $user_logs->num_rows > 0):
                        while($log = $user_logs->fetch_assoc()): 
                            // FIX: Checking for multiple possible key names to prevent 'Undefined array key'
                            $display_id = $log['student_id'] ?? $log['username'] ?? $log['user_id'] ?? 'Unknown';
                    ?>
                    <tr>
                        <td class="fw-bold"><?php echo htmlspecialchars($display_id); ?></td>
                        <td><span class="badge badge-login">LOGIN</span></td>
                        <td><?php echo date('M d, Y | h:i A', strtotime($log['login_time'])); ?></td>
                    </tr>
                    <?php 
                        endwhile; 
                    else:
                    ?>
                    <tr>
                        <td colspan="3" class="text-center py-4 text-muted">No logs recorded yet.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>