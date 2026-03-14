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

// 3. FETCH MATCH STATUS DATA
$active_tid = $player['active_team_id'] ?? 0;
$pending_matches = [];
$done_matches = [];

if ($active_tid > 0) {
    $status_query = $conn->prepare("
        SELECT mr.*, t1.team_name as home_n, t2.team_name as away_n 
        FROM match_requests mr
        JOIN reservations r ON mr.reservation_id = r.id
        JOIN teams t1 ON r.team_id = t1.id
        JOIN teams t2 ON mr.challenger_team_id = t2.id
        WHERE (r.team_id = ? OR mr.challenger_team_id = ?) 
        AND mr.status = 'accepted'
    ");
    $status_query->bind_param("ii", $active_tid, $active_tid);
    $status_query->execute();
    $status_results = $status_query->get_result();

    while($m = $status_results->fetch_assoc()) {
        if ($m['home_approved'] && $m['challenger_approved']) {
            $done_matches[] = $m;
        } else {
            $pending_matches[] = $m;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Profile | <?php echo htmlspecialchars($sid); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #0d47a1; color: white; padding: 40px; font-family: 'Segoe UI', sans-serif; }
        .profile-header { border-bottom: 2px solid #FFD700; margin-bottom: 30px; padding-bottom: 10px; }
        .team-card { background: rgba(0,0,0,0.3); border: 1px solid #FFD700; border-radius: 10px; padding: 20px; margin-bottom: 20px; transition: 0.3s; }
        .active-team { border: 3px solid #FFD700; background: rgba(255, 215, 0, 0.1); }
        .match-section { background: rgba(255, 255, 255, 0.1); padding: 15px; border-radius: 10px; margin-top: 20px; }
        
        /* Team Photo Styling */
        .team-img-container {
            width: 80px;
            height: 80px;
            border-radius: 10px;
            overflow: hidden;
            border: 2px solid #FFD700;
            background: #000;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }
        .team-img-container img { width: 100%; height: 100%; object-fit: cover; }
        .team-img-placeholder { font-weight: bold; color: #FFD700; font-size: 1.5rem; }
    </style>
</head>
<body>

<div class="container">
    <div class="profile-header">
        <h1><?php echo strtoupper($player['full_name'] ?? 'USER'); ?></h1>
        <p>Student ID: <?php echo htmlspecialchars($sid); ?> | Course: <?php echo $player['course'] ?? 'N/A'; ?></p>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="mb-4">
                <h3>Stats</h3>
                <p>Position: <?php echo $player['position'] ?? 'N/A'; ?></p>
                <p>Skill: <?php echo $player['skill_level'] ?? 'N/A'; ?></p>
                <a href="createteam.php" class="btn btn-warning w-100 fw-bold">Create New Team</a>
            </div>
            <hr>
            <div class="match-section">
                <h4 class="text-warning">MATCH STATUS</h4>
                <div class="mb-3">
                    <h6 class="text-white-50">⏳ PENDING APPROVAL</h6>
                    <?php foreach($pending_matches as $pm): ?>
                        <div class="p-2 mb-2 border border-warning rounded small">
                            <strong><?php echo $pm['home_n']; ?> VS <?php echo $pm['away_n']; ?></strong><br>
                            <a href="confirmation_match.php?id=<?php echo $pm['id']; ?>" class="text-warning text-decoration-none">⚠️ Click to Approve</a>
                        </div>
                    <?php endforeach; if(empty($pending_matches)) echo "<small class='text-white-50'>No pending approvals.</small>"; ?>
                </div>
                <div>
                    <h6 class="text-white-50">✅ CONFIRMED MATCHES</h6>
                    <?php foreach($done_matches as $dm): ?>
                        <div class="p-2 mb-2 border border-success rounded small bg-success bg-opacity-10">
                            <strong><?php echo $dm['home_n']; ?> VS <?php echo $dm['away_n']; ?></strong><br>
                            <span class="text-success small">Ready to Play</span>
                        </div>
                    <?php endforeach; if(empty($done_matches)) echo "<small class='text-white-50'>No confirmed matches.</small>"; ?>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <h3>My Created Teams</h3>
            
            <?php if ($teams_result->num_rows > 0): ?>
                <?php while($team = $teams_result->fetch_assoc()): ?>
                    <?php $isActive = ($player['active_team_id'] == $team['id']); ?>
                    
                    <div class="team-card <?php echo $isActive ? 'active-team' : ''; ?>">
                        <div class="d-flex align-items-center mb-3">
                            <div class="team-img-container">
                                <?php if (!empty($team['team_photo']) && file_exists("uploads/" . $team['team_photo'])): ?>
                                    <img src="uploads/<?php echo $team['team_photo']; ?>" alt="Logo">
                                <?php else: ?>
                                    <div class="team-img-placeholder"><?php echo substr($team['team_name'], 0, 1); ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between">
                                    <h4 class="mb-0"><?php echo strtoupper($team['team_name']); ?></h4>
                                    <?php if($isActive): ?>
                                        <span class="badge bg-warning text-dark">ACTIVE</span>
                                    <?php endif; ?>
                                </div>
                                <small class="text-white-50"><?php echo $team['game_type']; ?></small>
                            </div>
                        </div>

                        <?php
                        // Check for CHALLENGES on this specific team
                        $tid = $team['id'];
                        $check_req = $conn->prepare("SELECT mr.*, t.team_name as challenger_name 
                                                    FROM match_requests mr 
                                                    JOIN reservations r ON mr.reservation_id = r.id 
                                                    JOIN teams t ON mr.challenger_team_id = t.id
                                                    WHERE r.team_id = ? AND mr.status = 'pending'");
                        $check_req->bind_param("i", $tid);
                        $check_req->execute();
                        $requests = $check_req->get_result();

                        if ($requests->num_rows > 0): ?>
                            <div class="alert alert-warning mt-2 py-2" style="color: #000;">
                                <strong>🔥 CHALLENGE ALERT!</strong>
                                <?php while($req = $requests->fetch_assoc()): ?>
                                    <div class="d-flex justify-content-between align-items-center mt-1">
                                        <span>Team <b><?php echo htmlspecialchars($req['challenger_name']); ?></b></span>
                                        <div>
                                            <a href="accept_match.php?id=<?php echo $req['id']; ?>&action=accept" class="btn btn-dark btn-sm">Accept</a>
                                            <a href="accept_match.php?id=<?php echo $req['id']; ?>&action=decline" class="btn btn-outline-danger btn-sm">Decline</a>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php endif; ?>

                        <div class="mt-3 text-end">
                            <?php if ($isActive): ?>
                                <button class="btn btn-sm btn-light disabled" disabled>Currently In Use</button>
                            <?php else: ?>
                                <a href="selectdatetime.php?team_id=<?php echo $team['id']; ?>" class="btn btn-sm btn-outline-warning">
                                   Use this Team
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="text-white-50">You haven't created any teams yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>