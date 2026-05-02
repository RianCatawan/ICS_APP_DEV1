<?php
session_start();
require_once __DIR__ . '/../database_config/db.php';

if (!isset($_SESSION['username'])) {
    die("Please log in first.");
}

$current_user = $_SESSION['username'];
$today = date('Y-m-d');

if (isset($_GET['team_id'])) {
    $_SESSION['selected_team_id'] = intval($_GET['team_id']);
}
$my_team_id = $_SESSION['selected_team_id'] ?? 0;

$team_name = "None Selected";
if ($my_team_id > 0) {
    $t_stmt = $conn->prepare("SELECT team_name FROM teams WHERE id = ?");
    $t_stmt->bind_param("i", $my_team_id);
    $t_stmt->execute();
    $t_res = $t_stmt->get_result()->fetch_assoc();
    $team_name = $t_res['team_name'] ?? "None Selected";
}

// FETCH OPEN RESERVATIONS
$query = "SELECT r.*, t.team_name, t.game_type, t.id AS team_id, t.team_photo, t.created_by as team_owner
          FROM reservations r 
          JOIN teams t ON r.team_id = t.id 
          WHERE r.status = 'open'
          ORDER BY r.reservation_date ASC, r.selected_time ASC";
$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();

// ORGANIZE INTO THREE BUCKETS
$challengeable = [];
$my_reservations = [];
$expired = [];

while($row = $result->fetch_assoc()) {
    $is_mine = ($row['team_owner'] === $current_user);
    $is_expired = ($row['reservation_date'] < $today);

    if ($is_expired) {
        $expired[] = $row;
    } elseif ($is_mine) {
        $my_reservations[] = $row;
    } else {
        $challengeable[] = $row;
    }
}

