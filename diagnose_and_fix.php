<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$host = 'localhost';
$dbname = 'tienda_mall';
$username = 'root';
$password = '';

// Start HTML output
header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html>\n<html lang='ar' dir='rtl'>\n<head>\n";
echo "<meta charset='UTF-8'>\n";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>\n";
echo "<title>تشخيص وإصلاح الصور</title>\n";
echo "<style>\n";
echo "body { font-family: Arial, sans-serif; padding: 20px; direction: rtl; }\n";
echo "h1, h2, h3 { color: #333; }\n";
echo "table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }\n";
echo "th, td { border: 1px solid #ddd; padding: 8px; text-align: right; }\n";
echo "th { background-color: #f2f2f2; }\n";
echo ".success { color: green; }\n";
echo ".error { color: red; }\n";
echo ".product-grid { display: flex; flex-wrap: wrap; gap: 20px; margin-top: 20px; }\n";
echo ".product-card { border: 1px solid #ddd; padding: 15px; border-radius: 5px; width: 220px; }\n";
echo ".product-image { max-width: 100%; height: 200px; object-fit: contain; display: block; margin: 0 auto; }\n";
echo ".btn { display: inline-block; padding: 8px 16px; background: #DB4444; color: white; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; }\n";
echo ".btn-green { background: green; }\n";
echo ".steps { background: #f8f8f8; padding: 15px; border-radius: 5px; margin-bottom: 20px; }\n";
echo ".steps ol { margin-left: 20px; }\n";
echo "</style>\n";
echo "</head>\n<body>\n";
echo "<h1>تشخيص وإصلاح مشكلة عرض صور المنتجات</h1>\n";

