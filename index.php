<?php
session_start();

// FIX 1: Use the new folder name (no '&' symbol)
include(__DIR__ . '/database_config/db.php');

$current_user = $_SESSION['username'] ?? '';
$base = ""; 

$user_info = ['team_name' => 'None', 'active_team_id' => 0];

// Check if $conn exists to prevent the "Fatal Error"
if (isset($conn) && !empty($current_user)) {
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

// ... Keep your SQL queries as they are, but wrap them in "if(isset($conn))" ...

// FIX 2: Correct Image Path
function getImage($file) {
    if (empty($file)) return "https://via.placeholder.com/50";
    $server_path = __DIR__ . "/uploads/" . $file; 
    if (file_exists($server_path)) {
        return "uploads/" . $file;
    }
    return "https://via.placeholder.com/50";
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

.nav-link {
    color: var(--sky-light);
    font-size: 0.78rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    text-decoration: none;
    padding: 5px 13px;
    border-radius: var(--radius-pill);
    transition: background 0.2s, color 0.2s;
}
.nav-link:hover, .nav-link.active {
    background: var(--amber);
    color: var(--navy-deep);
}

/* ── Login / Profile Button ── */
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

/* ── Page Header ── */
.page-header {
    background: var(--navy);
    border-radius: var(--radius-lg);
    padding: 22px 26px;
    margin-bottom: 28px;
    border-left: 5px solid var(--amber);
    box-shadow: var(--shadow-sm);
}
.page-header h1, .page-header h2 { color: var(--amber-warm); margin: 0; }
.page-header p { color: var(--sky-light); font-size: 0.85rem; margin: 4px 0 0; }

/* ── Section Header ── */
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
.section-header h2,
.section-header h3 {
    margin: 0;
    font-size: 1.05rem;
    color: var(--navy);
}

/* ── Section headings with text-warning ── */
.text-warning {
    color: var(--amber) !important;
    font-family: 'Outfit', sans-serif;
    font-weight: 700;
    font-size: 1rem;
    letter-spacing: 0.01em;
}

/* ── Navbar username text ── */
.text-white {
    color: var(--white) !important;
}

/* ── Buttons ── */
.btn, button, input[type="submit"] {
    font-family: 'Outfit', sans-serif;
    font-weight: 700;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    padding: 9px 22px;
    border-radius: var(--radius-pill);
    border: 2px solid transparent;
    cursor: pointer;
    transition: 0.22s ease;
    display: inline-block;
    text-decoration: none;
    line-height: 1;
}
.btn-primary, .reserve-btn,
button[type="submit"], input[type="submit"] {
    background: var(--amber);
    color: var(--navy-deep);
    border-color: var(--amber);
}
.btn-primary:hover, .reserve-btn:hover,
button[type="submit"]:hover, input[type="submit"]:hover {
    background: transparent;
    color: var(--amber);
    border-color: var(--amber);
}
.btn-secondary {
    background: transparent;
    color: var(--navy);
    border-color: var(--navy);
}
.btn-secondary:hover {
    background: var(--navy);
    color: var(--white);
}
.btn-dark {
    background: var(--navy);
    color: var(--amber-warm);
    border-color: var(--navy);
}
.btn-dark:hover {
    background: var(--amber);
    color: var(--navy-deep);
    border-color: var(--amber);
}
.btn-sm { padding: 5px 14px; font-size: 0.72rem; }
.btn-lg { padding: 12px 32px; font-size: 0.95rem; }

/* ── Forms ── */
.form-group, .fg { margin-bottom: 16px; }

label, .form-label {
    display: block;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--navy);
    margin-bottom: 6px;
}

input[type="text"],
input[type="email"],
input[type="password"],
input[type="number"],
input[type="tel"],
input[type="search"],
input[type="date"],
input[type="time"],
select, textarea {
    width: 100%;
    padding: 10px 14px;
    border: 2px solid var(--border);
    border-radius: var(--radius-md);
    font-family: 'DM Sans', sans-serif;
    font-size: 0.9rem;
    color: var(--text-main);
    background: var(--white);
    transition: border-color 0.2s, box-shadow 0.2s;
    outline: none;
}
input:focus, select:focus, textarea:focus {
    border-color: var(--sky);
    box-shadow: var(--shadow-glow);
}
input::placeholder, textarea::placeholder {
    color: var(--text-muted);
    font-weight: 400;
}
select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%230D2F6E' stroke-width='2' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 14px center;
    padding-right: 38px;
}
textarea { resize: vertical; min-height: 100px; }

