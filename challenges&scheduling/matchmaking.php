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

// FETCH ALREADY CHALLENGED RESERVATION IDS
$already_challenged_ids = [];
if ($my_team_id > 0) {
    $ch_stmt = $conn->prepare("
        SELECT mr.reservation_id 
        FROM match_requests mr
        WHERE mr.challenger_team_id = ?
        AND mr.status NOT IN ('rejected')
    ");
    $ch_stmt->bind_param("i", $my_team_id);
    $ch_stmt->execute();
    $ch_result = $ch_stmt->get_result();
    while ($ch_row = $ch_result->fetch_assoc()) {
        $already_challenged_ids[] = $ch_row['reservation_id'];
    }
}

// ORGANIZE INTO BUCKETS
$open_challengeable = [];
$match_requested    = [];
$my_reservations    = [];
$expired            = [];

while ($row = $result->fetch_assoc()) {
    $is_mine    = ($row['team_owner'] === $current_user);
    $is_expired = ($row['reservation_date'] < $today);
    $is_challenged = !$is_mine && !$is_expired && in_array($row['id'], $already_challenged_ids);

    $row['already_challenged'] = $is_challenged;

    if ($is_expired)         { $expired[]            = $row; }
    elseif ($is_mine)        { $my_reservations[]    = $row; }
    elseif ($is_challenged)  { $match_requested[]    = $row; }
    else                     { $open_challengeable[] = $row; }
}

// ── CARD RENDER FUNCTION ────────────────────────────────────────────────────
function renderCard($row, $is_mine, $is_expired, $is_challenged, $my_team_id) {
    $card_class = $is_mine ? 'my-match' : ($is_expired ? 'expired' : ($is_challenged ? 'challenged' : ''));

    if ($is_expired)        { $badge_bg = '#dc2626'; $badge_color = '#fff'; $badge_text = 'EXPIRED'; }
    elseif ($is_mine)       { $badge_bg = '#16a34a'; $badge_color = '#fff'; $badge_text = 'MY RESERVATION'; }
    elseif ($is_challenged) { $badge_bg = '#2563EB'; $badge_color = '#fff'; $badge_text = 'MATCH REQUESTED'; }
    else                    { $badge_bg = '#1D4ED8'; $badge_color = '#fff'; $badge_text = 'CHALLENGEABLE'; }
    ?>
    <div class="match-card <?= $card_class ?>">
        <span class="status-badge" style="background:<?= $badge_bg ?>; color:<?= $badge_color ?>;">
            <?= $badge_text ?>
        </span>

        <div class="text-center mb-1 mt-3">
            <span class="type-badge"><?= strtoupper($row['game_type']) ?></span>
        </div>

        <div class="team-photo-container">
            <?php if (!empty($row['team_photo']) && file_exists("../uploads/" . $row['team_photo'])): ?>
                <img src="../uploads/<?= $row['team_photo'] ?>" class="w-100 h-100" style="object-fit:cover;">
            <?php else: ?>
                <i class="bi bi-shield-shaded fs-2 text-muted"></i>
            <?php endif; ?>
        </div>

        <h5 class="team-title"><?= strtoupper($row['team_name']) ?></h5>

        <div class="match-details">
            <div class="fw-bold mb-1 <?= $is_expired ? 'text-danger' : 'text-dark' ?>" style="font-size:0.82rem;">
                <i class="bi bi-calendar3 me-1"></i>
                <?= date('M d, Y', strtotime($row['reservation_date'])) ?>
            </div>
            <div class="text-muted" style="font-size:0.78rem;">
                <i class="bi bi-clock me-1"></i><?= $row['selected_time'] ?>
            </div>
        </div>

        <div class="mt-auto">
            <?php if ($is_expired): ?>
                <button class="btn btn-secondary w-100 btn-action" disabled>VOID</button>

            <?php elseif ($is_mine): ?>
                <button class="btn btn-outline-success w-100 btn-action" disabled>MANAGE IN PROFILE</button>

            <?php elseif ($is_challenged): ?>
                <button class="btn-match-found" disabled>
                    <i class="bi bi-check-circle-fill"></i> MATCH FOUND
                </button>
                <p class="match-found-note">
                    <i class="bi bi-hourglass-split me-1"></i>Awaiting confirmation
                </p>

            <?php else: ?>
                <a href="send_challenge.php?res_id=<?= $row['id'] ?>&challenger_id=<?= $my_team_id ?>"
                   class="btn btn-warning w-100 btn-action fw-bold">
                    <i class="bi bi-lightning-fill me-1"></i>CHALLENGE NOW
                </a>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
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
            --brand-accent:  #FFB800;
            --brand-success: #00E676;
            --brand-matched: #3B82F6;
            --bg-body:       #F4F7FA;
            --radius-lg:     16px;
            --radius-md:     10px;
        }

        *, *::before, *::after { box-sizing: border-box; }

        body {
            background: var(--bg-body);
            color: #4A5568;
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            margin: 0;
        }

        /* ── STICKY HEADER ── */
        .page-header {
            position: sticky;
            top: 0;
            z-index: 100;
            background: var(--brand-primary);
            padding: 16px 28px;
            border-bottom: 5px solid var(--brand-accent);
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.25);
        }

        .page-header h2 {
            font-family: 'Outfit';
            font-weight: 800;
            color: var(--brand-accent);
            margin: 0;
            font-size: 1.5rem;
            letter-spacing: -0.5px;
        }

        .back-btn-top {
            background: rgba(255,255,255,0.1);
            color: white;
            padding: 7px 16px;
            border-radius: var(--radius-md);
            text-decoration: none;
            font-weight: 700;
            font-size: 0.8rem;
            border: 1px solid rgba(255,255,255,0.2);
            letter-spacing: 0.5px;
            transition: 0.2s;
        }

        .back-btn-top:hover { background: rgba(255,255,255,0.2); color: white; }

        .header-action-btn {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: var(--radius-md);
            text-decoration: none;
            font-weight: 800;
            font-size: 0.78rem;
            letter-spacing: 0.5px;
            border: 2px solid transparent;
            color: white;
            text-transform: uppercase;
            transition: 0.2s;
            white-space: nowrap;
        }

        .header-action-btn:hover { filter: brightness(1.15); color: white; }

        /* ── MAIN CONTENT AREA ── */
        .main-content {
            flex-grow: 1;
            padding: 0 24px 24px 24px;
        }

        /* ── SECTION DIVIDERS ── */
        .section-label {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 28px 0 16px 0;
        }

        .label-pill {
            font-family: 'Outfit';
            font-weight: 800;
            font-size: 0.68rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            padding: 5px 14px;
            border-radius: 30px;
            white-space: nowrap;
        }

        .label-line {
            flex-grow: 1;
            height: 1px;
            background: #E2E8F0;
        }

        .label-count {
            font-size: 0.72rem;
            color: #94A3B8;
            font-weight: 700;
            white-space: nowrap;
        }

        /* ── 4-COLUMN RESPONSIVE GRID ── */
        .match-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
        }

        @media (max-width: 1280px) { .match-grid { grid-template-columns: repeat(3, 1fr); } }
        @media (max-width:  900px) { .match-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width:  540px) { .match-grid { grid-template-columns: 1fr; } }

        /* ── CARD ── */
        .match-card {
            background: #FFFFFF;
            border-radius: var(--radius-lg);
            border: 2px solid #E2E8F0;
            padding: 20px 16px 16px 16px;
            position: relative;
            display: flex;
            flex-direction: column;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .match-card:not(.expired):hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 24px rgba(0,0,0,0.1);
        }

        .match-card.my-match   { border-color: #22c55e; background: #f0fff4; }
        .match-card.expired    { opacity: 0.5; filter: grayscale(0.55); border-style: dashed; border-color: #CBD5E1; }
        .match-card.challenged { border-color: var(--brand-matched); background: linear-gradient(145deg, #EFF6FF, #DBEAFE); }

        /* ── STATUS BADGE (centred top) ── */
        .status-badge {
            position: absolute;
            top: -11px;
            left: 50%;
            transform: translateX(-50%);
            padding: 3px 13px;
            border-radius: 30px;
            font-size: 0.62rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            white-space: nowrap;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
        }

        /* ── GAME TYPE BADGE ── */
        .type-badge {
            background: var(--brand-primary);
            color: var(--brand-accent);
            padding: 3px 10px;
            border-radius: 6px;
            font-size: 0.68rem;
            font-weight: 700;
            display: inline-block;
        }

        /* ── TEAM PHOTO ── */
        .team-photo-container {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            border: 3px solid var(--brand-accent);
            margin: 10px auto 10px auto;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            flex-shrink: 0;
        }

        .match-card.challenged .team-photo-container { border-color: var(--brand-matched); }
        .match-card.my-match   .team-photo-container { border-color: #22c55e; }

        /* ── TEAM NAME ── */
        .team-title {
            text-align: center;
            font-family: 'Outfit';
            font-weight: 800;
            color: var(--brand-primary);
            font-size: 1rem;
            margin-bottom: 10px;
            line-height: 1.2;
        }

        /* ── DATE / TIME ── */
        .match-details {
            background: rgba(0,0,0,0.03);
            border-radius: var(--radius-md);
            padding: 9px 10px;
            margin-bottom: 12px;
            text-align: center;
        }

        /* ── ACTION BUTTONS ── */
        .btn-action {
            border-radius: var(--radius-md);
            padding: 8px;
            font-weight: 800;
            text-transform: uppercase;
            font-size: 0.75rem;
        }

        .btn-match-found {
            border-radius: var(--radius-md);
            padding: 9px 8px;
            font-weight: 800;
            text-transform: uppercase;
            background: var(--brand-matched);
            color: #fff;
            border: none;
            width: 100%;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            cursor: default;
            animation: pulse-blue 2.2s infinite;
        }

        @keyframes pulse-blue {
            0%, 100% { box-shadow: 0 0 0 0   rgba(59,130,246,0.5); }
            50%       { box-shadow: 0 0 0 8px rgba(59,130,246,0);   }
        }

        .match-found-note {
            text-align: center;
            font-size: 0.62rem;
            color: var(--brand-matched);
            font-weight: 700;
            margin-top: 6px;
        }

        /* ── EMPTY STATE ── */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: #94A3B8;
        }
    </style>
</head>
<body>

<!-- STICKY HEADER -->
<div class="page-header">
    <div>
        <h2><i class="bi bi-lightning-charge-fill me-2"></i>MATCHMAKING</h2>
        <span style="color:rgba(255,255,255,0.45); font-size:0.75rem;">
            Open slots · Requested · My reservations · Expired
        </span>
    </div>
    <div class="d-flex align-items-center gap-3">
        <div class="text-end">
            <small style="color:rgba(255,255,255,0.45); font-size:0.62rem; display:block; text-transform:uppercase; letter-spacing:1px;">Challenging as</small>
            <span class="badge bg-warning text-dark px-3 py-2 fw-bold" style="font-size:0.78rem; letter-spacing:0.5px;">
                <?php echo strtoupper($team_name); ?>
            </span>
        </div>
        <a href="../userManagement/profile.php?sid=<?php echo $current_user; ?>"
           class="header-action-btn" style="background:rgba(255,255,255,0.12); border-color:rgba(255,255,255,0.25);">
            <i class="bi bi-person-fill"></i> MY PROFILE
        </a>
        <a href="../challenges&scheduling/selectdatetime.php"
           class="header-action-btn" style="background:var(--brand-accent); color:var(--brand-primary); border-color:var(--brand-accent);">
            <i class="bi bi-calendar-plus"></i> NEW RESERVATION
        </a>
        <a href="javascript:history.back()" class="back-btn-top">← BACK</a>
    </div>
</div>

<div class="main-content">

<?php if (empty($open_challengeable) && empty($match_requested) && empty($my_reservations) && empty($expired)): ?>
    <div class="empty-state">
        <i class="bi bi-search" style="font-size:3.5rem; opacity:0.2;"></i>
        <h4 class="mt-3 mb-1">No reservations found</h4>
        <p class="small">Be the first — create a new reservation below.</p>
    </div>

<?php else: ?>

    <!-- ① OPEN TO CHALLENGE -->
    <?php if (!empty($open_challengeable)): ?>
    <div class="section-label">
        <span class="label-pill" style="background:#1D4ED8; color:#fff;">⚡ Open to challenge</span>
        <div class="label-line"></div>
        <span class="label-count"><?php echo count($open_challengeable); ?> available</span>
    </div>
    <div class="match-grid">
        <?php foreach ($open_challengeable as $row):
            renderCard($row, false, false, false, $my_team_id);
        endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- ② MATCH REQUESTED (already challenged by me) -->
    <?php if (!empty($match_requested)): ?>
    <div class="section-label">
        <span class="label-pill" style="background:#DBEAFE; color:#1E40AF;">✓ Match requested</span>
        <div class="label-line"></div>
        <span class="label-count"><?php echo count($match_requested); ?> pending</span>
    </div>
    <div class="match-grid">
        <?php foreach ($match_requested as $row):
            renderCard($row, false, false, true, $my_team_id);
        endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- ③ MY RESERVATIONS -->
    <?php if (!empty($my_reservations)): ?>
    <div class="section-label">
        <span class="label-pill" style="background:#D1FAE5; color:#065F46;">🛡 My reservations</span>
        <div class="label-line"></div>
        <span class="label-count"><?php echo count($my_reservations); ?></span>
    </div>
    <div class="match-grid">
        <?php foreach ($my_reservations as $row):
            renderCard($row, true, false, false, $my_team_id);
        endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- ④ EXPIRED -->
    <?php if (!empty($expired)): ?>
    <div class="section-label">
        <span class="label-pill" style="background:#FEE2E2; color:#991B1B;">✕ Expired</span>
        <div class="label-line"></div>
        <span class="label-count"><?php echo count($expired); ?></span>
    </div>
    <div class="match-grid">
        <?php foreach ($expired as $row):
            renderCard($row, ($row['team_owner'] === $current_user), true, false, $my_team_id);
        endforeach; ?>
    </div>
    <?php endif; ?>

<?php endif; ?>

</div><!-- /main-content -->



</body>
</html>