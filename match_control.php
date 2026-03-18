<?php
session_start();
include "db.php";
$match_id = $_GET['match_id'] ?? die("No Match ID");

$query = "SELECT mr.*, t1.team_name as home_team, t2.team_name as away_team
          FROM match_requests mr
          JOIN reservations r ON mr.reservation_id = r.id
          JOIN teams t1 ON r.team_id = t1.id
          JOIN teams t2 ON mr.challenger_team_id = t2.id
          WHERE mr.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $match_id);
$stmt->execute();
$match = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Live Score</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #051937; color: white; text-align: center; padding-top: 50px; }
        .score-box { font-size: 5rem; font-weight: bold; color: #FFD700; }
        .card-custom { background: rgba(0,0,0,0.3); border: 1px solid #FFD700; border-radius: 20px; padding: 30px; }
    </style>
</head>
<body>
<div class="container card-custom">
    <h2 class="text-warning mb-4">LIVE SCORING</h2>
    <div class="row align-items-center">
        <div class="col-5">
            <h3><?php echo $match['home_team']; ?></h3>
            <div class="score-box" id="h_disp">0</div>
            <button class="btn btn-outline-light" onclick="adj('h', -1)">-</button>
            <button class="btn btn-warning" onclick="adj('h', 1)">+</button>
        </div>
        <div class="col-2"><h1>VS</h1></div>
        <div class="col-5">
            <h3><?php echo $match['away_team']; ?></h3>
            <div class="score-box" id="a_disp">0</div>
            <button class="btn btn-outline-light" onclick="adj('a', -1)">-</button>
            <button class="btn btn-warning" onclick="adj('a', 1)">+</button>
        </div>
    </div>

    <form action="save_history.php" method="POST" class="mt-5">
        <input type="hidden" name="match_id" value="<?php echo $match_id; ?>">
        <input type="hidden" name="h_score" id="h_val" value="0">
        <input type="hidden" name="a_score" id="a_val" value="0">
        <button type="submit" class="btn btn-success btn-lg px-5">FINISH & SAVE RESULT</button>
    </form>
</div>

<script>
function adj(team, val) {
    let disp = document.getElementById(team + '_disp');
    let hidden = document.getElementById(team + '_val');
    let newScore = parseInt(disp.innerText) + val;
    if(newScore < 0) newScore = 0;
    disp.innerText = newScore;
    hidden.value = newScore;
}
</script>
</body>
</html>