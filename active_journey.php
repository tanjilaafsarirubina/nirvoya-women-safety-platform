<?php
// active_journey.php
session_start();

// Security: Must be logged in and have a valid Journey ID
if (!isset($_SESSION['user_id']) || !isset($_GET['journey_id'])) {
    header("Location: dashboard.php");
    exit();
}

include 'api/db_connect.php';

$journey_id = (int) $_GET['journey_id'];
$user_id = $_SESSION['user_id'];

// Fetch journey details just to confirm ownership
$stmt = $conn->prepare("SELECT * FROM Journeys WHERE journey_id = :jid AND member_id = :uid");
$stmt->execute([':jid' => $journey_id, ':uid' => $user_id]);
$journey = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$journey) {
    die("Journey not found or unauthorized.");
}

// Already finished? Nothing to track.
if ($journey['status'] !== 'Active') {
    header("Location: dashboard.php");
    exit();
}

// Build the public tracking link from wherever the app is actually hosted
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$base_path = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
$share_url = "$scheme://{$_SERVER['HTTP_HOST']}$base_path/track_journey.php?token=" . urlencode($journey['share_token']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Active Journey - Nirvoya</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="container">
    <h1><span class="pulse"></span>Live Tracking Active</h1>
    <p class="status">
        Sharing location with Trusted Contacts &mdash;
        <?php echo htmlspecialchars($journey['start_loc']); ?> &rarr; <?php echo htmlspecialchars($journey['end_loc']); ?>
    </p>

    <div class="tracking-link">
        <strong>Share this private link with your contacts:</strong><br>
        <a id="share-url" href="<?php echo htmlspecialchars($share_url); ?>" target="_blank"><?php echo htmlspecialchars($share_url); ?></a>
        <button type="button" class="btn btn-primary btn-sm" onclick="copyLink()">Copy</button>
    </div>

    <div id="map"></div>

    <p id="debug-info" class="debug-info">Initializing GPS...</p>

    <a href="api/end_journey.php?id=<?php echo $journey_id; ?>" class="btn-stop"
       onclick="return confirm('Have you arrived safely? This will stop sharing your location.');">I've Arrived &ndash; End Journey</a>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    const journeyId = <?php echo $journey_id; ?>;

    // Small map so the sharer can see what their contacts see
    const startLat = <?php echo json_encode((float) ($journey['current_lat'] ?? 23.8103)); ?>;
    const startLng = <?php echo json_encode((float) ($journey['current_lng'] ?? 90.4125)); ?>;
    const map = L.map('map').setView([startLat, startLng], 16);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap'
    }).addTo(map);
    const marker = L.marker([startLat, startLng]).addTo(map).bindPopup("You are here");

    function copyLink() {
        navigator.clipboard.writeText(document.getElementById('share-url').href)
            .then(() => alert("Tracking link copied!"));
    }

    function updateLocation() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(position => {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;

                document.getElementById('debug-info').innerText = "Last Update: " + new Date().toLocaleTimeString() + " (" + lat.toFixed(4) + ", " + lng.toFixed(4) + ")";
                marker.setLatLng([lat, lng]);
                map.panTo([lat, lng]);

                // Send to Backend
                const formData = new FormData();
                formData.append('journey_id', journeyId);
                formData.append('lat', lat);
                formData.append('lng', lng);

                fetch('api/update_location.php', {
                    method: 'POST',
                    body: formData
                }).then(response => response.json())
                  .then(data => console.log("Server says:", data.message));

            }, error => {
                console.error("GPS Error:", error);
                document.getElementById('debug-info').innerText = "GPS Error: " + error.message;
            }, { enableHighAccuracy: true, timeout: 10000 });
        }
    }

    // Run immediately, then every 5 seconds
    updateLocation();
    setInterval(updateLocation, 5000);
</script>

</body>
</html>
