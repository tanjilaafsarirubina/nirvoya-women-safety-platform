<?php
// api/end_journey.php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.html");
    exit();
}

if (isset($_GET['id'])) {
    $jid = (int) $_GET['id'];

    // Mark as Completed and set end time (only the owner can end their journey)
    $stmt = $conn->prepare("UPDATE Journeys SET status = 'Completed', end_time = NOW()
                            WHERE journey_id = :jid AND member_id = :uid AND status = 'Active'");
    $stmt->execute([':jid' => $jid, ':uid' => $_SESSION['user_id']]);
}

// Go back to Dashboard
header("Location: ../dashboard.php");
exit();
?>
