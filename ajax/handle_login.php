<?php
session_start();
require_once '../config/database.php';
require_once '../class/GroundBooking.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Please provide both email and password']);
    exit();
}

try {
    $database = new Database();
    $booking = new GroundBooking($database->conn);
    
    $result = $booking->authenticate($email, $password);
    
    if (isset($result['success'])) {
        $_SESSION['user_id'] = $result['user']['id'];
        $_SESSION['email'] = $result['user']['email'];
        $_SESSION['username'] = $result['user']['username'];
        $_SESSION['role'] = $result['user']['role'];
        
        $redirect = $_SESSION['role'] === 'admin' ? '../index.php' : '../dashboard.php';
        
        echo json_encode([
            'success' => true,
            'message' => 'Login successful',
            'redirect' => $redirect
        ]);
    } else {
        $message = match($result['error']) {
            'account_inactive' => 'Your account is currently inactive. Please contact support.',
            'invalid_credentials' => 'Invalid email or password',
            default => 'An error occurred during login'
        };
        echo json_encode(['success' => false, 'message' => $message]);
    }
} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
}