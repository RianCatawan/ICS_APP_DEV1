<?php
session_start();
include "db.php";

// THE QUERY: Ordered by ID DESC so the newest entries are at the top
$query = "SELECT 
            mr.id AS match_id,
            mr.home_score, 
            mr.away_score, 
            mr.winner_id,
            mr.final_status,
            r.team_id AS home_team_id, 
            mr.challenger_team_id AS away_team_id,
            t1.team_name AS home_name,
            t1.team_photo AS home_p,
            t2.team_name AS away_name,
            t2.team_photo AS away_p,
            r.reservation_date
          FROM match_requests mr
          JOIN reservations r ON mr.reservation_id = r.id
          JOIN teams t1 ON r.team_id = t1.id
          JOIN teams t2 ON mr.challenger_team_id = t2.id
          WHERE mr.final_status = 'confirmed'
          ORDER BY mr.id DESC"; // This ensures newest is first

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Match Results | NBSC History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --nbsc-blue: #0d47a1;
            --nbsc-gold: #FFD700;
            --glass-white: rgba(255, 255, 255, 0.95);
        }

        body { 
            background-image: url('Covered Court.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            font-family: 'Segoe UI', sans-serif;
            padding: 40px 0;
        }

        .match-card {
            background: var(--glass-white);
            backdrop-filter: blur(10px);
            border: 3px solid var(--nbsc-gold); /* Yellow Stroke */
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            color: #333;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            position: relative;
        }

        .score-text { 
            font-size: 3.5rem; 
            font-weight: 900; 
            color: var(--nbsc-blue);
            line-height: 1;
        }

        .winner-badge { 
            background: var(--nbsc-gold); 
            color: #000; 
            font-size: 0.75rem; 
            font-weight: 900; 
            padding: 5px 15px;
            border-radius: 20px;
            display: inline-block;
            margin-bottom: 10px;
            border: 1px solid #000;
        }

        .team-title { font-size: 1.4rem; font-weight: 800; color: var(--nbsc-blue); text-transform: uppercase; }
        
        .vs-badge { 
            background: var(--nbsc-blue); 
            color: var(--nbsc-gold); 
            width: 50px; 
            height: 50px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            border-radius: 50%; 
            font-weight: 900; 
            margin: 15px auto;
            border: 2px solid var(--nbsc-gold);
        }

        .team-img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #ddd;
            margin-bottom: 10px;
        }
        
        .winner-border { border-color: var(--nbsc-gold) !important; box-shadow: 0 0 15px rgba(255, 215, 0, 0.5); }
    </style>
</head>
<body>

<div class="container" style="max-width: 900px;">
    <div class="text-center mb-5">
        <h1 class="text-white fw-900" style="text-shadow: 2px 2px 10px rgba(0,0,0,0.8);">
            <i class="bi bi-trophy-fill text-warning"></i> BATTLE RESULTS
        </h1>
        <p class="text-white-50">Latest matches are displayed first</p>
    </div>

    <?php if ($result && $result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): 
            $h_img = !empty($row['home_p']) ? "uploads/".$row['home_p'] : "https://via.placeholder.com/80";
            $a_img = !empty($row['away_p']) ? "uploads/".$row['away_p'] : "https://via.placeholder.com/80";
        ?>
            <div class="match-card">
                <div class="row align-items-center text-center">
                    
                    <div class="col-md-4">
                        <?php if($row['winner_id'] != 0 && $row['winner_id'] == $row['home_team_id']): ?>
                            <span class="winner-badge"><i class="bi bi-crown-fill"></i> WINNER</span>
                        <?php endif; ?>
                        <div>
                            <img src="<?php echo $h_img; ?>" class="team-img <?php echo ($row['winner_id'] == $row['home_team_id']) ? 'winner-border' : ''; ?>">
                        </div>
                        <div class="team-title text-truncate"><?php echo htmlspecialchars($row['home_name']); ?></div>
                    </div>

                    <div class="col-md-4">
                        <div class="score-text">
                            <?php echo $row['home_score']; ?> - <?php echo $row['away_score']; ?>
                        </div>
                        <div class="vs-badge">VS</div>
                        <div class="fw-bold small text-muted">
                            <i class="bi bi-calendar3"></i> <?php echo date('M d, Y', strtotime($row['reservation_date'])); ?>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <?php if($row['winner_id'] != 0 && $row['winner_id'] == $row['away_team_id']): ?>
                            <span class="winner-badge"><i class="bi bi-crown-fill"></i> WINNER</span>
                        <?php endif; ?>
                        <div>
                            <img src="<?php echo $a_img; ?>" class="team-img <?php echo ($row['winner_id'] == $row['away_team_id']) ? 'winner-border' : ''; ?>">
                        </div>
                        <div class="team-title text-truncate"><?php echo htmlspecialchars($row['away_name']); ?></div>
                    </div>

                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="match-card text-center py-5">
            <h3 class="text-muted">No match history available yet.</h3>
            <a href="matchmaking.php" class="btn btn-primary mt-3">Find a Match</a>
        </div>
    <?php endif; ?>

    <div class="text-center mt-4">
        <a href="profile.php" class="btn btn-dark px-5 fw-bold"><i class="bi bi-arrow-left"></i> RETURN TO PROFILE</a>
    </div>
</div>

</body>
</html>