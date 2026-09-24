<?php
// api/register.php
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Get data from the form
    $name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $raw_password = $_POST['password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $dob = $_POST['dob'] ?? '';
    $blood = !empty($_POST['blood_group']) ? $_POST['blood_group'] : NULL;

    // Basic server-side validation (the browser checks can be bypassed)
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($raw_password) < 6 || $dob === '') {
        header("Location: ../register.html?error=invalid");
        exit();
    }

    // 2. Hash the password (SECURITY BEST PRACTICE)
    // Never store plain text passwords!
    $password_hash = password_hash($raw_password, PASSWORD_DEFAULT);

    try {
        // Start a Transaction (To ensure both inserts happen, or neither)
        $conn->beginTransaction();

        // 3. Insert into USERS table
        $sql_user = "INSERT INTO Users (full_name, email, password_hash, phone_number, date_of_birth, blood_group)
                     VALUES (:name, :email, :pass, :phone, :dob, :blood)";

        $stmt = $conn->prepare($sql_user);
        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':pass' => $password_hash,
            ':phone' => $phone,
            ':dob' => $dob,
            ':blood' => $blood
        ]);

        // 4. Get the ID of the new user
        $new_user_id = $conn->lastInsertId();

        // 5. Insert into MEMBERS table (Hierarchy Logic)
        $sql_member = "INSERT INTO Members (member_id) VALUES (:id)";
        $stmt = $conn->prepare($sql_member);
        $stmt->execute([':id' => $new_user_id]);

        // Commit the transaction
        $conn->commit();

        header("Location: ../index.html?registered=1");
        exit();

    } catch (PDOException $e) {
        // If something goes wrong, roll back changes
        $conn->rollBack();

        if ($e->getCode() == 23000) {
            header("Location: ../register.html?error=exists");
            exit();
        } else {
            echo "Error: " . htmlspecialchars($e->getMessage());
        }
    }
}
?>
