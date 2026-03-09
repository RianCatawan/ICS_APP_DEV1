<?php
session_start();
include "db.php";

// Handle Add User
if(isset($_POST['add_user'])){
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $password, $role);
    $stmt->execute();
    $stmt->close();
    header("Location: manage_users.php");
    exit();
}

// Fetch all users
$result = $conn->query("SELECT * FROM users ORDER BY id ASC");
?>

<!DOCTYPE html>
<html>
<head>
<title>Manage Users | HoopMatch</title>
<style>
body{
    background:#0f172a;
    font-family:Arial;
    color:white;
    margin:0;
    padding:40px;
}
.page-title{
    font-size:28px;
    margin-bottom:30px;
}
.back-btn{
    position:absolute;
    top:20px;
    left:20px;
    background:#111;
    color:white;
    padding:10px 18px;
    border-radius:8px;
    border:2px solid #38bdf8;
    text-decoration:none;
    font-weight:bold;
}
.back-btn:hover{
    background:#38bdf8;
    color:black;
}
.table-box{
    background:#1e293b;
    padding:20px;
    border-radius:10px;
    margin-bottom:30px;
}
table{
    width:100%;
    border-collapse:collapse;
    margin-top:10px;
}
th, td{
    padding:10px;
    border-bottom:1px solid #334155;
    text-align:left;
}
th{
    font-weight:bold;
}
a.action-link{
    display:inline-block;
    margin-right:10px;
    padding:6px 12px;
    background:#38bdf8;
    color:white;
    border-radius:6px;
    text-decoration:none;
    font-weight:bold;
}
a.action-link:hover{
    background:#22c55e;
}
form.add-user{
    background:#1e293b;
    padding:20px;
    border-radius:10px;
    margin-bottom:30px;
}
form.add-user input, form.add-user select{
    padding:8px;
    margin-right:10px;
    border-radius:6px;
    border:1px solid #38bdf8;
    background:#0f172a;
    color:white;
}
form.add-user button{
    padding:8px 15px;
    border:none;
    border-radius:6px;
    background:#38bdf8;
    color:white;
    font-weight:bold;
    cursor:pointer;
}
form.add-user button:hover{
    background:#22c55e;
}
</style>
</head>
<body>

<a href="admin.php" class="back-btn">← Back</a>

<h2 class="page-title">Manage Users</h2>

<!-- ADD USER FORM -->
<form class="add-user" method="POST">
<input type="text" name="username" placeholder="Username" required>
<input type="password" name="password" placeholder="Password" required>
<select name="role" required>
    <option value="">Select Role</option>
    <option value="admin">Admin</option>
    <option value="editor">Editor</option>
    <option value="user">User</option>
</select>
<button type="submit" name="add_user">Add User</button>
</form>

<!-- USER TABLE -->
<div class="table-box">
<table>
<tr>
<th>ID</th>
<th>Username</th>
<th>Role</th>
<th>Action</th>
</tr>

<?php
while($row = $result->fetch_assoc()){
    echo "<tr>
    <td>{$row['id']}</td>
    <td>{$row['username']}</td>
    <td>{$row['role']}</td>
    <td>
        <a class='action-link' href='edit_user.php?id={$row['id']}'>Edit</a>
        <a class='action-link' href='delete_user.php?id={$row['id']}' onclick=\"return confirm('Are you sure you want to delete this user?');\">Delete</a>
    </td>
    </tr>";
}
?>
</table>
</div>

</body>
</html>