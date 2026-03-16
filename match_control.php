<?php
session_start();
include "db.php";

$match_id = $_GET['id'] ?? 0; // Get the ID from the URL

// Fetch match details to show team names
$stmt = $conn->prepare("
    SELECT mr.*, t1.team_name as home_n, t2.team_name as away_n 
    FROM match_requests mr
    JOIN reservations r ON mr.reservation_id = r.id
    JOIN teams t1 ON r.team_id = t1.id
    JOIN teams t2 ON mr.challenger_team_id = t2.id
    WHERE mr.id = ?
");
$stmt->bind_param("i", $match_id);
$stmt->execute();
$match = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Match Control | NBSC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #000; color: white; text-align: center; padding-top: 50px; }
        .score-box { font-size: 8rem; font-weight: bold; color: #FFD700; }
        .team-label { font-size: 1.5rem; color: #aaa; text-transform: uppercase; }
    </style>
</head>
<body>

<div class="container">
    <div class="row mb-5">
        <div class="col-5">
            <div class="team-label"><?php echo $match['home_n']; ?></div>
            <div id="display_home" class="score-box">0</div>
            <button class="btn btn-outline-warning" onclick="changeScore('home', 1)">+1</button>
            <button class="btn btn-outline-danger" onclick="changeScore('home', -1)">-1</button>
        </div>
        <div class="col-2 align-self-center score-box" style="font-size: 3rem;">VS</div>
        <div class="col-5">
            <div class="team-label"><?php echo $match['away_n']; ?></div>
            <div id="display_away" class="score-box">0</div>
            <button class="btn btn-outline-warning" onclick="changeScore('away', 1)">+1</button>
            <button class="btn btn-outline-danger" onclick="changeScore('away', -1)">-1</button>
        </div>
    </div>

    <form action="process_match_done.php" method="POST" onsubmit="return syncScores()">
        <input type="hidden" name="match_id" value="<?php echo $match_id; ?>">
        <input type="hidden" id="final_home_score" name="home_score" value="0">
        <input type="hidden" id="final_away_score" name="away_score" value="0">
        
        <div class="col-md-6 mx-auto">
            <button type="submit" name="done_match" class="btn btn-danger btn-lg w-100 fw-bold">
                FINISH MATCH & SAVE TO HISTORY
            </button>
            <a href="index.php" class="btn btn-link text-white-50 mt-3">Cancel</a>
        </div>
    </form>
</div>

<script>
    let scores = { home: 0, away: 0 };

    function changeScore(team, val) {
        scores[team] += val;
        if (scores[team] < 0) scores[team] = 0;
        document.getElementById('display_' + team).innerText = scores[team];
    }

    function syncScores() {
        // CRITICAL: This puts the live score into the hidden inputs before submitting
        document.getElementById('final_home_score').value = scores.home;
        document.getElementById('final_away_score').value = scores.away;
        return confirm("Are you sure the match is finished?");
    }
</script>

</body>
</html>