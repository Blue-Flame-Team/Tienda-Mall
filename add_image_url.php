<?php
// إضافة عمود image_url إلى جدول المنتجات
// Database configuration
$host = 'localhost';
$dbname = 'tienda_mall';
$username = 'root';
$password = '';

header('Content-Type: text/html; charset=utf-8');
echo "<h1 style='font-family: Arial, sans-serif;'>إصلاح قاعدة البيانات</h1>";

try {
    // الاتصال بقاعدة البيانات
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // إضافة عمود image_url
    $conn->exec("ALTER TABLE products ADD COLUMN image_url VARCHAR(255) DEFAULT NULL");
    
    echo "<p style='color:green; font-family: Arial, sans-serif;'>تم إضافة عمود image_url بنجاح إلى جدول المنتجات.</p>";
    
    // التحقق من وجود جدول product_images وربط الصور بالمنتجات إذا كان موجودًا
    $tables = $conn->query("SHOW TABLES LIKE 'product_images'");
    
    if ($tables->rowCount() > 0) {
        // تحديث روابط الصور في جدول المنتجات
        $conn->exec(
            "UPDATE products p 
             LEFT JOIN (
                 SELECT product_id, image_url FROM product_images WHERE is_main = 1 LIMIT 1
             ) pi ON p.product_id = pi.product_id
             SET p.image_url = pi.image_url WHERE pi.image_url IS NOT NULL"
        );
        
        echo "<p style='color:green; font-family: Arial, sans-serif;'>تم تحديث المنتجات بروابط الصور من جدول product_images.</p>";
    }
    
    echo "<p style='font-family: Arial, sans-serif;'>تم إصلاح قاعدة البيانات بنجاح! يمكنك الآن العودة إلى <a href='index.php'>الصفحة الرئيسية</a>.</p>";
    
} catch (PDOException $e) {
    // إذا كان العمود موجودًا بالفعل، فهذا ليس خطأً
    if (strpos($e->getMessage(), "Duplicate column name") !== false) {
        echo "<p style='color:blue; font-family: Arial, sans-serif;'>عمود image_url موجود بالفعل في قاعدة البيانات.</p>";
        echo "<p style='font-family: Arial, sans-serif;'>يمكنك العودة إلى <a href='index.php'>الصفحة الرئيسية</a>.</p>";
    } else {
        echo "<p style='color:red; font-family: Arial, sans-serif;'>حدث خطأ: " . $e->getMessage() . "</p>";
    }
}
