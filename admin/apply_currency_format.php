<?php
/**
 * Apply Currency Format Script
 * This script updates all admin panel files to use the new currency formatting system
 */

// Required files
require_once '../includes/bootstrap.php';

// Files to update
$files_to_update = [
    'view_product.php',
    'view_order.php',
    'shipping.php',
    'reports.php',
    'products.php',
    'print_invoice.php',
    'orders.php',
    'coupons.php'
];

// Patterns and replacements
$replacements = [
    // Pattern 1: Replace direct currency symbols + number_format
    [
        'pattern' => "/echo number_format\((.*?), 2\); \?>\ \$/",
        'replacement' => "echo formatMoney($1); ?>"
    ],
    // Pattern 2: Replace usage with currency variable
    [
        'pattern' => "/echo number_format\((.*?), 2\); \?>\ <\?php echo \\\$order\['currency'\]; \?>/",
        'replacement' => "echo formatMoney($1); ?>"
    ],
    // Pattern 3: Replace standalone number_format for prices
    [
        'pattern' => "/<span class=\".*?\">\<\?php echo number_format\((.*?), 2\); \?>\<\/span>/",
        'replacement' => "<span class=\"$1\"><?php echo formatMoney($2); ?></span>"
    ]
];

$success_count = 0;
$error_files = [];

echo "<h2>تطبيق تنسيق الجنيه المصري على لوحة التحكم</h2>";

