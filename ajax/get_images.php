<?php
require_once '../config/database.php';
require_once '../class/UserImage.php';

$database = new Database();
$userImage = new UserImage($database->conn);

$user_id = $_GET['user_id'] ?? null;

if ($user_id) {
    $images = $userImage->getUserImages($user_id);
    echo json_encode($images);
} else {
    echo json_encode(['error' => 'User ID not provided']);
}
?>