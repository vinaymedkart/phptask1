<?php
session_start();
require_once '../config/database.php';
require_once '../class/GroundBooking.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

$database = new Database();
$db = $database->conn;
$booking = new GroundBooking($db);

$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchField = isset($_GET['field']) ? trim($_GET['field']) : 'all';

$results = $booking->searchBookings($searchTerm, $searchField);

if ($results === false) {
    echo json_encode(['error' => 'Search failed']);
    exit();
}

echo json_encode(['success' => true, 'data' => $results]);