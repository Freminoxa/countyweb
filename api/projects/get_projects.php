<?php
// At the VERY top with no whitespace before
ob_start(); // Start output buffering
header('Content-Type: application/json');

try {
    require __DIR__ . '/../config/database.php';
    
    $db = (new Database())->getConnection();
    if (!$db) {
        throw new Exception('Database connection failed');
    }
    
    $query = "SELECT * FROM projects ORDER BY created_at DESC";
    $stmt = $db->prepare($query);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to execute query');
    }
    
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($projects === false) {
        throw new Exception('Failed to fetch projects');
    }
    
    // Clear any output buffer
    ob_end_clean();
    
    echo json_encode([
        'success' => true,
        'data' => $projects
    ]);
    exit;
    
} catch (Exception $e) {
    // Clean any output
    ob_end_clean();
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred'
    ]);
    exit;
}