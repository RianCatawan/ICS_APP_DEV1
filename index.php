<?php
session_start();
include "db.php";

if(isset($_POST['login'])){

    $username = $_POST['username'];
    $password = $_POST['password'];

   
    if($username === "admin" && $password === "password"){

        $_SESSION['username'] = "admin";
        $_SESSION['role'] = "admin";


        $check = $conn->query("SELECT id FROM users WHERE username='admin'");
        
        if($check->num_rows == 0){
            $hashed = password_hash("password", PASSWORD_DEFAULT);
            $conn->query("INSERT INTO users (username, password, role) 
                          VALUES ('admin', '$hashed', 'admin')");
            $admin_id = $conn->insert_id;
        } else {
            $row = $check->fetch_assoc();
            $admin_id = $row['id'];
        }


        $conn->query("INSERT INTO user_logs (user_id, action)
                      VALUES ($admin_id, 'LOGIN')");

        header("Location: admin.php");
        exit();
    }



    $stmt = $conn->prepare("SELECT * FROM users WHERE username=?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0){

        $row = $result->fetch_assoc();

        if(password_verify($password, $row['password'])){

            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['user_id'] = $row['id'];

            $user_id = $row['id'];


            $conn->query("INSERT INTO user_logs (user_id, action)
                          VALUES ($user_id, 'LOGIN')");

            header("Location: match-start.php");
            exit();

        } else {
            $error = "Invalid Password";
        }

    } else {
        $error = "User Not Found";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>HoopMatch | Login</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

body{
    margin:0;
    font-family: Arial, sans-serif;
    background:#000;
    color:white;
}

/* ORANGE TOP BACKGROUND */
.top-bg{
    position:absolute;
    top:0;
    left:0;
    width:100%;
    height:300px;
    background:#F57C00; /* basketball orange */
    z-index:-5;
}

/* S CURVE LINE */
.curve{
    position:absolute;
    bottom:-1px;
    width:100%;
}

/* NAVBAR */
.navbar{
    background:transparent;
}

.navbar-brand{
    color:white !important;
    font-weight:bold;
    font-size:24px;
}

/* LOGIN BOX */
.login-container{
    margin-top:210px;
    background:#111;
    padding:50px;
    border-radius:12px;
    max-width:450px;
    margin-left:auto;
    margin-right:auto;
    text-align:center;
    box-shadow:0 10px 25px rgba(0,0,0,0.6);
}

.login-container h2{
    color:#F57C00;
    margin-bottom:25px;
}

/* INPUT FIELDS */
.form-control{
    background:#000;
    color:white;
    border:2px solid #F57C00;
    border-radius:8px;
    padding:14px;
    font-size:16px;
    margin-bottom:20px;
}

.form-control::placeholder{
    color:#aaa;
}

/* LOGIN BUTTON */
.btn-login{
    background:#F57C00;
    color:black;
    font-weight:bold;
    width:100%;
    padding:14px;
    border:none;
    border-radius:8px;
    font-size:17px;
}

.btn-login:hover{
    background:#ff8c00;
}

/* LINKS */
a{
    color:#F57C00;
    text-decoration:none;
}

a:hover{
    text-decoration:underline;
}

.error-msg{
    color:#ff6b6b;
    margin-bottom:15px;
}

</style>
</head>

<body>

<div class="top-bg">

<!-- S CURVE DIVIDER -->
<svg class="curve" viewBox="0 0 1440 120" preserveAspectRatio="none">
<path fill="#000"
d="M0,80 
C300,120 500,20 900,60
C1200,90 1300,10 1440,0
L1440,120
L0,120 Z">
</path>
</svg>

</div>

<nav class="navbar px-4">
<a class="navbar-brand" href="index.php">HoopMatch</a>
</nav>

<div class="login-container">

<h2>Login</h2>

<?php if(isset($error)) { echo "<div class='error-msg'>$error</div>"; } ?>

<form method="POST">

<input type="text" name="username" class="form-control" placeholder="Username" required>

<input type="password" name="password" class="form-control" placeholder="Password" required>

<button type="submit" name="login" class="btn-login">Login</button>

</form>

<p class="mt-3">
<a href="register.php">Create Account</a>
</p>

</div>

</body>
</html>