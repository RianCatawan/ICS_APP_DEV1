<?php
include "db.php";
?>

<h2>All Matches</h2>

<table border="1">
<tr>
<th>ID</th>
<th>Team 1</th>
<th>Team 2</th>
<th>Date</th>
<th>Status</th>
</tr>

<?php
$sql = "
SELECT m.id, t1.team_name AS team1, t2.team_name AS team2, m.match_time, m.status
FROM matches m
LEFT JOIN userteams t1 ON m.team1_id = t1.id
LEFT JOIN userteams t2 ON m.team2_id = t2.id
ORDER BY m.match_time DESC
";

$result = $conn->query($sql);

while($row = $result->fetch_assoc()){
    echo "<tr>
    <td>{$row['id']}</td>
    <td>{$row['team1']}</td>
    <td>{$row['team2']}</td>
    <td>{$row['match_time']}</td>
    <td>{$row['status']}</td>
    </tr>";
}
?>
</table>