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

// 2. Fetch COMPLETED Matches (History) 
// UPDATED: Changed WHERE mr.status = 'completed' to mr.final_status = 'confirmed'
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

// 3. Fetch UPCOMING/RECENT Reservations (Limit 10)
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
        body { background: #0d47a1; color: white; padding: 20px; font-family: 'Segoe UI', sans-serif; }
        .scroll-container { display: flex; overflow-x: auto; gap: 15px; padding-bottom: 15px; }
        .login-btn-top {
            background: #FFD700; color: #000; font-weight: 800; padding: 8px 25px;
            border-radius: 8px; text-decoration: none; text-transform: uppercase;
            font-size: 0.9rem; transition: 0.3s; border: 2px solid #FFD700;
        }
        .login-btn-top:hover { background: transparent; color: #FFD700; }

        /* HISTORY CARD STYLE */
        .history-card {
            min-width: 320px;
            background: linear-gradient(145deg, #001f4d, #003380);
            border: 2px solid #FFD700;
            border-radius: 15px;
            padding: 15px;
            position: relative;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }
        .winner-badge {
            position: absolute; top: 0; right: 0;
            background: #FFD700; color: #000;
            font-size: 0.7rem; font-weight: 900;
            padding: 3px 10px; border-radius: 0 0 0 10px;
        }
        .score-display { font-size: 1.8rem; font-weight: 900; color: #FFD700; line-height: 1; }
        
        .recent-card { min-width: 220px; background: rgba(0, 0, 0, 0.5); border: 1px solid rgba(255,255,255,0.3); border-radius: 10px; padding: 12px; text-align: center; }
        .team-card { min-width: 180px; background: rgba(255, 255, 255, 0.1); border-radius: 10px; padding: 15px; text-align: center; border: 1px solid rgba(255,255,255,0.2); }
        .mini-photo { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(255,255,255,0.3); }
        .winner-photo { border-color: #FFD700; box-shadow: 0 0 12px #FFD700; transform: scale(1.1); }
        .vs-text { font-weight: bold; color: #FFD700; font-size: 0.8rem; }
        .scroll-container::-webkit-scrollbar { height: 6px; }
        .scroll-container::-webkit-scrollbar-thumb { background: #FFD700; border-radius: 10px; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0"><i class="bi bi-dribbble text-warning"></i> NBSC MATCH MAKER</h2>
            <span class="badge bg-warning text-dark p-2 mt-1">TEAM: <?php echo strtoupper($user_info['team_name']); ?></span>
        </div>
        <div>
            <a href="login.php" class="login-btn-top">Login</a>
        </div>
    </div>

    <h4 class="mb-3 text-warning"><i class="bi bi-trophy-fill"></i> Battle History</h4>
    <div class="scroll-container mb-5">
        <?php if($history_matches && $history_matches->num_rows > 0): ?>
            <?php while($h = $history_matches->fetch_assoc()): 
                $h_img = !empty($h['home_p']) ? "uploads/".$h['home_p'] : "https://via.placeholder.com/50";
                $a_img = !empty($h['away_p']) ? "uploads/".$h['away_p'] : "https://via.placeholder.com/50";
                
                // Determine winner name display
                if($h['winner_id'] == 0) {
                    $winner_display = "DRAW";
                } else {
                    $winner_display = ($h['winner_id'] == $h['away_id']) ? $h['away_n'] : $h['home_n'];
                }
            ?>
            <div class="history-card">
                <div class="winner-badge">RESULT: <?php echo strtoupper($winner_display); ?></div>
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="text-center" style="width: 30%;">
                        <img src="<?php echo $h_img; ?>" class="mini-photo <?php echo ($h['winner_id'] == $h['home_id']) ? 'winner-photo' : ''; ?>">
                        <div class="small fw-bold mt-2 text-truncate"><?php echo $h['home_n']; ?></div>
                    </div>

                    <div class="text-center">
                        <div class="score-display"><?php echo $h['home_score']; ?> - <?php echo $h['away_score']; ?></div>
                        <div class="vs-text mt-1">FINAL</div>
                    </div>

                    <div class="text-center" style="width: 30%;">
                        <img src="<?php echo $a_img; ?>" class="mini-photo <?php echo ($h['winner_id'] == $h['away_id']) ? 'winner-photo' : ''; ?>">
                        <div class="small fw-bold mt-2 text-truncate"><?php echo $h['away_n']; ?></div>
                    </div>
                </div>
                <div class="text-center mt-3 small text-white-50">
                    <i class="bi bi-calendar-check"></i> <?php echo date('M d, Y', strtotime($row['reservation_date'] ?? 'today')); ?>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="p-4 bg-dark rounded w-100 text-center opacity-50 border border-secondary">
                <i class="bi bi-info-circle d-block fs-3 mb-2"></i> No battle history available yet.
            </div>
        <?php endif; ?>
    </div>

    <h4 class="mb-3 text-warning">Upcoming Reservations</h4>
    <div class="scroll-container mb-5">
        <?php while($row = $recent_matches->fetch_assoc()): 
            $res_id = $row['id'];
            $chal_q = "SELECT t.team_name, t.team_photo FROM match_requests mr 
                       JOIN teams t ON mr.challenger_team_id = t.id 
                       WHERE mr.reservation_id = $res_id AND mr.status = 'accepted' LIMIT 1";
            $chal_res = $conn->query($chal_q);
            $challenger = $chal_res->fetch_assoc();
            
            $opp_name = $challenger['team_name'] ?? "TBD";
            $opp_photo = (!empty($challenger['team_photo'])) ? "uploads/".$challenger['team_photo'] : "https://via.placeholder.com/50?text=? ";
            $home_photo = (!empty($row['team_photo'])) ? "uploads/".$row['team_photo'] : "https://via.placeholder.com/50?text=Team";
        ?>
            <div class="recent-card">
                <div class="d-flex justify-content-center gap-2 mb-2">
                    <img src="<?php echo $home_photo; ?>" class="mini-photo">
                    <span class="vs-text align-self-center">VS</span>
                    <img src="<?php echo $opp_photo; ?>" class="mini-photo">
                </div>
                <div class="match-title small fw-bold text-truncate"><?php echo $row['team_name']; ?> vs <?php echo $opp_name; ?></div>
                <div class="match-date small opacity-75">
                    <i class="bi bi-calendar"></i> <?php echo date('M d', strtotime($row['reservation_date'])); ?>
                </div>
            </div>
        <?php endwhile; ?>
    </div>

    <h4 class="mb-3 text-warning">All Teams</h4>
    <div class="scroll-container mb-5">
        <?php while($t = $all_teams->fetch_assoc()): 
            $t_photo = (!empty($t['team_photo'])) ? "uploads/".$t['team_photo'] : "https://via.placeholder.com/80?text=Team";
        ?>
            <div class="team-card">
                <img src="<?php echo $t_photo; ?>" style="width:70px; height:70px; object-fit:cover; border-radius:10px; margin-bottom:10px;">
                <div class="fw-bold small text-truncate"><?php echo strtoupper($t['team_name']); ?></div>
                <small class="text-white-50" style="font-size:0.7rem;"><?php echo $t['game_type']; ?></small>
            </div>
        <?php endwhile; ?>
    </div>

    <div class="mt-5 pb-5">
        <a href="register.php?sid=<?php echo $current_user; ?>" class="btn btn-warning fw-bold px-4">CREATE PROFILE</a>
    </div>
</div>

</body>
</html>