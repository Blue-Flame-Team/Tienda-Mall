<?php
// Database configuration
$host = 'localhost';
$dbname = 'tienda_mall';
$username = 'root';
$password = '';

header('Content-Type: text/html; charset=utf-8');
echo "<h1>فحص بنية قاعدة البيانات</h1>";

try {
    // Connect to database
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>جداول قاعدة البيانات</h2>";
    $tables = $conn->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li><strong>$table</strong></li>";
    }
    echo "</ul>";
    
    // Check products table structure
    echo "<h2>بنية جدول المنتجات (products)</h2>";
    $columns = $conn->query("DESCRIBE products")->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>اسم العمود</th><th>النوع</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check if product_images table exists
    $product_images_exists = in_array('product_images', $tables) || in_array('product_image', $tables);
    
    if ($product_images_exists) {
        $images_table = in_array('product_images', $tables) ? 'product_images' : 'product_image';
        
        // Check product_images table structure
        echo "<h2>بنية جدول صور المنتجات ($images_table)</h2>";
        $image_columns = $conn->query("DESCRIBE $images_table")->fetchAll(PDO::FETCH_ASSOC);
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>اسم العمود</th><th>النوع</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($image_columns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<h2>جدول صور المنتجات غير موجود!</h2>";
        echo "<p>يجب إنشاء هذا الجدول وفقاً لمخطط قاعدة البيانات.</p>";
    }
    
    // Generate SQL to fix product_image issues
    echo "<h2>استعلامات SQL لإصلاح المشاكل</h2>";
    echo "<pre style='background:#f5f5f5; padding:10px; direction:ltr;'>";
    
    // Add image_url column to products table
    echo "-- إضافة عمود image_url إلى جدول المنتجات\n";
    echo "ALTER TABLE products ADD COLUMN image_url VARCHAR(255) DEFAULT NULL;\n\n";
    
    // Create product_images table if not exists
    if (!$product_images_exists) {
        echo "-- إنشاء جدول صور المنتجات\n";
        echo "CREATE TABLE product_images (\n";
        echo "    image_id INT AUTO_INCREMENT PRIMARY KEY,\n";
        echo "    product_id INT NOT NULL,\n";
        echo "    image_url VARCHAR(255) NOT NULL,\n";
        echo "    is_main TINYINT(1) DEFAULT 0,\n";
        echo "    sort_order INT DEFAULT 0,\n";
        echo "    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,\n";
        echo "    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE\n";
        echo ");\n\n";
    }
    
    // Add other missing columns that might be used
    echo "-- إضافة الأعمدة المفقودة الأخرى\n";
    echo "ALTER TABLE products \n";
    echo "ADD COLUMN meta_title VARCHAR(255) DEFAULT NULL,\n";
    echo "ADD COLUMN meta_description TEXT DEFAULT NULL,\n";
    echo "ADD COLUMN meta_keywords VARCHAR(255) DEFAULT NULL,\n";
    echo "ADD COLUMN sale_price DECIMAL(10,2) DEFAULT NULL,\n";
    echo "ADD COLUMN is_featured TINYINT(1) DEFAULT 0,\n";
    echo "ADD COLUMN is_active TINYINT(1) DEFAULT 1,\n";
    echo "ADD COLUMN sku VARCHAR(50) DEFAULT NULL,\n";
    echo "ADD COLUMN short_description TEXT DEFAULT NULL,\n";
    echo "ADD COLUMN brand_id INT DEFAULT NULL,\n";
    echo "ADD COLUMN weight DECIMAL(10,2) DEFAULT NULL,\n";
    echo "ADD COLUMN dimensions VARCHAR(100) DEFAULT NULL;\n";
    
    echo "</pre>";
    
    // Analyze index.php to find image_url references
    echo "<h2>تحليل ملف index.php</h2>";
    if (file_exists('index.php')) {
        $index_content = file_get_contents('index.php');
        
        // Find all image_url references
        preg_match_all('/image_url/', $index_content, $matches, PREG_OFFSET_CAPTURE);
        
        echo "<p>تم العثور على " . count($matches[0]) . " إشارة إلى 'image_url' في ملف index.php</p>";
        
        // Generate replacement code for index.php
        echo "<h3>التعديلات المقترحة لملف index.php</h3>";
        echo "<p>استبدل جميع الإشارات إلى image_url بالصورة الافتراضية:</p>";
        echo "<pre style='background:#f5f5f5; padding:10px; direction:ltr;'>";
        echo htmlspecialchars("// من:\n<img src=\"<?php echo !empty(\$product['image_url']) ? \$product['image_url'] : 'assets/images/product-placeholder.png'; ?>\" alt=\"<?php echo htmlspecialchars(\$product['name']); ?>\">");
        echo "\n\n";
        echo htmlspecialchars("// إلى:\n<img src=\"assets/images/product-placeholder.png\" alt=\"<?php echo htmlspecialchars(\$product['name']); ?>\">");
        echo "</pre>";
    } else {
        echo "<p>ملف index.php غير موجود في الدليل الحالي!</p>";
    }
    
} catch (PDOException $e) {
    echo "<h2 style='color:red;'>خطأ في الاتصال بقاعدة البيانات</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