// MERGE IN THE REQUESTED ORDER: 1. Challengeable, 2. Mine, 3. Expired
$final_matches = array_merge($challengeable, $my_reservations, $expired);
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
        :root { --brand-primary: #0A192F; --brand-accent: #FFB800; --brand-success: #00E676; --bg-body: #F4F7FA; --surface-card: #FFFFFF; --border-color: #E2E8F0; --radius-lg: 16px; --radius-md: 10px; }
        body { background-color: var(--bg-body); color: #4A5568; font-family: 'Plus Jakarta Sans', sans-serif; padding: 20px; }
        .page-header { background: var(--brand-primary); padding: 25px 35px; border-radius: var(--radius-lg); margin-bottom: 30px; border-bottom: 5px solid var(--brand-accent); display: flex; justify-content: space-between; align-items: center; }
        .page-header h2 { font-family: 'Outfit'; font-weight: 800; color: var(--brand-accent); margin: 0; }
        .match-scroll { display: flex; overflow-x: auto; gap: 25px; padding: 10px 5px 30px 5px; scrollbar-width: thin; scrollbar-color: var(--brand-accent) transparent; }
        .match-card { min-width: 340px; background: var(--surface-card); border-radius: var(--radius-lg); border: 2px solid var(--border-color); padding: 25px; position: relative; transition: 0.3s; display: flex; flex-direction: column; }
        
        /* CARD VARIATIONS */
        .match-card.my-match { border-color: var(--brand-success); background: #f0fff4; }
        .match-card.expired { opacity: 0.6; filter: grayscale(0.6); border-style: dashed; }
        
        .status-badge { position: absolute; top: -12px; right: 20px; padding: 4px 15px; border-radius: 30px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; }
        .type-badge { background: var(--brand-primary); color: var(--brand-accent); padding: 3px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; text-align:center; }
        .team-photo-container { width: 90px; height: 90px; border-radius: 50%; border: 4px solid var(--brand-accent); margin: 0 auto 15px auto; overflow: hidden; display: flex; align-items: center; justify-content: center; background: #fff; }
        .team-title { text-align: center; font-family: 'Outfit'; font-weight: 800; color: var(--brand-primary); font-size: 1.25rem; }
        .match-details { background: rgba(0,0,0,0.03); border-radius: var(--radius-md); padding: 12px; margin-bottom: 15px; text-align: center; font-size: 0.85rem; }
        .btn-challenge { border-radius: var(--radius-md); padding: 10px; font-weight: 800; text-transform: uppercase; }
        .back-btn-top { background: rgba(255, 255, 255, 0.1); color: white; padding: 8px 18px; border-radius: var(--radius-md); text-decoration: none; font-weight: 600; border: 1px solid rgba(255,255,255,0.2); }
    </style>
</head>
<body>
<div class="container-fluid px-4">
    <div class="page-header">
        <div>
            <h2><i class="bi bi-lightning-charge-fill"></i> MATCHMAKING</h2>
            <span class="text-white-50">Active challenges are shown first, followed by your teams and expired slots.</span>
        </div>
        <div class="d-flex align-items-center gap-4">
            <div class="text-end text-white">
                <small class="text-white-50 d-block">CHALLENGING AS:</small>
                <span class="badge bg-warning text-dark px-3 py-2 fw-bold"><?php echo strtoupper($team_name); ?></span>
            </div>
            <a href="javascript:history.back()" class="back-btn-top">BACK</a>
        </div>
    </div>

    <div class="match-scroll">
        <?php if (!empty($final_matches)): ?>
            <?php foreach($final_matches as $row): 
                $is_mine = ($row['team_owner'] === $current_user);
                $is_expired = ($row['reservation_date'] < $today);
            ?>
                <div class="match-card <?php echo $is_mine ? 'my-match' : ''; ?> <?php echo $is_expired ? 'expired' : ''; ?>">
                    
                    <?php if($is_expired): ?>
                        <span class="status-badge bg-danger text-white">EXPIRED</span>
                    <?php elseif($is_mine): ?>
                        <span class="status-badge bg-success text-white">MY RESERVATION</span>
                    <?php else: ?>
                        <span class="status-badge bg-primary text-white">CHALLENGEABLE</span>
                    <?php endif; ?>

                    <div class="text-center mb-2">
                        <span class="type-badge"><?php echo strtoupper($row['game_type']); ?></span>
                    </div>

                    <div class="team-photo-container">
                        <?php if (!empty($row['team_photo']) && file_exists("../uploads/" . $row['team_photo'])): ?>
                            <img src="../uploads/<?php echo $row['team_photo']; ?>" class="w-100 h-100" style="object-fit:cover;">
                        <?php else: ?>
                            <i class="bi bi-shield-shaded fs-1 text-muted"></i>
                        <?php endif; ?>
                    </div>

                    <h5 class="team-title"><?php echo strtoupper($row['team_name']); ?></h5>
                    
                    <div class="match-details">
                        <div class="fw-bold <?php echo $is_expired ? 'text-danger' : 'text-dark'; ?> mb-1">
                            <i class="bi bi-calendar3 me-1"></i> <?php echo date('M d, Y', strtotime($row['reservation_date'])); ?>
                        </div>
                        <div class="text-muted">
                            <i class="bi bi-clock me-1"></i> <?php echo $row['selected_time']; ?>
                        </div>
                    </div>

                    <div class="mt-auto">
                        <?php if($is_expired): ?>
                            <button class="btn btn-secondary w-100 btn-challenge" disabled>VOID</button>
                        <?php elseif($is_mine): ?>
                            <button class="btn btn-outline-success w-100 btn-challenge" disabled>MANAGE IN PROFILE</button>
                        <?php else: ?>
                            <a href="send_challenge.php?res_id=<?php echo $row['id']; ?>&challenger_id=<?php echo $my_team_id; ?>" class="btn btn-warning w-100 btn-challenge">
                                <i class="bi bi-lightning-fill"></i> CHALLENGE NOW
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center w-100 py-5 bg-white rounded-4 border">
                <i class="bi bi-search" style="font-size: 3rem; opacity: 0.2;"></i>
                <h4 class="mt-3 text-muted">No Court Reservations Found</h4>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="d-flex justify-content-center gap-3 mt-4">
        <a href="../userManagement/profile.php?sid=<?php echo $current_user; ?>" class="btn btn-dark px-4 py-2 fw-bold">MY PROFILE</a>
        <a href="../challenges&scheduling/selectdatetime.php" class="btn btn-warning px-4 py-2 fw-bold shadow-sm">NEW RESERVATION</a>
    </div>
</div>
</body>
</html>