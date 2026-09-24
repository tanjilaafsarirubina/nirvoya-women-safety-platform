<?php
// api/get_location.php
// Public endpoint used by the tracking page. Looks journeys up by their
// random share token, never by the sequential journey_id.
include 'db_connect.php';

header('Content-Type: application/json');

$token = $_GET['token'] ?? '';

$stmt = $conn->prepare("SELECT current_lat, current_lng, status FROM Journeys WHERE share_token = :token");
$stmt->execute([':token' => $token]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    echo json_encode([
        'status' => 'success',
        'lat' => $row['current_lat'],
        'lng' => $row['current_lng'],
        'journey_status' => $row['status']
    ]);
} else {
    http_response_code(404);
    echo json_encode(['status' => 'error']);
}
?>
