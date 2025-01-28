
<?php
require_once __DIR__ . '/UserImage.php';  // Add this line at the top

class GroundBooking {
    private $conn;
    private $table_name = "ground_bookings";

    public $id;
    public $username;
    public $password;
    public $email;
    public $phone;
    public $players_count;
    public $booking_slot;
    public $ground_type;
    public $group_type;
    public $gender;
    public $address;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Validation methods
    public function validateUsername($username) {
        return preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username);
    }

    public function validatePassword($password) {
        return preg_match('/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/', $password);
    }

    public function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    public function validatePhone($phone) {
        return preg_match('/^\d{10}$/', $phone);
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (username, password, email, phone, players_count, booking_slot, 
                   ground_type, group_type, gender, address) 
                  VALUES 
                  ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10) RETURNING id";  // Added RETURNING id
    
        $hashed_password = password_hash($this->password, PASSWORD_DEFAULT);
    
        $result = pg_prepare($this->conn, "create_booking", $query);
        $result = pg_execute($this->conn, "create_booking", [
            $this->username,
            $hashed_password,
            $this->email,
            $this->phone,
            $this->players_count,
            $this->booking_slot,
            $this->ground_type,
            $this->group_type,
            $this->gender,
            $this->address
        ]);
    
        if ($result) {
            $row = pg_fetch_assoc($result);
            return $row['id']; // Return the new ID
        }
        return false;
    }
    public function validateImage($file) {
        // Maximum file size (5MB)
        $max_size = 5 * 1024 * 1024;
        
        // Allowed file types
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        
        // Check file size
        if ($file['size'] > $max_size) {
            return false;
        }
        
        // Check file type
        if (!in_array($file['type'], $allowed_types)) {
            return false;
        }
        
        return true;
    }

    public function handleImageUploads($files, $user_id) {
        $userImage = new UserImage($this->conn);
        $uploaded = [];
        
        // Check if files array is properly structured
        if (!isset($files['images']) || !isset($files['images']['tmp_name'])) {
            error_log("Files array is not properly structured");
            return $uploaded;
        }
        
        foreach ($files['images']['tmp_name'] as $key => $tmp_name) {
            if (empty($tmp_name)) continue;
            
            $file_name = $files['images']['name'][$key];
            $file_size = $files['images']['size'][$key];
            $file_type = $files['images']['type'][$key];
            
            // Validate file
            if ($this->validateImage([
                'name' => $file_name,
                'size' => $file_size,
                'type' => $file_type,
                'tmp_name' => $tmp_name
            ])) {
                $upload_dir = dirname(__DIR__) . '/uploads/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
    
                $unique_filename = uniqid() . '_' . $file_name;
                $upload_path = $upload_dir . $unique_filename;
    
                if (move_uploaded_file($tmp_name, $upload_path)) {
                    // Store only the relative path in the database
                    $db_path = 'uploads/' . $unique_filename;
                    if ($userImage->addImage($user_id, $db_path)) {
                        $uploaded[] = $db_path;
                        error_log("Successfully uploaded image: " . $db_path);
                    } else {
                        error_log("Failed to add image to database: " . $db_path);
                    }
                } else {
                    error_log("Failed to move uploaded file: " . $tmp_name);
                }
            } else {
                error_log("Image validation failed for file: " . $file_name);
            }
        }
        
        return $uploaded;
    }

    public function authenticate($email, $password) {
        $query = "SELECT id, email, password, is_active, username, role FROM " . $this->table_name . " 
                  WHERE email = $1";
        
        $result = pg_query_params($this->conn, $query, [$email]);
        
        if ($result && pg_num_rows($result) > 0) {
            $user = pg_fetch_assoc($result);
            
            // Convert PostgreSQL boolean 't'/'f' to PHP boolean
            $is_active = ($user['is_active'] === 't' || $user['is_active'] === true);
            
            if (!$is_active) {
                return ['error' => 'account_inactive'];
            }
            
            if (password_verify($password, $user['password'])) {
                unset($user['password']);
                $user['is_active'] = $is_active;
                return ['success' => true, 'user' => $user];
            }
            
            return ['error' => 'invalid_credentials'];
        }
        
        return ['error' => 'invalid_credentials'];
    }
    public function updateStatus($id, $status) {
        if ($status === 'true' || $status === '1' || $status === true) {
            $status = 't';
        } elseif ($status === 'false' || $status === '0' || $status === false) {
            $status = 'f';
        } else {
            $status = null;
        }
        
        $id = filter_var($id, FILTER_VALIDATE_INT);
        if ($id === false) {
            return ['success' => false, 'message' => 'Invalid user ID'];
        }
        
        try {
            // Create a unique statement name
            $stmt_name = 'update_status_' . uniqid();
            
            // First, deallocate if the statement exists
            @pg_query($this->conn, "DEALLOCATE IF EXISTS " . $stmt_name);
            
            // Query to update the status
            $query = "UPDATE " . $this->table_name . " 
            SET is_active = $1, updated_at = CURRENT_TIMESTAMP 
            WHERE id = $2 
            RETURNING id, is_active";
            
            // Prepare the statement
            $result = pg_prepare($this->conn, $stmt_name, $query);
            if (!$result) {
                error_log("Prepare failed: " . pg_last_error($this->conn));
                return ['success' => false, 'message' => 'Failed to prepare query'];
            }
            
            // Execute the statement
            $result = pg_execute($this->conn, $stmt_name, [$status, $id]);
            if (!$result) {
                error_log("Execute failed: " . pg_last_error($this->conn));
                return ['success' => false, 'message' => 'Failed to execute query'];
            }
            
            // Check if any row was updated
            if (pg_num_rows($result) > 0) {
                $updated = pg_fetch_assoc($result);
                
                // Clean up - deallocate the prepared statement
                @pg_query($this->conn, "DEALLOCATE " . $stmt_name);
                
                return [
                    'success' => true,
                    'message' => 'Status updated successfully',
                    'user_id' => $updated['id'],
                    'is_active' => $updated['is_active'] === 't'
                ];
            }
    
            // Clean up - deallocate the prepared statement
            @pg_query($this->conn, "DEALLOCATE " . $stmt_name);
            
            return ['success' => false, 'message' => 'No record found to update'];
            
        } catch (Exception $e) {
            error_log("Error in updateStatus: " . $e->getMessage());
            return ['success' => false, 'message' => 'Server error occurred'];
        }
    }
    
    
  
