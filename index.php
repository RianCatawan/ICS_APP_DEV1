<?php
session_start();
include "db.php";

$current_user = $_SESSION['username'] ?? '';

// 1. Fetch user's active team
$user_info = ['team_name' => 'None'];
if ($current_user) {
    $user_stmt = $conn->prepare("SELECT p.active_team_id, t.team_name FROM players p LEFT JOIN teams t ON p.active_team_id = t.id WHERE p.student_id = ?");
    $user_stmt->bind_param("s", $current_user);
    $user_stmt->execute();
    $user_info = $user_stmt->get_result()->fetch_assoc() ?? ['team_name' => 'None'];
}

// 2. Fetch COMPLETED Matches (History) - Limited to 5 for screen fit
$history_query = "
    SELECT mr.home_score, mr.away_score, mr.winner_id, mr.challenger_team_id,
    t1.id as home_id, t1.team_name as home_n, t1.team_photo as home_p,
    t2.id as away_id, t2.team_name as away_n, t2.team_photo as away_p,
    r.reservation_date
    FROM match_requests mr
    JOIN reservations r ON mr.reservation_id = r.id
    JOIN teams t1 ON r.team_id = t1.id
    JOIN teams t2 ON mr.challenger_team_id = t2.id
    WHERE mr.final_status = 'confirmed'
    ORDER BY r.reservation_date DESC LIMIT 5";
$history_matches = $conn->query($history_query);

// 3. Fetch UPCOMING/RECENT Reservations
$recent_query = "SELECT r.*, t.team_name, t.team_photo FROM reservations r JOIN teams t ON r.team_id = t.id ORDER BY r.reservation_date DESC LIMIT 10";
$recent_matches = $conn->query($recent_query);

// 4. Fetch All Teams
$all_teams = $conn->query("SELECT * FROM teams ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Home | NBSC Basketball</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary-blue: #0d47a1;
            --accent-gold: #FFD700;
            --glass-white: rgba(255, 255, 255, 0.9);
        }

        body { 
            background-image: url('Covered Court.png');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            background-repeat: no-repeat;
            height: 100vh;
            margin: 0;
            padding: 15px;
            font-family: 'Segoe UI', sans-serif; 
            overflow: hidden; /* Lock the main page scroll */
        }

        /* COMPACT SCROLL CONTAINERS */
        .scroll-container { display: flex; overflow-x: auto; gap: 15px; padding: 5px 5px 15px 5px; }
        .scroll-container::-webkit-scrollbar { height: 6px; }
        .scroll-container::-webkit-scrollbar-thumb { background: var(--primary-blue); border-radius: 10px; }

        /* COMPACT HEADER */
        .app-header {
            background: var(--glass-white);
            backdrop-filter: blur(10px);
            padding: 10px 20px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            margin-bottom: 15px;
        }

        .login-btn-top {
            background: var(--primary-blue);
            color: white;
            font-weight: 600;
            padding: 6px 15px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.9rem;
        }

        /* SMALLER CARDS */
        .card-base {
            background: var(--glass-white);
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transition: 0.3s;
        }

        .history-card { min-width: 280px; padding: 12px; position: relative; }
        .recent-card { min-width: 180px; padding: 10px; text-align: center; }
        .team-card { min-width: 140px; padding: 12px; text-align: center; }

        .winner-badge {
            position: absolute; top: 8px; right: 8px;
            background: var(--primary-blue); color: white;
            font-size: 0.65rem; font-weight: 800;
            padding: 2px 8px; border-radius: 10px;
        }

        .score-display { 
            font-size: 1.4rem; 
            font-weight: 800; 
            color: var(--primary-blue); 
            background: rgba(0,0,0,0.05);
            padding: 2px 10px;
            border-radius: 8px;
        }

        .mini-photo { 
            width: 45px; height: 45px; 
            border-radius: 50%; 
            object-fit: cover; 
            border: 2px solid #fff; 
        }

        .section-title {
            font-size: 1rem;
            font-weight: 700;
            color: white; /* Contrast against dark court background */
            text-shadow: 1px 1px 4px rgba(0,0,0,0.8);
            border-left: 4px solid var(--accent-gold);
            padding-left: 10px;
            margin-bottom: 10px;
            margin-top: 5px;
        }

        .vs-tag { font-weight: 900; color: #777; font-size: 0.7rem; }

        /* BUTTON ADJUSTMENT */
        .btn-bottom {
            background: var(--accent-gold);
            color: #000;
            font-weight: 700;
            padding: 8px 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.4);
            text-decoration: none;
            display: inline-block;
        }
    </style>
