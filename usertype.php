<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>HoopMatch | Select Court</title>

<!-- Leaflet -->
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<style>
body {
  margin: 0;
  padding: 40px;
  font-family: Arial, sans-serif;
  background: #0f172a;
  color: #e5e7eb;
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100vh;
}

.map-card {
  background: #020617;
  border-radius: 16px;
  padding: 15px;
  width: 500px;
  box-shadow: 0 0 20px rgba(56, 189, 248, 0.3);
  display: flex;
  flex-direction: column;
  gap: 12px;
}

/* Highlighted select dropdown */
#court {
  padding: 10px 12px;
  border-radius: 12px;
  border: 2px solid #38bdf8;
  background: #0f172a;
  color: #e5e7eb;
  font-weight: bold;
  font-size: 16px;
  outline: none;
  transition: all 0.3s;
}

#court:focus {
  border-color: #22c55e;
  box-shadow: 0 0 10px rgba(34, 197, 94, 0.5);
}

/* Map inside the card */
#map {
  width: 100%;
  height: 400px;
  border-radius: 12px;
  border: 3px solid #38bdf8;
}

/* Proceed button */
#proceedBtn {
  padding: 12px;
  border: none;
  border-radius: 12px;
  background: #38bdf8;
  color: #020617;
  font-weight: bold;
  font-size: 16px;
  cursor: pointer;
  transition: 0.3s;
}

#proceedBtn:hover {
  background: #22c55e;
  color: #ffffff;
}
</style>
</head>
<body>

<div class="map-card">
  <!-- Dropdown inside the box -->
  <select id="court">
    <option value="">-- Select Court --</option>
    <option value="Saint Joseph Court">Saint Joseph Court</option>
    <option value="Plaza">Plaza Court</option>
    <option value="Tankulan">Tankulan Court</option>
  </select>

  <!-- Map -->
  <div id="map"></div>

  <!-- Proceed Button -->
  <button id="proceedBtn">Proceed</button>
</div>

<script>
// Initialize Leaflet map centered around Manolo Fortich
const map = L.map('map').setView([8.419, 124.835], 14);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  attribution: '© OpenStreetMap contributors'
}).addTo(map);

let marker;

// Court coordinates (approximate)
const courtData = {
  "Saint Joseph Court": { coords: [8.420, 124.836] },
  "Plaza": { coords: [8.417, 124.841] },
  "Tankulan": { coords: [8.419, 124.835] }
};

// Update map when a court is selected
document.getElementById("court").addEventListener("change", function () {
  const selected = this.value;
  if (!courtData[selected]) return;

  if (marker) map.removeLayer(marker);
  marker = L.marker(courtData[selected].coords).addTo(map);
  marker.bindPopup(`<strong>${selected}</strong>`).openPopup();
  map.setView(courtData[selected].coords, 16);
});

// Proceed button click
document.getElementById("proceedBtn").addEventListener("click", () => {
  const selectedCourt = document.getElementById("court").value;
  if (!selectedCourt) {
    alert("Please select a court before proceeding.");
    return;
  }
  alert(`Proceeding with ${selectedCourt}!`);
  // Here you can redirect or submit data
});
</script>

</body>
</html>