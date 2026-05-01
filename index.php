<?php
session_start();
// Use require_once for stability
require_once(__DIR__ . '/database_config/db.php');

$current_user = $_SESSION['username'] ?? '';
$base = "";

$user_info = ['team_name' => 'None', 'active_team_id' => 0];

if (!empty($current_user)) {
    $stmt = $conn->prepare("
        SELECT p.active_team_id, t.team_name 
        FROM players p 
        LEFT JOIN teams t ON p.active_team_id = t.id 
        WHERE p.student_id = ?
    ");
    $stmt->bind_param("s", $current_user);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $user_info = $res->fetch_assoc();
    }
}

// ===== BATTLE HISTORY =====
if ($user_info['active_team_id'] > 0) {
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
          AND (t1.id = ? OR t2.id = ?)
        ORDER BY r.reservation_date DESC
        LIMIT 5
    ";
    $stmt = $conn->prepare($history_query);
    $stmt->bind_param("ii", $user_info['active_team_id'], $user_info['active_team_id']);
    $stmt->execute();
    $history_matches = $stmt->get_result();
} else {
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
        ORDER BY r.reservation_date DESC
        LIMIT 5
    ";
    $history_matches = $conn->query($history_query);
}

// ===== RECENT MATCHES =====
if ($user_info['active_team_id'] > 0) {
    $recent_query = "
        SELECT r.*, t.team_name, t.team_photo 
        FROM reservations r 
        JOIN teams t ON r.team_id = t.id 
        WHERE r.team_id = ?
        ORDER BY r.reservation_date DESC 
        LIMIT 10
    ";
    $stmt = $conn->prepare($recent_query);
    $stmt->bind_param("i", $user_info['active_team_id']);
    $stmt->execute();
    $recent_matches = $stmt->get_result();
} else {
    $recent_query = "
        SELECT r.*, t.team_name, t.team_photo 
        FROM reservations r 
        JOIN teams t ON r.team_id = t.id 
        ORDER BY r.reservation_date DESC 
        LIMIT 10
    ";
    $recent_matches = $conn->query($recent_query);
}

// ===== ALL TEAMS =====
$all_teams = $conn->query("SELECT * FROM teams ORDER BY id DESC");

