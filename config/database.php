<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'taitter';
    private $username = 'root';
    private $password = '';
    private $conn;

    public function getConnection() {
        if ($this->conn === null) {
            try {
                $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4";
                $this->conn = new PDO($dsn, $this->username, $this->password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ]);
            } catch(PDOException $exception) {
                error_log("Database connection error: " . $exception->getMessage());
                throw new Exception("Database connection failed: " . $exception->getMessage());
            }
        }
        
        return $this->conn;
    }
    
    public function testConnection() {
        try {
            $conn = $this->getConnection();
            $stmt = $conn->query("SELECT 1");
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
?>