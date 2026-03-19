<?php
session_start();
include "db.php";

if(isset($_POST['login'])){
    $username = $_POST['username'];
    $password = $_POST['password'];

    // 1. Check for the hardcoded Admin credentials first
    if($username === 'admin' && $password === 'password'){
        $_SESSION['username'] = 'admin';
        $_SESSION['role'] = 'admin';
        header("Location: admin.php"); // Create this page for admin tasks
        exit();
    }

    // 2. Otherwise, check the database for regular players
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0){
        $row = $result->fetch_assoc();
        
        // Using password_verify for security on registered accounts
        if(password_verify($password, $row['password'])){
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'] ?? 'player';
            
            header("Location: profile.php?sid=" . urlencode($row['username']));
            exit();
        } else { 
            $error = "Invalid Password"; 
        }
    } else { 
        $error = "User Not Found"; 
    }
    // Record the Login Activity
$log_action = "Logged In";
$log_stmt = $conn->prepare("INSERT INTO user_logs (username, action) VALUES (?, ?)");
$log_stmt->bind_param("ss", $row['username'], $log_action);
$log_stmt->execute();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>HoopMatch | Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #0a2e6e, #123e8c, #0d47a1);
            color: white;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .top-bar { width: 100%; height: 10px; background: #FFD700; }
        .navbar-brand { color: white !important; font-weight: bold; font-size: 24px; text-decoration: none; }
        .login-container {
            margin: auto;
            background: rgba(0,0,0,0.55);
            padding: 50px;
            border-radius: 10px;
            max-width: 420px;
            width: 100%;
            text-align: center;
            backdrop-filter: blur(6px);
            border: 1px solid rgba(255,255,255,0.2);
        }
        .login-container h2 { margin-bottom: 25px; font-weight: bold; text-transform: uppercase; }
        .form-control {
            background: #0a1f4f;
            color: white;
            border: 1px solid #FFD700;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 20px;
        }
        .form-control:focus { background: #0a1f4f; color: white; border-color: white; box-shadow: none; }
        .btn-login {
            background: #FFD700;
            color: black;
            font-weight: bold;
            width: 100%;
            padding: 13px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            transition: 0.3s;
        }
        .btn-login:hover { background: #ffcc00; transform: translateY(-2px); }
        a { color: #FFD700; text-decoration: none; font-weight: bold; }
        .error-msg { color: #ff6b6b; margin-bottom: 15px; font-weight: bold; }
    </style>
</head>
<body>

<div class="top-bar"></div>

<nav class="navbar px-4">
    <a class="navbar-brand" href="index.php"> HoopMatch</a>
</nav>

<div class="login-container">
    <h2>Login</h2>

    <?php if(isset($error)) { echo "<div class='error-msg'>$error</div>"; } ?>

    <form method="POST">
        <input type="text" name="username" class="form-control" placeholder="Student ID or Username" required>
        <input type="password" name="password" class="form-control" placeholder="Password" required>
        <button type="submit" name="login" class="btn-login">Login</button>
    </form>

    <p class="mt-4">
        Don't have an account? <br>
        <a href="register.php">Create Player Account</a>
    </p>
</div>

</body>
</html>