<?php
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