<?php
require_once 'config/database.php';
require_once 'class/GroundBooking.php';

$database = new Database();
$db = $database->conn;
$booking = new GroundBooking($db);

$id = $_GET['id'] ?? null;
$errors = [];
$formData = [];
$isPasswordChanged = false;

if ($id) {
    $existingBooking = $booking->readOne($id);
    if (!$existingBooking) {
        die('Booking not found');
    }
    $formData = $existingBooking;

    // Convert group_type from string to an array
    if (!empty($formData['group_type'])) {
        $formData['group_type'] = explode(', ', $formData['group_type']);
    }
    var_dump($formData['group_type']);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $formData = [
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
        'image' => $_FILES['image'] ?? null,
    ];

    // Validate username
    if (empty($formData['username']) || !$booking->validateUsername($formData['username'])) {
        $errors[] = "Username must be 3-20 characters long and contain only letters, numbers, and underscores.";
    }

    // Password change logic
    if (!empty($formData['password'])) {
        // Verify current password before allowing change
        if (!password_verify($formData['current_password'], $existingBooking['password'])) {
            $errors[] = "Current password is incorrect.";
        } elseif (!$booking->validatePassword($formData['password'])) {
            $errors[] = "New password must be at least 8 characters long, with at least one letter and one number.";
        } else {
            $isPasswordChanged = true;
        }
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
    // In update scenario, handle keeping existing image if no new image uploaded
    // In form validation
if (!empty($_FILES['image']) && $_FILES['image']['error'] == 0) {
    if (!$booking->validateImage($_FILES['image'])) {
        $errors[] = "Invalid image file. Max size is 5MB, and only JPEG, PNG, and GIF are allowed.";
    }
}
if (empty($formData['image_path']) && !empty($existingBooking['image_path'])) {
    $booking->image_path = $existingBooking['image_path'];
}

    if (empty($errors)) {
        $booking->id = $id;
        $booking->username = $formData['username'];
        
        // Handle password update
        if ($isPasswordChanged) {
            $booking->password = $formData['password'];
        } else {
            // Keep existing password
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
            echo "Booking updated successfully.";
        } else {
            echo "Unable to update booking.";
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
    
    <?php if (!empty($errors)): ?>
        <div style="color: red;">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="">
        Username: <input type="text" name="username" value="<?php echo htmlspecialchars($formData['username'] ?? ''); ?>" required><br>
        
        Current Password: <input type="password" name="current_password" placeholder="Enter current password"><br>
        
        New Password: <input type="password" name="password" placeholder="Leave blank if not changing"><br>
        
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
        
        <input type="submit" value="Update Booking">
    </form>
</body>
</html>