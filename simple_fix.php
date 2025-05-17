<?php
// حل بسيط لتعديل ملف index.php مباشرة

$indexFile = "index.php";

if (file_exists($indexFile)) {
    // قراءة محتوى الملف
    $content = file_get_contents($indexFile);
    
    // تعديل الاستعلامات SQL لإزالة إشارات إلى image_url
    $patterns = [
        '/SELECT p\.\*, pi\.image_url/i',
        '/LEFT JOIN \(SELECT product_id, image_url FROM product_images WHERE is_main = 1\) pi ON p\.product_id = pi\.product_id/i'
    ];
    
    $replacements = [
        'SELECT p.*',
        ''
    ];
    
    $content = preg_replace($patterns, $replacements, $content);
    
    // إصلاح عرض الصور باستخدام الصورة الافتراضية مباشرةً
    $imgPattern = '/src="\<\?php echo !empty\(\$product\[\'image_url\'\]\) \? \$product\[\'image_url\'\] : \'assets\/images\/product-placeholder\.png\'; \?\>"/i';
    $imgReplacement = 'src="assets/images/product-placeholder.png"';
    
    $content = preg_replace($imgPattern, $imgReplacement, $content);
    
    // حفظ الملف بعد التعديلات
    file_put_contents($indexFile, $content);
    
    echo "تم تعديل ملف index.php بنجاح!<br>";
    echo "<a href='index.php'>العودة إلى الصفحة الرئيسية</a>";
} else {
    echo "خطأ: الملف index.php غير موجود!";
}
