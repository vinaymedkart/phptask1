<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/database.php';
require_once 'class/GroundBooking.php';

$database = new Database();
$db = $database->conn;
$booking = new GroundBooking($db);

// Fetch user's booking details
$userBooking = $booking->readOne($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
</head>
<body>
    <h2>Welcome, <?php echo htmlspecialchars($_SESSION['email']); ?>!</h2>
    
    <h3>Your Booking Details:</h3>
    <?php if ($userBooking): ?>
        <p>Email: <?php echo htmlspecialchars($userBooking['email']); ?></p>
        <p>Phone: <?php echo htmlspecialchars($userBooking['phone']); ?></p>
        <p>Booking Slot: <?php echo htmlspecialchars($userBooking['booking_slot']); ?></p>
        <p>Ground Type: <?php echo htmlspecialchars($userBooking['ground_type']); ?></p>
        <p>Number of Players: <?php echo htmlspecialchars($userBooking['players_count']); ?></p>
        
        <a href="edit.php?id=<?php echo $userBooking['id']; ?>">Edit Booking</a>
        <a href="logout.php">Logout</a>
    <?php else: ?>
        <p>No booking details found.</p>
    <?php endif; ?>
</body>
</html>