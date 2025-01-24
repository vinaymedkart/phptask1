<?php
require_once 'config/database.php';
require_once 'class/GroundBooking.php';

$database = new Database();
$db = $database->conn;
$booking = new GroundBooking($db);

$id = $_GET['id'] ?? null;

if ($id && $booking->delete($id)) {
    echo "Booking deleted successfully.";
} else {
    echo "Unable to delete booking.";
}

header('Location: index.php'); // Redirect back to the main page
exit;
?>
