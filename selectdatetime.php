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
    header("Location: profile.php?error=select_team_first");
    exit();
}

// 3. Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm'])) {
    $reservation_date = $_POST['reservation_date'];
    $selected_time = $_POST['selected_time'];

    if (empty($team_id) || empty($reservation_date) || empty($selected_time)) {
        echo "<script>alert('Please fill in all fields!'); window.history.back();</script>";
        exit();
    }

    $updateActive = $conn->prepare("UPDATE players SET active_team_id = ? WHERE student_id = ?");
    $updateActive->bind_param("is", $team_id, $username);
    $updateActive->execute();

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reserve Court | NBSC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --nbsc-blue: #0d47a1;
            --nbsc-gold: #FFD700;
            --glass-bg: rgba(255, 255, 255, 0.95);
        }

        body { 
            background-image: url('Covered Court.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            font-family: 'Segoe UI', sans-serif;
            color: #2c3e50;
            padding-bottom: 50px;
        }

        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 35px;
            margin-top: 30px;
            border-bottom: 6px solid var(--nbsc-gold);
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
        }

        .section-header {
            color: var(--nbsc-blue);
            font-weight: 850;
            text-transform: uppercase;
            border-bottom: 2px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }

        /* TEAM BADGE */
        .team-banner {
            background: #f8f9fa;
            border: 2px solid var(--nbsc-gold);
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            margin-bottom: 30px;
        }
        .team-banner small { font-weight: 800; color: #7f8c8d; letter-spacing: 1px; }
        .team-banner h3 { color: var(--nbsc-blue); font-weight: 900; margin: 0; }

        /* LABELS & INPUTS */
        .info-label {
            font-size: 0.75rem;
            font-weight: 800;
            color: var(--nbsc-blue);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
            display: block;
        }

        .form-control[readonly] { background-color: #fff; cursor: default; font-weight: 700; border-color: var(--nbsc-gold); }

        /* TIME GRID */
        .time-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
            gap: 12px;
            margin-top: 15px;
        }

        .time-slot {
            background: white;
            border: 1px solid #ddd;
            padding: 12px;
            text-align: center;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.85rem;
            font-weight: 600;
            color: #555;
        }

        .time-slot:hover { border-color: var(--nbsc-blue); background: #f0f7ff; }
        .time-slot.selected {
            background: var(--nbsc-blue);
            color: white;
            border-color: var(--nbsc-blue);
            box-shadow: 0 4px 10px rgba(13, 71, 161, 0.3);
        }

        .btn-confirm {
            background: var(--nbsc-blue);
            color: white;
            font-weight: 800;
            padding: 18px;
            border-radius: 12px;
            border: none;
            width: 100%;
            margin-top: 40px;
            letter-spacing: 1px;
            transition: 0.3s;
        }
        .btn-confirm:hover { background: #08367a; transform: translateY(-2px); color: white;}
    </style>
</head>
<body onload="generateSlots()">

<div class="container" style="max-width: 900px;">
    <div class="d-flex justify-content-between align-items-center mt-4">
        <a href="javascript:history.back()" class="btn btn-dark fw-bold px-4"><i class="bi bi-arrow-left"></i> BACK</a>
        <a href="matchmaking.php" class="btn btn-warning fw-bold px-4">Find Match <i class="bi bi-search"></i></a>
    </div>

    <div class="glass-card">
        <h2 class="section-header"><i class="bi bi-calendar-check"></i> Court Reservation</h2>

        <div class="team-banner shadow-sm">
            <small>RESERVING FOR SQUAD</small>
            <h3><?php echo strtoupper($team_name); ?></h3>
        </div>

        <form action="" method="POST">
            <input type="hidden" name="team_id" value="<?php echo htmlspecialchars($team_id); ?>">

            <div class="row g-4">
                <div class="col-md-6">
                    <label class="info-label">Step 1: Choose Date</label>
                    <input type="date" name="reservation_date" class="form-control form-control-lg" required min="<?php echo date('Y-m-d'); ?>">
                </div>

                <div class="col-md-6">
                    <label class="info-label">Current Selection</label>
                    <input type="text" name="selected_time" id="finalTime" class="form-control form-control-lg" placeholder="Select a slot below" readonly required>
                </div>

                <div class="col-12">
                    <label class="info-label">Step 2: Pick a Match Slot (90-Min Sessions)</label>
                    <div class="time-grid" id="timeGrid"></div>
                </div>

                <div class="col-12">
                    <div class="p-3 rounded bg-light border mt-2">
                        <label class="info-label text-muted">Or Use Custom Start Time</label>
                        <div class="row g-2 align-items-center">
                            <div class="col-auto">
                                <input type="time" id="customTimeInput" class="form-control">
                            </div>
                            <div class="col-auto">
                                <button type="button" class="btn btn-outline-primary fw-bold" onclick="useCustomTime()">Apply Custom</button>
                            </div>
                            <div class="col text-end">
                                <span class="badge bg-secondary opacity-50">Manual Entry</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" name="confirm" class="btn-confirm shadow">
                <i class="bi bi-check-circle-fill"></i> CONFIRM COURT RESERVATION
            </button>
        </form>
    </div>
</div>

<script>
    function generateSlots() {
        const grid = document.getElementById('timeGrid');
        const startHour = 4; // 4 AM
        const endHour = 22;  // 10 PM
        const durationMin = 90;
        
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
                slotDiv.className = 'time-slot shadow-sm';
                slotDiv.innerHTML = `<i class="bi bi-clock"></i><br>${startTime}<br>—<br>${endTime}`;
                
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
            // Clear selections in the grid
            document.querySelectorAll('.time-slot').forEach(slot => slot.classList.remove('selected'));
        }
    }
</script>
</body>
</html>