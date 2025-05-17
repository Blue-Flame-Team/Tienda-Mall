<?php
// Include database connection
require_once 'includes/config.php';
require_once 'includes/image_helper.php';

// Set content type for Arabic support
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إصلاح جميع مسارات الصور</title>
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
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
        <h1>إصلاح جميع مسارات الصور</h1>
        <p>هذا البرنامج سيقوم بإصلاح جميع مشاكل مسارات الصور في قاعدة البيانات</p>
        
        <?php
        try {
            $issues = array();
            $fixed = array();
            
            // Check if product_images table exists
            $checkTable = $conn->query("SHOW TABLES LIKE 'product_images'");
            if ($checkTable->rowCount() == 0) {
                echo "<div class='error'>جدول 'product_images' غير موجود في قاعدة البيانات.</div>";
            } else {
                // Check for image_url column (primary image URL column)
                $checkImageUrl = $conn->query("SHOW COLUMNS FROM product_images LIKE 'image_url'");
                if ($checkImageUrl->rowCount() > 0) {
                    // Check for problematic paths
                    $stmt = $conn->prepare("SELECT id, product_id, image_url FROM product_images WHERE 
                        image_url LIKE '../%' OR
                        image_url LIKE '/Tienda/%' OR
                        image_url LIKE 'http://localhost/Tienda/http://%'");
                    $stmt->execute();
                    $problems = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (count($problems) > 0) {
                        $issues['image_url'] = $problems;
                        echo "<p>تم العثور على " . count($problems) . " مسار خاطئ في حقل image_url.</p>";
                    } else {
                        echo "<p class='info'>لا توجد مشاكل في حقل image_url.</p>";
                    }
                } else {
                    echo "<div class='info'>عمود 'image_url' غير موجود في جدول product_images.</div>";
                }
                
                // Check for image_path column if it exists
                $checkImagePath = $conn->query("SHOW COLUMNS FROM product_images LIKE 'image_path'");
                if ($checkImagePath->rowCount() > 0) {
                    $stmt = $conn->prepare("SELECT id, product_id, image_path FROM product_images WHERE 
                        image_path LIKE '../%' OR
                        image_path LIKE '/Tienda/%' OR
                        image_path LIKE 'http://localhost/Tienda/http://%'");
                    $stmt->execute();
                    $problems = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (count($problems) > 0) {
                        $issues['image_path'] = $problems;
                        echo "<p>تم العثور على " . count($problems) . " مسار خاطئ في حقل image_path.</p>";
                    } else {
                        echo "<p class='info'>لا توجد مشاكل في حقل image_path.</p>";
                    }
                }
                
                // Fix issues if the form is submitted
                if (isset($_POST['fix_all_paths']) && !empty($issues)) {
                    $conn->beginTransaction();
                    
                    try {
                        // Fix image_url issues
                        if (isset($issues['image_url'])) {
                            foreach ($issues['image_url'] as $item) {
                                $oldPath = $item['image_url'];
                                $newPath = fix_image_path($oldPath);
                                
                                $stmt = $conn->prepare("UPDATE product_images SET image_url = :new_path WHERE id = :id");
                                $stmt->bindParam(':new_path', $newPath);
                                $stmt->bindParam(':id', $item['id']);
                                $stmt->execute();
                                
                                $fixed[] = array(
                                    'id' => $item['id'],
                                    'product_id' => $item['product_id'],
                                    'old_path' => $oldPath,
                                    'new_path' => $newPath
                                );
                            }
                        }
                        
                        // Fix image_path issues if the column exists
                        if (isset($issues['image_path'])) {
                            foreach ($issues['image_path'] as $item) {
                                $oldPath = $item['image_path'];
                                $newPath = fix_image_path($oldPath);
                                
                                $stmt = $conn->prepare("UPDATE product_images SET image_path = :new_path WHERE id = :id");
                                $stmt->bindParam(':new_path', $newPath);
                                $stmt->bindParam(':id', $item['id']);
                                $stmt->execute();
                                
                                $fixed[] = array(
                                    'id' => $item['id'],
                                    'product_id' => $item['product_id'],
                                    'old_path' => $oldPath,
                                    'new_path' => $newPath
                                );
                            }
                        }
                        
                        $conn->commit();
                        echo "<div class='success'>تم إصلاح " . count($fixed) . " مسار صورة بنجاح!</div>";
                    } catch (Exception $e) {
                        $conn->rollBack();
                        echo "<div class='error'>حدث خطأ أثناء تحديث قاعدة البيانات: " . htmlspecialchars($e->getMessage()) . "</div>";
                    }
                }
                
                // Display form if there are issues to fix
                if (!empty($issues) && empty($fixed)) {
                    echo "<form method='post'>";
                    echo "<button type='submit' name='fix_all_paths' class='btn'>إصلاح جميع مسارات الصور</button>";
                    echo "</form>";
                }
                
                // Display fixed paths if any
                if (!empty($fixed)) {
                    echo "<h2>المسارات التي تم إصلاحها:</h2>";
                    echo "<table>";
                    echo "<tr><th>معرف</th><th>معرف المنتج</th><th>المسار القديم</th><th>المسار الجديد</th></tr>";
                    foreach ($fixed as $item) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($item['id']) . "</td>";
                        echo "<td>" . htmlspecialchars($item['product_id']) . "</td>";
                        echo "<td>" . htmlspecialchars($item['old_path']) . "</td>";
                        echo "<td>" . htmlspecialchars($item['new_path']) . "</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                }
            }
            
        } catch (PDOException $e) {
            echo "<div class='error'>خطأ في قاعدة البيانات: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
        ?>
        
        <h2>الخطوات التي تم تنفيذها لإصلاح مشاكل الصور:</h2>
        <ol>
            <li>تم إنشاء دالة <code>fix_image_path()</code> في ملف <code>image_helper.php</code> لإصلاح مسارات الصور</li>
            <li>تم إضافة ملف <code>cart_fix.js</code> لإصلاح مشاكل الصور في سلة التسوق</li>
            <li>تم إضافة ملف <code>image_path_fix.js</code> لإصلاح جميع مسارات الصور في واجهة المستخدم</li>
            <li>تم تحديث طريقة التعامل مع روابط الصور في ملفات الجافاسكربت</li>
            <li>هذا البرنامج يقوم بإصلاح مسارات الصور في قاعدة البيانات</li>
        </ol>
        
        <p><a href="index.php" class="btn">العودة للصفحة الرئيسية</a></p>
    </div>
</body>
</html>
