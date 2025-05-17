<?php
// Setup guest checkout functionality

// Database configuration
$host = 'localhost';
$dbname = 'tienda_mall';
$username = 'root';
$password = ''; // Default WAMP password is empty

echo "<h2>Setting up Guest Checkout System</h2>";

try {
    // Connect to database
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>✅ Connected to database '$dbname' successfully</p>";
    
    // Check if guest_carts table exists
    $tables = $conn->query("SHOW TABLES LIKE 'guest_carts'")->fetchAll();
    
    if (count($tables) === 0) {
        echo "<p>❌ Guest carts table doesn't exist. Creating it now...</p>";
        
        // Create guest_carts table
        $sqlCreateGuestCarts = "CREATE TABLE guest_carts (
            cart_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            session_id VARCHAR(100) NOT NULL UNIQUE,
            email VARCHAR(100),
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        
        $conn->exec($sqlCreateGuestCarts);
        echo "<p>✅ Guest carts table created successfully</p>";
    } else {
        echo "<p>✅ Guest carts table already exists</p>";
    }
    
    // Check if cart_items table exists
    $tables = $conn->query("SHOW TABLES LIKE 'cart_items'")->fetchAll();
    
    if (count($tables) === 0) {
        echo "<p>❌ Cart items table doesn't exist. Creating it now...</p>";
        
        // Create cart_items table
        $sqlCreateCartItems = "CREATE TABLE cart_items (
            item_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            cart_id INT(11) NOT NULL,
            user_id INT(11),
            product_id INT(11) NOT NULL,
            quantity INT(11) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        
        $conn->exec($sqlCreateCartItems);
        echo "<p>✅ Cart items table created successfully</p>";
    } else {
        echo "<p>✅ Cart items table already exists</p>";
    }
    
    // Check if guest_orders table exists
    $tables = $conn->query("SHOW TABLES LIKE 'guest_orders'")->fetchAll();
    
    if (count($tables) === 0) {
        echo "<p>❌ Guest orders table doesn't exist. Creating it now...</p>";
        
        // Create guest_orders table
        $sqlCreateGuestOrders = "CREATE TABLE guest_orders (
            order_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            order_number VARCHAR(50) NOT NULL UNIQUE,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            phone VARCHAR(20) NOT NULL,
            address TEXT NOT NULL,
            city VARCHAR(100) NOT NULL,
            state VARCHAR(100),
            postal_code VARCHAR(20) NOT NULL,
            country VARCHAR(100) NOT NULL,
            total_amount DECIMAL(10,2) NOT NULL,
            status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
            payment_method VARCHAR(50),
            payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
            notes TEXT,
            session_id VARCHAR(100),
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        
        $conn->exec($sqlCreateGuestOrders);
        echo "<p>✅ Guest orders table created successfully</p>";
    } else {
        echo "<p>✅ Guest orders table already exists</p>";
    }
    
    // Check if guest_order_items table exists
    $tables = $conn->query("SHOW TABLES LIKE 'guest_order_items'")->fetchAll();
    
    if (count($tables) === 0) {
        echo "<p>❌ Guest order items table doesn't exist. Creating it now...</p>";
        
        // Create guest_order_items table
        $sqlCreateGuestOrderItems = "CREATE TABLE guest_order_items (
            item_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            order_id INT(11) NOT NULL,
            product_id INT(11) NOT NULL,
            product_name VARCHAR(255) NOT NULL,
            quantity INT(11) NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES guest_orders(order_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        
        $conn->exec($sqlCreateGuestOrderItems);
        echo "<p>✅ Guest order items table created successfully</p>";
    } else {
        echo "<p>✅ Guest order items table already exists</p>";
    }
    
    // Update orders table to support both registered and guest users
    $tables = $conn->query("SHOW TABLES LIKE 'orders'")->fetchAll();
    
    if (count($tables) > 0) {
        // Check if guest_email column exists in orders table
        $columns = $conn->query("SHOW COLUMNS FROM orders LIKE 'guest_email'")->fetchAll();
        
        if (count($columns) === 0) {
            echo "<p>❌ Adding guest support to orders table...</p>";
            
            // Add guest_email column
            $conn->exec("ALTER TABLE orders ADD COLUMN guest_email VARCHAR(100) AFTER user_id");
            
            // Add session_id column
            $conn->exec("ALTER TABLE orders ADD COLUMN session_id VARCHAR(100) AFTER guest_email");
            
            echo "<p>✅ Orders table updated to support guest checkout</p>";
        } else {
            echo "<p>✅ Orders table already supports guest checkout</p>";
        }
    }
    
    // Create guest settings in admin_settings table
    $tables = $conn->query("SHOW TABLES LIKE 'admin_settings'")->fetchAll();
    
    if (count($tables) === 0) {
        echo "<p>❌ Admin settings table doesn't exist. Creating it now...</p>";
        
        // Create admin_settings table
        $sqlCreateAdminSettings = "CREATE TABLE admin_settings (
            setting_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) NOT NULL UNIQUE,
            setting_value TEXT,
            setting_group VARCHAR(50) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        
        $conn->exec($sqlCreateAdminSettings);
        echo "<p>✅ Admin settings table created successfully</p>";
        
        // Add guest checkout setting
        $stmt = $conn->prepare("INSERT INTO admin_settings (setting_key, setting_value, setting_group) VALUES ('enable_guest_checkout', '1', 'checkout')");
        $stmt->execute();
        echo "<p>✅ Guest checkout setting added</p>";
    } else {
        // Check if guest checkout setting exists
        $stmt = $conn->prepare("SELECT * FROM admin_settings WHERE setting_key = 'enable_guest_checkout'");
        $stmt->execute();
        $setting = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$setting) {
            // Add guest checkout setting
            $stmt = $conn->prepare("INSERT INTO admin_settings (setting_key, setting_value, setting_group) VALUES ('enable_guest_checkout', '1', 'checkout')");
            $stmt->execute();
            echo "<p>✅ Guest checkout setting added</p>";
        } else {
            echo "<p>✅ Guest checkout setting already exists</p>";
        }
    }
    
    echo "<h3>All guest checkout tables have been created successfully!</h3>";
    echo "<p>You can now <a href='../admin/settings.php'>configure guest checkout settings</a> in the admin panel.</p>";
    echo "<p><a href='../admin/index.php'>Return to Admin Dashboard</a></p>";
    
} catch (PDOException $e) {
    echo "<p>❌ Database Error: " . $e->getMessage() . "</p>";
}
?>