try {
    // Connect to database
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div class='success'><strong>✅ تم الاتصال بقاعدة البيانات بنجاح</strong></div>\n";
    
    // Check for fix action
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    
    // Actions
    if ($action == 'fix_database') {
        echo "<h2>إصلاح مسارات الصور في قاعدة البيانات</h2>\n";
        
        // Update paths in database - fix relative paths
        $stmt = $conn->prepare("UPDATE product_images SET image_path = SUBSTRING(image_path, 4) WHERE image_path LIKE '../%'");
        $stmt->execute();
        $fixedCount = $stmt->rowCount();
        
        echo "<div class='success'>✅ تم تحديث $fixedCount مسار صورة في قاعدة البيانات</div>\n";
        
        // Update is_primary if needed
        $stmt = $conn->prepare("SELECT product_id FROM products WHERE product_id NOT IN (SELECT DISTINCT product_id FROM product_images WHERE is_primary = 1)");
        $stmt->execute();
        $productsWithoutPrimary = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (count($productsWithoutPrimary) > 0) {
            echo "<div>تعيين صورة رئيسية للمنتجات التي ليس لديها صورة رئيسية...</div>\n";
            
            foreach ($productsWithoutPrimary as $productId) {
                // Find first image for this product and set as primary
                $stmt = $conn->prepare("UPDATE product_images SET is_primary = 1 WHERE product_id = :product_id ORDER BY image_id LIMIT 1");
                $stmt->bindParam(':product_id', $productId);
                $stmt->execute();
            }
            
            echo "<div class='success'>✅ تم تعيين صور رئيسية لـ " . count($productsWithoutPrimary) . " منتج</div>\n";
        }
    } elseif ($action == 'fix_structure') {
        // Check uploads directory exists
        $uploadsDir = $_SERVER['DOCUMENT_ROOT'] . '/Tienda/uploads/products';
        if (!file_exists($uploadsDir)) {
            mkdir($uploadsDir, 0755, true);
            echo "<div class='success'>✅ تم إنشاء مجلد uploads/products</div>\n";
        }
    }
    
    // Display current situation
    echo "<h2>حالة الصور الحالية</h2>\n";
    
    // Get product images
    $stmt = $conn->prepare("SELECT * FROM product_images ORDER BY product_id, is_primary DESC LIMIT 20");
    $stmt->execute();
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($images) > 0) {
        echo "<table>\n";
        echo "<tr><th>معرف الصورة</th><th>معرف المنتج</th><th>مسار الصورة</th><th>رئيسية؟</th><th>الملف موجود؟</th><th>معاينة</th></tr>\n";
        
        foreach ($images as $image) {
            $imagePath = $image['image_path'] ?? '';
            if (empty($imagePath) && isset($image['image_url'])) {
                $imagePath = $image['image_url'];
            }
            
            // Check if file exists
            $fileExists = false;
            $absolutePath = $_SERVER['DOCUMENT_ROOT'] . '/Tienda/' . ltrim($imagePath, '/');
            if (file_exists($absolutePath)) {
                $fileExists = true;
            }
            
            // Output row
            echo "<tr>\n";
            echo "<td>{$image['image_id']}</td>\n";
            echo "<td>{$image['product_id']}</td>\n";
            echo "<td>{$imagePath}</td>\n";
            echo "<td>" . ($image['is_primary'] ? 'نعم' : 'لا') . "</td>\n";
            echo "<td>" . ($fileExists ? '<span class="success">✅ موجود</span>' : '<span class="error">❌ غير موجود</span>') . "</td>\n";
            echo "<td>";
            if ($fileExists) {
                echo "<img src='/{$imagePath}' height='40'>";
            } else {
                echo "<span class='error'>لا توجد صورة</span>";
            }
            echo "</td>\n";
            echo "</tr>\n";
        }
        
        echo "</table>\n";
    } else {
        echo "<div class='error'>❌ لم يتم العثور على صور للمنتجات في قاعدة البيانات</div>\n";
    }
    
    // Get products with their primary images
    echo "<h2>المنتجات وصورها الرئيسية</h2>\n";
    
    $stmt = $conn->prepare(
        "SELECT p.*, 
        (SELECT image_path FROM product_images WHERE product_id = p.product_id AND is_primary = 1 LIMIT 1) as primary_image 
        FROM products p 
        WHERE p.is_active = 1 
        LIMIT 8"
    );
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($products) > 0) {
        echo "<div class='product-grid'>\n";
        
        foreach ($products as $product) {
            $imagePath = isset($product['primary_image']) && !empty($product['primary_image']) 
                ? $product['primary_image'] 
                : 'assets/images/product-placeholder.png';
            
            // Remove ../ if present
            if (strpos($imagePath, '../') === 0) {
                $imagePath = substr($imagePath, 3);
            }
            
            // Build absolute URL for testing
            $imageUrl = '/' . ltrim($imagePath, '/');
            
            // Check if file exists
            $fileExists = file_exists($_SERVER['DOCUMENT_ROOT'] . '/Tienda/' . ltrim($imagePath, '/'));
            
            echo "<div class='product-card'>\n";
            echo "<h3>{$product['name']}</h3>\n";
            echo "<p>معرف المنتج: {$product['product_id']}</p>\n";
            echo "<p>مسار الصورة: {$imagePath}</p>\n";
            echo "<p>" . ($fileExists ? '<span class="success">✅ الملف موجود</span>' : '<span class="error">❌ الملف غير موجود</span>') . "</p>\n";
            echo "<img src='{$imageUrl}' class='product-image' onerror=\"this.src='/Tienda/assets/images/product-placeholder.png'; this.onerror=null; this.style.border='1px solid red';\">\n";
            echo "</div>\n";
        }
        
        echo "</div>\n";
    } else {
        echo "<div class='error'>❌ لم يتم العثور على منتجات في قاعدة البيانات</div>\n";
    }
    
    // Action buttons
    echo "<h2>الإجراءات المتاحة</h2>\n";
    echo "<div class='steps'>\n";
    echo "<p>للحل النهائي، قم بتنفيذ الخطوات التالية بالترتيب:</p>\n";
    echo "<ol>\n";
    echo "<li>انقر على زر <strong>إصلاح مسارات الصور</strong> لتحويل المسارات النسبية إلى مسارات مطلقة</li>\n";
    echo "<li>انقر على زر <strong>إصلاح بنية المجلدات</strong> للتأكد من وجود مجلدات الرفع</li>\n";
    echo "<li>بعد الانتهاء، انقر على زر <strong>زيارة الصفحة الرئيسية</strong> للتأكد من ظهور الصور</li>\n";
    echo "</ol>\n";
    echo "</div>\n";
    
    echo "<p><a href='diagnose_and_fix.php?action=fix_database' class='btn'>إصلاح مسارات الصور</a> ";
    echo "<a href='diagnose_and_fix.php?action=fix_structure' class='btn'>إصلاح بنية المجلدات</a> ";
    echo "<a href='diagnose_and_fix.php' class='btn'>تحديث</a> ";
    echo "<a href='index.php' class='btn btn-green'>زيارة الصفحة الرئيسية</a></p>\n";
    
    echo "<h2>حل مشكلة عدم ظهور الصور في الصفحة الرئيسية</h2>\n";
    echo "<p>إذا استمرت المشكلة، جرب الحلول التالية:</p>\n";
    echo "<ol>\n";
    echo "<li><strong>تحقق من مسارات الصور:</strong> تأكد من أن المسارات المخزنة في قاعدة البيانات تشير إلى الموقع الصحيح للصور</li>\n";
    echo "<li><strong>تحقق من وجود الملفات:</strong> تأكد من أن ملفات الصور موجودة فعلياً في المجلدات المشار إليها</li>\n";
    echo "<li><strong>فحص صلاحيات المجلدات:</strong> تأكد من أن خادم الويب لديه صلاحيات لقراءة ملفات الصور</li>\n";
    echo "<li><strong>مراجعة تكوين الخادم:</strong> قد تكون هناك مشكلة في إعدادات الخادم تمنع الوصول إلى الصور</li>\n";
    echo "<li><strong>تعديل كود index.php:</strong> قم بتعديل كود عرض الصور في index.php لاستخدام مسارات مطلقة بدلاً من نسبية</li>\n";
    echo "</ol>\n";
    
    echo "<h3>ضبط مسارات الملفات يدوياً</h3>\n";
    echo "<p>إذا كنت ترغب في تعديل مسارات الصور يدوياً، استخدم الاستعلام التالي في phpMyAdmin:</p>\n";
    echo "<pre style='background:#f5f5f5;padding:10px;direction:ltr;'>\n";
    echo "UPDATE product_images SET image_path = REPLACE(image_path, '../uploads/products/', 'uploads/products/')\n";
    echo "</pre>\n";
    
} catch (PDOException $e) {
    echo "<div class='error'>❌ خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage() . "</div>\n";
}

echo "</body>\n</html>";
