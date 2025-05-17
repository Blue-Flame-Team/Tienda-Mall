<?php
// Create Database Script

echo "<h2>Database Creation Tool</h2>";

// Database configuration
$host = 'localhost';
$username = 'root';
$password = ''; // Default WAMP password is empty
$database = 'tienda_mall';

try {
    // Connect to MySQL server without specifying a database
    $conn = new PDO("mysql:host=$host", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>✅ Connected to MySQL server successfully</p>";
    
    // Check if database exists
    $stmt = $conn->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$database'");
    $dbExists = $stmt->fetch();
    
    if ($dbExists) {
        echo "<p>✅ Database '$database' already exists</p>";
    } else {
        // Create database
        $conn->exec("CREATE DATABASE $database CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
        echo "<p>✅ Database '$database' created successfully</p>";
    }
    
    // Connect to the database
    $conn = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create users table if it doesn't exist
    $sqlUsers = "CREATE TABLE IF NOT EXISTS users (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    $conn->exec($sqlUsers);
    echo "<p>✅ 'users' table created/checked successfully</p>";
    
    // Check if the table has data
    $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
    $userCount = $stmt->fetch();
    echo "<p>ℹ️ Number of users in database: {$userCount['count']}</p>";
    
    echo "<hr><p>Database setup is complete. You can now:</p>";
    echo "<ul>";
    echo "<li><a href='../pages/signup.php'>Register a new account</a></li>";
    echo "<li><a href='../pages/login.php'>Login with your account</a></li>";
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<p>❌ Database Error: " . $e->getMessage() . "</p>";
}
?>
