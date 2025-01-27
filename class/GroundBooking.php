<!-- 
-- Create ground_bookings table with merged queries
CREATE TABLE ground_bookings (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(15) NOT NULL,
    players_count INTEGER NOT NULL,
    booking_slot TIMESTAMP NOT NULL,
    ground_type VARCHAR(50),
    group_type TEXT,
    gender VARCHAR(10) NOT NULL,
    address TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create an index on email for faster lookup
CREATE INDEX idx_ground_bookings_email ON ground_bookings(email);

-- Create user_images table for managing user images with soft delete
CREATE TABLE user_images (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES ground_bookings(id),
    image_path VARCHAR(255) NOT NULL,
    is_deleted BOOLEAN DEFAULT FALSE, -- Soft delete flag
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create suggestions table for managing suggestions linked to user
CREATE TABLE suggestions (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES ground_bookings(id),
    suggestion TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-->
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
        $query = "SELECT * FROM {$this->table_name} WHERE email = $1";
        $result = pg_query_params($this->conn, $query, [$email]);
        
        if ($result && pg_num_rows($result) > 0) {
            $user = pg_fetch_assoc($result);
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                return $user;
            }
        }
        
        return false;
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

        // If password is not hashed, hash it
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
}
?>
