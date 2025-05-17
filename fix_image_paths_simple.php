<?php
// Database configuration
$host = 'localhost';
$dbname = 'tienda_mall';
$username = 'root';
$password = '';

header('Content-Type: text/html; charset=utf-8');
echo "<h1>u0625u0635u0644u0627u062d u0645u0633u0627u0631u0627u062a u0627u0644u0635u0648u0631</h1>";
echo "<style>body { font-family: Arial, sans-serif; direction: rtl; padding: 20px; }</style>";

try {
    // Connect to database
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>u0641u062du0635 u062cu062fu0648u0644 u0627u0644u0635u0648u0631</h2>";
    $tableExists = $conn->query("SHOW TABLES LIKE 'product_images'")->rowCount() > 0;
    
    if ($tableExists) {
        echo "<p style='color:green;'>u2713 u062cu062fu0648u0644 product_images u0645u0648u062cu0648u062f</p>";
        
        // Check columns
        $hasImagePath = $conn->query("SHOW COLUMNS FROM product_images LIKE 'image_path'")->rowCount() > 0;
        $hasIsPrimary = $conn->query("SHOW COLUMNS FROM product_images LIKE 'is_primary'")->rowCount() > 0;
        
        // Add missing columns if needed
        if (!$hasImagePath) {
            $conn->exec("ALTER TABLE product_images ADD COLUMN image_path VARCHAR(255) DEFAULT ''");
            $conn->exec("UPDATE product_images SET image_path = image_url WHERE image_url IS NOT NULL");
            echo "<p style='color:green;'>u2713 u062au0645u062a u0625u0636u0627u0641u0629 u0639u0645u0648u062f image_path</p>";
            $hasImagePath = true;
        }
        
        if (!$hasIsPrimary) {
            $conn->exec("ALTER TABLE product_images ADD COLUMN is_primary TINYINT(1) DEFAULT 0");
            echo "<p style='color:green;'>u2713 u062au0645u062a u0625u0636u0627u0641u0629 u0639u0645u0648u062f is_primary</p>";
            $hasIsPrimary = true;
        }
        
        // Set primary images if none exist
        $primaryCount = $conn->query("SELECT COUNT(*) FROM product_images WHERE is_primary = 1")->fetchColumn();
        if ($primaryCount == 0) {
            $conn->exec("UPDATE product_images p1
                        JOIN (SELECT MIN(image_id) as first_image_id, product_id FROM product_images GROUP BY product_id) p2
                        ON p1.image_id = p2.first_image_id
                        SET p1.is_primary = 1");
            
            $newPrimaryCount = $conn->query("SELECT COUNT(*) FROM product_images WHERE is_primary = 1")->fetchColumn();
            echo "<p style='color:green;'>u2713 u062au0645 u062au0639u064au064au0646 $newPrimaryCount u0635u0648u0631u0629 u0643u0635u0648u0631 u0631u0626u064au0633u064au0629</p>";
        }
        
        // Fix index.php query to use the right columns
        $indexFile = $_SERVER['DOCUMENT_ROOT'] . '/Tienda/index.php';
        if (file_exists($indexFile)) {
            $content = file_get_contents($indexFile);
            $modified = false;
            
            // Replace image_url usage with product-placeholder.png
            if (strpos($content, '<img src="assets/images/product-placeholder.png"') !== false) {
                $newContent = str_replace(
                    '<img src="assets/images/product-placeholder.png"',
                    '<img src="<?php echo !empty($product[\'primary_image\']) && file_exists($_SERVER[\'DOCUMENT_ROOT\'].\"/Tienda/\".$product[\'primary_image\']) ? $product[\'primary_image\'] : \'assets/images/product-placeholder.png\'; ?>"',
                    $content
                );
                
                if ($content != $newContent) {
                    file_put_contents($indexFile, $newContent);
                    echo "<p style='color:green;'>u2713 u062au0645 u062au062du062fu064au062b index.php u0644u0627u0633u062au062eu062fu0627u0645 u0645u0633u0627u0631u0627u062a u0627u0644u0635u0648u0631 u0627u0644u0635u062du064au062du0629</p>";
                    $modified = true;
                }
            }
            
            // Make sure the query includes is_primary condition
            if (strpos($content, 'SELECT image_path FROM product_images WHERE product_id = p.product_id') !== false) {
                $fixedQuery = str_replace(
                    'SELECT image_path FROM product_images WHERE product_id = p.product_id',
                    'SELECT image_path FROM product_images WHERE product_id = p.product_id AND is_primary = 1',
                    $content
                );
                
                if ($content != $fixedQuery) {
                    file_put_contents($indexFile, $fixedQuery);
                    echo "<p style='color:green;'>u2713 u062au0645 u062au062du062fu064au062b u0627u0633u062au0639u0644u0627u0645 u0627u0644u0635u0648u0631 u0641u064a index.php</p>";
                    $modified = true;
                }
            }
            
            if (!$modified) {
                echo "<p>u0644u0645 u064au062au0645 u0627u0644u0639u062bu0648u0631 u0639u0644u0649 u0623u064a u0623u0646u0645u0627u0637 u0642u064au0627u0633u064au0629 u0644u0644u062au062du062fu064au062b u0641u064a index.php</p>";
            }
        } else {
            echo "<p style='color:red;'>u2717 u0645u0644u0641 index.php u063au064au0631 u0645u0648u062cu0648u062f!</p>";
        }
        
    } else {
        echo "<p style='color:red;'>u2717 u062cu062fu0648u0644 product_images u063au064au0631 u0645u0648u062cu0648u062f!</p>";
        
        // Create the table if it doesn't exist
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
        
        echo "<p style='color:green;'>u2713 u062au0645 u0625u0646u0634u0627u0621 u062cu062fu0648u0644 product_images</p>";
    }
    
    // Make sure upload directories exist
    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/Tienda/uploads/products';
    if (!file_exists($uploadDir)) {
        if (!is_dir($_SERVER['DOCUMENT_ROOT'] . '/Tienda/uploads')) {
            mkdir($_SERVER['DOCUMENT_ROOT'] . '/Tienda/uploads', 0755);
        }
        mkdir($uploadDir, 0755);
        echo "<p style='color:green;'>u2713 u062au0645 u0625u0646u0634u0627u0621 u0645u062cu0644u062f uploads/products</p>";
    }
    
    // Make sure assets directory exists
    $assetsDir = $_SERVER['DOCUMENT_ROOT'] . '/Tienda/assets/images';
    if (!file_exists($assetsDir)) {
        if (!is_dir($_SERVER['DOCUMENT_ROOT'] . '/Tienda/assets')) {
            mkdir($_SERVER['DOCUMENT_ROOT'] . '/Tienda/assets', 0755);
        }
        mkdir($assetsDir, 0755);
        echo "<p style='color:green;'>u2713 u062au0645 u0625u0646u0634u0627u0621 u0645u062cu0644u062f assets/images</p>";
    }
    
    // Create placeholder image if it doesn't exist
    $placeholderFile = $assetsDir . '/product-placeholder.png';
    if (!file_exists($placeholderFile)) {
        $img = imagecreatetruecolor(200, 200);
        $bgColor = imagecolorallocate($img, 240, 240, 240);
        $textColor = imagecolorallocate($img, 100, 100, 100);
        imagefilledrectangle($img, 0, 0, 199, 199, $bgColor);
        imagestring($img, 5, 40, 90, 'Product Image', $textColor);
        imagepng($img, $placeholderFile);
        imagedestroy($img);
        
        echo "<p style='color:green;'>u2713 u062au0645 u0625u0646u0634u0627u0621 u0635u0648u0631u0629 product-placeholder.png</p>";
    }
    
    // Sample image data in database
    echo "<h2>u0639u064au0646u0629 u0645u0646 u0645u0633u0627u0631u0627u062a u0627u0644u0635u0648u0631</h2>";
    $images = $conn->query("SELECT * FROM product_images LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($images) > 0) {
        echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
        echo "<tr>";
        foreach (array_keys($images[0]) as $key) {
            echo "<th>$key</th>";
        }
        echo "</tr>";
        
        foreach ($images as $image) {
            echo "<tr>";
            foreach ($image as $value) {
                echo "<td>" . htmlspecialchars($value) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>u0644u0627 u062au0648u062cu062f u0628u064au0627u0646u0627u062a u0635u0648u0631 u0641u064a u0642u0627u0639u062fu0629 u0627u0644u0628u064au0627u0646u0627u062a.</p>";
    }
    
    echo "<h2>u0627u0644u062eu0637u0648u0627u062a u0627u0644u062au0627u0644u064au0629</h2>";
    echo "<ol>";
    echo "<li>u0627u0644u0639u0648u062fu0629 u0625u0644u0649 u0627u0644u0635u0641u062du0629 u0627u0644u0631u0626u064au0633u064au0629 u0648u062au062du062fu064au062bu0647u0627</li>";
    echo "<li>u0625u0630u0627 u0627u0633u062au0645u0631u062a u0627u0644u0645u0634u0643u0644u0629u060c u062au062du0642u0642 u0645u0646 u0645u0644u0641 <code>admin/edit_product.php</code> u0644u0644u062au0623u0643u062f u0645u0646 u0623u0646 u0639u0645u0644u064au0629 u0631u0641u0639 u0627u0644u0635u0648u0631 u062au0639u0645u0644 u0628u0634u0643u0644 u0635u062du064au062d</li>";
    echo "<li>u062au0623u0643u062f u0645u0646 u0623u0646 u0627u0644u0635u0648u0631 u0627u0644u0645u0631u0641u0648u0639u0629 u062au062du0641u0638 u0641u064a u0645u062cu0644u062f <code>uploads/products</code></li>";
    echo "</ol>";
    
    echo "<p><a href='/Tienda/index.php' style='background: #DB4444; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>u0627u0644u0639u0648u062fu0629 u0625u0644u0649 u0627u0644u0635u0641u062du0629 u0627u0644u0631u0626u064au0633u064au0629</a></p>";
    
} catch (PDOException $e) {
    echo "<div style='color:red;'>";
    echo "<h2>u062eu0637u0623 u0641u064a u0627u0644u0627u062au0635u0627u0644 u0628u0642u0627u0639u062fu0629 u0627u0644u0628u064au0627u0646u0627u062a</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
