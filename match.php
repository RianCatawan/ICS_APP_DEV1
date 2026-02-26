<?php
session_start();
include "db.php";

/* ==============================
   SAFE SESSION CHECK
   (No redirect loop)
==============================*/

// If user not logged in, create temporary session
if(!isset($_SESSION['username'])){
    $_SESSION['username'] = "Guest";
    $_SESSION['user_id'] = 0;
}

$user_id = $_SESSION['user_id'];

/* ==============================
   SAVE GAME TYPE
==============================*/
if(isset($_POST['startMatch'])){

    $game_type = $_POST['gameType'];

    if(!empty($game_type) && $user_id != 0){

        $stmt = $conn->prepare("INSERT INTO game_types (user_id, game_type) VALUES (?, ?)");
        $stmt->bind_param("is", $user_id, $game_type);
        $stmt->execute();
        $stmt->close();
    }

    // Safe redirect (same folder)
    header("Location: match-begin.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>HoopMatch | Match</title>
<style>
body{
  background:#0f172a;
  color:#e5e7eb;
  font-family:Arial;
  display:flex;
  justify-content:center;
  align-items:center;
  height:100vh;
  flex-direction:column;
}
.match-container{
  display:flex;
  align-items:center;
  gap:40px;
  margin-bottom:30px;
}
.team-box{
  background:#020617;
  border-radius:20px;
  width:250px;
  height:250px;
  display:flex;
  flex-direction:column;
  justify-content:center;
  align-items:center;
  font-size:28px;
  font-weight:bold;
  color:#38bdf8;
}
.vs-label{
  font-size:48px;
  font-weight:bold;
  color:#22c55e;
}
select{
  padding:10px;
  border-radius:12px;
  border:2px solid #38bdf8;
  background:#020617;
  color:#e5e7eb;
  margin-top:15px;
}
button{
  padding:14px 28px;
  border:none;
  border-radius:12px;
  background:#38bdf8;
  color:#020617;
  font-weight:bold;
  font-size:18px;
  cursor:pointer;
  margin-top:20px;
  display:none;
}
button:hover{
  background:#22c55e;
  color:#fff;
}
#countdown{
  margin-top:20px;
  font-size:48px;
  font-weight:bold;
  color:#22c55e;
}
</style>
</head>
<body>

<h1>HoopMatch | Match</h1>
<p>Welcome, <?php echo $_SESSION['username']; ?></p>

<form method="POST">

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
    <select name="gameType" id="player2Select" required>
      <option value="">Select Game Type</option>
      <option value="1v1">1v1</option>
      <option value="2v2">2v2</option>
      <option value="3v3">3v3</option>
      <option value="5v5">5v5</option>
    </select>
  </div>

</div>

<button type="submit" name="startMatch" id="startMatchBtn">
Start Match
</button>

</form>

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

startBtn.addEventListener('click', function(e){

  e.preventDefault();

  let timeLeft = 3;
  countdownEl.textContent = timeLeft;

  const countdown = setInterval(()=>{
    timeLeft--;
    countdownEl.textContent = timeLeft;

    if(timeLeft === 0){
      clearInterval(countdown);
      countdownEl.textContent = "GO!";
      setTimeout(()=>{
        e.target.closest("form").submit();
      }, 1000);
    }
  },1000);

});
</script>

</body>
</html>