<?php
// Include database connection
require_once 'includes/config.php';

echo "<h1>إصلاح مسارات صور المنتجات</h1>";
echo "<p style='direction: rtl; text-align: right;'>هذا البرنامج سيقوم بإصلاح مشكلة مسارات الصور التي تبدأ بـ '../' في قاعدة البيانات</p>";

try {
    // Count total images with '../' prefix
    $stmt = $conn->prepare("SELECT COUNT(*) FROM product_images WHERE image_path LIKE '../%'");
    $stmt->execute();
    $total = $stmt->fetchColumn();
    
    echo "<p>عدد الصور التي تحتاج إلى إصلاح: {$total}</p>";
    
    if ($total > 0) {
        // Update all image paths by removing '../' prefix
        $stmt = $conn->prepare("UPDATE product_images SET image_path = SUBSTRING(image_path, 4) WHERE image_path LIKE '../%'");
        $stmt->execute();
        $updated = $stmt->rowCount();
        
        echo "<div style='color: green; font-weight: bold;'>تم إصلاح {$updated} صورة بنجاح!</div>";
    } else {
        echo "<div style='color: blue;'>لا توجد مسارات صور تحتاج إلى إصلاح.</div>";
    }
    
    echo "<h2>تعديل ملف index.php</h2>";
    
    // Now let's add code to index.php to handle any paths that still might have '../' prefix
    $indexFile = __DIR__ . '/index.php';
    $content = file_get_contents($indexFile);
    
    // First, let's check if our fix is already applied
    $fixAlreadyApplied = strpos($content, 'if (strpos($image_path, \'../\') === 0)') !== false;
    
    if ($fixAlreadyApplied) {
        echo "<div style='color: blue;'>تم تطبيق الإصلاح في ملف index.php مسبقاً.</div>";
    } else {
        // Search for the image tag patterns we need to replace
        $pattern = '/<img src="\/Tienda\/\<\?php echo (!empty\(\$product\[\'primary_image\'\]\)) \? htmlspecialchars\(\$product\[\'primary_image\'\]\) : \'assets\/images\/product-placeholder\.png\'; \?>" alt="\<\?php echo htmlspecialchars\(\$product\[\'name\'\]\); \?>">/';
        
        // Create replacement with the path fix
        $replacement = '<img src="/Tienda/<?php 
            if (\1) {
                $image_path = $product[\'primary_image\'];
                // Remove \'../\' from the beginning of the path if it exists
                if (strpos($image_path, \'../\') === 0) {
                    $image_path = substr($image_path, 3);
                }
                echo htmlspecialchars($image_path);
            } else {
                echo \'assets/images/product-placeholder.png\';
            }
        ?>" alt="<?php echo htmlspecialchars($product[\'name\']); ?>">';
        
        // This direct pattern replacement might not work due to complexity, let's take a safer approach
        echo "<div style='color: orange;'>ملاحظة: لإصلاح ملف index.php بشكل نهائي، أضف الكود التالي في موضع عرض الصور:</div>";
        echo "<pre style='background: #f4f4f4; padding: 10px; border-radius: 5px;'>
&lt;img src=\"/Tienda/&lt;?php 
    if (!empty(\$product['primary_image'])) {
        \$image_path = \$product['primary_image'];
        // Remove '../' from the beginning of the path if it exists
        if (strpos(\$image_path, '../') === 0) {
            \$image_path = substr(\$image_path, 3);
        }
        echo htmlspecialchars(\$image_path);
    } else {
        echo 'assets/images/product-placeholder.png';
    }
?&gt;\" alt=\"&lt;?php echo htmlspecialchars(\$product['name']); ?&gt;\">
</pre>";
    }
    
    // Create a temporary solution in all pages
    echo "<h2>تطبيق حل مؤقت</h2>";
    echo "<p>يمكنك تطبيق الحل التالي في جميع الصفحات لعرض الصور بشكل صحيح:</p>";
    
    // Create a helper file
    $helperFile = __DIR__ . '/includes/image_helper.php';
    if (!file_exists($helperFile)) {
        $helperContent = '<?php
/**
 * Helper function to fix image paths
 */
function fix_image_path($path) {
    if (empty($path)) {
        return "assets/images/product-placeholder.png";
    }
    
    // Remove "../" from the beginning of the path if it exists
    if (strpos($path, "../") === 0) {
        $path = substr($path, 3);
    }
    
    return $path;
}
?>';
        file_put_contents($helperFile, $helperContent);
        echo "<div style='color: green;'>تم إنشاء ملف المساعدة: includes/image_helper.php</div>";
    } else {
        echo "<div style='color: blue;'>ملف المساعدة موجود مسبقاً: includes/image_helper.php</div>";
    }
    
    echo "<p>لاستخدام هذا الملف، أضف السطر التالي في بداية الصفحات التي تعرض صور المنتجات:</p>";
    echo "<pre style='background: #f4f4f4; padding: 10px; border-radius: 5px;'>require_once 'includes/image_helper.php';</pre>";
    
    echo "<p>ثم استخدم الدالة لإصلاح مسار الصورة على النحو التالي:</p>";
    echo "<pre style='background: #f4f4f4; padding: 10px; border-radius: 5px;'>&lt;img src=\"/Tienda/&lt;?php echo htmlspecialchars(fix_image_path(\$product['primary_image'])); ?&gt;\" alt=\"...\"&gt;</pre>";
    
} catch (PDOException $e) {
    echo "<div style='color: red; font-weight: bold;'>خطأ في قاعدة البيانات: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "<p><a href='index.php' style='display: inline-block; padding: 10px 20px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 5px;'>العودة للصفحة الرئيسية</a></p>";
?>
