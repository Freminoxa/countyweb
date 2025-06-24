<?php

// Enable error reporting
ini_set('display_errors', 0); // Don't show on browser
ini_set('log_errors', 1); // Log errors
ini_set('error_log', __DIR__ . '/debug.log'); // Log to file
error_reporting(E_ALL); // Report all errors


session_start();
header('Content-Type: application/json');

require '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['email']) || empty(trim($data['email']))) {
        http_response_code(400);
        echo json_encode(['message' => 'Email is required']);
        exit;
    }

    $email = trim($data['email']);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['message' => 'Invalid email format']);
        exit;
    }

    $db = (new Database())->getConnection();
    $query = "SELECT id, username FROM users WHERE email = :email";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['message' => 'No account found with this email']);
        exit;
    }

    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $newPassword = bin2hex(random_bytes(8)); // Generate a random 8-character password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    $updateQuery = "UPDATE users SET password = :password WHERE id = :id";
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->bindParam(':password', $hashedPassword);
    $updateStmt->bindParam(':id', $user['id']);

    try {
        if ($updateStmt->execute()) {
            // Simulate email sending (replace with actual SMTP setup)
            $to = $email;
            $subject = "Password Reset for Meru County PMS";
            $message = "Your new password is: $newPassword\nPlease change it after logging in.";
            $headers = "From: no-reply@meru.go.ke";
            // Uncomment and configure below for email (requires mail server)
            // mail($to, $subject, $message, $headers);

            echo json_encode(['message' => 'A new password has been sent to your email']);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'Failed to reset password']);
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