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
    $column_exists = ($stmt->rowCount() > 0);
    
    if (!$column_exists) {
        // Add weight column
        $conn->exec("ALTER TABLE products ADD COLUMN weight DECIMAL(10,2) DEFAULT NULL AFTER stock_quantity");
        echo "Success: 'weight' column added to the products table.<br>";
    } else {
        echo "Info: 'weight' column already exists in the products table.<br>";
    }
    
    echo "<a href='../index.php'>Return to homepage</a>";
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
