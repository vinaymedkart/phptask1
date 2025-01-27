<?php
class UserImage {
    private $conn;
    private $table_name = "user_images";
    private static $stmt_counter = 0;

    public $id;
    public $user_id;
    public $image_path;
    public $is_deleted;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function addImage($user_id, $image_path) {
        try {
            // Generate a unique statement name
            $stmt_name = 'add_image_' . self::$stmt_counter++;
            
            // Clean the image path for logging
            error_log("Adding image: " . $image_path . " for user: " . $user_id);
            
            $query = "INSERT INTO " . $this->table_name . " (user_id, image_path) VALUES ($1, $2) RETURNING id";
            
            // Deallocate the statement if it exists
            @pg_query($this->conn, "DEALLOCATE IF EXISTS " . $stmt_name);
            
            // Prepare new statement
            $result = pg_prepare($this->conn, $stmt_name, $query);
            if (!$result) {
                error_log("Failed to prepare statement: " . pg_last_error($this->conn));
                return false;
            }
            
            // Execute statement
            $result = pg_execute($this->conn, $stmt_name, [$user_id, $image_path]);
            if (!$result) {
                error_log("Failed to execute statement: " . pg_last_error($this->conn));
                return false;
            }

            $row = pg_fetch_assoc($result);
            
            // Deallocate the statement after use
            @pg_query($this->conn, "DEALLOCATE " . $stmt_name);
            
            return $row['id'] ?? false;
            
        } catch (Exception $e) {
            error_log("Error in addImage: " . $e->getMessage());
            return false;
        }
    }

    public function getUserImages($user_id) {
        try {
            $stmt_name = 'get_user_images_' . self::$stmt_counter++;
            
            // Deallocate if exists
            @pg_query($this->conn, "DEALLOCATE IF EXISTS " . $stmt_name);
            
            $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = $1 AND is_deleted = FALSE";
            
            $result = pg_prepare($this->conn, $stmt_name, $query);
            if (!$result) {
                error_log("Failed to prepare getUserImages statement: " . pg_last_error($this->conn));
                return [];
            }
            
            $result = pg_execute($this->conn, $stmt_name, [$user_id]);
            if (!$result) {
                error_log("Failed to execute getUserImages statement: " . pg_last_error($this->conn));
                return [];
            }

            $images = [];
            while ($row = pg_fetch_assoc($result)) {
                $images[] = $row;
            }
            
            // Deallocate the statement after use
            @pg_query($this->conn, "DEALLOCATE " . $stmt_name);
            
            return $images;
        } catch (Exception $e) {
            error_log("Error in getUserImages: " . $e->getMessage());
            return [];
        }
    }

    public function deleteImage($id) {
        try {
            $stmt_name = 'delete_image_' . self::$stmt_counter++;
            
            // Deallocate if exists
            @pg_query($this->conn, "DEALLOCATE IF EXISTS " . $stmt_name);
            
            $query = "UPDATE " . $this->table_name . " SET is_deleted = TRUE WHERE id = $1";
            
            $result = pg_prepare($this->conn, $stmt_name, $query);
            if (!$result) {
                error_log("Failed to prepare deleteImage statement: " . pg_last_error($this->conn));
                return false;
            }
            
            $result = pg_execute($this->conn, $stmt_name, [$id]);
            if (!$result) {
                error_log("Failed to execute deleteImage statement: " . pg_last_error($this->conn));
                return false;
            }
            
            // Deallocate the statement after use
            @pg_query($this->conn, "DEALLOCATE " . $stmt_name);
            
            return true;
        } catch (Exception $e) {
            error_log("Error in deleteImage: " . $e->getMessage());
            return false;
        }
    }
}
?>