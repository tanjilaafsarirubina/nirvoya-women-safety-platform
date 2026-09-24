<?php
// api/login.php
session_start();
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    try {
        // 1. Check User Credentials
        $stmt = $conn->prepare("SELECT user_id, full_name, password_hash FROM Users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {

            // Issue a fresh session ID on login (prevents session fixation)
            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_name'] = $user['full_name'];

            // 2. CHECK ROLE: Is this user an Admin?
            $stmt_admin = $conn->prepare("SELECT employee_id FROM Admins WHERE employee_id = :uid");
            $stmt_admin->execute([':uid' => $user['user_id']]);

            if ($stmt_admin->rowCount() > 0) {
                // Yes, Admin -> Go to Admin Panel
                $_SESSION['role'] = 'Admin';
                header("Location: ../admin_panel.php");
            } else {
                // No, Member -> Go to Dashboard
                $_SESSION['role'] = 'Member';
                header("Location: ../dashboard.php");
            }
            exit();
        } else {
            header("Location: ../index.html?error=invalid");
            exit();
        }

    } catch (PDOException $e) {
        echo "Error: " . htmlspecialchars($e->getMessage());
    }
}
?>
