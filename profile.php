<?php
session_start();
include "db.php";

$sid = $_GET['sid'] ?? '';
if (empty($sid)) { die("No username provided."); }

// 1. Fetch Player Personal Info
$stmt = $conn->prepare("SELECT * FROM players WHERE student_id = ?");
$stmt->bind_param("s", $sid);
$stmt->execute();
$player = $stmt->get_result()->fetch_assoc();

// 2. Fetch Teams created by this user
$team_stmt = $conn->prepare("SELECT * FROM teams WHERE created_by = ?");
$team_stmt->bind_param("s", $sid);
$team_stmt->execute();
$teams_result = $team_stmt->get_result();
$team_count = $teams_result->num_rows;

// 3. FETCH MATCH STATUS DATA
$pending_matches = [];
$done_matches = [];

$status_query = $conn->prepare("
    SELECT mr.*, t1.team_name as home_n, t2.team_name as away_n, 
           t1.created_by as home_owner, t2.created_by as away_owner
    FROM match_requests mr
    JOIN reservations r ON mr.reservation_id = r.id
    JOIN teams t1 ON r.team_id = t1.id
    JOIN teams t2 ON mr.challenger_team_id = t2.id
    WHERE (t1.created_by = ? OR t2.created_by = ?) 
    AND mr.status = 'accepted'
");
$status_query->bind_param("ss", $sid, $sid);
$status_query->execute();
$status_results = $status_query->get_result();

while($m = $status_results->fetch_assoc()) {
    if ($m['home_approved'] == 1 && $m['challenger_approved'] == 1) {
        $done_matches[] = $m;
    } else {
        $pending_matches[] = $m;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Player Profile | <?php echo htmlspecialchars($sid); ?></title>
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
            font-family: 'Segoe UI', Roboto, sans-serif;
            color: #2c3e50;
            padding-bottom: 50px;
        }

        /* PROFILE TOP SECTION */
        .profile-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            margin-top: 30px;
            border-bottom: 6px solid var(--nbsc-gold);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .avatar-circle {
            width: 100px;
            height: 100px;
            background: var(--nbsc-blue);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: bold;
            border: 4px solid white;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        /* LABELS & TYPOGRAPHY */
        .info-label {
            font-size: 0.75rem;
            font-weight: 800;
            color: #7f8c8d;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 2px;
        }

        .info-value {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--nbsc-blue);
        }

        /* QUICK STATS */
        .stat-box {
            background: white;
            padding: 15px;
            border-radius: 12px;
            text-align: center;
            border: 1px solid #eee;
        }

        /* MATCH & TEAM SECTIONS */
        .glass-panel {
            background: var(--glass-bg);
            border-radius: 18px;
            padding: 20px;
            height: 100%;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }

        .team-item {
            background: white;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 15px;
            border-left: 5px solid #ddd;
            transition: 0.2s;
        }
        .team-item.active-border { border-left-color: var(--nbsc-gold); background: #fffdf2; }

        .btn-action {
            border-radius: 8px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 0.8rem;
        }

        .section-title {
            font-weight: 800;
            color: var(--nbsc-blue);
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mt-4 px-2">
        <a href="index.php" class="btn btn-light shadow-sm fw-bold"><i class="bi bi-house-door"></i> Home</a>
        <a href="login.php"btn btn-danger shadow-sm fw-bold">Logout <i class="bi bi-box-arrow-right"></i></a>
    </div>

    <div class="profile-card mb-4">
        <div class="row align-items-center text-center text-md-start">
            <div class="col-md-auto mb-3 mb-md-0">
                <div class="avatar-circle mx-auto">
                    <?php echo substr($player['full_name'] ?? 'U', 0, 1); ?>
                </div>
            </div>
            <div class="col-md">
                <h1 class="fw-extrabold mb-1" style="color: var(--nbsc-blue); font-weight: 850;">
                    <?php echo strtoupper($player['full_name'] ?? 'PLAYER NAME'); ?>
                </h1>
                <div class="row">
                    <div class="col-6 col-md-auto pe-4">
                        <div class="info-label">STUDENT ID</div>
                        <div class="info-value"><?php echo htmlspecialchars($sid); ?></div>
                    </div>
                    <div class="col-6 col-md-auto">
                        <div class="info-label">COURSE / DEPT</div>
                        <div class="info-value"><?php echo $player['course'] ?? 'GENERAL'; ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mt-3 mt-md-0">
                <div class="row g-2">
                    <div class="col-6">
                        <div class="stat-box">
                            <div class="info-label">TEAMS</div>
                            <div class="h4 mb-0 fw-bold"><?php echo $team_count; ?></div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stat-box">
                            <div class="info-label">MATCHES</div>
                            <div class="h4 mb-0 fw-bold text-success"><?php echo count($done_matches); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="glass-panel">
                <h5 class="section-title"><i class="bi bi-calendar-check text-warning"></i> Match Status</h5>
                
                <div class="mb-4">
                    <div class="info-label mb-2 text-danger"><i class="bi bi-clock-history"></i> Pending Approval</div>
                    <?php foreach($pending_matches as $pm): 
                        $needs_my_approval = ($sid == $pm['home_owner'] && !$pm['home_approved']) || ($sid == $pm['away_owner'] && !$pm['challenger_approved']);
                    ?>
                        <div class="p-3 mb-2 bg-white rounded border <?php echo $needs_my_approval ? 'border-danger shadow-sm' : 'border-light'; ?>">
                            <div class="fw-bold small text-center"><?php echo $pm['home_n']; ?> <span class="text-muted">vs</span> <?php echo $pm['away_n']; ?></div>
                            <?php if($needs_my_approval): ?>
                                <a href="confirmation_match.php?id=<?php echo $pm['id']; ?>" class="btn btn-danger btn-action w-100 mt-2">Approve Challenge</a>
                            <?php else: ?>
                                <div class="text-center mt-2"><span class="badge bg-light text-muted fw-normal">Waiting for opponent...</span></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; if(empty($pending_matches)) echo "<p class='small text-muted text-center'>No pending requests.</p>"; ?>
                </div>

                <div>
                    <div class="info-label mb-2 text-success"><i class="bi bi-play-circle-fill"></i> Ready to Play</div>
                    <?php foreach($done_matches as $dm): ?>
                        <div class="p-2 mb-2 bg-success bg-opacity-10 border border-success rounded text-success text-center">
                            <div class="fw-bold small"><?php echo $dm['home_n']; ?> VS <?php echo $dm['away_n']; ?></div>
                            <div class="small fw-bold">GO TO COURT!</div>
                        </div>
                    <?php endforeach; if(empty($done_matches)) echo "<p class='small text-muted text-center'>No active matches.</p>"; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="glass-panel">
                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                    <h5 class="fw-bold text-primary mb-0"><i class="bi bi-people-fill"></i> My Teams</h5>
                    <a href="createteam.php" class="btn btn-warning btn-sm fw-bold">+ New Team</a>
                </div>
                
                <div class="row">
                    <?php if ($teams_result->num_rows > 0): ?>
                        <?php while($team = $teams_result->fetch_assoc()): 
                            $isActive = ($player['active_team_id'] == $team['id']); ?>
                            <div class="col-md-6">
                                <div class="team-item <?php echo $isActive ? 'active-border' : ''; ?> shadow-sm">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <div class="info-label"><?php echo $team['game_type']; ?></div>
                                            <h5 class="fw-bold text-dark mb-2"><?php echo strtoupper($team['team_name']); ?></h5>
                                        </div>
                                        <?php if($isActive): ?>
                                            <span class="badge bg-warning text-dark"><i class="bi bi-star-fill"></i> ACTIVE</span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="d-flex gap-2 mt-2">
                                        <?php if(!$isActive): ?>
                                            <a href="selectdatetime.php?team_id=<?php echo $team['id']; ?>" class="btn btn-outline-primary btn-action flex-grow-1">Use Team</a>
                                        <?php endif; ?>
                                        <a href="view_team.php?id=<?php echo $team['id']; ?>" class="btn btn-light btn-action border"><i class="bi bi-eye"></i></a>
                                    </div>

                                    <?php
                                    $tid = $team['id'];
                                    $check_req = $conn->prepare("SELECT mr.*, t.team_name as challenger_name FROM match_requests mr 
                                                                 JOIN reservations r ON mr.reservation_id = r.id 
                                                                 JOIN teams t ON mr.challenger_team_id = t.id
                                                                 WHERE r.team_id = ? AND mr.status = 'pending'");
                                    $check_req->bind_param("i", $tid);
                                    $check_req->execute();
                                    $requests = $check_req->get_result();

                                    if ($requests->num_rows > 0): ?>
                                        <div class="mt-3 p-2 bg-warning bg-opacity-10 rounded border border-warning">
                                            <div class="info-label text-warning mb-1">Incoming Challenge:</div>
                                            <?php while($req = $requests->fetch_assoc()): ?>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="small fw-bold"><?php echo htmlspecialchars($req['challenger_name']); ?></span>
                                                    <div class="btn-group">
                                                        <a href="accept_match.php?id=<?php echo $req['id']; ?>&action=accept" class="btn btn-success btn-sm py-0 fw-bold">Accept</a>
                                                        <a href="accept_match.php?id=<?php echo $req['id']; ?>&action=decline" class="btn btn-outline-danger btn-sm py-0">X</a>
                                                    </div>
                                                </div>
                                            <?php endwhile; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-12 text-center py-4">
                            <i class="bi bi-emoji-frown text-muted" style="font-size: 2rem;"></i>
                            <p class="text-muted mt-2">No teams found. Create one to start playing!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>