<?php
include "db.php";

if(isset($_POST['register'])){

    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = "user";

    $sql = "INSERT INTO users (username, password, role)
            VALUES ('$username', '$password', '$role')";

    if($conn->query($sql)){
        echo "<script>alert('Registered Successfully'); window.location='index.php';</script>";
    } else {
        echo "Error: " . $conn->error;
    }
}
?>

<h2>Register</h2>
<form method="POST">
    <input type="text" name="username" placeholder="Username" required><br><br>
    <input type="password" name="password" placeholder="Password" required><br><br>
    <button type="submit" name="register">Register</button>
</form>

<a href="index.php">Back to Login</a>
