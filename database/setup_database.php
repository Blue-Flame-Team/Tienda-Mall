<?php
// Database setup script - creates the tienda_mall database and imports the schema

// Include the configuration file to get database credentials
require_once '../includes/config.php';

// Enable error display for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Helper function to output HTML messages
function outputMessage($message, $isError = false) {
    $style = $isError ? 'color: red; font-weight: bold;' : 'color: green;';
    echo "<div style='{$style}'>{$message}</div>";
}

// Create connection without database selected
try {
    outputMessage("Starting database setup...");
    
    // Connect to MySQL server without selecting a database
    $conn = new PDO(
        "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    
    outputMessage("Connected to MySQL server successfully");
    
    // Check if database exists and drop it if it does
    $conn->exec("DROP DATABASE IF EXISTS " . DB_NAME);
    outputMessage("Dropped existing database if it existed");
    
    // Create database
    $sql = "CREATE DATABASE " . DB_NAME;
    $conn->exec($sql);
    outputMessage("Database '" . DB_NAME . "' created successfully");
    
    // Select the database
    $conn->exec("USE " . DB_NAME);
    outputMessage("Database '" . DB_NAME . "' selected");
    
    // Read the SQL file and execute it
    $sqlFilePath = 'fixed_schema_v2.sql';
    
    if (!file_exists($sqlFilePath)) {
        // If fixed schema v2 isn't found, try the other files
        $sqlFilePath = 'fixed_schema.sql';
        if (!file_exists($sqlFilePath)) {
            $sqlFilePath = 'new_schema.sql';
            if (!file_exists($sqlFilePath)) {
                $sqlFilePath = 'mysql_database.sql';
                if (!file_exists($sqlFilePath)) {
                    throw new Exception("No SQL schema files found. Please ensure one of the schema files exists.");
                }
                outputMessage("Using original schema file: {$sqlFilePath}");
            } else {
                outputMessage("Using new schema file: {$sqlFilePath}");
            }
        } else {
            outputMessage("Using fixed schema file: {$sqlFilePath}");
        }
    } else {
        outputMessage("Using fixed schema v2 file: {$sqlFilePath}");
    }
    
    $sqlFile = file_get_contents($sqlFilePath);
    
    // Remove CREATE DATABASE and USE statements since we've already handled them
    $sqlFile = preg_replace('/CREATE DATABASE.*?;/is', '', $sqlFile);
    $sqlFile = preg_replace('/USE.*?;/is', '', $sqlFile);
    
    // Execute each statement individually to avoid parsing issues
    $executedStatements = 0;
    $errorStatements = 0;
    
    // Create tables one by one to avoid parsing issues
    try {
        // Users table
        $conn->exec("CREATE TABLE users ( 
          user_id INT PRIMARY KEY AUTO_INCREMENT, 
          profile_image VARCHAR(255), 
          email_verified VARCHAR(3) DEFAULT 'NO', 
          verification_token VARCHAR(64), 
          password_hash CHAR(60) NOT NULL, 
          reset_token VARCHAR(64), 
          reset_token_expiry DATETIME DEFAULT CURRENT_TIMESTAMP,
          email VARCHAR(100) UNIQUE NOT NULL,
          first_name VARCHAR(80) NOT NULL,
          last_name VARCHAR(80) NOT NULL,
          phone VARCHAR(20) UNIQUE,
          date_of_birth DATE,
          is_active VARCHAR(3) DEFAULT 'YES',
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          last_login DATETIME
        );");
        $executedStatements++;
        outputMessage("Created users table successfully");
        
        // Shipping addresses table
        $conn->exec("CREATE TABLE shipping_addresses(
          address_id INT PRIMARY KEY AUTO_INCREMENT,
          user_id INT NOT NULL,
          full_name VARCHAR(255) NOT NULL,
          city VARCHAR(80) NOT NULL,
          address_line1 VARCHAR(255) NOT NULL,
          address_line2 VARCHAR(255), 
          phone VARCHAR(20) NOT NULL,
          is_default TINYINT(1) DEFAULT 0,
          country VARCHAR(100) NOT NULL, 
          postal_code VARCHAR(20) NOT NULL,
          state VARCHAR(100) NOT NULL,
          FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        );");
        $executedStatements++;
        outputMessage("Created shipping_addresses table successfully");
        
        // Orders table
        $conn->exec("CREATE TABLE `order` (
          order_id INT PRIMARY KEY AUTO_INCREMENT,
          user_id INT NOT NULL,
          order_date DATETIME DEFAULT CURRENT_TIMESTAMP,
          status VARCHAR(20) NOT NULL DEFAULT 'Pending',
          subtotal DECIMAL(10,2) NOT NULL,
          shipping_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
          tax_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
          discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
          total_amount DECIMAL(10,2) NOT NULL,
          payment_method VARCHAR(50),
          payment_status VARCHAR(20) DEFAULT 'Pending',
          notes TEXT,
          tracking_number VARCHAR(100),
          shipping_provider VARCHAR(100),
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE RESTRICT
        );");
        $executedStatements++;
        outputMessage("Created order table successfully");
        
        // Category table
        $conn->exec("CREATE TABLE category (
          category_id INT PRIMARY KEY AUTO_INCREMENT,
          name VARCHAR(100) NOT NULL,
          description TEXT,
          parent_id INT,
          image_url VARCHAR(255),
          is_active VARCHAR(3) DEFAULT 'YES',
          is_featured VARCHAR(3) DEFAULT 'NO',
          sort_order INT DEFAULT 0,
          seo_title VARCHAR(100),
          seo_description VARCHAR(255),
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          FOREIGN KEY (parent_id) REFERENCES category(category_id) ON DELETE SET NULL
        );");
        $executedStatements++;
        outputMessage("Created category table successfully");
        
        // Product table
        $conn->exec("CREATE TABLE product (
          product_id INT PRIMARY KEY AUTO_INCREMENT,
          title VARCHAR(255) NOT NULL,
          description TEXT,
          price DECIMAL(10,2) NOT NULL,
          cost_price DECIMAL(10,2),
          old_price DECIMAL(10,2),
          sku VARCHAR(50) UNIQUE,
          quantity INT NOT NULL DEFAULT 0,
          is_featured CHAR(3) DEFAULT 'YES',
          is_on_sale CHAR(3) DEFAULT 'NO',
          is_new CHAR(3) DEFAULT 'NO',
          is_best_seller CHAR(3) DEFAULT 'NO',
          is_active CHAR(1) DEFAULT 'Y',
          image_url VARCHAR(255),
          weight DECIMAL(8,2),
          dimensions VARCHAR(50),
          rating DECIMAL(3,2) DEFAULT 0,
          review_count INT DEFAULT 0,
          seo_title VARCHAR(100),
          seo_description VARCHAR(255),
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        );");
        $executedStatements++;
        outputMessage("Created product table successfully");
        
        // Product image table
        $conn->exec("CREATE TABLE product_image (
          image_id INT PRIMARY KEY AUTO_INCREMENT,
          product_id INT NOT NULL,
          image_url VARCHAR(255) NOT NULL,
          is_primary CHAR(3) DEFAULT 'YES',
          sort_order INT DEFAULT 0,
          created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
          FOREIGN KEY (product_id) REFERENCES product(product_id) ON DELETE CASCADE
        );");
        $executedStatements++;
        outputMessage("Created product_image table successfully");
        
        // Order items table
        $conn->exec("CREATE TABLE order_item (
          item_id INT PRIMARY KEY AUTO_INCREMENT,
          order_id INT NOT NULL,
          product_id INT NOT NULL,
          quantity INT NOT NULL,
          price DECIMAL(10,2) NOT NULL,
          discount DECIMAL(10,2) DEFAULT 0,
          variation TEXT,
          FOREIGN KEY (order_id) REFERENCES `order`(order_id) ON DELETE CASCADE,
          FOREIGN KEY (product_id) REFERENCES product(product_id) ON DELETE RESTRICT
        );");
        $executedStatements++;
        outputMessage("Created order_item table successfully");
        
        // Wishlist table
        $conn->exec("CREATE TABLE wishlist (
          wishlist_id INT PRIMARY KEY AUTO_INCREMENT,
          user_id INT NOT NULL,
          product_id INT NOT NULL,
          added_date DATETIME DEFAULT CURRENT_TIMESTAMP,
          UNIQUE KEY (user_id, product_id),
          FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
          FOREIGN KEY (product_id) REFERENCES product(product_id) ON DELETE CASCADE
        );");
        $executedStatements++;
        outputMessage("Created wishlist table successfully");
        
    } catch(PDOException $e) {
        $errorStatements++;
        outputMessage("Error executing statement: {$e->getMessage()}", true);
    }
    
    outputMessage("Database schema import completed. {$executedStatements} statements executed successfully. {$errorStatements} errors.");
    
    // Create test/demo data if needed
    // Insert a sample admin user if not exists
    try {
        $stmt = $conn->prepare("INSERT INTO users (email, first_name, last_name, password_hash, is_active) 
                              VALUES (?, ?, ?, ?, ?)");
        $adminEmail = 'admin@tiendamall.com';
        $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt->execute([$adminEmail, 'Admin', 'User', $passwordHash, 'YES']);
        
        outputMessage("Sample admin user created with email: {$adminEmail} and password: admin123");
    } catch(PDOException $e) {
        outputMessage("Note: Admin user may already exist. {$e->getMessage()}");
    }
    
    outputMessage("Database setup complete!");
    
    // Display message with next steps
    echo "<div style='margin-top: 20px; padding: 10px; background-color: #f0f0f0; border: 1px solid #ccc;'>";
    echo "<h3>Next Steps:</h3>";
    echo "<ol>";
    echo "<li>Go to <a href='../index.php'>Homepage</a> to view your site</li>";
    echo "<li>Login with admin@tiendamall.com / admin123</li>";
    echo "</ol>";
    echo "</div>";
    
} catch(Exception $e) {
    outputMessage("Setup failed: " . $e->getMessage(), true);
}
?>
