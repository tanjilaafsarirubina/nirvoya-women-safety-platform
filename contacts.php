<?php
// contacts.php
session_start();

// 1. Security Check FIRST
if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

// 2. Connect to DB
include 'api/db_connect.php';

$user_id = $_SESSION['user_id'];

// Fetch existing contacts to display
$stmt = $conn->prepare("SELECT * FROM Trusted_Contacts WHERE member_id = :uid ORDER BY name");
$stmt->execute([':uid' => $user_id]);
$contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Trusted Contacts - Nirvoya</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="container">
    <h2>Manage Trusted Contacts</h2>

    <!-- Add New Contact Form -->
    <form action="api/manage_contacts.php" method="POST">
        <input type="hidden" name="action" value="add">
        <div class="form-group">
            <input type="text" name="name" placeholder="Name (e.g. Mom)" required>
            <input type="tel" name="phone" placeholder="Phone Number" required>
            <select name="relationship" required>
                <option value="Parent">Parent</option>
                <option value="Sibling">Sibling</option>
                <option value="Friend">Friend</option>
                <option value="Partner">Partner</option>
                <option value="Other">Other</option>
            </select>
            <button type="submit" class="btn btn-safe btn-add">Add</button>
        </div>
    </form>

    <!-- List of Contacts -->
    <h3>Your Circle</h3>
    <?php if (count($contacts) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Relationship</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($contacts as $contact): ?>
                <tr>
                    <td><?php echo htmlspecialchars($contact['name']); ?></td>
                    <td><?php echo htmlspecialchars($contact['phone']); ?></td>
                    <td><?php echo htmlspecialchars($contact['relationship']); ?></td>
                    <td>
                        <form action="api/manage_contacts.php" method="POST" class="inline-form"
                              onsubmit="return confirm('Remove this contact?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo (int) $contact['contact_id']; ?>">
                            <button type="submit" class="btn-delete">Remove</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="empty-state">No trusted contacts added yet.</p>
    <?php endif; ?>

    <a href="dashboard.php" class="back-link">&larr; Back to Dashboard</a>
</div>

</body>
</html>
