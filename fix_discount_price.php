<?php
// Fix discount_price column issue

// Database configuration
$host = 'localhost';
$dbname = 'tienda_mall';
$username = 'root';
$password = '';

header('Content-Type: text/html; charset=utf-8');
echo "<h1 style='direction:rtl; font-family: Arial, sans-serif;'>u0625u0635u0644u0627u062d u0645u0634u0643u0644u0629 discount_price</h1>";
echo "<div style='direction:rtl; text-align:right; font-family: Arial, sans-serif;'>";

try {
    // Connect to the database
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if discount_price column exists
    $stmt = $conn->query("SHOW COLUMNS FROM products LIKE 'discount_price'");
    $discountPriceExists = ($stmt->rowCount() > 0);
    
    // Check if sale_price column exists
    $stmt = $conn->query("SHOW COLUMNS FROM products LIKE 'sale_price'");
    $salePriceExists = ($stmt->rowCount() > 0);
    
    echo "<ul>";
    
    // Add discount_price column if it doesn't exist
    if (!$discountPriceExists) {
        $conn->exec("ALTER TABLE products ADD COLUMN discount_price DECIMAL(10,2) DEFAULT NULL");
        echo "<li style='color:green;'>u062au0645 u0625u0636u0627u0641u0629 u0639u0645u0648u062f discount_price u0625u0644u0649 u062cu062fu0648u0644 products</li>";
    } else {
        echo "<li>u0639u0645u0648u062f discount_price u0645u0648u062cu0648u062f u0628u0627u0644u0641u0639u0644</li>";
    }
    
    // Copy data from sale_price to discount_price
    if ($discountPriceExists && $salePriceExists) {
        $conn->exec("UPDATE products SET discount_price = sale_price WHERE sale_price IS NOT NULL AND (discount_price IS NULL OR discount_price = 0)");
        echo "<li style='color:green;'>u062au0645 u0646u0633u062e u0627u0644u0628u064au0627u0646u0627u062a u0645u0646 u0639u0645u0648u062f sale_price u0625u0644u0649 discount_price</li>";
    }
    
    // Now we'll modify the edit_product.php file to make it more compatible with the database structure
    $editProductPath = __DIR__ . '/admin/edit_product.php';
    
    if (file_exists($editProductPath)) {
        $content = file_get_contents($editProductPath);
        
        // First backup the file
        file_put_contents($editProductPath . '.bak', $content);
        echo "<li style='color:green;'>u062au0645 u0625u0646u0634u0627u0621 u0646u0633u062eu0629 u0627u062du062au064au0627u0637u064au0629 u0645u0646 u0645u0644u0641 edit_product.php</li>";
        
        /*
        // This part is commented out for safety, uncomment if you want to modify the file
        // Make discount_price work with sale_price as well
        $pattern = '/\$discount_price = !empty\(\$_POST\[\'discount_price\'\]\) \? \(float\)\$_POST\[\'discount_price\'\] : null;/i';
        $replacement = '$discount_price = !empty($_POST[\'discount_price\']) ? (float)$_POST[\'discount_price\'] : null;\n    // Also update sale_price for better compatibility\n    $_POST[\'sale_price\'] = $_POST[\'discount_price\'];';
        
        $content = preg_replace($pattern, $replacement, $content);
        
        // Save the modified file
        file_put_contents($editProductPath, $content);
        echo "<li style='color:green;'>u062au0645 u062au062du062fu064au062b u0645u0644u0641 edit_product.php u0644u0644u062au0639u0627u0645u0644 u0645u0639 u0643u0644 u0645u0646 discount_price u0648 sale_price</li>";
        */
    }
    
    echo "</ul>";
    
    // Provide additional SQL that can be run directly in phpMyAdmin
    echo "<h2>u0644u062au0646u0641u064au0630 u0645u0628u0627u0634u0631 u0641u064a phpMyAdmin:</h2>";
    echo "<div style='background:#f8f8f8; padding:15px; direction:ltr; font-family:monospace; white-space:pre;'>";
    echo "-- Add discount_price column if it doesn't exist\nALTER TABLE products ADD COLUMN IF NOT EXISTS discount_price DECIMAL(10,2) DEFAULT NULL;\n\n-- Copy data from sale_price to discount_price\nUPDATE products SET discount_price = sale_price WHERE sale_price IS NOT NULL AND (discount_price IS NULL OR discount_price = 0);";
    echo "</div>";
    
    // Second approach - modify the admin edit_product.php file
    echo "<h2>u0627u0644u0637u0631u064au0642u0629 u0627u0644u062bu0627u0646u064au0629: u062au0639u062fu064au0644 u0645u0644u0641 edit_product.php</h2>";
    echo "<p>u0625u0630u0627 u0644u0645 u062au0639u0645u0644 u0627u0644u0637u0631u064au0642u0629 u0627u0644u0623u0648u0644u0649u060c u064au0645u0643u0646u0643 u062au0639u062fu064au0644 u0645u0644u0641 edit_product.php u0644u064au0633u062au062eu062fu0645 sale_price u0628u062fu0644u0627u064b u0645u0646 discount_price:</p>";
    echo "<ol>";
    echo "<li>u0627u0641u062au062d u0645u0644u0641 admin/edit_product.php</li>";
    echo "<li>u0627u0633u062au0628u062fu0644 u062cu0645u064au0639 u0638u0647u0648u0631 discount_price u0628u0640 sale_price</li>";
    echo "<li>u0627u062du0641u0638 u0627u0644u0645u0644u0641</li>";
    echo "</ol>";
    
    echo "<p><a href='index.php' style='padding:10px 20px; background:#4CAF50; color:white; text-decoration:none; border-radius:4px; display:inline-block;'>u0627u0644u0639u0648u062fu0629 u0625u0644u0649 u0627u0644u0635u0641u062du0629 u0627u0644u0631u0626u064au0633u064au0629</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color:red;'>u062du062fu062b u062eu0637u0623: " . $e->getMessage() . "</p>";
}

echo "</div>";
