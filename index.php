<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>HoopMatch | Home</title>

 <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
  
<style>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

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
 background: #0f172a;}

nav h1 {
  color: #38bdf8;
}

nav a button {
  margin-left: 10px;
  padding: 10px 18px;
  border-radius: 8px;
  border: none;
  cursor: pointer;
  font-weight: bold;
}

.btn-main {
  background: #22c55e;
  color: #020617;
}

.btn-admin {
  background: #38bdf8;
  color: #020617;
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
<nav class="navbar navbar-expand-lg navbar-dark bg-dark px-4">
  <!-- Brand -->
  <a class="navbar-brand fw-bold" href="index.php">🏀 HoopMatch</a>

  <!-- Mobile toggle -->
  <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar"
    aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>

  <!-- Nav links -->
  <div class="collapse navbar-collapse" id="mainNavbar">
    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
     

    </ul>

    <!-- Buttons -->
    <div class="d-flex gap-2">
      <a href="login.php">
        <button class="btn btn-outline-light">Login</button>
      </a>
      <a href="admin.php">
        <button class="btn btn-warning">Admin</button>
      </a>
    </div>
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
