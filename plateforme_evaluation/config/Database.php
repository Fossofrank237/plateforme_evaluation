<?php
namespace Config;

    use PDO;
    use PDOException;

    // Configuration de la base de données
    class Database {
        private static $instance = null;
        private $host = 'localhost';
        private $dbName = 'quiz_system';
        private $username = 'root';
        private $password = '';
        private $conn;
        
        private function __construct() {
            try {
                $this->conn = new PDO(
                    dsn: "mysql:host={$this->host};dbname={$this->dbName};charset=utf8mb4",
                    username: $this->username,
                    password: $this->password,
                    options: [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES   => false,
                    ]
                );
            } catch (PDOException $e) {
                error_log("Database connection error: " . $e->getMessage());
                die("A database error occurred. Please try again later.");
            }                        
        }

        public function __destruct() {
            $this->disconnect();
        }        
        
        public static function getInstance() {
            if (self::$instance === null) {
                self::$instance = new self();
            }
            return self::$instance;
        }
        
        public function getConnection() {
            return $this->conn;
        }

        public function disconnect() {
            $this->conn = null;
        }
    }

?>