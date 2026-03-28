<?php
include(__DIR__ . '/../database&config/db.php');

if (isset($_POST['submit_reg'])) {

    $username  = $_POST['username'];
    $password  = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $full_name = $_POST['full_name'];
    $course    = $_POST['course'];
    $contact   = $_POST['contact'];
    $position  = $_POST['position'];
    $skill     = $_POST['skill'];

    $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $check->bind_param("s", $username);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $error = "Username already exists! Please choose another.";
    } else {

        $stmt1 = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
        $stmt1->bind_param("ss", $username, $password);

        if ($stmt1->execute()) {

            $stmt2 = $conn->prepare("INSERT INTO players (student_id, full_name, course, contact, position, skill_level) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt2->bind_param("ssssss", $username, $full_name, $course, $contact, $position, $skill);

            if ($stmt2->execute()) {
                header("Location: ../userManagement/profile.php?sid=" . urlencode($username));
                exit();
            } else {
                $error = "Error saving player info. Please try again.";
            }
        } else {
            $error = "Error creating account. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | NBSC Basketball</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=DM+Sans:wght@400;500;600&display=swap');

*, *::before, *::after { box-sizing: border-box; }

:root {
    --navy:        #0D2F6E;
    --navy-deep:   #071A42;
    --navy-mid:    #1A4EA6;
    --sky:         #4A9EE8;
    --sky-light:   #BDD9F5;
    --sky-pale:    #E8F3FC;
    --amber:       #F5A623;
    --amber-warm:  #FFBE4D;
    --amber-pale:  #FFF4DC;
    --white:       #FFFFFF;
    --border:      #C8DCEF;
    --text-main:   #0D2F6E;
    --text-body:   #2C4A72;
    --text-muted:  #6A8BB0;
    --danger:      #C0392B;
    --danger-bg:   #FEECEB;
    --success:     #1B7A4A;
    --success-bg:  #E3F5EC;
    --radius-sm:   8px;
    --radius-md:   12px;
    --radius-lg:   18px;
    --radius-pill: 9999px;
    --shadow-sm:   0 2px 8px rgba(13, 47, 110, 0.08);
    --shadow-md:   0 4px 18px rgba(13, 47, 110, 0.13);
    --shadow-glow: 0 0 0 4px rgba(74, 158, 232, 0.18);
}

body {
    margin: 0;
    min-height: 100vh;
    background: linear-gradient(160deg, #daeeff 0%, #eef5fb 50%, #f5f9fd 100%);
    font-family: 'DM Sans', 'Segoe UI', sans-serif;
    font-size: 15px;
    color: var(--text-main);
    display: flex;
    flex-direction: column;
}

/* ── Navbar ── */
.navbar {
    background: var(--navy-deep) !important;
    border-radius: var(--radius-lg);
    padding: 14px 22px;
    margin: 16px 16px 0 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 3px solid var(--amber);
    box-shadow: var(--shadow-md);
}

.navbar-brand {
    font-family: 'Outfit', sans-serif;
    font-size: 1.4rem;
    font-weight: 800;
    color: var(--white) !important;
    text-decoration: none;
    letter-spacing: 0.02em;
    display: flex;
    align-items: center;
    gap: 8px;
}

.nav-login-btn {
    background: transparent;
    color: var(--amber) !important;
    font-family: 'Outfit', sans-serif;
    font-weight: 700;
    padding: 6px 18px;
    border-radius: var(--radius-pill);
    text-decoration: none;
    font-size: 0.76rem;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    border: 2px solid var(--amber);
    transition: 0.22s ease;
}
.nav-login-btn:hover {
    background: var(--amber);
    color: var(--navy-deep) !important;
}

/* ── Page wrapper ── */
.page-wrapper {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 28px 16px 32px;
}

/* ── Registration card ── */
.reg-card {
    background: var(--white);
    border: 1.5px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 36px 36px 32px;
    width: 100%;
    max-width: 780px;
    box-shadow: var(--shadow-md);
    position: relative;
    overflow: hidden;
}

.reg-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 5px;
    background: linear-gradient(90deg, var(--sky), var(--amber-warm));
}

/* ── Card header ── */
.card-header-block {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 28px;
    padding-bottom: 18px;
    border-bottom: 1.5px solid var(--sky-pale);
}

.header-icon {
    width: 50px;
    height: 50px;
    background: var(--navy-deep);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 3px solid var(--amber);
    flex-shrink: 0;
}
.header-icon i {
    font-size: 1.3rem;
    color: var(--amber);
}

.card-header-block h2 {
    font-family: 'Outfit', sans-serif;
    font-size: 1.45rem;
    font-weight: 800;
    color: var(--navy);
    text-transform: uppercase;
    letter-spacing: 0.04em;
    margin: 0 0 2px;
}
.card-header-block p {
    font-size: 0.82rem;
    color: var(--text-muted);
    margin: 0;
}

/* ── Section labels ── */
.section-divider {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 4px 0 14px;
    grid-column: span 2;
}
.section-divider::before {
    content: '';
    width: 4px;
    height: 16px;
    background: var(--amber);
    border-radius: 2px;
    flex-shrink: 0;
}
.section-divider span {
    font-family: 'Outfit', sans-serif;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.09em;
    color: var(--navy-mid);
}
.section-divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: var(--sky-pale);
}

/* ── Form grid ── */
.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px 20px;
}
.full { grid-column: span 2; }

/* ── Labels ── */
.field-label {
    display: block;
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--navy);
    margin-bottom: 5px;
    font-family: 'Outfit', sans-serif;
}

