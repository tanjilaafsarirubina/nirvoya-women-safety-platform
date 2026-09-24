<?php
// api/db_connect.php

// 1. Database Credentials
// Defaults match a stock XAMPP install. Override with environment variables if needed.
$servername = getenv('DB_HOST') ?: "localhost";
$port       = getenv('DB_PORT') ?: "3306";
$username   = getenv('DB_USER') ?: "root";
$password   = getenv('DB_PASS') ?: "";       // Default XAMPP password is empty
$dbname     = getenv('DB_NAME') ?: "nirvoya_db";

try {
    // 2. Create the PDO Connection
    $conn = new PDO("mysql:host=$servername;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);

    // 3. Set Error Mode to Exception
    // This ensures PHP tells us exactly what goes wrong if a query fails
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch(PDOException $e) {
    // 4. Handle Errors
    // If connection fails, stop the script and show the error
    http_response_code(500);
    die("Database connection failed: " . $e->getMessage());
}
?>
