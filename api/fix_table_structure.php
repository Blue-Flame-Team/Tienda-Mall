<?php
// Fix Table Structure Script

echo "<h2>Fix Database Table Structure</h2>";

// Database configuration
$host = 'localhost';
$username = 'root';
$password = ''; // Default WAMP password is empty
$database = 'tienda_mall';

try {
    // Connect to the database
    $conn = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>✅ Connected to database '$database' successfully</p>";
    
    // Check if users table exists
    $tables = $conn->query("SHOW TABLES LIKE 'users'")->fetchAll();
    
    if (count($tables) === 0) {
        echo "<p>❌ Users table doesn't exist. Creating it now...</p>";
        
        // Create users table with structure matching existing database
        $sqlCreateUsers = "CREATE TABLE users (
            user_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100),
            email VARCHAR(100) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            profile_image VARCHAR(255),
            email_verified TINYINT(1) DEFAULT 0,
            verification_token VARCHAR(100),
            reset_token VARCHAR(100),
            reset_token_expiry DATETIME,
            phone VARCHAR(20),
            date_of_birth DATE,
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            last_login DATETIME
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        
        $conn->exec($sqlCreateUsers);
        echo "<p>✅ Users table created successfully</p>";
    } else {
        echo "<p>✅ Users table exists. Checking structure...</p>";
        
        // Get current columns
        $columns = [];
        $columnsResult = $conn->query("SHOW COLUMNS FROM users");
        while ($column = $columnsResult->fetch(PDO::FETCH_ASSOC)) {
            $columns[] = $column['Field'];
        }
        
        echo "<p>Current columns: " . implode(", ", $columns) . "</p>";
        
        // Check that we're working with the primary key field (user_id)
        if (!in_array('user_id', $columns)) {
            echo "<p>❌ Primary key 'user_id' column is missing. This is a critical issue.</p>";
        }
        
        // Ensure the first_name field exists
        if (!in_array('first_name', $columns)) {
            echo "<p>❌ 'first_name' column is missing. Adding it now...</p>";
            $conn->exec("ALTER TABLE users ADD COLUMN first_name VARCHAR(100) NOT NULL AFTER user_id");
            echo "<p>✅ Added 'first_name' column</p>";
        }
        
        // Ensure the last_name field exists
        if (!in_array('last_name', $columns)) {
            echo "<p>❌ 'last_name' column is missing. Adding it now...</p>";
            $conn->exec("ALTER TABLE users ADD COLUMN last_name VARCHAR(100) AFTER first_name");
            echo "<p>✅ Added 'last_name' column</p>";
        }
        
        // Ensure password_hash exists
        if (!in_array('password_hash', $columns)) {
            echo "<p>❌ 'password_hash' column is missing. Adding it now...</p>";
            $conn->exec("ALTER TABLE users ADD COLUMN password_hash VARCHAR(255) NOT NULL AFTER email");
            echo "<p>✅ Added 'password_hash' column</p>";
        }
        
        // Ensure is_active exists
        if (!in_array('is_active', $columns)) {
            echo "<p>❌ 'is_active' column is missing. Adding it now...</p>";
            $conn->exec("ALTER TABLE users ADD COLUMN is_active TINYINT(1) DEFAULT 1");
            echo "<p>✅ Added 'is_active' column</p>";
        }
        
        // Ensure last_login exists
        if (!in_array('last_login', $columns)) {
            echo "<p>❌ 'last_login' column is missing. Adding it now...</p>";
            $conn->exec("ALTER TABLE users ADD COLUMN last_login DATETIME");
            echo "<p>✅ Added 'last_login' column</p>";
        }
    }
    
    // Show final table structure
    echo "<h3>Final Users Table Structure:</h3>";
    $columnsResult = $conn->query("SHOW COLUMNS FROM users");
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($column = $columnsResult->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        foreach ($column as $value) {
            echo "<td>{$value}</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<hr><p>Table structure has been fixed. You can now:</p>";
    echo "<ul>";
    echo "<li><a href='../pages/signup.php'>Register a new account</a></li>";
    echo "<li><a href='../pages/login.html'>Login with your account</a></li>";
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<p>❌ Database Error: " . $e->getMessage() . "</p>";
}
?>
