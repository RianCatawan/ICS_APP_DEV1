<?php
session_start();
include(__DIR__ . '/../database_config/db.php');

// THE QUERY: Join 'reservations' and 'teams' twice to get both Names.
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Battle History | NBSC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@600;800&family=Plus+Jakarta+Sans:wght@400;700&display=swap');

        :root {
            --brand-primary: #0A192F;    
            --brand-accent: #FFB800;     
            --bg-body: #F4F7FA;          
            --border-bold: 3px solid #0A192F;
        }

        body { 
            background-color: var(--bg-body); 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            color: var(--brand-primary);
            padding-bottom: 50px;
        }

        /* Consistent Header Style */
        .nb-header {
            background: var(--brand-primary);
            padding: 30px;
            border-radius: 20px;
            border-bottom: 5px solid var(--brand-accent);
            color: white;
            margin: 20px 0 30px 0;
            box-shadow: 0 10px 30px rgba(10, 25, 47, 0.15);
        }

        /* Match Card - Redesigned for Core Structure */
        .match-card {
            background: white;
            border: var(--border-bold);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 25px;
            position: relative;
            overflow: hidden;
        }

        .score-box {
            background: var(--brand-primary);
            color: var(--brand-accent);
            padding: 10px 25px;
            border-radius: 12px;
            font-family: 'Outfit', sans-serif;
            font-size: 2.5rem;
            font-weight: 900;
            display: inline-block;
            min-width: 150px;
        }

        .winner-badge {
            background: var(--brand-accent);
            color: var(--brand-primary);
            font-weight: 800;
            padding: 5px 15px;
            border-radius: 50px;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: inline-block;
            margin-bottom: 10px;
        }

        .vs-divider {
            font-family: 'Outfit';
            font-weight: 800;
            color: #CBD5E1;
            font-size: 1.2rem;
            margin: 10px 0;
        }

        .team-name {
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            font-size: 1.4rem;
            color: var(--brand-primary);
        }

        .match-meta {
            font-size: 0.85rem;
            font-weight: 700;
            color: #64748B;
            text-transform: uppercase;
        }

        .btn-back {
            background: transparent;
            color: white;
            border: 2px solid rgba(255,255,255,0.3);
            font-weight: 700;
            border-radius: 50px;
            padding: 8px 20px;
            transition: 0.3s;
        }
        .btn-back:hover {
            background: var(--brand-accent);
            border-color: var(--brand-accent);
            color: var(--brand-primary);
        }
    </style>
</head>
<body>

<div class="container">
    <div class="nb-header d-flex justify-content-between align-items-center">
        <div>
            <small class="fw-bold text-uppercase" style="color: var(--brand-accent);">Competition Records</small>
            <h1 class="m-0 fw-800">BATTLE HISTORY</h1>
        </div>
        <a href="/ICS_APP_DEV1/userManagement/profile.php" class="btn btn-back">
            <i class="bi bi-arrow-left me-2"></i>BACK
        </a>
    </div>

    <?php if ($result && $result->num_rows > 0): ?>
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <?php while($row = $result->fetch_assoc()): ?>
                    <div class="match-card shadow-sm">
                        <div class="row align-items-center text-center">
                            
                            <div class="col-md-4 order-2 order-md-1">
                                <?php if($row['winner_id'] != 0 && $row['winner_id'] == $row['home_team_id']): ?>
                                    <div class="winner-badge"><i class="bi bi-trophy-fill me-1"></i> Winner</div>
                                <?php endif; ?>
                                <div class="team-name"><?php echo htmlspecialchars($row['home_name']); ?></div>
                                <div class="text-muted small fw-bold">ID: #<?php echo $row['home_team_id']; ?></div>
                            </div>

                            <div class="col-md-4 order-1 order-md-2 mb-4 mb-md-0">
                                <div class="match-meta mb-2">
                                    <i class="bi bi-calendar3 me-1"></i> 
                                    <?php echo date('M d, Y', strtotime($row['reservation_date'])); ?>
                                </div>
                                <div class="score-box">
                                    <?php echo $row['home_score']; ?> : <?php echo $row['away_score']; ?>
                                </div>
                                <div class="vs-divider">VS</div>
                            </div>

                            <div class="col-md-4 order-3 order-md-3">
                                <?php if($row['winner_id'] != 0 && $row['winner_id'] == $row['away_team_id']): ?>
                                    <div class="winner-badge"><i class="bi bi-trophy-fill me-1"></i> Winner</div>
                                <?php endif; ?>
                                <div class="team-name"><?php echo htmlspecialchars($row['away_name']); ?></div>
                                <div class="text-muted small fw-bold">ID: #<?php echo $row['away_team_id']; ?></div>
                            </div>

                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="text-center py-5">
            <i class="bi bi-shield-exclamation display-1 text-muted"></i>
            <h3 class="mt-3 fw-bold">No Records Found</h3>
            <p class="text-muted">Matches will appear here once they are marked as 'confirmed'.</p>
        </div>
    <?php endif; ?>
</div>

</body>
</html>