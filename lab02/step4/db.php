<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'gpa_calculator';

try {
    // Attempt to connect to the specific database
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // We suppress the error here because setup.php might be running to create it
    $pdo_error = $e->getMessage();
}
?>
