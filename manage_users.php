<?php
session_start();
include "db.php";

if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
    header("Location: login.php"); exit();
}

// Handle Add User (Admin level only)
if(isset($_POST['add_user'])){
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $password, $role);
    $stmt->execute();
    header("Location: manage_users.php"); exit();
}

$result = $conn->query("SELECT id, username, role, created_at FROM users ORDER BY id ASC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Users | HoopMatch</title>
    <style>
        body { background:#0d47a1; font-family:'Segoe UI', sans-serif; color:white; padding:40px; }
        .back-btn { background:#000; color:#FFD700; padding:10px 18px; border-radius:8px; border:2px solid #FFD700; text-decoration:none; font-weight:bold; }
        .table-box { background:rgba(0,0,0,0.4); padding:20px; border-radius:15px; border:1px solid #FFD700; backdrop-filter:blur(10px); }
        table { width:100%; border-collapse:collapse; }
        th { color:#FFD700; border-bottom:2px solid #FFD700; padding:12px; text-align:left; }
        td { padding:12px; border-bottom:1px solid rgba(255,255,255,0.1); }
        .add-user-card { background:rgba(255,255,255,0.1); padding:20px; border-radius:10px; margin-bottom:20px; }
        input, select { background:#0a1f4f; color:white; border:1px solid #FFD700; padding:8px; border-radius:5px; margin-right:10px; }
        button { background:#FFD700; color:black; font-weight:bold; border:none; padding:10px 20px; border-radius:5px; cursor:pointer; }
    </style>
</head>
<body>
    <a href="admin.php" class="back-btn">← Back to Dashboard</a>
    <h2 style="margin-top:40px;">User Management</h2>

    <div class="add-user-card">
        <form method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <select name="role" required>
                <option value="user">Player/User</option>
                <option value="admin">Administrator</option>
            </select>
            <button type="submit" name="add_user">Create Account</button>
        </form>
    </div>

    <div class="table-box">
        <table>
            <tr><th>ID</th><th>Username</th><th>Role</th><th>Actions</th></tr>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td>#<?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['username']) ?></td>
                <td><span style="color:#FFD700"><?= strtoupper($row['role']) ?></span></td>
                <td>
                    <a href="delete_user.php?id=<?= $row['id'] ?>" style="color:#ff6b6b; text-decoration:none;" onclick="return confirm('Delete user account?')">Remove</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
</body>
</html>