<?php
// api/report_incident.php
session_start();
include 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Please login first.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id']; // Get the logged-in user's ID
    $type_id = (int) ($_POST['type_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $lat = $_POST['lat'] ?? '';
    $lng = $_POST['lng'] ?? '';

    // A pinned start location is mandatory
    if (!is_numeric($lat) || !is_numeric($lng)) {
        header("Location: ../report_incident.php?error=location");
        exit();
    }

    // Capture the End Location coordinates (if they exist)
    // Only present if the user clicked the map a second time
    $end_lat = is_numeric($_POST['end_lat'] ?? null) ? $_POST['end_lat'] : NULL;
    $end_lng = is_numeric($_POST['end_lng'] ?? null) ? $_POST['end_lng'] : NULL;

    try {
        $conn->beginTransaction();

        // 1. Insert into INCIDENTS table
        $sql = "INSERT INTO Incidents (member_id, description, incident_time, gps_lat, gps_lng, end_lat, end_lng)
                VALUES (:uid, :desc, NOW(), :lat, :lng, :elat, :elng)";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':uid' => $user_id,
            ':desc' => $description,
            ':lat' => $lat,
            ':lng' => $lng,
            ':elat' => $end_lat,
            ':elng' => $end_lng
        ]);

        $incident_id = $conn->lastInsertId();

        // 2. Insert into INCIDENT_CATEGORIES (Junction Table)
        $sql_cat = "INSERT INTO Incident_Categories (incident_id, type_id) VALUES (:iid, :tid)";
        $stmt_cat = $conn->prepare($sql_cat);
        $stmt_cat->execute([':iid' => $incident_id, ':tid' => $type_id]);

        $conn->commit();

        header("Location: ../dashboard.php?msg=reported");
        exit();

    } catch (PDOException $e) {
        $conn->rollBack();
        echo "Error: " . htmlspecialchars($e->getMessage());
    }
}
?>
