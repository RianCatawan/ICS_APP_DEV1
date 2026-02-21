<?php
session_start();
include "db.php";

if(isset($_POST['login'])){

    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username='$username'";
    $result = $conn->query($sql);

    if($result->num_rows > 0){

        $row = $result->fetch_assoc();

        if(password_verify($password, $row['password'])){

            $_SESSION['username'] = $username;
            $_SESSION['role'] = $row['role'];

            // Insert Login Log
            $conn->query("INSERT INTO user_logs (username, action)
                          VALUES ('$username', 'LOGIN')");

            if($row['role'] == 'admin'){
                header("Location: admin.php");
            } else {
                header("Location: match-start.php");
            }

            exit();

        } else {
            echo "<script>alert('Invalid Password');</script>";
        }

    } else {
        echo "<script>alert('User Not Found');</script>";
    }
}
?>

<h2>Login</h2>

<form method="POST">
    <input type="text" name="username" placeholder="Username" required><br><br>
    <input type="password" name="password" placeholder="Password" required><br><br>
    <button type="submit" name="login">Login</button>
</form>

<a href="register.php">Create Account</a>
