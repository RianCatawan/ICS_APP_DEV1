<?php
session_start();
require_once __DIR__ . '/../database_config/db.php';

$match_id = $_POST['match_id'] ?? $_GET['match_id'] ?? null;
if (!$match_id) die("No match selected.");

$query = "SELECT mr.*, t1.team_name AS home_team, t2.team_name AS away_team, r.reservation_date, r.team_id AS home_id, mr.challenger_team_id AS away_id
          FROM match_requests mr
          JOIN reservations r ON mr.reservation_id = r.id
          JOIN teams t1 ON r.team_id = t1.id
          JOIN teams t2 ON mr.challenger_team_id = t2.id
          WHERE mr.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $match_id);
$stmt->execute();
$match = $stmt->get_result()->fetch_assoc();

if (!$match) die("Match not found.");

$today      = date('Y-m-d');
$match_date = date('Y-m-d', strtotime($match['reservation_date']));

if ($match['final_status'] === 'confirmed') {
    die("<script>alert('This match is already finished.'); window.location.href='../userManagement/profile.php';</script>");
}
if ($today > $match_date) {
    die("<script>alert('Match time has expired.'); window.location.href='../userManagement/profile.php';</script>");
}

$home_team = strtoupper($match['home_team'] ?? 'HOME');
$away_team = strtoupper($match['away_team'] ?? 'AWAY');
$home_id   = $match['home_id'];
$away_id   = $match['away_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Scoring | NBSC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@400;700;900&family=Share+Tech+Mono&family=Plus+Jakarta+Sans:wght@400;600;700&display=swap');

        :root {
            --court-dark:   #0A192F;
            --court-mid:    #112240;
            --accent:       #FFB800;
            --accent-dim:   #cc9200;
            --success:      #22c55e;
            --danger:       #ef4444;
            --text-bright:  #F0F4FF;
            --text-dim:     #8899BB;
            --panel-bg:     #0D2137;
            --border-glow:  rgba(255,184,0,0.25);
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background: var(--court-dark);
            color: var(--text-bright);
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ── TOP NAV BAR ── */
        .top-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 24px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            background: var(--court-mid);
        }

        .nav-brand {
            font-family: 'Outfit';
            font-weight: 900;
            font-size: 1rem;
            color: var(--accent);
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .btn-back-nav {
            background: transparent;
            border: 1px solid rgba(255,255,255,0.15);
            color: var(--text-dim);
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 1px;
            padding: 6px 14px;
            border-radius: 8px;
            text-decoration: none;
            text-transform: uppercase;
            transition: 0.2s;
        }

        .btn-back-nav:hover { border-color: var(--accent); color: var(--accent); }

        .match-date-tag {
            font-size: 0.72rem;
            color: var(--text-dim);
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        /* ── SCOREBOARD WRAPPER ── */
        .scoreboard {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            padding: 20px 16px;
            max-width: 900px;
            width: 100%;
            margin: 0 auto;
            gap: 16px;
        }

        /* ── QUARTER + TIMER ROW ── */
        .meta-row {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        .quarter-pills {
            display: flex;
            gap: 6px;
        }

        .q-pill {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            border: 2px solid rgba(255,255,255,0.12);
            background: transparent;
            color: var(--text-dim);
            font-family: 'Outfit';
            font-weight: 900;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: 0.2s;
        }

        .q-pill.active {
            background: var(--accent);
            border-color: var(--accent);
            color: var(--court-dark);
        }

        .q-pill.done {
            background: rgba(255,184,0,0.15);
            border-color: rgba(255,184,0,0.35);
            color: var(--accent-dim);
        }

        /* ── TIMER ── */
        .timer-block {
            background: var(--panel-bg);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 14px;
            padding: 10px 24px;
            text-align: center;
            min-width: 160px;
        }

        .timer-label {
            font-size: 0.6rem;
            color: var(--text-dim);
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 2px;
        }

        .timer-display {
            font-family: 'Share Tech Mono', monospace;
            font-size: 2.8rem;
            color: var(--accent);
            line-height: 1;
            letter-spacing: 2px;
        }

        .timer-display.running { color: var(--success); }
        .timer-display.warning { color: var(--danger); }

        .timer-controls {
            display: flex;
            gap: 6px;
            justify-content: center;
            margin-top: 8px;
        }

        .timer-btn {
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.12);
            color: var(--text-bright);
            border-radius: 8px;
            padding: 5px 14px;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            cursor: pointer;
            transition: 0.15s;
            text-transform: uppercase;
        }

        .timer-btn:hover { background: rgba(255,255,255,0.12); }
        .timer-btn.go     { background: var(--success); border-color: var(--success); color: #fff; }
        .timer-btn.go:hover { background: #16a34a; }
        .timer-btn.stop   { background: var(--danger); border-color: var(--danger); color: #fff; }
        .timer-btn.stop:hover { background: #dc2626; }

        /* ── MAIN SCORE AREA ── */
        .score-area {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            gap: 12px;
            align-items: stretch;
        }

        /* ── TEAM PANEL ── */
        .team-panel {
            background: var(--panel-bg);
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 20px;
            padding: 20px 16px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
        }

        .team-label {
            font-family: 'Outfit';
            font-weight: 900;
            font-size: 1.05rem;
            color: var(--text-bright);
            letter-spacing: 1px;
            text-align: center;
            line-height: 1.2;
        }

        .score-number {
            font-family: 'Outfit';
            font-weight: 900;
            font-size: 6.5rem;
            color: var(--text-bright);
            line-height: 1;
            min-width: 120px;
            text-align: center;
            letter-spacing: -2px;
        }

        /* ── POINT BUTTONS ── */
        .point-btns {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 6px;
            width: 100%;
        }

        .pt-btn {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 10px;
            color: var(--text-bright);
            font-family: 'Outfit';
            font-weight: 900;
            font-size: 1rem;
            padding: 10px 0;
            cursor: pointer;
            transition: 0.15s;
            text-align: center;
        }

        .pt-btn:hover { background: rgba(255,184,0,0.18); border-color: var(--accent); color: var(--accent); }
        .pt-btn:active { transform: scale(0.95); }

        .pt-btn.minus {
            background: rgba(239,68,68,0.08);
            border-color: rgba(239,68,68,0.2);
            color: var(--danger);
            font-size: 0.85rem;
        }

        .pt-btn.minus:hover { background: rgba(239,68,68,0.2); }

        /* ── FOULS & TIMEOUTS ── */
        .stats-row {
            display: flex;
            gap: 8px;
            width: 100%;
        }

        .stat-box {
            flex: 1;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 10px;
            padding: 8px 6px;
            text-align: center;
        }

        .stat-label {
            font-size: 0.58rem;
            color: var(--text-dim);
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .stat-value {
            font-family: 'Outfit';
            font-weight: 900;
            font-size: 1.4rem;
            color: var(--text-bright);
            line-height: 1;
        }

        .stat-value.foul-warn { color: #f97316; }
        .stat-value.foul-out  { color: var(--danger); }

        .stat-controls {
            display: flex;
            gap: 4px;
            justify-content: center;
            margin-top: 5px;
        }

        .mini-btn {
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 6px;
            color: var(--text-bright);
            font-size: 0.75rem;
            font-weight: 700;
            padding: 2px 10px;
            cursor: pointer;
            transition: 0.15s;
        }

        .mini-btn:hover { background: rgba(255,255,255,0.14); }

        /* ── CENTER DIVIDER ── */
        .center-col {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 10px 0;
        }

        .vs-text {
            font-family: 'Outfit';
            font-weight: 900;
            font-size: 1.6rem;
            color: var(--accent);
            letter-spacing: 2px;
        }

        .divider-line {
            width: 1px;
            flex-grow: 1;
            background: rgba(255,255,255,0.08);
        }

        /* ── PERIOD SCORE TABLE ── */
        .period-table-wrap {
            background: var(--panel-bg);
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 14px;
            padding: 14px 16px;
        }

        .period-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.78rem;
        }

        .period-table th {
            color: var(--text-dim);
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            padding: 4px 8px;
            text-align: center;
            font-size: 0.65rem;
        }

        .period-table td {
            text-align: center;
            padding: 5px 8px;
            font-family: 'Outfit';
            font-weight: 700;
            font-size: 0.9rem;
            color: var(--text-bright);
        }

        .period-table .team-col { text-align: left; color: var(--text-dim); font-family: 'Plus Jakarta Sans'; font-size: 0.72rem; }
        .period-table .total-col { color: var(--accent); font-size: 1rem; }

        /* ── FINISH BUTTON ── */
        .finish-strip {
            background: var(--panel-bg);
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 14px;
            padding: 14px 16px;
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .btn-finish {
            flex-grow: 1;
            background: var(--success);
            border: none;
            border-radius: 10px;
            color: #fff;
            font-family: 'Outfit';
            font-weight: 900;
            font-size: 1rem;
            letter-spacing: 1px;
            padding: 14px;
            cursor: pointer;
            text-transform: uppercase;
            transition: 0.2s;
        }

        .btn-finish:hover { background: #16a34a; }

        .btn-reset-all {
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.25);
            border-radius: 10px;
            color: var(--danger);
            font-family: 'Outfit';
            font-weight: 900;
            font-size: 0.78rem;
            letter-spacing: 1px;
            padding: 14px 18px;
            cursor: pointer;
            text-transform: uppercase;
            transition: 0.2s;
        }

        .btn-reset-all:hover { background: rgba(239,68,68,0.2); }

        /* ── LIVE INDICATOR ── */
        .live-dot {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.65rem;
            color: var(--success);
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .live-dot::before {
            content: '';
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--success);
            animation: blink 1.2s infinite;
        }

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50%       { opacity: 0.2; }
        }

        /* ── BONUS INDICATOR ── */
        .bonus-badge {
            font-size: 0.6rem;
            background: rgba(239,68,68,0.18);
            color: var(--danger);
            border: 1px solid rgba(239,68,68,0.3);
            border-radius: 20px;
            padding: 2px 8px;
            font-weight: 700;
            letter-spacing: 1px;
            display: none;
        }

        .bonus-badge.show { display: inline-block; }

        /* HIDDEN INPUTS */
        .hidden { display: none; }
    </style>
</head>
<body>

<!-- TOP NAV -->
<div class="top-nav">
    <a href="../userManagement/profile.php" class="btn-back-nav">
        <i class="bi bi-arrow-left me-1"></i> Back
    </a>
    <div class="nav-brand">NBSC Live Scoring</div>
    <div class="d-flex align-items-center gap-3">
        <span class="live-dot">Live</span>
        <span class="match-date-tag"><?php echo date('M d, Y', strtotime($match['reservation_date'])); ?></span>
    </div>
</div>

<div class="scoreboard">

    <!-- QUARTER SELECTOR + TIMER -->
    <div class="meta-row">
        <div class="quarter-pills">
            <div class="q-pill active" id="q1" onclick="setQuarter(1)">Q1</div>
            <div class="q-pill"       id="q2" onclick="setQuarter(2)">Q2</div>
            <div class="q-pill"       id="q3" onclick="setQuarter(3)">Q3</div>
            <div class="q-pill"       id="q4" onclick="setQuarter(4)">Q4</div>
            <div class="q-pill"       id="qot" onclick="setQuarter(5)" style="font-size:0.6rem;">OT</div>
        </div>

        <div class="timer-block">
            <div class="timer-label">Game clock</div>
            <div class="timer-display" id="timerDisplay">10:00</div>
            <div class="timer-controls">
                <button class="timer-btn go"   id="btnStart"  onclick="startTimer()">Start</button>
                <button class="timer-btn stop" id="btnStop"   onclick="stopTimer()" style="display:none;">Stop</button>
                <button class="timer-btn"      id="btnReset"  onclick="resetTimer()">Reset</button>
            </div>
        </div>
    </div>

    <!-- SCORE AREA -->
    <div class="score-area">

        <!-- HOME TEAM -->
        <div class="team-panel">
            <div class="team-label"><?php echo $home_team; ?></div>
            <span class="bonus-badge" id="h_bonus">BONUS</span>
            <div class="score-number" id="h_disp">0</div>

            <div class="point-btns">
                <button class="pt-btn" onclick="addPoints('h', 1)">+1</button>
                <button class="pt-btn" onclick="addPoints('h', 2)">+2</button>
                <button class="pt-btn" onclick="addPoints('h', 3)">+3</button>
            </div>
            <button class="pt-btn minus" style="width:100%;" onclick="addPoints('h', -1)">− Undo</button>

            <div class="stats-row">
                <div class="stat-box">
                    <div class="stat-label">Fouls</div>
                    <div class="stat-value" id="h_fouls">0</div>
                    <div class="stat-controls">
                        <button class="mini-btn" onclick="addFoul('h', 1)">+</button>
                        <button class="mini-btn" onclick="addFoul('h', -1)">−</button>
                    </div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Timeouts</div>
                    <div class="stat-value" id="h_to">3</div>
                    <div class="stat-controls">
                        <button class="mini-btn" onclick="addTimeout('h', -1)">Use</button>
                        <button class="mini-btn" onclick="addTimeout('h', 1)">+</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- CENTER -->
        <div class="center-col">
            <div class="divider-line"></div>
            <div class="vs-text">VS</div>
            <div class="divider-line"></div>
        </div>

        <!-- AWAY TEAM -->
        <div class="team-panel">
            <div class="team-label"><?php echo $away_team; ?></div>
            <span class="bonus-badge" id="a_bonus">BONUS</span>
            <div class="score-number" id="a_disp">0</div>

            <div class="point-btns">
                <button class="pt-btn" onclick="addPoints('a', 1)">+1</button>
                <button class="pt-btn" onclick="addPoints('a', 2)">+2</button>
                <button class="pt-btn" onclick="addPoints('a', 3)">+3</button>
            </div>
            <button class="pt-btn minus" style="width:100%;" onclick="addPoints('a', -1)">− Undo</button>

            <div class="stats-row">
                <div class="stat-box">
                    <div class="stat-label">Fouls</div>
                    <div class="stat-value" id="a_fouls">0</div>
                    <div class="stat-controls">
                        <button class="mini-btn" onclick="addFoul('a', 1)">+</button>
                        <button class="mini-btn" onclick="addFoul('a', -1)">−</button>
                    </div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">Timeouts</div>
                    <div class="stat-value" id="a_to">3</div>
                    <div class="stat-controls">
                        <button class="mini-btn" onclick="addTimeout('a', -1)">Use</button>
                        <button class="mini-btn" onclick="addTimeout('a', 1)">+</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- QUARTER SCORE TABLE -->
    <div class="period-table-wrap">
        <table class="period-table">
            <thead>
                <tr>
                    <th class="team-col">Team</th>
                    <th>Q1</th>
                    <th>Q2</th>
                    <th>Q3</th>
                    <th>Q4</th>
                    <th>OT</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="team-col"><?php echo $home_team; ?></td>
                    <td id="h_q1">—</td>
                    <td id="h_q2">—</td>
                    <td id="h_q3">—</td>
                    <td id="h_q4">—</td>
                    <td id="h_qot">—</td>
                    <td class="total-col" id="h_total">0</td>
                </tr>
                <tr>
                    <td class="team-col"><?php echo $away_team; ?></td>
                    <td id="a_q1">—</td>
                    <td id="a_q2">—</td>
                    <td id="a_q3">—</td>
                    <td id="a_q4">—</td>
                    <td id="a_qot">—</td>
                    <td class="total-col" id="a_total">0</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- FINISH -->
    <form action="../match_system/save_result.php" method="POST" id="finishForm"
          onsubmit="prepareSubmit(event)">
        <input type="hidden" name="match_id" value="<?php echo $match_id; ?>">
        <input type="hidden" name="h_score"  id="h_val" value="0">
        <input type="hidden" name="a_score"  id="a_val" value="0">
        <input type="hidden" name="h_id"     value="<?php echo $home_id; ?>">
        <input type="hidden" name="a_id"     value="<?php echo $away_id; ?>">

        <div class="finish-strip">
            <button type="button" class="btn-reset-all" onclick="resetAll()">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
            </button>
            <button type="submit" class="btn-finish">
                <i class="bi bi-flag-fill me-1"></i> Save Result &amp; Finish
            </button>
        </div>
    </form>

</div><!-- /scoreboard -->

<script>
    // ── STATE ───────────────────────────────────────────────
    const state = {
        h: 0, a: 0,
        h_fouls: 0, a_fouls: 0,
        h_to: 3,    a_to: 3,
        quarter: 1,
        quarterScores: {
            h: [null, null, null, null, null],
            a: [null, null, null, null, null]
        },
        quarterStart: { h: 0, a: 0 }
    };

    // ── TIMER ────────────────────────────────────────────────
    let timerSeconds  = 600;   // 10:00
    let timerRunning  = false;
    let timerInterval = null;

    const QUARTER_DURATION = 600;

    function formatTime(s) {
        const m = Math.floor(s / 60);
        const sec = s % 60;
        return String(m).padStart(2, '0') + ':' + String(sec).padStart(2, '0');
    }

    function updateTimerDisplay() {
        const el = document.getElementById('timerDisplay');
        el.textContent = formatTime(timerSeconds);
        el.className = 'timer-display';
        if (timerRunning)       el.classList.add('running');
        if (timerSeconds <= 30) el.classList.add('warning');
    }

    function startTimer() {
        if (timerRunning) return;
        timerRunning = true;
        document.getElementById('btnStart').style.display = 'none';
        document.getElementById('btnStop').style.display  = '';
        timerInterval = setInterval(() => {
            if (timerSeconds <= 0) {
                stopTimer();
                buzzEnd();
                return;
            }
            timerSeconds--;
            updateTimerDisplay();
        }, 1000);
    }

    function stopTimer() {
        timerRunning = false;
        clearInterval(timerInterval);
        document.getElementById('btnStart').style.display = '';
        document.getElementById('btnStop').style.display  = 'none';
        updateTimerDisplay();
    }

    function resetTimer() {
        stopTimer();
        timerSeconds = QUARTER_DURATION;
        updateTimerDisplay();
    }

    function buzzEnd() {
        document.getElementById('timerDisplay').style.color = '#ef4444';
        setTimeout(() => updateTimerDisplay(), 2000);
    }

    // ── QUARTERS ────────────────────────────────────────────
    function setQuarter(q) {
        // Lock previous quarter score
        const prev = state.quarter;
        const qKey = ['q1','q2','q3','q4','qot'][prev - 1];
        state.quarterScores.h[prev - 1] = state.h - state.quarterStart.h;
        state.quarterScores.a[prev - 1] = state.a - state.quarterStart.a;
        state.quarterStart.h = state.h;
        state.quarterStart.a = state.a;

        // Mark pill
        document.getElementById(['q1','q2','q3','q4','qot'][prev - 1]).className = 'q-pill done';
        state.quarter = q;
        document.getElementById(['q1','q2','q3','q4','qot'][q - 1]).className = 'q-pill active';

        resetTimer();
        renderPeriodTable();

        // Reset fouls each quarter
        state.h_fouls = 0;
        state.a_fouls = 0;
        renderFouls();
    }

    // ── SCORING ──────────────────────────────────────────────
    function addPoints(team, pts) {
        state[team] = Math.max(0, state[team] + pts);
        document.getElementById(team + '_disp').textContent = state[team];
        document.getElementById(team + '_val').value        = state[team];
        document.getElementById(team + '_total').textContent = state[team];
        animateScore(team + '_disp');
    }

    function animateScore(id) {
        const el = document.getElementById(id);
        el.style.color = '#FFB800';
        setTimeout(() => { el.style.color = ''; }, 300);
    }

    // ── FOULS ────────────────────────────────────────────────
    function addFoul(team, val) {
        state[team + '_fouls'] = Math.max(0, state[team + '_fouls'] + val);
        renderFouls();
    }

    function renderFouls() {
        ['h','a'].forEach(t => {
            const f  = state[t + '_fouls'];
            const el = document.getElementById(t + '_fouls');
            el.textContent = f;
            el.className = 'stat-value' + (f >= 5 ? ' foul-out' : f >= 3 ? ' foul-warn' : '');
            document.getElementById(t + '_bonus').classList.toggle('show', f >= 7);
        });
    }

    // ── TIMEOUTS ─────────────────────────────────────────────
    function addTimeout(team, val) {
        state[team + '_to'] = Math.max(0, Math.min(5, state[team + '_to'] + val));
        document.getElementById(team + '_to').textContent = state[team + '_to'];
    }

    // ── PERIOD TABLE ─────────────────────────────────────────
    function renderPeriodTable() {
        const keys = ['q1','q2','q3','q4','qot'];
        ['h','a'].forEach(t => {
            let total = 0;
            state.quarterScores[t].forEach((sc, i) => {
                const cell = document.getElementById(t + '_' + keys[i]);
                if (sc !== null) {
                    cell.textContent = sc;
                    total += sc;
                } else {
                    cell.textContent = '—';
                }
            });
            document.getElementById(t + '_total').textContent = state[t];
        });
    }

    // ── RESET ALL ────────────────────────────────────────────
    function resetAll() {
        if (!confirm('Reset the entire scoreboard?')) return;
        state.h = 0; state.a = 0;
        state.h_fouls = 0; state.a_fouls = 0;
        state.h_to = 3; state.a_to = 3;
        state.quarter = 1;
        state.quarterScores = { h: [null,null,null,null,null], a: [null,null,null,null,null] };
        state.quarterStart = { h: 0, a: 0 };

        ['h','a'].forEach(t => {
            document.getElementById(t + '_disp').textContent = 0;
            document.getElementById(t + '_val').value        = 0;
            document.getElementById(t + '_to').textContent   = 3;
        });

        ['q1','q2','q3','q4','qot'].forEach(id => {
            document.getElementById(id).className = 'q-pill';
        });
        document.getElementById('q1').className = 'q-pill active';

        resetTimer();
        renderFouls();
        renderPeriodTable();
    }

    // ── SUBMIT ───────────────────────────────────────────────
    function prepareSubmit(e) {
        const hScore = state.h;
        const aScore = state.a;
        if (!confirm(`Finalize?\n\n<?php echo $home_team; ?>: ${hScore}\n<?php echo $away_team; ?>: ${aScore}\n\nThis cannot be undone.`)) {
            e.preventDefault();
            return;
        }
        document.getElementById('h_val').value = hScore;
        document.getElementById('a_val').value = aScore;
    }

    // ── INIT ─────────────────────────────────────────────────
    updateTimerDisplay();
</script>
</body>
</html>