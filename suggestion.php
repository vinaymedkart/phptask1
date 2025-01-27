<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/database.php';
require_once 'class/UserSuggestion.php';

$database = new Database();
$db = $database->conn;
$suggestionObj = new UserSuggestion($db);

$errors = [];
$formData = [];
$currentSuggestion = $suggestionObj->readByUserId($_SESSION['user_id']);
$hasSuggestion = $suggestionObj->hasSuggestion($_SESSION['user_id']);

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $formData['suggestion'] = $_POST['suggestion_content'] ?? '';
    
    // Validate the suggestion content
    if (empty($formData['suggestion'])) {
        $errors[] = "Suggestion content cannot be empty.";
    }
    
    // If there are no errors, proceed with saving/updating the suggestion
    if (empty($errors)) {
        try {   
            if ($hasSuggestion) {
                // Update existing suggestion
                $result = $suggestionObj->update($currentSuggestion['id'], $formData['suggestion']);
            } else {
                // Create new suggestion
                $result = $suggestionObj->create($_SESSION['user_id'], $formData['suggestion']);
            }
            
            if ($result) {
                $_SESSION['success_message'] = "Suggestion saved successfully.";
                header("Location: dashboard.php");
                exit();
            } else {
                $errors[] = "Failed to save the suggestion.";
            }
        } catch (Exception $e) {
            $errors[] = "Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo $hasSuggestion ? 'Edit' : 'Add'; ?> Suggestion</title>
    <style>
        .error { color: red; margin-bottom: 15px; }
        .form-container { max-width: 800px; margin: 20px auto; }
        .buttons { margin-top: 15px; }
    </style>
</head>
<body>
    <div class="form-container">
        <h2><?php echo $hasSuggestion ? 'Edit' : 'Add'; ?> Suggestion</h2>
        
        <?php if (!empty($errors)): ?>
            <div class="error">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <textarea name="suggestion_content" id="suggestion"><?php 
                echo htmlspecialchars($currentSuggestion['suggestion'] ?? $formData['suggestion'] ?? ''); 
            ?></textarea>
            
            <script src="https://cdn.ckeditor.com/4.21.0/standard/ckeditor.js"></script>
            <script>
                CKEDITOR.replace('suggestion');
            </script>
            
            <div class="buttons">
                <button type="submit" name="editor_submit">
                    <?php echo $hasSuggestion ? 'Update' : 'Save'; ?> Suggestion
                </button>
                <a href="dashboard.php">Back to Dashboard</a>
            </div>
        </form>
    </div>
</body>
</html>