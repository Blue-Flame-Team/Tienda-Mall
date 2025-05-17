<?php
/**
 * Apply Currency Format to Frontend
 * This script updates all frontend files to use the new Egyptian Pound currency formatting
 */

// Required files
require_once '../includes/bootstrap.php';

// Files to update
$files_to_update = [
    '../index.php',
    '../pages/ProductDetails.php',
    '../pages/cart.php',
    '../pages/checkout.php',
    '../pages/orders.php',
    '../pages/user-orders.php'
];

$success_count = 0;
$error_files = [];

echo "<h2>تطبيق تنسيق الجنيه المصري على واجهة المستخدم الأمامية</h2>";

foreach ($files_to_update as $file) {
    $file_path = realpath(__DIR__ . '/' . $file);
    
    if (file_exists($file_path)) {
        $content = file_get_contents($file_path);
        $updated_content = $content;
        
        // Apply replacements for different files
        if (basename($file) == 'index.php') {
            // Update homepage product pricing
            $updated_content = preg_replace(
                "/<span class=\"new\">\\\$(.*?)<\/span>/",
                "<span class=\"new\"><?php echo formatMoney($1); ?></span>",
                $updated_content
            );
            
            $updated_content = preg_replace(
                "/<span class=\"old\">\\\$(.*?)<\/span>/",
                "<span class=\"old\"><?php echo formatMoney($1); ?></span>",
                $updated_content
            );
            
            $updated_content = preg_replace(
                "/<span class=\"price\">\\\$(.*?)<\/span>/",
                "<span class=\"price\"><?php echo formatMoney($1); ?></span>",
                $updated_content
            );
        }
        
        if (basename($file) == 'ProductDetails.php') {
            // Update product details page pricing
            $updated_content = preg_replace(
                "/<span class=\"price\">\\\$\{\{price\}\}<\/span>/",
                "<span class=\"price\"><?php echo formatMoney({{price}}); ?></span>",
                $updated_content
            );
            
            $updated_content = str_replace(
                "$<?php echo number_format(\$product['price'], 2); ?>",
                "<?php echo formatMoney(\$product['price']); ?>",
                $updated_content
            );
            
            $updated_content = str_replace(
                "$<?php echo number_format(\$discountPrice, 2); ?>",
                "<?php echo formatMoney(\$discountPrice); ?>",
                $updated_content
            );
        }
        
        if (basename($file) == 'cart.php' || basename($file) == 'checkout.php') {
            // Update cart and checkout pricing
            $updated_content = str_replace(
                "$<?php echo number_format(\$item['price'], 2); ?>",
                "<?php echo formatMoney(\$item['price']); ?>",
                $updated_content
            );
            
            $updated_content = str_replace(
                "$<?php echo number_format(\$subtotal, 2); ?>",
                "<?php echo formatMoney(\$subtotal); ?>",
                $updated_content
            );
            
            $updated_content = str_replace(
                "$<?php echo number_format(\$total, 2); ?>",
                "<?php echo formatMoney(\$total); ?>",
                $updated_content
            );
            
            $updated_content = str_replace(
                "$<?php echo number_format(\$tax, 2); ?>",
                "<?php echo formatMoney(\$tax); ?>",
                $updated_content
            );
            
            $updated_content = str_replace(
                "$<?php echo number_format(\$shipping, 2); ?>",
                "<?php echo formatMoney(\$shipping); ?>",
                $updated_content
            );
        }
        
        if (basename($file) == 'orders.php' || basename($file) == 'user-orders.php') {
            // Update orders display
            $updated_content = str_replace(
                "$<?php echo number_format(\$order['total_amount'], 2); ?>",
                "<?php echo formatMoney(\$order['total_amount']); ?>",
                $updated_content
            );
        }
        
        // Update JavaScript currency formatting
        if (strpos($updated_content, '<script') !== false) {
            // Add JavaScript function to format currency
            $js_function = "
<script>
// Format currency to Egyptian Pound
function formatCurrency(amount) {
    return parseFloat(amount).toFixed(2) + ' L.E';
}
</script>";
            
            // Add the function before the closing body tag if it doesn't exist
            if (strpos($updated_content, 'function formatCurrency') === false) {
                $updated_content = str_replace('</body>', $js_function . "\n</body>", $updated_content);
            }
            
            // Replace inline dollar sign formatting in JavaScript
            $updated_content = preg_replace(
                "/price: '\\\$' \+ (.*?),/",
                "price: formatCurrency($1),",
                $updated_content
            );
            
            $updated_content = preg_replace(
                "/\\\$\" \+ (.*?) \+ \"/",
                "\" + formatCurrency($1) + \"",
                $updated_content
            );
        }
        
        if ($updated_content !== $content) {
            if (file_put_contents($file_path, $updated_content)) {
                echo "<p class='text-success'>✅ تم تحديث ملف " . basename($file) . " بنجاح.</p>";
                $success_count++;
            } else {
                echo "<p class='text-danger'>❌ حدث خطأ عند تحديث ملف " . basename($file) . ".</p>";
                $error_files[] = $file;
            }
        } else {
            echo "<p class='text-warning'>⚠️ لم يتم إجراء أي تغييرات على ملف " . basename($file) . ".</p>";
        }
    } else {
        echo "<p class='text-danger'>❌ لم يتم العثور على ملف " . basename($file) . ".</p>";
        $error_files[] = $file;
    }
}

