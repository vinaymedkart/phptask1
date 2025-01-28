<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: authentication/login.php");
    exit();
}

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

require_once 'config/database.php';
require_once 'class/GroundBooking.php';

try {
    $database = new Database();
    $db = $database->conn;
    $booking = new GroundBooking($db);

    // Validate and sanitize the ID
    $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
    
    if (!$id) {
        $_SESSION['error'] = "Invalid booking ID.";
        header('Location: index.php');
        exit();
    }

    // Check if booking exists before deleting
    $existingBooking = $booking->readOne($id);
    if (!$existingBooking) {
        $_SESSION['error'] = "Booking not found.";
        header('Location: index.php');
        exit();
    }

    // Attempt to delete the booking
    if ($booking->delete($id)) {
        $_SESSION['success'] = "Booking deleted successfully.";
    } else {
        $_SESSION['error'] = "Unable to delete booking.";
    }

} catch (Exception $e) {
    error_log("Delete error: " . $e->getMessage());
    $_SESSION['error'] = "An error occurred while deleting the booking.";
}

header('Location: index.php');
exit();
?>