/* ── Input with icon ── */
.input-wrap {
    position: relative;
}
.input-wrap i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-muted);
    font-size: 0.95rem;
    pointer-events: none;
}
.input-wrap input,
.input-wrap select {
    width: 100%;
    padding: 10px 12px 10px 35px;
    border: 2px solid var(--border);
    border-radius: var(--radius-md);
    font-family: 'DM Sans', sans-serif;
    font-size: 0.88rem;
    color: var(--text-main);
    background: var(--white);
    transition: border-color 0.2s, box-shadow 0.2s;
    outline: none;
    appearance: none;
}
.input-wrap.no-icon input,
.input-wrap.no-icon select {
    padding-left: 12px;
}
.input-wrap input:focus,
.input-wrap select:focus {
    border-color: var(--sky);
    box-shadow: var(--shadow-glow);
}
.input-wrap input::placeholder {
    color: var(--text-muted);
    font-weight: 400;
}

/* Select arrow */
.input-wrap select {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%230D2F6E' stroke-width='2' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    padding-right: 34px;
}

/* ── Skill Level radio buttons ── */
.skill-group {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.skill-option {
    flex: 1;
    min-width: 90px;
}

.skill-option input[type="radio"] {
    display: none;
}

.skill-option label {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 9px 12px;
    border: 2px solid var(--border);
    border-radius: var(--radius-md);
    cursor: pointer;
    font-family: 'Outfit', sans-serif;
    font-size: 0.78rem;
    font-weight: 700;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    transition: 0.2s ease;
    background: var(--white);
    width: 100%;
    text-align: center;
}

.skill-option input[type="radio"]:checked + label {
    border-color: var(--sky);
    background: var(--sky-pale);
    color: var(--navy);
}

.skill-option label:hover {
    border-color: var(--sky-light);
    background: var(--sky-pale);
    color: var(--navy);
}

/* Skill level accent colors */
.skill-option.beginner input[type="radio"]:checked + label {
    border-color: #43A047;
    background: #E3F5EC;
    color: #1B7A4A;
}
.skill-option.intermediate input[type="radio"]:checked + label {
    border-color: var(--sky);
    background: var(--sky-pale);
    color: var(--navy-mid);
}
.skill-option.advanced input[type="radio"]:checked + label {
    border-color: var(--amber);
    background: var(--amber-pale);
    color: var(--navy-deep);
}

/* ── Error message ── */
.error-msg {
    background: var(--danger-bg);
    color: var(--danger);
    padding: 10px 14px;
    border-radius: var(--radius-md);
    margin-bottom: 20px;
    font-size: 0.82rem;
    border-left: 4px solid var(--danger);
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
    grid-column: span 2;
}

/* ── Submit button ── */
.btn-submit {
    grid-column: span 2;
    width: 100%;
    padding: 13px;
    background: var(--amber);
    color: var(--navy-deep);
    font-family: 'Outfit', sans-serif;
    font-weight: 800;
    font-size: 0.88rem;
    letter-spacing: 0.07em;
    text-transform: uppercase;
    border: 2px solid var(--amber);
    border-radius: var(--radius-pill);
    cursor: pointer;
    transition: 0.22s ease;
    margin-top: 6px;
}
.btn-submit:hover {
    background: transparent;
    color: var(--amber);
}

/* ── Login redirect ── */
.login-redirect {
    grid-column: span 2;
    text-align: center;
    padding-top: 4px;
}
.login-redirect span {
    font-size: 0.82rem;
    color: var(--text-muted);
}
.login-redirect a {
    color: var(--navy-mid);
    font-weight: 700;
    text-decoration: none;
    font-size: 0.82rem;
    transition: color 0.2s;
}
.login-redirect a:hover { color: var(--amber); }

/* ── Footer ── */
.page-footer {
    text-align: center;
    padding: 14px;
    font-size: 0.76rem;
    color: var(--text-muted);
}
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
    <a class="navbar-brand" href="/ICS_APP_DEV1/dashboard_and_admin/index.php">
        <i class="bi bi-dribbble" style="color:var(--amber);font-size:1.3rem"></i>
        NBSC Match Maker
    </a>
    <a href="/ICS_APP_DEV1/authentication/login.php" class="nav-login-btn">
        <i class="bi bi-box-arrow-in-right"></i> Sign In
    </a>
</nav>

<!-- REGISTRATION FORM -->
<div class="page-wrapper">
    <div class="reg-card">

        <!-- Card Header -->
        <div class="card-header-block">
            <div class="header-icon">
                <i class="bi bi-person-plus-fill"></i>
            </div>
            <div>
                <h2>Player Registration</h2>
                <p>Join the NBSC Basketball League — create your player profile</p>
            </div>
        </div>

        <form method="POST" class="form-grid">

            <?php if(isset($error)): ?>
            <div class="error-msg">
                <i class="bi bi-exclamation-circle-fill"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <!-- ACCOUNT SECTION -->
            <div class="section-divider full">
                <span><i class="bi bi-shield-lock" style="margin-right:5px"></i>Account</span>
            </div>

            <div>
                <label class="field-label">Username</label>
                <div class="input-wrap">
                    <i class="bi bi-person"></i>
                    <input type="text" name="username" placeholder="Choose a username" required>
                </div>
            </div>

            <div>
                <label class="field-label">Password</label>
                <div class="input-wrap">
                    <i class="bi bi-lock"></i>
                    <input type="password" name="password" placeholder="Create a password" required>
                </div>
            </div>

            <!-- PERSONAL SECTION -->
            <div class="section-divider full">
                <span><i class="bi bi-person-vcard" style="margin-right:5px"></i>Personal Info</span>
            </div>

            <div class="full">
                <label class="field-label">Full Name</label>
                <div class="input-wrap">
                    <i class="bi bi-type"></i>
                    <input type="text" name="full_name" placeholder="Enter your full name" required>
                </div>
            </div>

            <div>
                <label class="field-label">Student ID</label>
                <div class="input-wrap">
                    <i class="bi bi-card-text"></i>
                    <input type="text" name="student_id" placeholder="e.g. 2024-00001" required>
                </div>
            </div>

            <div>
                <label class="field-label">Contact Number</label>
                <div class="input-wrap">
                    <i class="bi bi-telephone"></i>
                    <input type="text" name="contact" placeholder="e.g. 09XXXXXXXXX" required>
                </div>
            </div>

            <div class="full">
                <label class="field-label">Course / Program</label>
                <div class="input-wrap">
                    <i class="bi bi-mortarboard"></i>
                    <input type="text" name="course" placeholder="e.g. BSIT, BSCS, BSBA" required>
                </div>
            </div>

            <!-- BASKETBALL SECTION -->
            <div class="section-divider full">
                <span><i class="bi bi-dribbble" style="margin-right:5px"></i>Basketball Profile</span>
            </div>

            <div class="full">
                <label class="field-label">Position</label>
                <div class="input-wrap">
                    <i class="bi bi-person-standing"></i>
                    <select name="position" required>
                        <option value="">Select your position</option>
                        <option>Point Guard</option>
                        <option>Shooting Guard</option>
                        <option>Small Forward</option>
                        <option>Power Forward</option>
                        <option>Center</option>
                    </select>
                </div>
            </div>

            <div class="full">
                <label class="field-label">Skill Level</label>
                <div class="skill-group">
                    <div class="skill-option beginner">
                        <input type="radio" name="skill" id="skill_beginner" value="Beginner" checked>
                        <label for="skill_beginner">
                            <i class="bi bi-star"></i> Beginner
                        </label>
                    </div>
                    <div class="skill-option intermediate">
                        <input type="radio" name="skill" id="skill_intermediate" value="Intermediate">
                        <label for="skill_intermediate">
                            <i class="bi bi-star-half"></i> Intermediate
                        </label>
                    </div>
                    <div class="skill-option advanced">
                        <input type="radio" name="skill" id="skill_advanced" value="Advanced">
                        <label for="skill_advanced">
                            <i class="bi bi-star-fill"></i> Advanced
                        </label>
                    </div>
                </div>
            </div>

            <!-- SUBMIT -->
            <button type="submit" name="submit_reg" class="btn-submit">
                <i class="bi bi-person-check-fill"></i> Create Player Account
            </button>

            <div class="login-redirect">
                <span>Already have an account? </span>
                <a href="/ICS_APP_DEV1/authentication/login.php">Sign in here</a>
            </div>

        </form>
    </div>
</div>

<div class="page-footer">
    NBSC Match Maker &mdash; Basketball Court Reservation System
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>