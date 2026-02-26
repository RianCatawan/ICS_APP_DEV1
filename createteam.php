<?php
session_start();
include "db.php";

/* ===============================
   CHECK IF USER LOGGED IN
================================*/
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

/* ===============================
   HANDLE TEAM CREATION
================================*/
if (isset($_POST['createTeam'])) {

    $team_name = trim($_POST['teamName']);
    $team_captain = trim($_POST['teamCaptain']);

    if (!empty($team_name) && !empty($team_captain)) {

        $stmt = $conn->prepare("INSERT INTO teams (user_id, team_name, team_captain) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $team_name, $team_captain);
        $stmt->execute();
        $stmt->close();

        // Redirect AFTER saving
        header("Location: match.php");
        exit();
    } else {
        $error = "Please fill all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>HoopMatch | Create Team</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body {
  margin: 0;
  padding: 40px;
  font-family: Arial;
  background: #0f172a;
  color: #e5e7eb;
  display: flex;
  flex-direction: column;
  align-items: center;
  min-height: 100vh;
}

h1 {
  color: #38bdf8;
  margin-bottom: 30px;
}

.team-card {
  background: #020617;
  border-radius: 16px;
  padding: 25px 30px;
  width: 400px;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

input {
  padding: 12px;
  border-radius: 12px;
  border: 2px solid #38bdf8;
  background: #0f172a;
  color: #e5e7eb;
  width: 100%;
}

button {
  padding: 14px;
  border: none;
  border-radius: 12px;
  font-weight: bold;
  background: #38bdf8;
  color: #020617;
  cursor: pointer;
}

button:hover {
  background: #22c55e;
  color: #ffffff;
}

.error {
  color: #f87171;
  font-weight: bold;
}
</style>
</head>
<body>

<h1>Create Team</h1>
<p>Welcome, <?php echo $_SESSION['username']; ?></p>

<div class="team-card">

  <?php if(isset($error)) echo "<div class='error'>$error</div>"; ?>

  <form method="POST">
    <label>Team Name</label>
    <input type="text" name="teamName" required>

    <label>Team Captain</label>
    <input type="text" name="teamCaptain" required>

    <button  type="submit" name="createTeam">Create Team</button>
  </form>

</div>

</body>
</html>