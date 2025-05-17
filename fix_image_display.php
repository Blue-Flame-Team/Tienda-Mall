<?php
// Database configuration
$host = 'localhost';
$dbname = 'tienda_mall';
$username = 'root';
$password = '';

header('Content-Type: text/html; charset=utf-8');
echo "<h1>u0625u0635u0644u0627u062d u0645u0634u0643u0644u0629 u0639u0631u0636 u0627u0644u0635u0648u0631</h1>";
echo "<style>body { font-family: Arial, sans-serif; direction: rtl; padding: 20px; }</style>";

try {
    // Connect to database
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>u0641u062du0635 u0645u0633u0627u0631u0627u062a u0627u0644u0635u0648u0631 u0641u064a u0642u0627u0639u062fu0629 u0627u0644u0628u064au0627u0646u0627u062a</h2>";
    
    // Check image paths
    $images = $conn->query("SELECT * FROM product_images LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
    echo "<tr><th>u0631u0642u0645 u0627u0644u0635u0648u0631u0629</th><th>u0631u0642u0645 u0627u0644u0645u0646u062au062c</th><th>u0627u0644u0645u0633u0627u0631 u0627u0644u062du0627u0644u064a</th><th>u0627u0644u0645u0633u0627u0631 u0627u0644u062cu062fu064au062f</th><th>u0648u062cu0648u062f u0627u0644u0645u0644u0641</th><th>u0645u0639u0627u064au0646u0629</th></tr>";
    
    $pathsToFix = 0;
    foreach ($images as $image) {
        $currentPath = $image['image_path'] ?? $image['image_url'] ?? '';
        $newPath = '';
        
        // Fix paths starting with ../
        if (strpos($currentPath, '../') === 0) {
            $newPath = substr($currentPath, 3); // Remove the ../
            $pathsToFix++;
        }
        // Paths might be already correct
        else {
            $newPath = $currentPath;
        }
        
        // Check if file exists
        $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/Tienda/' . ltrim($newPath, '/');
        $fileExists = file_exists($fullPath);
        
        echo "<tr>";
        echo "<td>{$image['image_id']}</td>";
        echo "<td>{$image['product_id']}</td>";
        echo "<td>$currentPath</td>";
        echo "<td>$newPath</td>";
        echo "<td>" . ($fileExists ? '<span style="color:green;">u2713</span>' : '<span style="color:red;">u2717</span>') . "</td>";
        echo "<td>" . ($fileExists ? "<img src='/$newPath' height='50'>" : "u0644u0627 u062au0648u062cu062f") . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    if ($pathsToFix > 0) {
        echo "<h3>u0625u0635u0644u0627u062d u0627u0644u0645u0633u0627u0631u0627u062a</h3>";
        echo "<p>u064au0648u062cu062f $pathsToFix u0645u0633u0627u0631u0627u062a u062au062du062au0627u062c u0625u0644u0649 u0625u0635u0644u0627u062d.</p>";
        
        if (isset($_GET['fix']) && $_GET['fix'] == '1') {
            // Update the paths in database
            $conn->exec("UPDATE product_images SET image_path = SUBSTRING(image_path, 4) WHERE image_path LIKE '../%'");
            
            // Try to modify index.php to fix image display
            $indexFile = $_SERVER['DOCUMENT_ROOT'] . '/Tienda/index.php';
            if (file_exists($indexFile)) {
                $content = file_get_contents($indexFile);
                
                // Update image display in index.php
                $modified = str_replace(
                    '<img src="<?php echo !empty($product[\'primary_image\']) ? htmlspecialchars($product[\'primary_image\']) : \'assets/images/product-placeholder.png\'; ?>"',
                    '<img src="<?php echo !empty($product[\'primary_image\']) ? (strpos($product[\'primary_image\'], \'../\') === 0 ? htmlspecialchars(substr($product[\'primary_image\'], 3)) : htmlspecialchars($product[\'primary_image\'])) : \'assets/images/product-placeholder.png\'; ?>"',
                    $content
                );
                
                if ($content != $modified) {
                    file_put_contents($indexFile, $modified);
                    echo "<p style='color:green;'>u2713 u062au0645 u062au062du062fu064au062b index.php u0644u0644u062au0639u0627u0645u0644 u0645u0639 u0627u0644u0645u0633u0627u0631u0627u062a u0627u0644u0646u0633u0628u064au0629</p>";
                }
            }
            
            echo "<p style='color:green;'>u2713 u062au0645 u0625u0635u0644u0627u062d $pathsToFix u0645u0633u0627u0631 u0641u064a u0642u0627u0639u062fu0629 u0627u0644u0628u064au0627u0646u0627u062a</p>";
            echo "<p><a href='fix_image_display.php'>u0625u0639u0627u062fu0629 u0627u0644u0641u062du0635</a></p>";
        } else {
            echo "<p><a href='fix_image_display.php?fix=1' style='background: #DB4444; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>u0625u0635u0644u0627u062d u0627u0644u0645u0633u0627u0631u0627u062a</a></p>";
        }
    } else {
        echo "<p>u0644u0627 u062au0648u062cu062f u0645u0633u0627u0631u0627u062a u062au062du062au0627u062c u0625u0644u0649 u0625u0635u0644u0627u062d.</p>";
    }
    
    // Create a test image display
    echo "<h2>u0627u062eu062au0628u0627u0631 u0639u0631u0636 u0627u0644u0635u0648u0631</h2>";
    echo "<div style='background:#f5f5f5; padding:15px; margin-top:20px;'>";
    
    // Get products with images
    $products = $conn->query(
        "SELECT p.*, 
          (SELECT image_path FROM product_images WHERE product_id = p.product_id AND is_primary = 1 LIMIT 1) as primary_image 
          FROM products p 
          WHERE p.is_active = 1 
          LIMIT 4"
    )->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($products) > 0) {
        echo "<div style='display:flex; flex-wrap:wrap; gap:20px;'>";
        foreach ($products as $product) {
            echo "<div style='border:1px solid #ddd; padding:10px; border-radius:5px; width:200px;'>";
            echo "<h3>{$product['name']}</h3>";
            
            $imagePath = !empty($product['primary_image']) ? $product['primary_image'] : 'assets/images/product-placeholder.png';
            
            // Remove ../ if present
            if (strpos($imagePath, '../') === 0) {
                $imagePath = substr($imagePath, 3);
            }
            
            echo "<p>u0645u0633u0627u0631 u0627u0644u0635u0648u0631u0629: $imagePath</p>";
            echo "<img src='/$imagePath' style='max-width:100%; height:150px; object-fit:contain;'>";
            echo "</div>";
        }
        echo "</div>";
    } else {
        echo "<p>u0644u0627 u062au0648u062cu062f u0645u0646u062au062cu0627u062a u0644u0639u0631u0636u0647u0627.</p>";
    }
    
    echo "</div>";
    
    // Provide more advanced solutions
    echo "<h2>u062du0644u0648u0644 u0625u0636u0627u0641u064au0629</h2>";
    echo "<ol>";
    echo "<li>u062au0639u062fu064au0644 u0645u0644u0641 <code>admin/edit_product.php</code> u0644u062du0641u0638 u0645u0633u0627u0631u0627u062a u0635u062du064au062du0629 u0645u0646 u0627u0644u0628u062fu0627u064au0629</li>";
    echo "<li>u062au0639u062fu064au0644 u062cu0645u064au0639 u0627u0644u0645u0633u0627u0631u0627u062a u0641u064a u0642u0627u0639u062fu0629 u0627u0644u0628u064au0627u0646u0627u062a u0644u0625u0632u0627u0644u0629 '../' u0645u0646 u0628u062fu0627u064au0629 u0627u0644u0645u0633u0627u0631</li>";
    echo "<li>u0625u0636u0627u0641u0629 u0634u064au0641u0631u0629 PHP u0641u064a index.php u0644u0645u0639u0627u0644u062cu0629 u0627u0644u0645u0633u0627u0631u0627u062a u0627u0644u0646u0633u0628u064au0629 u062au0644u0642u0627u0626u064au0627u064b</li>";
    echo "</ol>";
    
    echo "<p><a href='/Tienda/index.php' style='background: #DB4444; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display:inline-block; margin-top:20px;'>u0627u0644u0639u0648u062fu0629 u0625u0644u0649 u0627u0644u0635u0641u062du0629 u0627u0644u0631u0626u064au0633u064au0629</a></p>";
    
} catch (PDOException $e) {
    echo "<div style='color:red;'>";
    echo "<h2>u062eu0637u0623 u0641u064a u0627u0644u0627u062au0635u0627u0644 u0628u0642u0627u0639u062fu0629 u0627u0644u0628u064au0627u0646u0627u062a</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