public function getAllBookingsWithDetails() {
    $query = "SELECT gb.*, 
              array_agg(DISTINCT ui.image_path) FILTER (WHERE ui.image_path IS NOT NULL) as images,
              s.suggestion,
              CASE 
                  WHEN gb.is_active = true THEN 't'
                  ELSE 'f'
              END as is_active
              FROM " . $this->table_name . " gb
              LEFT JOIN user_images ui ON gb.id = ui.user_id AND ui.is_deleted = FALSE
              LEFT JOIN suggestions s ON gb.id = s.user_id
              GROUP BY gb.id, s.suggestion
              ORDER BY gb.created_at DESC";
              
    $result = pg_query($this->conn, $query);
    
    if (!$result) {
        return false;
    }
    
    $bookings = [];
    while ($row = pg_fetch_assoc($result)) {
        if (!empty($row['images'])) {
            $row['images'] = explode(',', trim($row['images'], '{}'));
        } else {
            $row['images'] = [];
        }
        $row['is_active'] = ($row['is_active'] === 't' || $row['is_active'] === true || $row['is_active'] === '1');
        $bookings[] = $row;
    }
    
    return $bookings;
}
public function readOne($id) {
    $query = "SELECT * FROM " . $this->table_name . " WHERE id = $1";
    $result = pg_prepare($this->conn, "read_one", $query);
    $result = pg_execute($this->conn, "read_one", [$id]);

    return pg_num_rows($result) > 0 ? pg_fetch_assoc($result) : false;
}
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET username = $1, password = $2, email = $3, phone = $4, 
                      players_count = $5, booking_slot = $6, ground_type = $7, 
                      group_type = $8, gender = $9, address = $10 
                  WHERE id = $11";

       
        $password = strpos($this->password, '$2y$') === false 
            ? password_hash($this->password, PASSWORD_DEFAULT) 
            : $this->password;

        $result = pg_prepare($this->conn, "update_booking", $query);
        $result = pg_execute($this->conn, "update_booking", [
            $this->username,
            $password,
            $this->email,
            $this->phone,
            $this->players_count,
            $this->booking_slot,
            $this->ground_type,
            $this->group_type,
            $this->gender,
            $this->address,
            $this->id
        ]);

        return $result ? true : false;
    }
    public function delete($id) {
        try {
           
            pg_query($this->conn, "BEGIN");
    
            $delete_images_query = "UPDATE user_images 
                                   SET is_deleted = TRUE, 
                                       deleted_at = CURRENT_TIMESTAMP 
                                   WHERE user_id = $1";
            $result = pg_query_params($this->conn, $delete_images_query, [$id]);
            
            if (!$result) {
                throw new Exception("Failed to update image records");
            }
    
            $delete_suggestions_query = "DELETE FROM suggestions WHERE user_id = $1";
            $result = pg_query_params($this->conn, $delete_suggestions_query, [$id]);
            
            if (!$result) {
                throw new Exception("Failed to delete suggestions");
            }
    
            $delete_booking_query = "DELETE FROM " . $this->table_name . " WHERE id = $1";
            $result = pg_query_params($this->conn, $delete_booking_query, [$id]);
            
            if (!$result) {
                throw new Exception("Failed to delete booking");
            }
    
            pg_query($this->conn, "COMMIT");
            
            return true;
    
        } catch (Exception $e) {
            pg_query($this->conn, "ROLLBACK");
            error_log("Delete error in GroundBooking: " . $e->getMessage());
            return false;
        }
    }
    function searchBookings($searchTerm, $searchField) {
        $baseQuery = "SELECT gb.*, 
                      array_agg(DISTINCT ui.image_path) FILTER (WHERE ui.image_path IS NOT NULL) as images,
                      s.suggestion
                      FROM " . $this->table_name . " gb
                      LEFT JOIN user_images ui ON gb.id = ui.user_id AND ui.is_deleted = FALSE
                      LEFT JOIN suggestions s ON gb.id = s.user_id";
    
        $whereConditions = [];
        $params = [];
    
        if ($searchTerm !== '') {
            if ($searchField === 'all') {
                $whereConditions[] = "(username ILIKE $1 OR email ILIKE $1)";
                $params[] = "%$searchTerm%";
            } elseif ($searchField === 'username') {
                $whereConditions[] = "username ILIKE $1";
                $params[] = "%$searchTerm%";
            } elseif ($searchField === 'email') {
                $whereConditions[] = "email ILIKE $1";
                $params[] = "%$searchTerm%";
            }
        }
    
        if (!empty($whereConditions)) {
            $baseQuery .= " WHERE " . implode(" AND ", $whereConditions);
        }
    
        $baseQuery .= " GROUP BY gb.id, s.suggestion ORDER BY gb.created_at DESC";
    
        $result = pg_query_params($this->conn, $baseQuery, $params);
        
        if (!$result) {
            return false;
        }
    
        $bookings = [];
        while ($row = pg_fetch_assoc($result)) {
            if (!empty($row['images'])) {
                $row['images'] = explode(',', trim($row['images'], '{}'));
            } else {
                $row['images'] = [];
            }
            $bookings[] = $row;
        }
    
        return $bookings;
    }
}
?>
