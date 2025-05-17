<?php
// برنامج للتحقق من جميع ملفات PHP في المشروع للبحث عن مشكلة image_url

header('Content-Type: text/html; charset=utf-8');
echo "<h1 style='direction:rtl; font-family: Arial, sans-serif;'>فحص شامل لمشكلة image_url</h1>";
echo "<div style='direction:rtl; text-align:right; font-family: Arial, sans-serif;'>";

// مجلد المشروع
$projectDir = __DIR__;
$fileCount = 0;
$problemFiles = [];

// وظيفة لفحص الملفات بشكل متكرر
function scanDirectory($dir) {
    global $fileCount, $problemFiles;
    
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file == '.' || $file == '..' || $file === 'check_all_files.php') {
            continue;
        }
        
        $path = $dir . '/' . $file;
        
        if (is_dir($path)) {
            scanDirectory($path);
        } elseif (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
            $fileCount++;
            $content = file_get_contents($path);
            
            // البحث عن استخدام image_url في استعلامات SQL
            if (preg_match('/SELECT.*?image_url.*?FROM.*?product_images/i', $content)) {
                $problemFiles[] = [
                    'path' => str_replace($projectDir, '', $path),
                    'type' => 'SQL query with image_url'
                ];
            }
            
            // البحث عن استخدام image_url في عرض HTML
            if (preg_match('/\$product\[.?image_url.?\]/i', $content) || 
                preg_match('/\$related\[.?image_url.?\]/i', $content)) {
                $problemFiles[] = [
                    'path' => str_replace($projectDir, '', $path),
                    'type' => 'HTML display with image_url'
                ];
            }
        }
    }
}

// بدء الفحص
scanDirectory($projectDir);

echo "<p>تم فحص <strong>$fileCount</strong> ملف PHP</p>";

if (count($problemFiles) > 0) {
    echo "<h2>الملفات التي تحتاج إلى إصلاح:</h2>";
    echo "<table style='width:100%; border-collapse:collapse;'>";
    echo "<tr style='background:#f0f0f0;'><th style='padding:10px; border:1px solid #ddd;'>المسار</th><th style='padding:10px; border:1px solid #ddd;'>نوع المشكلة</th></tr>";
    
    foreach ($problemFiles as $file) {
        echo "<tr>";
        echo "<td style='padding:10px; border:1px solid #ddd;'>" . htmlspecialchars($file['path']) . "</td>";
        echo "<td style='padding:10px; border:1px solid #ddd;'>" . htmlspecialchars($file['type']) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<h2>كيفية الإصلاح:</h2>";
    echo "<ol>";
    echo "<li>افتح كل ملف من الملفات المذكورة أعلاه</li>";
    echo "<li>في حالة استعلامات SQL: قم بإزالة أجزاء JOINS التي تشير إلى جدول product_images وإزالة pi.image_url من قائمة الحقول المحددة</li>";
    echo "<li>في حالة عرض HTML: استبدل <code>\$product['image_url']</code> بـ <code>assets/images/product-placeholder.png</code></li>";
    echo "</ol>";
    
} else {
    echo "<p style='color:green;'>✓ لم يتم العثور على أي مشكلات متعلقة بـ image_url!</p>";
}

echo "<p>تأكد من تشغيل SQL التالي لإضافة أو تحديث العمود في جدول المنتجات:</p>";
echo "<div style='background:#f8f8f8; padding:15px; border-left:4px solid #4CAF50; margin:20px 0; direction:ltr;'>";
echo "<pre>ALTER TABLE products ADD COLUMN IF NOT EXISTS image_url VARCHAR(255) DEFAULT NULL;</pre>";
echo "</div>";

echo "<p><a href='index.php' style='padding:10px 20px; background:#4CAF50; color:white; text-decoration:none; border-radius:4px; display:inline-block;'>العودة إلى الصفحة الرئيسية</a></p>";
echo "</div>";