.form-card {
    background: var(--white);
    border: 2px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 26px;
    max-width: 480px;
    box-shadow: var(--shadow-sm);
}

/* ── Cards ── */
.card {
    background: var(--white);
    border: 1.5px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 20px;
    color: var(--text-main);
    transition: border-color 0.2s, box-shadow 0.2s;
    position: relative;
    overflow: hidden;
    box-shadow: var(--shadow-sm);
}
.card::before {
    content: '';
    position: absolute;
    top: 0; left: 0;
    width: 100%; height: 4px;
    background: linear-gradient(90deg, var(--sky), var(--amber));
}
.card:hover {
    border-color: var(--sky);
    box-shadow: var(--shadow-md);
}
.card-dark {
    background: var(--navy);
    border: 2px solid var(--navy-mid);
    border-radius: var(--radius-lg);
    padding: 20px;
    color: var(--white);
    box-shadow: var(--shadow-md);
}
.card-dark p, .card-dark span,
.card-dark h4, .card-dark small { color: var(--white); }
.card-dark small, .card-dark .text-muted { color: var(--sky-light); }

.card-surface {
    background: var(--sky-pale);
    border: 1.5px solid var(--sky-light);
    border-radius: var(--radius-lg);
    padding: 20px;
    color: var(--text-main);
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
    letter-spacing: 0.06em;
    text-transform: uppercase;
    font-family: 'Outfit', sans-serif;
}

.score-display {
    font-family: 'Outfit', sans-serif;
    font-size: 2.2rem;
    font-weight: 800;
    color: var(--navy);
    line-height: 1;
}
.score-display.win { color: var(--amber); }

/* ── Recent Card ── */
.recent-card {
    min-width: 160px;
    background: var(--navy);
    border: 1.5px solid var(--navy-mid);
    border-radius: var(--radius-lg);
    padding: 16px;
    color: var(--white);
    transition: box-shadow 0.2s, border-color 0.2s;
    box-shadow: var(--shadow-sm);
    text-align: center;
}
.recent-card:hover {
    border-color: var(--sky);
    box-shadow: var(--shadow-md);
}
.recent-card p, .recent-card span,
.recent-card h1, .recent-card h2,
.recent-card h3, .recent-card h4,
.recent-card h5, .recent-card .label { color: var(--white) !important; }
.recent-card small,
.recent-card .text-muted { color: var(--sky-light) !important; }
.recent-card .fw-bold { color: var(--white) !important; }

/* ── Team Card ── */
.team-card {
    min-width: 140px;
    background: var(--white);
    border: 1.5px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 16px;
    text-align: center;
    color: var(--text-main);
    transition: border-color 0.2s, background 0.2s, box-shadow 0.2s;
    box-shadow: var(--shadow-sm);
}
.team-card:hover {
    border-color: var(--sky);
    background: var(--sky-pale);
    box-shadow: var(--shadow-md);
}
.team-card .fw-bold { color: var(--text-main); }

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

/* ── VS / Final Pill ── */
.vs-text {
    background: var(--navy);
    color: var(--sky-light);
    font-weight: 700;
    font-size: 0.74rem;
    padding: 4px 11px;
    border-radius: var(--radius-pill);
    text-transform: uppercase;
    letter-spacing: 0.06em;
    font-family: 'Outfit', sans-serif;
    display: inline-block;
}