</head>
<body>

<div class="container-fluid" style="height: 100vh; display: flex; flex-direction: column;">
    <div class="app-header d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fw-bold mb-0" style="color: var(--primary-blue);">
                <i class="bi bi-dribbble text-warning"></i> NBSC MATCH MAKER
            </h4>
            <span class="badge bg-light text-primary border mt-1" style="font-size: 0.7rem;">
                TEAM: <?php echo strtoupper($user_info['team_name']); ?>
            </span>
        </div>
        <a href="login.php" class="login-btn-top">Log In</a>
    </div>

    <h6 class="section-title"><i class="bi bi-trophy"></i> Battle History</h6>
    <div class="scroll-container">
        <?php if($history_matches && $history_matches->num_rows > 0): ?>
            <?php while($h = $history_matches->fetch_assoc()): 
                $h_img = !empty($h['home_p']) ? "uploads/".$h['home_p'] : "https://via.placeholder.com/45";
                $a_img = !empty($h['away_p']) ? "uploads/".$h['away_p'] : "https://via.placeholder.com/45";
                $winner_label = ($h['winner_id'] == 0) ? "DRAW" : (($h['winner_id'] == $h['away_id']) ? $h['away_n'] : $h['home_n']);
            ?>
            <div class="card-base history-card">
                <div class="winner-badge text-truncate" style="max-width: 100px;">WIN: <?php echo strtoupper($winner_label); ?></div>
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="text-center" style="width: 35%;">
                        <img src="<?php echo $h_img; ?>" class="mini-photo <?php echo ($h['winner_id'] == $h['home_id']) ? 'winner-photo' : ''; ?>">
                        <div style="font-size: 0.7rem;" class="fw-bold mt-1 text-truncate"><?php echo $h['home_n']; ?></div>
                    </div>
                    <div class="score-display"><?php echo $h['home_score']; ?>-<?php echo $h['away_score']; ?></div>
                    <div class="text-center" style="width: 35%;">
                        <img src="<?php echo $a_img; ?>" class="mini-photo <?php echo ($h['winner_id'] == $h['away_id']) ? 'winner-photo' : ''; ?>">
                        <div style="font-size: 0.7rem;" class="fw-bold mt-1 text-truncate"><?php echo $h['away_n']; ?></div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>

    <h6 class="section-title"><i class="bi bi-calendar-event"></i> Upcoming Games</h6>
    <div class="scroll-container">
        <?php while($row = $recent_matches->fetch_assoc()): 
            $home_photo = (!empty($row['team_photo'])) ? "uploads/".$row['team_photo'] : "https://via.placeholder.com/45";
        ?>
            <div class="card-base recent-card">
                <div class="d-flex justify-content-center align-items-center gap-2 mb-1">
                    <img src="<?php echo $home_photo; ?>" class="mini-photo">
                    <span class="vs-tag">VS</span>
                    <div class="mini-photo bg-secondary d-flex align-items-center justify-content-center text-white" style="font-size: 0.6rem;">TBD</div>
                </div>
                <div class="fw-bold text-truncate" style="font-size: 0.75rem;"><?php echo $row['team_name']; ?></div>
                <div class="badge bg-light text-muted fw-normal" style="font-size: 0.65rem;">
                    <?php echo date('M d', strtotime($row['reservation_date'])); ?>
                </div>
            </div>
        <?php endwhile; ?>
    </div>

    <h6 class="section-title"><i class="bi bi-people"></i> Teams</h6>
    <div class="scroll-container">
        <?php while($t = $all_teams->fetch_assoc()): 
            $t_photo = (!empty($t['team_photo'])) ? "uploads/".$t['team_photo'] : "https://via.placeholder.com/45";
        ?>
            <div class="card-base team-card">
                <img src="<?php echo $t_photo; ?>" style="width:40px; height:40px; object-fit:cover; border-radius:8px;">
                <div class="fw-bold text-truncate mt-1" style="font-size: 0.75rem;"><?php echo strtoupper($t['team_name']); ?></div>
            </div>
        <?php endwhile; ?>
    </div>

    <div class="text-center mt-auto pb-3">
        <a href="register.php?sid=<?php echo $current_user; ?>" class="btn-bottom">
            CREATE PLAYER PROFILE
        </a>
    </div>
</div>

</body>
</html>