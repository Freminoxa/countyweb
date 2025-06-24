<?php

// Enable error reporting
ini_set('display_errors', 0); // Don't show on browser
ini_set('log_errors', 1); // Log errors
ini_set('error_log', __DIR__ . '/debug.log'); // Log to file
error_reporting(E_ALL); // Report all errors

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    http_response_code(403);
    echo json_encode(['message' => 'Unauthorized']);
    exit;
}
require 'api/config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $required_fields = ['fullName', 'department', 'email', 'password', 'confirmPassword'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            http_response_code(400);
            echo json_encode(['message' => "Missing required field: $field"]);
            exit;
        }
    }

    if ($data['password'] !== $data['confirmPassword']) {
        http_response_code(400);
        echo json_encode(['message' => 'Passwords do not match']);
        exit;
    }

    $fullName = trim($data['fullName']);
    $department = trim($data['department']);
    $email = trim($data['email']);
    $password = password_hash(trim($data['password']), PASSWORD_DEFAULT);

    try {
        $db = (new Database())->getConnection();
        $query = "INSERT INTO users (username, password, role, full_name, email, department) VALUES (:username, :password, 'admin', :full_name, :email, :department)";
        $stmt = $db->prepare($query);
        $username = strtolower(str_replace(' ', '_', $fullName)); // Generate username from full name
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $password);
        $stmt->bindParam(':full_name', $fullName);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':department', $department);

        if ($stmt->execute()) {
            echo json_encode(['message' => 'Admin added successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'Failed to add admin']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['message' => 'Method not allowed']);
}
?>  