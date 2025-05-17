<?php
// Setup admin database tables

// Database configuration
$host = 'localhost';
$dbname = 'tienda_mall';
$username = 'root';
$password = ''; // Default WAMP password is empty

echo "<h2>Setting up Admin System Tables</h2>";

try {
    // Connect to database
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>✅ Connected to database '$dbname' successfully</p>";
    
    // Check if admin_users table exists
    $tables = $conn->query("SHOW TABLES LIKE 'admin_users'")->fetchAll();
    
    if (count($tables) === 0) {
        echo "<p>❌ Admin users table doesn't exist. Creating it now...</p>";
        
        // Create admin_users table
        $sqlCreateAdminUsers = "CREATE TABLE admin_users (
            admin_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            email VARCHAR(100) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            full_name VARCHAR(100) NOT NULL,
            role ENUM('super_admin', 'admin', 'editor', 'viewer') NOT NULL DEFAULT 'admin',
            last_login DATETIME,
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        
        $conn->exec($sqlCreateAdminUsers);
        echo "<p>✅ Admin users table created successfully</p>";
        
        // Insert default admin user
        $defaultUsername = 'admin';
        $defaultEmail = 'admin@tienda.com';
        $defaultPassword = 'admin123'; // This should be changed immediately
        $defaultFullName = 'Administrator';
        $hashedPassword = password_hash($defaultPassword, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO admin_users (username, email, password_hash, full_name, role) VALUES (:username, :email, :password_hash, :full_name, 'super_admin')");
        $stmt->bindParam(':username', $defaultUsername);
        $stmt->bindParam(':email', $defaultEmail);
        $stmt->bindParam(':password_hash', $hashedPassword);
        $stmt->bindParam(':full_name', $defaultFullName);
        $stmt->execute();
        
        echo "<p>✅ Default admin user created:</p>";
        echo "<ul>";
        echo "<li><strong>Username:</strong> $defaultUsername</li>";
        echo "<li><strong>Email:</strong> $defaultEmail</li>";
        echo "<li><strong>Password:</strong> $defaultPassword</li>";
        echo "<li><strong>Role:</strong> super_admin</li>";
        echo "</ul>";
        echo "<p><strong>Important:</strong> Please change this password immediately after login!</p>";
    } else {
        echo "<p>✅ Admin users table already exists</p>";
    }
    
    // Check if admin_sessions table exists
    $tables = $conn->query("SHOW TABLES LIKE 'admin_sessions'")->fetchAll();
    
    if (count($tables) === 0) {
        echo "<p>❌ Admin sessions table doesn't exist. Creating it now...</p>";
        
        // Create admin_sessions table
        $sqlCreateAdminSessions = "CREATE TABLE admin_sessions (
            session_id VARCHAR(128) NOT NULL PRIMARY KEY,
            admin_id INT(11) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            user_agent TEXT,
            payload TEXT NOT NULL,
            last_activity INT(11) NOT NULL,
            FOREIGN KEY (admin_id) REFERENCES admin_users(admin_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        
        $conn->exec($sqlCreateAdminSessions);
        echo "<p>✅ Admin sessions table created successfully</p>";
    } else {
        echo "<p>✅ Admin sessions table already exists</p>";
    }
    
    // Create admin_log table for activity tracking
    $tables = $conn->query("SHOW TABLES LIKE 'admin_log'")->fetchAll();
    
    if (count($tables) === 0) {
        echo "<p>❌ Admin log table doesn't exist. Creating it now...</p>";
        
        // Create admin_log table
        $sqlCreateAdminLog = "CREATE TABLE admin_log (
            log_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            admin_id INT(11) NOT NULL,
            action VARCHAR(100) NOT NULL,
            module VARCHAR(50) NOT NULL,
            description TEXT,
            ip_address VARCHAR(45),
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (admin_id) REFERENCES admin_users(admin_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        
        $conn->exec($sqlCreateAdminLog);
        echo "<p>✅ Admin log table created successfully</p>";
    } else {
        echo "<p>✅ Admin log table already exists</p>";
    }
    
    // Check if products table exists
    $tables = $conn->query("SHOW TABLES LIKE 'products'")->fetchAll();
    
    if (count($tables) === 0) {
        echo "<p>❌ Products table doesn't exist. Creating it now...</p>";
        
        // Create products table
        $sqlCreateProducts = "CREATE TABLE products (
            product_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            short_description VARCHAR(255),
            price DECIMAL(10,2) NOT NULL,
            sale_price DECIMAL(10,2),
            sku VARCHAR(50) UNIQUE,
            stock_quantity INT(11) DEFAULT 0,
            category_id INT(11),
            brand_id INT(11),
            featured TINYINT(1) DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        
        $conn->exec($sqlCreateProducts);
        echo "<p>✅ Products table created successfully</p>";
    } else {
        echo "<p>✅ Products table already exists</p>";
    }
    
    // Check if product_images table exists
    $tables = $conn->query("SHOW TABLES LIKE 'product_images'")->fetchAll();
    
    if (count($tables) === 0) {
        echo "<p>❌ Product images table doesn't exist. Creating it now...</p>";
        
        // Create product_images table
        $sqlCreateProductImages = "CREATE TABLE product_images (
            image_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            product_id INT(11) NOT NULL,
            image_path VARCHAR(255) NOT NULL,
            is_primary TINYINT(1) DEFAULT 0,
            sort_order INT(11) DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        
        $conn->exec($sqlCreateProductImages);
        echo "<p>✅ Product images table created successfully</p>";
    } else {
        echo "<p>✅ Product images table already exists</p>";
    }
    
    // Check if categories table exists
    $tables = $conn->query("SHOW TABLES LIKE 'categories'")->fetchAll();
    
    if (count($tables) === 0) {
        echo "<p>❌ Categories table doesn't exist. Creating it now...</p>";
        
        // Create categories table
        $sqlCreateCategories = "CREATE TABLE categories (
            category_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            parent_id INT(11) DEFAULT NULL,
            image VARCHAR(255),
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (parent_id) REFERENCES categories(category_id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        
        $conn->exec($sqlCreateCategories);
        echo "<p>✅ Categories table created successfully</p>";
    } else {
        echo "<p>✅ Categories table already exists</p>";
    }
    
    // Check if brands table exists
    $tables = $conn->query("SHOW TABLES LIKE 'brands'")->fetchAll();
    
    if (count($tables) === 0) {
        echo "<p>❌ Brands table doesn't exist. Creating it now...</p>";
        
        // Create brands table
        $sqlCreateBrands = "CREATE TABLE brands (
            brand_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            logo VARCHAR(255),
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        
        $conn->exec($sqlCreateBrands);
        echo "<p>✅ Brands table created successfully</p>";
    } else {
        echo "<p>✅ Brands table already exists</p>";
    }
    
    // Check if users table exists and create it if not
    $tables = $conn->query("SHOW TABLES LIKE 'users'")->fetchAll();
    
    if (count($tables) === 0) {
        echo "<p>❌ Users table doesn't exist. Creating it now...</p>";
        
        // Create users table
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
        echo "<p>✅ Users table already exists</p>";
    }
    
    // Check if orders table exists
    $tables = $conn->query("SHOW TABLES LIKE 'orders'")->fetchAll();
    
    if (count($tables) === 0) {
        echo "<p>❌ Orders table doesn't exist. Creating it now...</p>";
        
        // Create orders table without foreign key constraint
        $sqlCreateOrders = "CREATE TABLE orders (
            order_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id INT(11),
            order_number VARCHAR(50) NOT NULL UNIQUE,
            status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
            total_amount DECIMAL(10,2) NOT NULL,
            shipping_address TEXT,
            billing_address TEXT,
            payment_method VARCHAR(50),
            payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP
            -- Foreign key temporarily removed
            -- FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        
        $conn->exec($sqlCreateOrders);
        echo "<p>✅ Orders table created successfully</p>";
    } else {
        echo "<p>✅ Orders table already exists</p>";
    }
    
    // Check if order_items table exists
    $tables = $conn->query("SHOW TABLES LIKE 'order_items'")->fetchAll();
    
    if (count($tables) === 0) {
        echo "<p>❌ Order items table doesn't exist. Creating it now...</p>";
        
        // Create order_items table
        $sqlCreateOrderItems = "CREATE TABLE order_items (
            item_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            order_id INT(11) NOT NULL,
            product_id INT(11),
            product_name VARCHAR(255) NOT NULL,
            quantity INT(11) NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        
        $conn->exec($sqlCreateOrderItems);
        echo "<p>✅ Order items table created successfully</p>";
    } else {
        echo "<p>✅ Order items table already exists</p>";
    }
    
    // Create admin_log table for activity tracking
    $tables = $conn->query("SHOW TABLES LIKE 'admin_log'")->fetchAll();
    
    if (count($tables) === 0) {
        echo "<p>❌ Admin log table doesn't exist. Creating it now...</p>";
        
        // Create admin_log table
        $sqlCreateAdminLog = "CREATE TABLE admin_log (
            log_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            admin_id INT(11) NOT NULL,
            action VARCHAR(100) NOT NULL,
            module VARCHAR(50) NOT NULL,
            description TEXT,
            ip_address VARCHAR(45),
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (admin_id) REFERENCES admin_users(admin_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        
        $conn->exec($sqlCreateAdminLog);
        echo "<p>✅ Admin log table created successfully</p>";
    } else {
        echo "<p>✅ Admin log table already exists</p>";
    }
    
    echo "<h3>All admin system tables have been created successfully!</h3>";
    echo "<p>You can now <a href='../admin/login.php'>login to the admin panel</a> using the default credentials.</p>";
    
} catch (PDOException $e) {
    echo "<p>❌ Database Error: " . $e->getMessage() . "</p>";
}
?>
