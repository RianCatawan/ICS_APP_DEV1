<?php
session_start();
require_once __DIR__ . '/../database_config/db.php';

if (!isset($_SESSION['username'])) {
    header("Location: ../authentication/login.php");
    exit();
}

$sid = $_SESSION['username'];

// TEAM ACTIVATION LOGIC
$team_id = isset($_REQUEST['team_id']) ? intval($_REQUEST['team_id']) : 0;

if ($team_id > 0) {
    $update_active = $conn->prepare("UPDATE players SET active_team_id = ? WHERE student_id = ?");
    $update_active->bind_param("is", $team_id, $sid);
    $update_active->execute();
} else {
    $check_active = $conn->prepare("SELECT active_team_id FROM players WHERE student_id = ?");
    $check_active->bind_param("s", $sid);
    $check_active->execute();
    $res = $check_active->get_result()->fetch_assoc();
    $team_id = intval($res['active_team_id'] ?? 0);
}

if ($team_id <= 0) {
    echo "<script>alert('Please select a team from your profile first!'); window.location.href='profile.php';</script>";
    exit();
}

// FETCH TEAM NAME
$team_name = "Unknown Team";
$stmt_name = $conn->prepare("SELECT team_name FROM teams WHERE id = ?");
$stmt_name->bind_param("i", $team_id);
$stmt_name->execute();
if ($row_n = $stmt_name->get_result()->fetch_assoc()) {
    $team_name = $row_n['team_name'];
}

// FETCH EXISTING RESERVATIONS
$booked_slots = [];
$check_res = $conn->query("SELECT reservation_date, selected_time FROM reservations WHERE status != 'cancelled'");
while ($row = $check_res->fetch_assoc()) {
    $booked_slots[] = $row['reservation_date'] . "|" . $row['selected_time'];
}

