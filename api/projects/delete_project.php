//api/projects/delete_project.php
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

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['id']) || empty($data['id'])) {
        http_response_code(400);
        echo json_encode(['message' => 'Project ID is required']);
        exit;
    }

    $db = (new Database())->getConnection();
    $query = "DELETE FROM projects WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $data['id'], PDO::PARAM_INT);

    try {
        if ($stmt->execute()) {
            echo json_encode(['message' => 'Project deleted successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'Failed to delete project']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>