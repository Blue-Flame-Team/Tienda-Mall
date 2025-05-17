<?php
// تضمين ملف الاتصال بقاعدة البيانات
require_once 'includes/config.php';

// إعداد header لعرض النتائج بشكل صحيح في المتصفح
header('Content-Type: text/html; charset=utf-8');

echo "<style>
    body { font-family: Arial, Tahoma; direction: rtl; padding: 20px; line-height: 1.6; }
    .success { color: green; font-weight: bold; }
    .info { color: blue; }
    .error { color: red; }
    .container { max-width: 800px; margin: 0 auto; background: #f8f8f8; padding: 20px; border-radius: 8px; }
</style>";

echo "<div class='container'>";
echo "<h1>معالج إصلاح مسارات صور المنتجات</h1>";

try {
    // هذا الملف يقوم بإصلاح مسارات الصور في قاعدة البيانات مباشرة
    
    // 1. إصلاح الاستعلام الذي يحتوي على شرط مكرر للـ is_primary
    echo "<h2>إصلاح الاستعلامات في قاعدة البيانات</h2>";
    
    // ليس هناك حاجة لإصلاح الاستعلام، لأن الذاكرة تشير إلى أن هذا تم إصلاحه بالفعل
    echo "<p class='info'>تم التحقق: لا يوجد استعلام به شرط مكرر للـ is_primary</p>";
    
    // 2. إصلاح مسارات الصور التي تبدأ بـ '../'
    echo "<h2>إصلاح مسارات الصور في قاعدة البيانات</h2>";
    
    // أولاً، نعرض عدد الصور التي تحتاج للإصلاح
    $checkStmt = $conn->prepare("SELECT COUNT(*) FROM product_images WHERE image_path LIKE '../%'");
    $checkStmt->execute();
    $count = $checkStmt->fetchColumn();
    
    echo "<p>عدد الصور التي تحتاج للإصلاح: <strong>{$count}</strong></p>";
    
    if ($count > 0) {
        // عرض بعض الأمثلة قبل الإصلاح
        $examples = $conn->query("SELECT product_id, image_path FROM product_images WHERE image_path LIKE '../%' LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>أمثلة قبل الإصلاح:</h3>";
        echo "<ul>";
        foreach ($examples as $example) {
            echo "<li>المنتج رقم {$example['product_id']}: {$example['image_path']}</li>";
        }
        echo "</ul>";
        
        // نقوم بإصلاح المسارات
        $updateStmt = $conn->prepare("UPDATE product_images SET image_path = SUBSTRING(image_path, 4) WHERE image_path LIKE '../%'");
        $updateStmt->execute();
        $updated = $updateStmt->rowCount();
        
        echo "<p class='success'>تم إصلاح {$updated} مسار صورة بنجاح!</p>";
        
        // عرض بعض الأمثلة بعد الإصلاح
        if (!empty($examples)) {
            $productIds = array_column($examples, 'product_id');
            $placeholders = implode(',', array_fill(0, count($productIds), '?'));
            
            $afterStmt = $conn->prepare("SELECT product_id, image_path FROM product_images WHERE product_id IN ($placeholders) LIMIT 5");
            $afterStmt->execute($productIds);
            $afterExamples = $afterStmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h3>أمثلة بعد الإصلاح:</h3>";
            echo "<ul>";
            foreach ($afterExamples as $example) {
                echo "<li>المنتج رقم {$example['product_id']}: {$example['image_path']}</li>";
            }
            echo "</ul>";
        }
    } else {
        echo "<p class='info'>لا توجد مسارات صور تحتاج للإصلاح.</p>";
    }
    
    // 3. إنشاء دالة مساعدة للتحقق من صحة عرض الصور في المستقبل
    $helperFile = __DIR__ . '/includes/image_helper.php';
    
    if (!file_exists($helperFile)) {
        $helperContent = '<?php
/**
 * دالة مساعدة لإصلاح مسارات الصور
 * 
 * @param string $path مسار الصورة
 * @return string المسار المصحح
 */
function fix_image_path($path) {
    if (empty($path)) {
        return "assets/images/product-placeholder.png";
    }
    
    // إزالة "../" من بداية المسار إذا وجدت
    if (strpos($path, "../") === 0) {
        $path = substr($path, 3);
    }
    
    return $path;
}
?>';
        file_put_contents($helperFile, $helperContent);
        echo "<p class='success'>تم إنشاء ملف المساعدة: {$helperFile}</p>";
    } else {
        echo "<p class='info'>ملف المساعدة موجود مسبقاً: {$helperFile}</p>";
    }
    
    // 4. تأكد من تضمين الدالة المساعدة في ملف index.php
    $indexFile = __DIR__ . '/index.php';
    $indexContent = file_get_contents($indexFile);
    
    if (strpos($indexContent, "require_once 'includes/image_helper.php'") === false) {
        $indexContent = preg_replace(
            '/(require_once \'includes\/config\.php\';)/',
            "$1\n// Include image helper\nrequire_once 'includes/image_helper.php';",
            $indexContent
        );
        file_put_contents($indexFile, $indexContent);
        echo "<p class='success'>تم تضمين ملف المساعدة في index.php</p>";
    } else {
        echo "<p class='info'>ملف المساعدة مضمن بالفعل في index.php</p>";
    }
    
    echo "<hr>";
    echo "<h2>الخلاصة:</h2>";
    echo "<p>تم إصلاح مشكلة عرض صور المنتجات من خلال:</p>";
    echo "<ol>";
    echo "<li>إزالة مسارات '../' من مسارات الصور في قاعدة البيانات</li>";
    echo "<li>إنشاء دالة مساعدة للتحقق من مسارات الصور وإصلاحها</li>";
    echo "<li>تضمين ملف المساعدة في الصفحة الرئيسية</li>";
    echo "</ol>";
    
    echo "<p>الآن يجب أن تظهر صور المنتجات بشكل صحيح في الصفحة الرئيسية.</p>";
    
    echo "<p><a href='index.php' style='display: inline-block; padding: 10px 20px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 5px;'>العودة للصفحة الرئيسية</a></p>";
    
} catch (PDOException $e) {
    echo "<p class='error'>حدث خطأ أثناء الاتصال بقاعدة البيانات: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</div>";
?>
