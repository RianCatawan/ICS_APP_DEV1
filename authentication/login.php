<?php
session_start();
include(__DIR__ . '/../database&config/db.php');

if(isset($_POST['login'])){
    $username = $_POST['username'];
    $password = $_POST['password'];

    // ── HARDCODED ADMIN CHECK ──
    if($username === 'admin' && $password === 'password'){
        $_SESSION['username'] = 'admin';
        $_SESSION['role'] = 'admin'; // Set role as admin
        header("Location: /dashboard_and_admin/admin.php");
        exit();
    }

    // ── REGULAR USER CHECK ──
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0){
        $row = $result->fetch_assoc();
        if(password_verify($password, $row['password'])){
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'] ?? 'player'; // Default role is player

            $log_stmt = $conn->prepare("INSERT INTO user_logs (username, action) VALUES (?, 'Logged In')");
            $log_stmt->bind_param("s", $row['username']);
            $log_stmt->execute();

            header("Location: /userManagement/profile.php?sid=" . urlencode($row['username']));
            exit();
        } else {
            $error = "Invalid password. Please try again.";
        }
    } else {
        $error = "No account found with that username.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | NBSC Basketball</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800&family=DM+Sans:wght@400;500;700&display=swap');

        :root {
            --navy: #0D2F6E;
            --navy-deep: #071A42;
            --amber: #F5A623;
            --white: #FFFFFF;
            --border: #C8DCEF;
            --radius-lg: 18px;
            --radius-pill: 9999px;
        }

        /* ── ONE PAGE FIX ── */
        html, body { 
            height: 100vh; 
            margin: 0; 
            overflow: hidden; 
            background: linear-gradient(160deg, #daeeff 0%, #eef5fb 100%);
            font-family: 'DM Sans', sans-serif;
            display: flex;
            flex-direction: column;
        }

        /* ── NAVBAR ── */
        .navbar {
            background: var(--navy-deep) !important;
            border-radius: var(--radius-lg);
            padding: 12px 25px;
            margin: 15px;
            border-bottom: 3px solid var(--amber);
            flex-shrink: 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar-brand {
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            color: var(--white) !important;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-outline-custom {
            color: var(--amber);
            border: 2px solid var(--amber);
            padding: 6px 15px;
            border-radius: var(--radius-pill);
            text-decoration: none;
            font-family: 'Outfit';
            font-weight: 700;
            font-size: 0.8rem;
            transition: 0.3s;
        }

        .btn-outline-custom:hover {
            background: var(--amber);
            color: var(--navy-deep);
        }

        /* ── LOGIN CARD ── */
        .login-wrapper {
            flex-grow: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-card {
            background: var(--white);
            border: 3px solid var(--navy-deep);
            border-radius: var(--radius-lg);
            padding: 35px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            position: relative;
        }

        .login-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; height: 5px;
            background: var(--amber);
        }

        .login-icon {
            width: 55px;
            height: 55px;
            background: var(--navy-deep);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            border: 3px solid var(--amber);
            color: var(--amber);
            font-size: 1.5rem;
        }

        .login-card h2 {
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            text-align: center;
            color: var(--navy);
            text-transform: uppercase;
        }

        /* ── INPUTS & BUTTONS ── */
        .field-label {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--navy);
            margin-bottom: 5px;
            display: block;
        }

        .input-group-custom {
            position: relative;
            margin-bottom: 15px;
        }

        .input-group-custom i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--navy);
        }

        .input-group-custom input {
            width: 100%;
            padding: 10px 10px 10px 35px;
            border: 2px solid var(--border);
            border-radius: 10px;
            outline: none;
        }

        .input-group-custom input:focus {
            border-color: var(--navy);
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            background: var(--navy-deep);
            color: var(--amber);
            font-family: 'Outfit';
            font-weight: 700;
            border: none;
            border-radius: var(--radius-pill);
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: 0.3s;
        }

        .btn-login:hover {
            background: var(--amber);
            color: var(--navy-deep);
        }

        .divider {
            text-align: center;
            margin: 15px 0;
            font-size: 0.7rem;
            color: #888;
            text-transform: uppercase;
            position: relative;
        }

        .error-msg {
            background: #FEE2E2;
            color: #B91C1C;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            border-left: 4px solid #B91C1C;
        }
    </style>
</head>
<body>

<nav class="navbar">
    <a class="navbar-brand" href="/index.php">
        <i class="bi bi-dribbble"></i> NBSC MATCH MAKER
    </a>
    <div class="d-flex gap-2 align-items-center">
        <a href="/dashboard_and_admin/index.php" class="btn-outline-custom">BACK TO HOME</a>
        <a href="/authentication/register.php" class="btn btn-sm btn-light fw-bold rounded-pill px-3">REGISTER</a>
    </div>
</nav>

<div class="login-wrapper">
    <div class="login-card">
        <div class="login-icon"><i class="bi bi-shield-lock-fill"></i></div>
        <h2>Welcome Back</h2>
        <p class="text-center text-muted small mb-4">Sign in to your account</p>

        <?php if(isset($error)): ?>
            <div class="error-msg"><i class="bi bi-exclamation-circle"></i> <?= $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <label class="field-label">Username / Student ID</label>
            <div class="input-group-custom">
                <i class="bi bi-person"></i>
                <input type="text" name="username" placeholder="Enter ID or 'admin'" required>
            </div>

            <label class="field-label">Password</label>
            <div class="input-group-custom">
                <i class="bi bi-lock"></i>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>

            <button type="submit" name="login" class="btn-login">SIGN IN</button>
        </form>

        <div class="divider">OR</div>

        <a href="/authentication/register.php" class="btn btn-outline-dark w-100 rounded-pill fw-bold btn-sm py-2">
            CREATE PLAYER ACCOUNT
        </a>
    </div>
</div>

<footer class="text-center p-3 text-muted small flex-shrink-0">
    NBSC Match Maker &mdash; Basketball Court System &copy; 2026
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>