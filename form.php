<?php
function renderBookingForm($formData = [], $errors = [], $isEdit = false) {
    ob_start();
?>
    <?php if (!empty($errors)): ?>
        <div style="color: red;">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="" enctype="multipart/form-data" id="bookingForm">
        Username: <input type="text" name="username" value="<?php echo htmlspecialchars($formData['username'] ?? ''); ?>" required><br>
        
        <?php if ($isEdit): ?>
            Current Password: <input type="password" name="current_password" placeholder="Enter current password"><br>
            New Password: <input type="password" name="password" placeholder="Leave blank if not changing"><br>
        <?php else: ?>
            Password: <input type="password" name="password" required><br>
        <?php endif; ?>
        
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
        <input type="checkbox" name="group_type[]" value="family" <?php echo (isset($formData['group_type']) && in_array('family', (array)$formData['group_type'])) ? 'checked' : ''; ?>> Family
        <input type="checkbox" name="group_type[]" value="friends" <?php echo (isset($formData['group_type']) && in_array('friends', (array)$formData['group_type'])) ? 'checked' : ''; ?>> Friends
        <input type="checkbox" name="group_type[]" value="children" <?php echo (isset($formData['group_type']) && in_array('children', (array)$formData['group_type'])) ? 'checked' : ''; ?>> Children<br>
        
        Gender:
        <input type="radio" name="gender" value="male" <?php echo (isset($formData['gender']) && $formData['gender'] == 'male') ? 'checked' : ''; ?>> Male
        <input type="radio" name="gender" value="female" <?php echo (isset($formData['gender']) && $formData['gender'] == 'female') ? 'checked' : ''; ?>> Female
        <input type="radio" name="gender" value="other" <?php echo (isset($formData['gender']) && $formData['gender'] == 'other') ? 'checked' : ''; ?>> Other<br>
        
        Address: 
        <textarea name="address" rows="4" cols="50" required><?php echo htmlspecialchars($formData['address'] ?? ''); ?></textarea><br>
        
        <div class="image-upload-section">
            Profile Images: 
            <input type="file" name="images[]" id="imageUpload" accept="image/*" multiple><br>
            <div id="imagePreviewContainer" class="image-preview">
                <?php if ($isEdit && !empty($formData['id'])): ?>
                    <div id="existingImages">
                        <!-- Images will be loaded via AJAX -->
                    </div>
                <?php endif; ?>
            </div>
            <!-- Hidden input to track number of images -->
            <input type="hidden" name="image_count" id="imageCount" value="0">
        </div>

        <style>
            .image-preview {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
                margin: 10px 0;
            }
            .image-container {
                position: relative;
                width: 150px;
                height: 150px;
            }
            .image-container img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }
            .delete-btn {
                position: absolute;
                top: 5px;
                right: 5px;
                background: red;
                color: white;
                border: none;
                border-radius: 50%;
                width: 20px;
                height: 20px;
                cursor: pointer;
            }
            .error-message {
                color: red;
                display: none;
                margin-top: 5px;
            }
        </style>

        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script>
            $(document).ready(function() {
                <?php if ($isEdit && !empty($formData['id'])): ?>
                    loadExistingImages(<?php echo $formData['id']; ?>);
                <?php endif; ?>

                // Handle form submission
                $('#bookingForm').on('submit', function(e) {
                    const imageCount = $('.image-container').length;
                    if (imageCount === 0) {
                        e.preventDefault();
                        alert('Please upload at least one image');
                        return false;
                    }
                    return true;
                });

                // Handle new image uploads
                $('#imageUpload').change(function(e) {
                    const files = e.target.files;
                    if (files.length > 0) {
                        handleImagePreview(files);
                    }
                    // Update image count
                    updateImageCount();
                });
            });

            function loadExistingImages(userId) {
                $.ajax({
                    url: 'ajax/get_images.php',
                    type: 'GET',
                    data: { user_id: userId },
                    success: function(response) {
                        const images = JSON.parse(response);
                        images.forEach(image => {
                            $('#existingImages').append(createImageElement(image));
                        });
                        updateImageCount();
                    }
                });
            }

            function handleImagePreview(files) {
                // Clear file input if no files are selected (cancelled)
                if (files.length === 0) {
                    $('#imageUpload').val('');
                    return;
                }

                Array.from(files).forEach(file => {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const preview = $('<div class="image-container temp-preview">')
                            .append(`<img src="${e.target.result}">`)
                            .append('<button type="button" class="delete-btn" onclick="removePreview(this)">×</button>');
                        $('#imagePreviewContainer').append(preview);
                        updateImageCount();
                    };
                    reader.readAsDataURL(file);
                });
            }

            function createImageElement(image) {
                return `
                    <div class="image-container" data-id="${image.id}">
                        <img src="${image.image_path}">
                        <button type="button" class="delete-btn" onclick="deleteImage(${image.id}, this)">×</button>
                    </div>
                `;
            }

            function deleteImage(imageId, button) {
                if (confirm('Are you sure you want to delete this image?')) {
                    $.ajax({
                        url: 'ajax/delete_image.php',
                        type: 'POST',
                        data: { image_id: imageId },
                        success: function(response) {
                            if (JSON.parse(response).success) {
                                $(button).closest('.image-container').remove();
                                updateImageCount();
                            }
                        }
                    });
                }
            }

            function removePreview(button) {
                $(button).closest('.image-container').remove();
                updateImageCount();
            }

            function updateImageCount() {
                const count = $('.image-container').length;
                $('#imageCount').val(count);
            }
        </script>
        
        <input type="reset" value="Reset" onclick="resetForm()">
        <input type="submit" value="<?php echo $isEdit ? 'Update Booking' : 'Book Ground'; ?>">
    </form>
<?php
    return ob_get_clean();
}
?>