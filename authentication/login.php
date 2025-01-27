<?php
session_start();
require_once '../config/database.php';
require_once '../class/GroundBooking.php';

$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $database = new Database();
    $db = $database->conn;
    $booking = new GroundBooking($db);

    $user = $booking->authenticate($email, $password);

    if ($user) {
        // Successful login
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        header("Location: ../dashboard.php");
        exit();
    } else {
        $errors[] = "Invalid email or password";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
</head>
<body>
    <h2>Login</h2>
    
    <?php if (!empty($errors)): ?>
        <div style="color: red;">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="">
        Email: <input type="email" name="email" required><br>
        Password: <input type="password" name="password" required><br>
        
        <input type="submit" value="Login">
        <p>Don't have an account? <a href="../register.php">Register here</a></p>
    </form>
</body>
</html>