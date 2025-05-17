<?php
// Fix for missing columns in the products table
// This script will add all missing columns required by the admin system

// Database configuration
$host = 'localhost';
$dbname = 'tienda_mall';
$username = 'root';
$password = '';

echo "<h1>Database Structure Fix Utility</h1>";

try {
    // Connect to database
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>Connected to database successfully.</p>";
    
    // Get current columns in the products table
    $stmt = $conn->query("SHOW COLUMNS FROM products");
    $existing_columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    
    echo "<p>Current columns in products table: " . implode(", ", $existing_columns) . "</p>";
    
    // Define all required columns with their definitions
    $required_columns = [
        'product_id' => 'INT AUTO_INCREMENT PRIMARY KEY',
        'name' => 'VARCHAR(255) NOT NULL',
        'sku' => 'VARCHAR(50) DEFAULT NULL',
        'description' => 'TEXT',
        'short_description' => 'TEXT',
        'price' => 'DECIMAL(10,2) NOT NULL',
        'sale_price' => 'DECIMAL(10,2) DEFAULT NULL',
        'stock_quantity' => 'INT DEFAULT 0',
        'weight' => 'DECIMAL(10,2) DEFAULT NULL',
        'dimensions' => 'VARCHAR(100) DEFAULT NULL',
        'category_id' => 'INT',
        'brand_id' => 'INT DEFAULT NULL',
        'is_featured' => 'TINYINT(1) DEFAULT 0',
        'is_active' => 'TINYINT(1) DEFAULT 1',
        'meta_title' => 'VARCHAR(255) DEFAULT NULL',
        'meta_description' => 'TEXT DEFAULT NULL',
        'meta_keywords' => 'VARCHAR(255) DEFAULT NULL',
        'image_url' => 'VARCHAR(255) DEFAULT NULL',
        'created_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
        'updated_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
    ];
    
    // Find missing columns
    $missing_columns = array_diff(array_keys($required_columns), $existing_columns);
    
    if (count($missing_columns) === 0) {
        echo "<p style='color:green'>All required columns already exist in the products table.</p>";
    } else {
        echo "<p>Missing columns: " . implode(", ", $missing_columns) . "</p>";
        
        // Add missing columns
        foreach ($missing_columns as $column) {
            $definition = $required_columns[$column];
            
            // Skip primary key as it likely already exists with a different name
            if ($column === 'product_id') continue;
            
            try {
                $sql = "ALTER TABLE products ADD COLUMN $column $definition";
                $conn->exec($sql);
                echo "<p style='color:green'>Added column '$column' with definition '$definition'</p>";
            } catch (PDOException $e) {
                echo "<p style='color:red'>Error adding column '$column': " . $e->getMessage() . "</p>";
            }
        }
    }
    
    echo "<p><a href='index.php' style='padding: 10px 20px; background-color: #4CAF50; color: white; 
             text-decoration: none; border-radius: 5px;'>Return to Homepage</a></p>";
    
} catch (PDOException $e) {
    die("<p style='color:red'>Database connection failed: " . $e->getMessage() . "</p>");
}
