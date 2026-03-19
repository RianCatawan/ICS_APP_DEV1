<?php
session_start();
include "db.php";

// THE QUERY: We must join 'reservations' to get the Home Team ID
// and 'teams' twice to get both Names.
$query = "SELECT 
            mr.id AS match_id,
            mr.home_score, 
            mr.away_score, 
            mr.winner_id,
            mr.final_status,
            r.team_id AS home_team_id, 
            mr.challenger_team_id AS away_team_id,
            t1.team_name AS home_name,
            t2.team_name AS away_name,
            r.reservation_date
          FROM match_requests mr
          JOIN reservations r ON mr.reservation_id = r.id
          JOIN teams t1 ON r.team_id = t1.id
          JOIN teams t2 ON mr.challenger_team_id = t2.id
          WHERE mr.final_status = 'confirmed'
          ORDER BY mr.id DESC";

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Battle History | NBSC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #051937; color: white; padding: 50px 0; }
        .match-card {
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid #FFD700;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 25px;
        }
        .score-text { font-size: 3rem; font-weight: 900; color: #FFD700; }
        .winner-crown { color: #FFD700; font-size: 1.5rem; display: block; }
        .team-title { font-size: 1.5rem; font-weight: bold; }
        .vs-circle { background: #FFD700; color: #000; padding: 10px; border-radius: 50%; font-weight: bold; }
    </style>
</head>
<body>

<div class="container">
    <h2 class="text-warning fw-bold mb-5 text-center">MATCH HISTORY RESULTS</h2>

    <?php if ($result && $result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): ?>
            <div class="match-card">
                <div class="row align-items-center text-center">
                    
                    <div class="col-md-4">
                        <?php if($row['winner_id'] != 0 && $row['winner_id'] == $row['home_team_id']): ?>
                            <span class="winner-crown">🏆 WINNER</span>
                        <?php endif; ?>
                        <div class="team-title"><?php echo htmlspecialchars($row['home_name']); ?></div>
                        <small class="text-white-50">ID: <?php echo $row['home_team_id']; ?></small>
                    </div>

                    <div class="col-md-4">
                        <div class="score-text">
                            <?php echo $row['home_score']; ?> - <?php echo $row['away_score']; ?>
                        </div>
                        <div class="vs-circle">VS</div>
                        <div class="mt-3 small text-white-50">
                            Date: <?php echo date('M d, Y', strtotime($row['reservation_date'])); ?>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <?php if($row['winner_id'] != 0 && $row['winner_id'] == $row['away_team_id']): ?>
                            <span class="winner-crown">🏆 WINNER</span>
                        <?php endif; ?>
                        <div class="team-title"><?php echo htmlspecialchars($row['away_name']); ?></div>
                        <small class="text-white-50">ID: <?php echo $row['away_team_id']; ?></small>
                    </div>

                </div>
                
                <div class="text-center mt-3 border-top border-secondary pt-2 small text-white-50">
                    Database Debug -> Winner ID in DB: <strong><?php echo $row['winner_id']; ?></strong>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="text-center mt-5">
            <h3>No matches found with status 'confirmed'.</h3>
            <p>Make sure your <code>save_history.php</code> sets <code>final_status = 'confirmed'</code>.</p>
        </div>
    <?php endif; ?>
</div>

</body>
</html>