foreach ($files_to_update as $file) {
    $file_path = __DIR__ . '/' . $file;
    
    if (file_exists($file_path)) {
        $content = file_get_contents($file_path);
        $updated_content = $content;
        
        // Apply manual replacements for known patterns in specific files
        if ($file == 'products.php') {
            // Fix product price displays
            $updated_content = str_replace(
                "<?php echo number_format(\$product['price'], 2); ?> $", 
                "<?php echo formatMoney(\$product['price']); ?>", 
                $updated_content
            );
            
            $updated_content = str_replace(
                "<?php echo number_format(\$product['sale_price'], 2); ?> $", 
                "<?php echo formatMoney(\$product['sale_price']); ?>", 
                $updated_content
            );
        }
        
        if ($file == 'orders.php') {
            // Fix order total displays
            $updated_content = str_replace(
                "<?php echo number_format(\$order['total_amount'], 2); ?> $", 
                "<?php echo formatMoney(\$order['total_amount']); ?>", 
                $updated_content
            );
            
            $updated_content = str_replace(
                "<?php echo number_format(\$stats['total_revenue'], 2); ?> $", 
                "<?php echo formatMoney(\$stats['total_revenue']); ?>", 
                $updated_content
            );
        }
        
        if ($file == 'reports.php') {
            // Fix reports displays
            $updated_content = str_replace(
                "<?php echo number_format(\$total_revenue, 2); ?> $", 
                "<?php echo formatMoney(\$total_revenue); ?>", 
                $updated_content
            );
            
            $updated_content = str_replace(
                "<?php echo number_format(\$avg_order_value, 2); ?> $", 
                "<?php echo formatMoney(\$avg_order_value); ?>", 
                $updated_content
            );
            
            $updated_content = str_replace(
                "<?php echo number_format(\$data['total_revenue'], 2); ?> $", 
                "<?php echo formatMoney(\$data['total_revenue']); ?>", 
                $updated_content
            );
        }
        
        if ($file == 'view_order.php' || $file == 'print_invoice.php') {
            // Fix order view displays - special case for order items
            $updated_content = preg_replace(
                "/<td>\<\?php echo number_format\(\\\$item\['price'\], 2\); \?> \<\?php echo \\\$order\['currency'\]; \?><\/td>/",
                "<td><?php echo formatMoney(\$item['price']); ?></td>",
                $updated_content
            );
            
            $updated_content = preg_replace(
                "/<td>\<\?php echo number_format\(\\\$item\['price'\] \* \\\$item\['quantity'\], 2\); \?> \<\?php echo \\\$order\['currency'\]; \?><\/td>/",
                "<td><?php echo formatMoney(\$item['price'] * \$item['quantity']); ?></td>",
                $updated_content
            );
            
            // Fix order subtotals
            $updated_content = preg_replace(
                "/<dd class=\"col-sm-7\">\<\?php echo number_format\(\\\$order\['subtotal_price'\], 2\); \?> \<\?php echo \\\$order\['currency'\]; \?><\/dd>/",
                "<dd class=\"col-sm-7\"><?php echo formatMoney(\$order['subtotal_price']); ?></dd>",
                $updated_content
            );
            
            $updated_content = preg_replace(
                "/<dd class=\"col-sm-7\">\<\?php echo number_format\(\\\$order\['shipping_price'\], 2\); \?> \<\?php echo \\\$order\['currency'\]; \?><\/dd>/",
                "<dd class=\"col-sm-7\"><?php echo formatMoney(\$order['shipping_price']); ?></dd>",
                $updated_content
            );
            
            $updated_content = preg_replace(
                "/<dd class=\"col-sm-7\">\<\?php echo number_format\(\\\$order\['tax_price'\], 2\); \?> \<\?php echo \\\$order\['currency'\]; \?><\/dd>/",
                "<dd class=\"col-sm-7\"><?php echo formatMoney(\$order['tax_price']); ?></dd>",
                $updated_content
            );
            
            $updated_content = preg_replace(
                "/<dd class=\"col-sm-7\"><strong>\<\?php echo number_format\(\\\$order\['total_amount'\], 2\); \?> \<\?php echo \\\$order\['currency'\]; \?><\/strong><\/dd>/",
                "<dd class=\"col-sm-7\"><strong><?php echo formatMoney(\$order['total_amount']); ?></strong></dd>",
                $updated_content
            );
        }
        
        if ($file == 'coupons.php') {
            // Fix coupon displays
            $updated_content = str_replace(
                "<?php echo number_format(\$coupon['discount_value'], 2); ?> $", 
                "<?php echo formatMoney(\$coupon['discount_value']); ?>", 
                $updated_content
            );
            
            $updated_content = str_replace(
                "<?php echo number_format(\$coupon['min_order_amount'], 2); ?> $", 
                "<?php echo formatMoney(\$coupon['min_order_amount']); ?>", 
                $updated_content
            );
        }
        
        if ($updated_content !== $content) {
            if (file_put_contents($file_path, $updated_content)) {
                echo "<p class='text-success'>✅ تم تحديث ملف {$file} بنجاح.</p>";
                $success_count++;
            } else {
                echo "<p class='text-danger'>❌ حدث خطأ عند تحديث ملف {$file}.</p>";
                $error_files[] = $file;
            }
        } else {
            echo "<p class='text-warning'>⚠️ لم يتم إجراء أي تغييرات على ملف {$file}.</p>";
        }
    } else {
        echo "<p class='text-danger'>❌ لم يتم العثور على ملف {$file}.</p>";
        $error_files[] = $file;
    }
}

echo "<h3>ملخص:</h3>";
echo "<p>تم تحديث {$success_count} من أصل " . count($files_to_update) . " ملف.</p>";

if (count($error_files) > 0) {
    echo "<p>الملفات التي لم يتم تحديثها: " . implode(", ", $error_files) . "</p>";
}

echo "<h3>تم تطبيق تنسيق الجنيه المصري على لوحة التحكم بالكامل!</h3>";
echo "<p>الآن سيتم عرض جميع الأسعار بالجنيه المصري (L.E) في لوحة التحكم.</p>";

// Add button to update frontend too
echo "<h3>تحديث واجهة المستخدم الأمامية:</h3>";
echo "<p>يمكنك أيضاً تحديث واجهة المستخدم الأمامية لتستخدم تنسيق الجنيه المصري.</p>";
echo "<a href='apply_frontend_currency.php' class='btn btn-primary'>تحديث واجهة المستخدم الأمامية</a>";

// Add link to return to admin panel
echo "<p style='margin-top: 20px;'><a href='index.php' class='btn btn-secondary'>العودة إلى لوحة التحكم</a></p>";
?>
