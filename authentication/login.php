<?php
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <h2>Login</h2>
    
    <div id="error-message" style="color: red; display: none;"></div>
    
    <form id="login-form" method="POST">
        Email: <input type="email" name="email" required><br>
        Password: <input type="password" name="password" required><br>
        
        <input type="submit" value="Login">
        <p>Don't have an account? <a href="../register.php">Register here</a></p>
    </form>

    <script>
    $(document).ready(function() {
        $('#login-form').on('submit', function(e) {
            e.preventDefault();
            
            $.ajax({
                url: '../ajax/handle_login.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        window.location.href = response.redirect;
                    } else {
                        $('#error-message')
                            .text(response.message)
                            .show();
                    }
                },
                error: function() {
                    $('#error-message')
                        .text('An error occurred. Please try again.')
                        .show();
                }
            });
        });
    });
    </script>
</body>
</html>