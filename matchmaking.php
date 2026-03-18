<?php
session_start();
include "db.php";

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['username'];

// 1. Fetch current user's active team
$stmt_active = $conn->prepare("SELECT active_team_id FROM players WHERE student_id = ?");
$stmt_active->bind_param("s", $user_id);
$stmt_active->execute();
$active_res = $stmt_active->get_result()->fetch_assoc();
$my_team_id = $active_res['active_team_id'] ?? 0;

// 2. Fetch all 'OPEN' reservations (potential matches)
$query = "SELECT r.*, t.team_name, t.team_photo, t.game_type 
          FROM reservations r 
          JOIN teams t ON r.team_id = t.id 
          WHERE r.status = 'open' AND r.team_id != ?";
$stmt_matches = $conn->prepare($query);
$stmt_matches->bind_param("i", $my_team_id);
$stmt_matches->execute();
$matches = $stmt_matches->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find a Match | NBSC Court</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --nbsc-blue: #0d47a1;
            --nbsc-gold: #FFD700;
            --glass-bg: rgba(255, 255, 255, 0.95);
        }

        body { 
            background-image: url('Covered Court.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            font-family: 'Segoe UI', sans-serif;
            color: #2c3e50;
            padding-bottom: 50px;
        }

        .glass-panel {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            margin-top: 20px;
            border-bottom: 6px solid var(--nbsc-gold);
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
        }

        .section-header {
            color: var(--nbsc-blue);
            font-weight: 850;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 2px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 30px;
        }

        /* MATCH CARD WITH YELLOW STROKE */
        .match-card {
            background: white;
            border-radius: 15px;
            margin-bottom: 20px;
            padding: 20px;
            /* REMADE STROKE TO YELLOW */
            border: 3px solid var(--nbsc-gold); 
            transition: 0.3s;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .match-card:hover {
            transform: scale(1.01);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }

        .team-info {
            text-align: center;
            width: 30%;
        }

        .team-photo {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--nbsc-blue);
            margin-bottom: 8px;
        }

        .vs-badge {
            background: var(--nbsc-blue);
            color: var(--nbsc-gold);
            font-weight: 900;
            padding: 10px 15px;
            border-radius: 50%;
            font-size: 1.2rem;
            box-shadow: 0 0 10px rgba(13, 71, 161, 0.4);
        }

        .details-col {
            text-align: center;
            width: 25%;
        }

        .info-label {
            font-size: 0.7rem;
            font-weight: 800;
            color: #7f8c8d;
            text-transform: uppercase;
            margin-bottom: 2px;
            display: block;
        }

        .info-value {
            font-weight: 700;
            color: var(--nbsc-blue);
            font-size: 0.95rem;
        }

        .btn-challenge {
            background: var(--nbsc-gold);
            color: #000;
            font-weight: 800;
            padding: 12px 25px;
            border-radius: 10px;
            border: none;
            text-transform: uppercase;
            font-size: 0.85rem;
            transition: 0.3s;
        }

        .btn-challenge:hover {
            background: #000;
            color: var(--nbsc-gold);
        }

        .no-matches {
            text-align: center;
            padding: 50px;
            color: #888;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mt-4 px-2">
        <a href="profile.php" class="btn btn-dark fw-bold shadow-sm"><i class="bi bi-person-circle"></i> MY PROFILE</a>
        <h5 class="mb-0 text-white fw-bold">NBSC MATCHMAKING</h5>
    </div>

    <div class="glass-panel">
        <h2 class="section-header"><i class="bi bi-trophy-fill text-warning"></i> Open Challenges</h2>

        <?php if ($matches->num_rows > 0): ?>
            <?php while($row = $matches->fetch_assoc()): ?>
                <div class="match-card shadow-sm">
                    <div class="team-info">
                        <?php 
                            $img = (!empty($row['team_photo'])) ? "uploads/" . $row['team_photo'] : "https://via.placeholder.com/70";
                        ?>
                        <img src="<?php echo $img; ?>" class="team-photo" alt="Team">
                        <div class="info-value"><?php echo strtoupper($row['team_name']); ?></div>
                        <span class="badge bg-light text-dark border"><?php echo $row['game_type']; ?></span>
                    </div>

                    <div class="text-center">
                        <div class="vs-badge">VS</div>
                    </div>

                    <div class="details-col">
                        <div>
                            <span class="info-label">SCHEDULED DATE</span>
                            <span class="info-value"><?php echo date('M d, Y', strtotime($row['reservation_date'])); ?></span>
                        </div>
                        <div class="mt-3">
                            <span class="info-label">TIME SLOT</span>
                            <span class="info-value text-success"><?php echo $row['selected_time']; ?></span>
                        </div>
                    </div>

                    <div class="text-end">
                        <form action="send_request.php" method="POST">
                            <input type="hidden" name="reservation_id" value="<?php echo $row['id']; ?>">
                            <input type="hidden" name="challenger_team_id" value="<?php echo $my_team_id; ?>">
                            <button type="submit" class="btn-challenge">
                                CHALLENGE <i class="bi bi-lightning-fill"></i>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-matches">
                <i class="bi bi-search" style="font-size: 3rem; opacity: 0.3;"></i>
                <h4 class="mt-3">No Open Reservations</h4>
                <p>Currently, there are no teams looking for a match. Why not create your own reservation first?</p>
                <a href="selectdatetime.php" class="btn btn-primary fw-bold mt-2">Create Reservation</a>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>