<?php
session_start();
include(__DIR__ . '/../database_config/db.php');

$match_id = $_POST['match_id'] ?? $_GET['match_id'] ?? null;
if (!$match_id) die("No match selected.");

// Fetch match info + final_status + date
$query = "SELECT mr.*, t1.team_name AS home_team, t2.team_name AS away_team, r.reservation_date, r.team_id AS home_id, mr.challenger_team_id AS away_id
          FROM match_requests mr
          JOIN reservations r ON mr.reservation_id = r.id
          JOIN teams t1 ON r.team_id = t1.id
          JOIN teams t2 ON mr.challenger_team_id = t2.id
          WHERE mr.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $match_id);
$stmt->execute();
$match = $stmt->get_result()->fetch_assoc();

if (!$match) die("Match not found.");

// --- SECURITY CHECK ---
$today = date('Y-m-d');
$match_date = date('Y-m-d', strtotime($match['reservation_date']));

if ($match['final_status'] === 'confirmed') {
    die("<script>alert('This match is already finished.'); window.location.href='/ICS_APP_DEV1/match_system/upcoming_reservation';</script>");
}
if ($today > $match_date) {
    die("<script>alert('Match time has expired.'); window.location.href='upcoming_reservation.php';</script>");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Live Scoring | NBSC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@900&family=Plus+Jakarta+Sans:wght@700&display=swap');
        body { background: #F4F7FA; color: #0A192F; font-family: 'Plus Jakarta Sans', sans-serif; }
        .score-card { background: white; border: 3px solid #0A192F; border-radius: 20px; padding: 40px; margin-top: 50px; }
        .score-box { font-family: 'Outfit'; font-size: 8rem; font-weight: 900; }
        .btn-adj { width: 60px; height: 60px; font-size: 1.5rem; font-weight: bold; border-radius: 12px; }
    </style>
</head>
<body>

<div class="container text-center">
    <div class="score-card shadow">
        <div class="row align-items-center">
            <div class="col-md-5">
                <h3 class="fw-bold"><?= strtoupper($match['home_team']) ?></h3>
                <div class="score-box" id="h_disp">0</div>
                <button class="btn btn-outline-secondary btn-adj" onclick="adj('h', -1)">-</button>
                <button class="btn btn-warning btn-adj ms-2" onclick="adj('h', 1)">+</button>
            </div>
            <div class="col-md-2"><h1 class="text-warning fw-900">VS</h1></div>
            <div class="col-md-5">
                <h3 class="fw-bold"><?= strtoupper($match['away_team']) ?></h3>
                <div class="score-box" id="a_disp">0</div>
                <button class="btn btn-outline-secondary btn-adj" onclick="adj('a', -1)">-</button>
                <button class="btn btn-warning btn-adj ms-2" onclick="adj('a', 1)">+</button>
            </div>
        </div>

        <form action="/ICS_APP_DEV1/match_system/save_result.php" method="POST" class="mt-5" onsubmit="return confirm('Finalize this score? This cannot be undone.');">
            <input type="hidden" name="match_id" value="<?= $match_id ?>">
            <input type="hidden" name="h_score" id="h_val" value="0">
            <input type="hidden" name="a_score" id="a_val" value="0">
            <input type="hidden" name="h_id" value="<?= $match['home_id'] ?>">
            <input type="hidden" name="a_id" value="<?= $match['away_id'] ?>">
            <button type="submit" class="btn btn-success btn-lg w-100 fw-bold p-3">SAVE RESULT & FINISH</button>
        </form>
    </div>
</div>

<script>
function adj(team, val) {
    let d = document.getElementById(team+'_disp');
    let v = document.getElementById(team+'_val');
    let res = parseInt(d.innerText) + val;
    if(res < 0) res = 0;
    d.innerText = res; v.value = res;
}
</script>
</body>
</html>