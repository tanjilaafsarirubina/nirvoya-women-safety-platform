<?php
// track_journey.php
// Public page opened by trusted contacts (no login needed).
// Access is granted by the random share token in the link.
include 'api/db_connect.php';

$token = $_GET['token'] ?? '';

// Initial Fetch to get Start Location and Name
$stmt = $conn->prepare("SELECT j.*, u.full_name FROM Journeys j JOIN Users u ON j.member_id = u.user_id WHERE j.share_token = :token");
$stmt->execute([':token' => $token]);
$trip = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$trip) {
    http_response_code(404);
    die("Journey not found. The tracking link may be incorrect.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tracking <?php echo htmlspecialchars($trip['full_name']); ?> - Nirvoya</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="info-box">
    <strong><?php echo htmlspecialchars($trip['full_name']); ?></strong> is on a Safe Journey.<br>
    <small>From: <?php echo htmlspecialchars($trip['start_loc']); ?> &nbsp;&rarr;&nbsp; To: <?php echo htmlspecialchars($trip['end_loc']); ?></small>
    <span class="status-badge" id="status-badge">LIVE</span>
</div>

<div id="map" class="map-full"></div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    const token = <?php echo json_encode($trip['share_token']); ?>;
    const initialStatus = <?php echo json_encode($trip['status']); ?>;

    // Initialize Map
    // Start with the initial coordinates from PHP, or a default (Dhaka)
    const startLat = <?php echo json_encode((float) ($trip['current_lat'] ?? 23.8103)); ?>;
    const startLng = <?php echo json_encode((float) ($trip['current_lng'] ?? 90.4125)); ?>;

    const map = L.map('map').setView([startLat, startLng], 16);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap'
    }).addTo(map);

    // Create the "Person" Marker (pure CSS pulsing dot, no external image)
    const iconPerson = L.divIcon({
        className: '',
        html: '<div class="person-dot"></div>',
        iconSize: [22, 22],
        iconAnchor: [11, 11],
        popupAnchor: [0, -12]
    });

    let marker = L.marker([startLat, startLng], {icon: iconPerson}).addTo(map)
                  .bindPopup("Latest Location").openPopup();

    function markArrived() {
        const badge = document.getElementById('status-badge');
        badge.innerText = "ARRIVED";
        badge.classList.add('arrived');
    }

    // Polling Function
    function fetchLocation() {
        fetch(`api/get_location.php?token=${encodeURIComponent(token)}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    if (data.lat !== null && data.lng !== null) {
                        const newLatLng = [data.lat, data.lng];
                        marker.setLatLng(newLatLng);
                        map.panTo(newLatLng);
                    }

                    if (data.journey_status !== 'Active') {
                        markArrived();
                        alert("Journey Ended.");
                        clearInterval(poller); // Stop updating
                    }
                }
            })
            .catch(err => console.error(err));
    }

    // Update every 5 seconds (unless the journey is already over)
    let poller = null;
    if (initialStatus === 'Active') {
        poller = setInterval(fetchLocation, 5000);
    } else {
        markArrived();
    }
</script>

</body>
</html>
