<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'api/config/database.php';

$database = new Database();
$db = $database->getConnection();

if ($db) {
    echo "Database connection successful!";
    // Optional: Test a query
    $query = "SELECT COUNT(*) FROM users";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $count = $stmt->fetchColumn();
    echo " Number of users: " . $count;
} else {
    echo "Database connection failed.";
}
?>