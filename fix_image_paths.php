<?php
// Database configuration
$host = 'localhost';
$dbname = 'tienda_mall';
$username = 'root';
$password = '';

header('Content-Type: text/html; charset=utf-8');
echo "<h1>u0641u062du0635 u0648u0625u0635u0644u0627u062d u0645u0633u0627u0631u0627u062a u0627u0644u0635u0648u0631</h1>";
echo "<style>body { font-family: Arial, sans-serif; direction: rtl; padding: 20px; }</style>";

try {
    // Connect to database
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check product_images structure
    $hasImagePath = $conn->query("SHOW COLUMNS FROM product_images LIKE 'image_path'")->rowCount() > 0;
    $hasImageUrl = $conn->query("SHOW COLUMNS FROM product_images LIKE 'image_url'")->rowCount() > 0;
    $hasIsPrimary = $conn->query("SHOW COLUMNS FROM product_images LIKE 'is_primary'")->rowCount() > 0;
    $hasIsMain = $conn->query("SHOW COLUMNS FROM product_images LIKE 'is_main'")->rowCount() > 0;
    
    // Check existing images
    $imagesCount = $conn->query("SELECT COUNT(*) FROM product_images")->fetchColumn();
    $primaryImagesCount = $hasIsPrimary ? 
        $conn->query("SELECT COUNT(*) FROM product_images WHERE is_primary = 1")->fetchColumn() : 0;
    
    echo "<h2>u0645u0639u0644u0648u0645u0627u062a u0639u0646 u062cu062fu0648u0644 u0627u0644u0635u0648u0631</h2>";
    echo "<ul>";
    echo "<li>u0639u062fu062f u0627u0644u0635u0648u0631 u0627u0644u0643u0644u064a: $imagesCount</li>";
    echo "<li>u0639u062fu062f u0627u0644u0635u0648u0631 u0627u0644u0631u0626u064au0633u064au0629: $primaryImagesCount</li>";
    echo "</ul>";
    
    $changes = false;
    
    // Add is_primary column if missing
    if (!$hasIsPrimary && $imagesCount > 0) {
        $conn->exec("ALTER TABLE product_images ADD COLUMN is_primary TINYINT(1) DEFAULT 0");
        echo "<p style='color:green;'>u2713 u062au0645u062a u0625u0636u0627u0641u0629 u0639u0645u0648u062f is_primary</p>";
        $hasIsPrimary = true;
        $changes = true;
    }
    
    // Fix primary images if none exist
    if ($hasIsPrimary && $primaryImagesCount == 0 && $imagesCount > 0) {
        $conn->exec("UPDATE product_images p1
                    JOIN (SELECT MIN(image_id) as first_image_id, product_id FROM product_images GROUP BY product_id) p2 
                    ON p1.image_id = p2.first_image_id 
                    SET p1.is_primary = 1" . ($hasIsMain ? ", p1.is_main = 1" : ""));
        
        $updatedCount = $conn->query("SELECT COUNT(*) FROM product_images WHERE is_primary = 1")->fetchColumn();
        echo "<p style='color:green;'>u2713 u062au0645 u062au0639u064au064au0646 $updatedCount u0635u0648u0631u0629 u0643u0635u0648u0631 u0631u0626u064au0633u064au0629</p>";
        $changes = true;
    }
    
    // Add image_path if missing but image_url exists
    if (!$hasImagePath && $hasImageUrl) {
        $conn->exec("ALTER TABLE product_images ADD COLUMN image_path VARCHAR(255) NOT NULL");
        $conn->exec("UPDATE product_images SET image_path = image_url WHERE image_url IS NOT NULL");
        echo "<p style='color:green;'>u2713 u062au0645u062a u0625u0636u0627u0641u0629 u0639u0645u0648u062f image_path u0648u0646u0633u062e u0627u0644u0628u064au0627u0646u0627u062a u0645u0646 image_url</p>";
        $hasImagePath = true;
        $changes = true;
    }
    
    // Check image paths and fix paths if needed
    if ($hasImagePath && $imagesCount > 0) {
        $images = $conn->query("SELECT * FROM product_images LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
        $pathFixed = false;
        
        echo "<h2>u0639u064au0646u0629 u0645u0646 u0645u0633u0627u0631u0627u062a u0627u0644u0635u0648u0631</h2>";
        echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
        echo "<tr><th>u0631u0642u0645 u0627u0644u0635u0648u0631u0629</th><th>u0631u0642u0645 u0627u0644u0645u0646u062au062c</th><th>u0627u0644u0645u0633u0627u0631</th><th>u0647u0644 u0627u0644u0645u0644u0641 u0645u0648u062cu0648u062f</th></tr>";
        
        foreach ($images as $image) {
            $path = $image['image_path'] ?? $image['image_url'] ?? '';
            $filePath = $_SERVER['DOCUMENT_ROOT'] . '/Tienda/' . ltrim($path, '/');
            $fileExists = file_exists($filePath);
            
            echo "<tr>";
            echo "<td>{$image['image_id']}</td>";
            echo "<td>{$image['product_id']}</td>";
            echo "<td>$path</td>";
            echo "<td>" . ($fileExists ? '<span style="color:green;">✓ موجود</span>' : '<span style="color:red;">✗ غير موجود</span>') . "</td>";
            echo "</tr>";
            
            // Check if path needs fixing
            if (!$fileExists && !empty($path)) {
                // Look for upload patterns
                if (strpos($path, 'upload') !== false || strpos($path, 'images') !== false) {
                    $pathFixed = true;
                }
            }
        }
        echo "</table>";
        
        // Fix problem with index.php query
        echo "<h2>u0625u0635u0644u0627u062d u0627u0633u062au0639u0644u0627u0645 u0627u0644u0635u0641u062du0629 u0627u0644u0631u0626u064au0633u064au0629</h2>";
        
        // Replace the problem query in index.php
        $indexPath = $_SERVER['DOCUMENT_ROOT'] . '/Tienda/index.php';
        if (file_exists($indexPath)) {
            $indexContent = file_get_contents($indexPath);
            $updated = false;
            
            // Fix subquery to use correct is_primary condition
            $oldPattern = "/(SELECT image_path FROM product_images WHERE product_id = p\.product_id AND is_primary = 1 LIMIT 1)/i";
            $newQuery = "(SELECT image_path FROM product_images WHERE product_id = p.product_id" . 
                      ($hasIsPrimary ? " AND is_primary = 1" : "") . 
                      " LIMIT 1)";
            
            $newContent = preg_replace($oldPattern, $newQuery, $indexContent, -1, $count);
            
            if ($count > 0) {
                file_put_contents($indexPath, $newContent);
                echo "<p style='color:green;'>u2713 u062au0645 u062au062du062fu064au062b u0627u0633u062au0639u0644u0627u0645 u0627u0644u0635u0648u0631 u0641u064a index.php ($count u062au063au064au064au0631u0627u062a)</p>";
                $updated = true;
                $changes = true;
            }
            
            // Update placeholder condition to check if primary_image is null or empty
            $imgPattern = "/(<img src=\\"<\?php echo !empty\(\$product\['primary_image'\]\) \? htmlspecialchars\(\$product\['primary_image'\]\) : 'assets\/images\/product-placeholder\.png'; \?>\\")/";
            $newImgTag = "<img src=\"<?php echo !empty(\$product['primary_image']) ? htmlspecialchars(\$product['primary_image']) : 'assets/images/product-placeholder.png'; ?>\"";
            
            if (!$updated) {
                $newContent = preg_replace($imgPattern, $newImgTag, $indexContent, -1, $count);
                if ($count > 0) {
                    file_put_contents($indexPath, $newContent);
                    echo "<p style='color:green;'>u2713 u062au0645 u062au062du062fu064au062b u0634u0631u0637 u0639u0631u0636 u0627u0644u0635u0648u0631 u0641u064a index.php</p>";
                    $changes = true;
                }
            }
            
            // Add debug code at the end of the file
            if (!strpos($indexContent, 'debug=')) {
                $debugCode = "\n<?php if (isset($_GET['debug'])): ?>\n" .
                             "<div style=\"direction:ltr; text-align:left; background:#f5f5f5; padding:15px; margin:20px; border-radius:5px;\">\n" .
                             "    <h3>Debug Information</h3>\n" .
                             "    <h4>Flash Sale Products:</h4>\n" .
                             "    <pre><?php print_r($flashSaleProducts); ?></pre>\n" .
                             "    \n" .
                             "    <h4>Best Selling Products:</h4>\n" .
                             "    <pre><?php print_r($bestSellingProducts); ?></pre>\n" .
                             "    \n" .
                             "    <h4>All Products:</h4>\n" .
                             "    <pre><?php print_r($allProducts); ?></pre>\n" .
                             "</div>\n" .
                             "<?php endif; ?>\n";
                
                // Add before the closing PHP tag or at the end
                if (strrpos($indexContent, '?>') !== false) {
                    $newContent = str_replace('?>', $debugCode . '?>', $indexContent);
                    file_put_contents($indexPath, $newContent);
                    echo "<p style='color:green;'>u2713 u062au0645u062a u0625u0636u0627u0641u0629 u0648u0636u0639 u0627u0644u062au0635u062du064au062d u0625u0644u0649 index.php</p>";
                    $changes = true;
                } else {
                    file_put_contents($indexPath, $indexContent . $debugCode);
                    echo "<p style='color:green;'>u2713 u062au0645u062a u0625u0636u0627u0641u0629 u0648u0636u0639 u0627u0644u062au0635u062du064au062d u0625u0644u0649 index.php</p>";
                    $changes = true;
                }
            }
            
        } else {
            echo "<p style='color:red;'>u2717 u0644u0645 u064au062au0645 u0627u0644u0639u062bu0648u0631 u0639u0644u0649 u0645u0644u0641 index.php!</p>";
        }
    }
    
    if ($changes) {
        echo "<h2>u062au0645 u0627u0644u0625u0635u0644u0627u062d u0628u0646u062cu0627u062d!</h2>";
        echo "<p>u064au0631u062cu0649 u0627u0644u0639u0648u062fu0629 u0625u0644u0649 u0627u0644u0635u0641u062du0629 u0627u0644u0631u0626u064au0633u064au0629 u0648u062au062du062fu064au062bu0647u0627 u0644u0645u0634u0627u0647u062fu0629 u0627u0644u062au063au064au064au0631u0627u062a.</p>";
        echo "<p><a href='/Tienda/index.php' style='background: #DB4444; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>u0627u0644u0639u0648u062fu0629 u0625u0644u0649 u0627u0644u0635u0641u062du0629 u0627u0644u0631u0626u064au0633u064au0629</a></p>";
        echo "<p><a href='/Tienda/index.php?debug=1' style='background: #4444DB; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>u0639u0631u0636 u0645u0639u0644u0648u0645u0627u062a u0627u0644u062au0635u062du064au062d</a></p>";
    } else {
        echo "<h2>u0644u0645 u064au062au0645 u0625u062cu0631u0627u0621 u0623u064a u062au063au064au064au0631u0627u062a</h2>";
        echo "<p>u0644u0645 u064au062au0645 u0627u0644u0639u062bu0648u0631 u0639u0644u0649 u0623u064a u0645u0634u0627u0643u0644 u062au062du062au0627u062c u0625u0644u0649 u0625u0635u0644u0627u062d u0623u0648 u062au0645 u0625u0635u0644u0627u062du0647u0627 u0633u0627u0628u0642u0627u064b.</p>";
    }
    
    // Check if uploads folder exists
    $uploadsDir = $_SERVER['DOCUMENT_ROOT'] . '/Tienda/uploads/products';
    if (!file_exists($uploadsDir)) {
        mkdir($uploadsDir, 0755, true);
        echo "<p style='color:green;'>u2713 u062au0645 u0625u0646u0634u0627u0621 u0645u062cu0644u062f uploads/products</p>";
    }
    
    // Ensure placeholder image exists
    $placeholderFile = $_SERVER['DOCUMENT_ROOT'] . '/Tienda/assets/images/product-placeholder.png';
    if (!file_exists($placeholderFile)) {
        $assetsDir = $_SERVER['DOCUMENT_ROOT'] . '/Tienda/assets/images';
        if (!file_exists($assetsDir)) {
            mkdir($assetsDir, 0755, true);
        }
        
        // Create a simple placeholder image
        $img = imagecreatetruecolor(200, 200);
        $bgColor = imagecolorallocate($img, 240, 240, 240);
        $textColor = imagecolorallocate($img, 100, 100, 100);
        imagefilledrectangle($img, 0, 0, 199, 199, $bgColor);
        imagestring($img, 5, 40, 90, 'Product Image', $textColor);
        imagepng($img, $placeholderFile);
        imagedestroy($img);
        
        echo "<p style='color:green;'>u2713 u062au0645 u0625u0646u0634u0627u0621 u0635u0648u0631u0629 product-placeholder.png</p>";
    }
    
} catch (PDOException $e) {
    echo "<div style='color:red;'>";
    echo "<h2>u062eu0637u0623 u0641u064a u0627u0644u0627u062au0635u0627u0644 u0628u0642u0627u0639u062fu0629 u0627u0644u0628u064au0627u0646u0627u062a</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
