<?php
include "db.php";

if (isset($_POST['submit_reg'])) {
    $username = $_POST['username']; 
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $full_name = $_POST['full_name'];
    $course = $_POST['course'];
    $contact = $_POST['contact'];
    $position = $_POST['position'];
    $skill = $_POST['skill'];

    // 1. Insert into users
    $stmt1 = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
    $stmt1->bind_param("ss", $username, $password);

    if ($stmt1->execute()) {
        // 2. Insert into players (Using $username as the student_id)
        $stmt2 = $conn->prepare("INSERT INTO players (student_id, full_name, course, contact, position, skill_level) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt2->bind_param("ssssss", $username, $full_name, $course, $contact, $position, $skill);
        
        if ($stmt2->execute()) {
            header("Location: profile.php?sid=" . urlencode($username));
            exit();
        }
    } else {
        echo "<script>alert('Error: Username already exists!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Player Registration | UniHoops</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #0d47a1; color: white; font-family: 'Segoe UI', sans-serif; padding: 40px; }
        .reg-container { max-width: 600px; margin: auto; }
        .form-label { font-weight: bold; color: #FFD700; text-transform: uppercase; font-size: 0.85rem; }
        .form-control, .form-select { background: rgba(255, 255, 255, 0.9); border: none; margin-bottom: 15px; }
        .btn-register { background: #FFD700; color: #000; font-weight: bold; border: none; padding: 12px; transition: 0.3s; }
        .btn-register:hover { background: #e6c200; transform: scale(1.02); }
        .header-line { border-bottom: 2px solid #FFD700; margin-bottom: 25px; padding-bottom: 10px; }
        .section-title { color: #FFD700; font-size: 1rem; margin-top: 20px; margin-bottom: 15px; border-left: 3px solid #FFD700; padding-left: 10px; }
    </style>
</head>
<body>

<div class="reg-container">
    <div class="header-line">
        <h2>PLAYER REGISTRATION</h2>
        <small>Join the University Basketball League</small>
    </div>

    <form action="" method="POST">
        <div class="section-title">Account Credentials</div>
        <div class="row">
            <div class="col-md-6">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" placeholder="Choose a username" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="Choose a password" required>
            </div>
        </div>

        <div class="section-title">Personal Information</div>
        <label class="form-label">Full Name</label>
        <input type="text" name="full_name" class="form-control" placeholder="Enter Full Name" required>

        <label class="form-label">Student ID Number</label>
        <input type="text" name="student_id" class="form-control" placeholder="e.g. 2024-10025" required>

        <div class="row">
            <div class="col-md-7">
                <label class="form-label">Course / Year Level</label>
                <input type="text" name="course" class="form-control" placeholder="BSIT - 3rd Year" required>
            </div>
            <div class="col-md-5">
                <label class="form-label">Contact Number</label>
                <input type="text" name="contact" class="form-control" placeholder="09123456789" required>
            </div>
        </div>

        <label class="form-label">Preferred Position</label>
        <select name="position" class="form-select" required>
            <option value="">Select Position</option>
            <option>Point Guard</option>
            <option>Shooting Guard</option>
            <option>Small Forward</option>
            <option>Power Forward</option>
            <option>Center</option>
        </select>

        <label class="form-label">Skill Level</label>
        <div class="d-flex gap-3 mb-4">
            <div class="form-check">
                <input class="form-check-input" type="radio" name="skill" value="Beginner" id="s1" checked>
                <label class="form-check-label" for="s1">Beginner</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="skill" value="Intermediate" id="s2">
                <label class="form-check-label" for="s2">Intermediate</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="skill" value="Advanced" id="s3">
                <label class="form-check-label" for="s3">Advanced</label>
            </div>
        </div>

        <button type="submit" name="submit_reg" class="btn btn-register w-100">SUBMIT & CREATE PROFILE</button>
    </form>
    
    <div class="text-center mt-3">
        <p>Already have an account? <a href="login.php" style="color: #FFD700; text-decoration: none; font-weight: bold;">Login here</a></p>
    </div>
</div>

</body>
</html>