<?php
include "db.php";

if(isset($_POST['register'])){

    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = "user";

    $sql = "INSERT INTO users (username, password, role)
            VALUES ('$username', '$password', '$role')";

    if($conn->query($sql)){
        // Optional: log registration
        $conn->query("INSERT INTO user_logs (username, action)
                      VALUES ('$username', 'REGISTER')");

        echo "<script>alert('Registered Successfully'); window.location='index.php';</script>";
    } else {
        $error = "Error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>HoopMatch | Register</title>

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
    background:#F57C00;
    z-index:-5;
}

/* S CURVE */
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

/* REGISTER BOX */
.register-container{
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

.register-container h2{
    color:#F57C00;
    margin-bottom:25px;
}

/* INPUT */
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

/* BUTTON */
.btn-register{
    background:#F57C00;
    color:black;
    font-weight:bold;
    width:100%;
    padding:14px;
    border:none;
    border-radius:8px;
    font-size:17px;
}

.btn-register:hover{
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
<a class="navbar-brand" href="index.php">🏀 HoopMatch</a>
</nav>

<div class="register-container">

<h2>Register</h2>

<?php if(isset($error)) { echo "<div class='error-msg'>$error</div>"; } ?>

<form method="POST">

<input type="text" name="username" class="form-control" placeholder="Username" required>

<input type="password" name="password" class="form-control" placeholder="Password" required>

<button type="submit" name="register" class="btn-register">Register</button>

</form>

<p class="mt-3">
<a href="index.php">Back to Login</a>
</p>

</div>

</body>
</html>