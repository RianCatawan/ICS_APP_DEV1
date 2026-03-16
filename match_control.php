<?php
session_start();
include "db.php";

$match_id = $_GET['match_id'] ?? '';

if (empty($match_id)) {
    die("Error: No Match ID provided.");
}

// 1. Fetch Match Data with Team Names
$query = "SELECT mr.*, 
          t1.team_name as home_team, 
          t2.team_name as away_team
          FROM match_requests mr
          JOIN reservations r ON mr.reservation_id = r.id
          JOIN teams t1 ON r.team_id = t1.id
          JOIN teams t2 ON mr.challenger_team_id = t2.id
          WHERE mr.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $match_id);
$stmt->execute();
$result = $stmt->get_result();
$match = $result->fetch_assoc();

if (!$match) {
    die("Error: Match not found in database.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Match Control | LIVE SCORE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { 
            background: #0d47a1; 
            color: white; 
            font-family: 'Segoe UI', sans-serif; 
            height: 100vh; 
            display: flex; 
            align-items: center; 
        }
        .main-container {
            background: rgba(0, 0, 0, 0.4);
            border: 2px solid #FFD700;
            border-radius: 30px;
            padding: 50px;
            backdrop-filter: blur(15px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            position: relative; /* Needed for the back button positioning */
        }
        
        /* NEW BACK BUTTON STYLE */
        .back-btn {
            position: absolute;
            top: 25px;
            left: 25px;
            color: #FFD700;
            text-decoration: none;
            font-weight: bold;
            border: 1px solid #FFD700;
            padding: 5px 15px;
            border-radius: 8px;
            transition: 0.3s;
            font-size: 0.8rem;
        }
        .back-btn:hover {
            background: #FFD700;
            color: #0d47a1;
        }

        /* Rest of your existing styles */
        .score-display {
            font-size: 8rem;
            font-weight: 900;
            color: #FFD700;
            line-height: 1;
            text-shadow: 0 0 20px rgba(255, 215, 0, 0.3);
        }
        .team-label {
            font-size: 1.8rem;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 3px;
            margin-bottom: 20px;
        }
        .vs-capsule {
            background: #FFD700;
            color: #0d47a1;
            padding: 5px 20px;
            border-radius: 50px;
            font-weight: 900;
            font-size: 1.5rem;
        }
        .control-btn {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            font-size: 1.5rem;
            font-weight: bold;
            transition: 0.3s;
        }
        .btn-plus { background: #FFD700; color: #000; border: none; }
        .btn-minus { background: rgba(255,255,255,0.1); color: white; border: 1px solid white; }
        .btn-plus:hover { background: #fff; transform: scale(1.1); }
        .finish-btn {
            background: #FFD700;
            color: #0d47a1;
            border: none;
            font-weight: 800;
            letter-spacing: 1px;
            transition: 0.3s;
        }
        .finish-btn:hover {
            background: #28a745;
            color: white;
            transform: translateY(-3px);
        }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-10 main-container text-center">
            
            <a href="confirmation_match.php?id=<?php echo $match_id; ?>" class="back-btn">
                <i class="bi bi-arrow-left"></i> BACK
            </a>

            <h4 class="text-warning mb-5"><i class="bi bi-broadcast"></i> LIVE MATCH SCORING</h4>

            <div class="row align-items-center">
                <div class="col-md-5">
                    <div class="team-label"><?php echo htmlspecialchars($match['home_team']); ?></div>
                    <div class="score-display" id="homeScore">0</div>
                    <div class="mt-4">
                        <button class="control-btn btn-minus me-2" onclick="changeScore('homeScore', -1)">-</button>
                        <button class="control-btn btn-plus" onclick="changeScore('homeScore', 1)">+</button>
                    </div>
                </div>

                <div class="col-md-2 my-4 my-md-0">
                    <span class="vs-capsule">VS</span>
                </div>

                <div class="col-md-5">
                    <div class="team-label"><?php echo htmlspecialchars($match['away_team']); ?></div>
                    <div class="score-display" id="awayScore">0</div>
                    <div class="mt-4">
                        <button class="control-btn btn-minus me-2" onclick="changeScore('awayScore', -1)">-</button>
                        <button class="control-btn btn-plus" onclick="changeScore('awayScore', 1)">+</button>
                    </div>
                </div>
            </div>

            <form action="save_history.php" method="POST" class="mt-5 pt-4 border-top border-secondary">
                <input type="hidden" name="match_id" value="<?php echo $match_id; ?>">
                <input type="hidden" name="home_final_score" id="finalHome" value="0">
                <input type="hidden" name="away_final_score" id="finalAway" value="0">
                
                <button type="submit" class="btn finish-btn btn-lg px-5 py-3 shadow">
                    <i class="bi bi-check-circle-fill me-2"></i> FINISH & SAVE RESULT
                </button>
                <div class="mt-3 text-white-50 small">Once finished, this match will move to battle history.</div>
            </form>
        </div>
    </div>
</div>

<script>
function changeScore(id, val) {
    const scoreEl = document.getElementById(id);
    let current = parseInt(scoreEl.innerText);
    let newValue = current + val;
    
    if (newValue < 0) newValue = 0;
    
    scoreEl.innerText = newValue;
    
    if (id === 'homeScore') document.getElementById('finalHome').value = newValue;
    if (id === 'awayScore') document.getElementById('finalAway').value = newValue;
}
</script>

</body>
</html>