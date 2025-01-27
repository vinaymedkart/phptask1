<?php
class UserSuggestion {
    private $conn;
    private $table_name = "suggestions";
    private static $stmt_counter = 0;

    public $id;
    public $user_id;
    public $suggestion;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($user_id, $suggestion) {
        try {
            $stmt_name = 'create_suggestion_' . self::$stmt_counter++;
            
            // Deallocate if exists
            @pg_query($this->conn, "DEALLOCATE IF EXISTS " . $stmt_name);
            
            $query = "INSERT INTO " . $this->table_name . " 
                      (user_id, suggestion) 
                      VALUES ($1, $2) 
                      RETURNING id";
            
            $result = pg_prepare($this->conn, $stmt_name, $query);
            if (!$result) {
                error_log("Failed to prepare create suggestion statement: " . pg_last_error($this->conn));
                return false;
            }
            
            $result = pg_execute($this->conn, $stmt_name, [$user_id, $suggestion]);
            if (!$result) {
                error_log("Failed to execute create suggestion statement: " . pg_last_error($this->conn));
                return false;
            }

            $row = pg_fetch_assoc($result);
            
            // Deallocate the statement
            @pg_query($this->conn, "DEALLOCATE " . $stmt_name);
            
            return $row['id'] ?? false;
        } catch (Exception $e) {
            error_log("Error in create suggestion: " . $e->getMessage());
            return false;
        }
    }

    public function readByUserId($user_id) {
        try {
            $stmt_name = 'read_suggestion_' . self::$stmt_counter++;
            
            // Deallocate if exists
            @pg_query($this->conn, "DEALLOCATE IF EXISTS " . $stmt_name);
            
            $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = $1 ORDER BY created_at DESC LIMIT 1";
            
            $result = pg_prepare($this->conn, $stmt_name, $query);
            if (!$result) {
                error_log("Failed to prepare read suggestion statement: " . pg_last_error($this->conn));
                return false;
            }
            
            $result = pg_execute($this->conn, $stmt_name, [$user_id]);
            if (!$result) {
                error_log("Failed to execute read suggestion statement: " . pg_last_error($this->conn));
                return false;
            }

            $suggestion = pg_fetch_assoc($result);
            
            // Deallocate the statement
            @pg_query($this->conn, "DEALLOCATE " . $stmt_name);
            
            return $suggestion;
        } catch (Exception $e) {
            error_log("Error in read suggestion: " . $e->getMessage());
            return false;
        }
    }

    public function update($id, $suggestion) {
        try {
            $stmt_name = 'update_suggestion_' . self::$stmt_counter++;
            
            // Deallocate if exists
            @pg_query($this->conn, "DEALLOCATE IF EXISTS " . $stmt_name);
            
            $query = "UPDATE " . $this->table_name . " 
                      SET suggestion = $1, updated_at = CURRENT_TIMESTAMP 
                      WHERE id = $2";
            
            $result = pg_prepare($this->conn, $stmt_name, $query);
            if (!$result) {
                error_log("Failed to prepare update suggestion statement: " . pg_last_error($this->conn));
                return false;
            }
            
            $result = pg_execute($this->conn, $stmt_name, [$suggestion, $id]);
            if (!$result) {
                error_log("Failed to execute update suggestion statement: " . pg_last_error($this->conn));
                return false;
            }
            
            // Deallocate the statement
            @pg_query($this->conn, "DEALLOCATE " . $stmt_name);
            
            return true;
        } catch (Exception $e) {
            error_log("Error in update suggestion: " . $e->getMessage());
            return false;
        }
    }

    public function hasSuggestion($user_id) {
        try {
            $stmt_name = 'check_suggestion_' . self::$stmt_counter++;
            
            // Deallocate if exists
            @pg_query($this->conn, "DEALLOCATE IF EXISTS " . $stmt_name);
            
            $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " WHERE user_id = $1";
            
            $result = pg_prepare($this->conn, $stmt_name, $query);
            if (!$result) {
                error_log("Failed to prepare check suggestion statement: " . pg_last_error($this->conn));
                return false;
            }
            
            $result = pg_execute($this->conn, $stmt_name, [$user_id]);
            if (!$result) {
                error_log("Failed to execute check suggestion statement: " . pg_last_error($this->conn));
                return false;
            }

            $row = pg_fetch_assoc($result);
            
            // Deallocate the statement
            @pg_query($this->conn, "DEALLOCATE " . $stmt_name);
            
            return $row['count'] > 0;
        } catch (Exception $e) {
            error_log("Error in check suggestion: " . $e->getMessage());
            return false;
        }
    }
}
?>