// ===== IMAGE HELPER FIXED =====
function getImage($file) {
    if (empty($file)) return "https://via.placeholder.com/150?text=No+Photo";
    
    // Logic: Web browsers cannot read absolute system paths like C:/xampp...
    // They need relative URL paths. 
    $url_path = "uploads/" . $file; 
    
    // Return the relative URL for the <img> tag
    return $url_path;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home | NBSC Basketball</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=DM+Sans:wght@400;500;600&display=swap');

*, *::before, *::after { box-sizing: border-box; }

:root {
    --navy:        #0D2F6E;
    --navy-deep:   #071A42;
    --navy-mid:    #1A4EA6;
    --sky:         #4A9EE8;
    --sky-light:   #BDD9F5;
    --sky-pale:    #E8F3FC;
    --amber:       #F5A623;
    --amber-warm:  #FFBE4D;
    --amber-pale:  #FFF4DC;
    --white:       #FFFFFF;
    --surface:     #F2F7FD;
    --border:      #C8DCEF;
    --text-main:   #0D2F6E;
    --text-body:   #2C4A72;
    --text-muted:  #6A8BB0;
    --success:     #1B7A4A;
    --success-bg:  #E3F5EC;
    --danger:      #C0392B;
    --danger-bg:   #FEECEB;
    --radius-sm:   8px;
    --radius-md:   12px;
    --radius-lg:   18px;
    --radius-pill: 9999px;
    --shadow-sm:   0 2px 8px rgba(13, 47, 110, 0.08);
    --shadow-md:   0 4px 18px rgba(13, 47, 110, 0.13);
    --shadow-glow: 0 0 0 4px rgba(74, 158, 232, 0.18);
}

body {
    background: linear-gradient(160deg, #daeeff 0%, #eef5fb 50%, #f5f9fd 100%);
    color: var(--text-main);
    padding: 20px;
    font-family: 'DM Sans', 'Segoe UI', sans-serif;
    font-size: 15px;
    line-height: 1.65;
    min-height: 100vh;
}

h1, h2, h3, h4, h5, h6 {
    font-family: 'Outfit', sans-serif;
    font-weight: 700;
    color: var(--navy);
    letter-spacing: -0.01em;
    margin-bottom: 8px;
}
h1 { font-size: 2rem; }
h2 { font-size: 1.45rem; }
h3 { font-size: 1.15rem; }
h4 { font-size: 0.98rem; }

p { color: var(--text-body); margin-bottom: 10px; }
small, .text-muted { color: var(--text-muted) !important; font-size: 0.82rem; }

a {
    color: var(--navy-mid);
    font-weight: 600;
    text-decoration: none;
    transition: color 0.2s;
}
a:hover { color: var(--amber); }

/* ── Navbar ── */
.navbar {
    background: var(--navy-deep) !important;
    border-radius: var(--radius-lg);
    padding: 14px 22px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 26px;
    box-shadow: var(--shadow-md);
    border-bottom: 3px solid var(--amber);
}

.navbar-brand {
    font-family: 'Outfit', sans-serif;
    font-size: 1.4rem;
    font-weight: 800;
    color: var(--white) !important;
    letter-spacing: 0.02em;
}

.login-btn-top {
    background: var(--amber);
    color: var(--navy-deep) !important;
    font-family: 'Outfit', sans-serif;
    font-weight: 700;
    padding: 7px 20px;
    border-radius: var(--radius-pill);
    text-decoration: none;
    text-transform: uppercase;
    font-size: 0.76rem;
    letter-spacing: 0.06em;
    transition: 0.25s ease;
    border: 2px solid var(--amber);
    display: inline-block;
}
.login-btn-top:hover {
    background: transparent;
    color: var(--amber) !important;
    border-color: var(--amber);
}

.section-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 14px;
}
.section-header::before {
    content: '';
    display: block;
    width: 4px;
    height: 22px;
    background: var(--amber);
    border-radius: 2px;
    flex-shrink: 0;
}

.text-warning {
    color: var(--amber) !important;
    font-family: 'Outfit', sans-serif;
    font-weight: 700;
}

/* ── History Card ── */
.history-card {
    min-width: 300px;
    background: var(--white);
    border: 1.5px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 18px;
    position: relative;
    overflow: hidden;
    color: var(--text-main);
    box-shadow: var(--shadow-sm);
    transition: box-shadow 0.2s, border-color 0.2s;
}
.history-card:hover {
    box-shadow: var(--shadow-md);
    border-color: var(--sky);
}
.history-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0;
    width: 100%; height: 4px;
    background: linear-gradient(90deg, var(--sky), var(--amber-warm));
}

.winner-badge {
    position: absolute; top: 4px; right: 0;
    background: var(--amber);
    color: var(--navy-deep);
    font-size: 0.64rem;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: 0 0 0 var(--radius-sm);
    text-transform: uppercase;
}

.score-display {
    font-family: 'Outfit', sans-serif;
    font-size: 2.2rem;
    font-weight: 800;
    color: var(--navy);
    line-height: 1;
}

/* ── Photos ── */
.mini-photo {
    width: 50px; height: 50px;
    border-radius: 50%;
    object-fit: cover;
    border: 2.5px solid var(--sky-light);
    background: var(--sky-pale);
}
.winner-photo {
    border-color: var(--amber);
    box-shadow: 0 0 0 3px rgba(245, 166, 35, 0.25);
    transform: scale(1.1);
}

.vs-text {
    background: var(--navy);
    color: var(--sky-light);
    font-weight: 700;
    font-size: 0.74rem;
    padding: 4px 11px;
    border-radius: var(--radius-pill);
    text-transform: uppercase;
    display: inline-block;
}

.scroll-container {
    display: flex;
    overflow-x: auto;
    gap: 15px;
    padding-bottom: 14px;
}
.scroll-container::-webkit-scrollbar { height: 5px; }
.scroll-container::-webkit-scrollbar-thumb { background: var(--sky); border-radius: 10px; }

.team-card {
    min-width: 140px;
    background: var(--white);
    border: 1.5px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 16px;
    text-align: center;
}

.recent-card {
    min-width: 160px;
    background: var(--navy);
    border-radius: var(--radius-lg);
    padding: 16px;
    color: var(--white);
    text-align: center;
}

.empty-state {
    padding: 2.5rem;
    background: var(--white);
    border-radius: var(--radius-lg);
    width: 100%;
    text-align: center;
    border: 2px dashed var(--sky-light);
}
    </style>
</head>
<body>

