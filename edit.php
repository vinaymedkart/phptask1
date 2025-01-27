<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: authentication/login.php");
    exit();
}

require_once 'config/database.php';
require_once 'class/GroundBooking.php';
require_once 'class/UserImage.php';
require_once 'form.php';

$database = new Database();
$db = $database->conn;
$booking = new GroundBooking($db);
$userImage = new UserImage($db);

$user_id = $_SESSION['user_id'];
$errors = [];
$formData = [];
$isPasswordChanged = false;

// Fetch booking details
$existingBooking = $booking->readOne($user_id);
if (!$existingBooking) {
    die('Booking not found');
}
$formData = $existingBooking;

// Fetch existing images
$existingImages = $userImage->getUserImages($user_id);

// Append "class/" to the image_path of each image
foreach ($existingImages as &$image) {
    $image['image_path'] = 'class/' . $image['image_path'];
}

// Update the form data with the modified images
$formData['images'] = $existingImages;

// Debug output to verify the changes
// var_dump($existingImages);
if (!empty($formData['group_type'])) {
    $formData['group_type'] = explode(', ', $formData['group_type']);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Capture and process form data
    $formData = [
        'id' => $user_id,  // Add this to keep track of the user ID
        'username' => $_POST['username'] ?? '',
        'password' => $_POST['password'] ?? '',
        'current_password' => $_POST['current_password'] ?? '',
        'email' => $_POST['email'] ?? '',
        'phone' => $_POST['phone'] ?? '',
        'players_count' => $_POST['players_count'] ?? '',
        'booking_slot' => $_POST['booking_slot'] ?? '',
        'ground_type' => $_POST['ground_type'] ?? '',
        'group_type' => $_POST['group_type'] ?? [],
        'gender' => $_POST['gender'] ?? '',
        'address' => $_POST['address'] ?? '',
    ];

    // Validation logic
    if (empty($formData['username']) || !$booking->validateUsername($formData['username'])) {
        $errors[] = "Username must be 3-20 characters long and contain only letters, numbers, and underscores.";
    }

    // Password change logic
    if (!empty($formData['password'])) {
        if (!password_verify($formData['current_password'], $existingBooking['password'])) {
            $errors[] = "Current password is incorrect.";
        } elseif (!$booking->validatePassword($formData['password'])) {
            $errors[] = "New password must be at least 8 characters long, with at least one letter and one number.";
        } else {
            $isPasswordChanged = true;
        }
    }

    // Other validations...
    if (empty($formData['email']) || !$booking->validateEmail($formData['email'])) {
        $errors[] = "Enter a valid email address.";
    }

    if (empty($formData['phone']) || !$booking->validatePhone($formData['phone'])) {
        $errors[] = "Phone number must be 10 digits.";
    }

    if (empty($formData['players_count']) || $formData['players_count'] < 1 || $formData['players_count'] > 22) {
        $errors[] = "Number of players must be between 1 and 22.";
    }

    if (empty($formData['booking_slot']) || strtotime($formData['booking_slot']) < time()) {
        $errors[] = "Booking slot must be a future date and time.";
    }

    // Handle new image uploads
    if (!empty($_FILES['images']['name'][0])) {
        foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['images']['error'][$key] === 0) {
                if (!$booking->validateImage([
                    'name' => $_FILES['images']['name'][$key],
                    'type' => $_FILES['images']['type'][$key],
                    'size' => $_FILES['images']['size'][$key],
                    'tmp_name' => $tmp_name
                ])) {
                    $errors[] = "Invalid image file at position " . ($key + 1) . ". Max size is 5MB, and only JPEG, PNG, and GIF are allowed.";
                }
            }
        }
    }

    if (empty($errors)) {
        // Update booking
        $booking->id = $user_id;
        $booking->username = $formData['username'];
        
        if ($isPasswordChanged) {
            $booking->password = $formData['password'];
        } else {
            $booking->password = $existingBooking['password'];
        }

        $booking->email = $formData['email'];
        $booking->phone = $formData['phone'];
        $booking->players_count = $formData['players_count'];
        $booking->booking_slot = $formData['booking_slot'];
        $booking->ground_type = $formData['ground_type'];
        $booking->group_type = implode(', ', $formData['group_type']);
        $booking->gender = $formData['gender'];
        $booking->address = $formData['address'];

        if ($booking->update()) {
            // Handle new image uploads
            if (!empty($_FILES['images']['name'][0])) {
                $uploaded_images = $booking->handleImageUploads($_FILES, $user_id);
                if (empty($uploaded_images)) {
                    error_log("No images were uploaded successfully for user_id: " . $user_id);
                } else {
                    error_log("Successfully uploaded " . count($uploaded_images) . " images for user_id: " . $user_id);
                }
            }
            
            header("Location: dashboard.php?success=1");
            exit();
        } else {
            $errors[] = "Unable to update booking.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Ground Booking</title>
</head>
<body>
    <h2>Edit Ground Booking</h2>
    <?php 
    // Pass the user_id to the form for AJAX image loading
    $formData['id'] = $user_id;
    echo renderBookingForm($formData, $errors, true); 
    ?>
</body>
</html>