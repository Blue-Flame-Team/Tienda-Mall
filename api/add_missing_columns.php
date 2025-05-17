<?php
// Database configuration
$host = 'localhost';
$dbname = 'tienda_mall';
$username = 'root';
$password = '';

try {
    // Connect to database
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if weight column already exists
    $stmt = $conn->query("SHOW COLUMNS FROM products LIKE 'weight'");
    $weight_exists = ($stmt->rowCount() > 0);
    
    if (!$weight_exists) {
        // Add weight column
        $conn->exec("ALTER TABLE products ADD COLUMN weight DECIMAL(10,2) DEFAULT NULL AFTER stock_quantity");
        echo "Success: 'weight' column added to the products table.<br>";
    } else {
        echo "Info: 'weight' column already exists in the products table.<br>";
    }
    
    // Check if dimensions column already exists
    $stmt = $conn->query("SHOW COLUMNS FROM products LIKE 'dimensions'");
    $dimensions_exists = ($stmt->rowCount() > 0);
    
    if (!$dimensions_exists) {
        // Add dimensions column
        $conn->exec("ALTER TABLE products ADD COLUMN dimensions VARCHAR(50) DEFAULT NULL AFTER weight");
        echo "Success: 'dimensions' column added to the products table.<br>";
    } else {
        echo "Info: 'dimensions' column already exists in the products table.<br>";
    }
    
    // Check and add other possibly missing columns
    $columns_to_check = [
        'meta_title' => 'VARCHAR(255) DEFAULT NULL',
        'meta_description' => 'TEXT DEFAULT NULL',
        'meta_keywords' => 'VARCHAR(255) DEFAULT NULL',
        'brand_id' => 'INT DEFAULT NULL',
        'is_featured' => 'TINYINT(1) DEFAULT 0',
        'short_description' => 'TEXT DEFAULT NULL',
        'sku' => 'VARCHAR(50) DEFAULT NULL UNIQUE'
    ];
    
    foreach ($columns_to_check as $column => $definition) {
        $stmt = $conn->query("SHOW COLUMNS FROM products LIKE '$column'");
        $column_exists = ($stmt->rowCount() > 0);
        
        if (!$column_exists) {
            // Add the column
            $conn->exec("ALTER TABLE products ADD COLUMN $column $definition");
            echo "Success: '$column' column added to the products table.<br>";
        }
    }
    
    echo "<br><strong>All necessary columns have been added or already exist.</strong><br>";
    echo "<a href='../index.php'>Return to homepage</a>";
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
