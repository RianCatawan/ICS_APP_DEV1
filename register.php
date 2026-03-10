<?php
session_start();
include "db.php";

if(isset($_POST['register'])) {

    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = "user";

    // Prepare statement to avoid SQL injection
    $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $password, $role);

    if($stmt->execute()) {

        // Log the registration in user_logs table
        $stmt_log = $conn->prepare("INSERT INTO user_logs (user_id, action) VALUES (?, ?)");
        $user_id = $stmt->insert_id; // get the last inserted ID
        $action = "REGISTER";
        $stmt_log->bind_param("is", $user_id, $action);
        $stmt_log->execute();
        $stmt_log->close();

        echo "<script>alert('Registered Successfully'); window.location='index.php';</script>";
    } else {
        $error = "Error: " . $stmt->error;
    }

    $stmt->close();
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

.top-bg{
    position:absolute;
    top:0;
    left:0;
    width:100%;
    height:300px;
    background:#F57C00;
    z-index:-5;
}


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

<div class="register-container">
<h2>Register</h2>

<?php if(isset($error)) { echo "<div class='error-msg'>$error</div>"; } ?>

<form method="POST">
<input type="text" name="username" class="form-control" placeholder="Username" required>
<input type="password" name="password" class="form-control" placeholder="Password" required>
<button type="submit" name="register" class="btn-register">Register</button>
</form>

<p class="mt-3">
<a href="index.php" style="color:#F57C00;">Back to Login</a>
</p>
</div>

</body>
</html>