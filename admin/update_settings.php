<?php
// Script to update currency and payment settings

// Database configuration
$host = 'localhost';
$dbname = 'tienda_mall';
$username = 'root';
$password = '';

// Settings to update
$settings = [
    'currency' => 'EGP',
    'currency_symbol' => 'L.E',
    'tax_rate' => '15',
    'shipping_cost' => '10',
    'free_shipping_min' => '100',
    'enable_guest_checkout' => '1'
];

try {
    // Connect to database
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Update each setting
    $stmt = $conn->prepare("UPDATE site_settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?");
    
    foreach ($settings as $key => $value) {
        $stmt->execute([$value, $key]);
        echo "تم تحديث {$key} إلى {$value}<br>";
    }
    
    echo "<h3>تم تحديث الإعدادات بنجاح!</h3>";
    echo "<p>تم تغيير العملة إلى الجنيه المصري (EGP) ورمز العملة إلى L.E</p>";
    echo "<p>تم تحديث نسبة الضريبة المضافة إلى 15%</p>";
    echo "<p>تم تحديث تكلفة الشحن الافتراضية إلى 10</p>";
    echo "<p>تم تحديث الحد الأدنى للطلب للحصول على شحن مجاني إلى 100</p>";
    echo "<p>تم تمكين الدفع للزوار (بدون تسجيل)</p>";
    
    echo "<a href='settings.php'>العودة إلى صفحة الإعدادات</a>";
    
} catch(PDOException $e) {
    echo "حدث خطأ: " . $e->getMessage();
}
?>
