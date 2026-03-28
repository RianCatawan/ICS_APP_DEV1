<?php
session_start();
include(__DIR__ . '/../database&config/db.php');

// Redirect to login if not logged in
if (!isset($_SESSION['username'])) {
    header("Location: authentication/login.php");
    exit();
}

if (isset($_POST['create'])) {
    $team_name = $_POST['team_name'];
    $game_type = $_POST['game_type'];
    $creator = $_SESSION['username'];

    $team_photo_name = ""; 
    if (isset($_FILES['team_photo']) && $_FILES['team_photo']['error'] == 0) {
        $target_dir = "../uploads/"; 
        if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }
        
        $file_ext = pathinfo($_FILES["team_photo"]["name"], PATHINFO_EXTENSION);
        $team_photo_name = "team_" . time() . "_" . rand(100,999) . "." . $file_ext; 
        move_uploaded_file($_FILES["team_photo"]["tmp_name"], $target_dir . $team_photo_name);
    }

    $stmt = $conn->prepare("INSERT INTO teams (team_name, game_type, created_by, team_photo) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $team_name, $game_type, $creator, $team_photo_name);
    
    if ($stmt->execute()) {
        $team_id = $conn->insert_id; 

        $update_active = $conn->prepare("UPDATE players SET active_team_id = ? WHERE student_id = ?");
        $update_active->bind_param("is", $team_id, $creator);
        $update_active->execute();

        $names = $_POST['player_name'];
        $ages = $_POST['age'];
        $heights = $_POST['height'];
        $roles = $_POST['role'];

        $stmt_player = $conn->prepare("INSERT INTO team_players (team_id, player_name, age, height, role) VALUES (?, ?, ?, ?, ?)");

        for ($i = 0; $i < count($names); $i++) {
            $stmt_player->bind_param("isiss", $team_id, $names[$i], $ages[$i], $heights[$i], $roles[$i]);
            $stmt_player->execute();
        }

        echo "<script>alert('Team Created and Activated!'); window.location.href='/challenges&scheduling/selectdatetime.php?team_id=$team_id&sid=$creator';</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Team | NBSC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800&family=Plus+Jakarta+Sans:wght@400;500;700&display=swap');

        :root {
            --brand-primary: #0A192F;    
            --brand-accent: #FFB800;     
            --bg-body: #F4F7FA;          
            --surface-card: #FFFFFF;     
            --radius-lg: 16px;
            --radius-md: 10px;
        }

        body {
            background-color: var(--bg-body);
            color: var(--brand-primary);
            font-family: 'Plus Jakarta Sans', sans-serif;
            padding: 0;
            margin: 0;
            padding-bottom: 50px;
        }

        /* ── Header Nav ── */
        .nb-header {
            background: var(--brand-primary);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 5px solid var(--brand-accent);
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .nb-header h4 {
            color: var(--brand-accent);
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            margin: 0;
            letter-spacing: 1px;
        }

        .back-btn {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            padding: 8px 18px;
            border-radius: var(--radius-md);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            border: 1px solid rgba(255,255,255,0.2);
            transition: 0.3s;
        }

        .back-btn:hover {
            background: var(--brand-accent);
            color: var(--brand-primary);
        }

        /* ── Feature Highlight Strokes ── */
        .main-form-card {
            background: var(--surface-card);
            border-radius: var(--radius-lg);
            padding: 30px;
            border: 3px solid var(--brand-primary); /* Primary Stroke Highlight */
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            margin-bottom: 40px;
        }

        .form-label {
            font-weight: 800;
            color: var(--brand-primary);
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-control, .form-select {
            border: 2px solid #E2E8F0; /* Default Stroke */
            border-radius: var(--radius-md);
            padding: 12px;
            transition: 0.3s;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--brand-accent); /* Gold Stroke Highlight */
            box-shadow: 0 0 0 4px rgba(255, 184, 0, 0.1);
        }

        .photo-upload-wrapper {
            background: #F8FAFC;
            border: 2px dashed var(--brand-accent); /* Featured Stroke */
            border-radius: var(--radius-md);
            padding: 5px;
        }

        /* ── Player Cards ── */
        .player-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .player-card {
            background: var(--surface-card);
            border: 2px solid #E2E8F0; /* Standard Stroke */
            padding: 20px;
            border-radius: var(--radius-lg);
            transition: 0.3s;
        }

        .player-card:hover {
            border-color: var(--brand-primary); /* Hover Stroke */
            transform: translateY(-5px);
        }

        .player-card b {
            display: block;
            margin-bottom: 15px;
            color: var(--brand-primary);
            font-family: 'Outfit', sans-serif;
            font-size: 1rem;
            border-bottom: 3px solid var(--brand-accent); /* Feature Accent Stroke */
            padding-bottom: 5px;
        }

        .sample-card {
            opacity: 0.5;
            border-style: dashed;
            background: #F1F5F9;
        }

        /* ── Action Button ── */
        .btn-create {
            background: var(--brand-primary);
            color: var(--brand-accent);
            font-family: 'Outfit', sans-serif;
            font-weight: 900;
            padding: 20px;
            border-radius: var(--radius-md);
            border: 3px solid var(--brand-primary);
            transition: 0.3s;
            margin-top: 40px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .btn-create:hover {
            background: var(--brand-accent);
            color: var(--brand-primary);
            border-color: var(--brand-primary);
        }
    </style>
</head>
<body onload="showPlaceholder()">

    <header class="nb-header">
        <h4><i class="bi bi-shield-shaded"></i> NBSC ATHLETICS</h4>
        <a href="javascript:history.back()" class="back-btn"><i class="bi bi-arrow-left"></i> BACK</a>
    </header>

    <div class="container">
        <form method="POST" enctype="multipart/form-data">
            
            <div class="main-form-card">
                <h5 class="mb-4" style="font-weight:800; font-family:'Outfit';">TEAM REGISTRATION</h5>
                <div class="row g-4">
                    <div class="col-md-3">
                        <label class="form-label">Team Name</label>
                        <input type="text" name="team_name" class="form-control" placeholder="e.g. NBSC Tigers" required>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">Game Type</label>
                        <select name="game_type" class="form-select" id="gameType" onchange="handleGameTypeChange()" required>
                            <option value="">Select Category</option>
                            <option value="1v1">1v1 (+1 Sub)</option>
                            <option value="2v2">2v2 (+1 Sub)</option>
                            <option value="3v3">3v3 (+1 Sub)</option>
                            <option value="4v4">4v4 (+1 Sub)</option>
                            <option value="5v5">5v5 (Multiple Options)</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Team Logo / Photo</label>
                        <div class="photo-upload-wrapper">
                            <input type="file" name="team_photo" class="form-control border-0 bg-transparent shadow-none" accept="image/*">
                        </div>
                    </div>

                    <div class="col-md-3" id="sizeSelectorContainer" style="display:none;">
                        <label class="form-label">Roster Size (5v5)</label>
                        <select id="playerSize" class="form-select" onchange="generateFields()">
                            <option value="5">5 Players</option>
                            <option value="10">10 Players</option>
                            <option value="15">15 Players</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="d-flex align-items-center mb-4">
                <h4 class="m-0" style="font-family:'Outfit'; font-weight:800; color:var(--brand-primary);">TEAM ROSTER</h4>
                <hr class="flex-grow-1 ms-3" style="opacity:0.2; height:2px; color:var(--brand-primary);">
            </div>
            
            <div class="player-grid" id="playerFields"></div>

            <button type="submit" class="btn btn-create w-100" name="create">
                <i class="bi bi-check2-circle"></i> CONFIRM & CREATE TEAM
            </button>
        </form>
    </div>

    <script>
        function showPlaceholder() {
            const container = document.getElementById("playerFields");
            container.innerHTML = "";
            for (let i = 1; i <= 4; i++) {
                container.innerHTML += `
                    <div class="player-card sample-card">
                        <b>DRAFT PLAYER ${i}</b>
                        <div style="height:40px; border:1px solid #ddd; margin-bottom:10px; border-radius:8px; background:#fff;"></div>
                        <div style="height:40px; border:1px solid #ddd; margin-bottom:10px; border-radius:8px; background:#fff;"></div>
                    </div>
                `;
            }
        }

        function handleGameTypeChange() {
            const type = document.getElementById("gameType").value;
            const sizeContainer = document.getElementById("sizeSelectorContainer");
            sizeContainer.style.display = (type === "5v5") ? "block" : "none";
            if (!type) { showPlaceholder(); } else { generateFields(); }
        }

        function generateFields() {
            const type = document.getElementById("gameType").value;
            const container = document.getElementById("playerFields");
            let count = 0;

            if (type === "1v1") count = 2; 
            else if (type === "2v2") count = 3; 
            else if (type === "3v3") count = 4; 
            else if (type === "4v4") count = 5; 
            else if (type === "5v5") {
                count = parseInt(document.getElementById("playerSize").value);
            }

            container.innerHTML = "";

            for (let i = 1; i <= count; i++) {
                let isSub = (i === count && type !== "5v5");
                let label = isSub ? `<i class="bi bi-arrow-repeat"></i> SUB PLAYER` : `<i class="bi bi-person-fill"></i> PLAYER ${i}`;
                
                container.innerHTML += `
                    <div class="player-card" style="${isSub ? 'background: #f8fafc; border-style: dashed;' : ''}">
                        <b>${label}</b>
                        <input type="text" name="player_name[]" class="form-control mb-2" placeholder="Full Name" required>
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <input type="number" name="age[]" class="form-control" placeholder="Age" required>
                            </div>
                            <div class="col-6">
                                <input type="text" name="height[]" class="form-control" placeholder="Ht (e.g. 5'10)" required>
                            </div>
                        </div>
                        <input type="text" name="role[]" class="form-control" placeholder="Role (e.g. Point Guard)" required>
                    </div>
                `;
            }
        }
    </script>
</body>
</html>