/* ── Badges ── */
.badge {
    display: inline-block;
    font-size: 0.64rem;
    font-weight: 700;
    padding: 3px 9px;
    border-radius: var(--radius-pill);
    text-transform: uppercase;
    letter-spacing: 0.06em;
    font-family: 'Outfit', sans-serif;
}
.badge-live, .badge.live         { background: var(--amber); color: var(--navy-deep); }
.badge-upcoming, .badge.upcoming { background: var(--sky-pale); color: var(--navy-mid); border: 1px solid var(--sky); }
.badge-winner, .badge.winner     { background: var(--amber); color: var(--navy-deep); }
.badge-dark                      { background: var(--navy); color: var(--sky-light); }
.badge-success                   { background: var(--success-bg); color: var(--success); border: 1px solid #5cc898; }
.badge-danger                    { background: var(--danger-bg); color: var(--danger); border: 1px solid #e88880; }

/* Bootstrap badge overrides */
.badge.bg-warning,
.bg-warning {
    background: var(--amber) !important;
    color: var(--navy-deep) !important;
    font-family: 'Outfit', sans-serif;
    font-weight: 700;
    font-size: 0.68rem;
    letter-spacing: 0.05em;
    padding: 4px 10px;
    border-radius: var(--radius-pill);
}
.text-dark { color: var(--navy-deep) !important; }

/* ── Stat Cards ── */
.stat-card {
    background: var(--white);
    border: 1.5px solid var(--border);
    border-radius: var(--radius-md);
    padding: 16px;
    text-align: center;
    box-shadow: var(--shadow-sm);
}
.stat-card.accent {
    background: var(--navy);
    border-color: var(--navy-mid);
}
.stat-card .stat-value {
    font-family: 'Outfit', sans-serif;
    font-size: 2rem;
    font-weight: 800;
    color: var(--navy);
    line-height: 1;
}
.stat-card.accent .stat-value { color: var(--amber-warm); }
.stat-card .stat-label {
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--text-muted);
    margin-top: 4px;
    font-family: 'Outfit', sans-serif;
}
.stat-card.accent .stat-label { color: var(--sky-light); }

/* ── Tables ── */
.table-wrapper {
    background: var(--white);
    border: 1.5px solid var(--border);
    border-radius: var(--radius-lg);
    overflow: hidden;
    box-shadow: var(--shadow-sm);
}
table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
thead tr { background: var(--navy); }
thead th {
    padding: 11px 15px;
    color: var(--amber-warm);
    font-family: 'Outfit', sans-serif;
    font-weight: 700;
    font-size: 0.83rem;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    text-align: left;
    border: none;
}
tbody tr { border-bottom: 1px solid var(--sky-pale); transition: background 0.15s; }
tbody tr:hover { background: var(--sky-pale); }
tbody tr:last-child { border-bottom: none; }
tbody td { padding: 11px 15px; color: var(--text-body); font-weight: 500; vertical-align: middle; }
tfoot tr { background: var(--surface); border-top: 2px solid var(--border); }
tfoot td { padding: 10px 15px; font-weight: 700; color: var(--navy); }

/* ── Alerts ── */
.alert {
    padding: 12px 16px;
    border-radius: var(--radius-md);
    font-size: 0.85rem;
    font-weight: 600;
    margin-bottom: 10px;
    border-left: 4px solid transparent;
}
.alert-success { background: var(--success-bg); color: var(--success); border-left-color: #43A047; }
.alert-danger, .alert-error { background: var(--danger-bg); color: var(--danger); border-left-color: #EF5350; }
.alert-info    { background: var(--sky-pale); color: var(--navy-mid); border-left-color: var(--sky); }
.alert-warning { background: var(--amber-pale); color: var(--navy-deep); border-left-color: var(--amber); }

/* ── Modals ── */
.modal-overlay {
    position: fixed; inset: 0;
    background: rgba(7, 26, 66, 0.55);
    display: flex; align-items: center; justify-content: center;
    z-index: 1000;
    backdrop-filter: blur(2px);
}
.modal-box {
    background: var(--white);
    border: 1.5px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 28px;
    width: 90%; max-width: 480px;
    position: relative;
    box-shadow: 0 16px 48px rgba(13, 47, 110, 0.22);
}
.modal-header {
    background: var(--navy);
    color: var(--amber-warm);
    font-family: 'Outfit', sans-serif;
    font-weight: 700;
    font-size: 1.05rem;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    padding: 13px 20px;
    border-radius: var(--radius-md) var(--radius-md) 0 0;
    margin: -28px -28px 20px -28px;
}
.modal-close {
    position: absolute; top: 12px; right: 16px;
    background: none; border: none;
    color: var(--amber-warm); font-size: 1.3rem;
    cursor: pointer; font-weight: 900; line-height: 1;
}

/* ── Scroll row ── */
.scroll-container {
    display: flex;
    overflow-x: auto;
    gap: 15px;
    padding-bottom: 14px;
}
.scroll-container::-webkit-scrollbar { height: 5px; }
.scroll-container::-webkit-scrollbar-track { background: var(--sky-pale); border-radius: 10px; }
.scroll-container::-webkit-scrollbar-thumb { background: var(--sky); border-radius: 10px; }

/* ── Empty State ── */
.empty-state {
    padding: 2.5rem;
    background: var(--white);
    border-radius: var(--radius-lg);
    width: 100%;
    text-align: center;
    border: 2px dashed var(--sky-light);
    color: var(--text-muted);
}
.empty-state p { color: var(--text-muted); margin: 0; }

/* ── Dividers ── */
hr { border: none; border-top: 1.5px solid var(--sky-pale); margin: 20px 0; }
.divider-gold { border: none; border-top: 2px solid var(--amber); margin: 20px 0; }

/* ── Pagination ── */
.pagination { display: flex; gap: 6px; align-items: center; flex-wrap: wrap; }
.page-btn {
    background: var(--white);
    color: var(--navy);
    border: 1.5px solid var(--border);
    border-radius: var(--radius-sm);
    padding: 6px 13px;
    font-size: 0.78rem;
    font-weight: 700;
    cursor: pointer;
    transition: 0.2s;
    font-family: 'Outfit', sans-serif;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.page-btn:hover, .page-btn.active {
    background: var(--amber);
    color: var(--navy-deep);
    border-color: var(--amber);
}

/* ── Utility ── */
.text-gold    { color: var(--amber) !important; }
.text-navy    { color: var(--navy) !important; }
.text-sky     { color: var(--sky) !important; }
.bg-navy      { background: var(--navy) !important; }
.bg-gold      { background: var(--amber) !important; }
.bg-surface   { background: var(--surface) !important; }
.bg-page      { background: var(--sky-pale) !important; }
.bg-white     { background: var(--white) !important; }
.border-navy  { border: 1.5px solid var(--navy) !important; }
.border-gold  { border: 2px solid var(--amber) !important; }
.border-sky   { border: 1.5px solid var(--sky) !important; }
.rounded      { border-radius: var(--radius-sm) !important; }
.rounded-lg   { border-radius: var(--radius-lg) !important; }
.rounded-pill { border-radius: var(--radius-pill) !important; }
.fw-black     { font-weight: 800 !important; }
.fw-bold      { font-weight: 700 !important; }
.uppercase    { text-transform: uppercase; letter-spacing: 0.06em; }
.section      { margin-bottom: 32px; }
    </style>
</head>
<body>

<!-- NAVBAR -->
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
            <a href="userManagement/profile.php" class="login-btn-top">Back to Profile</a>
        <?php else: ?>
            <a href="authentication/login.php" class="login-btn-top">Login</a>
        <?php endif; ?>
    </div>
</div>

<!-- BATTLE HISTORY -->
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

<!-- RECENT / UPCOMING MATCHES -->
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
            <div class="small text-muted"><?php echo date("M d, Y", strtotime($r['reservation_date'])); ?></div>
        </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="empty-state"><p>No recent or upcoming matches.</p></div>
    <?php endif; ?>
</div>

<!-- ALL TEAMS -->
<div class="section">
    <div class="section-header">
        <h2>All Teams</h2>
    </div>
    <div class="scroll-container">
        <?php while ($team = $all_teams->fetch_assoc()):
            $team_img = getImage($team['team_photo']);
        ?>
        <div class="team-card">
            <img src="<?php echo $team_img; ?>" class="mini-photo mb-2">
            <div class="fw-bold text-truncate"><?php echo htmlspecialchars($team['team_name']); ?></div>
        </div>
        <?php endwhile; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>