<?php
require_once 'config/database.php';
require_once 'class/GroundBooking.php'; 

$database = new Database();
$db = $database->conn;

$booking = new GroundBooking($db);

$query = "SELECT * FROM ground_bookings";
$result = pg_query($db, $query);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Booking List</title>
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        img { max-width: 100px; max-height: 100px; object-fit: cover; }
    </style>
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
                <th>Profile Image</th>
                <th>Actions</th>
            </tr>
        </thead>    
        <tbody>
            <?php if ($result && pg_num_rows($result) > 0): ?>
                <?php while ($row = pg_fetch_assoc($result)): ?>
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
                            <?php if (!empty($row['image_path']) && file_exists($row['image_path'])): ?>
                                <img src="<?php echo htmlspecialchars($row['image_path']); ?>" 
                                     alt="Profile Image">
                            <?php else: ?>
                                No Image
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="edit.php?id=<?php echo htmlspecialchars($row['id']); ?>">Edit</a> |
                            <a href="delete.php?id=<?php echo htmlspecialchars($row['id']); ?>" 
                               onclick="return confirm('Are you sure you want to delete this booking?');">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="12">No records found</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>