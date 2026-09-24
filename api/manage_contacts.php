<?php
// api/manage_contacts.php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized");
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

// Both actions change data, so both are POST-only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../contacts.php");
    exit();
}

// --- HANDLE ADDING CONTACT ---
if ($action == 'add') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $relation = $_POST['relationship'] ?? 'Other';

    if ($name === '' || $phone === '') {
        header("Location: ../contacts.php");
        exit();
    }

    try {
        $sql = "INSERT INTO Trusted_Contacts (member_id, name, phone, relationship) VALUES (:uid, :nm, :ph, :rel)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':uid' => $user_id, ':nm' => $name, ':ph' => $phone, ':rel' => $relation]);

        header("Location: ../contacts.php"); // Reload the page
        exit();
    } catch (PDOException $e) {
        die("Error adding contact: " . htmlspecialchars($e->getMessage()));
    }
}

// --- HANDLE DELETING CONTACT ---
if ($action == 'delete') {
    $contact_id = (int) ($_POST['id'] ?? 0);

    try {
        // Security: Ensure the contact actually belongs to the logged-in user!
        $sql = "DELETE FROM Trusted_Contacts WHERE contact_id = :cid AND member_id = :uid";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':cid' => $contact_id, ':uid' => $user_id]);

        header("Location: ../contacts.php"); // Reload the page
        exit();
    } catch (PDOException $e) {
        die("Error deleting contact: " . htmlspecialchars($e->getMessage()));
    }
}

header("Location: ../contacts.php");
exit();
?>
