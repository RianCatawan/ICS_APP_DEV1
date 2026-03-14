<?php
session_start();
include "db.php";

// Redirect to login if not logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

if (isset($_POST['create'])) {
    $team_name = $_POST['team_name'];
    $game_type = $_POST['game_type'];
    $creator = $_SESSION['username'];

    // 1. Team Photo Upload Logic
    $team_photo_name = ""; 
    if (isset($_FILES['team_photo']) && $_FILES['team_photo']['error'] == 0) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }
        
        $file_ext = pathinfo($_FILES["team_photo"]["name"], PATHINFO_EXTENSION);
        $team_photo_name = "team_" . time() . "_" . rand(100,999) . "." . $file_ext; 
        move_uploaded_file($_FILES["team_photo"]["tmp_name"], $target_dir . $team_photo_name);
    }

    // 2. Save the Team Header
    $stmt = $conn->prepare("INSERT INTO teams (team_name, game_type, created_by, team_photo) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $team_name, $game_type, $creator, $team_photo_name);
    
    if ($stmt->execute()) {
        $team_id = $conn->insert_id; 

        // --- NEW: AUTO-ACTIVATE THIS TEAM FOR THE USER ---
        // This prevents the "select_team_first" error
        $update_active = $conn->prepare("UPDATE players SET active_team_id = ? WHERE student_id = ?");
        $update_active->bind_param("is", $team_id, $creator);
        $update_active->execute();

        // 3. Save each player in the team
        $names = $_POST['player_name'];
        $ages = $_POST['age'];
        $heights = $_POST['height'];
        $roles = $_POST['role'];

        $stmt_player = $conn->prepare("INSERT INTO team_players (team_id, player_name, age, height, role) VALUES (?, ?, ?, ?, ?)");

        for ($i = 0; $i < count($names); $i++) {
            $stmt_player->bind_param("isiss", $team_id, $names[$i], $ages[$i], $heights[$i], $roles[$i]);
            $stmt_player->execute();
        }

        // Redirect specifically to the team selection with the active ID
        echo "<script>alert('Team Created and Activated!'); window.location.href='selectdatetime.php?team_id=$team_id&sid=$creator';</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Team</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #0d47a1; 
            font-family: 'Segoe UI', Arial, sans-serif;
            color: white;
            padding: 40px;
        }

        .form-label {
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 0.9rem;
        }

        .player-grid {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-top: 30px;
            min-height: 250px;
        }

        .player-card {
            background: rgba(0, 0, 0, 0.4);
            padding: 15px;
            border-radius: 8px;
            width: 200px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: transform 0.2s;
        }

        .sample-card {
            background: rgba(255, 255, 255, 0.05);
            border: 2px dashed rgba(255, 255, 255, 0.2);
            opacity: 0.5;
        }

        .player-card b {
            display: block;
            margin-bottom: 10px;
            border-bottom: 1px solid #FFD700;
            padding-bottom: 5px;
            font-size: 0.85rem;
        }

        .form-control {
            margin-bottom: 8px;
            background: rgba(255, 255, 255, 0.9);
            font-size: 0.8rem;
        }

        .btn-create {
            background: #FFD700;
            color: #000;
            font-weight: bold;
            margin-top: 30px;
            padding: 12px;
            border: none;
        }

        #sizeSelectorContainer {
            margin-top: 0;
            display: none;
        }

        /* Styling for the new Team Photo Input Box */
        .photo-box {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 215, 0, 0.3);
            border-radius: 5px;
            padding: 5px;
        }
    </style>
</head>
<body onload="showPlaceholder()">

    <div class="container">
        <h2 class="mb-4">CREATE TEAM</h2>

        <form method="POST" enctype="multipart/form-data">
            <div class="row align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Team Name</label>
                    <input type="text" name="team_name" class="form-control" placeholder="Enter Team Name" required>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Game Type</label>
                    <select name="game_type" class="form-control" id="gameType" onchange="handleGameTypeChange()" required>
                        <option value="">Select Game Type</option>
                        <option value="1v1">1v1 (+1 Sub)</option>
                        <option value="2v2">2v2 (+1 Sub)</option>
                        <option value="3v3">3v3 (+1 Sub)</option>
                        <option value="4v4">4v4 (+1 Sub)</option>
                        <option value="5v5">5v5 (Multiple Options)</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Team Photo</label>
                    <div class="photo-box">
                        <input type="file" name="team_photo" class="form-control form-control-sm" accept="image/*">
                    </div>
                </div>

                <div class="col-md-3" id="sizeSelectorContainer">
                    <label class="form-label">Player Count (5v5)</label>
                    <select id="playerSize" class="form-control" onchange="generateFields()">
                        <option value="5">5 Players</option>
                        <option value="10">10 Players</option>
                        <option value="15">15 Players</option>
                    </select>
                </div>
            </div>

            <div class="player-grid" id="playerFields">
                </div>

            <button type="submit" class="btn btn-create w-100" name="create">CREATE TEAM</button>
        </form>
    </div>

    <script>
        function showPlaceholder() {
            const container = document.getElementById("playerFields");
            container.innerHTML = "";
            for (let i = 1; i <= 5; i++) {
                container.innerHTML += `
                    <div class="player-card sample-card">
                        <b>Sample Player ${i}</b>
                        <div style="height:25px; background:rgba(255,255,255,0.1); margin-bottom:8px; border-radius:4px;"></div>
                        <div style="height:25px; background:rgba(255,255,255,0.1); margin-bottom:8px; border-radius:4px;"></div>
                        <div style="height:25px; background:rgba(255,255,255,0.1); margin-bottom:8px; border-radius:4px;"></div>
                        <p class="text-center mt-3" style="font-size:10px;">Select Game Type to Edit</p>
                    </div>
                `;
            }
        }

        function handleGameTypeChange() {
            const type = document.getElementById("gameType").value;
            const sizeContainer = document.getElementById("sizeSelectorContainer");
            
            if (type === "5v5") {
                sizeContainer.style.display = "block";
            } else {
                sizeContainer.style.display = "none";
            }
            
            if (!type) {
                showPlaceholder();
            } else {
                generateFields();
            }
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
                let label = (i === count && type !== "5v5") ? `Sub Player` : `Player ${i}`;
                
                container.innerHTML += `
                    <div class="player-card">
                        <b>${label}</b>
                        <input type="text" name="player_name[]" class="form-control" placeholder="Name" required>
                        <input type="number" name="age[]" class="form-control" placeholder="Age" required>
                        <input type="text" name="height[]" class="form-control" placeholder="Height" required>
                        <input type="text" name="role[]" class="form-control" placeholder="Role" required>
                    </div>
                `;
            }
        }
    </script>
</body>
</html>