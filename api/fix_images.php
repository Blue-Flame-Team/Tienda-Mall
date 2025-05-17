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
    
    echo "<h2>Fixing Image URL Column</h2>";
    
    // First check if the column already exists
    $stmt = $conn->query("SHOW COLUMNS FROM products LIKE 'image_url'");
    $column_exists = ($stmt->rowCount() > 0);
    
    if (!$column_exists) {
        // Add the image_url column to products table
        $conn->exec("ALTER TABLE products ADD COLUMN image_url VARCHAR(255) DEFAULT NULL");
        echo "<p style='color:green'>Successfully added 'image_url' column to products table.</p>";
        
        // If product_images table exists, let's migrate image data
        try {
            $conn->query("SELECT 1 FROM product_images LIMIT 1");
            
            // We have a product_images table, let's update the image_url in products with main images
            $conn->exec(
                "UPDATE products p 
                 INNER JOIN (
                     SELECT product_id, image_url 
                     FROM product_images 
                     WHERE is_main = 1 OR is_primary = 1 
                     GROUP BY product_id
                 ) pi ON p.product_id = pi.product_id
                 SET p.image_url = pi.image_url"
            );
            echo "<p style='color:green'>Successfully migrated main images from product_images table to products table.</p>";
        } catch (PDOException $e) {
            // No product_images table, that's fine
            echo "<p>Note: No product_images table found to migrate images from.</p>";
        }
    } else {
        echo "<p>The 'image_url' column already exists in products table.</p>";
    }
    
    echo "<p><a href='../index.php' style='padding: 10px 20px; background: #4CAF50; color: white; 
          text-decoration: none; border-radius: 4px;'>Return to Homepage</a></p>";
    
} catch (PDOException $e) {
    echo "<h2 style='color:red'>Database Error</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
