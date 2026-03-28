<?php
session_start();
include(__DIR__ . '/../database&config/db.php');

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Matchmaking | NBSC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800&family=Plus+Jakarta+Sans:wght@400;500;700&display=swap');

        :root {
            --brand-primary: #0A192F;    
            --brand-accent: #FFB800;     
            --brand-success: #00E676;
            --bg-body: #F4F7FA;          
            --surface-card: #FFFFFF;     
            --border-color: #E2E8F0;
            --radius-lg: 16px;
            --radius-md: 10px;
        }

        body {
            background-color: var(--bg-body);
            color: #4A5568;
            font-family: 'Plus Jakarta Sans', sans-serif;
            padding: 20px;
        }

        /* ── Header ── */
        .page-header {
            background: var(--brand-primary);
            padding: 25px 35px;
            border-radius: var(--radius-lg);
            margin-bottom: 30px;
            border-bottom: 5px solid var(--brand-accent);
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .page-header h2 {
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            color: var(--brand-accent);
            margin: 0;
            letter-spacing: 1px;
        }

        /* ── Scroll Section ── */
        .match-scroll {
            display: flex;
            overflow-x: auto;
            gap: 25px;
            padding: 10px 5px 30px 5px;
            scrollbar-width: thin;
            scrollbar-color: var(--brand-accent) transparent;
        }

        .match-scroll::-webkit-scrollbar { height: 8px; }
        .match-scroll::-webkit-scrollbar-thumb { background: var(--brand-accent); border-radius: 10px; }

        /* ── Match Cards ── */
        .match-card {
            min-width: 340px;
            background: var(--surface-card);
            border-radius: var(--radius-lg);
            border: 2px solid var(--border-color);
            padding: 25px;
            position: relative;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
        }

        .match-card:hover {
            transform: translateY(-5px);
            border-color: var(--brand-accent);
            box-shadow: 0 12px 25px rgba(0,0,0,0.1);
        }

        /* Highlight My Match */
        .my-match {
            border-color: var(--brand-success);
            background: linear-gradient(to bottom, #ffffff, #f0fff4);
        }

        /* ── Badges ── */
        .status-badge {
            position: absolute;
            top: -12px;
            right: 20px;
            padding: 4px 15px;
            border-radius: 30px;
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .type-badge {
            background: var(--brand-primary);
            color: var(--brand-accent);
            padding: 3px 10px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 700;
        }

        /* ── Team Info ── */
        .team-photo-container {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            border: 4px solid var(--brand-accent);
            margin: 0 auto 15px auto;
            overflow: hidden;
            background: #F8FAFC;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .my-match .team-photo-container { border-color: var(--brand-success); }

        .team-photo-container img { width: 100%; height: 100%; object-fit: cover; }
        .team-photo-container i { font-size: 2.5rem; color: var(--brand-accent); }

        .team-title {
            text-align: center;
            font-family: 'Outfit';
            font-weight: 800;
            color: var(--brand-primary);
            margin-bottom: 15px;
            font-size: 1.25rem;
        }

        /* ── Details ── */
        .match-details {
            background: #F8FAFC;
            border-radius: var(--radius-md);
            padding: 12px;
            margin-bottom: 15px;
            text-align: center;
            font-size: 0.85rem;
        }

        .roster-section {
            flex-grow: 1;
            margin-bottom: 20px;
        }

        .roster-section h6 {
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            color: #94A3B8;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .roster-list {
            list-style: none;
            padding: 0;
            margin: 0;
            font-size: 0.85rem;
        }

        .roster-list li {
            padding: 4px 0;
            border-bottom: 1px solid #F1F5F9;
            color: var(--brand-primary);
            font-weight: 500;
        }

        /* ── Action Buttons ── */
        .btn-challenge {
            border-radius: var(--radius-md);
            padding: 10px;
            font-weight: 800;
            font-family: 'Outfit';
            text-transform: uppercase;
            transition: 0.3s;
        }

        .back-btn-top {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            padding: 8px 18px;
            border-radius: var(--radius-md);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            border: 1px solid rgba(255,255,255,0.2);
        }
        .back-btn-top:hover { background: var(--brand-accent); color: var(--brand-primary); }

    </style>
</head>
<body>

<div class="container-fluid px-4">
    <div class="page-header">
        <div>
            <h2><i class="bi bi-globe-americas"></i> MATCHMAKING</h2>
            <span class="text-white-50" style="font-size:0.9rem">Explore active court reservations and challenge teams</span>
        </div>
        <div class="d-flex align-items-center gap-4">
            <div class="text-end">
                <small class="text-white-50 d-block">YOUR ACTIVE TEAM</small>
                <span class="badge bg-warning text-dark px-3 py-2 fw-bold"><?php echo strtoupper($user_info['team_name'] ?? 'NONE'); ?></span>
            </div>
            <a href="javascript:history.back()" class="back-btn-top">BACK</a>
        </div>
    </div>

    <div class="match-scroll">
        <?php if ($matches->num_rows > 0): ?>
            <?php while($row = $matches->fetch_assoc()): 
                $is_mine = ($row['username'] === $current_user);
            ?>
                <div class="match-card <?php echo $is_mine ? 'my-match' : ''; ?>">
                    
                    <?php if($is_mine): ?>
                        <span class="status-badge bg-success text-white">MY TEAM</span>
                    <?php else: ?>
                        <span class="status-badge bg-primary text-white">CHALLENGEABLE</span>
                    <?php endif; ?>

                    <div class="d-flex justify-content-center mb-2">
                        <span class="type-badge"><?php echo strtoupper($row['game_type']); ?></span>
                    </div>

                    <div class="team-photo-container">
                        <?php if (!empty($row['team_photo']) && file_exists("../uploads/" . $row['team_photo'])): ?>
                            <img src="../uploads/<?php echo $row['team_photo']; ?>" alt="Team Photo">
                        <?php else: ?>
                            <i class="bi bi-shield-shaded"></i>
                        <?php endif; ?>
                    </div>

                    <h5 class="team-title"><?php echo strtoupper($row['team_name']); ?></h5>
                    
                    <div class="match-details">
                        <div class="fw-bold text-dark mb-1">
                            <i class="bi bi-calendar3 me-1"></i> <?php echo date('M d, Y', strtotime($row['reservation_date'])); ?>
                        </div>
                        <div class="text-muted">
                            <i class="bi bi-clock me-1"></i> <?php echo $row['selected_time']; ?>
                        </div>
                    </div>
                    
                    <div class="roster-section">
                        <h6><i class="bi bi-people-fill"></i> Roster Preview</h6>
                        <ul class="roster-list">
                            <?php
                            $t_id = $row['team_id'];
                            $p_stmt = $conn->prepare("SELECT player_name, role FROM team_players WHERE team_id = ? LIMIT 5");
                            $p_stmt->bind_param("i", $t_id);
                            $p_stmt->execute();
                            $plist = $p_stmt->get_result();
                            
                            while($p = $plist->fetch_assoc()) {
                                echo "<li>" . htmlspecialchars($p['player_name']) . " <span class='text-muted float-end' style='font-size:0.7rem'>" . $p['role'] . "</span></li>";
                            }
                            ?>
                        </ul>
                    </div>

                    <form action="send_challenge.php" method="POST">
                        <input type="hidden" name="res_id" value="<?php echo $row['id']; ?>">
                        <input type="hidden" name="challenger_team_id" value="<?php echo $user_info['active_team_id']; ?>">
                        
                        <?php if($is_mine): ?>
                            <button type="button" class="btn btn-outline-success w-100 btn-challenge" disabled>
                                <i class="bi bi-gear-fill"></i> MANAGE
                            </button>
                        <?php elseif(!$user_info['active_team_id']): ?>
                            <button type="button" class="btn btn-secondary w-100 btn-challenge disabled">
                                SELECT ACTIVE TEAM
                            </button>
                        <?php else: ?>
                            <button type="submit" name="challenge" class="btn btn-warning w-100 btn-challenge">
                                <i class="bi bi-lightning-fill"></i> CHALLENGE
                            </button>
                        <?php endif; ?>
                    </form>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="text-center w-100 py-5 bg-white rounded-4 border">
                <i class="bi bi-search" style="font-size: 3rem; opacity: 0.2;"></i>
                <h4 class="mt-3 text-muted">No Open Challenges</h4>
                <p>Be the first to reserve the court and start the action!</p>
            </div>
        <?php endif; ?>
    </div>

    <div class="d-flex justify-content-center gap-3 mt-4">
        <a href="/ICS_APP_DEV1/userManagement/profile.php?sid=<?php echo $current_user; ?>" class="btn btn-dark px-4 py-2 fw-bold">
            <i class="bi bi-person-circle me-2"></i> MY PROFILE
        </a>
        <a href="selectdatetime.php?team_id=<?php echo $user_info['active_team_id']; ?>" class="btn btn-warning px-4 py-2 fw-bold shadow-sm">
            <i class="bi bi-plus-lg me-2"></i> NEW RESERVATION
        </a>
    </div>
</div>

</body>
</html>