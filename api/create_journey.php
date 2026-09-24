<?php
// api/create_journey.php
session_start();
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $start = trim($_POST['start_loc'] ?? '');
    $end = trim($_POST['end_loc'] ?? '');
    $lat = is_numeric($_POST['lat'] ?? null) ? $_POST['lat'] : NULL;
    $lng = is_numeric($_POST['lng'] ?? null) ? $_POST['lng'] : NULL;

    // Random, unguessable token for the public tracking link.
    // (Sequential journey IDs would let anyone watch anyone's journey.)
    $token = bin2hex(random_bytes(16));

    try {
        // Insert new journey
        $sql = "INSERT INTO Journeys (member_id, share_token, start_loc, end_loc, current_lat, current_lng, status)
                VALUES (:uid, :token, :start, :end, :lat, :lng, 'Active')";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':uid'=>$uid, ':token'=>$token, ':start'=>$start, ':end'=>$end, ':lat'=>$lat, ':lng'=>$lng]);

        $jid = $conn->lastInsertId();

        // Redirect to the "Sharer" view (Active Journey Mode)
        header("Location: ../active_journey.php?journey_id=" . $jid);
        exit();

    } catch (PDOException $e) {
        die("Error: " . htmlspecialchars($e->getMessage()));
    }
}

header("Location: ../index.html");
exit();
?>
