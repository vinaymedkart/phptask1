<?php
require_once 'config/database.php';

class User {
    private $conn;
    private $table_name = 'users';

    public $id;
    public $name;
    public $email;
    public $phone;
    public $age;
    public $gender;
    public $password;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function validateName($name) {
        return preg_match("/^[a-zA-Z ]{3,50}$/", $name);
    }

    public function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    public function validatePhone($phone) {
        return preg_match("/^[0-9]{10}$/", $phone);
    }

    public function validateAge($age) {
        return $age >= 18 && $age <= 100;
    }

    public function validatePassword($password) {
        echo $password;
        return preg_match("/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/", $password);
    }

    public function create() {
        $query = "INSERT INTO {$this->table_name} (name, email, phone, age, gender, password) 
                  VALUES ($1, $2, $3, $4, $5, $6) RETURNING id";
        
        $hashed_password = password_hash($this->password, PASSWORD_DEFAULT);

        $params = [
            $this->name, 
            $this->email, 
            $this->phone, 
            $this->age, 
            $this->gender, 
            $hashed_password
        ];

        try {
            $result = pg_query_params($this->conn, $query, $params);
            
            if ($result) {
                $row = pg_fetch_row($result);
                $this->id = $row[0];
                return true;
            }
            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    public function read() {
        $query = "SELECT * FROM {$this->table_name}";
        return pg_query($this->conn, $query);
    }

    public function update() {
        $query = "UPDATE {$this->table_name} 
                  SET name=$1, email=$2, phone=$3, age=$4, gender=$5 
                  WHERE id=$6";
        
        $params = [
            $this->name, 
            $this->email, 
            $this->phone, 
            $this->age, 
            $this->gender, 
            $this->id
        ];

        try {
            $result = pg_query_params($this->conn, $query, $params);
            return $result ? true : false;
        } catch (Exception $e) {
            return false;
        }
    }

    public function delete($id) {
        $query = "DELETE FROM {$this->table_name} WHERE id=$1";
        
        try {
            $result = pg_query_params($this->conn, $query, [$id]);
            return $result ? true : false;
        } catch (Exception $e) {
            return false;
        }
    }
}
?>