<?php
session_start();
include "db.php";

if(!isset($_SESSION['username']) || $_SESSION['role'] != 'admin'){
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard | HoopMatch</title>

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body {
    background-color: #0f172a;
    color: #e5e7eb;
}

.navbar {
    background-color: #020617;
}

.card {
    background-color: #1e293b;
    border: none;
}

.table {
    color: white;
}

.table thead {
    background-color: #334155;
}

.btn-logout {
    background-color: #ef4444;
    border: none;
}
</style>

</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-dark px-4">
    <span class="navbar-brand fw-bold">🏀 HoopMatch Admin</span>
    <a href="logout.php" class="btn btn-logout text-white">Logout</a>
</nav>

<div class="container mt-4">

    <!-- Welcome Card -->
    <div class="card p-4 mb-4">
        <h3>Welcome, <?php echo $_SESSION['username']; ?> 👑</h3>
        <p>Here you can monitor all user login and logout activities.</p>
    </div>

    <!-- Logs Table -->
    <div class="card p-4">
        <h4 class="mb-3">User Activity Logs</h4>

        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Action</th>
                        <th>Date & Time</th>
                    </tr>
                </thead>
                <tbody>

                <?php
                $result = $conn->query("SELECT * FROM user_logs ORDER BY log_time DESC");

                if($result->num_rows > 0){
                    while($row = $result->fetch_assoc()){
                        echo "<tr>
                                <td>{$row['username']}</td>
                                <td>{$row['action']}</td>
                                <td>{$row['log_time']}</td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='3' class='text-center'>No logs found</td></tr>";
                }
                ?>

                </tbody>
            </table>
        </div>

    </div>

</div>

</body>
</html>
