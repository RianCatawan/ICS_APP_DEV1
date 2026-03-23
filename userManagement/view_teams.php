<?php
session_start();
include "db.php";

// Admin Security Check
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Handle Team Deletion (If needed)
if (isset($_GET['delete_id'])) {
    $del_id = intval($_GET['delete_id']);
    $conn->query("DELETE FROM teams WHERE id = $del_id");
    header("Location: view_teams.php?msg=Team Deleted");
    exit();
}

// Fetch all teams from the correct table name: 'teams'
$query = "SELECT * FROM teams ORDER BY created_at DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Teams | HoopMatch Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background: #0d47a1; color: white; padding: 40px; font-family: 'Segoe UI', sans-serif; }
        .back-btn { background:#000; color:#FFD700; padding:10px 18px; border-radius:8px; border:2px solid #FFD700; text-decoration:none; font-weight:bold; }
        .back-btn:hover { background: #FFD700; color: #000; }
        .team-card { 
            background: rgba(0, 0, 0, 0.4); 
            border: 1px solid rgba(255, 215, 0, 0.3); 
            border-radius: 15px; 
            padding: 20px; 
            transition: 0.3s;
            height: 100%;
            backdrop-filter: blur(10px);
        }
        .team-card:hover { border-color: #FFD700; transform: translateY(-5px); }
        .team-logo { 
            width: 80px; 
            height: 80px; 
            object-fit: cover; 
            border-radius: 50%; 
            border: 2px solid #FFD700;
            margin-bottom: 15px;
        }
        .game-type {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #FFD700;
        }
    </style>
</head>
<body>

    <a href="admin.php" class="back-btn">← Back to Dashboard</a>

    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-shield-shaded text-warning"></i> Registered Teams</h2>
            <span class="badge bg-dark border border-warning">Total: <?php echo $result->num_rows; ?></span>
        </div>

        <?php if(isset($_GET['msg'])): ?>
            <div class="alert alert-success bg-success text-white border-0"><?php echo $_GET['msg']; ?></div>
        <?php endif; ?>

        <div class="row g-4">
            <?php while($team = $result->fetch_assoc()): ?>
                <div class="col-md-4 col-lg-3">
                    <div class="team-card text-center">
                        <?php 
                            $photo = !empty($team['team_photo']) ? 'uploads/'.$team['team_photo'] : 'assets/default_team.png';
                        ?>
                        <img src="<?php echo $photo; ?>" class="team-logo" alt="Logo">
                        
                        <div class="game-type"><?php echo htmlspecialchars($team['game_type']); ?></div>
                        <h5 class="fw-bold mt-2"><?php echo htmlspecialchars($team['team_name']); ?></h5>
                        <p class="small text-white-50">Created by: <?php echo htmlspecialchars($team['created_by']); ?></p>
                        
                        <div class="mt-3 pt-3 border-top border-secondary">
                            <a href="delete_team.php?id=<?php echo $team['id']; ?>" 
                               class="text-danger text-decoration-none small"
                               onclick="return confirm('Delete this team permanently?')">
                                <i class="bi bi-trash"></i> Delete Team
                            </a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>

            <?php if($result->num_rows == 0): ?>
                <div class="col-12 text-center py-5">
                    <i class="bi bi-people text-white-50" style="font-size: 3rem;"></i>
                    <p class="text-white-50 mt-3">No teams have been registered yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>