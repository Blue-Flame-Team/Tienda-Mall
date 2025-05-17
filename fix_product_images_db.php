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
    <title>إصلاح جدول صور المنتجات</title>
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 8px;
            text-align: right;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>إصلاح جدول صور المنتجات</h1>
        <p>هذا البرنامج سيقوم بإصلاح مشاكل صور المنتجات في قاعدة البيانات</p>
        
        <?php
        try {
            // 1. التحقق من بنية جدول product_images
            echo "<h2>1. التحقق من هيكل جدول صور المنتجات</h2>";
            
            // استخراج أسماء الأعمدة من الجدول
            $result = $conn->query("SHOW COLUMNS FROM product_images");
            $columns = $result->fetchAll(PDO::FETCH_COLUMN);
            
            echo "<p>الأعمدة الموجودة حالياً: " . implode(", ", $columns) . "</p>";
            
            $hasImagePath = in_array('image_path', $columns);
            $hasImageUrl = in_array('image_url', $columns);
            $hasIsPrimary = in_array('is_primary', $columns);
            $hasIsMain = in_array('is_main', $columns);
            
            // إصلاح مشاكل أسماء الأعمدة
            $columnsToFix = [];
            
            // 1. التحقق من عمود image_path و image_url
            if ($hasImagePath && $hasImageUrl) {
                echo "<p class='info'>كلا العمودين image_path و image_url موجودان.</p>";
                
                // نقوم بنقل البيانات من image_url إلى image_path إذا لم تكن موجودة بالفعل
                if (isset($_POST['fix_columns'])) {
                    $conn->exec("UPDATE product_images SET image_path = image_url WHERE image_path IS NULL OR image_path = ''");
                    echo "<p class='success'>تم نقل البيانات من image_url إلى image_path.</p>";
                } else {
                    $columnsToFix[] = "نقل البيانات من image_url إلى image_path";
                }
            } else if ($hasImageUrl && !$hasImagePath) {
                echo "<p class='warning'>يوجد عمود image_url ولكن لا يوجد عمود image_path.</p>";
                
                if (isset($_POST['fix_columns'])) {
                    $conn->exec("ALTER TABLE product_images ADD COLUMN image_path VARCHAR(255)");
                    $conn->exec("UPDATE product_images SET image_path = image_url");
                    echo "<p class='success'>تم إضافة عمود image_path ونقل البيانات من image_url.</p>";
                } else {
                    $columnsToFix[] = "إضافة عمود image_path ونقل البيانات من image_url";
                }
            } else if (!$hasImageUrl && !$hasImagePath) {
                echo "<p class='error'>لا يوجد أي من العمودين image_path أو image_url!</p>";
                
                if (isset($_POST['fix_columns'])) {
                    $conn->exec("ALTER TABLE product_images ADD COLUMN image_path VARCHAR(255)");
                    echo "<p class='success'>تم إضافة عمود image_path.</p>";
                } else {
                    $columnsToFix[] = "إضافة عمود image_path";
                }
            } else {
                echo "<p class='success'>عمود image_path موجود بالفعل.</p>";
            }
            
            // 2. التحقق من عمود is_primary و is_main
            if ($hasIsPrimary && $hasIsMain) {
                echo "<p class='info'>كلا العمودين is_primary و is_main موجودان.</p>";
                
                if (isset($_POST['fix_columns'])) {
                    // التأكد من تطابق القيم بين العمودين
                    $conn->exec("UPDATE product_images SET is_primary = is_main WHERE is_primary != is_main");
                    echo "<p class='success'>تم مزامنة البيانات بين is_primary و is_main.</p>";
                } else {
                    $columnsToFix[] = "مزامنة البيانات بين is_primary و is_main";
                }
            } else if ($hasIsMain && !$hasIsPrimary) {
                echo "<p class='warning'>يوجد عمود is_main ولكن لا يوجد عمود is_primary.</p>";
                
                if (isset($_POST['fix_columns'])) {
                    $conn->exec("ALTER TABLE product_images ADD COLUMN is_primary TINYINT(1) DEFAULT 0");
                    $conn->exec("UPDATE product_images SET is_primary = is_main");
                    echo "<p class='success'>تم إضافة عمود is_primary ونقل البيانات من is_main.</p>";
                } else {
                    $columnsToFix[] = "إضافة عمود is_primary ونقل البيانات من is_main";
                }
            } else if (!$hasIsMain && !$hasIsPrimary) {
                echo "<p class='error'>لا يوجد أي من العمودين is_primary أو is_main!</p>";
                
                if (isset($_POST['fix_columns'])) {
                    $conn->exec("ALTER TABLE product_images ADD COLUMN is_primary TINYINT(1) DEFAULT 0");
                    // تعيين أول صورة لكل منتج كصورة رئيسية
                    $conn->exec("
                        UPDATE product_images pi1
                        INNER JOIN (
                            SELECT product_id, MIN(image_id) as min_id
                            FROM product_images
                            GROUP BY product_id
                        ) pi2 ON pi1.product_id = pi2.product_id AND pi1.image_id = pi2.min_id
                        SET pi1.is_primary = 1
                    ");
                    echo "<p class='success'>تم إضافة عمود is_primary وتعيين الصور الرئيسية.</p>";
                } else {
                    $columnsToFix[] = "إضافة عمود is_primary وتعيين الصور الرئيسية";
                }
            } else if ($hasIsPrimary && !$hasIsMain) {
                echo "<p class='info'>عمود is_primary موجود بالفعل.</p>";
                
                if (isset($_POST['fix_columns'])) {
                    $conn->exec("ALTER TABLE product_images ADD COLUMN is_main TINYINT(1) DEFAULT 0");
                    $conn->exec("UPDATE product_images SET is_main = is_primary");
                    echo "<p class='success'>تم إضافة عمود is_main ونسخ البيانات من is_primary.</p>";
                } else {
                    $columnsToFix[] = "إضافة عمود is_main ونسخ البيانات من is_primary";
                }
            }
            
            // 3. إصلاح مسارات الصور التي تبدأ بـ '../'
            echo "<h2>2. التحقق من مسارات الصور</h2>";
            
            if ($hasImagePath) {
                $query = "SELECT COUNT(*) FROM product_images WHERE image_path LIKE '../%'";
            } else if ($hasImageUrl) {
                $query = "SELECT COUNT(*) FROM product_images WHERE image_url LIKE '../%'";
            } else {
                $query = "SELECT 0";
            }
            
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $count = $stmt->fetchColumn();
            
            echo "<p>عدد الصور التي تحتاج إلى إصلاح: <strong>{$count}</strong></p>";
            
            if ($count > 0) {
                if (isset($_POST['fix_paths'])) {
                    if ($hasImagePath) {
                        $conn->exec("UPDATE product_images SET image_path = SUBSTRING(image_path, 4) WHERE image_path LIKE '../%'");
                    }
                    
                    if ($hasImageUrl) {
                        $conn->exec("UPDATE product_images SET image_url = SUBSTRING(image_url, 4) WHERE image_url LIKE '../%'");
                    }
                    
                    echo "<p class='success'>تم إصلاح {$count} مسار صورة.</p>";
                } else {
                    echo "<p class='warning'>توجد صور تحتاج إلى إصلاح المسارات.</p>";
                }
            } else {
                echo "<p class='success'>جميع مسارات الصور صحيحة.</p>";
            }
            
            // 4. عرض عينة من الصور
            echo "<h2>3. عينة من بيانات الصور</h2>";
            
            $query = "SELECT * FROM product_images LIMIT 10";
            $images = $conn->query($query)->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($images) > 0) {
                echo "<table>";
                echo "<tr>";
                foreach (array_keys($images[0]) as $header) {
                    echo "<th>{$header}</th>";
                }
                echo "</tr>";
                
                foreach ($images as $image) {
                    echo "<tr>";
                    foreach ($image as $key => $value) {
                        echo "<td>" . htmlspecialchars($value) . "</td>";
                    }
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p class='info'>لا توجد صور في قاعدة البيانات.</p>";
            }
            
            // 5. تحديث ملف index.php لاستخدام الأعمدة الصحيحة
            echo "<h2>4. تحديث index.php</h2>";
            
            $indexFile = __DIR__ . '/index.php';
            $indexContent = file_get_contents($indexFile);
            
            $sqlPattern = '/\(SELECT\s+[a-zA-Z_]+\s+FROM\s+product_images\s+WHERE\s+product_id\s*=\s*p\.product_id\s+AND\s+[a-zA-Z_]+\s*=\s*1.*?\)\s+as\s+primary_image/';
            
            // عرض نتيجة الاستعلام SQL في ملف index.php
            preg_match($sqlPattern, $indexContent, $matches);
            
            if (!empty($matches)) {
                echo "<p>الاستعلام الحالي في index.php:</p>";
                echo "<pre style='background:#f4f4f4;padding:10px;border-radius:4px;direction:ltr;text-align:left'>" . htmlspecialchars($matches[0]) . "</pre>";
                
                if (isset($_POST['fix_query'])) {
                    // تحديث الاستعلام ليستخدم الأعمدة الصحيحة
                    $newQuery = "(SELECT image_path FROM product_images WHERE product_id = p.product_id AND is_primary = 1 LIMIT 1) as primary_image";
                    $newContent = preg_replace($sqlPattern, $newQuery, $indexContent);
                    
                    if ($newContent !== $indexContent) {
                        file_put_contents($indexFile, $newContent);
                        echo "<p class='success'>تم تحديث الاستعلام في ملف index.php لاستخدام الأعمدة الصحيحة.</p>";
                    } else {
                        echo "<p class='warning'>لم يتم تطبيق أي تغييرات على ملف index.php.</p>";
                    }
                } else {
                    echo "<p>الاستعلام المقترح:</p>";
                    echo "<pre style='background:#f4f4f4;padding:10px;border-radius:4px;direction:ltr;text-align:left'>(SELECT image_path FROM product_images WHERE product_id = p.product_id AND is_primary = 1 LIMIT 1) as primary_image</pre>";
                }
            } else {
                echo "<p class='warning'>لم يتم العثور على الاستعلام في index.php.</p>";
            }
            
            // 6. نموذج لإصلاح المشاكل
            if (!empty($columnsToFix) || $count > 0 || !empty($matches)) {
                echo "<h2>5. تطبيق الإصلاحات</h2>";
                echo "<form action='' method='post'>";
                
                if (!empty($columnsToFix)) {
                    echo "<p><button type='submit' name='fix_columns' class='btn'>إصلاح هيكل جدول product_images</button></p>";
                }
                
                if ($count > 0) {
                    echo "<p><button type='submit' name='fix_paths' class='btn'>إصلاح مسارات الصور</button></p>";
                }
                
                if (!empty($matches)) {
                    echo "<p><button type='submit' name='fix_query' class='btn'>تحديث استعلام SQL في index.php</button></p>";
                }
                
                echo "</form>";
            } else {
                echo "<h2 class='success'>تهانينا! تم إصلاح جميع المشاكل.</h2>";
            }
            
        } catch (PDOException $e) {
            echo "<p class='error'>حدث خطأ أثناء الاتصال بقاعدة البيانات: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        ?>
        
        <p><a href="index.php" class="btn" style="background-color: #2196F3;">العودة للصفحة الرئيسية</a></p>
    </div>
</body>
</html>