// HANDLE BOOKING SUBMISSION
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['complete_res'])) {
    $reservation_date = $_POST['reservation_date'];
    $selected_time    = $_POST['selected_time'];

    $double_check = $conn->prepare("SELECT id FROM reservations WHERE reservation_date = ? AND selected_time = ? AND status != 'cancelled'");
    $double_check->bind_param("ss", $reservation_date, $selected_time);
    $double_check->execute();
    if ($double_check->get_result()->num_rows > 0) {
        echo "<script>alert('Error: This slot was just taken! Please choose another.'); window.history.back();</script>";
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO reservations (team_id, username, reservation_date, selected_time, status) VALUES (?, ?, ?, ?, 'open')");
    $stmt->bind_param("isss", $team_id, $sid, $reservation_date, $selected_time);

    if ($stmt->execute()) {
        echo "<script>alert('Reservation Successful!'); window.location.href = '../userManagement/profile.php';</script>";
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reserve Court | NBSC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@600;800&family=Plus+Jakarta+Sans:wght@400;700&display=swap');

        :root {
            --brand-primary: #0A192F;
            --brand-accent: #FFB800;
            --bg-body: #F4F7FA;
        }

        html, body {
            height: 100vh;
            overflow: hidden;
            background: var(--bg-body);
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .main-wrapper {
            height: 100vh;
            display: flex;
            flex-direction: column;
            padding: 15px;
        }

        .page-header {
            background: var(--brand-primary);
            padding: 12px 30px;
            border-radius: 12px;
            border-bottom: 4px solid var(--brand-accent);
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            flex-shrink: 0;
        }

        .booking-container {
            flex-grow: 1;
            display: flex;
            gap: 15px;
            min-height: 0;
        }

        /* ── LEFT CONFIG PANEL ── */
        .config-panel {
            flex: 0 0 380px;
            background: white;
            border: 3px solid var(--brand-primary);
            border-radius: 16px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 14px;
            overflow-y: auto;
        }

        .team-hero {
            background: #F8FAFC;
            border-left: 6px solid var(--brand-accent);
            padding: 14px;
            border-radius: 8px;
        }

        /* ── MODE TOGGLE ── */
        .mode-tabs {
            display: flex;
            border: 2px solid var(--brand-primary);
            border-radius: 10px;
            overflow: hidden;
        }

        .mode-tab {
            flex: 1;
            padding: 9px;
            text-align: center;
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            cursor: pointer;
            transition: 0.2s;
            background: transparent;
            color: var(--brand-primary);
            border: none;
            letter-spacing: 0.5px;
        }

        .mode-tab.active {
            background: var(--brand-primary);
            color: var(--brand-accent);
        }

        /* ── CUSTOM TIME SECTION ── */
        .custom-time-box {
            background: #F8FAFC;
            border: 2px solid #E2E8F0;
            border-radius: 12px;
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .custom-time-box label {
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            color: #64748B;
            margin-bottom: 3px;
            display: block;
        }

        .custom-time-box input[type="time"],
        .custom-time-box select {
            width: 100%;
            padding: 9px 12px;
            border: 2px solid #E2E8F0;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.9rem;
            color: var(--brand-primary);
            background: white;
            outline: none;
            transition: border-color 0.2s;
        }

        .custom-time-box input[type="time"]:focus,
        .custom-time-box select:focus {
            border-color: var(--brand-accent);
        }

        .custom-preview {
            background: var(--brand-primary);
            color: var(--brand-accent);
            border-radius: 8px;
            padding: 10px;
            text-align: center;
            font-family: 'Outfit';
            font-weight: 800;
            font-size: 0.95rem;
            letter-spacing: 0.5px;
            min-height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-apply-custom {
            background: var(--brand-accent);
            color: var(--brand-primary);
            border: 2px solid var(--brand-primary);
            font-family: 'Outfit';
            font-weight: 900;
            font-size: 0.8rem;
            padding: 9px;
            border-radius: 8px;
            width: 100%;
            text-transform: uppercase;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-apply-custom:hover {
            background: var(--brand-primary);
            color: var(--brand-accent);
        }

        /* ── SELECTED SLOT DISPLAY ── */
        .selected-slot-display {
            background: #f0fff4;
            border: 2px solid #22c55e;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 0.82rem;
            font-weight: 700;
            color: #166534;
            display: flex;
            align-items: center;
            gap: 8px;
            min-height: 44px;
        }

        /* ── BOTTOM BUTTONS ── */
        .btn-reserve {
            background: var(--brand-primary);
            color: var(--brand-accent);
            border: 3px solid var(--brand-primary);
            font-family: 'Outfit';
            font-weight: 900;
            padding: 14px;
            border-radius: 12px;
            width: 100%;
            text-transform: uppercase;
            cursor: pointer;
            transition: 0.2s;
            font-size: 1rem;
        }

        .btn-reserve:hover {
            background: var(--brand-accent);
            color: var(--brand-primary);
        }

        .btn-match {
            background: var(--brand-accent);
            color: var(--brand-primary);
            border: 3px solid var(--brand-primary);
            font-family: 'Outfit';
            font-weight: 900;
            padding: 13px;
            border-radius: 12px;
            width: 100%;
            text-transform: uppercase;
            text-decoration: none;
            text-align: center;
            display: block;
            transition: 0.3s;
        }

        .btn-match:hover {
            background: var(--brand-primary);
            color: var(--brand-accent);
        }

        /* ── RIGHT SLOTS PANEL ── */
        .slots-panel {
            flex-grow: 1;
            background: white;
            border: 3px solid #E2E8F0;
            border-radius: 16px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        .scrollable-grid {
            flex-grow: 1;
            overflow-y: auto;
            padding-right: 10px;
        }

        .time-slot {
            background: #f1f5f9;
            border: 2px solid transparent;
            padding: 12px;
            border-radius: 10px;
            cursor: pointer;
            transition: 0.2s;
            text-align: center;
            font-weight: 700;
            font-size: 0.82rem;
            position: relative;
        }

        .time-slot:hover:not(.booked) {
            border-color: var(--brand-accent);
            background: #fffbeb;
        }

        .time-slot.selected {
            border-color: var(--brand-accent);
            background: var(--brand-primary);
            color: var(--brand-accent);
        }

        .time-slot.booked {
            background: #E2E8F0;
            color: #94A3B8;
            cursor: not-allowed;
            border: 2px solid #CBD5E1;
            opacity: 0.6;
        }

        .time-slot.booked::after {
            content: "BOOKED";
            position: absolute;
            top: 5px;
            right: 5px;
            font-size: 0.5rem;
            background: #dc3545;
            color: white;
            padding: 2px 5px;
            border-radius: 4px;
        }

        /* custom-selected highlight in grid */
        .time-slot.custom-active {
            border-color: #3B82F6;
            background: #EFF6FF;
            color: #1e40af;
        }

        .panel-label {
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            color: #94a3b8;
            letter-spacing: 1px;
        }
    </style>
</head>
<body>
<div class="main-wrapper">
    <header class="page-header">
        <h2 style="color:var(--brand-accent); font-family:'Outfit'; font-weight:800; margin:0;">
            <i class="bi bi-calendar2-check me-2"></i>COURT RESERVATION
        </h2>
        <a href="javascript:history.back()" class="btn btn-sm btn-outline-light fw-bold">BACK</a>
    </header>

    <form method="POST" class="booking-container" id="resForm">
        <input type="hidden" name="team_id"        value="<?php echo htmlspecialchars($team_id); ?>">
        <input type="hidden" name="reservation_date" id="hdnDate" value="">
        <input type="hidden" name="selected_time"  id="hdnTime" value="">

        <!-- ══════════════ LEFT PANEL ══════════════ -->
        <div class="config-panel">

            <!-- Team -->
            <div class="team-hero">
                <small class="text-muted fw-bold" style="font-size:0.7rem;">RESERVING FOR</small>
                <h4 class="m-0" style="font-family:'Outfit'; font-weight:800; color:var(--brand-primary);">
                    <?php echo strtoupper($team_name); ?>
                </h4>
            </div>

            <!-- Date -->
            <div>
                <label class="panel-label mb-1">1. Select Date</label>
                <input type="date" id="resDate" class="form-control fw-bold"
                       required min="<?php echo date('Y-m-d'); ?>"
                       onchange="onDateChange()">
            </div>

            <!-- Mode toggle -->
            <div>
                <label class="panel-label mb-1">2. Choose Mode</label>
                <div class="mode-tabs">
                    <button type="button" class="mode-tab active" id="tabPreset" onclick="switchMode('preset')">
                        <i class="bi bi-grid-3x3-gap me-1"></i> Preset Slots
                    </button>
                    <button type="button" class="mode-tab" id="tabCustom" onclick="switchMode('custom')">
                        <i class="bi bi-sliders me-1"></i> Custom Time
                    </button>
                </div>
            </div>

            <!-- CUSTOM TIME BOX -->
            <div class="custom-time-box" id="customBox" style="display:none;">
                <div>
                    <label>Start Time</label>
                    <input type="time" id="customStart" value="08:00" onchange="updateCustomPreview()">
                </div>
                <div>
                    <label>Duration</label>
                    <select id="customDuration" onchange="updateCustomPreview()">
                        <option value="30">30 minutes</option>
                        <option value="60">1 hour</option>
                        <option value="90" selected>1.5 hours (default)</option>
                        <option value="120">2 hours</option>
                        <option value="150">2.5 hours</option>
                        <option value="180">3 hours</option>
                        <option value="240">4 hours</option>
                    </select>
                </div>
                <div>
                    <label>Time Preview</label>
                    <div class="custom-preview" id="customPreview">—</div>
                </div>
                <button type="button" class="btn-apply-custom" onclick="applyCustomTime()">
                    <i class="bi bi-check-circle me-1"></i> APPLY THIS TIME
                </button>
            </div>

            <!-- Selected slot display -->
            <div>
                <label class="panel-label mb-1">3. Your Selection</label>
                <div class="selected-slot-display" id="selectionDisplay">
                    <i class="bi bi-clock text-muted"></i>
                    <span id="selectionText" class="text-muted">No slot selected yet</span>
                </div>
            </div>

            <!-- Action buttons -->
            <a href="../challenges&scheduling/matchmaking.php" class="btn-match">
                <i class="bi bi-person-bounding-box me-1"></i> FIND MATCH
            </a>

            <button type="submit" name="complete_res" id="submitBtn" class="btn-reserve">
                <i class="bi bi-calendar-check me-1"></i> COMPLETE BOOKING
            </button>
        </div>

        <!-- ══════════════ RIGHT SLOTS PANEL ══════════════ -->
        <div class="slots-panel" id="slotsPanel">
            <h5 class="fw-bold mb-1">
                <i class="bi bi-clock me-1"></i> Available Slots
                <small class="text-muted fw-normal ms-2" style="font-size:0.75rem;">Click a slot to select it</small>
            </h5>
            <p class="text-muted small mb-3" id="slotNote">Select a date to view available slots.</p>
            <div class="scrollable-grid">
                <div class="row g-2" id="timeGrid"></div>
            </div>
        </div>
    </form>
</div>

<script>
    const bookedData  = <?php echo json_encode($booked_slots); ?>;
    let currentMode   = 'preset';
    let selectedDate  = '';
    let selectedSlot  = '';

    // ── Helpers ──────────────────────────────────────────
    function pad2(n) { return String(n).padStart(2, '0'); }

    function minutesToDisplay(totalMin) {
        const h   = Math.floor(totalMin / 60);
        const m   = totalMin % 60;
        const ampm = h >= 12 ? 'PM' : 'AM';
        const h12 = h % 12 || 12;
        return h12 + ':' + pad2(m) + ' ' + ampm;
    }

    function timeStrToMinutes(t) {            // "08:30" → 510
        const [h, m] = t.split(':').map(Number);
        return h * 60 + m;
    }

    function formatSlotString(startMin, endMin) {
        return minutesToDisplay(startMin) + ' - ' + minutesToDisplay(endMin);
    }

    // ── Mode switch ──────────────────────────────────────
    function switchMode(mode) {
        currentMode = mode;
        document.getElementById('tabPreset').classList.toggle('active', mode === 'preset');
        document.getElementById('tabCustom').classList.toggle('active', mode === 'custom');
        document.getElementById('customBox').style.display  = mode === 'custom' ? 'flex' : 'none';
        document.getElementById('slotsPanel').style.display = mode === 'preset' ? 'flex' : 'none';

        // Clear selection when switching modes
        clearSelection();

        if (mode === 'preset' && selectedDate) generateSlots();
        if (mode === 'custom') updateCustomPreview();
    }

    // ── Date change ──────────────────────────────────────
    function onDateChange() {
        selectedDate = document.getElementById('resDate').value;
        document.getElementById('hdnDate').value = selectedDate;
        clearSelection();
        if (currentMode === 'preset') generateSlots();
        if (currentMode === 'custom') updateCustomPreview();
    }

    // ── Clear selection ───────────────────────────────────
    function clearSelection() {
        selectedSlot = '';
        document.getElementById('hdnTime').value = '';
        document.getElementById('selectionText').textContent = 'No slot selected yet';
        document.getElementById('selectionText').style.color = '';
        document.getElementById('selectionDisplay').style.borderColor = '#22c55e';
        document.querySelectorAll('.time-slot').forEach(s => s.classList.remove('selected','custom-active'));
    }

    // ── Set selection ─────────────────────────────────────
    function setSelection(slotString, isCustom) {
        if (!selectedDate) { alert('Please select a date first.'); return false; }

        // Check if booked
        const key = selectedDate + '|' + slotString;
        if (bookedData.includes(key)) {
            alert('This time slot is already booked. Please choose another.');
            return false;
        }

        selectedSlot = slotString;
        document.getElementById('hdnTime').value = slotString;
        document.getElementById('hdnDate').value = selectedDate;

        const disp = document.getElementById('selectionDisplay');
        document.getElementById('selectionText').textContent = '📅 ' + formatDate(selectedDate) + '  ·  ⏰ ' + slotString;
        document.getElementById('selectionText').style.color = '#166534';
        disp.style.borderColor = isCustom ? '#3B82F6' : '#22c55e';
        disp.style.background  = isCustom ? '#EFF6FF' : '#f0fff4';
        return true;
    }

    function formatDate(d) {
        const dt = new Date(d + 'T00:00:00');
        return dt.toLocaleDateString('en-US', { month:'short', day:'numeric', year:'numeric' });
    }

    // ── Preset slot grid ─────────────────────────────────
    function generateSlots() {
        const grid = document.getElementById('timeGrid');
        grid.innerHTML = '';
        document.getElementById('slotNote').textContent = selectedDate
            ? 'Showing 90-min preset slots. Booked slots are greyed out.'
            : 'Select a date to view available slots.';

        if (!selectedDate) return;

        const startHour = 4, endHour = 22, durationMin = 90;
        let currentMin = startHour * 60;
        const endMin   = endHour  * 60;

        while (currentMin + durationMin <= endMin) {
            const slotEnd    = currentMin + durationMin;
            const slotString = formatSlotString(currentMin, slotEnd);
            const isBooked   = bookedData.includes(selectedDate + '|' + slotString);

            const col = document.createElement('div');
            col.className = 'col-4';

            const slotDiv = document.createElement('div');
            slotDiv.className = 'time-slot' + (isBooked ? ' booked' : '');
            slotDiv.innerHTML = minutesToDisplay(currentMin)
                + '<br><span style="font-size:0.6rem;opacity:0.7;">TO</span><br>'
                + minutesToDisplay(slotEnd);

            if (!isBooked) {
                slotDiv.onclick = function () {
                    document.querySelectorAll('.time-slot').forEach(s => s.classList.remove('selected', 'custom-active'));
                    this.classList.add('selected');
                    setSelection(slotString, false);
                };
            }

            col.appendChild(slotDiv);
            grid.appendChild(col);
            currentMin += durationMin;
        }
    }

    // ── Custom time preview ───────────────────────────────
    function updateCustomPreview() {
        const startVal = document.getElementById('customStart').value;  // "HH:MM"
        const dur      = parseInt(document.getElementById('customDuration').value);
        if (!startVal) return;

        const startMin = timeStrToMinutes(startVal);
        const endMin   = startMin + dur;
        const preview  = formatSlotString(startMin, endMin);

        document.getElementById('customPreview').textContent = preview;
    }

    // ── Apply custom time ─────────────────────────────────
    function applyCustomTime() {
        if (!selectedDate) { alert('Please select a date first.'); return; }

        const startVal = document.getElementById('customStart').value;
        const dur      = parseInt(document.getElementById('customDuration').value);
        const startMin = timeStrToMinutes(startVal);
        const endMin   = startMin + dur;
        const slotStr  = formatSlotString(startMin, endMin);

        if (setSelection(slotStr, true)) {
            // Visual feedback on the preview box
            const prev = document.getElementById('customPreview');
            prev.style.background = '#3B82F6';
            prev.textContent = '✓ ' + slotStr + ' — Applied!';
            setTimeout(() => {
                prev.style.background = '';
                prev.textContent = slotStr;
            }, 1500);
        }
    }

    // ── Form submit guard ─────────────────────────────────
    document.getElementById('resForm').addEventListener('submit', function(e) {
        if (!document.getElementById('hdnDate').value || !document.getElementById('hdnTime').value) {
            e.preventDefault();
            alert('Please select a date and a time slot before booking.');
        }
    });

    // ── Init ──────────────────────────────────────────────
    window.onload = function() {
        updateCustomPreview();
        // slots panel hidden by default in custom mode; visible in preset
        document.getElementById('slotsPanel').style.display = 'flex';
    };
</script>
</body>
</html>