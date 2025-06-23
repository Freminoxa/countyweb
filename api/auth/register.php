<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in and an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['message' => 'Unauthorized']);
    exit;
}

require '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    // Validate required fields
    $required_fields = ['fullName', 'department', 'email', 'password', 'confirmPassword'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            http_response_code(400);
            echo json_encode(['message' => "Missing or empty required field: $field"]);
            exit;
        }
    }

    // Validate password match
    if ($data['password'] !== $data['confirmPassword']) {
        http_response_code(400);
        echo json_encode(['message' => 'Passwords do not match']);
        exit;
    }

    // Validate email format and uniqueness
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['message' => 'Invalid email format']);
        exit;
    }

    $db = (new Database())->getConnection();
    $check_email = $db->prepare("SELECT id FROM users WHERE email = :email");
    $check_email->bindParam(':email', $data['email']);
    $check_email->execute();
    if ($check_email->rowCount() > 0) {
        http_response_code(400);
        echo json_encode(['message' => 'Email already registered']);
        exit;
    }

    // Prepare data
    $fullName = trim($data['fullName']);
    $department = trim($data['department']);
    $email = trim($data['email']);
    $password = password_hash($data['password'], PASSWORD_DEFAULT);

    // Insert new user with admin role (set by current admin)
    try {
        $query = "INSERT INTO users (username, full_name, department, email, password, role) 
                  VALUES (:username, :full_name, :department, :email, :password, 'admin')";
        $stmt = $db->prepare($query);

        // Use email as username for simplicity (can be modified to separate username)
        $stmt->bindParam(':username', $email);
        $stmt->bindParam(':full_name', $fullName);
        $stmt->bindParam(':department', $department);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $password);

        if ($stmt->execute()) {
            echo json_encode(['message' => 'User registered successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'Failed to register user']);
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