<?php
session_start();
include "db.php";

if (!isset($_SESSION['username'])) {
    die("Please log in first.");
}

$current_user = $_SESSION['username'];

// 1. Fetch the user's current ACTIVE team
$user_stmt = $conn->prepare("SELECT p.active_team_id, t.team_name 
                            FROM players p 
                            LEFT JOIN teams t ON p.active_team_id = t.id 
                            WHERE p.student_id = ?");
$user_stmt->bind_param("s", $current_user);
$user_stmt->execute();
$user_info = $user_stmt->get_result()->fetch_assoc();

// 2. FETCH ALL RESERVATIONS (Updated to include team_photo)
$query = "SELECT r.*, t.team_name, t.game_type, t.id AS team_id, t.team_photo
          FROM reservations r 
          JOIN teams t ON r.team_id = t.id 
          ORDER BY r.reservation_date ASC, r.selected_time ASC";
$stmt = $conn->prepare($query);
$stmt->execute();
$matches = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Matchmaking | All Reservations</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #0d47a1; color: white; padding: 40px; font-family: 'Segoe UI', sans-serif; position: relative; }
        .header-section { border-bottom: 1px solid rgba(255,255,255,0.2); margin-bottom: 30px; padding-bottom: 10px; }
        
        /* BACK BUTTON STYLE */
        .back-btn {
            position: absolute;
            top: 20px;
            right: 40px;
            background: #000;
            color: #FFD700;
            padding: 8px 20px;
            border: 2px solid #FFD700;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            font-size: 0.9rem;
            transition: 0.3s;
            z-index: 1000;
        }
        .back-btn:hover { background: #FFD700; color: #000; }

        .match-scroll {
            display: flex;
            overflow-x: auto;
            gap: 20px;
            padding: 20px 5px;
            scroll-behavior: smooth;
        }
        
        .match-card {
            min-width: 320px;
            background: rgba(0, 0, 0, 0.4);
            border: 2px solid #FFD700;
            border-radius: 15px;
            padding: 20px;
            transition: 0.3s;
            display: flex;
            flex-direction: column;
            position: relative;
        }
        
        .my-match { border-color: #00ff88 !important; background: rgba(0, 255, 136, 0.05); }

        .team-photo-container {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 3px solid #FFD700;
            margin: 0 auto 15px auto;
            overflow: hidden;
            background: #1a1a1a;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .my-match .team-photo-container { border-color: #00ff88; }
        
        .team-photo-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .team-photo-container i { font-size: 2.5rem; color: #FFD700; }
        
        .team-title { text-align: center; color: #FFD700; font-weight: bold; border-bottom: 1px solid rgba(255,215,0,0.3); margin-bottom: 10px; padding-bottom: 5px; }
        .my-match .team-title { color: #00ff88; border-bottom-color: #00ff88; }
        
        .roster-list { list-style: none; padding: 0; font-size: 0.85rem; color: #ccc; margin-bottom: 15px; flex-grow: 1; }
        .info-badge { background: #FFD700; color: #000; font-weight: bold; font-size: 0.75rem; padding: 3px 8px; border-radius: 4px; margin-right: 5px; }
        .status-badge { position: absolute; top: -10px; right: 10px; padding: 2px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: bold; text-transform: uppercase; }

        .match-scroll::-webkit-scrollbar { height: 8px; }
        .match-scroll::-webkit-scrollbar-thumb { background: #FFD700; border-radius: 10px; }
    </style>
</head>
<body>

<a href="javascript:history.back()" class="back-btn">← BACK</a>

<div class="container">
    <div class="header-section d-flex justify-content-between align-items-center">
        <div>
            <h2><i class="bi bi-trophy-fill text-warning"></i> GLOBAL MATCHMAKING</h2>
            <p class="text-white-50">Viewing all court reservations currently filed.</p>
        </div>
        <div class="text-end" style="margin-right: 180px;"> <small class="text-warning">ACTIVE TEAM:</small><br>
            <span class="badge bg-warning text-dark"><?php echo strtoupper($user_info['team_name'] ?? 'None'); ?></span>
        </div>
    </div>

    <div class="match-scroll">
        <?php if ($matches->num_rows > 0): ?>
            <?php while($row = $matches->fetch_assoc()): 
                $is_mine = ($row['username'] === $current_user);
            ?>
                <div class="match-card <?php echo $is_mine ? 'my-match' : ''; ?>">
                    
                    <?php if($is_mine): ?>
                        <span class="status-badge bg-success">Your Reservation</span>
                    <?php else: ?>
                        <span class="status-badge bg-primary">Open Opponent</span>
                    <?php endif; ?>

                    <div class="team-photo-container">
                        <?php if (!empty($row['team_photo']) && file_exists("uploads/" . $row['team_photo'])): ?>
                            <img src="uploads/<?php echo $row['team_photo']; ?>" alt="Team Photo">
                        <?php else: ?>
                            <i class="bi bi-shield-shaded"></i>
                        <?php endif; ?>
                    </div>

                    <div class="mb-2 text-center">
                        <span class="info-badge"><?php echo strtoupper($row['game_type']); ?></span>
                        <span class="info-badge bg-light"><?php echo strtoupper($row['status']); ?></span>
                    </div>

                    <h5 class="team-title"><?php echo strtoupper($row['team_name']); ?></h5>
                    
                    <div class="small mb-3 text-center">
                        <div class="mb-1"><i class="bi bi-calendar-event text-warning"></i> <?php echo date('M d, Y', strtotime($row['reservation_date'])); ?></div>
                        <div><i class="bi bi-clock text-warning"></i> <?php echo $row['selected_time']; ?></div>
                    </div>
                    
                    <h6><i class="bi bi-people"></i> ROSTER:</h6>
                    <ul class="roster-list">
                        <?php
                        $t_id = $row['team_id'];
                        $p_stmt = $conn->prepare("SELECT player_name, role FROM team_players WHERE team_id = ?");
                        $p_stmt->bind_param("i", $t_id);
                        $p_stmt->execute();
                        $plist = $p_stmt->get_result();
                        
                        while($p = $plist->fetch_assoc()) {
                            echo "<li>• " . htmlspecialchars($p['player_name']) . " <small class='text-white-50'>(" . $p['role'] . ")</small></li>";
                        }
                        ?>
                    </ul>

                    <form action="send_challenge.php" method="POST">
                        <input type="hidden" name="res_id" value="<?php echo $row['id']; ?>">
                        <input type="hidden" name="challenger_team_id" value="<?php echo $user_info['active_team_id']; ?>">
                        
                        <?php if($is_mine): ?>
                            <button type="button" class="btn btn-outline-success btn-sm w-100 fw-bold" disabled>MANAGE MY TEAM</button>
                        <?php elseif(!$user_info['active_team_id']): ?>
                            <button type="button" class="btn btn-secondary btn-sm w-100 disabled">SET ACTIVE TEAM</button>
                        <?php else: ?>
                            <button type="submit" name="challenge" class="btn btn-warning btn-sm w-100 fw-bold">CHALLENGE TEAM</button>
                        <?php endif; ?>
                    </form>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="text-center w-100 py-5">
                <i class="bi bi-search" style="font-size: 3rem; opacity: 0.3;"></i>
                <p class="mt-3">No reservations found in the system.</p>
            </div>
        <?php endif; ?>
    </div>

    <div class="mt-5 d-flex gap-2">
        <a href="profile.php?sid=<?php echo $current_user; ?>" class="btn btn-outline-light btn-sm">
            <i class="bi bi-person"></i> My Profile
        </a>
        <a href="selectdatetime.php" class="btn btn-warning btn-sm fw-bold">
            <i class="bi bi-plus-circle"></i> Create New Reservation
        </a>
    </div>
</div>

</body>
</html>