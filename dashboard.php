<?php
session_start();

// 1. SECURITY CHECK FIRST
// Check this immediately. If they aren't logged in, bye-bye.
if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit(); // Stop everything. Do not load DB, do not load HTML.
}

// Admins have their own panel
if (($_SESSION['role'] ?? '') === 'Admin') {
    header("Location: admin_panel.php");
    exit();
}

// 2. NOW connect to DB (Only if they are allowed to be here)
include 'api/db_connect.php';

$user_id = $_SESSION['user_id'];

// --- FEATURE 6 IMPLEMENTATION: PERSONAL STATS ---
try {
    // 1. Count Completed Journeys
    $stmt1 = $conn->prepare("SELECT COUNT(*) FROM Journeys WHERE member_id = :uid AND status = 'Completed'");
    $stmt1->execute([':uid' => $user_id]);
    $journey_count = $stmt1->fetchColumn();

    // 2. Count Reported Incidents
    $stmt2 = $conn->prepare("SELECT COUNT(*) FROM Incidents WHERE member_id = :uid");
    $stmt2->execute([':uid' => $user_id]);
    $incident_count = $stmt2->fetchColumn();

    // 3. Count Trusted Contacts
    $stmt3 = $conn->prepare("SELECT COUNT(*) FROM Trusted_Contacts WHERE member_id = :uid");
    $stmt3->execute([':uid' => $user_id]);
    $contact_count = $stmt3->fetchColumn();

    // 4. Is there a journey still in progress? (e.g. the tab was closed)
    $stmt4 = $conn->prepare("SELECT journey_id, end_loc FROM Journeys WHERE member_id = :uid AND status = 'Active' ORDER BY journey_id DESC LIMIT 1");
    $stmt4->execute([':uid' => $user_id]);
    $active_journey = $stmt4->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $journey_count = 0;
    $incident_count = 0;
    $contact_count = 0;
    $active_journey = false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nirvoya Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <div class="header">
        <div class="welcome">
            Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?> |
            <a href="contacts.php">Manage Contacts</a>
        </div>
        <a href="api/logout.php" class="logout">Logout</a>
    </div>

    <?php if (($_GET['msg'] ?? '') === 'reported'): ?>
        <div class="alert alert-success page-alert">Report submitted. An admin will review it before it appears on the community map.</div>
    <?php endif; ?>

    <?php if ($active_journey): ?>
        <div class="alert alert-info page-alert">
            You have a journey in progress to <strong><?php echo htmlspecialchars($active_journey['end_loc']); ?></strong>.
            <a href="active_journey.php?journey_id=<?php echo (int) $active_journey['journey_id']; ?>">Resume live tracking &rarr;</a>
        </div>
    <?php endif; ?>

    <!-- FEATURE 6: PERSONAL STATS DISPLAY -->
    <div class="stats-container">
        <div class="stat-box" style="border-left-color: #28a745;">
            <span class="stat-number"><?php echo (int) $journey_count; ?></span>
            <span class="stat-label">Safe Journeys</span>
        </div>
        <div class="stat-box" style="border-left-color: #ffc107;">
            <span class="stat-number"><?php echo (int) $incident_count; ?></span>
            <span class="stat-label">Reports Filed</span>
        </div>
        <div class="stat-box" style="border-left-color: #17a2b8;">
            <span class="stat-number"><?php echo (int) $contact_count; ?></span>
            <span class="stat-label">Trusted Contacts</span>
        </div>
    </div>

    <div class="grid">
        <!-- Feature 1: Safe Journey -->
        <div class="card">
            <h3>Start Safe Journey</h3>
            <p>Share your live location with trusted contacts.</p>
            <a href="start_journey.php" class="btn btn-safe">Start Journey</a>
        </div>

        <!-- Feature 5: Trusted Circle -->
        <div class="card">
            <h3>Trusted Contacts</h3>
            <p>Manage family & friends who receive your alerts.</p>
            <a href="contacts.php" class="btn btn-secondary">Manage Circle</a>
        </div>

        <!-- Feature 2: Report Incident -->
        <div class="card">
            <h3>Report Incident</h3>
            <p>Report harassment or safety issues. Your name is never shown on the public map.</p>
            <a href="report_incident.php" class="btn btn-report">File Report</a>
        </div>

        <!-- Feature 4: Community Map -->
        <div class="card">
            <h3>Safety Map</h3>
            <p>View verified incident hotspots in your area.</p>
            <a href="community_map.php" class="btn btn-info">View Map</a>
        </div>

        <!-- Feature 3: SOS -->
        <div class="card card-sos">
            <h3>EMERGENCY</h3>
            <p>Immediate alert to all contacts.</p>
            <button id="sos-btn" class="btn btn-sos" onclick="triggerSOS()">SOS ALERT</button>
            <p id="sos-status" class="sos-status"></p>
        </div>
    </div>

    <script>
        function resetSOS(btn) {
            btn.disabled = false;
            btn.innerText = "SOS ALERT";
        }

        function triggerSOS() {
            const btn = document.getElementById('sos-btn');
            const status = document.getElementById('sos-status');

            if (confirm("Are you sure? This will alert your trusted contacts.")) {
                btn.disabled = true;
                btn.innerText = "SENDING...";

                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(position => {
                        const formData = new FormData();
                        formData.append('lat', position.coords.latitude);
                        formData.append('lng', position.coords.longitude);

                        fetch('api/trigger_sos.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if(data.status === 'success') {
                                alert("ALERTS SENT! " + data.notified_count + " contacts notified.");
                                btn.innerText = "SOS SENT";
                                status.innerText = "Help is on the way.";
                            } else {
                                alert("Error: " + data.message);
                                resetSOS(btn);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert("Connection Failed");
                            resetSOS(btn);
                        });
                    }, error => {
                        // Permission denied / GPS unavailable: don't leave the button stuck
                        alert("Could not get your location: " + error.message);
                        resetSOS(btn);
                    }, { enableHighAccuracy: true, timeout: 10000 });
                } else {
                    alert("GPS required for SOS.");
                    resetSOS(btn);
                }
            }
        }
    </script>

</body>
</html>
