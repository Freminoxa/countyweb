<?php


// Enable error reporting
ini_set('display_errors', 0); // Don't show on browser
ini_set('log_errors', 1); // Log errors
ini_set('error_log', __DIR__ . '/debug.log'); // Log to file
error_reporting(E_ALL); // Report all errors



// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers before any output
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Start session after headers
session_start();

require_once '../config/database.php';

try {
    // Check request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }

    // Get and validate input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
        exit;
    }

    $username = trim($data['username'] ?? '');
    $password = trim($data['password'] ?? '');

    if (empty($username) || empty($password)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Username and password are required']);
        file_put_contents('debug.log', "Error: Missing credentials for '$username' at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
        exit;
    }

    // Database connection
    try {
        $db = (new Database())->getConnection();
    } catch (Exception $e) {
        throw new Exception('Database connection failed: ' . $e->getMessage());
    }

    // Prepare and execute query
    $query = "SELECT id, username, password, role FROM users WHERE username = :username LIMIT 1";
    $stmt = $db->prepare($query);
    
    if (!$stmt) {
        throw new Exception('Query preparation failed');
    }
    
    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
    
    if (!$stmt->execute()) {
        throw new Exception('Query execution failed');
    }

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $isValidPassword = false;
        
        // Check if password is hashed (bcrypt hashes start with $2y$)
        if (substr($user['password'], 0, 4) === '$2y$') {
            $isValidPassword = password_verify($password, $user['password']);
            file_put_contents('debug.log', "Checking hashed password for '$username' at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
        } else {
            // Plain text comparison (should be temporary)
            $isValidPassword = ($password === $user['password']);
            file_put_contents('debug.log', "Checking plain text password for '$username' at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
        }

        if ($isValidPassword) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            file_put_contents('debug.log', "Login successful for '$username' with role '{$user['role']}' at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
            
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Login successful',
                'role' => $user['role'],
                'username' => $user['username']
            ]);
        } else {
            file_put_contents('debug.log', "Invalid password for '$username' at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
        }
    } else {
        file_put_contents('debug.log', "User '$username' not found at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
    }

} catch (Exception $e) {
    http_response_code(500);
    $errorMsg = 'Server error: ' . $e->getMessage();
    file_put_contents('debug.log', $errorMsg . " at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
}
?>