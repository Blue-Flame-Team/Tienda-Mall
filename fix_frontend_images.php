<?php
// تضمين ملف الاتصال بقاعدة البيانات
require_once 'includes/config.php';

// إعداد header لعرض النتائج بشكل صحيح في المتصفح
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إصلاح صور المنتجات في الواجهة الأمامية</title>
    <style>
        body {
            font-family: Arial, Tahoma, sans-serif;
            margin: 20px;
            line-height: 1.6;
            direction: rtl;
        }
        .success { color: green; font-weight: bold; }
        .info { color: blue; }
        .warning { color: orange; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 15px;
            cursor: pointer;
            border: none;
        }
        pre {
            background: #f4f4f4;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
            direction: ltr;
            text-align: left;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>إصلاح صور المنتجات في الواجهة الأمامية</h1>
        
        <?php
        try {
            echo "<h2>1. تحديث ملف index.php</h2>";
            
            $indexFile = __DIR__ . '/index.php';
            $indexContent = file_get_contents($indexFile);
            $changes = [];
            $errors = [];
            
            // 1. إصلاح استعلام SQL للمنتجات الأكثر مبيعًا
            $oldQuery = '/\(SELECT\s+image_path\s+FROM\s+product_images\s+WHERE\s+product_id\s*=\s*p\.product_id\s+AND\s+is_primary\s*=\s*1(\s+AND\s+is_primary\s*=\s*1)*\s+LIMIT\s+1\)\s+as\s+primary_image/';
            $newQuery = '(SELECT image_path FROM product_images WHERE product_id = p.product_id AND is_primary = 1 LIMIT 1) as primary_image';
            
            if (preg_match($oldQuery, $indexContent)) {
                $indexContent = preg_replace($oldQuery, $newQuery, $indexContent, -1, $count);
                if ($count > 0) {
                    $changes[] = "تم إصلاح استعلام SQL للمنتجات الأكثر مبيعًا ({$count} تغيير)";
                }
            } else {
                $errors[] = "لم يتم العثور على استعلام SQL للمنتجات الأكثر مبيعًا";
            }
            
            // 2. التحقق من تضمين ملف image_helper.php
            if (strpos($indexContent, "require_once 'includes/image_helper.php';") === false) {
                $indexContent = preg_replace(
                    '/(require_once\s+\'includes\/config\.php\';)/',
                    "$1\n// Include image helper functions\nrequire_once 'includes/image_helper.php';",
                    $indexContent,
                    1,
                    $count
                );
                
                if ($count > 0) {
                    $changes[] = "تم تضمين ملف image_helper.php";
                } else {
                    $errors[] = "فشل في تضمين ملف image_helper.php";
                }
            } else {
                $changes[] = "ملف image_helper.php مضمن بالفعل";
            }
            
            // 3. استبدال جميع أكواد عرض الصور لاستخدام دالة fix_image_path
            $imgPattern = '/<img src="\/Tienda\/\<\?php echo (!empty\(\$product\[\'primary_image\'\])) \? htmlspecialchars\(\$product\[\'primary_image\'\]\) : \'assets\/images\/product-placeholder\.png\'; \?>" alt=/';
            $newImgCode = '<img src="/Tienda/<?php echo $1 ? htmlspecialchars(fix_image_path($product[\'primary_image\'])) : \'assets/images/product-placeholder.png\'; ?>" alt=';
            
            if (preg_match($imgPattern, $indexContent)) {
                $indexContent = preg_replace($imgPattern, $newImgCode, $indexContent, -1, $count);
                if ($count > 0) {
                    $changes[] = "تم تحديث أكواد عرض الصور لاستخدام دالة fix_image_path ({$count} تغيير)";
                }
            } else {
                $errors[] = "لم يتم العثور على نمط أكواد عرض الصور";
                
                // محاولة استخدام طريقة أخرى للبحث
                $simpleImgPattern = '/<img src="\/Tienda\/.*?\$product\[\'primary_image\'\].*?>/';
                preg_match_all($simpleImgPattern, $indexContent, $matches);
                
                if (!empty($matches[0])) {
                    $errors[] = "تم العثور على " . count($matches[0]) . " صور بنمط مختلف. يرجى التحقق من الكود يدويًا.";
                    
                    foreach ($matches[0] as $index => $match) {
                        if ($index < 3) { // عرض أول 3 أمثلة فقط
                            echo "<pre>" . htmlspecialchars($match) . "</pre>";
                        }
                    }
                }
            }
            
            // 4. إنشاء دالة check_image_exists
            $functionExists = strpos($indexContent, "function check_image_exists(") !== false;
            if (!$functionExists) {
                // إضافة الدالة قبل وسم DOCTYPE
                $docTypePos = strpos($indexContent, "<!DOCTYPE");
                if ($docTypePos !== false) {
                    $functionCode = "
// دالة للتحقق من وجود الصورة
function check_image_exists(\$path) {
    // إزالة '/' من بداية المسار إذا وجدت
    \$path = ltrim(\$path, '/');
    
    // إزالة مسار Tienda إذا كان موجودًا في بداية المسار
    if (strpos(\$path, 'Tienda/') === 0) {
        \$path = substr(\$path, 7);
    }
    
    // إضافة مسار الملف الكامل
    \$fullPath = __DIR__ . '/' . \$path;
    
    return file_exists(\$fullPath);
}

";
                    $indexContent = substr_replace($indexContent, $functionCode, $docTypePos, 0);
                    $changes[] = "تم إضافة دالة check_image_exists للتحقق من وجود الصور";
                } else {
                    $errors[] = "لم يتم العثور على مكان مناسب لإضافة دالة check_image_exists";
                }
            } else {
                $changes[] = "دالة check_image_exists موجودة بالفعل";
            }
            
            // 5. كتابة التغييرات إلى الملف
            if (isset($_POST['apply_changes']) && !empty($changes)) {
                if (file_put_contents($indexFile, $indexContent)) {
                    echo "<p class='success'>تم تطبيق التغييرات على ملف index.php بنجاح!</p>";
                } else {
                    echo "<p class='error'>فشل في كتابة التغييرات إلى ملف index.php</p>";
                }
            }
            
            // 6. تحديث إصلاح الصور في قاعدة البيانات
            echo "<h2>2. إصلاح مسارات الصور في قاعدة البيانات</h2>";
            
            $stmt = $conn->prepare("SELECT COUNT(*) FROM product_images WHERE image_path LIKE '../%'");
            $stmt->execute();
            $count = $stmt->fetchColumn();
            
            echo "<p>عدد الصور التي تحتاج إلى إصلاح: <strong>{$count}</strong></p>";
            
            if ($count > 0 && isset($_POST['fix_paths'])) {
                $stmt = $conn->prepare("UPDATE product_images SET image_path = SUBSTRING(image_path, 4) WHERE image_path LIKE '../%'");
                $stmt->execute();
                $updated = $stmt->rowCount();
                
                echo "<p class='success'>تم إصلاح {$updated} مسار صورة في قاعدة البيانات!</p>";
            }
            
            // 7. حذف الملفات المؤقتة للصور وإعادة إنشائها
            echo "<h2>3. اختبار صور المنتجات</h2>";
            
            // إحضار بعض صور المنتجات للاختبار
            $stmt = $conn->prepare("SELECT p.product_id, p.name, pi.image_path 
                                  FROM products p 
                                  JOIN product_images pi ON p.product_id = pi.product_id 
                                  WHERE pi.is_primary = 1
                                  LIMIT 5");
            $stmt->execute();
            $testImages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($testImages)) {
                echo "<p>عينة من 5 صور منتجات في قاعدة البيانات:</p>";
                echo "<ul>";
                foreach ($testImages as $img) {
                    $imagePath = !empty($img['image_path']) ? $img['image_path'] : 'assets/images/product-placeholder.png';
                    $fixedPath = fix_image_path($imagePath);
                    $fullPath = __DIR__ . '/' . $fixedPath;
                    $exists = file_exists($fullPath);
                    
                    echo "<li>";
                    echo "المنتج: " . htmlspecialchars($img['name']) . "<br>";
                    echo "المسار الأصلي: " . htmlspecialchars($imagePath) . "<br>";
                    echo "المسار المصحح: " . htmlspecialchars($fixedPath) . "<br>";
                    echo "الملف " . ($exists ? "<span class='success'>موجود</span>" : "<span class='error'>غير موجود</span>") . "<br>";
                    
                    if ($exists) {
                        $url = "/Tienda/" . $fixedPath;
                        echo "<img src='{$url}' alt='صورة المنتج' style='max-width:100px; max-height:100px; margin-top:5px;'>";
                    }
                    
                    echo "</li>";
                }
                echo "</ul>";
            } else {
                echo "<p class='warning'>لم يتم العثور على صور منتجات في قاعدة البيانات</p>";
            }
            
            // عرض التغييرات والأخطاء
            if (!empty($changes)) {
                echo "<h2>4. التغييرات التي سيتم تطبيقها:</h2>";
                echo "<ul>";
                foreach ($changes as $change) {
                    echo "<li class='success'>{$change}</li>";
                }
                echo "</ul>";
                
                if (!isset($_POST['apply_changes'])) {
                    echo "<form method='post'>";
                    echo "<button type='submit' name='apply_changes' class='btn'>تطبيق التغييرات على ملف index.php</button>";
                    
                    if ($count > 0) {
                        echo "<button type='submit' name='fix_paths' class='btn' style='margin-right:10px;'>إصلاح مسارات الصور في قاعدة البيانات</button>";
                    }
                    
                    echo "</form>";
                }
            }
            
            if (!empty($errors)) {
                echo "<h2>5. الأخطاء:</h2>";
                echo "<ul>";
                foreach ($errors as $error) {
                    echo "<li class='error'>{$error}</li>";
                }
                echo "</ul>";
            }
            
            // رابط العودة
            echo "<p><a href='index.php' class='btn' style='background-color:#2196F3; margin-top:20px;'>العودة للصفحة الرئيسية</a></p>";
        } catch (PDOException $e) {
            echo "<p class='error'>حدث خطأ أثناء الاتصال بقاعدة البيانات: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        ?>
    </div>
</body>
</html>
<?php
// دالة fix_image_path - نسخة محلية للاختبار
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
?>
