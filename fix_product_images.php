<?php
// Database configuration
$host = 'localhost';
$dbname = 'tienda_mall';
$username = 'root';
$password = '';

header('Content-Type: text/html; charset=utf-8');
echo "<h1>u0625u0635u0644u0627u062d u062cu062fu0648u0644 u0635u0648u0631 u0627u0644u0645u0646u062au062cu0627u062a</h1>";
echo "<style>body { font-family: Arial, sans-serif; direction: rtl; padding: 20px; }</style>";

try {
    // Connect to database
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>u062au062du0644u064au0644 u0627u0644u062cu062fu0648u0644 u0627u0644u062du0627u0644u064a</h2>";
    echo "<ul>";
    
    // Check if product_images table exists
    $tableExists = $conn->query("SHOW TABLES LIKE 'product_images'")->rowCount() > 0;
    
    if ($tableExists) {
        echo "<li>u062cu062fu0648u0644 product_images u0645u0648u062cu0648u062f</li>";
        
        // Get current columns
        $columns = $conn->query("SHOW COLUMNS FROM product_images")->fetchAll(PDO::FETCH_COLUMN);
        echo "<li>u0627u0644u0623u0639u0645u062fu0629 u0627u0644u062du0627u0644u064au0629: " . implode(", ", $columns) . "</li>";
        
        // Check if image_path exists
        $hasImagePath = in_array('image_path', $columns);
        if (!$hasImagePath) {
            echo "<li>u0639u0645u0648u062f image_path u063au064au0631 u0645u0648u062cu0648u062f u0648u0633u062au062au0645 u0625u0636u0627u0641u062au0647</li>";
        } else {
            echo "<li>u0639u0645u0648u062f image_path u0645u0648u062cu0648u062f u0628u0627u0644u0641u0639u0644</li>";
        }
        
        // Check if image_url exists
        $hasImageUrl = in_array('image_url', $columns);
        if ($hasImageUrl) {
            echo "<li>u0639u0645u0648u062f image_url u0645u0648u062cu0648u062f</li>";
        } else {
            echo "<li>u0639u0645u0648u062f image_url u063au064au0631 u0645u0648u062cu0648u062f</li>";
        }
        
        // Check if table has a foreign key
        $foreignKeys = $conn->query("SHOW CREATE TABLE product_images")->fetch()[1];
        $hasForeignKey = strpos($foreignKeys, 'FOREIGN KEY') !== false;
        
        if ($hasForeignKey) {
            echo "<li>u064au0648u062cu062f u0645u0641u062au0627u062d u0623u062cu0646u0628u064a u0641u064a u0627u0644u062cu062fu0648u0644</li>";
            
            // Check if foreign key references products_old
            $referencesProductsOld = strpos($foreignKeys, 'REFERENCES `products_old`') !== false;
            if ($referencesProductsOld) {
                echo "<li>u0627u0644u0645u0641u062au0627u062d u0627u0644u0623u062cu0646u0628u064a u064au0634u064au0631 u0625u0644u0649 u062cu062fu0648u0644 products_old u0648u0644u064au0633 products</li>";
            }
        } else {
            echo "<li>u0644u0627 u064au0648u062cu062f u0645u0641u062au0627u062d u0623u062cu0646u0628u064a u0641u064a u0627u0644u062cu062fu0648u0644</li>";
        }
        
    } else {
        echo "<li>u062cu062fu0648u0644 product_images u063au064au0631 u0645u0648u062cu0648u062f u0648u0633u064au062au0645 u0625u0646u0634u0627u0624u0647</li>";
    }
    
    echo "</ul>";
    
    // Fix issues
    echo "<h2>u0625u0635u0644u0627u062d u0627u0644u0645u0634u0627u0643u0644</h2>";
    echo "<ul>";
    
    // Create backup of original table if it exists
    if ($tableExists) {
        $conn->exec("CREATE TABLE IF NOT EXISTS product_images_backup LIKE product_images");
        $conn->exec("INSERT INTO product_images_backup SELECT * FROM product_images");
        echo "<li>u062au0645 u0625u0646u0634u0627u0621 u0646u0633u062eu0629 u0627u062du062au064au0627u0637u064au0629 u0645u0646 u062cu062fu0648u0644 product_images</li>";
    }
    
    // Add image_path if not exists
    if ($tableExists && !$hasImagePath) {
        $conn->exec("ALTER TABLE product_images ADD COLUMN image_path VARCHAR(255) NOT NULL");
        echo "<li>u062au0645u062a u0625u0636u0627u0641u0629 u0639u0645u0648u062f image_path</li>";
        
        // Copy data from image_url to image_path if both exist
        if ($hasImageUrl) {
            $conn->exec("UPDATE product_images SET image_path = image_url WHERE image_url IS NOT NULL");
            echo "<li>u062au0645 u0646u0633u062e u0628u064au0627u0646u0627u062a u0645u0646 image_url u0625u0644u0649 image_path</li>";
        }
    }
    
    // Fix foreign key if needed
    if ($tableExists && $hasForeignKey && $referencesProductsOld) {
        // Drop the foreign key constraint
        $fkName = '';
        $showCreateTable = $conn->query("SHOW CREATE TABLE product_images")->fetch()[1];
        if (preg_match('/CONSTRAINT `([^`]+)` FOREIGN KEY/', $showCreateTable, $matches)) {
            $fkName = $matches[1];
            $conn->exec("ALTER TABLE product_images DROP FOREIGN KEY `$fkName`");
            echo "<li>u062au0645 u062du0630u0641 u0627u0644u0645u0641u062au0627u062d u0627u0644u0623u062cu0646u0628u064a u0627u0644u0642u062fu064au0645 `$fkName`</li>";
            
            // Add new foreign key to products table
            $conn->exec("ALTER TABLE product_images ADD CONSTRAINT product_images_product_fk FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE");
            echo "<li>u062au0645u062a u0625u0636u0627u0641u0629 u0645u0641u062au0627u062d u0623u062cu0646u0628u064a u062cu062fu064au062f u064au0634u064au0631 u0625u0644u0649 u062cu062fu0648u0644 products</li>";
        } else {
            echo "<li>u0644u0645 u064au062au0645 u0627u0644u0639u062bu0648u0631 u0639u0644u0649 u0627u0633u0645 u0627u0644u0645u0641u062au0627u062d u0627u0644u0623u062cu0646u0628u064a</li>";
        }
    }
    
    // Recreate table if it doesn't exist or if it had serious issues
    if (!$tableExists || (empty($fkName) && $referencesProductsOld)) {
        // If table exists but we couldn't fix it, drop it and recreate
        if ($tableExists) {
            $conn->exec("DROP TABLE IF EXISTS product_images");
            echo "<li>u062au0645 u062du0630u0641 u062cu062fu0648u0644 product_images u0627u0644u0642u062fu064au0645</li>";
        }
        
        // Create new table with correct structure
        $conn->exec("CREATE TABLE product_images (
            image_id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            image_url VARCHAR(255) NOT NULL,
            image_path VARCHAR(255) NOT NULL,
            is_primary TINYINT(1) DEFAULT 0,
            sort_order INT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
        )");
        echo "<li>u062au0645 u0625u0646u0634u0627u0621 u062cu062fu0648u0644 product_images u0628u0628u0646u064au0629 u0635u062du064au062du0629</li>";
        
        // Restore data if we had a backup
        if ($tableExists) {
            $conn->exec("INSERT INTO product_images (image_id, product_id, image_url, image_path, is_primary, sort_order, created_at)
                        SELECT image_id, product_id, image_url, image_url, is_primary, sort_order, created_at
                        FROM product_images_backup
                        WHERE product_id IN (SELECT product_id FROM products)");
            echo "<li>u062au0645u062a u0627u0633u062au0639u0627u062fu0629 u0627u0644u0628u064au0627u0646u0627u062a u0645u0646 u0627u0644u0646u0633u062eu0629 u0627u0644u0627u062du062au064au0627u0637u064au0629</li>";
        }
    }
    
    echo "</ul>";
    echo "<h2>u0627u0644u0646u062au064au062cu0629</h2>";
    echo "<p>u062au0645 u0625u0635u0644u0627u062d u062cu062fu0648u0644 product_images u0628u0646u062cu0627u062d. u064au0645u0643u0646u0643 u0627u0644u0622u0646 u0627u0644u0639u0648u062fu0629 u0625u0644u0649 u0627u0644u0645u0648u0642u0639 u0648u062au062cu0631u0628u0629 u0625u0636u0627u0641u0629 u0648u062au062du0631u064au0631 u0627u0644u0645u0646u062au062cu0627u062a.</p>";
    echo "<p><a href='/Tienda' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>u0627u0644u0639u0648u062fu0629 u0625u0644u0649 u0627u0644u0645u0648u0642u0639</a></p>";
    
    // Display SQL for manual execution
    echo "<h3>u0627u0633u062au0639u0644u0627u0645u0627u062a SQL u0644u0644u062au0646u0641u064au0630 u0627u0644u064au062fu0648u064a:</h3>";
    echo "<pre style='direction: ltr; background: #f5f5f5; padding: 10px; border-radius: 5px;'>";
    echo "-- u0625u0636u0627u0641u0629 u0639u0645u0648u062f image_path u0625u0644u0649 u062cu062fu0648u0644 product_images\n";
    echo "ALTER TABLE product_images ADD COLUMN image_path VARCHAR(255) NOT NULL;\n\n";
    
    echo "-- u0646u0633u062e u0627u0644u0628u064au0627u0646u0627u062a u0645u0646 image_url u0625u0644u0649 image_path\n";
    echo "UPDATE product_images SET image_path = image_url WHERE image_url IS NOT NULL;\n\n";
    
    echo "-- u0625u0639u0627u062fu0629 u0625u0646u0634u0627u0621 u0627u0644u062cu062fu0648u0644 u0645u0646 u0627u0644u0635u0641u0631 (u0627u0633u062au062eu062fu0645 u0647u0630u0627 u0641u0642u0637 u0625u0630u0627 u0644u0645 u062au0639u0645u0644 u0627u0644u062du0644u0648u0644 u0623u0639u0644u0627u0647)\n";
    echo "CREATE TABLE product_images_new (\n";
    echo "    image_id INT AUTO_INCREMENT PRIMARY KEY,\n";
    echo "    product_id INT NOT NULL,\n";
    echo "    image_url VARCHAR(255) NOT NULL,\n";
    echo "    image_path VARCHAR(255) NOT NULL,\n";
    echo "    is_primary TINYINT(1) DEFAULT 0,\n";
    echo "    sort_order INT DEFAULT 0,\n";
    echo "    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,\n";
    echo "    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE\n";
    echo ");\n\n";
    
    echo "-- u0646u0633u062e u0627u0644u0628u064au0627u0646u0627u062a u0645u0646 u0627u0644u062cu062fu0648u0644 u0627u0644u0642u062fu064au0645 u0625u0644u0649 u0627u0644u062cu062fu064au062f\n";
    echo "INSERT INTO product_images_new (product_id, image_url, image_path, is_primary, sort_order, created_at)\n";
    echo "SELECT product_id, image_url, image_url, is_primary, sort_order, created_at FROM product_images;\n\n";
    
    echo "-- u062du0630u0641 u0627u0644u062cu062fu0648u0644 u0627u0644u0642u062fu064au0645 u0648u0625u0639u0627u062fu0629 u062au0633u0645u064au0629 u0627u0644u062cu062fu064au062f\n";
    echo "DROP TABLE product_images;\n";
    echo "RENAME TABLE product_images_new TO product_images;";
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "<div style='color: red; padding: 20px; background: #ffeeee; border-radius: 5px;'>";
    echo "<h2>u062du062fu062b u062eu0637u0623:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
