<?php
session_start();
include "db.php";

if(isset($_POST['login'])){

    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username='$username'";
    $result = $conn->query($sql);

    if($result->num_rows > 0){

        $row = $result->fetch_assoc();

        if(password_verify($password, $row['password'])){

            $_SESSION['username'] = $username;
            $_SESSION['role'] = $row['role'];

            // Insert Login Log
            $conn->query("INSERT INTO suser_logs (username, action)
                          VALUES ('$username', 'LOGIN')");

            if($row['role'] == 'admin'){
                header("Location: admin.php");
            } else {
                header("Location: match-start.php");
            }

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
<title>HoopMatch | Login</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<style>
body {
  font-family: Arial, sans-serif;
  background: #0f172a;
  color: #e5e7eb;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

.navbar {
  background: #0f172a;
}

.navbar-brand {
  color: #38bdf8 !important;
  font-weight: bold;
}

.login-container {
  margin: auto;
  padding: 40px;
  background: #1e293b;
  border-radius: 12px;
  width: 100%;
  max-width: 400px;
  text-align: center;
}

.login-container h2 {
  margin-bottom: 25px;
  color: #38bdf8;
}

.form-control {
  background: #0f172a;
  color: #e5e7eb;
  border: 1px solid #38bdf8;
  border-radius: 8px;
  margin-bottom: 20px;
}

.form-control::placeholder {
  color: #cbd5f5;
}

.btn-login {
  background: #22c55e;
  color: #020617;
  font-weight: bold;
  padding: 12px 25px;
  border-radius: 10px;
  border: none;
}

.btn-login:hover {
  background: #16a34a;
}

.error-msg {
  color: #f87171;
  margin-bottom: 15px;
}
</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg px-4">
  <a class="navbar-brand" href="index.php">🏀 HoopMatch</a>
</nav>

<!-- LOGIN FORM -->
<div class="login-container">
    <h2>Login</h2>

    <?php if(isset($error)) { echo "<div class='error-msg'>$error</div>"; } ?>

    <form method="POST">
        <input type="text" name="username" class="form-control" placeholder="Username" required>
        <input type="password" name="password" class="form-control" placeholder="Password" required>
        <button type="submit" name="login" class="btn-login">Login</button>
    </form>

    <p class="mt-3">
        <a href="register.php" style="color:#38bdf8;">Create Account</a>
    </p>
</div>

</body>
</html>
