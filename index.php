<?php
session_start();
include "db.php";

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$current_user = $_SESSION['username'];

// 1. Fetch user's active team
$user_stmt = $conn->prepare("SELECT p.active_team_id, t.team_name FROM players p LEFT JOIN teams t ON p.active_team_id = t.id WHERE p.student_id = ?");
$user_stmt->bind_param("s", $current_user);
$user_stmt->execute();
$user_info = $user_stmt->get_result()->fetch_assoc();

// 2. Fetch Reservations for Recent Matches (Limit 10)
$recent_query = "SELECT r.*, t.team_name, t.team_photo FROM reservations r JOIN teams t ON r.team_id = t.id ORDER BY r.reservation_date DESC LIMIT 10";
$recent_matches = $conn->query($recent_query);

// 3. Fetch All Teams for the Bottom Section
$teams_query = "SELECT * FROM teams ORDER BY id DESC";
$all_teams = $conn->query($teams_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Home | NBSC Basketball</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #0d47a1; color: white; padding: 20px; font-family: 'Segoe UI', sans-serif; }
        
        .scroll-container {
            display: flex;
            overflow-x: auto;
            gap: 15px;
            padding-bottom: 15px;
        }

        /* SMALLER CARDS FOR RECENT MATCHES */
        .recent-card {
            min-width: 220px; /* Reduced width */
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid #FFD700;
            border-radius: 10px;
            padding: 12px;
            text-align: center;
        }

        /* TEAM LIST CARDS */
        .team-card {
            min-width: 180px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .vs-photo-box {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-bottom: 10px;
        }

        .mini-photo {
            width: 50px;
            height: 50px;
            border-radius: 5px;
            object-fit: cover;
            border: 2px solid #FFD700;
            background: #222;
        }

        .team-photo-large {
            width: 80px;
            height: 80px;
            border-radius: 10px;
            object-fit: cover;
            margin-bottom: 10px;
        }

        .vs-text { font-weight: bold; color: #FFD700; font-size: 0.8rem; }
        .match-title { font-size: 0.85rem; font-weight: bold; margin-bottom: 5px; }
        .match-date { font-size: 0.75rem; color: #ccc; }
        
        .scroll-container::-webkit-scrollbar { height: 6px; }
        .scroll-container::-webkit-scrollbar-thumb { background: #FFD700; border-radius: 10px; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>NBSC BASKETBALL MATCHMAKING</h2>
        <span class="badge bg-warning text-dark">Active: <?php echo strtoupper($user_info['team_name'] ?? 'None'); ?></span>
    </div>

    <h4 class="mb-3 text-warning">Recent Matches</h4>
    <div class="scroll-container">
        <?php while($row = $recent_matches->fetch_assoc()): 
            // Fetch challenger logic
            $res_id = $row['id'];
            $chal_q = "SELECT t.team_name, t.team_photo FROM match_requests mr 
                       JOIN teams t ON mr.challenger_team_id = t.id 
                       WHERE mr.reservation_id = $res_id AND mr.status = 'accepted' LIMIT 1";
            $chal_res = $conn->query($chal_q);
            $challenger = $chal_res->fetch_assoc();
            
            // Handle NULL challenger
            $opp_name = $challenger['team_name'] ?? "TBD";
            $opp_photo = (!empty($challenger['team_photo'])) ? "uploads/".$challenger['team_photo'] : "https://via.placeholder.com/50?text=? ";
            $home_photo = (!empty($row['team_photo'])) ? "uploads/".$row['team_photo'] : "https://via.placeholder.com/50?text=Team";
        ?>
            <div class="recent-card">
                <div class="vs-photo-box">
                    <img src="<?php echo $home_photo; ?>" class="mini-photo">
                    <span class="vs-text">VS</span>
                    <img src="<?php echo $opp_photo; ?>" class="mini-photo">
                </div>
                <div class="match-title"><?php echo $row['team_name']; ?> vs <?php echo $opp_name; ?></div>
                <div class="match-date">
                    <i class="bi bi-calendar"></i> <?php echo date('M d', strtotime($row['reservation_date'])); ?><br>
                    <i class="bi bi-clock"></i> <?php echo $row['selected_time']; ?>
                </div>
            </div>
        <?php endwhile; ?>
    </div>

    <h4 class="mt-5 mb-3 text-warning">Teams</h4>
    <div class="scroll-container">
        <?php while($t = $all_teams->fetch_assoc()): 
            $t_photo = (!empty($t['team_photo'])) ? "uploads/".$t['team_photo'] : "https://via.placeholder.com/80?text=Team";
        ?>
            <div class="team-card">
                <img src="<?php echo $t_photo; ?>" class="team-photo-large">
                <div class="fw-bold"><?php echo strtoupper($t['team_name']); ?></div>
                <small class="text-white-50"><?php echo $t['game_type']; ?></small>
            </div>
        <?php endwhile; ?>
    </div>

    <div class="mt-5">
        <a href="register.php" class="btn btn-warning fw-bold">CREATE PROFILE</a>
        <a href="login.php" class="btn btn-outline-light ms-2">lOG IN</a>
    </div>
</div>

</body>
</html>