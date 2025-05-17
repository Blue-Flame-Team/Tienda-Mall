<?php
// قم بتضمين ملف الاتصال بقاعدة البيانات
require_once 'includes/config.php';

// تعيين نوع المحتوى ليدعم اللغة العربية
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إصلاح مسارات الصور</title>
    <style>
        body {
            font-family: Arial, Tahoma, sans-serif;
            margin: 20px;
            line-height: 1.6;
        }
        .success {
            color: green;
            font-weight: bold;
        }
        .info {
            color: blue;
        }
        .error {
            color: red;
            font-weight: bold;
        }
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
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>إصلاح مسارات صور المنتجات</h1>
        <p>هذا البرنامج سيقوم بإصلاح مشكلة مسارات الصور التي تبدأ بـ '../' في قاعدة البيانات</p>
        
        <?php
        try {
            // التحقق من وجود عمود image_path في جدول product_images
            $checkColumn = $conn->query("SHOW COLUMNS FROM product_images LIKE 'image_path'");
            
            if ($checkColumn->rowCount() > 0) {
                // عد عدد الصور التي تحتاج إلى إصلاح
                $stmt = $conn->prepare("SELECT COUNT(*) FROM product_images WHERE image_path LIKE '../%'");
                $stmt->execute();
                $total = $stmt->fetchColumn();
                
                echo "<p>عدد الصور التي تحتاج إلى إصلاح: <strong>{$total}</strong></p>";
                
                if ($total > 0) {
                    if (isset($_POST['fix_images'])) {
                        // تحديث جميع مسارات الصور بإزالة '../' من البداية
                        $stmt = $conn->prepare("UPDATE product_images SET image_path = SUBSTRING(image_path, 4) WHERE image_path LIKE '../%'");
                        $stmt->execute();
                        $updated = $stmt->rowCount();
                        
                        echo "<div class='success'>تم إصلاح {$updated} صورة بنجاح!</div>";
                        echo "<p>يمكنك الآن العودة للصفحة الرئيسية وتحديثها لمشاهدة الصور بشكل صحيح.</p>";
                    } else {
                        echo "<form method='post'>";
                        echo "<p>انقر على الزر أدناه لإصلاح مسارات الصور:</p>";
                        echo "<button type='submit' name='fix_images' class='btn'>إصلاح مسارات الصور</button>";
                        echo "</form>";
                    }
                } else {
                    echo "<div class='info'>لا توجد مسارات صور تحتاج إلى إصلاح.</div>";
                }
                
                // عرض بعض الأمثلة على مسارات الصور في قاعدة البيانات
                echo "<h2>أمثلة على مسارات الصور الحالية:</h2>";
                $stmt = $conn->prepare("SELECT product_id, image_path FROM product_images LIMIT 10");
                $stmt->execute();
                $examples = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (count($examples) > 0) {
                    echo "<ul>";
                    foreach ($examples as $example) {
                        echo "<li>منتج رقم: {$example['product_id']} - مسار الصورة: {$example['image_path']}</li>";
                    }
                    echo "</ul>";
                } else {
                    echo "<p class='info'>لا توجد صور للمنتجات في قاعدة البيانات.</p>";
                }
            } else {
                echo "<div class='error'>لا يوجد عمود 'image_path' في جدول product_images. تحقق من هيكلية قاعدة البيانات.</div>";
            }
        } catch (PDOException $e) {
            echo "<div class='error'>خطأ في قاعدة البيانات: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
        ?>
        
        <h2>الخطوة التالية: استخدام دالة fix_image_path</h2>
        <p>لقد تم تضمين ملف 'image_helper.php' في الملف 'index.php' بالفعل. هذا الملف يحتوي على دالة 'fix_image_path' التي ستقوم تلقائيًا بإصلاح مسارات الصور التي تبدأ بـ '../'.</p>
        
        <p>تأكد من استخدام هذه الدالة في أي مكان تعرض فيه صور المنتجات. مثال:</p>
        <pre dir="ltr" style="background: #f4f4f4; padding: 10px; border-radius: 5px; text-align: left;">
&lt;img src="/Tienda/&lt;?php echo htmlspecialchars(fix_image_path($product['image_path'])); ?&gt;" alt="..."&gt;
        </pre>
        
        <p><a href="index.php" class="btn">العودة للصفحة الرئيسية</a></p>
    </div>
</body>
</html>
