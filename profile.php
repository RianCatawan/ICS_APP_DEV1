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

// 3. FETCH MATCH STATUS DATA (Aggregated for all user-owned teams)
$pending_matches = [];
$done_matches = [];

// This query looks for any 'accepted' match request where the logged-in user 
// is the owner of either the Home team OR the Challenger team.
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
    // A match is "DONE/READY" only if BOTH sides have approved.
    if ($m['home_approved'] == 1 && $m['challenger_approved'] == 1) {
        $done_matches[] = $m;
    } else {
        // If the current user is the Home Owner and hasn't approved, 
        // OR is the Away Owner and hasn't approved, it stays in pending.
        $pending_matches[] = $m;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Profile | <?php echo htmlspecialchars($sid); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #0d47a1; color: white; padding: 40px; font-family: 'Segoe UI', sans-serif; }
        .profile-header { border-bottom: 2px solid #FFD700; margin-bottom: 30px; padding-bottom: 10px; position: relative; }
        .team-card { background: rgba(0,0,0,0.3); border: 1px solid #FFD700; border-radius: 10px; padding: 20px; margin-bottom: 20px; }
        .active-team { border: 3px solid #FFD700; background: rgba(255, 215, 0, 0.1); }
        .match-section { background: rgba(0, 0, 0, 0.2); padding: 15px; border-radius: 10px; border: 1px solid rgba(255,215,0,0.3); }
        .back-btn { position: absolute; right: 0; top: 0; }
    </style>
</head>
<body>

<div class="container">
   <div class="profile-header">
    <a href="index.php" class="btn btn-danger btn-sm back-btn">
        <i class="bi bi-box-arrow-right"></i> LOGOUT
    </a>
    <h1><?php echo strtoupper($player['full_name'] ?? 'USER'); ?></h1>
    <p>Student ID: <?php echo htmlspecialchars($sid); ?> | Course: <?php echo $player['course'] ?? 'N/A'; ?></p>
</div>
    <div class="row">
        <div class="col-md-4">
            <div class="match-section">
                <h4 class="text-warning">MATCH STATUS</h4>
                <div class="mb-4">
                    <h6 class="text-white-50 small">⏳ ACTION REQUIRED</h6>
                    <?php foreach($pending_matches as $pm): 
                        // Determine if the current user still needs to click approve
                        $needs_my_approval = ($sid == $pm['home_owner'] && !$pm['home_approved']) || ($sid == $pm['away_owner'] && !$pm['challenger_approved']);
                    ?>
                        <div class="p-2 mb-2 border <?php echo $needs_my_approval ? 'border-danger' : 'border-warning'; ?> rounded small">
                            <strong><?php echo $pm['home_n']; ?> VS <?php echo $pm['away_n']; ?></strong><br>
                            <?php if($needs_my_approval): ?>
                                <a href="confirmation_match.php?id=<?php echo $pm['id']; ?>" class="btn btn-warning btn-sm w-100 mt-1 fw-bold">APPROVE NOW</a>
                            <?php else: ?>
                                <span class="text-white-50 small italic">Waiting for opponent...</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; if(empty($pending_matches)) echo "<small class='text-white-50'>No pending approvals.</small>"; ?>
                </div>

                <div>
                    <h6 class="text-white-50 small">✅ READY TO START</h6>
                    <?php foreach($done_matches as $dm): ?>
                        <div class="p-2 mb-2 border border-success rounded small bg-success bg-opacity-10">
                            <strong class="text-success"><?php echo $dm['home_n']; ?> VS <?php echo $dm['away_n']; ?></strong><br>
                            <span class="small text-white">Match is Live! Proceed to court.</span>
                        </div>
                    <?php endforeach; if(empty($done_matches)) echo "<small class='text-white-50'>No confirmed matches.</small>"; ?>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3>My Managed Teams</h3>
                <a href="createteam.php" class="btn btn-warning fw-bold btn-sm">Create Team</a>
            </div>
            
            <?php if ($teams_result->num_rows > 0): ?>
                <?php while($team = $teams_result->fetch_assoc()): 
                    $isActive = ($player['active_team_id'] == $team['id']); ?>
                    
                    <div class="team-card <?php echo $isActive ? 'active-team' : ''; ?>">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="mb-0"><?php echo strtoupper($team['team_name']); ?></h4>
                                <small class="text-warning"><?php echo $team['game_type']; ?></small>
                            </div>
                            <?php if($isActive): ?>
                                <span class="badge bg-warning text-dark px-3">ACTIVE</span>
                            <?php else: ?>
                                <a href="selectdatetime.php?team_id=<?php echo $team['id']; ?>" class="btn btn-sm btn-outline-warning">Use Team</a>
                            <?php endif; ?>
                        </div>

                        <?php
                        // Fetch Challenges for this specific team
                        $tid = $team['id'];
                        $check_req = $conn->prepare("SELECT mr.*, t.team_name as challenger_name FROM match_requests mr 
                                                     JOIN reservations r ON mr.reservation_id = r.id 
                                                     JOIN teams t ON mr.challenger_team_id = t.id
                                                     WHERE r.team_id = ? AND mr.status = 'pending'");
                        $check_req->bind_param("i", $tid);
                        $check_req->execute();
                        $requests = $check_req->get_result();

                        if ($requests->num_rows > 0): ?>
                            <div class="alert alert-warning mt-3 py-2 text-dark">
                                <small><strong>🔥 CHALLENGE RECEIVED:</strong></small>
                                <?php while($req = $requests->fetch_assoc()): ?>
                                    <div class="d-flex justify-content-between align-items-center mt-1 border-top border-dark pt-1">
                                        <span><?php echo htmlspecialchars($req['challenger_name']); ?></span>
                                        <div>
                                            <a href="accept_match.php?id=<?php echo $req['id']; ?>&action=accept" class="btn btn-dark btn-sm py-0">Accept</a>
                                            <a href="accept_match.php?id=<?php echo $req['id']; ?>&action=decline" class="btn btn-outline-danger btn-sm py-0">X</a>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>