<?php
session_start();
require_once '../config/database.php';
require_once '../class/GroundBooking.php';

header('Content-Type: application/json');

$userId = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);
$status = isset($_POST['status']) ? $_POST['status'] : null;

if ($userId === false || $status === null) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit();
}

try {
    $database = new Database();
    $booking = new GroundBooking($database->conn);
    $result = $booking->updateStatus($userId, $status);
    echo json_encode($result);
} catch (Exception $e) {
    error_log("Error updating status: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
}