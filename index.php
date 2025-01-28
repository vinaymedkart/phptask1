<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: authentication/login.php");
    exit();
}

// If user is not admin, redirect to dashboard
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

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
    .search-container {
        margin: 20px 0;
        display: flex;
        gap: 10px;
    }

    .search-input {
        padding: 8px;
        width: 300px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    .search-field {
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    .highlight {
        background-color: yellow;
    }
    </style>
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
    <a href="authentication/logout.php">Logout</a>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']); 
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']); 
            ?>
        </div>
    <?php endif; ?>
    <div class="search-container">
    <input type="text" id="searchInput" class="search-input" placeholder="Search by username or email...">
    <select id="searchField" class="search-field">
        <option value="all">Both</option>
        <option value="username">Username</option>
        <option value="email">Email</option>
    </select>
</div>
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
                        <!-- Change this part in your index.php table rendering -->
                        <td>
                            <label class="status-toggle">
                                <input type="checkbox" 
                                    class="status-checkbox" 
                                    data-user-id="<?php echo $row['id']; ?>"
                                    <?php echo ($row['is_active'] === 't' || $row['is_active'] === true) ? 'checked' : ''; ?>>
                                <span class="<?php echo ($row['is_active'] === 't' || $row['is_active'] === true) ? 'active' : 'inactive'; ?>">
                                    <?php echo ($row['is_active'] === 't' || $row['is_active'] === true) ? 'Active' : 'Inactive'; ?>
                                </span>
                            </label>
                        </td>
                        <td>
                            <a href="edit.php?id=<?php echo htmlspecialchars($row['id']); ?>">Edit</a> 
                            <!-- | -->
                            <!-- <a href="delete.php?id=<?php echo htmlspecialchars($row['id']); ?>" 
                               onclick="return confirm('Are you sure you want to delete this booking?');">Delete</a> -->
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
    // Function to handle status toggle
    function handleStatusToggle(checkbox) {
        const userId = checkbox.data('user-id');
        const isActive = checkbox.prop('checked');
        const statusSpan = checkbox.siblings('span');
        
        $.ajax({
            url: 'ajax/update_status.php',
            method: 'POST',
            data: {
                user_id: userId,
                status: isActive ? 'true' : 'false'
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    statusSpan.removeClass('active inactive')
                        .addClass(isActive ? 'active' : 'inactive')
                        .text(isActive ? 'Active' : 'Inactive');
                    
                    // Update all instances of this user's status in the table
                    $(`.status-checkbox[data-user-id="${userId}"]`).each(function() {
                        $(this).prop('checked', isActive);
                        $(this).siblings('span')
                            .removeClass('active inactive')
                            .addClass(isActive ? 'active' : 'inactive')
                            .text(isActive ? 'Active' : 'Inactive');
                    });
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
    }

    // Function to attach status toggle handlers
    function attachStatusToggleHandlers() {
        $('.status-checkbox').off('change').on('change', function() {
            handleStatusToggle($(this));
        });
    }

    // Initial attachment of status toggle handlers
    attachStatusToggleHandlers();

    // Search functionality
    let searchTimer;
    
    function highlightText(text, searchTerm) {
        if (!searchTerm) return text;
        const regex = new RegExp(`(${searchTerm})`, 'gi');
        return text.toString().replace(regex, '<span class="highlight">$1</span>');
    }
    
    function updateTable(data) {
    const tbody = $('table tbody');
    tbody.empty();
    
    if (data.length === 0) {
        tbody.html('<tr><td colspan="14">No records found</td></tr>');
        return;
    }
    
    const searchTerm = $('#searchInput').val();
    
    data.forEach(function(row) {
        let imagesHtml = '<div class="image-gallery">';
        if (row.images && row.images.length > 0) {
            row.images.forEach(function(image) {
                imagesHtml += `<img src="${image}" alt="User Image">`;
            });
        } else {
            imagesHtml += 'No Images';
        }
        imagesHtml += '</div>';
        
        // Convert the is_active value to boolean
        const isActive = row.is_active === 't' || row.is_active === true || row.is_active === '1';
        
        const tr = $('<tr>').append(
            $('<td>').text(row.id),
            $('<td>').html(highlightText(row.username, searchTerm)),
            $('<td>').html(highlightText(row.email, searchTerm)),
            $('<td>').text(row.phone),
            $('<td>').text(row.players_count),
            $('<td>').text(row.booking_slot),
            $('<td>').text(row.ground_type),
            $('<td>').text(row.group_type),
            $('<td>').text(row.gender),
            $('<td>').text(row.address),
            $('<td>').html(imagesHtml),
            $('<td>').html(`<div class="suggestion-content">${row.suggestion || 'No suggestion'}</div>`),
            $('<td>').html(`
                <label class="status-toggle">
                    <input type="checkbox" class="status-checkbox" 
                           data-user-id="${row.id}" 
                           ${isActive ? 'checked' : ''}>
                    <span class="${isActive ? 'active' : 'inactive'}">
                        ${isActive ? 'Active' : 'Inactive'}
                    </span>
                </label>
            `),
            $('<td>').html(`<a href="edit.php?id=${row.id}">Edit</a>`)
        );
        tbody.append(tr);
    });
    
    // Reattach event handlers for status toggles
    attachStatusToggleHandlers();
}
    
    $('#searchInput, #searchField').on('input change', function() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function() {
            const searchTerm = $('#searchInput').val();
            const searchField = $('#searchField').val();
            
            $.ajax({
                url: 'ajax/search_bookings.php',
                method: 'GET',
                data: {
                    search: searchTerm,
                    field: searchField
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        updateTable(response.data);
                    } else {
                        alert(response.error || 'Search failed');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    alert('Error connecting to server');
                }
            });
        }, 300);
    });
});
</script>
</body>
</html>