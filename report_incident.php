<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    // If not logged in, kick them back to login page
    header("Location: index.html");
    exit();
}

include 'api/db_connect.php';

// Load categories from the Incident_Types lookup table
$types = $conn->query("SELECT type_id, type_name FROM Incident_Types ORDER BY type_id")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Incident - Nirvoya</title>
    <!-- Leaflet CSS for the Map -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="container">
    <h2>Report Safety Incident</h2>

    <div id="msg" class="alert alert-error" <?php if (($_GET['error'] ?? '') !== 'location') echo 'hidden'; ?>>
        Please click on the map to pin where the incident happened.
    </div>

    <form id="report-form" action="api/report_incident.php" method="POST">

        <label>Incident Type:</label>
        <select name="type_id" required>
            <?php foreach ($types as $t): ?>
                <option value="<?php echo (int) $t['type_id']; ?>"><?php echo htmlspecialchars($t['type_name']); ?></option>
            <?php endforeach; ?>
        </select>

        <label>Description (Optional):</label>
        <textarea name="description" rows="3" placeholder="Describe what happened..."></textarea>

        <label>Location:</label>
        <p class="hint">Click once to pin where it started. Click again to mark where it ended (optional, e.g. a stalking route). A third click starts over.</p>
        <div id="map"></div>

        <!-- Hidden inputs to store the coordinates from the map -->
        <input type="hidden" id="lat" name="lat">
        <input type="hidden" id="lng" name="lng">
        <input type="hidden" id="end_lat" name="end_lat">
        <input type="hidden" id="end_lng" name="end_lng">

        <button type="submit" class="btn btn-report btn-block">Submit Report</button>
    </form>
    <br>
    <a href="dashboard.php">&larr; Back to Dashboard</a>
</div>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    var map = L.map('map').setView([23.8103, 90.4125], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(map);

    var startMarker = null;
    var endMarker = null;
    var pathLine = null;

    function setField(id, value) { document.getElementById(id).value = value; }

    map.on('click', function(e) {
        if (!startMarker) {
            // First Click: Set Start Point
            startMarker = L.marker(e.latlng, {title: "Start"}).addTo(map).bindPopup("Incident Started Here").openPopup();
            setField('lat', e.latlng.lat);
            setField('lng', e.latlng.lng);
            document.getElementById('msg').hidden = true;
        } else if (!endMarker) {
            // Second Click: Set End Point
            endMarker = L.marker(e.latlng, {title: "End"}).addTo(map).bindPopup("Incident Ended Here").openPopup();
            setField('end_lat', e.latlng.lat);
            setField('end_lng', e.latlng.lng);

            // Draw line
            pathLine = L.polyline([startMarker.getLatLng(), endMarker.getLatLng()], {color: 'red'}).addTo(map);
        } else {
            // Reset if they click a 3rd time: new start point, clear the old end point
            map.removeLayer(startMarker);
            map.removeLayer(endMarker);
            map.removeLayer(pathLine);
            startMarker = L.marker(e.latlng, {title: "Start"}).addTo(map).bindPopup("Incident Started Here").openPopup();
            endMarker = null;
            pathLine = null;
            setField('lat', e.latlng.lat);
            setField('lng', e.latlng.lng);
            setField('end_lat', '');
            setField('end_lng', '');
        }
    });

    // Hidden inputs are skipped by the browser's "required" check, so validate manually
    document.getElementById('report-form').addEventListener('submit', function(e) {
        if (!document.getElementById('lat').value) {
            e.preventDefault();
            document.getElementById('msg').hidden = false;
        }
    });
</script>

</body>
</html>
