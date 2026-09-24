<?php
// start_journey.php
session_start();

// Security Check
if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit(); // Always exit after a header redirect!
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Start Safe Journey - Nirvoya</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="center-screen">

<div class="container">
    <h2>Start a Safe Journey</h2>
    <p>We will share your live location with your trusted contacts.</p>

    <form action="api/create_journey.php" method="POST">
        <input type="text" name="start_loc" placeholder="Starting Point (e.g. Work)" required>
        <input type="text" name="end_loc" placeholder="Destination (e.g. Home)" required>

        <!-- We need initial coords to start the map properly later -->
        <input type="hidden" id="lat" name="lat">
        <input type="hidden" id="lng" name="lng">

        <button type="submit" id="btn-start" class="btn btn-safe btn-block" disabled>Getting GPS...</button>
    </form>
    <br>
    <a href="dashboard.php">Cancel</a>
</div>

<script>
    // Get GPS immediately so we have a valid start point
    navigator.geolocation.getCurrentPosition(
        function(position) {
            document.getElementById('lat').value = position.coords.latitude;
            document.getElementById('lng').value = position.coords.longitude;
            document.getElementById('btn-start').innerText = "Start Journey Now";
            document.getElementById('btn-start').disabled = false;
        },
        function(error) {
            document.getElementById('btn-start').innerText = "Location access required";
            alert("Please allow Location Access to use this feature.");
        },
        { enableHighAccuracy: true, timeout: 10000 }
    );
</script>

</body>
</html>
