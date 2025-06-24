<?php

// Enable error reporting
ini_set('display_errors', 0); // Don't show on browser
ini_set('log_errors', 1); // Log errors
ini_set('error_log', __DIR__ . '/debug.log'); // Log to file
error_reporting(E_ALL); // Report all errors

session_start();
header('Content-Type: application/json');

if (isset($_SESSION['user_id'])) {
    echo json_encode([
        'isLoggedIn' => true,
        'role' => $_SESSION['role'],
        'username' => $_SESSION['username']
    ]);
} else {
    echo json_encode(['isLoggedIn' => false]);
}
?>