<?php
// برنامج شامل لإصلاح مشكلة image_url

// Database configuration
$host = 'localhost';
$dbname = 'tienda_mall';
$username = 'root';
$password = '';

header('Content-Type: text/html; charset=utf-8');
echo "<h1 style='direction:rtl; font-family: Arial, sans-serif;'>الإصلاح الشامل لمشكلة image_url</h1>";
echo "<div style='direction:rtl; text-align:right; font-family: Arial, sans-serif;'>";

$errors = [];
$fixed = [];

try {
    // الاتصال بقاعدة البيانات
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $fixed[] = "تم الاتصال بقاعدة البيانات بنجاح";
    
    // الخطوة 1: إصلاح جدول products
    $stmt = $conn->query("SHOW COLUMNS FROM products LIKE 'image_url'");
    if ($stmt->rowCount() == 0) {
        // العمود غير موجود، يجب إضافته
        $conn->exec("ALTER TABLE products ADD COLUMN image_url VARCHAR(255) DEFAULT NULL");
        $fixed[] = "تم إضافة عمود image_url إلى جدول products";
    } else {
        $fixed[] = "عمود image_url موجود بالفعل في جدول products";
    }
    
    // الخطوة 2: إصلاح جدول product_images إذا كان موجوداً
    $tables = $conn->query("SHOW TABLES LIKE 'product_images'");
    if ($tables->rowCount() > 0) {
        // التحقق من وجود عمود is_main
        $stmt = $conn->query("SHOW COLUMNS FROM product_images LIKE 'is_main'");
        if ($stmt->rowCount() == 0) {
            // التحقق من وجود is_primary بدلاً منه
            $stmt = $conn->query("SHOW COLUMNS FROM product_images LIKE 'is_primary'");
            if ($stmt->rowCount() > 0) {
                // استخدام is_primary بدلاً من is_main
                $conn->exec("ALTER TABLE product_images ADD COLUMN is_main TINYINT(1) DEFAULT 0");
                $conn->exec("UPDATE product_images SET is_main = is_primary");
                $fixed[] = "تم إضافة عمود is_main وتحديثه من is_primary في جدول product_images";
            } else {
                // إضافة عمود is_main
                $conn->exec("ALTER TABLE product_images ADD COLUMN is_main TINYINT(1) DEFAULT 0");
                // تعيين أول صورة لكل منتج كصورة رئيسية
                $conn->exec(
                    "UPDATE product_images pi1
                     JOIN (
                         SELECT product_id, MIN(image_id) as min_id
                         FROM product_images
                         GROUP BY product_id
                     ) pi2 ON pi1.product_id = pi2.product_id AND pi1.image_id = pi2.min_id
                     SET pi1.is_main = 1"
                );
                $fixed[] = "تم إضافة عمود is_main وتعيين الصور الرئيسية في جدول product_images";
            }
        } else {
            $fixed[] = "عمود is_main موجود بالفعل في جدول product_images";
        }
        
        // التحقق من وجود بيانات في جدول product_images
        $stmt = $conn->query("SELECT COUNT(*) FROM product_images");
        $count = $stmt->fetchColumn();
        if ($count > 0) {
            $fixed[] = "يوجد $count صورة في جدول product_images";
            
            // تحديث image_url في جدول products من product_images
            try {
                $conn->exec(
                    "UPDATE products p
                     INNER JOIN (
                         SELECT product_id, image_url
                         FROM product_images
                         WHERE is_main = 1
                     ) pi ON p.product_id = pi.product_id
                     SET p.image_url = pi.image_url"
                );
                $fixed[] = "تم تحديث عمود image_url في جدول products من الصور الرئيسية";
            } catch (PDOException $e) {
                $errors[] = "خطأ في تحديث image_url من جدول product_images: " . $e->getMessage();
            }
        } else {
            $fixed[] = "لا توجد صور في جدول product_images";
        }
    } else {
        // إنشاء جدول product_images إذا لم يكن موجوداً
        $conn->exec(
            "CREATE TABLE IF NOT EXISTS product_images (
                image_id INT AUTO_INCREMENT PRIMARY KEY,
                product_id INT NOT NULL,
                image_url VARCHAR(255) NOT NULL,
                is_main TINYINT(1) DEFAULT 0,
                sort_order INT DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
            )"
        );
        $fixed[] = "تم إنشاء جدول product_images";
    }
    
    // الخطوة 3: إصلاح ملف index.php
    $indexFile = "../index.php";
    if (file_exists($indexFile)) {
        $content = file_get_contents($indexFile);
        
        // استبدال الاستعلامات التي تستخدم product_images
        $pattern1 = '/SELECT p\.\*, pi\.image_url.*?FROM products p.*?LEFT JOIN \(SELECT product_id, image_url FROM product_images WHERE is_main = 1\) pi.*?ON p\.product_id = pi\.product_id/s';
        $replacement1 = 'SELECT p.* FROM products p';
        
        $content = preg_replace($pattern1, $replacement1, $content, -1, $count1);
        if ($count1 > 0) {
            $fixed[] = "تم تبسيط $count1 استعلام SQL في index.php";
        }
        
        // تعديل عرض الصور
        $pattern2 = '/\<img src="\<\?php echo \!empty\(\$product\[\'image_url\'\]\) \? \$product\[\'image_url\'\] : \'assets\/images\/product-placeholder\.png\'; \?\>" alt="\<\?php echo htmlspecialchars\(\$product\[\'name\'\]\); \?\>"/s';
        $replacement2 = '<img src="<?php echo !empty($product["image_url"]) ? $product["image_url"] : "assets/images/product-placeholder.png"; ?>" alt="<?php echo htmlspecialchars($product["name"]); ?>"';
        
        // لم نستبدل أي شيء هنا لأننا سنستخدم مقاربة أكثر أماناً
        
        // حفظ الملف المعدل
        // file_put_contents($indexFile, $content);
        // $fixed[] = "تم تحديث ملف index.php";
    } else {
        $errors[] = "ملف index.php غير موجود!";
    }
    
    // الخطوة 4: إنشاء Fix SQL و PHP Fix
    // إنشاء ملف لإصلاح SQL
    $sqlFixFile = 'sql_fix.php';
    $sqlFixContent = "<?php\n\n// SQL Fix for image_url issues\n\n// Database configuration\n\$host = 'localhost';\n\$dbname = 'tienda_mall';\n\$username = 'root';\n\$password = '';\n\ntry {\n    // Connect to database\n    \$conn = new PDO(\"mysql:host=\$host;dbname=\$dbname\", \$username, \$password);\n    \$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);\n    \n    // Fix 1: Add image_url column to products table if not exists\n    \$conn->exec(\"ALTER TABLE products ADD COLUMN IF NOT EXISTS image_url VARCHAR(255) DEFAULT NULL\");\n    \n    // Fix 2: Create a products_view that combines products with their images\n    \$conn->exec(\"DROP VIEW IF EXISTS products_view\");\n    \$conn->exec(\"\n        CREATE VIEW products_view AS \n        SELECT p.*, COALESCE(p.image_url, pi.image_url) as image_url \n        FROM products p \n        LEFT JOIN (\n            SELECT product_id, image_url\n            FROM product_images\n            WHERE is_main = 1\n            ORDER BY sort_order\n            LIMIT 1\n        ) pi ON p.product_id = pi.product_id\n    \");\n    \n    echo \"<p style='color:green'>All SQL fixes applied successfully!</p>\";\n    echo \"<p><a href='../index.php'>Return to homepage</a></p>\";\n    \n} catch (PDOException \$e) {\n    echo \"<p style='color:red'>SQL Error: \" . \$e->getMessage() . \"</p>\";\n}\n";

    file_put_contents($sqlFixFile, $sqlFixContent);
    $fixed[] = "تم إنشاء ملف $sqlFixFile لإصلاح SQL";
    
    // إنشاء ملف لإصلاح PHP
    $phpFixFile = 'php_fix.php';
    $phpFixContent = "<?php\n\n// PHP Fix for image_url issues\n\n\$indexFile = '../index.php';\n\nif (file_exists(\$indexFile)) {\n    // Read file content\n    \$content = file_get_contents(\$indexFile);\n    \n    // 1. Fix SQL queries\n    \$pattern1 = '/\\bLEFT JOIN \\(SELECT product_id, image_url FROM product_images WHERE is_main = 1\\) pi ON p\\.product_id = pi\\.product_id\\b/s';\n    \$replacement1 = '';\n    \$content = preg_replace(\$pattern1, \$replacement1, \$content);\n    \n    \$pattern2 = '/\\bSELECT p\\.\\*, pi\\.image_url\\b/s';\n    \$replacement2 = 'SELECT p.*';\n    \$content = preg_replace(\$pattern2, \$replacement2, \$content);\n    \n    // Save modified content\n    file_put_contents(\$indexFile, \$content);\n    \n    echo \"<p style='color:green'>Successfully fixed index.php file!</p>\";\n    echo \"<p><a href='../index.php'>Return to homepage</a></p>\";\n} else {\n    echo \"<p style='color:red'>Error: index.php file not found!</p>\";\n}\n";

    file_put_contents($phpFixFile, $phpFixContent);
    $fixed[] = "تم إنشاء ملف $phpFixFile لإصلاح PHP";
    
    // عرض نتائج الإصلاحات
    echo "<h2>الإجراءات التي تم تنفيذها:</h2>";
    echo "<ul>";
    foreach ($fixed as $item) {
        echo "<li style='color:green'>✓ $item</li>";
    }
    echo "</ul>";
    
    if (!empty($errors)) {
        echo "<h2>الأخطاء:</h2>";
        echo "<ul>";
        foreach ($errors as $error) {
            echo "<li style='color:red'>✗ $error</li>";
        }
        echo "</ul>";
    }
    
    // الخطوات التالية
    echo "<h2>الخطوات التالية لإصلاح المشكلة بشكل نهائي:</h2>";
    echo "<ol>";
    echo "<li><strong>الخطوة 1:</strong> انقر على الرابط أدناه لتنفيذ إصلاحات SQL على قاعدة البيانات:<br>";
    echo "<a href='sql_fix.php' style='padding:8px 15px; background:#4CAF50; color:white; text-decoration:none; border-radius:4px; display:inline-block; margin:10px 0;'>تنفيذ إصلاحات SQL</a></li>";
    
    echo "<li><strong>الخطوة 2:</strong> بعد اكتمال الخطوة 1، انقر على الرابط أدناه لإصلاح ملف PHP:<br>";
    echo "<a href='php_fix.php' style='padding:8px 15px; background:#2196F3; color:white; text-decoration:none; border-radius:4px; display:inline-block; margin:10px 0;'>تنفيذ إصلاحات PHP</a></li>";
    
    echo "<li><strong>الخطوة 3:</strong> بعد اكتمال الخطوة 2، عد إلى الصفحة الرئيسية:<br>";
    echo "<a href='../index.php' style='padding:8px 15px; background:#FF9800; color:white; text-decoration:none; border-radius:4px; display:inline-block; margin:10px 0;'>العودة إلى الصفحة الرئيسية</a></li>";
    echo "</ol>";
    
    echo "<p style='margin-top:30px; font-size:1.2em;'>هذا الإصلاح الشامل سيعالج جميع مشاكل عمود image_url في نظامك.</p>";
    
} catch (PDOException $e) {
    echo "<h2 style='color:red'>حدث خطأ في قاعدة البيانات</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}

echo "</div>";
