<?php
// api/update_location.php
session_start();
include 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $jid = (int) ($_POST['journey_id'] ?? 0);
    $lat = $_POST['lat'] ?? null;
    $lng = $_POST['lng'] ?? null;

    if (!is_numeric($lat) || !is_numeric($lng)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid coordinates']);
        exit();
    }

    try {
        // Update the Journey record with new coordinates.
        // Only the owner can move their own journey, and only while it is still Active.
        $sql = "UPDATE Journeys SET current_lat = :lat, current_lng = :lng
                WHERE journey_id = :jid AND member_id = :uid AND status = 'Active'";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':lat' => $lat, ':lng' => $lng, ':jid' => $jid, ':uid' => $_SESSION['user_id']]);
        echo json_encode(['status' => 'success', 'message' => 'Location Updated']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
?>
