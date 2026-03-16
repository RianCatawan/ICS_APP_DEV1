<?php
session_start();
include "db.php";

// Get username if logged in, otherwise null
$current_user = $_SESSION['username'] ?? null;
$user_info = null;

// Only fetch active team if user is logged in
if ($current_user) {
    $user_stmt = $conn->prepare("SELECT p.active_team_id, t.team_name FROM players p LEFT JOIN teams t ON p.active_team_id = t.id WHERE p.student_id = ?");
    $user_stmt->bind_param("s", $current_user);
    $user_stmt->execute();
    $user_info = $user_stmt->get_result()->fetch_assoc();
}

// 2. Fetch Reservations for Recent Matches (Limit 10)
$recent_query = "SELECT r.*, t.team_name, t.team_photo FROM reservations r JOIN teams t ON r.team_id = t.id ORDER BY r.reservation_date DESC LIMIT 10";
$recent_matches = $conn->query($recent_query);

// 3. Fetch All Teams for the Bottom Section
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
        body { background: #0d47a1; color: white; padding-top: 85px; font-family: 'Segoe UI', sans-serif; }
        
        /* COMBINED NAVBAR STYLES */
        .navbar-custom {
            background: rgba(0, 0, 0, 0.9);
            backdrop-filter: blur(10px);
            border-bottom: 2px solid #FFD700;
            padding: 10px 20px;
        }

        .nav-title { font-size: 1.2rem; font-weight: 800; color: #FFD700; text-transform: uppercase; letter-spacing: 1px; }
        .active-badge { font-size: 0.7rem; background: #FFD700; color: #000; padding: 4px 10px; border-radius: 4px; font-weight: bold; }
        
        .btn-nav-action {
            color: #fff;
            border: 1px solid rgba(255,255,255,0.3);
            font-size: 0.8rem;
            font-weight: bold;
            padding: 5px 15px;
            transition: 0.3s;
            text-decoration: none;
            border-radius: 5px;
            background: transparent;
        }
        .btn-nav-action:hover { background: #FFD700; color: #000; border-color: #FFD700; }

        /* HORIZONTAL SCROLLING */
        .scroll-container { display: flex; overflow-x: auto; gap: 15px; padding: 10px 5px 20px 5px; scrollbar-width: thin; }
        .scroll-container::-webkit-scrollbar { height: 5px; }
        .scroll-container::-webkit-scrollbar-thumb { background: #FFD700; border-radius: 10px; }

        /* CARDS */
        .recent-card {
            min-width: 200px; max-width: 200px;
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255,215,0,0.3);
            border-radius: 12px; padding: 15px; text-align: center; flex-shrink: 0;
        }
        .team-card {
            min-width: 150px; background: rgba(255, 255, 255, 0.1);
            border-radius: 10px; padding: 15px; text-align: center; border: 1px solid rgba(255,255,255,0.1);
        }

        .mini-photo { width: 50px; height: 50px; border-radius: 6px; object-fit: cover; border: 2px solid #FFD700; }
        .team-photo-large { width: 70px; height: 70px; border-radius: 50%; object-fit: cover; margin-bottom: 10px; border: 2px solid #fff; }
        
        .vs-text { font-weight: bold; color: #FFD700; font-size: 0.7rem; margin: 0 5px; }
    </style>
</head>
<body>

<nav class="navbar fixed-top navbar-custom">
    <div class="container-fluid d-flex align-items-center">
        <div class="d-flex align-items-center gap-3">
            <span class="nav-title"><i class="bi bi-trophy-fill"></i> NBSC BASKETBALL</span>
            <?php if($current_user): ?>
                <span class="active-badge">ACTIVE: <?php echo strtoupper($user_info['team_name'] ?? 'NONE'); ?></span>
            <?php endif; ?>
        </div>

     
    </div>
</nav>

<div class="container-fluid px-4">
    
    <h5 class="text-warning mb-3 mt-2"><i class="bi bi-lightning-fill"></i> RECENT MATCHES</h5>
    <div class="scroll-container">
        <?php while($row = $recent_matches->fetch_assoc()): 
            $res_id = $row['id'];
            $chal_q = "SELECT t.team_name, t.team_photo FROM match_requests mr 
                       JOIN teams t ON mr.challenger_team_id = t.id 
                       WHERE mr.reservation_id = $res_id AND mr.status = 'accepted' LIMIT 1";
            $challenger_res = $conn->query($chal_q);
            $challenger = $challenger_res ? $challenger_res->fetch_assoc() : null;
            
            $opp_name = $challenger['team_name'] ?? "TBD";
            $home_img = (!empty($row['team_photo']) && file_exists("uploads/".$row['team_photo'])) ? "uploads/".$row['team_photo'] : "https://via.placeholder.com/50?text=Team";
            $opp_img = (!empty($challenger['team_photo']) && file_exists("uploads/".$challenger['team_photo'])) ? "uploads/".$challenger['team_photo'] : "https://via.placeholder.com/50?text=Opp";
        ?>
            <div class="recent-card">
                <div class="d-flex align-items-center justify-content-center mb-2">
                    <img src="<?php echo $home_img; ?>" class="mini-photo">
                    <span class="vs-text">VS</span>
                    <img src="<?php echo $opp_img; ?>" class="mini-photo">
                </div>
                <div class="small fw-bold text-truncate text-uppercase"><?php echo $row['team_name']; ?> v <?php echo $opp_name; ?></div>
                <div class="text-white-50" style="font-size: 0.7rem;"><?php echo date('M d', strtotime($row['reservation_date'])); ?></div>
            </div>
        <?php endwhile; ?>
    </div>

    <h5 class="text-warning mt-4 mb-3"><i class="bi bi-people-fill"></i> REGISTERED TEAMS</h5>
    <div class="scroll-container">
        <?php while($t = $all_teams->fetch_assoc()): 
            $t_photo = (!empty($t['team_photo']) && file_exists("uploads/".$t['team_photo'])) ? "uploads/".$t['team_photo'] : "https://via.placeholder.com/70?text=Logo";
        ?>
            <div class="team-card">
                <img src="<?php echo $t_photo; ?>" class="team-photo-large">
                <div class="fw-bold small"><?php echo strtoupper($t['team_name']); ?></div>
                <div style="font-size: 0.65rem;" class="text-white-50"><?php echo $t['game_type']; ?></div>
            </div>
        <?php endwhile; ?>
    </div>

    <div class="mt-5 border-top pt-4">
        <a href="profile.php" class="btn btn-warning fw-bold">BACK TO PROFILE</a>
        <?php if($current_user): ?>
            <a href="selectdatetime.php" class="btn btn-outline-light ms-2">RESERVE COURT</a>
        <?php endif; ?>
    </div>
</div>

</body>
</html>