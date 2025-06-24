<?php
// At the VERY TOP with no whitespace before
ob_start(); // Start output buffering

// Enable error reporting
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/debug.log');
error_reporting(E_ALL);

// Start session with strict settings
session_start([
    'cookie_secure' => isset($_SERVER['HTTPS']),
    'cookie_httponly' => true,
    'use_strict_mode' => true
]);

// Set headers
header('Content-Type: application/json; charset=utf-8');

// CORS headers - adjust as needed for your environment
header('Access-Control-Allow-Origin: ' . (isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*'));
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Check authentication
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    ob_end_clean();
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// Check authorization
if (!in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    ob_end_clean();
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Insufficient privileges']);
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
        $required_fields = ['title', 'department', 'status', 'manager', 'startDate', 'description'];
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || empty(trim($data[$field]))) {
                http_response_code(400);
                echo json_encode(['message' => "Missing or empty required field: $field"]);
                exit;
            }
        }

        // Get database connection
        $database = new Database();
        $db = $database->getConnection();
        
        if (!$db) {
            http_response_code(500);
            echo json_encode(['message' => 'Database connection failed']);
            exit;
        }

        // Check department authorization (if not super admin)
        if ($_SESSION['role'] !== 'super_admin') {
            $userQuery = "SELECT department FROM users WHERE id = :id";
            $userStmt = $db->prepare($userQuery);
            $userStmt->bindParam(':id', $_SESSION['user_id'], PDO::PARAM_INT);
            
            if (!$userStmt->execute()) {
                http_response_code(500);
                echo json_encode(['message' => 'Failed to verify user department']);
                exit;
            }
            
            $userDept = $userStmt->fetchColumn();
            if ($userDept !== trim($data['department'])) {
                http_response_code(403);
                echo json_encode(['message' => 'Unauthorized to add projects for this department']);
                exit;
            }
        }

        // Validate status
        $valid_statuses = ['finished', 'proposed', 'unfinished'];
        if (!in_array($data['status'], $valid_statuses)) {
            http_response_code(400);
            echo json_encode(['message' => 'Invalid status value. Must be: ' . implode(', ', $valid_statuses)]);
            exit;
        }

        // Sanitize and validate data
        $title = trim($data['title']);
        $department = trim($data['department']);
        $status = trim($data['status']);
        $manager = trim($data['manager']);
        $start_date = trim($data['startDate']);
        $end_date = !empty(trim($data['endDate'])) ? trim($data['endDate']) : null;
        $description = trim($data['description']);
        $budget = isset($data['budget']) && is_numeric($data['budget']) ? floatval($data['budget']) : 0.00;

        // Validate field lengths
        if (strlen($title) > 255) {
            http_response_code(400);
            echo json_encode(['message' => 'Project title too long (max 255 characters)']);
            exit;
        }

        if (strlen($department) > 100) {
            http_response_code(400);
            echo json_encode(['message' => 'Department name too long (max 100 characters)']);
            exit;
        }

        if (strlen($manager) > 100) {
            http_response_code(400);
            echo json_encode(['message' => 'Manager name too long (max 100 characters)']);
            exit;
        }

        // Validate dates
        if (!DateTime::createFromFormat('Y-m-d', $start_date)) {
            http_response_code(400);
            echo json_encode(['message' => 'Invalid start date format. Use YYYY-MM-DD']);
            exit;
        }

        if ($end_date && !DateTime::createFromFormat('Y-m-d', $end_date)) {
            http_response_code(400);
            echo json_encode(['message' => 'Invalid end date format. Use YYYY-MM-DD']);
            exit;
        }

        if ($end_date && strtotime($end_date) < strtotime($start_date)) {
            http_response_code(400);
            echo json_encode(['message' => 'End date cannot be earlier than start date']);
            exit;
        }

        // Validate budget
        if ($budget < 0) {
            http_response_code(400);
            echo json_encode(['message' => 'Budget cannot be negative']);
            exit;
        }

        // Insert project
        $query = "INSERT INTO projects (title, department, status, manager, start_date, end_date, description, budget, created_at) 
                  VALUES (:title, :department, :status, :manager, :start_date, :end_date, :description, :budget, NOW())";
        
        $stmt = $db->prepare($query);
        
        if (!$stmt) {
            http_response_code(500);
            echo json_encode(['message' => 'Failed to prepare database statement']);
            exit;
        }

        $stmt->bindParam(':title', $title, PDO::PARAM_STR);
        $stmt->bindParam(':department', $department, PDO::PARAM_STR);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->bindParam(':manager', $manager, PDO::PARAM_STR);
        $stmt->bindParam(':start_date', $start_date, PDO::PARAM_STR);
        $stmt->bindParam(':end_date', $end_date, PDO::PARAM_STR);
        $stmt->bindParam(':description', $description, PDO::PARAM_STR);
        $stmt->bindParam(':budget', $budget, PDO::PARAM_STR);

        if ($stmt->execute()) {
            http_response_code(201);
            echo json_encode([
                'message' => 'Project added successfully',
                'project_id' => $db->lastInsertId()
            ]);
        } else {
            $errorInfo = $stmt->errorInfo();
            error_log("Database insert failed: " . print_r($errorInfo, true));
            http_response_code(500);
            echo json_encode(['message' => 'Failed to add project']);
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