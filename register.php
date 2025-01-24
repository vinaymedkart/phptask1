<?php
require_once 'config/database.php';
require_once 'class/GroundBooking.php';

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

    // Validate username
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
    // Validate files
    if (!empty($_FILES['image']) && $_FILES['image']['error'] == 0) {
        if ($booking->validateImage($_FILES['image'])) {
            $upload_dir = __DIR__ . '/uploads/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $unique_filename = uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $unique_filename;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $formData['image_path'] = $upload_path;
                $booking->image_path = $upload_path;
            } else {
                $errors[] = "Failed to upload image.";
            }
        } else {
            $errors[] = "Invalid image file. Max size is 5MB, and only JPEG, PNG, and GIF are allowed.";
        }
    }
    
    if (empty($errors)) {
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

        if ($booking->create()) {
            echo "Booking created successfully";
            // Redirect to booking list or success page
            header("Location: booking_list.php");
            exit();
        } else {
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
    
    <?php if (!empty($errors)): ?>
        <div style="color: red;">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="" enctype="multipart/form-data">
        Username: <input type="text" name="username" value="<?php echo htmlspecialchars($formData['username'] ?? ''); ?>" required><br>
        
        Password: <input type="password" name="password" value="<?php echo htmlspecialchars($formData['password'] ?? ''); ?>" required><br>
        
        Email: <input type="email" name="email" value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>" required><br>
        
        Phone: <input type="tel" name="phone" value="<?php echo htmlspecialchars($formData['phone'] ?? ''); ?>" required><br>
        
        Number of Players: <input type="number" name="players_count" min="1" max="22" value="<?php echo htmlspecialchars($formData['players_count'] ?? ''); ?>" required><br>
        
        Booking Slot: <input type="datetime-local" name="booking_slot" value="<?php echo htmlspecialchars($formData['booking_slot'] ?? ''); ?>" required><br>
        
        Select Ground Type: 
        <select name="ground_type">
            <option value="indoor" <?php echo (isset($formData['ground_type']) && $formData['ground_type'] == 'indoor') ? 'selected' : ''; ?>>Indoor</option>
            <option value="outdoor" <?php echo (isset($formData['ground_type']) && $formData['ground_type'] == 'outdoor') ? 'selected' : ''; ?>>Outdoor</option>
            <option value="covered" <?php echo (isset($formData['ground_type']) && $formData['ground_type'] == 'covered') ? 'selected' : ''; ?>>Covered</option>
        </select><br>
        
        Group Type: 
        <input type="checkbox" name="group_type[]" value="family" <?php echo (isset($formData['group_type']) && in_array('family', $formData['group_type'])) ? 'checked' : ''; ?>> Family
        <input type="checkbox" name="group_type[]" value="friends" <?php echo (isset($formData['group_type']) && in_array('friends', $formData['group_type'])) ? 'checked' : ''; ?>> Friends
        <input type="checkbox" name="group_type[]" value="children" <?php echo (isset($formData['group_type']) && in_array('children', $formData['group_type'])) ? 'checked' : ''; ?>> Children<br>
        
        Gender:
        <input type="radio" name="gender" value="male" <?php echo (isset($formData['gender']) && $formData['gender'] == 'male') ? 'checked' : ''; ?>> Male
        <input type="radio" name="gender" value="female" <?php echo (isset($formData['gender']) && $formData['gender'] == 'female') ? 'checked' : ''; ?>> Female
        <input type="radio" name="gender" value="other" <?php echo (isset($formData['gender']) && $formData['gender'] == 'other') ? 'checked' : ''; ?>> Other<br>
        
        Address: 
        <textarea name="address" rows="4" cols="50" required><?php echo htmlspecialchars($formData['address'] ?? ''); ?></textarea><br>
        Profile Image: <input type="file" name="image" accept="image/*"><br>
        <input type="reset" value="Reset">
        <input type="submit" value="Book Ground">
    </form>
</body>
</html>
