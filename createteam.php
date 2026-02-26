<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>HoopMatch | Create Team</title>

<!-- Bootstrap (optional for layout) -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body {
  margin: 0;
  padding: 40px;
  font-family: Arial, sans-serif;
  background: #0f172a;
  color: #e5e7eb;
  display: flex;
  flex-direction: column;
  align-items: center;
  min-height: 100vh;
}

/* Page Header */
h1 {
  color: #38bdf8;
  margin-bottom: 30px;
  text-shadow: 0 0 10px #38bdf8;
}

/* Form container */
.team-card {
  background: #020617;
  border-radius: 16px;
  padding: 25px 30px;
  width: 400px;
  display: flex;
  flex-direction: column;
  gap: 20px;
  box-shadow: 0 0 20px rgba(56, 189, 248, 0.3);
}

/* Input fields */
input {
  padding: 12px 15px;
  border-radius: 12px;
  border: 2px solid #38bdf8;
  background: #0f172a;
  color: #e5e7eb;
  font-weight: bold;
  font-size: 16px;
  outline: none;
  transition: all 0.3s;
  width: 100%;
}

input:focus {
  border-color: #22c55e;
  box-shadow: 0 0 10px rgba(34, 197, 94, 0.5);
}

/* Buttons */
button {
  padding: 14px;
  border: none;
  border-radius: 12px;
  font-weight: bold;
  font-size: 16px;
  cursor: pointer;
  transition: 0.3s;
}

#createTeamBtn {
  background: #38bdf8;
  color: #020617;
}

#createTeamBtn:hover {
  background: #22c55e;
  color: #ffffff;
}

/* Optional: Team preview box */
.team-preview {
  background: #0f172a;
  border-radius: 12px;
  padding: 15px;
  border: 2px solid #38bdf8;
  text-align: center;
  font-weight: bold;
  color: #38bdf8;
  display: none; /* Initially hidden */
}
</style>
</head>
<body>

<h1>Create Team</h1>

<div class="team-card">
  <label for="teamName">Team Name</label>
  <input type="text" id="teamName" placeholder="Enter your team name">

  <label for="teamCaptain">Team Captain</label>
  <input type="text" id="teamCaptain" placeholder="Enter captain's name">

  <button id="createTeamBtn">Create Team</button>

  <!-- Optional team preview -->
  <div class="team-preview" id="teamPreview"></div>
</div>

<script>
const createBtn = document.getElementById('createTeamBtn');
const teamPreview = document.getElementById('teamPreview');

createBtn.addEventListener('click', () => {
  const name = document.getElementById('teamName').value.trim();
  const captain = document.getElementById('teamCaptain').value.trim();

  if (!name || !captain) {
    alert('Please fill all fields to create the team.');
    return;
  }

  // Display team preview
  teamPreview.style.display = 'block';
  teamPreview.innerHTML = `
    Team <strong>${name}</strong> <br>
    Captain: <strong>${captain}</strong>
  `;

  alert(`Team "${name}" created successfully!`);
});
</script>

</body>
</html>