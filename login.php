<?php
session_start();
include "db.php";

if(isset($_POST['login'])){
    $username = $_POST['username'];
    $password = $_POST['password'];

    // 1. Admin Login
    if($username === 'admin' && $password === 'password'){
        $_SESSION['username'] = 'admin';
        $_SESSION['role'] = 'admin';
        header("Location: admin.php");
        exit();
    }

    // 2. Regular Player Login
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0){
        $row = $result->fetch_assoc();
        
        if(password_verify($password, $row['password'])){
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'] ?? 'player';
            
            // Record Login Activity only on success
            $log_action = "Logged In";
            $log_stmt = $conn->prepare("INSERT INTO user_logs (username, action) VALUES (?, ?)");
            $log_stmt->bind_param("ss", $row['username'], $log_action);
            $log_stmt->execute();

            header("Location: profile.php?sid=" . urlencode($row['username']));
            exit();
        } else { 
            $error = "Invalid Password"; 
        }
    } else { 
        $error = "User Not Found"; 
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
        :root {
            --primary-blue: #0d47a1;
            --accent-gold: #FFD700;
            --glass-white: rgba(255, 255, 255, 0.92);
        }

        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            /* Same background as your home page */
            background-image: url('Covered Court.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            background: var(--glass-white);
            backdrop-filter: blur(12px);
            padding: 40px;
            border-radius: 20px;
            max-width: 400px;
            width: 90%;
            text-align: center;
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
            border: 1px solid rgba(255,255,255,0.4);
        }

        .brand-icon {
            font-size: 3rem;
            color: var(--accent-gold);
            margin-bottom: 10px;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
        }

        h2 { 
            color: var(--primary-blue); 
            font-weight: 800; 
            text-transform: uppercase; 
            letter-spacing: 1px;
            margin-bottom: 30px;
        }

        .form-control {
            background: rgba(255, 255, 255, 0.8);
            border: 2px solid #ddd;
            border-radius: 12px;
            padding: 12px 15px;
            margin-bottom: 20px;
            transition: 0.3s;
            color: #333;
        }

        .form-control:focus {
            border-color: var(--primary-blue);
            box-shadow: none;
            background: #fff;
        }

        .btn-login {
            background: var(--primary-blue);
            color: white;
            font-weight: 700;
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            text-transform: uppercase;
            transition: 0.3s;
            box-shadow: 0 4px 15px rgba(13, 71, 161, 0.3);
        }

        .btn-login:hover {
            background: #0a3d8d;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(13, 71, 161, 0.4);
        }

        .error-msg {
            background: #ffebee;
            color: #c62828;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            border: 1px solid #ffcdd2;
        }

        .reg-link {
            color: var(--primary-blue);
            text-decoration: none;
            font-weight: 700;
        }

        .reg-link:hover { text-decoration: underline; }

        .back-home {
            position: absolute;
            top: 20px;
            left: 20px;
            background: var(--glass-white);
            padding: 8px 15px;
            border-radius: 10px;
            color: var(--primary-blue);
            text-decoration: none;
            font-weight: bold;
            font-size: 0.9rem;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

<a href="index.php" class="back-home"><i class="bi bi-arrow-left"></i> Back to Home</a>

<div class="login-card">
    <div class="brand-icon">
    </div>
    <h2>Login</h2>

    <?php if(isset($error)): ?>
        <div class='error-msg'><i class="bi bi-exclamation-circle"></i> <?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="text-start mb-1 small fw-bold text-muted ml-2">STUDENT ID / USERNAME</div>
        <input type="text" name="username" class="form-control" placeholder="e.g. 2024-0001" required>
        
        <div class="text-start mb-1 small fw-bold text-muted ml-2">PASSWORD</div>
        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
        
        <button type="submit" name="login" class="btn-login">Sign In</button>
    </form>

    <div class="mt-4 small text-muted">
        Don't have an account yet? <br>
        <a href="register.php" class="reg-link">Create Player Account</a>
    </div>
</div>

</body>
</html>