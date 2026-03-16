<?php
session_start();
include "db.php";

// Simple security check
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Stats Queries (Fixed table names)
$total_teams = $conn->query("SELECT COUNT(*) as total FROM teams")->fetch_assoc()['total'];
$total_players = $conn->query("SELECT COUNT(*) as total FROM players")->fetch_assoc()['total'];
$active_matches = $conn->query("SELECT COUNT(*) as total FROM match_requests WHERE status = 'accepted'")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Admin Dashboard | HoopMatch</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #0d47a1; color: white; font-family: 'Segoe UI', sans-serif; }
        .sidebar { background: rgba(0,0,0,0.3); height: 100vh; padding: 20px; border-right: 2px solid #FFD700; }
        .stat-card { background: rgba(255, 255, 255, 0.1); border-left: 5px solid #FFD700; padding: 20px; border-radius: 10px; }
        .table-container { background: rgba(0,0,0,0.2); padding: 20px; border-radius: 15px; border: 1px solid rgba(255,215,0,0.3); }
        .nav-link { color: white; margin-bottom: 10px; border-radius: 5px; }
        .nav-link:hover, .nav-link.active { background: #FFD700; color: #0d47a1; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
       <div class="col-md-2 sidebar">
    <h4 class="fw-bold text-warning mb-4">ADMIN PANEL</h4>
    <nav class="nav flex-column">
        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'admin.php') ? 'active' : ''; ?>" href="admin.php">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        
        <a class="nav-link" href="manage_users.php">
            <i class="bi bi-person-gear"></i> Manage Users
        </a>
        
        <a class="nav-link" href="matches.php">
            <i class="bi bi-trophy"></i> View Matches
        </a>
        
        <a class="nav-link" href="user_logs.php">
            <i class="bi bi-journal-text"></i> User Logs
        </a>

        <hr style="border-color: rgba(255,215,0,0.3);">

        <a href="view_teams.php" class="btn btn-warning fw-bold w-100 mb-3">
            <i class="bi bi-people-fill"></i> View Teams
        </a>

        <a class="nav-link text-danger mt-auto" href="index.php">
            <i class="bi bi-box-arrow-left"></i> Logout
        </a>
    </nav>
</div>

        <div class="col-md-10 p-5">
            <h2 class="mb-4">Welcome, System Administrator</h2>
            
            <div class="row mb-5">
                <div class="col-md-4">
                    <div class="stat-card">
                        <small class="text-white-50">TOTAL TEAMS</small>
                        <h1 class="fw-bold text-warning"><?php echo $total_teams; ?></h1>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <small class="text-white-50">TOTAL PLAYERS</small>
                        <h1 class="fw-bold text-warning"><?php echo $total_players; ?></h1>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <small class="text-white-50">LIVE BATTLES</small>
                        <h1 class="fw-bold text-warning"><?php echo $active_matches; ?></h1>
                    </div>
                </div>
            </div>

            <div class="table-container">
                <h4 class="text-warning mb-3">RECENT MATCH REQUESTS</h4>
                <table class="table table-dark table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Home Team</th>
                            <th>Challenger</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $matches = $conn->query("SELECT mr.*, t1.team_name as home, t2.team_name as away 
                                               FROM match_requests mr
                                               JOIN reservations r ON mr.reservation_id = r.id
                                               JOIN teams t1 ON r.team_id = t1.id
                                               JOIN teams t2 ON mr.challenger_team_id = t2.id
                                               ORDER BY mr.id DESC LIMIT 10");
                        while($row = $matches->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $row['id']; ?></td>
                            <td><?php echo $row['home']; ?></td>
                            <td><?php echo $row['away']; ?></td>
                            <td><span class="badge bg-info text-dark"><?php echo strtoupper($row['status']); ?></span></td>
                            <td>
                                <a href="confirmation_match.php?id=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm">VIEW</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</body>
</html>