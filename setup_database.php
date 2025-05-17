<?php
// Database setup script - creates the tienda_mall database and imports the schema

// Enable detailed error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include the configuration file to get database credentials
require_once 'includes/config.php';

echo "<h1>Database Setup for Tienda Mall</h1>";

// Get the database host (remove port if present)
$host = explode(':', DB_HOST)[0];
$port = isset(explode(':', DB_HOST)[1]) ? explode(':', DB_HOST)[1] : 3306;

// Create connection to MySQL server without specifying a database
$conn = new mysqli($host, DB_USER, DB_PASS, "", $port);

// Check connection
if ($conn->connect_error) {
    die("<p style='color:red'>Connection failed: " . $conn->connect_error . "</p>");
}

echo "<p style='color:green'>✅ Connected to MySQL server successfully</p>";

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
if ($conn->query($sql) === TRUE) {
    echo "<p style='color:green'>✅ Database '" . DB_NAME . "' created successfully</p>";
} else {
    echo "<p style='color:red'>Error creating database: " . $conn->error . "</p>";
    die();
}

// Select the database
$conn->select_db(DB_NAME);
echo "<p style='color:green'>✅ Database '" . DB_NAME . "' selected</p>";

// Read the SQL file contents
$sqlFile = file_get_contents('database/mysql_database.sql');

// Remove CREATE DATABASE and USE statements
$sqlFile = preg_replace('/CREATE DATABASE.*?;/is', '', $sqlFile);
$sqlFile = preg_replace('/USE.*?;/is', '', $sqlFile);

// Split the SQL into individual statements
$statements = explode(';', $sqlFile);

// Counter for successful operations
$successCount = 0;

// Execute each statement
foreach ($statements as $statement) {
    $statement = trim($statement);
    if (!empty($statement)) {
        if ($conn->query($statement) === TRUE) {
            if (stripos($statement, 'CREATE TABLE') !== false) {
                // Extract table name for better reporting
                preg_match('/CREATE TABLE\s+(\w+)/i', $statement, $matches);
                $tableName = isset($matches[1]) ? $matches[1] : 'Unknown';
                echo "<p style='color:green'>✅ Created table: $tableName</p>";
                $successCount++;
            }
        } else {
            echo "<p style='color:red'>❌ Error executing statement: " . $conn->error . "</p>";
            echo "<p>Statement: " . htmlspecialchars($statement) . "</p>";
        }
    }
}

// Insert sample data for testing
echo "<h3>Creating sample data...</h3>";

// Create a category if none exist
$result = $conn->query("SELECT COUNT(*) as count FROM category");
$row = $result->fetch_assoc();
if ($row['count'] == 0) {
    $conn->query("INSERT INTO category (category_id, name, description, is_active, image_url) VALUES 
        (1, 'Smartphones', 'Latest smartphones and accessories', 'YES', 'assets/images/Category-Smartphone.png'),
        (2, 'Computers', 'Laptops and desktop computers', 'YES', 'assets/images/Category-Computer.png'),
        (3, 'Electronics', 'Electronic devices and gadgets', 'YES', 'assets/images/Category-Camera.png'),
        (4, 'Headphones', 'Premium audio headphones', 'YES', 'assets/images/Category-Headphone.png')");
    echo "<p style='color:green'>✅ Added sample categories</p>";
}

// Create products if none exist
$result = $conn->query("SELECT COUNT(*) as count FROM product");
$row = $result->fetch_assoc();
if ($row['count'] == 0) {
    $conn->query("INSERT INTO product (product_id, title, description, price, sku, is_active, created_at, meta_title, meta_description) VALUES
        (1, 'Smartphone X Pro', 'Latest flagship smartphone with premium features', 999.99, 'PHONE-X-PRO', 'Y', NOW(), 'Smartphone X Pro', 'High-end smartphone with advanced features'),
        (2, 'Laptop UltraBook', 'Lightweight and powerful laptop', 1299.99, 'LAPTOP-ULTRA', 'Y', NOW(), 'Laptop UltraBook', 'Powerful laptop for professionals'),
        (3, 'Wireless Headphones', 'Premium noise-cancelling headphones', 249.99, 'AUDIO-HEAD-01', 'Y', NOW(), 'Wireless Headphones', 'Premium audio experience'),
        (4, 'Smart Watch Series 5', 'Advanced smartwatch with health features', 349.99, 'WATCH-S5', 'Y', NOW(), 'Smart Watch', 'Track your fitness and stay connected')");
    echo "<p style='color:green'>✅ Added sample products</p>";
}

// Add product images
$result = $conn->query("SELECT COUNT(*) as count FROM product_image");
$row = $result->fetch_assoc();
if ($row['count'] == 0) {
    $conn->query("INSERT INTO product_image (image_id, product_id, image_url, is_primary, sort_order) VALUES
        (1, 1, 'assets/images/image-product-1.jpg', 'YES', 1),
        (2, 2, 'assets/images/image-product-2.jpg', 'YES', 1),
        (3, 3, 'assets/images/image-product-3.jpg', 'YES', 1),
        (4, 4, 'assets/images/image-product-4.jpg', 'YES', 1)");
    echo "<p style='color:green'>✅ Added product images</p>";
}

// Add inventory records
$result = $conn->query("SELECT COUNT(*) as count FROM inventory");
$row = $result->fetch_assoc();
if ($row['count'] == 0) {
    $conn->query("INSERT INTO inventory (inventory_id, product_id, quantity, low_stock_threshold, warehous_location) VALUES
        (1, 1, 100, 10, 'Warehouse A'),
        (2, 2, 50, 5, 'Warehouse B'),
        (3, 3, 200, 20, 'Warehouse A'),
        (4, 4, 75, 10, 'Warehouse C')");
    echo "<p style='color:green'>✅ Added inventory records</p>";
}

// Add product categorization
$result = $conn->query("SELECT COUNT(*) as count FROM categorized_in");
$row = $result->fetch_assoc();
if ($row['count'] == 0) {
    $conn->query("INSERT INTO categorized_in (product_id, category_id) VALUES
        (1, 1), (2, 2), (3, 4), (4, 3)");
    echo "<p style='color:green'>✅ Added product categorization</p>";
}

echo "<h3>Setup Summary</h3>";
echo "<p>Successfully created $successCount tables and added sample data</p>";
echo "<p style='color:green; font-weight:bold'>Database setup complete! You can now <a href='index.php'>go to the homepage</a>.</p>";

// Close connection
$conn->close();
?>
