<?php
include "db.php"; // Your db.php should connect to hoopmatch_db
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>User Logs | HoopMatch</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark text-light">

<div class="container py-5">
    <h2 class="mb-4 text-info">User Activity Logs</h2>

    <table class="table table-dark table-striped">
        <thead>
            <tr>
                <th>#</th>
                <th>Username</th>
                <th>Action</th>
                <th>Time</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $sql = "SELECT * FROM user_logs ORDER BY log_time DESC";
        $result = $conn->query($sql);
        if($result->num_rows > 0){
            $i = 1;
            while($row = $result->fetch_assoc()){
                echo "<tr>
                        <td>".$i++."</td>
                        <td>".$row['username']."</td>
                        <td>".$row['action']."</td>
                        <td>".$row['log_time']."</td>
                      </tr>";
            }
        } else {
            echo "<tr><td colspan='4' class='text-center'>No logs found</td></tr>";
        }
        ?>
        </tbody>
    </table>
</div>

</body>
</html>