<?php
require_once 'config/database.php';
require_once 'class/GroundBooking.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id']) && isset($_POST['status'])) {
    $database = new Database();
    $db = $database->conn;
    $booking = new GroundBooking($db);
    
    $user_id = $_POST['user_id'];
    $status = $_POST['status'] === 'true';
    
    if ($booking->updateStatus($user_id, $status)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update status']);
    }
    exit;
}