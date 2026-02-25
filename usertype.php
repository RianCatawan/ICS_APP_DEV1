<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>HoopMatch | Basketball Matchmaking</title>

<!-- Leaflet -->
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<style>
body {
  margin: 0;
  padding: 0;
  background: #0f172a;
  font-family: Arial, sans-serif;
  color: #e5e7eb;
}

/* Navbar */
.navbar {
  background: #0f172a;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px 30px;
}
.navbar-brand {
  color: #38bdf8;
  font-weight: bold;
  text-decoration: none;
}
.btn-back {
  background: #64748b;
  color: #fff;
  text-decoration: none;
  padding: 8px 15px;
  border-radius: 8px;
}
.btn-back:hover {
  background: #475569;
}

.page {
  display: flex;
  justify-content: center;
  align-items: flex-start;
  padding: 40px;
  gap: 30px;
  flex-wrap: wrap;
}

.form-panel {
  background: #020617;
  padding: 25px;
  border-radius: 14px;
  width: 360px;
}

.map-panel {
  background: #020617;
  padding: 15px;
  border-radius: 14px;
}

#map {
  width: 480px;
  height: 420px;
  border-radius: 12px;
  border: 3px solid #38bdf8;
}

h2 {
  text-align: center;
  color: #38bdf8;
  margin-bottom: 20px;
}

label {
  display: block;
  margin-top: 15px;
}

input, select {
  width: 100%;
  padding: 10px;
  margin-top: 5px;
  border-radius: 8px;
  border: none;
}

button {
  width: 100%;
  padding: 12px;
  margin-top: 12px;
  border: none;
  border-radius: 8px;
  font-weight: bold;
  cursor: pointer;
}

.proceed { background: #38bdf8; }
.find { background: #22c55e; }

@media (max-width: 900px) {
  .page { flex-direction: column; align-items: center; }
  #map { width: 100%; height: 350px; }
}
</style>
</head>

<body>

<!-- NAVBAR -->
<nav class="navbar">
  <a class="navbar-brand" href="index.php">🏀 HoopMatch</a>
  <a href="match+-start.php" class="btn-back">← Back</a>
</nav>

<!-- MAIN PAGE -->
<div class="page">

  <!-- LEFT: FORM PANEL -->
  <div class="form-panel">
    <h2>🏀 HoopMatch</h2>

    <label>Player Name</label>
    <input type="text" id="playerName" placeholder="Enter your name">

    <label>Game Type</label>
    <select id="gameType">
      <option>1v1</option>
      <option>2v2</option>
      <option>3v3</option>
      <option>4v4</option>
      <option>5v5</option>
    </select>

    <label>Barangay</label>
    <select id="barangay">
      <option value="">-- Select Barangay --</option>
      <option value="Tankulan">Tankulan (Center)</option>
      <option value="Poblacion">Poblacion</option>
      <option value="San Isidro">San Isidro</option>
      <option value="Maluko">Maluko</option>
      <option value="Damilag">Damilag</option>
      <option value="Dahilayan">Dahilayan</option>
      <option value="Kisolon">Kisolon</option>
    </select>

    <button class="proceed">Proceed</button>
    <a href="match-data.html"><button class="find">Find Match</button></a>
  </div>

  <!-- RIGHT: MAP PANEL -->
  <div class="map-panel">
    <div id="map"></div>
  </div>

</div>

<script>
// ================= MAP SETUP =================
const map = L.map('map').setView([8.419, 124.835], 14);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  attribution: '© OpenStreetMap contributors'
}).addTo(map);

let marker;

const barangayData = {
  "Tankulan": { coords: [8.419, 124.835], img: "https://upload.wikimedia.org/wikipedia/commons/6/6e/Barangay.png" },
  "Poblacion": { coords: [8.417, 124.841], img: "https://upload.wikimedia.org/wikipedia/commons/6/6e/Barangay.png" },
  "San Isidro": { coords: [8.421, 124.832], img: "https://upload.wikimedia.org/wikipedia/commons/6/6e/Barangay.png" },
  "Maluko": { coords: [8.423, 124.839], img: "https://upload.wikimedia.org/wikipedia/commons/6/6e/Barangay.png" },
  "Damilag": { coords: [8.426, 124.845], img: "https://upload.wikimedia.org/wikipedia/commons/6/6e/Barangay.png" },
  "Dahilayan": { coords: [8.422, 124.847], img: "https://upload.wikimedia.org/wikipedia/commons/6/6e/Barangay.png" },
  "Kisolon": { coords: [8.418, 124.848], img: "https://upload.wikimedia.org/wikipedia/commons/6/6e/Barangay.png" }
};

document.getElementById("barangay").addEventListener("change", function () {
  const selected = this.value;
  if (!barangayData[selected]) return;

  if (marker) map.removeLayer(marker);
  marker = L.marker(barangayData[selected].coords).addTo(map);
  marker.bindPopup(`<strong>${selected}</strong><br><img src="${barangayData[selected].img}" width="120">`).openPopup();
  map.setView(barangayData[selected].coords, 16);
});

// ================= MATCH LOGIC =================
document.querySelector('.proceed').addEventListener('click', () => {
  const playerName = document.getElementById('playerName').value.trim();
  if (!playerName) {
    alert('Please enter your Player Name before proceeding.');
    return;
  }
  alert('Please review your selections, then click Find Match.');
});

document.querySelector('.find').addEventListener('click', () => {
  const playerName = document.getElementById('playerName').value.trim();
  if (!playerName) {
    alert('Cannot proceed: Player Name is required.');
    return;
  }
  alert('Match submitted! (logic continues here)');
});
</script>

</body>
</html>