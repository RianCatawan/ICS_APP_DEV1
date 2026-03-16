
<?php
session_start();
include "db.php";

if (!isset($_SESSION['username'])) {
    die("Error: You must be logged in to reserve a court.");
}

$username = $_SESSION['username'];

// 1. Capture the team_id strictly from the URL (Profile) or POST
$team_id = $_POST['team_id'] ?? $_GET['team_id'] ?? '';

// 2. Fetch the Team Name just for display purposes
$team_name = "Unknown Team";
if (!empty($team_id)) {
    $stmt_name = $conn->prepare("SELECT team_name FROM teams WHERE id = ?");
    $stmt_name->bind_param("i", $team_id);
    $stmt_name->execute();
    $result_name = $stmt_name->get_result();
    if ($row = $result_name->fetch_assoc()) {
        $team_name = $row['team_name'];
    }
} else {
    // If no ID is found, redirect back to profile to pick one
    header("Location: profile.php?error=select_team_first");
    exit();
}

// 3. Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $reservation_date = $_POST['reservation_date'];
    $selected_time = $_POST['selected_time'];

    if (empty($team_id) || empty($reservation_date) || empty($selected_time)) {
        echo "<script>alert('Please fill in all fields!'); window.history.back();</script>";
        exit();
    }

    // Update Active Team for the player
    $updateActive = $conn->prepare("UPDATE players SET active_team_id = ? WHERE student_id = ?");
    $updateActive->bind_param("is", $team_id, $username);
    $updateActive->execute();

    // Insert Reservation
    $stmt = $conn->prepare("INSERT INTO reservations (team_id, username, reservation_date, selected_time, status) VALUES (?, ?, ?, ?, 'open')");
    $stmt->bind_param("isss", $team_id, $username, $reservation_date, $selected_time);

    if ($stmt->execute()) {
        echo "<script>
                alert('Reservation Successful!');
                window.location.href = 'matchmaking.php';
              </script>";
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reserve Court</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #0d47a1; font-family: 'Segoe UI', sans-serif; color: white; padding: 40px; }
        .reservation-container { max-width: 800px; margin: 0 auto; background: rgba(0,0,0,0.3); padding: 30px; border-radius: 15px; border: 1px solid rgba(255,255,255,0.1); }
        .form-label { font-weight: bold; color: #FFD700; text-transform: uppercase; margin-bottom: 8px; display: block; }
        
        /* Fixed Team Display */
        .fixed-team-box { background: rgba(255, 215, 0, 0.1); border: 2px dashed #FFD700; padding: 15px; border-radius: 8px; text-align: center; margin-bottom: 25px; }
        .fixed-team-box h4 { margin: 0; color: #FFD700; font-weight: 800; }

        .time-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 10px; margin-top: 15px; }
        .time-slot { background: rgba(0, 0, 0, 0.4); border: 1px solid rgba(255, 255, 255, 0.2); padding: 12px; text-align: center; border-radius: 8px; cursor: pointer; transition: 0.3s; }
        .time-slot.selected { background: #FFD700; color: #000; font-weight: bold; }
        
        .form-control { border-radius: 5px; font-weight: 500; }
        .btn-reserve { background: #FFD700; color: #000; font-weight: 800; padding: 15px; margin-top: 30px; border: none; width: 100%; border-radius: 8px; font-size: 1.2rem; }
    </style>
</head>
<body>

<div class="container reservation-container">
    <h2 class="text-center mb-4" style="color: #FFD700; font-weight: 900;">COURT RESERVATION</h2>
    
    <div class="fixed-team-box">
        <small class="text-white-50">RESERVING FOR TEAM</small>
        <h4><?php echo strtoupper($team_name); ?> (ID: <?php echo $team_id; ?>)</h4>
    </div>

    <form action="" method="POST">
        <input type="hidden" name="team_id" value="<?php echo htmlspecialchars($team_id); ?>">

        <div class="row">
            <div class="col-md-6 mb-4">
                <label class="form-label">Step 1: Reservation Date</label>
                <input type="date" name="reservation_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="col-md-6 mb-4">
                <label class="form-label">Confirmed Time Slot</label>
                <input type="text" name="selected_time" id="finalTime" class="form-control" placeholder="Select a slot below" readonly required>
            </div>
        </div>

        <label class="form-label">Step 2: Pick a Match Slot</label>
        <div class="time-grid" id="timeGrid"></div>

        <div class="mt-4 pt-3 border-top border-secondary">
            <label class="form-label">Or Use Custom Time</label>
            <div class="row g-2">
                <div class="col-auto"><input type="time" id="customTimeInput" class="form-control"></div>
                <div class="col-auto"><button type="button" class="btn btn-outline-warning" onclick="useCustomTime()">Apply</button></div>
            </div>
        </div>

        <button type="submit" class="btn-reserve">CONFIRM RESERVATION</button>
       <a href="matchmaking.php">SELECT MATCH</a>
    </form>
</div>

<script>
    function generateSlots() {
        const grid = document.getElementById('timeGrid');
        const startHour = 4; const endHour = 22; const durationMin = 90;
        let current = new Date();
        current.setHours(startHour, 0, 0);
        const endLimit = new Date();
        endLimit.setHours(endHour, 0, 0);

        while (current < endLimit) {
            let startTime = current.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            current.setMinutes(current.getMinutes() + durationMin);
            let endTime = current.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

            if (current <= endLimit) {
                const slotDiv = document.createElement('div');
                slotDiv.className = 'time-slot';
                slotDiv.innerHTML = `${startTime}<br>to<br>${endTime}`;
                slotDiv.onclick = function() {
                    document.querySelectorAll('.time-slot').forEach(s => s.classList.remove('selected'));
                    this.classList.add('selected');
                    document.getElementById('finalTime').value = startTime + " - " + endTime;
                };
                grid.appendChild(slotDiv);
            }
        }
    }

    function useCustomTime() {
        const timeVal = document.getElementById('customTimeInput').value;
        if(timeVal) {
            const [h, m] = timeVal.split(':');
            const ampm = h >= 12 ? 'PM' : 'AM';
            const displayH = h % 12 || 12;
            const formatted = `${displayH}:${m} ${ampm}`;
            document.getElementById('finalTime').value = formatted;
            document.querySelectorAll('.time-slot').forEach(slot => slot.classList.remove('selected'));
        }
    }
    window.onload = generateSlots;
</script>
</body>
</html>