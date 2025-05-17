<?php
// Connect to database
try {
    require_once '../includes/db.php';
    $db = Database::getInstance();
    
    // Settings to update
    $settings = [
        'currency' => 'EGP',
        'currency_symbol' => 'L.E',
        'tax_rate' => '15',
        'shipping_cost' => '10',
        'free_shipping_min' => '100',
        'enable_guest_checkout' => '1'
    ];
    
    // Update each setting
    foreach ($settings as $key => $value) {
        $sql = "UPDATE site_settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?";
        $db->query($sql, [$value, $key]);
        echo "تم تحديث {$key} إلى {$value}<br>";
    }
    
    echo "<h3>تم تحديث الإعدادات بنجاح!</h3>";
    echo "<p>العملة: الجنيه المصري (EGP)</p>";
    echo "<p>رمز العملة: L.E</p>";
    echo "<p>نسبة الضريبة المضافة: 15%</p>";
    echo "<p>تكلفة الشحن الافتراضية: 10</p>";
    echo "<p>الحد الأدنى للطلب للحصول على شحن مجاني: 100</p>";
    echo "<p>تمكين الدفع للزوار (بدون تسجيل): نعم</p>";
    
    echo "<br><a href='settings.php' style='background-color: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>العودة إلى صفحة الإعدادات</a>";
    
} catch(Exception $e) {
    echo "حدث خطأ: " . $e->getMessage();
}
?>
