<?php
session_start();
include "db.php";

$sql = "SELECT mr.id, t1.team_name AS team1, t2.team_name AS team2, 
               r.reservation_date, r.selected_time, mr.status, mr.home_score, mr.away_score
        FROM match_requests mr
        JOIN reservations r ON mr.reservation_id = r.id
        JOIN teams t1 ON r.team_id = t1.id
        JOIN teams t2 ON mr.challenger_team_id = t2.id
        ORDER BY r.reservation_date DESC";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>All Matches | HoopMatch</title>
    <style>
        body { background:#0d47a1; font-family:Arial; color:white; padding:40px; }
        .back-btn { background:#000; color:#FFD700; padding:10px 18px; border-radius:8px; border:2px solid #FFD700; text-decoration:none; font-weight:bold; }
        .table-box { background:rgba(0,0,0,0.4); padding:20px; border-radius:15px; border:1px solid #FFD700; }
        table { width:100%; border-collapse:collapse; }
        th { color:#FFD700; padding:12px; text-align:left; border-bottom:2px solid #FFD700; }
        td { padding:12px; border-bottom:1px solid rgba(255,255,255,0.1); }
        .status-badge { padding:4px 8px; border-radius:4px; font-size:12px; font-weight:bold; background:#FFD700; color:black; }
    </style>
</head>
<body>
    <a href="admin.php" class="back-btn">← Back</a>
    <h2 style="margin-top:40px;">Complete Match Schedule</h2>
    <div class="table-box">
        <table>
            <tr><th>Match ID</th><th>Versus</th><th>Schedule</th><th>Score</th><th>Status</th></tr>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td>#<?= $row['id'] ?></td>
                <td><?= $row['team1'] ?> <strong>vs</strong> <?= $row['team2'] ?></td>
                <td><?= $row['reservation_date'] ?> (<?= $row['selected_time'] ?>)</td>
                <td style="color:#FFD700; font-weight:bold;"><?= $row['home_score'] ?> - <?= $row['away_score'] ?></td>
                <td><span class="status-badge"><?= strtoupper($row['status']) ?></span></td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
</body>
</html>