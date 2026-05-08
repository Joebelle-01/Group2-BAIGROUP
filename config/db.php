<?php
/**
 * Database Configuration
 * Uses PDO for secure and modern database interaction
 */

// Enable Error Reporting for Debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$db   = 'payroll_db';
$user = 'root';
$pass = ''; // Default XAMPP password is empty
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
    // Include centralized constants
    require_once __DIR__ . '/../includes/constants.php';

} catch (\PDOException $e) {
     // For academic projects, simple error message is fine
     die("Connection failed: " . $e->getMessage());
}
?>
