<?php
require_once '../config/database.php';
require_once '../class/UserImage.php';

$database = new Database();
$userImage = new UserImage($database->conn);

$image_id = $_POST['image_id'] ?? null;

if ($image_id) {
    $success = $userImage->deleteImage($image_id);
    echo json_encode(['success' => $success]);
} else {
    echo json_encode(['success' => false, 'error' => 'Image ID not provided']);
}
?>