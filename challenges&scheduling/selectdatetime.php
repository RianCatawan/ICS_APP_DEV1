<?php
session_start();
include(__DIR__ . '/../database&config/db.php');

if (!isset($_SESSION['username'])) {
    die("Error: You must be logged in.");
}

$username = $_SESSION['username'];
$team_id = $_POST['team_id'] ?? $_GET['team_id'] ?? '';

// 1. Fetch Existing Reservations to prevent double-booking
$booked_slots = [];
$check_res = $conn->query("SELECT reservation_date, selected_time FROM reservations WHERE status != 'cancelled'");
while($row = $check_res->fetch_assoc()) {
    // We store them in a JS-friendly format: "YYYY-MM-DD|TimeSlot"
    $booked_slots[] = $row['reservation_date'] . "|" . $row['selected_time'];
}

$team_name = "Unknown Team";
if (!empty($team_id)) {
    $stmt_name = $conn->prepare("SELECT team_name FROM teams WHERE id = ?");
    $stmt_name->bind_param("i", $team_id);
    $stmt_name->execute();
    $result_name = $stmt_name->get_result();
    if ($row = $result_name->fetch_assoc()) { $team_name = $row['team_name']; }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['complete_res'])) {
    $reservation_date = $_POST['reservation_date'];
    $selected_time = $_POST['selected_time'];

    // Final Server-side check to prevent bypass
    $double_check = $conn->prepare("SELECT id FROM reservations WHERE reservation_date = ? AND selected_time = ? AND status != 'cancelled'");
    $double_check->bind_param("ss", $reservation_date, $selected_time);
    $double_check->execute();
    if ($double_check->get_result()->num_rows > 0) {
        echo "<script>alert('Error: This slot was just taken! Please choose another.'); window.history.back();</script>";
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO reservations (team_id, username, reservation_date, selected_time, status) VALUES (?, ?, ?, ?, 'open')");
    $stmt->bind_param("isss", $team_id, $username, $reservation_date, $selected_time);
    if ($stmt->execute()) {
        echo "<script>alert('Reservation Successful!'); window.location.href = 'matchmaking.php';</script>";
        exit();
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

        /* LEFT PANEL */
        .config-panel { flex: 0 0 380px; background: white; border: 3px solid var(--brand-primary); border-radius: 16px; padding: 20px; display: flex; flex-direction: column; }
        .team-hero { background: #F8FAFC; border-left: 6px solid var(--brand-accent); padding: 15px; border-radius: 8px; margin-bottom: 20px; }

        /* RIGHT PANEL */
        .slots-panel { flex-grow: 1; background: white; border: 3px solid #E2E8F0; border-radius: 16px; padding: 20px; display: flex; flex-direction: column; min-height: 0; }
        .scrollable-grid { flex-grow: 1; overflow-y: auto; padding-right: 10px; }

        /* TIME SLOTS STYLES */
        .time-slot { background: #f1f5f9; border: 2px solid transparent; padding: 12px; border-radius: 10px; cursor: pointer; transition: 0.2s; text-align: center; font-weight: 700; font-size: 0.85rem; position: relative; }
        .time-slot.selected { border-color: var(--brand-accent); background: var(--brand-primary); color: var(--brand-accent); }
        
        /* BOOKED STATE */
        .time-slot.booked { background: #E2E8F0; color: #94A3B8; cursor: not-allowed; border: 2px solid #CBD5E1; opacity: 0.6; }
        .time-slot.booked::after { content: "BOOKED"; position: absolute; top: 5px; right: 5px; font-size: 0.5rem; background: #dc3545; color: white; padding: 2px 5px; border-radius: 4px; }

        .btn-reserve { background: var(--brand-primary); color: var(--brand-accent); border: 3px solid var(--brand-primary); font-family: 'Outfit'; font-weight: 900; padding: 15px; border-radius: 12px; width: 100%; margin-top: auto; text-transform: uppercase; }
        .btn-reserve:disabled { opacity: 0.5; cursor: not-allowed; }
    </style>
</head>
<body>

<div class="main-wrapper">
    <header class="page-header">
        <h2 style="color:var(--brand-accent); font-family:'Outfit'; font-weight:800; margin:0;">COURT RESERVATION</h2>
        <a href="javascript:history.back()" class="btn btn-sm btn-outline-light">BACK</a>
    </header>

    <form method="POST" class="booking-container" id="resForm">
        <input type="hidden" name="team_id" value="<?= htmlspecialchars($team_id); ?>">

        <div class="config-panel">
            <div class="team-hero">
                <small class="text-muted fw-bold">RESERVING FOR:</small>
                <h4 class="m-0" style="font-family:'Outfit'; font-weight:800; color:var(--brand-primary);"><?= strtoupper($team_name); ?></h4>
            </div>

            <div class="mb-3">
                <label class="fw-bold small text-uppercase mb-1">1. Select Date</label>
                <input type="date" name="reservation_date" id="resDate" class="form-control fw-bold" required min="<?= date('Y-m-d'); ?>" onchange="generateSlots()">
            </div>

            <div class="mb-3">
                <label class="fw-bold small text-uppercase mb-1">2. Selected Slot</label>
                <input type="text" name="selected_time" id="finalTime" class="form-control bg-light fw-bold text-center" placeholder="Choose a slot →" readonly required>
            </div>

            <button type="submit" name="complete_res" id="submitBtn" class="btn-reserve">COMPLETE BOOKING</button>
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
    // Pass PHP data to JS
    const bookedData = <?= json_encode($booked_slots); ?>;

    function generateSlots() {
        const grid = document.getElementById('timeGrid');
        const selectedDate = document.getElementById('resDate').value;
        const finalTimeInput = document.getElementById('finalTime');
        
        grid.innerHTML = ""; // Clear grid
        finalTimeInput.value = ""; // Reset selection on date change

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
            
            // Check if this date|time combination exists in bookedData
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

    // Initial load
    window.onload = generateSlots;
</script>
</body>
</html>