<?php
require_once 'config/database.php';
require_once 'class/GroundBooking.php';
require_once 'form.php';

$database = new Database();
$db = $database->conn;
$booking = new GroundBooking($db);

$errors = [];
$formData = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Capture input values
    $formData = [
        'username' => $_POST['username'] ?? '',
        'password' => $_POST['password'] ?? '',
        'email' => $_POST['email'] ?? '',
        'phone' => $_POST['phone'] ?? '',
        'players_count' => $_POST['players_count'] ?? '',
        'booking_slot' => $_POST['booking_slot'] ?? '',
        'ground_type' => $_POST['ground_type'] ?? '',
        'group_type' => $_POST['group_type'] ?? [],
        'gender' => $_POST['gender'] ?? '',
        'address' => $_POST['address'] ?? '',
        'image' => $_FILES['image'] ?? null,
    ];

    // Validation logic
    if (empty($formData['username']) || !$booking->validateUsername($formData['username'])) {
        $errors[] = "Username must be 3-20 characters long and contain only letters, numbers, and underscores.";
    }

    // Validate password
    if (empty($formData['password']) || !$booking->validatePassword($formData['password'])) {
        $errors[] = "Password must be at least 8 characters long, with at least one letter and one number.";
    }

    // Validate email
    if (empty($formData['email']) || !$booking->validateEmail($formData['email'])) {
        $errors[] = "Enter a valid email address.";
    }

    // Validate phone
    if (empty($formData['phone']) || !$booking->validatePhone($formData['phone'])) {
        $errors[] = "Phone number must be 10 digits.";
    }

    // Validate players count
    if (empty($formData['players_count']) || $formData['players_count'] < 1 || $formData['players_count'] > 22) {
        $errors[] = "Number of players must be between 1 and 22.";
    }

    // Validate booking slot (cannot be in the past)
    if (empty($formData['booking_slot']) || strtotime($formData['booking_slot']) < time()) {
        $errors[] = "Booking slot must be a future date and time.";
    }
    // Check if at least one image is uploaded
    if (empty($_FILES['images']['name'][0])) {
        $errors[] = "Please upload at least one image.";
    }

    // Validate files only if they are uploaded
    if (!empty($_FILES['images']['name'][0])) {
        $totalFiles = count($_FILES['images']['name']);
        $validFiles = 0;

        for ($i = 0; $i < $totalFiles; $i++) {
            if ($_FILES['images']['error'][$i] === 0) {
                if ($booking->validateImage([
                    'name' => $_FILES['images']['name'][$i],
                    'type' => $_FILES['images']['type'][$i],
                    'size' => $_FILES['images']['size'][$i],
                    'tmp_name' => $_FILES['images']['tmp_name'][$i]
                ])) {
                    $validFiles++;
                } else {
                    $errors[] = "Invalid image file at position " . ($i + 1) . ". Max size is 5MB, and only JPEG, PNG, and GIF are allowed.";
                }
            }
        }

        if ($validFiles === 0) {
            $errors[] = "No valid images were uploaded.";
        }
    }
    
    if (empty($errors)) {
        // Process form data and create booking
        $booking->username = $formData['username'];
        $booking->password = $formData['password'];
        $booking->email = $formData['email'];
        $booking->phone = $formData['phone'];
        $booking->players_count = $formData['players_count'];
        $booking->booking_slot = $formData['booking_slot'];
        $booking->ground_type = $formData['ground_type'];
        $booking->group_type = implode(', ', $formData['group_type']);
        $booking->gender = $formData['gender'];
        $booking->address = $formData['address'];
        
        if ($user_id = $booking->create()) {
            // Handle image uploads
            if (!empty($_FILES['images']['name'][0])) {
                $uploaded_images = $booking->handleImageUploads($_FILES, $user_id);
                if (empty($uploaded_images)) {
                    error_log("No images were uploaded successfully for user_id: " . $user_id);
                    $errors[] = "Failed to upload images";
                } else {
                    error_log("Successfully uploaded " . count($uploaded_images) . " images for user_id: " . $user_id);
                    header("Location: authentication/login.php");
                    exit();
                }
            }
            
        } else {
            error_log("Failed to create booking");
            echo "Unable to create booking";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Box Cricket Ground Booking</title>
</head>
<body>
    <h2>Box Cricket Ground Booking</h2>
    <?php echo renderBookingForm($formData, $errors); ?>
</body>
</html>