<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// If user is admin, redirect to index.php
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: index.php");
    exit();
}

require_once 'config/database.php';
require_once 'class/GroundBooking.php';
require_once 'class/UserSuggestion.php';

$database = new Database();
$db = $database->conn;
$booking = new GroundBooking($db);

// Fetch user's booking details
$userBooking = $booking->readOne($_SESSION['user_id']);
$suggestionObj = new UserSuggestion($db);
$userSuggestion = $suggestionObj->readByUserId($_SESSION['user_id']);
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
        <h3>Your Suggestion:</h3>
<?php if ($userSuggestion): ?>
    <div class="suggestion-content">
        <?php echo html_entity_decode($userSuggestion['suggestion']); ?>
        <a href="suggestion.php" class="btn">Edit Suggestion</a>
    </div>
<?php else: ?>
    <p>No suggestion provided yet.</p>
    <a href="suggestion.php" class="btn">Add Suggestion</a>
<?php endif; ?>
        
        <a href="edit.php">Edit Booking</a>
        <a href="authentication/logout.php">Logout</a>
    <?php else: ?>
        <p>No booking details found.</p>
    <?php endif; ?>
</body>
<style>
    .suggestion-content {
        margin: 15px 0;
        padding: 15px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    .suggestion-content p {
        margin: 0 0 10px 0;
    }
    .btn {
        display: inline-block;
        padding: 8px 15px;
        margin: 5px;
        text-decoration: none;
        background-color: #007bff;
        color: white;
        border-radius: 4px;
    }
    .btn:hover {
        background-color: #0056b3;
    }
</style>
</html>