<?php
session_start();
require_once 'config/database.php';
require_once 'class/GroundBooking.php';

$database = new Database();
$db = $database->conn;
$booking = new GroundBooking($db);

$bookings = $booking->getAllBookingsWithDetails();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Booking List</title>
    <style>
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .image-gallery { display: flex; gap: 10px; flex-wrap: wrap; }
        .image-gallery img { max-width: 100px; max-height: 100px; object-fit: cover; }
        .status-toggle { cursor: pointer; }
        .active { color: green; }
        .inactive { color: red; }
        .suggestion-content { max-width: 300px; overflow: hidden; text-overflow: ellipsis; }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <h2>Ground Booking List</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Players Count</th>
                <th>Booking Slot</th>
                <th>Ground Type</th>
                <th>Group Type</th>
                <th>Gender</th>
                <th>Address</th>
                <th>Images</th>
                <th>Suggestion</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>    
        <tbody>
            <?php if ($bookings): ?>
                <?php foreach ($bookings as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><?php echo htmlspecialchars($row['phone']); ?></td>
                        <td><?php echo htmlspecialchars($row['players_count']); ?></td>
                        <td><?php echo htmlspecialchars($row['booking_slot']); ?></td>
                        <td><?php echo htmlspecialchars($row['ground_type']); ?></td>
                        <td><?php echo htmlspecialchars($row['group_type']); ?></td>
                        <td><?php echo htmlspecialchars($row['gender']); ?></td>
                        <td><?php echo htmlspecialchars($row['address']); ?></td>
                        <td>
                            <div class="image-gallery">
                                <?php if (!empty($row['images'])): ?>
                                    <?php foreach ($row['images'] as $image): ?>
                                        <img src="<?php echo htmlspecialchars($image); ?>" alt="User Image">
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    No Images
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <div class="suggestion-content">
                                <?php echo !empty($row['suggestion']) ? html_entity_decode($row['suggestion']) : 'No suggestion'; ?>
                            </div>
                        </td>
                        <td>
                            <label class="status-toggle">
                                <input type="checkbox" 
                                       class="status-checkbox" 
                                       data-user-id="<?php echo $row['id']; ?>"
                                       <?php echo $row['is_active'] ? 'checked' : ''; ?>>
                                <span class="<?php echo $row['is_active'] ? 'active' : 'inactive'; ?>">
                                    <?php echo $row['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </label>
                        </td>
                        <td>
                            <a href="edit.php?id=<?php echo htmlspecialchars($row['id']); ?>">Edit</a> |
                            <a href="delete.php?id=<?php echo htmlspecialchars($row['id']); ?>" 
                               onclick="return confirm('Are you sure you want to delete this booking?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="14">No records found</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <script>
    $(document).ready(function() {
    $('.status-checkbox').change(function() {
        const checkbox = $(this);
        const userId = checkbox.data('user-id');
        const isActive = checkbox.prop('checked');
        const statusSpan = checkbox.siblings('span');
        
        $.ajax({
            url: 'ajax/update_status.php',
            method: 'POST',
            data: {
                user_id: userId,
                status: isActive ? 'true' : 'false'  // Send as string 'true'/'false'
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    statusSpan.removeClass('active inactive')
                        .addClass(isActive ? 'active' : 'inactive')
                        .text(isActive ? 'Active' : 'Inactive');
                } else {
                    alert(response.message || 'Failed to update status');
                    checkbox.prop('checked', !isActive);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                console.error('Response:', xhr.responseText);
                alert('Error connecting to server');
                checkbox.prop('checked', !isActive);
            }
        });

        });
    });
    </script>
</body>
</html>