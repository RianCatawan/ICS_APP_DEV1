<?php
session_start();
include(__DIR__ . '/../database&config/db.php');

// Security Check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /authentication/login.php"); 
    exit();
}

// Handle Add User
if(isset($_POST['add_user'])){
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $password, $role);
    
    if($stmt->execute()){
        header("Location: manage_users.php?msg=User Created Successfully");
    } else {
        header("Location: manage_users.php?err=Registration Failed");
    }
    exit();
}

// Fetch all users
$result = $conn->query("SELECT id, username, role, created_at FROM users ORDER BY id ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management | NBSC Admin</title>
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

        /* ── SIDEBAR (Stable Anti-Flicker) ── */
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

        /* ── FORMS & TABLES ── */
        .admin-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            border: none;
            margin-bottom: 30px;
            transform: translateZ(0);
        }

        .form-control, .form-select {
            border: 1px solid #dee2e6;
            padding: 10px 15px;
            border-radius: 8px;
        }

        .form-control:focus {
            border-color: var(--primary-blue);
            box-shadow: none;
        }

        .btn-create {
            background: var(--primary-blue);
            color: white;
            font-weight: 600;
            border-radius: 8px;
            padding: 10px 25px;
        }
        .btn-create:hover { background: #0a2663; color: white; }

        .role-badge {
            font-size: 0.75rem;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 50px;
            text-transform: uppercase;
        }
        .role-admin { background: #fff3e0; color: #ef6c00; }
        .role-user { background: #e3f2fd; color: #1565c0; }

        .delete-link {
            color: #dc3545;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.85rem;
        }
        .delete-link:hover { text-decoration: underline; }
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
        <a class="nav-link" href="/ICS_APP_DEV1/match_system/matches.php"><i class="bi bi-trophy me-2"></i> Matches</a>
        <a class="nav-link active" href="#"><i class="bi bi-person-gear me-2"></i> Settings</a>
        <hr class="mx-3 opacity-25">
        <a class="nav-link text-danger" href="/ICS_APP_DEV1/authentication/logout.php"><i class="bi bi-box-arrow-left me-2"></i> Logout</a>
    </nav>
</aside>

<main id="main-content">
    <div class="container-fluid">
        <div class="mb-5">
            <h2 class="fw-bold mb-0">User Management</h2>
            <p class="text-muted">Create and manage administrative or player accounts.</p>
        </div>

        <div class="admin-card">
            <h5 class="fw-bold mb-4"><i class="bi bi-person-plus me-2"></i>Register New Account</h5>
            <form method="POST" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="small fw-bold text-muted">Username</label>
                    <input type="text" name="username" class="form-control" placeholder="Enter username" required>
                </div>
                <div class="col-md-3">
                    <label class="small fw-bold text-muted">Temporary Password</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
                <div class="col-md-3">
                    <label class="small fw-bold text-muted">Account Role</label>
                    <select name="role" class="form-select" required>
                        <option value="user">Player/User</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" name="add_user" class="btn btn-create w-100">
                        <i class="bi bi-check-lg me-1"></i> Create Account
                    </button>
                </div>
            </form>
        </div>

        <?php if(isset($_GET['msg'])): ?>
            <div class="alert alert-success border-0 shadow-sm mb-4"><?= htmlspecialchars($_GET['msg']) ?></div>
        <?php endif; ?>

        <div class="admin-card">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr class="text-muted small">
                            <th>ID</th>
                            <th>USERNAME</th>
                            <th>ROLE</th>
                            <th>CREATED DATE</th>
                            <th class="text-end">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td class="fw-bold">#<?= $row['id'] ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="bg-light rounded-circle p-2 me-2">
                                        <i class="bi bi-person text-primary"></i>
                                    </div>
                                    <span class="fw-medium"><?= htmlspecialchars($row['username']) ?></span>
                                </div>
                            </td>
                            <td>
                                <span class="role-badge <?= ($row['role'] == 'admin') ? 'role-admin' : 'role-user' ?>">
                                    <?= $row['role'] ?>
                                </span>
                            </td>
                            <td class="text-muted small">
                                <?= date('M d, Y', strtotime($row['created_at'])) ?>
                            </td>
                            <td class="text-end">
                                <?php if($row['username'] !== 'admin'): ?>
                                    <a href="delete_user.php?id=<?= $row['id'] ?>" 
                                       class="delete-link" 
                                       onclick="return confirm('Permanently delete this account?')">
                                        <i class="bi bi-trash3 me-1"></i> Remove
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted small italic text-decoration-none">System Protected</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>