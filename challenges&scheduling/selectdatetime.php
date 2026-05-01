<?php
session_start();
// 1. DATABASE CONNECTION (Path: Outside userManagement, inside database_config)
require_once __DIR__ . '/../database_config/db.php';

// 2. SECURITY & IDENTITY CHECK
if (!isset($_SESSION['username'])) {
    header("Location: ../authentication/login.php");
    exit();
}

$sid = $_SESSION['username'];

// 3. TEAM ACTIVATION LOGIC
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

// 4. FINAL VALIDATION
if ($team_id <= 0) {
    echo "<script>alert('Please select a team from your profile first!'); window.location.href='profile.php';</script>";
    exit();
}

// 5. FETCH TEAM NAME
$team_name = "Unknown Team";
$stmt_name = $conn->prepare("SELECT team_name FROM teams WHERE id = ?");
$stmt_name->bind_param("i", $team_id);
$stmt_name->execute();
$result_name = $stmt_name->get_result();
if ($row_n = $result_name->fetch_assoc()) { 
    $team_name = $row_n['team_name']; 
}

// 6. FETCH EXISTING RESERVATIONS
$booked_slots = [];
$check_res = $conn->query("SELECT reservation_date, selected_time FROM reservations WHERE status != 'cancelled'");
while($row = $check_res->fetch_assoc()) {
    $booked_slots[] = $row['reservation_date'] . "|" . $row['selected_time'];
}

