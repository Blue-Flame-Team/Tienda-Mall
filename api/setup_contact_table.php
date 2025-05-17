<?php
// Setup contact messages table

// Database configuration
$host = 'localhost';
$dbname = 'tienda_mall';
$username = 'root';
$password = ''; // Default WAMP password is empty

echo "<h2>Setting up Contact Messages Table</h2>";

try {
    // Connect to database
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>✅ Connected to database '$dbname' successfully</p>";
    
    // Check if contact_messages table exists
    $tables = $conn->query("SHOW TABLES LIKE 'contact_messages'")->fetchAll();
    
    if (count($tables) === 0) {
        echo "<p>❌ 'contact_messages' table doesn't exist. Creating it now...</p>";
        
        // Create contact_messages table
        $sql = "CREATE TABLE contact_messages (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            phone VARCHAR(20) NOT NULL,
            message TEXT NOT NULL,
            status ENUM('new', 'read', 'responded') DEFAULT 'new',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        
        $conn->exec($sql);
        echo "<p>✅ 'contact_messages' table created successfully</p>";
    } else {
        echo "<p>✅ 'contact_messages' table already exists</p>";
        
        // Get current columns
        $columns = [];
        $columnsResult = $conn->query("SHOW COLUMNS FROM contact_messages");
        while ($column = $columnsResult->fetch(PDO::FETCH_ASSOC)) {
            $columns[] = $column['Field'];
        }
        
        echo "<p>Current columns: " . implode(", ", $columns) . "</p>";
    }
    
    // Show table structure
    echo "<h3>Contact Messages Table Structure:</h3>";
    $columnsResult = $conn->query("SHOW COLUMNS FROM contact_messages");
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
    
} catch (PDOException $e) {
    echo "<p>❌ Database Error: " . $e->getMessage() . "</p>";
}
?>
