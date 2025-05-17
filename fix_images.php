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
echo "<html><head><title>إصلاح مشكلة الصور</title>";
echo "<style>body{font-family:Arial;direction:rtl;padding:20px;} .success{color:green;} .error{color:red;} .btn{background:#DB4444;color:white;padding:10px 15px;text-decoration:none;display:inline-block;margin:5px;border-radius:4px;}</style>";
echo "</head><body>";
echo "<h1>أداة إصلاح صور المنتجات</h1>";

try {
    // Connect to the database
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    
    // Display current server information
    echo "<h2>معلومات الخادم</h2>";
    echo "<p>مسار الجذر للمستندات: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
    echo "<p>اسم المستند الحالي: " . $_SERVER['PHP_SELF'] . "</p>";
    echo "<p>مسار الموقع الكامل: http://" . $_SERVER['HTTP_HOST'] . "/Tienda/</p>";
    
    // Create directories if needed
    if ($action == 'create_dirs') {
        $dirs = [
            $_SERVER['DOCUMENT_ROOT'] . '/Tienda/uploads',
            $_SERVER['DOCUMENT_ROOT'] . '/Tienda/uploads/products',
            $_SERVER['DOCUMENT_ROOT'] . '/Tienda/assets',
            $_SERVER['DOCUMENT_ROOT'] . '/Tienda/assets/images'
        ];
        
        foreach ($dirs as $dir) {
            if (!file_exists($dir)) {
                if (mkdir($dir, 0755, true)) {
                    echo "<p class='success'>✅ تم إنشاء المجلد: $dir</p>";
                } else {
                    echo "<p class='error'>❌ فشل إنشاء المجلد: $dir</p>";
                }
            } else {
                echo "<p>المجلد موجود بالفعل: $dir</p>";
            }
        }
        
        // Create placeholder image
        $placeholderPath = $_SERVER['DOCUMENT_ROOT'] . '/Tienda/assets/images/product-placeholder.png';
        if (!file_exists($placeholderPath)) {
            // Create a simple placeholder image
            $img = imagecreatetruecolor(300, 300);
            $bgColor = imagecolorallocate($img, 200, 200, 200);
            $textColor = imagecolorallocate($img, 50, 50, 50);
            imagefill($img, 0, 0, $bgColor);
            imagestring($img, 5, 60, 140, 'Product Image Placeholder', $textColor);
            imagepng($img, $placeholderPath);
            imagedestroy($img);
            echo "<p class='success'>✅ تم إنشاء صورة بديلة: $placeholderPath</p>";
        }
    }
    
    // Search for image files in admin uploads folder
    if ($action == 'find_images') {
        echo "<h2>البحث عن ملفات الصور</h2>";
        
        // Common locations to check
        $possibleLocations = [
            $_SERVER['DOCUMENT_ROOT'] . '/Tienda/admin/uploads/products',
            $_SERVER['DOCUMENT_ROOT'] . '/Tienda/uploads/products',
            $_SERVER['DOCUMENT_ROOT'] . '/Tienda/admin/uploads',
            $_SERVER['DOCUMENT_ROOT'] . '/Tienda/uploads',
        ];
        
        $foundFiles = [];
        foreach ($possibleLocations as $location) {
            if (file_exists($location) && is_dir($location)) {
                echo "<p>فحص المجلد: $location</p>";
                $files = glob($location . '/*.{jpg,jpeg,png,gif}', GLOB_BRACE);
                foreach ($files as $file) {
                    $foundFiles[] = $file;
                    echo "<p class='success'>وجد ملف: " . basename($file) . " في $location</p>";
                }
            } else {
                echo "<p>المجلد غير موجود: $location</p>";
            }
        }
        
        if (count($foundFiles) > 0) {
            echo "<p>تم العثور على " . count($foundFiles) . " صورة. انقر على 'نسخ الصور' لنسخها إلى المجلد الصحيح.</p>";
        } else {
            echo "<p class='error'>لم يتم العثور على أي صور.</p>";
        }
    }
    
    // Copy images to the correct location
    if ($action == 'copy_images') {
        echo "<h2>نسخ الصور إلى المجلد الصحيح</h2>";
        
        // Source locations
        $sourceLocations = [
            $_SERVER['DOCUMENT_ROOT'] . '/Tienda/admin/uploads/products',
            $_SERVER['DOCUMENT_ROOT'] . '/Tienda/admin/uploads'
        ];
        
        // Target location
        $targetLocation = $_SERVER['DOCUMENT_ROOT'] . '/Tienda/uploads/products/';
        
        if (!file_exists($targetLocation)) {
            mkdir($targetLocation, 0755, true);
            echo "<p class='success'>تم إنشاء مجلد الهدف: $targetLocation</p>";
        }
        
        $copiedCount = 0;
        foreach ($sourceLocations as $sourceLocation) {
            if (file_exists($sourceLocation) && is_dir($sourceLocation)) {
                $files = glob($sourceLocation . '/*.{jpg,jpeg,png,gif}', GLOB_BRACE);
                foreach ($files as $file) {
                    $targetFile = $targetLocation . basename($file);
                    if (copy($file, $targetFile)) {
                        $copiedCount++;
                        echo "<p class='success'>✅ تم نسخ: " . basename($file) . "</p>";
                    } else {
                        echo "<p class='error'>❌ فشل نسخ: " . basename($file) . "</p>";
                    }
                }
            }
        }
        
        if ($copiedCount > 0) {
            echo "<p class='success'>تم نسخ $copiedCount صورة إلى المجلد الصحيح.</p>";
        } else {
            echo "<p class='error'>لم يتم نسخ أي صور. تأكد من وجود صور في المجلدات المصدر.</p>";
        }
    }
    
    // Fix image paths in database
    if ($action == 'fix_paths') {
        echo "<h2>إصلاح مسارات الصور في قاعدة البيانات</h2>";
        
        // First, show current paths
        $stmt = $conn->query("SELECT * FROM product_images LIMIT 10");
        $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>المسارات الحالية في قاعدة البيانات:</h3>";
        echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
        echo "<tr><th>معرف الصورة</th><th>معرف المنتج</th><th>المسار الحالي</th></tr>";
        
        foreach ($images as $image) {
            echo "<tr>";
            echo "<td>{$image['image_id']}</td>";
            echo "<td>{$image['product_id']}</td>";
            echo "<td>" . ($image['image_path'] ?? 'غير محدد') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Update paths in database
        $stmt = $conn->prepare(
            "UPDATE product_images 
             SET image_path = CASE 
                WHEN image_path LIKE '../uploads/products/%' THEN CONCAT('uploads/products/', SUBSTRING(image_path, 21))
                WHEN image_path LIKE '%/uploads/products/%' THEN CONCAT('uploads/products/', SUBSTRING_INDEX(image_path, '/uploads/products/', -1))
                ELSE image_path 
             END"
        );
        $stmt->execute();
        
        $updatedRows = $stmt->rowCount();
        echo "<p class='success'>✅ تم تحديث $updatedRows مسار في قاعدة البيانات.</p>";
        
        // Now show the updated paths
        $stmt = $conn->query("SELECT * FROM product_images LIMIT 10");
        $updatedImages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>المسارات بعد التحديث:</h3>";
        echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
        echo "<tr><th>معرف الصورة</th><th>معرف المنتج</th><th>المسار الجديد</th></tr>";
        
        foreach ($updatedImages as $image) {
            echo "<tr>";
            echo "<td>{$image['image_id']}</td>";
            echo "<td>{$image['product_id']}</td>";
            echo "<td>" . ($image['image_path'] ?? 'غير محدد') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Update index.php to handle image paths correctly
    if ($action == 'fix_code') {
        echo "<h2>تحديث كود عرض الصور في index.php</h2>";
        
        $indexFile = $_SERVER['DOCUMENT_ROOT'] . '/Tienda/index.php';
        if (file_exists($indexFile)) {
            $content = file_get_contents($indexFile);
            
            // Replace image paths to use /Tienda/ prefix
            $content = str_replace(
                'src="<?php echo !empty($product[\'primary_image\']) ? (strpos($product[\'primary_image\'], \'../\') === 0 ? htmlspecialchars(substr($product[\'primary_image\'], 3)) : htmlspecialchars($product[\'primary_image\'])) : \'assets/images/product-placeholder.png\'; ?>"',
                'src="/Tienda/<?php echo !empty($product[\'primary_image\']) ? htmlspecialchars($product[\'primary_image\']) : \'assets/images/product-placeholder.png\'; ?>"',
                $content
            );
            
            file_put_contents($indexFile, $content);
            echo "<p class='success'>✅ تم تحديث ملف index.php بنجاح.</p>";
        } else {
            echo "<p class='error'>❌ لم يتم العثور على ملف index.php.</p>";
        }
    }
    
    // Test image display
    echo "<h2>اختبار عرض الصور</h2>";
    
    // Get products with images
    $stmt = $conn->query(
        "SELECT p.*, 
        (SELECT image_path FROM product_images WHERE product_id = p.product_id AND is_primary = 1 LIMIT 1) as primary_image 
        FROM products p 
        WHERE p.is_active = 1 
        LIMIT 4"
    );
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div style='display:flex;flex-wrap:wrap;gap:20px;'>";
    foreach ($products as $product) {
        $imagePath = $product['primary_image'] ?? 'assets/images/product-placeholder.png';
        
        echo "<div style='border:1px solid #ddd;padding:10px;border-radius:5px;width:200px;'>";
        echo "<h3>{$product['name']}</h3>";
        echo "<p>مسار الصورة: $imagePath</p>";
        echo "<p>اختبار الصورة بمسار نسبي:</p>";
        echo "<img src='$imagePath' style='max-width:100%;height:150px;object-fit:contain;' onerror=\"this.src='/Tienda/assets/images/product-placeholder.png';this.onerror=null;\">";
        echo "<p>اختبار الصورة بمسار مطلق:</p>";
        echo "<img src='/Tienda/$imagePath' style='max-width:100%;height:150px;object-fit:contain;' onerror=\"this.src='/Tienda/assets/images/product-placeholder.png';this.onerror=null;\">";
        echo "</div>";
    }
    echo "</div>";
    
    // Show action links
    echo "<h2>الإجراءات المتاحة</h2>";
    echo "<p>اتبع هذه الخطوات بالترتيب لإصلاح مشكلة الصور:</p>";
    echo "<ol>";
    echo "<li>إنشاء مجلدات الصور المطلوبة</li>";
    echo "<li>البحث عن ملفات الصور الموجودة</li>";
    echo "<li>نسخ الصور إلى المجلد الصحيح</li>";
    echo "<li>تحديث مسارات الصور في قاعدة البيانات</li>";
    echo "<li>تحديث كود عرض الصور في index.php</li>";
    echo "</ol>";
    
    echo "<div style='margin-top:20px;'>";
    echo "<a href='fix_images.php?action=create_dirs' class='btn'>1. إنشاء المجلدات</a> ";
    echo "<a href='fix_images.php?action=find_images' class='btn'>2. البحث عن الصور</a> ";
    echo "<a href='fix_images.php?action=copy_images' class='btn'>3. نسخ الصور</a> ";
    echo "<a href='fix_images.php?action=fix_paths' class='btn'>4. إصلاح المسارات</a> ";
    echo "<a href='fix_images.php?action=fix_code' class='btn'>5. تحديث الكود</a> ";
    echo "<a href='fix_images.php' class='btn'>تحديث الصفحة</a>";
    echo "<a href='index.php' class='btn' style='background:green;'>الصفحة الرئيسية</a>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div class='error'>خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage() . "</div>";
}

echo "</body></html>";
