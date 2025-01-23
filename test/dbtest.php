<?php
require_once '../config/database.php';

// Create an instance of the Database class
$database = new Database();
$db = $database->getConnection();

if ($db) {
    echo "Database connection successfully.<br>";
} else {
    echo "Database connection failed.<br>";
}
?>
