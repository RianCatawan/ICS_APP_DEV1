<?php
session_start();
include "db.php";

// Ensure only admin can access
if(!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin'){
    header("Location: index.php");
    exit();
}

$id = $_GET['id'] ?? null;

if(!$id){
    header("Location: manage_users.php");
    exit();
}

// Fetch existing user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt->bind_param("i",$id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if(!$user){
    echo "User not found!";
    exit();
}

// Handle form submission
if(isset($_POST['update_user'])){
    $username = $_POST['username'];
    $role = $_POST['role'];

    // Optional: update password only if filled
    if(!empty($_POST['password'])){
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET username=?, password=?, role=? WHERE id=?");
        $stmt->bind_param("sssi",$username,$password,$role,$id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET username=?, role=? WHERE id=?");
        $stmt->bind_param("ssi",$username,$role,$id);
    }

    $stmt->execute();
    $stmt->close();

    header("Location: manage_users.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Edit User | HoopMatch</title>
<style>
body{
    background:#0f172a;
    font-family:Arial;
    color:white;
    margin:0;
    padding:40px;
}
form{
    background:#1e293b;
    padding:20px;
    border-radius:10px;
}
input, select{
    padding:8px;
    margin:5px 0;
    border-radius:6px;
    border:1px solid #38bdf8;
    background:#0f172a;
    color:white;
    width:100%;
}
button{
    padding:10px 15px;
    border:none;
    border-radius:6px;
    background:#38bdf8;
    color:white;
    font-weight:bold;
    cursor:pointer;
}
button:hover{
    background:#22c55e;
}
a{
    color:#38bdf8;
    text-decoration:none;
}
a:hover{
    color:#22c55e;
}
</style>
</head>
<body>

<a href="manage_users.php">← Back</a>
<h2>Edit User</h2>

<form method="POST">
<input type="text" name="username" placeholder="Username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
<input type="password" name="password" placeholder="New Password (leave blank to keep)">
<select name="role" required>
    <option value="admin" <?php if($user['role']=='admin') echo 'selected'; ?>>Admin</option>
    <option value="editor" <?php if($user['role']=='editor') echo 'selected'; ?>>Editor</option>
    <option value="user" <?php if($user['role']=='user') echo 'selected'; ?>>User</option>
</select>
<button type="submit" name="update_user">Update User</button>
</form>

</body>
</html>