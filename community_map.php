<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit(); // Stop execution immediately
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community Safety Map - Nirvoya</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="map-page">

<div class="header header-flush">
    <span class="welcome">🛡️ Community Safety Map</span>
    <a href="dashboard.php">Back to Dashboard</a>
</div>

<div id="map" class="map-full"></div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    // Initialize Map
    const map = L.map('map').setView([23.8103, 90.4125], 13); // Default Dhaka
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap'
    }).addTo(map);

    // Severity comes from the Incident_Types lookup table (1-10)
    function severityColor(severity) {
        return severity >= 7 ? '#dc3545' : severity >= 4 ? '#fd7e14' : '#ffc107';
    }

    // Build popup content with text nodes so user-submitted text can't inject HTML
    function buildPopup(report) {
        const div = document.createElement('div');
        const title = document.createElement('strong');
        title.textContent = report.type_name;
        div.appendChild(title);
        if (report.description) {
            div.appendChild(document.createElement('br'));
            div.appendChild(document.createTextNode(report.description));
        }
        div.appendChild(document.createElement('br'));
        const time = document.createElement('small');
        time.textContent = report.incident_time;
        div.appendChild(time);
        return div;
    }

    // Legend
    const legend = L.control({ position: 'bottomright' });
    legend.onAdd = function () {
        const div = L.DomUtil.create('div', 'map-legend');
        div.innerHTML = '<strong>Severity</strong>' +
            '<div><span style="background:#dc3545"></span>High (7-10)</div>' +
            '<div><span style="background:#fd7e14"></span>Medium (4-6)</div>' +
            '<div><span style="background:#ffc107"></span>Low (1-3)</div>';
        return div;
    };
    legend.addTo(map);

    // Fetch Data
    fetch('api/get_verified_incidents.php')
        .then(response => response.json())
        .then(data => {
            const bounds = [];
            data.forEach(report => {
                bounds.push([report.gps_lat, report.gps_lng]);
                const color = severityColor(Number(report.severity));

                // Create a Circle Marker (looks like a heat dot)
                L.circleMarker([report.gps_lat, report.gps_lng], {
                    color: color,
                    fillColor: color,
                    fillOpacity: 0.5,
                    radius: 10
                }).addTo(map)
                .bindPopup(buildPopup(report));

                // If the report has an end point (e.g. someone was followed), draw the route
                if (report.end_lat !== null && report.end_lng !== null) {
                    L.polyline([[report.gps_lat, report.gps_lng], [report.end_lat, report.end_lng]], {
                        color: color, weight: 3, dashArray: '6 6'
                    }).addTo(map);
                    bounds.push([report.end_lat, report.end_lng]);
                }
            });

            // Zoom to where the reports actually are
            if (bounds.length > 0) {
                map.fitBounds(bounds, { padding: [50, 50], maxZoom: 15 });
            }
        })
        .catch(err => console.error(err));
</script>

</body>
</html>
