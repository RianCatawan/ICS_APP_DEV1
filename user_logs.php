<?php
session_start();
include "db.php";

// Handle Add Log
if(isset($_POST['add_log'])){
    $user_id = $_POST['user_id'];
    $action = $_POST['action'];
    $stmt = $conn->prepare("INSERT INTO user_logs (user_id, action, log_time) VALUES (?, ?, NOW())");
    $stmt->bind_param("is", $user_id, $action);
    $stmt->execute();
    $stmt->close();
    header("Location: user_logs.php");
    exit();
}

// Handle Delete Log
if(isset($_GET['delete'])){
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM user_logs WHERE id = $id");
    header("Location: user_logs.php");
    exit();
}

// Handle Update Log
if(isset($_POST['edit_log'])){
    $id = $_POST['id'];
    $user_id = $_POST['user_id'];
    $action = $_POST['action'];
    $stmt = $conn->prepare("UPDATE user_logs SET user_id=?, action=? WHERE id=?");
    $stmt->bind_param("isi", $user_id, $action, $id);
    $stmt->execute();
    $stmt->close();
    header("Location: user_logs.php");
    exit();
}
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

    <!-- Add Log Form -->
    <form method="POST" class="mb-4 row g-2">
        <div class="col-md-4">
            <select name="user_id" class="form-select" required>
                <option value="">Select User</option>
                <?php
                $users = $conn->query("SELECT * FROM users ORDER BY username ASC");
                while($u = $users->fetch_assoc()){
                    echo "<option value='{$u['id']}'>{$u['username']}</option>";
                }
                ?>
            </select>
        </div>
        <div class="col-md-4">
            <input type="text" name="action" class="form-control" placeholder="Action" required>
        </div>
        <div class="col-md-4">
            <button type="submit" name="add_log" class="btn btn-success w-100">Add Log</button>
        </div>
    </form>

    <!-- Logs Table -->
    <table class="table table-dark table-striped">
        <thead>
            <tr>
                <th>#</th>
                <th>Username</th>
                <th>Action</th>
                <th>Time</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $sql = "SELECT ul.*, u.username 
                FROM user_logs ul
                LEFT JOIN users u ON ul.user_id = u.id
                ORDER BY ul.log_time DESC";
        $result = $conn->query($sql);

        if($result && $result->num_rows > 0){
            $i = 1;
            while($row = $result->fetch_assoc()){
                ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= htmlspecialchars($row['username'] ?? 'Unknown') ?></td>
                    <td><?= htmlspecialchars($row['action']) ?></td>
                    <td><?= $row['log_time'] ?></td>
                    <td>
                        <!-- Edit Form -->
                        <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['id'] ?>">Edit</button>
                        <a href="user_logs.php?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this log?')">Delete</a>
                        
                        <!-- Edit Modal -->
                        <div class="modal fade" id="editModal<?= $row['id'] ?>" tabindex="-1">
                          <div class="modal-dialog">
                            <div class="modal-content bg-dark text-light">
                              <div class="modal-header">
                                <h5 class="modal-title">Edit Log</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                              </div>
                              <form method="POST">
                              <div class="modal-body">
                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                <div class="mb-3">
                                    <select name="user_id" class="form-select" required>
                                        <?php
                                        $users2 = $conn->query("SELECT * FROM users ORDER BY username ASC");
                                        while($u2 = $users2->fetch_assoc()){
                                            $selected = ($u2['id']==$row['user_id']) ? "selected" : "";
                                            echo "<option value='{$u2['id']}' $selected>{$u2['username']}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <input type="text" name="action" class="form-control" value="<?= htmlspecialchars($row['action']) ?>" required>
                                </div>
                              </div>
                              <div class="modal-footer">
                                <button type="submit" name="edit_log" class="btn btn-warning">Save Changes</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                              </div>
                              </form>
                            </div>
                          </div>
                        </div>

                    </td>
                </tr>
                <?php
            }
        } else {
            echo "<tr><td colspan='5' class='text-center'>No logs found</td></tr>";
        }
        ?>
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>