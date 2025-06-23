<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['message' => 'Unauthorized']);
    exit;
}

require '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Required fields validation
    $required_fields = ['title', 'department', 'status', 'manager', 'startDate', 'description'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            http_response_code(400);
            echo json_encode(['message' => "Missing or empty required field: $field"]);
            exit;
        }
    }

    // Validate status against ENUM values
    $valid_statuses = ['finished', 'proposed', 'unfinished'];
    if (!in_array($data['status'], $valid_statuses)) {
        http_response_code(400);
        echo json_encode(['message' => 'Invalid status value']);
        exit;
    }

    // Sanitize and prepare data
    $title = trim($data['title']);
    $department = trim($data['department']);
    $status = trim($data['status']);
    $manager = trim($data['manager']);
    $start_date = trim($data['startDate']);
    $end_date = !empty(trim($data['endDate'])) ? trim($data['endDate']) : null;
    $description = trim($data['description']);
    $budget = isset($data['budget']) && is_numeric($data['budget']) ? floatval($data['budget']) : 0.00;

    // Validate dates
    if (!strtotime($start_date)) {
        http_response_code(400);
        echo json_encode(['message' => 'Invalid start date format']);
        exit;
    }
    if ($end_date && !strtotime($end_date)) {
        http_response_code(400);
        echo json_encode(['message' => 'Invalid end date format']);
        exit;
    }
    if ($end_date && strtotime($end_date) < strtotime($start_date)) {
        http_response_code(400);
        echo json_encode(['message' => 'End date cannot be earlier than start date']);
        exit;
    }

    // Database operation
    try {
        $db = (new Database())->getConnection();
        $query = "INSERT INTO projects (title, department, status, manager, start_date, end_date, description, budget) 
                  VALUES (:title, :department, :status, :manager, :start_date, :end_date, :description, :budget)";
        $stmt = $db->prepare($query);

        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':department', $department);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':manager', $manager);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date, PDO::PARAM_STR, $end_date ? null : -1); // Handle NULL for end_date
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':budget', $budget, PDO::PARAM_STR);

        if ($stmt->execute()) {
            echo json_encode(['message' => 'Project added successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'Failed to add project']);
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