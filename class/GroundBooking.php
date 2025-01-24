<?php
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
    public $image_path;

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

    public function validateImage($image) {
        // Check file size (max 5MB)
        if ($image['size'] > 5 * 1024 * 1024) {
            return false;
        }

        // Check file type
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        return in_array($image['type'], $allowed_types);
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (username, password, email, phone, players_count, booking_slot, 
                   ground_type, group_type, gender, address, image_path) 
                  VALUES 
                  ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11)";

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
            $this->address,
            $this->image_path ?? null
        ]);

        return $result ? true : false;
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
                      group_type = $8, gender = $9, address = $10, image_path = $11 
                  WHERE id = $12";

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
            $this->image_path ?? null,
            $this->id
        ]);

        return $result ? true : false;
    }
}