<?php
// IMPROVED register.php (add_admin.php)

// Enable error reporting
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/debug.log');
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json');

// CORS headers if needed
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Check if user is logged in and an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['message' => 'Unauthorized access']);
    exit;
}

require '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get and validate input data
        $input = file_get_contents('php://input');
        if (empty($input)) {
            http_response_code(400);
            echo json_encode(['message' => 'No data received']);
            exit;
        }

        $data = json_decode($input, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            echo json_encode(['message' => 'Invalid JSON data']);
            exit;
        }

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

        // Validate password strength
        if (strlen($data['password']) < 6) {
            http_response_code(400);
            echo json_encode(['message' => 'Password must be at least 6 characters long']);
            exit;
        }

        // Validate email format
        $email = filter_var(trim($data['email']), FILTER_VALIDATE_EMAIL);
        if (!$email) {
            http_response_code(400);
            echo json_encode(['message' => 'Invalid email format']);
            exit;
        }

        // Get database connection
        $database = new Database();
        $db = $database->getConnection();
        
        if (!$db) {
            http_response_code(500);
            echo json_encode(['message' => 'Database connection failed']);
            exit;
        }

        // Check if email already exists
        $check_email = $db->prepare("SELECT id FROM users WHERE email = :email");
        $check_email->bindParam(':email', $email, PDO::PARAM_STR);
        
        if (!$check_email->execute()) {
            http_response_code(500);
            echo json_encode(['message' => 'Database query failed']);
            exit;
        }

        if ($check_email->rowCount() > 0) {
            http_response_code(400);
            echo json_encode(['message' => 'Email already registered']);
            exit;
        }

        // Prepare data for insertion
        $fullName = trim($data['fullName']);
        $department = trim($data['department']);
        $password = password_hash($data['password'], PASSWORD_DEFAULT);

        // Validate name and department length
        if (strlen($fullName) > 100) {
            http_response_code(400);
            echo json_encode(['message' => 'Full name too long (max 100 characters)']);
            exit;
        }

        if (strlen($department) > 50) {
            http_response_code(400);
            echo json_encode(['message' => 'Department name too long (max 50 characters)']);
            exit;
        }

        // Insert new admin user
        $query = "INSERT INTO users (username, full_name, department, email, password, role, created_at) 
                  VALUES (:username, :full_name, :department, :email, :password, 'admin', NOW())";
        
        $stmt = $db->prepare($query);
        
        if (!$stmt) {
            http_response_code(500);
            echo json_encode(['message' => 'Failed to prepare database statement']);
            exit;
        }

        // Use email as username for consistency
        $stmt->bindParam(':username', $email, PDO::PARAM_STR);
        $stmt->bindParam(':full_name', $fullName, PDO::PARAM_STR);
        $stmt->bindParam(':department', $department, PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':password', $password, PDO::PARAM_STR);

        if ($stmt->execute()) {
            http_response_code(201);
            echo json_encode([
                'message' => 'Admin registered successfully',
                'user_id' => $db->lastInsertId()
            ]);
        } else {
            $errorInfo = $stmt->errorInfo();
            error_log("Database insert failed: " . print_r($errorInfo, true));
            http_response_code(500);
            echo json_encode(['message' => 'Failed to register admin user']);
        }

    } catch (PDOException $e) {
        error_log("PDO Exception: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['message' => 'Database error occurred']);
    } catch (Exception $e) {
        error_log("General Exception: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['message' => 'An unexpected error occurred']);
    }
} else {
    http_response_code(405);
    echo json_encode(['message' => 'Method not allowed']);
}
?>