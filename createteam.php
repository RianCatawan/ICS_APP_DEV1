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

        // AUTO-ACTIVATE THIS TEAM FOR THE USER
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

        echo "<script>alert('Team Created and Activated!'); window.location.href='selectdatetime.php?team_id=$team_id&sid=$creator';</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Team | NBSC Court</title>
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
            font-family: 'Segoe UI', Roboto, sans-serif;
            color: #2c3e50;
            padding-bottom: 50px;
        }

        .glass-container {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            margin-top: 30px;
            border-bottom: 6px solid var(--nbsc-gold);
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
        }

        .section-title {
            color: var(--nbsc-blue);
            font-weight: 850;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 30px;
            border-left: 5px solid var(--nbsc-gold);
            padding-left: 15px;
        }

        .info-label {
            font-size: 0.75rem;
            font-weight: 800;
            color: var(--nbsc-blue);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
            display: block;
        }

        .form-control, .form-select {
            border-radius: 10px;
            border: 1px solid #ced4da;
            padding: 10px;
            font-weight: 600;
            color: #333;
        }

        .player-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .player-card {
            background: white;
            padding: 20px;
            border-radius: 15px;
            border: 1px solid #eee;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            transition: 0.3s;
        }

        .player-card:hover {
            border-color: var(--nbsc-blue);
            transform: translateY(-3px);
        }

        .player-card b {
            display: block;
            color: var(--nbsc-blue);
            border-bottom: 2px solid #f1f1f1;
            margin-bottom: 15px;
            padding-bottom: 5px;
            font-size: 0.95rem;
        }

        .btn-create {
            background: var(--nbsc-blue);
            color: white;
            font-weight: 800;
            padding: 15px;
            border-radius: 12px;
            border: none;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: 0.3s;
        }

        .btn-create:hover {
            background: #08367a;
            box-shadow: 0 5px 15px rgba(13, 71, 161, 0.4);
            color: white;
        }

        .placeholder-box {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px;
            background: rgba(0,0,0,0.03);
            border: 2px dashed #ccc;
            border-radius: 15px;
            color: #888;
        }
    </style>
</head>
<body onload="showPlaceholder()">

<div class="container">
    <div class="d-flex justify-content-between mt-4">
        <a href="javascript:history.back()" class="btn btn-dark fw-bold"><i class="bi bi-arrow-left"></i> BACK</a>
    </div>

    <div class="glass-container">
        <h2 class="section-title">Team Registration</h2>

        <form method="POST" enctype="multipart/form-data">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="info-label">Team Name</label>
                    <input type="text" name="team_name" class="form-control" placeholder="Enter Team Name" required>
                </div>
                
                <div class="col-md-4">
                    <label class="info-label">Game Category</label>
                    <select name="game_type" class="form-select" id="gameType" onchange="handleGameTypeChange()" required>
                        <option value="">-- Choose Category --</option>
                        <option value="1v1">1v1 (+1 Sub)</option>
                        <option value="2v2">2v2 (+1 Sub)</option>
                        <option value="3v3">3v3 (+1 Sub)</option>
                        <option value="4v4">4v4 (+1 Sub)</option>
                        <option value="5v5">5v5 (Standard)</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="info-label">Team Photo / Logo</label>
                    <input type="file" name="team_photo" class="form-control" accept="image/*">
                </div>

                <div class="col-md-4 offset-md-4 mt-3" id="sizeSelectorContainer" style="display:none;">
                    <label class="info-label">Roster Limit (5v5)</label>
                    <select id="playerSize" class="form-select" onchange="generateFields()">
                        <option value="5">5 Players</option>
                        <option value="10">10 Players</option>
                        <option value="15">15 Players</option>
                    </select>
                </div>
            </div>

            <hr class="my-5">

            <div class="player-grid" id="playerFields"></div>

            <button type="submit" class="btn btn-create w-100 mt-5" name="create">
                <i class="bi bi-plus-circle-fill"></i> REGISTER AND SET AS ACTIVE TEAM
            </button>
        </form>
    </div>
</div>

<script>
    function showPlaceholder() {
        const container = document.getElementById("playerFields");
        container.innerHTML = `
            <div class="placeholder-box">
                <i class="bi bi-people" style="font-size: 3rem;"></i>
                <h5 class="mt-3">Ready to Build Your Squad?</h5>
                <p>Select a Game Category above to start adding player details.</p>
            </div>`;
    }

    function handleGameTypeChange() {
        const type = document.getElementById("gameType").value;
        const sizeContainer = document.getElementById("sizeSelectorContainer");
        
        sizeContainer.style.display = (type === "5v5") ? "block" : "none";
        
        if (!type) showPlaceholder();
        else generateFields();
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
                    <b><i class="bi bi-person-badge"></i> ${label}</b>
                    
                    <div class="mb-3">
                        <label class="info-label text-muted">Full Name</label>
                        <input type="text" name="player_name[]" class="form-control form-control-sm" placeholder="Full Name" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-4">
                            <label class="info-label text-muted">Age</label>
                            <input type="number" name="age[]" class="form-control form-control-sm" placeholder="00" required>
                        </div>
                        <div class="col-8">
                            <label class="info-label text-muted">Height (cm)</label>
                            <input type="text" name="height[]" class="form-control form-control-sm" placeholder="e.g. 175cm" required>
                        </div>
                    </div>

                    <label class="info-label text-muted">Court Role</label>
                    <select name="role[]" class="form-select form-select-sm" required>
                        <option value="" selected disabled>Select Role</option>
                        <option value="Point Guard">Point Guard (PG)</option>
                        <option value="Shooting Guard">Shooting Guard (SG)</option>
                        <option value="Small Forward">Small Forward (SF)</option>
                        <option value="Power Forward">Power Forward (PF)</option>
                        <option value="Center">Center (C)</option>
                        <option value="Sixth Man">Sixth Man (Sub)</option>
                        <option value="Captain">Team Captain</option>
                    </select>
                </div>
            `;
        }
    }
</script>

</body>
</html>