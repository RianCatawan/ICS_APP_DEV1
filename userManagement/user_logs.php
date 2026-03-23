<?php
session_start();
include "db.php";

// Admin Check
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
    header("Location: login.php"); exit();
}

// Handle Delete Log
if(isset($_GET['delete'])){
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM user_logs WHERE id = $id");
    header("Location: user_logs.php"); exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>User Logs | HoopMatch</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #0d47a1; color: white; padding: 40px; font-family: 'Segoe UI', sans-serif; }
        .back-btn { background:#000; color:#FFD700; padding:10px 18px; border-radius:8px; border:2px solid #FFD700; text-decoration:none; font-weight:bold; transition: 0.3s; }
        .back-btn:hover { background: #FFD700; color: #000; }
        .log-container { background: rgba(0,0,0,0.4); border: 1px solid #FFD700; border-radius:15px; padding:30px; margin-top:40px; backdrop-filter: blur(10px);}
        .table { color: white; border-color: rgba(255, 215, 0, 0.2); }
        .table thead th { color: #FFD700; border-bottom: 2px solid #FFD700; text-transform: uppercase; letter-spacing: 1px; }
        .badge-login { background: #28a745; color: white; }
        .badge-logout { background: #dc3545; color: white; }
    </style>
</head>
<body>
    <a href="admin.php" class="back-btn">← Back to Dashboard</a>
    
    <div class="log-container">
        <h3><i class="bi bi-journal-text text-warning"></i> System Activity Logs</h3>
        <p class="text-white-50">Tracking user access and session activity.</p>
        
        <table class="table table-hover mt-4">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Action Performed</th>
                    <th>Timestamp</th>
                    <th class="text-center">Manage</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Fetching directly from user_logs
                $sql = "SELECT * FROM user_logs ORDER BY login_time DESC";
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()): 
                        $badgeClass = ($row['action'] == 'Logged In') ? 'badge-login' : 'badge-logout';
                    ?>
                    <tr>
                        <td class="fw-bold"><?= htmlspecialchars($row['username']) ?></td>
                        <td>
                            <span class="badge <?= $badgeClass ?>"><?= strtoupper($row['action']) ?></span>
                        </td>
                        <td class="text-white-50"><?= date('M d, Y - h:i A', strtotime($row['login_time'])) ?></td>
                        <td class="text-center">
                            <a href="user_logs.php?delete=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this log entry?')">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; 
                } else {
                    echo "<tr><td colspan='4' class='text-center text-white-50'>No activity logs found.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>