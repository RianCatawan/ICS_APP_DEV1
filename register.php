<?php
include "db.php";

if(isset($_POST['register'])){

    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = "user";

    $sql = "INSERT INTO users (username, password, role)
            VALUES ('$username', '$password', '$role')";

    if($conn->query($sql)){
        // Optional: log registration
        $conn->query("INSERT INTO user_logs (username, action)
                      VALUES ('$username', 'REGISTER')");

        echo "<script>alert('Registered Successfully'); window.location='index.php';</script>";
    } else {
        $error = "Error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>HoopMatch | Register</title>

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

.register-container {
  margin: auto;
  padding: 40px;
  background: #1e293b;
  border-radius: 12px;
  width: 100%;
  max-width: 400px;
  text-align: center;
}

.register-container h2 {
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

.btn-register {
  background: #22c55e;
  color: #020617;
  font-weight: bold;
  padding: 12px 25px;
  border-radius: 10px;
  border: none;
}

.btn-register:hover {
  background: #16a34a;
}

.error-msg {
  color: #f87171;
  margin-bottom: 15px;
}

.back-link {
  color: #38bdf8;
  text-decoration: none;
}
</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg px-4">
  <a class="navbar-brand" href="index.php">🏀 HoopMatch</a>
</nav>

<!-- REGISTER FORM -->
<div class="register-container">
    <h2>Register</h2>

    <?php if(isset($error)) { echo "<div class='error-msg'>$error</div>"; } ?>

    <form method="POST">
        <input type="text" name="username" class="form-control" placeholder="Username" required>
        <input type="password" name="password" class="form-control" placeholder="Password" required>
        <button type="submit" name="register" class="btn-register">Register</button>
    </form>

    <p class="mt-3">
        <a href="index.php" class="back-link">Back to Login</a>
    </p>
</div>

</body>
</html>