<?php
session_start();
include "db.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>HoopMatch | Home</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<style>
* { margin: 0; padding: 0; box-sizing: border-box; }

body {
  font-family: Arial, sans-serif;
  background: #0f172a;
  color: #e5e7eb;
  min-height: 100vh;
}

nav {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 40px;
  background: #0f172a;
}

.navbar-brand {
  color: #38bdf8 !important;
  font-weight: bold;
}

.navbar .btn-logout {
  background: #ef4444;
  color: #fff;
  border-radius: 8px;
  border: none;
  padding: 10px 18px;
  font-weight: bold;
  cursor: pointer;
}

.hero {
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  text-align: center;
  height: 100%;
  padding: 100px 20px;
}

.hero h2 {
  font-size: 2.5rem;
  margin-bottom: 15px;
}

.hero p {
  max-width: 600px;
  font-size: 1.1rem;
  margin-bottom: 30px;
  color: #cbd5f5;
}

.hero button {
  padding: 14px 30px;
  font-size: 1.1rem;
  border-radius: 10px;
  border: none;
  background: #22c55e;
  color: #020617;
  cursor: pointer;
  font-weight: bold;
}
</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark px-4">
  <!-- Brand -->
  <a class="navbar-brand fw-bold" href="index.php">HoopMatch</a>

  <!-- Right side: Logout if logged in -->
  <div class="ms-auto">
    <?php if(isset($_SESSION['username'])): ?>
      <span class="me-3">welcome, <?php echo $_SESSION['username']; ?>!</span>
      <a href="logout.php" class="btn btn-logout">Logout</a>
    <?php else: ?>
      <a href="index.php" class="btn btn-main">Login</a>
      <a href="register.php" class="btn btn-admin">Register</a>
    <?php endif; ?>
  </div>
</nav>

<!-- HERO SECTION -->
<section class="hero">
  <h2>Find Your Next Basketball Game</h2>

  <a href="usertype.php">
    <button>Start Matching</button>
  </a>
</section>

</body>
</html>