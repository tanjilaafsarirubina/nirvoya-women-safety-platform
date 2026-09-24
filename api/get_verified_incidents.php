<?php
// api/get_verified_incidents.php
session_start();
include 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

try {
    // Join with Types to get the category name (e.g., "Stalking").
    // Reporter identity is deliberately NOT returned: the community map is anonymous.
    $sql = "SELECT i.gps_lat, i.gps_lng, i.end_lat, i.end_lng, i.description, i.incident_time, t.type_name, t.severity
            FROM Incidents i
            JOIN Incident_Categories ic ON i.incident_id = ic.incident_id
            JOIN Incident_Types t ON ic.type_id = t.type_id
            WHERE i.status = 'Verified'";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $incidents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($incidents);

} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
