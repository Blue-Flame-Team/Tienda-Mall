<?php
/* 
 * Database Connection File
 * This file creates a connection to the database using the configuration settings
 */

// Include configuration file
require_once 'config.php';

/**
 * Database Connection Class
 */
// Only define the Database class if it doesn't already exist
if (!class_exists('Database')) {
class Database {
    private $conn;
    private static $instance;

    /**
     * Private constructor - singleton pattern
     */
    private function __construct() {
        try {
            // Create a PDO instance
            $this->conn = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            // Log error and show specific message during development
            error_log('Connection Error: ' . $e->getMessage());
            
            // In development mode, show the actual error for debugging
            if (defined('DEV_MODE') && DEV_MODE === true) {
                die("Database connection failed: " . $e->getMessage());
            } else {
                die("Database connection failed. Please try again later.");
            }
        }
    }

    /**
     * Get database instance - singleton pattern
     * @return Database instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get database connection
     * @return PDO connection
     */
    public function getConnection() {
        return $this->conn;
    }

    /**
     * Prepare and execute a query
     * @param string $sql SQL query
     * @param array $params Parameters for prepared statement
     * @return PDOStatement
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log('Query Error: ' . $e->getMessage());
            die("Database query failed. Please try again later.");
        }
    }
}
} // End of class_exists check
?>
