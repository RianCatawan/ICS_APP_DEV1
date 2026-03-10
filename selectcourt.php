<?php
session_start();
include "db.php";

$user_id = $_SESSION['user_id'];

if(isset($_POST['proceed'])){

$court = $_POST['court'];

$stmt = $conn->prepare("INSERT INTO usercourts (user_id, court)
VALUES (?, ?)
ON DUPLICATE KEY UPDATE court=?");

$stmt->bind_param("iss",$user_id,$court,$court);
$stmt->execute();
$stmt->close();

header("Location: match.php");
exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>HoopMatch | Select Court</title>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>

<style>

body{
margin:0;
font-family:Arial, sans-serif;
background:#000;
color:white;
}

/* ORANGE HEADER */
.top-bg{
position:absolute;
top:0;
left:0;
width:100%;
height:300px;
background:#F57C00;
z-index:-5;
}

/* CURVE */
.curve{
position:absolute;
bottom:-1px;
width:100%;
}

/* NAVBAR */
.navbar{
padding:20px 40px;
}

.navbar-brand{
color:white;
font-weight:bold;
font-size:24px;
text-decoration:none;
}

/* BACK BUTTON */
.back-btn{
position:absolute;
top:25px;
right:40px;
padding:8px 18px;
border-radius:8px;
border:2px solid #F57C00;
color:#F57C00;
text-decoration:none;
font-weight:bold;
transition:0.3s;
}

.back-btn:hover{
background:#F57C00;
color:black;
}

/* CARD */
.card{
margin-top:170px;
background:#111;
padding:45px;
border-radius:12px;
width:480px;
margin-left:auto;
margin-right:auto;
display:flex;
flex-direction:column;
gap:20px;
box-shadow:0 10px 25px rgba(0,0,0,0.6);
text-align:center;
}

/* MAP */
.map{
height:240px;
border-radius:10px;
}

/* DROPDOWN */
select{
padding:15px;
border-radius:10px;
border:2px solid #F57C00;
background:#000;
color:white;
font-size:16px;
width:100%;
}

/* BUTTON */
button{
padding:18px;
border:none;
border-radius:10px;
background:#F57C00;
font-weight:bold;
font-size:18px;
cursor:pointer;
transition:0.3s;
}

button:hover{
background:#ff8c00;
transform:scale(1.04);
}

</style>
</head>

<body>

<div class="top-bg">

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

<div class="navbar">
<a class="navbar-brand" href="index.php">HoopMatch</a>
</div>

<a href="createteam.php" class="back-btn">⬅ Back</a>

<div class="card">

<h2>Select Court</h2>

<div id="map" class="map"></div>

<form method="POST">

<select id="courtSelect" name="court" required>
<option value="">Select Court</option>
<option value="Court 1">plaza</option>
<option value="Court 2">tankulan</option>
<option value="Court 3">saint joseph</option>
</select>

<button name="proceed">Proceed</button>

</form>

</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>

/* Create Map */

const map = L.map('map').setView([8.359735,124.869206],18);

L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png',{
maxZoom:17,
attribution:'© OpenStreetMap'
}).addTo(map);

/* Court Locations */

const courts = {
"Court 1":[8.369345, 124.869061],
"Court 2":[8.368307, 124.866327],
"Court 3":[8.364335, 124.868374]
};

/* Add Markers */

for(const court in courts){

const marker = L.marker(courts[court]).addTo(map);

marker.bindPopup(court);

marker.on("click", ()=>{
document.getElementById("courtSelect").value = court;
});

}

/* Dropdown → Move Map */

document.getElementById("courtSelect").addEventListener("change", function(){

const selectedCourt = this.value;

if(courts[selectedCourt]){
map.setView(courts[selectedCourt],18);
}

});

</script>

</body>
</html>