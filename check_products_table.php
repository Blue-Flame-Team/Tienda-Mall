<?php
// Database configuration
$host = 'localhost';
$dbname = 'tienda_mall';
$username = 'root';
$password = '';

header('Content-Type: text/html; charset=utf-8');
echo "<h1>بنية جدول المنتجات الحالية</h1>";

try {
    // Connect to the database
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get the current structure of the products table
    $stmt = $conn->query("DESCRIBE products");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>أعمدة جدول المنتجات الحالية:</h2>";
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>اسم العمود</th><th>النوع</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "<td>{$column['Extra']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Add the missing sale_price column with proper structure
    echo "<h2>إضافة عمود sale_price:</h2>";
    
    try {
        $conn->exec("ALTER TABLE products ADD COLUMN sale_price DECIMAL(10,2) DEFAULT NULL AFTER price");
        echo "<p style='color:green;'>✓ تم إضافة عمود sale_price بنجاح!</p>";
    } catch (PDOException $columnError) {
        if (strpos($columnError->getMessage(), "Duplicate column") !== false) {
            echo "<p style='color:blue;'>العمود sale_price موجود بالفعل.</p>";
        } else {
            echo "<p style='color:red;'>خطأ في إضافة العمود: {$columnError->getMessage()}</p>";
        }
    }
    
    // Check which files reference sale_price
    echo "<h2>الملفات التي تستخدم sale_price:</h2>";
    
    function searchInFiles($directory, $search) {
        $results = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
        
        foreach ($iterator as $file) {
            if ($file->isFile() && pathinfo($file->getFilename(), PATHINFO_EXTENSION) === 'php') {
                $content = file_get_contents($file->getPathname());
                if (stripos($content, $search) !== false) {
                    $results[] = str_replace(realpath($directory) . '/', '', $file->getPathname());
                }
            }
        }
        
        return $results;
    }
    
    $files = searchInFiles(__DIR__, 'sale_price');
    
    if (count($files) > 0) {
        echo "<ul>";
        foreach ($files as $file) {
            echo "<li>{$file}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>لم يتم العثور على ملفات تستخدم sale_price.</p>";
    }
    
    // Now let's look at the index.php file to find the specific query causing the issue
    echo "<h2>تحليل ملف index.php:</h2>";
    
    $indexContent = file_get_contents('index.php');
    preg_match_all('/sale_price/', $indexContent, $matches, PREG_OFFSET_CAPTURE);
    
    echo "<p>تم العثور على {$matches[0]} مرجع لـ sale_price في ملف index.php</p>";
    
    echo "<h2>الحل النهائي:</h2>";
    echo "<p>قم بنسخ الاستعلام التالي وتنفيذه في phpMyAdmin:</p>";
    echo "<div style='background:#f5f5f5; padding:10px; border:1px solid #ddd;'>";
    echo "<pre>ALTER TABLE products ADD COLUMN sale_price DECIMAL(10,2) DEFAULT NULL AFTER price;</pre>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<h2 style='color:red;'>حدث خطأ في الاتصال بقاعدة البيانات:</h2>";
    echo "<p>{$e->getMessage()}</p>";
}
