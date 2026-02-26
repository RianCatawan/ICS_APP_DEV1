<?php
session_start();
include "db.php";

if(!isset($_SESSION['username'])){
    header("Location: start-match.php");
    exit;
}

$user = $_SESSION['username'];
$result = $conn->query("SELECT id FROM users WHERE username='$user'");
$row = $result->fetch_assoc();
$user_id = $row['id'];

if(isset($_POST['gameType'])){
    $game_type = $_POST['gameType'];
    $stmt = $conn->prepare("INSERT INTO game_types (user_id, game_type) VALUES (?, ?)");
    $stmt->bind_param("is", $user_id, $game_type);
    $stmt->execute();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>HoopMatch | Match</title>
<style>
body{background:#0f172a;color:#e5e7eb;font-family:Arial; display:flex; justify-content:center; align-items:center; height:100vh; flex-direction:column;}
.match-container{display:flex;align-items:center;gap:40px;margin-bottom:30px;}
.team-box{background:#020617;border-radius:20px;width:250px;height:250px;display:flex;flex-direction:column;justify-content:center;align-items:center;font-size:28px;font-weight:bold;color:#38bdf8;box-shadow:0 0 20px rgba(56,189,248,0.3);}
.team-box:hover{transform:scale(1.05);box-shadow:0 0 25px rgba(34,197,94,0.7);}
.vs-label{font-size:48px;font-weight:bold;color:#22c55e;text-shadow:0 0 20px #22c55e,0 0 40px #38bdf8;}
select{padding:10px 12px;border-radius:12px;border:2px solid #38bdf8;background:#020617;color:#e5e7eb;font-weight:bold;margin-top:15px;}
select:focus{border-color:#22c55e;box-shadow:0 0 10px rgba(34,197,94,0.5);}
button{padding:14px 28px;border:none;border-radius:12px;background:#38bdf8;color:#020617;font-weight:bold;font-size:18px;cursor:pointer;margin-top:20px; display:none;}
button:hover{background:#22c55e;color:#fff;}
#countdown{margin-top:20px;font-size:48px;font-weight:bold;color:#22c55e;text-shadow:0 0 20px #22c55e,0 0 40px #38bdf8;}
</style>
</head>
<body>

<h1>HoopMatch | Match</h1>

<div class="match-container">
  <div class="team-box">
    Player 1
    <select id="player1Select">
      <option value="">Select Game Type</option>
      <option value="1v1">1v1</option>
      <option value="2v2">2v2</option>
      <option value="3v3">3v3</option>
      <option value="5v5">5v5</option>
    </select>
  </div>

  <div class="vs-label">VS</div>

  <div class="team-box">
    Player 2
    <select id="player2Select">
      <option value="">Select Game Type</option>
      <option value="1v1">1v1</option>
      <option value="2v2">2v2</option>
      <option value="3v3">3v3</option>
      <option value="5v5">5v5</option>
    </select>
  </div>
</div>

<button id="startMatchBtn">Start Match</button>
<div id="countdown"></div>

<script>
const p1Select = document.getElementById('player1Select');
const p2Select = document.getElementById('player2Select');
const startBtn = document.getElementById('startMatchBtn');
const countdownEl = document.getElementById('countdown');

function checkMatch(){
  if(p1Select.value && p1Select.value === p2Select.value){
    startBtn.style.display="block";
  } else {
    startBtn.style.display="none";
  }
}

p1Select.addEventListener('change', checkMatch);
p2Select.addEventListener('change', checkMatch);

startBtn.addEventListener('click', ()=>{
  let timeLeft=3;
  countdownEl.textContent=timeLeft;
  startBtn.style.display="none";

  const countdown = setInterval(()=>{
    timeLeft--;
    countdownEl.textContent=timeLeft;
    if(timeLeft===0){
      clearInterval(countdown);
      countdownEl.textContent="GO!";
      alert(`Match Started! Game Type: ${p1Select.value}`);
    }
  },1000);
});
</script>
</body>
</html>