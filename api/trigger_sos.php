<?php
// api/trigger_sos.php
session_start();
include 'db_connect.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $lat = $_POST['lat'] ?? null;
    $lng = $_POST['lng'] ?? null;

    if (!is_numeric($lat) || !is_numeric($lng)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid GPS coordinates.']);
        exit();
    }

    try {
        // 1. Check if user is currently on an active journey
        // We want to link this Alert to the Journey if possible (Ternary Relationship logic)
        $stmt_check = $conn->prepare("SELECT journey_id FROM Journeys WHERE member_id = :uid AND status = 'Active' ORDER BY journey_id DESC LIMIT 1");
        $stmt_check->execute([':uid' => $uid]);
        $active_journey = $stmt_check->fetch(PDO::FETCH_ASSOC);

        $journey_id = $active_journey ? $active_journey['journey_id'] : NULL;

        // 2. Insert the Alert
        $sql = "INSERT INTO SOS_Alerts (member_id, journey_id, alert_time, gps_lat, gps_lng, status)
                VALUES (:uid, :jid, NOW(), :lat, :lng, 'Pending')";

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':uid' => $uid,
            ':jid' => $journey_id,
            ':lat' => $lat,
            ':lng' => $lng
        ]);

        // 3. Fetch Trusted Contacts to simulate sending messages
        $stmt_contacts = $conn->prepare("SELECT name, phone FROM Trusted_Contacts WHERE member_id = :uid");
        $stmt_contacts->execute([':uid' => $uid]);
        $contacts = $stmt_contacts->fetchAll(PDO::FETCH_ASSOC);

        // Return success and the list of people "notified"
        echo json_encode([
            'status' => 'success',
            'message' => 'SOS Alert Logged.',
            'notified_count' => count($contacts)
        ]);

    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Not logged in.']);
}
?>
