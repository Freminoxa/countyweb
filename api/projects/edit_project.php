//api/projects/edit_project.php

<?php

// Enable error reporting
ini_set('display_errors', 0); // Don't show on browser
ini_set('log_errors', 1); // Log errors
ini_set('error_log', __DIR__ . '/debug.log'); // Log to file
error_reporting(E_ALL); // Report all errors

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['message' => 'Unauthorized']);
    exit;
}

require '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $required_fields = ['id', 'title', 'department', 'status', 'manager', 'startDate', 'description'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            http_response_code(400);
            echo json_encode(['message' => "Missing required field: $field"]);
            exit;
        }
    }

    $db = (new Database())->getConnection();
    $query = "UPDATE projects SET 
              title = :title, 
              department = :department, 
              status = :status, 
              manager = :manager, 
              start_date = :start_date, 
              end_date = :end_date, 
              description = :description, 
              budget = :budget 
              WHERE id = :id";
    $stmt = $db->prepare($query);

    $stmt->bindParam(':id', $data['id'], PDO::PARAM_INT);
    $stmt->bindParam(':title', $data['title']);
    $stmt->bindParam(':department', $data['department']);
    $stmt->bindParam(':status', $data['status']);
    $stmt->bindParam(':manager', $data['manager']);
    $stmt->bindParam(':start_date', $data['startDate']);
    $stmt->bindParam(':end_date', $data['endDate']);
    $stmt->bindParam(':description', $data['description']);
    $stmt->bindParam(':budget', $data['budget'], PDO::PARAM_STR);

    try {
        if ($stmt->execute()) {
            echo json_encode(['message' => 'Project updated successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'Failed to update project']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>