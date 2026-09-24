<?php
// admin_panel.php
session_start();

// 1. Security Check FIRST
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.html");
    exit();
}

// 2. Connect to DB
include 'api/db_connect.php';

$admin_id = $_SESSION['user_id'];

// --- ACTIONS (POST only, so a link or image tag can't trigger them) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);

    if ($action === 'verify') {
        // ACTION 1: VERIFY REPORT
        $stmt = $conn->prepare("UPDATE Incidents SET status = 'Verified', employee_id = :eid WHERE incident_id = :iid");
        $stmt->execute([':eid' => $admin_id, ':iid' => $id]);
    } elseif ($action === 'reject') {
        // ACTION 2: REJECT REPORT
        $stmt = $conn->prepare("UPDATE Incidents SET status = 'False Report', employee_id = :eid WHERE incident_id = :iid");
        $stmt->execute([':eid' => $admin_id, ':iid' => $id]);
    } elseif ($action === 'resolve_sos') {
        // ACTION 3: RESOLVE SOS ALERT
        $stmt = $conn->prepare("UPDATE SOS_Alerts SET status = 'Resolved' WHERE alert_id = :aid");
        $stmt->execute([':aid' => $id]);
    }

    header("Location: admin_panel.php");
    exit();
}

// Fetch SOS Alerts (pending first), with the journey if the alert happened during one
$sql_sos = "SELECT s.*, u.full_name, u.phone_number, j.end_loc, j.share_token, j.status AS journey_status
            FROM SOS_Alerts s
            JOIN Users u ON s.member_id = u.user_id
            LEFT JOIN Journeys j ON s.journey_id = j.journey_id
            ORDER BY (s.status = 'Pending') DESC, s.alert_time DESC";
$alerts = $conn->query($sql_sos)->fetchAll(PDO::FETCH_ASSOC);

// Fetch All Incidents (LEFT JOIN so reports from deleted/anonymous members still show)
$sql = "SELECT i.*, u.full_name, t.type_name, a.full_name AS admin_name
        FROM Incidents i
        LEFT JOIN Users u ON i.member_id = u.user_id
        JOIN Incident_Categories ic ON i.incident_id = ic.incident_id
        JOIN Incident_Types t ON ic.type_id = t.type_id
        LEFT JOIN Users a ON i.employee_id = a.user_id
        ORDER BY i.incident_time DESC";
$reports = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);

function map_link($lat, $lng) {
    return "https://www.google.com/maps?q=" . (float) $lat . "," . (float) $lng;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Nirvoya</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="container container-wide">
    <h2>🛡️ Safety Operations Center</h2>
    <p>Logged in as: <?php echo htmlspecialchars($_SESSION['user_name']); ?> | <a href="api/logout.php" class="logout">Logout</a></p>

    <h3>SOS Alerts</h3>
    <?php if (count($alerts) > 0): ?>
    <div class="table-scroll">
    <table>
        <thead>
            <tr>
                <th>Time</th>
                <th>Member</th>
                <th>Phone</th>
                <th>Location</th>
                <th>During Journey</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($alerts as $a): ?>
            <tr>
                <td class="nowrap"><?php echo htmlspecialchars($a['alert_time']); ?></td>
                <td><?php echo htmlspecialchars($a['full_name']); ?></td>
                <td><?php echo htmlspecialchars($a['phone_number']); ?></td>
                <td class="nowrap"><a href="<?php echo map_link($a['gps_lat'], $a['gps_lng']); ?>" target="_blank">View Map</a></td>
                <td>
                    <?php if ($a['journey_id']): ?>
                        To <?php echo htmlspecialchars($a['end_loc']); ?>
                        <?php if ($a['journey_status'] === 'Active'): ?>
                            (<a href="track_journey.php?token=<?php echo urlencode($a['share_token']); ?>" target="_blank">live</a>)
                        <?php endif; ?>
                    <?php else: ?>
                        &mdash;
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($a['status'] == 'Pending'): ?>
                        <span class="badge badge-false">Pending</span>
                    <?php else: ?>
                        <span class="badge badge-ver">Resolved</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($a['status'] == 'Pending'): ?>
                        <form method="POST" class="inline-form">
                            <input type="hidden" name="action" value="resolve_sos">
                            <input type="hidden" name="id" value="<?php echo (int) $a['alert_id']; ?>">
                            <button type="submit" class="btn btn-safe btn-sm">Mark Resolved</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php else: ?>
        <p class="empty-state">No SOS alerts.</p>
    <?php endif; ?>

    <h3>Incident Reports</h3>
    <?php if (count($reports) > 0): ?>
    <div class="table-scroll">
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Reported By</th>
                <th>Location</th>
                <th>Description</th>
                <th>Status</th>
                <th style="min-width: 150px;">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($reports as $r): ?>
            <tr>
                <td class="nowrap"><?php echo htmlspecialchars($r['incident_time']); ?></td>
                <td><?php echo htmlspecialchars($r['type_name']); ?></td>
                <td><?php echo $r['full_name'] !== null ? htmlspecialchars($r['full_name']) : '<em>Anonymous</em>'; ?></td>
                <td class="nowrap">
                    <a href="<?php echo map_link($r['gps_lat'], $r['gps_lng']); ?>" target="_blank">View Map</a>
                </td>
                <td><?php echo htmlspecialchars($r['description'] ?? ''); ?></td>
                <td>
                    <?php if($r['status'] == 'Verified'): ?>
                        <span class="badge badge-ver">Verified</span>
                    <?php elseif($r['status'] == 'False Report'): ?>
                        <span class="badge badge-false">Rejected</span>
                    <?php else: ?>
                        <span class="badge badge-un">Unverified</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if($r['status'] == 'Unverified'): ?>
                        <!-- Show buttons only if Unverified -->
                        <form method="POST" class="inline-form">
                            <input type="hidden" name="action" value="verify">
                            <input type="hidden" name="id" value="<?php echo (int) $r['incident_id']; ?>">
                            <button type="submit" class="btn btn-primary btn-sm">Verify</button>
                        </form>
                        <form method="POST" class="inline-form" onsubmit="return confirm('Mark this as False Report?');">
                            <input type="hidden" name="action" value="reject">
                            <input type="hidden" name="id" value="<?php echo (int) $r['incident_id']; ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Reject</button>
                        </form>
                    <?php else: ?>
                        <!-- If already processed, show which Admin did it -->
                        <small class="muted">Processed by <?php echo htmlspecialchars($r['admin_name'] ?? 'Admin #' . $r['employee_id']); ?></small>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php else: ?>
        <p class="empty-state">No incident reports yet.</p>
    <?php endif; ?>
</div>

</body>
</html>