// Now let's update JavaScript files directly
$js_files = [
    '../scripts/cart.js',
    '../scripts/tienda-cart.js',
    '../scripts/product-details.js'
];

foreach ($js_files as $file) {
    $file_path = realpath(__DIR__ . '/' . $file);
    
    if (file_exists($file_path)) {
        $content = file_get_contents($file_path);
        $updated_content = $content;
        
        // Add formatCurrency function to the top of the file if it doesn't exist
        if (strpos($updated_content, 'function formatCurrency') === false) {
            $format_function = "
// Format currency to Egyptian Pound
function formatCurrency(amount) {
    return parseFloat(amount).toFixed(2) + ' L.E';
}
";
            $updated_content = $format_function . $updated_content;
        }
        
        // Replace dollar sign formatting
        $updated_content = preg_replace(
            "/\\\$\" \+ (.*?) \+ \"/",
            "\" + formatCurrency($1) + \"",
            $updated_content
        );
        
        $updated_content = preg_replace(
            "/\\\$\" \+ parseFloat\((.*?)\)\.toFixed\(2\) \+ \"/",
            "\" + formatCurrency($1) + \"",
            $updated_content
        );
        
        if ($updated_content !== $content) {
            if (file_put_contents($file_path, $updated_content)) {
                echo "<p class='text-success'>✅ تم تحديث ملف " . basename($file) . " بنجاح.</p>";
                $success_count++;
            } else {
                echo "<p class='text-danger'>❌ حدث خطأ عند تحديث ملف " . basename($file) . ".</p>";
                $error_files[] = $file;
            }
        } else {
            echo "<p class='text-warning'>⚠️ لم يتم إجراء أي تغييرات على ملف " . basename($file) . ".</p>";
        }
    } else {
        echo "<p class='text-danger'>❌ لم يتم العثور على ملف " . basename($file) . ".</p>";
        $error_files[] = $file;
    }
}

echo "<h3>ملخص:</h3>";
echo "<p>تم تحديث {$success_count} من أصل " . (count($files_to_update) + count($js_files)) . " ملف.</p>";

if (count($error_files) > 0) {
    echo "<p>الملفات التي لم يتم تحديثها: " . implode(", ", $error_files) . "</p>";
}

echo "<h3>تم تطبيق تنسيق الجنيه المصري على واجهة المستخدم الأمامية بالكامل!</h3>";
echo "<p>الآن سيتم عرض جميع الأسعار بالجنيه المصري (L.E) في الموقع بالكامل.</p>";

// Add link to check settings page
echo "<a href='settings.php' class='btn btn-primary'>الانتقال لصفحة الإعدادات</a>&nbsp;";
echo "<a href='index.php' class='btn btn-secondary'>العودة إلى لوحة التحكم</a>";

// Create an update button for product database
echo "<hr>";
echo "<h3>تحديث قاعدة البيانات:</h3>";
echo "<p>يمكنك أيضاً تحديث قاعدة البيانات لتحويل كل أسعار المنتجات من الدولار إلى الجنيه المصري.</p>";
echo "<form method='post'>";
echo "<input type='hidden' name='update_currency' value='1'>";
echo "<button type='submit' class='btn btn-warning'>تحويل الأسعار من الدولار إلى الجنيه المصري</button>";
echo "</form>";

// Process currency conversion if requested
if (isset($_POST['update_currency'])) {
    try {
        $db = Database::getInstance();
        
        // Get current exchange rate from settings (default to 31 EGP per USD if not set)
        $exchange_rate = 31;
        $stmt = $db->query("SELECT setting_value FROM site_settings WHERE setting_key = 'usd_to_egp_rate' LIMIT 1");
        $rate_result = $stmt->fetch();
        if ($rate_result) {
            $exchange_rate = floatval($rate_result['setting_value']);
        } else {
            // Create the setting if it doesn't exist
            $db->query("INSERT INTO site_settings (setting_key, setting_value, setting_description) VALUES ('usd_to_egp_rate', '31', 'سعر تحويل الدولار إلى الجنيه المصري')");
        }
        
        // Update all product prices
        $db->query("UPDATE product SET price = price * {$exchange_rate}, sale_price = CASE WHEN sale_price > 0 THEN sale_price * {$exchange_rate} ELSE 0 END");
        
        echo "<div class='alert alert-success mt-3'>تم تحويل أسعار المنتجات بنجاح من الدولار إلى الجنيه المصري باستخدام سعر التحويل: {$exchange_rate}</div>";
    } catch (Exception $e) {
        echo "<div class='alert alert-danger mt-3'>حدث خطأ أثناء تحويل الأسعار: " . $e->getMessage() . "</div>";
    }
}
?>