<div class="navbar">
    <div>
        <span class="navbar-brand">
            <i class="bi bi-dribbble"></i> NBSC MATCH MAKER
        </span>
        <div>
            <span class="badge bg-warning text-dark mt-1">
                TEAM: <?php echo strtoupper($user_info['team_name'] ?? 'None'); ?>
            </span>
        </div>
    </div>
    <div class="d-flex align-items-center gap-3">
        <?php if ($current_user): ?>
            <span class="text-white fw-bold">
                <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($current_user); ?>
            </span>
            <a href="/userManagement/profile.php" class="login-btn-top">Back to Profile</a>
        <?php else: ?>
            <a href="/ICS_APP_DEV1/authentication/login.php" class="login-btn-top">Login</a>
        <?php endif; ?>
    </div>
</div>

<h4 class="mb-3 text-warning">
    <i class="bi bi-trophy-fill"></i> Battle History
</h4>
<div class="scroll-container mb-5">
    <?php if ($history_matches && $history_matches->num_rows > 0): ?>
        <?php while ($h = $history_matches->fetch_assoc()):
            $h_img = getImage($h['home_p']);
            $a_img = getImage($h['away_p']);
            if ($h['winner_id'] == 0) {
                $winner_display = "DRAW";
            } else {
                $winner_display = ($h['winner_id'] == $h['away_id']) ? $h['away_n'] : $h['home_n'];
            }
        ?>
        <div class="history-card">
            <div class="winner-badge">RESULT: <?php echo strtoupper(htmlspecialchars($winner_display)); ?></div>
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="text-center" style="width:30%">
                    <img src="<?php echo $h_img; ?>" class="mini-photo <?php echo ($h['winner_id'] == $h['home_id']) ? 'winner-photo' : ''; ?>">
                    <div class="small fw-bold mt-2 text-truncate"><?php echo htmlspecialchars($h['home_n']); ?></div>
                </div>
                <div class="text-center">
                    <div class="score-display"><?php echo $h['home_score']; ?> - <?php echo $h['away_score']; ?></div>
                    <div class="vs-text mt-1">FINAL</div>
                </div>
                <div class="text-center" style="width:30%">
                    <img src="<?php echo $a_img; ?>" class="mini-photo <?php echo ($h['winner_id'] == $h['away_id']) ? 'winner-photo' : ''; ?>">
                    <div class="small fw-bold mt-2 text-truncate"><?php echo htmlspecialchars($h['away_n']); ?></div>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-2 pt-2" style="border-top:1px solid var(--sky-pale)">
                <small><?php echo date("M d, Y", strtotime($h['reservation_date'])); ?></small>
            </div>
        </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="empty-state"><p>No battle history available.</p></div>
    <?php endif; ?>
</div>

<h4 class="mb-3 text-warning">
    <i class="bi bi-clock-history"></i> Recent / Upcoming Matches
</h4>
<div class="scroll-container mb-5">
    <?php if ($recent_matches && $recent_matches->num_rows > 0): ?>
        <?php while ($r = $recent_matches->fetch_assoc()):
            $team_img = getImage($r['team_photo']);
        ?>
        <div class="recent-card">
            <div class="mb-2">
                <img src="<?php echo $team_img; ?>" class="mini-photo">
            </div>
            <div class="fw-bold"><?php echo htmlspecialchars($r['team_name']); ?></div>
            <div class="small text-white-50"><?php echo date("M d, Y", strtotime($r['reservation_date'])); ?></div>
        </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="empty-state"><p>No recent or upcoming matches.</p></div>
    <?php endif; ?>
</div>

<div class="section">
    <div class="section-header">
        <h2>All Teams</h2>
    </div>
    <div class="scroll-container">
        <?php if ($all_teams && $all_teams->num_rows > 0): ?>
            <?php while ($team = $all_teams->fetch_assoc()):
                $team_img = getImage($team['team_photo']);
            ?>
            <div class="team-card">
                <img src="<?php echo $team_img; ?>" class="mini-photo mb-2">
                <div class="fw-bold text-truncate"><?php echo htmlspecialchars($team['team_name']); ?></div>
            </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Fix broken images on the fly if the file doesn't exist in the folder
document.querySelectorAll('img').forEach(img => {
    img.onerror = function() {
        this.src = "https://via.placeholder.com/150?text=No+Photo";
    };
});
</script>

</body>
</html>