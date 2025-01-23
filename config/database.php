<?php
class Database {
    private $host = 'localhost';
    private $username = 'postgres';
    private $password = 'Shiv@00000';
    private $database = 'phptask';
    private $port = 5432;
    public $conn;

    public function __construct() {
        $connection_string = "host={$this->host} port={$this->port} dbname={$this->database} user={$this->username} password={$this->password}";
        
        try {
            $this->conn = pg_connect($connection_string);
            
            if (!$this->conn) {
                throw new Exception("Connection failed");
            }
        } catch (Exception $e) {
            die("Database Connection Error: " . $e->getMessage());
        }
    }

    public function closeConnection() {
        pg_close($this->conn);
    }

    public function prepareAndExecute($query, $params = []) {
        $result = pg_query_params($this->conn, $query, $params);
        
        if (!$result) {
            throw new Exception("Query execution failed: " . pg_last_error($this->conn));
        }
        
        return $result;
    }
}
?>