<?php
// Database configuration
$host = 'localhost';
$dbname = 'tienda_mall';
$username = 'root';
$password = '';

header('Content-Type: text/html; charset=utf-8');
echo "<h1>فحص مشكلة الصور</h1>";
echo "<style>body { font-family: Arial, sans-serif; direction: rtl; padding: 20px; }</style>";

try {
    // Connect to database
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>1. التحقق من جدول product_images</h2>";
    
    // Check if table exists
    $tableExists = $conn->query("SHOW TABLES LIKE 'product_images'")->rowCount() > 0;
    if ($tableExists) {
        echo "<p style='color:green;'>✓ جدول product_images موجود</p>";
        
        // Check structure
        $columns = $conn->query("SHOW COLUMNS FROM product_images")->fetchAll(PDO::FETCH_COLUMN);
        echo "<p>الأعمدة الموجودة: " . implode(", ", $columns) . "</p>";
        
        // Check if necessary columns exist
        $hasImagePath = in_array('image_path', $columns);
        $hasImageUrl = in_array('image_url', $columns);
        $hasIsPrimary = in_array('is_primary', $columns);
        $hasIsMain = in_array('is_main', $columns);
        
        if ($hasImagePath) {
            echo "<p style='color:green;'>✓ عمود image_path موجود</p>";
        } else {
            echo "<p style='color:red;'>✗ عمود image_path غير موجود</p>";
        }
        
        if ($hasIsPrimary) {
            echo "<p style='color:green;'>✓ عمود is_primary موجود</p>";
        } else {
            echo "<p style='color:red;'>✗ عمود is_primary غير موجود</p>";
        }
        
        // Check data
        $count = $conn->query("SELECT COUNT(*) FROM product_images")->fetchColumn();
        echo "<p>عدد الصور: $count</p>";
        
        if ($count > 0) {
            // Sample data
            echo "<h3>عينة من البيانات:</h3>";
            $images = $conn->query("SELECT * FROM product_images LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
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
            
            // Check primary images
            $primary = $conn->query("SELECT COUNT(*) FROM product_images WHERE " . 
                                   ($hasIsPrimary ? "is_primary = 1" : "1=0"))->fetchColumn();
            echo "<p>عدد الصور الرئيسية (is_primary = 1): $primary</p>";
            
            // Physical files exist
            if ($hasImagePath || $hasImageUrl) {
                echo "<h3>التحقق من وجود الملفات الفعلية:</h3>";
                $paths = $conn->query("SELECT " . 
                                     ($hasImagePath ? "image_path" : "image_url") . 
                                     " FROM product_images LIMIT 5")->fetchAll(PDO::FETCH_COLUMN);
                
                echo "<ul>";
                foreach ($paths as $path) {
                    if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($path, '/'))) {
                        echo "<li style='color:green;'>✓ $path (موجود)</li>";
                    } else {
                        echo "<li style='color:red;'>✗ $path (غير موجود)</li>";
                    }
                }
                echo "</ul>";
            }
        } else {
            echo "<p style='color:red;'>لا توجد صور في الجدول!</p>";
        }
    } else {
        echo "<p style='color:red;'>✗ جدول product_images غير موجود!</p>";
    }
    
    echo "<h2>2. التحقق من استعلام الصفحة الرئيسية</h2>";
    
    // Test the query from index.php
    $testQuery = "SELECT p.*, 
                 (SELECT image_path FROM product_images WHERE product_id = p.product_id AND is_primary = 1 LIMIT 1) as primary_image
                 FROM products p 
                 WHERE p.is_active = 1 
                 LIMIT 4";
    
    try {
        $stmt = $conn->prepare($testQuery);
        $stmt->execute();
        $testProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p style='color:green;'>✓ الاستعلام يعمل بدون أخطاء</p>";
        
        // Show products with image paths
        echo "<h3>المنتجات المسترجعة:</h3>";
        echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
        echo "<tr><th>المنتج</th><th>مسار الصورة</th><th>معاينة الصورة</th></tr>";
        
        foreach ($testProducts as $product) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($product['name']) . "</td>";
            echo "<td>" . (isset($product['primary_image']) ? htmlspecialchars($product['primary_image']) : 'لا يوجد') . "</td>";
            echo "<td>";
            if (!empty($product['primary_image'])) {
                echo "<img src='" . htmlspecialchars($product['primary_image']) . "' style='max-width:100px; max-height:100px;'>";
            } else {
                echo "لا توجد صورة";
            }
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } catch (PDOException $e) {
        echo "<p style='color:red;'>✗ خطأ في الاستعلام: " . $e->getMessage() . "</p>";
    }
    
    echo "<h2>3. الحلول المقترحة</h2>";
    echo "<div style='background:#f5f5f5; padding:15px; border-radius:5px;'>";
    
    // Create sample images in assets folder
    $assetsDir = $_SERVER['DOCUMENT_ROOT'] . '/Tienda/assets/images';
    if (!file_exists($assetsDir)) {
        @mkdir($assetsDir, 0755, true);
        echo "<p>تم إنشاء مجلد assets/images</p>";
    }
    
    $placeholderPath = $assetsDir . '/product-placeholder.png';
    if (!file_exists($placeholderPath)) {
        // Create a simple placeholder
        $placeholder = imagecreatetruecolor(200, 200);
        $bgColor = imagecolorallocate($placeholder, 240, 240, 240);
        $textColor = imagecolorallocate($placeholder, 100, 100, 100);
        imagefilledrectangle($placeholder, 0, 0, 199, 199, $bgColor);
        imagestring($placeholder, 5, 30, 90, 'Product Image', $textColor);
        imagepng($placeholder, $placeholderPath);
        imagedestroy($placeholder);
        echo "<p>تم إنشاء صورة product-placeholder.png</p>";
    }
    
    // Solution 1: Fix is_primary
    if ($tableExists && $count > 0 && $primary == 0) {
        echo "<h3>1. تعيين الصور الرئيسية للمنتجات</h3>";
        echo "<pre>
UPDATE product_images p1
JOIN (
    SELECT MIN(image_id) as first_image_id, product_id
    FROM product_images
    GROUP BY product_id
) p2 ON p1.image_id = p2.first_image_id
SET p1.is_primary = 1" . 
    ($hasIsMain ? ", p1.is_main = 1" : "") .
";</pre>";
    }
    
    // Solution 2: Add image_path
    if ($tableExists && !$hasImagePath && $hasImageUrl) {
        echo "<h3>2. إضافة عمود image_path ونسخ البيانات من image_url</h3>";
        echo "<pre>
ALTER TABLE product_images ADD COLUMN image_path VARCHAR(255) NOT NULL;
UPDATE product_images SET image_path = image_url WHERE image_url IS NOT NULL;</pre>";
    }
    
    // Solution 3: Debug mode
    echo "<h3>3. عرض قيم المتغيرات في صفحة index.php</h3>";
    echo "<p>قم بإضافة الكود التالي قبل نهاية ملف index.php:</p>";
    echo "<pre>
&lt;?php if (isset(\$_GET['debug'])): ?&gt;
&lt;div style=\"direction:ltr; text-align:left; background:#f5f5f5; padding:15px; margin:20px; border-radius:5px;\"&gt;
    &lt;h3&gt;Debug Information&lt;/h3&gt;
    &lt;h4&gt;Flash Sale Products:&lt;/h4&gt;
    &lt;pre&gt;&lt;?php print_r(\$flashSaleProducts); ?&gt;&lt;/pre&gt;
    
    &lt;h4&gt;Best Selling Products:&lt;/h4&gt;
    &lt;pre&gt;&lt;?php print_r(\$bestSellingProducts); ?&gt;&lt;/pre&gt;
    
    &lt;h4&gt;All Products:&lt;/h4&gt;
    &lt;pre&gt;&lt;?php print_r(\$allProducts); ?&gt;&lt;/pre&gt;
&lt;/div&gt;
&lt;?php endif; ?&gt;</pre>";
    echo "<p>ثم قم بزيارة <a href='/Tienda/index.php?debug=1'>http://localhost/Tienda/index.php?debug=1</a></p>";
    
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div style='color:red;'>";
    echo "<h2>خطأ في الاتصال بقاعدة البيانات</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
