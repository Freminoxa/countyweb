<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers before any output
header('Content-Type: application/json');

// Start session after headers
session_start();

require 'api/config/database.php';

try {
    // Check request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['message' => 'Method not allowed']);
        exit;
    }

    // Get and validate input
    $data = json_decode(file_get_contents('php://input'), true);
    $username = trim($data['username'] ?? '');
    $password = trim($data['password'] ?? '');

    if (empty($username) || empty($password)) {
        http_response_code(400);
        echo json_encode(['message' => 'Username and password are required']);
        file_put_contents('debug.log', "Error: Missing username or password for $username at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
        exit;
    }

    // Database connection
    $db = (new Database())->getConnection();
    if (!$db) {
        throw new Exception('Database connection failed');
    }

    // Prepare and execute query
    $query = "SELECT id, username, password, role FROM users WHERE username = :username";
    $stmt = $db->prepare($query);
    if (!$stmt) {
        throw new Exception('Query preparation failed: ' . $db->errorInfo()[2]);
    }
    $stmt->bindParam(':username', $username);
    $stmt->execute();

    // Check if user exists
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Try password_verify for hashed passwords
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            file_put_contents('debug.log', "Login successful (hashed) for: " . $user['username'] . " at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
            echo json_encode(['message' => 'Login successful', 'role' => $user['role']]);
        }
        // Fallback for plain text password (temporary for 'frank')
        elseif ($password === $user['password']) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            file_put_contents('debug.log', "Login successful (plain text) for: " . $user['username'] . " at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
            echo json_encode(['message' => 'Login successful', 'role' => $user['role']]);
        } else {
            file_put_contents('debug.log', "Authentication failed for: $username, stored: " . $user['password'] . ", input: $password at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
            http_response_code(401);
            echo json_encode(['message' => 'Invalid credentials']);
        }
    } else {
        file_put_contents('debug.log', "User not found: $username at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
        http_response_code(401);
        echo json_encode(['message' => 'Invalid credentials']);
    }
} catch (Exception $e) {
    http_response_code(500);
    $errorMsg = 'Server error: ' . $e->getMessage();
    file_put_contents('debug.log', $errorMsg . " at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
    echo json_encode(['message' => $errorMsg]);
}
?>