// 7. HANDLE THE BOOKING SUBMISSION
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['complete_res'])) {
    $reservation_date = $_POST['reservation_date'];
    $selected_time = $_POST['selected_time'];

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
        :root { --brand-primary: #0A192F; --brand-accent: #FFB800; --bg-body: #F4F7FA; }
        html, body { height: 100vh; overflow: hidden; background: var(--bg-body); font-family: 'Plus Jakarta Sans', sans-serif; }
        .main-wrapper { height: 100vh; display: flex; flex-direction: column; padding: 15px; }
        .page-header { background: var(--brand-primary); padding: 12px 30px; border-radius: 12px; border-bottom: 4px solid var(--brand-accent); display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-shrink: 0; }
        .booking-container { flex-grow: 1; display: flex; gap: 15px; min-height: 0; }
        .config-panel { flex: 0 0 380px; background: white; border: 3px solid var(--brand-primary); border-radius: 16px; padding: 20px; display: flex; flex-direction: column; }
        .team-hero { background: #F8FAFC; border-left: 6px solid var(--brand-accent); padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .slots-panel { flex-grow: 1; background: white; border: 3px solid #E2E8F0; border-radius: 16px; padding: 20px; display: flex; flex-direction: column; min-height: 0; }
        .scrollable-grid { flex-grow: 1; overflow-y: auto; padding-right: 10px; }
        .time-slot { background: #f1f5f9; border: 2px solid transparent; padding: 12px; border-radius: 10px; cursor: pointer; transition: 0.2s; text-align: center; font-weight: 700; font-size: 0.85rem; position: relative; }
        .time-slot.selected { border-color: var(--brand-accent); background: var(--brand-primary); color: var(--brand-accent); }
        .time-slot.booked { background: #E2E8F0; color: #94A3B8; cursor: not-allowed; border: 2px solid #CBD5E1; opacity: 0.6; }
        .time-slot.booked::after { content: "BOOKED"; position: absolute; top: 5px; right: 5px; font-size: 0.5rem; background: #dc3545; color: white; padding: 2px 5px; border-radius: 4px; }
        .btn-reserve { background: var(--brand-primary); color: var(--brand-accent); border: 3px solid var(--brand-primary); font-family: 'Outfit'; font-weight: 900; padding: 15px; border-radius: 12px; width: 100%; margin-top: 10px; text-transform: uppercase; }
        
        /* FIND MATCH BUTTON STYLE */
        .btn-match { background: var(--brand-accent); color: var(--brand-primary); border: 3px solid var(--brand-primary); font-family: 'Outfit'; font-weight: 900; padding: 15px; border-radius: 12px; width: 100%; margin-top: auto; text-transform: uppercase; text-decoration: none; text-align: center; display: block; transition: 0.3s; }
        .btn-match:hover { background: var(--brand-primary); color: var(--brand-accent); }
    </style>
</head>
<body>

<div class="main-wrapper">
    <header class="page-header">
        <h2 style="color:var(--brand-accent); font-family:'Outfit'; font-weight:800; margin:0;">COURT RESERVATION</h2>
        <a href="javascript:history.back()" class="btn btn-sm btn-outline-light">BACK</a>
    </header>

    <form method="POST" class="booking-container" id="resForm">
        <input type="hidden" name="team_id" value="<?php echo htmlspecialchars($team_id); ?>">

        <div class="config-panel">
            <div class="team-hero">
                <small class="text-muted fw-bold">RESERVING FOR:</small>
                <h4 class="m-0" style="font-family:'Outfit'; font-weight:800; color:var(--brand-primary);"><?php echo strtoupper($team_name); ?></h4>
            </div>

            <div class="mb-3">
                <label class="fw-bold small text-uppercase mb-1">1. Select Date</label>
                <input type="date" name="reservation_date" id="resDate" class="form-control fw-bold" required min="<?php echo date('Y-m-d'); ?>" onchange="generateSlots()">
            </div>

            <div class="mb-3">
                <label class="fw-bold small text-uppercase mb-1">2. Selected Slot</label>
                <input type="text" name="selected_time" id="finalTime" class="form-control bg-light fw-bold text-center" placeholder="Choose a slot →" readonly required>
            </div>

            <a href="../challenges&scheduling/matchmaking.php" class="btn-match">
                <i class="bi bi-person-bounding-box"></i> FIND MATCH
            </a>

            <button type="submit" name="complete_res" id="submitBtn" class="btn-reserve">
                COMPLETE BOOKING
            </button>
        </div>

        <div class="slots-panel">
            <h5 class="fw-bold mb-3"><i class="bi bi-clock"></i> Available Slots</h5>
            <div class="scrollable-grid">
                <div class="row g-2" id="timeGrid"></div>
            </div>
        </div>
    </form>
</div>

<script>
    const bookedData = <?php echo json_encode($booked_slots); ?>;

    function generateSlots() {
        const grid = document.getElementById('timeGrid');
        const selectedDate = document.getElementById('resDate').value;
        const finalTimeInput = document.getElementById('finalTime');
        
        grid.innerHTML = ""; 
        finalTimeInput.value = ""; 

        if(!selectedDate) {
            grid.innerHTML = "<p class='text-center mt-5 text-muted'>Please select a date first.</p>";
            return;
        }

        const startHour = 4; const endHour = 22; const durationMin = 90;
        let current = new Date();
        current.setHours(startHour, 0, 0);
        const endLimit = new Date();
        endLimit.setHours(endHour, 0, 0);

        while (current < endLimit) {
            let startTime = current.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            current.setMinutes(current.getMinutes() + durationMin);
            let endTime = current.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            let slotString = startTime + " - " + endTime;
            
            let isBooked = bookedData.includes(selectedDate + "|" + slotString);

            const col = document.createElement('div');
            col.className = 'col-4';
            
            const slotDiv = document.createElement('div');
            slotDiv.className = `time-slot ${isBooked ? 'booked' : ''}`;
            slotDiv.innerHTML = `${startTime}<br><span style="font-size:0.6rem">TO</span> ${endTime}`;
            
            if(!isBooked) {
                slotDiv.onclick = function() {
                    document.querySelectorAll('.time-slot').forEach(s => s.classList.remove('selected'));
                    this.classList.add('selected');
                    finalTimeInput.value = slotString;
                };
            }

            col.appendChild(slotDiv);
            grid.appendChild(col);
        }
    }
    window.onload = generateSlots;
</script>
</body>
</html>