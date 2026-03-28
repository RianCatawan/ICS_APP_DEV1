<?php
session_start();
include(__DIR__ . '/../database&config/db.php');

// Security Check: Only 'admin' role can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /ICS_APP_DEV1/authentication/login.php");
    exit();
}

// Handle Team Deletion
if (isset($_GET['delete_id'])) {
    $del_id = intval($_GET['delete_id']);
    $conn->query("DELETE FROM teams WHERE id = $del_id");
    header("Location: view_teams.php?msg=Team Deleted Successfully");
    exit();
}

// Fetch all teams
$query = "SELECT * FROM teams ORDER BY created_at DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Teams | NBSC Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

        :root {
            --primary-blue: #071A42;
            --sidebar-width: 260px;
            --accent-gold: #FFD700;
            --bg-body: #f4f7f6;
        }
        
        /* ── STABLE LAYOUT ── */
        body { 
            background-color: var(--bg-body); 
            font-family: 'Inter', sans-serif; 
            margin: 0;
            display: flex; /* Use flex for sidebar/content stability */
        }

        /* ── SIDEBAR ── */
        #sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            background: var(--primary-blue);
            color: white;
            border-right: 4px solid var(--accent-gold);
            z-index: 1000;
            overflow-y: auto;
        }

        /* ── MAIN CONTENT ── */
        #main-wrapper {
            flex: 1;
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            padding: 40px;
            box-sizing: border-box;
        }

        .nav-link { 
            color: rgba(255,255,255,0.7); 
            padding: 15px 25px; 
            font-weight: 500; 
            transition: background 0.2s ease, color 0.2s ease; 
            text-decoration: none;
            display: block;
        }
        .nav-link:hover, .nav-link.active { 
            color: white; 
            background: rgba(255,255,255,0.1); 
        }
        .nav-link.active { 
            border-left: 4px solid var(--accent-gold); 
        }

        /* ── CARDS (Flicker Fix) ── */
        .team-card {
            background: white;
            border: none;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
            transition: transform 0.2s ease-out, box-shadow 0.2s ease-out;
            height: 100%;
            backface-visibility: hidden; /* Fixes hover flickering */
        }
        .team-card:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 12px 20px rgba(0,0,0,0.08); 
        }
        
        .team-logo { 
            width: 80px; height: 80px; 
            object-fit: cover; 
            border-radius: 50%; 
            border: 3px solid #eee;
            margin-bottom: 15px;
        }

        .badge-category {
            font-size: 0.7rem;
            text-transform: uppercase;
            font-weight: 700;
            background: #f0f7ff;
            color: #007bff;
            padding: 4px 12px;
            border-radius: 6px;
            display: inline-block;
        }

        .delete-btn {
            color: #dc3545;
            font-size: 0.8rem;
            font-weight: 700;
            text-decoration: none;
            display: inline-block;
            margin-top: 15px;
        }
        .delete-btn:hover { color: #842029; }

        /* Smooth scroll for the whole page */
        html { scroll-behavior: smooth; }
    </style>
</head>
<body>

<div id="sidebar">
    <div class="p-4 text-center">
        <i class="bi bi-dribbble text-warning" style="font-size: 2.5rem;"></i>
        <h4 class="fw-bold mb-0 mt-2">NBSC ADMIN</h4>
    </div>
    <nav class="mt-4">
        <a class="nav-link" href="/ICS_APP_DEV1/dashboard_and_admin/admin.php"><i class="bi bi-speedometer2 me-2"></i> Overview</a>
        <a class="nav-link active" href="#"><i class="bi bi-people me-2"></i> Teams</a>
        <a class="nav-link" href="/ICS_APP_DEV1/match_system/matches.php"><i class="bi bi-trophy me-2"></i> Matches</a>
        <a class="nav-link" href="/ICS_APP_DEV1/userManagement/manage_users.php"><i class="bi bi-person-gear me-2"></i> User Settings</a>
        <hr class="mx-3 opacity-25">
        <a class="nav-link text-danger" href="/ICS_APP_DEV1/authentication/logout.php"><i class="bi bi-box-arrow-left me-2"></i> Sign Out</a>
    </nav>
</div>

<div id="main-wrapper">
    <div class="container-fluid p-0">
        
        <div class="d-flex justify-content-between align-items-center mb-5">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1 small">
                        <li class="breadcrumb-item"><a href="/ICS_APP_DEV1/dashboard_and_admin/admin.php" class="text-muted">Admin</a></li>
                        <li class="breadcrumb-item active text-primary">Manage Teams</li>
                    </ol>
                </nav>
                <h2 class="fw-bold mb-0">Registered Teams</h2>
            </div>
            <div class="bg-white px-4 py-2 rounded-pill shadow-sm">
                <span class="text-muted small fw-bold">TOTAL DATABASE: </span>
                <span class="text-primary fw-bold"><?php echo $result->num_rows; ?></span>
            </div>
        </div>

        <?php if(isset($_GET['msg'])): ?>
            <div class="alert alert-success border-0 shadow-sm py-3 mb-4">
                <i class="bi bi-check-circle-fill me-2"></i> <?php echo htmlspecialchars($_GET['msg']); ?>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <?php while($team = $result->fetch_assoc()): ?>
                <div class="col-sm-6 col-lg-4 col-xl-3">
                    <div class="team-card text-center">
                        <?php 
                            $photo = !empty($team['team_photo']) ? '../uploads/'.$team['team_photo'] : '../assets/default_team.png';
                        ?>
                        <img src="<?php echo $photo; ?>" class="team-logo" alt="Team Logo" onerror="this.src='../assets/default_team.png'">
                        
                        <div>
                            <span class="badge-category mb-2"><?php echo htmlspecialchars($team['game_type']); ?></span>
                        </div>
                        
                        <h5 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($team['team_name']); ?></h5>
                        <p class="text-muted small mb-0">Coach: <strong><?php echo htmlspecialchars($team['created_by']); ?></strong></p>
                        
                        <div class="mt-4 pt-3 border-top">
                            <a href="view_teams.php?delete_id=<?php echo $team['id']; ?>" 
                               class="delete-btn"
                               onclick="return confirm('Confirm deletion of <?php echo $team['team_name']; ?>?')">
                                <i class="bi bi-trash3 me-1"></i> DELETE SQUAD
                            </a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>

            <?php if($result->num_rows == 0): ?>
                <div class="col-12 text-center mt-5">
                    <div class="py-5">
                        <i class="bi bi-inbox text-muted display-1"></i>
                        <h4 class="text-muted mt-3">No teams found in the system.</h4>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>