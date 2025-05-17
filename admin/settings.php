<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin'])) {
    header('Location: login.php');
    exit;
}

// Get admin data
$admin = $_SESSION['admin'];

// Database configuration
$host = 'localhost';
$dbname = 'tienda_mall';
$username = 'root';
$password = '';

// Initialize variables
$settings = [];
$error = '';
$success = '';

// Get current settings
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if settings table exists
    $tables = $conn->query("SHOW TABLES LIKE 'site_settings'")->fetchAll();
    if (count($tables) == 0) {
        // Create settings table if it doesn't exist
        $conn->exec("CREATE TABLE site_settings (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(255) NOT NULL UNIQUE,
            setting_value TEXT,
            setting_description VARCHAR(255),
            created_at DATETIME NOT NULL,
            updated_at DATETIME DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // Insert default settings
        $default_settings = [
            ['site_name', 'متجر Tienda', 'اسم الموقع'],
            ['site_description', 'متجر إلكتروني لبيع المنتجات', 'وصف الموقع'],
            ['contact_email', 'info@tienda.com', 'البريد الإلكتروني للاتصال'],
            ['contact_phone', '123456789', 'رقم الهاتف للاتصال'],
            ['address', 'العنوان، المدينة، البلد', 'عنوان المتجر الفعلي'],
            ['currency', 'USD', 'العملة المستخدمة'],
            ['currency_symbol', '$', 'رمز العملة'],
            ['tax_rate', '15', 'نسبة الضريبة المضافة (%)'],
            ['shipping_cost', '10', 'تكلفة الشحن الافتراضية'],
            ['free_shipping_min', '100', 'الحد الأدنى للطلب للحصول على شحن مجاني'],
            ['enable_guest_checkout', '1', 'تمكين الدفع للزوار'],
            ['social_facebook', '', 'رابط صفحة الفيسبوك'],
            ['social_twitter', '', 'رابط صفحة تويتر'],
            ['social_instagram', '', 'رابط صفحة انستجرام'],
            ['maintenance_mode', '0', 'وضع الصيانة']
        ];
        
        $stmt = $conn->prepare("INSERT INTO site_settings (setting_key, setting_value, setting_description, created_at) VALUES (?, ?, ?, NOW())");
        
        foreach ($default_settings as $setting) {
            $stmt->execute([$setting[0], $setting[1], $setting[2]]);
        }
        
        $success = 'تم إنشاء جدول الإعدادات بنجاح وإضافة الإعدادات الافتراضية.';
    }
    
    // Get all settings
    $stmt = $conn->query("SELECT * FROM site_settings ORDER BY id");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row;
    }
} catch (PDOException $e) {
    $error = 'حدث خطأ: ' . $e->getMessage();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_settings'])) {
        try {
            $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Update each setting
            $stmt = $conn->prepare("UPDATE site_settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?");
            
            foreach ($_POST as $key => $value) {
                if ($key !== 'update_settings' && isset($settings[$key])) {
                    $stmt->execute([$value, $key]);
                }
            }
            
            // Special case for checkboxes - if they're not in POST, they're unchecked
            $checkboxes = ['enable_guest_checkout', 'maintenance_mode'];
            foreach ($checkboxes as $checkbox) {
                if (!isset($_POST[$checkbox]) && isset($settings[$checkbox])) {
                    $stmt->execute(['0', $checkbox]);
                }
            }
            
            $success = 'تم تحديث الإعدادات بنجاح.';
            
            // Refresh settings
            $stmt = $conn->query("SELECT * FROM site_settings ORDER BY id");
            $settings = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $settings[$row['setting_key']] = $row;
            }
        } catch (PDOException $e) {
            $error = 'حدث خطأ: ' . $e->getMessage();
        }
    }
}

// Set current page for navigation
$current_page = 'settings';

// Include header
include 'includes/header.php';
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-cog text-primary mr-2"></i>إعدادات الموقع</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="index.php">الرئيسية</a></li>
                    <li class="breadcrumb-item active">الإعدادات</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="content">
    <div class="container-fluid">
        <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <i class="fas fa-exclamation-triangle mr-2"></i> <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <i class="fas fa-check-circle mr-2"></i> <?php echo $success; ?>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-sliders-h mr-2"></i>إعدادات الموقع</h3>
            </div>
            <div class="card-body">
                <form method="post">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="nav-tabs-custom">
                                <ul class="nav nav-tabs" id="settings-tabs" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link active" id="general-tab" data-toggle="tab" href="#general" role="tab">إعدادات عامة</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" id="contact-tab" data-toggle="tab" href="#contact" role="tab">معلومات الاتصال</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" id="payment-tab" data-toggle="tab" href="#payment" role="tab">الدفع والشحن</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" id="social-tab" data-toggle="tab" href="#social" role="tab">وسائل التواصل الاجتماعي</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" id="system-tab" data-toggle="tab" href="#system" role="tab">إعدادات النظام</a>
                                    </li>
                                </ul>
                            </div>
                            
                            <div class="tab-content pt-3" id="settings-content">
                                <!-- General Settings -->
                                <div class="tab-pane fade show active" id="general" role="tabpanel">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="site_name">اسم الموقع</label>
                                                <input type="text" class="form-control" id="site_name" name="site_name" value="<?php echo isset($settings['site_name']) ? htmlspecialchars($settings['site_name']['setting_value']) : ''; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="site_description">وصف الموقع</label>
                                                <input type="text" class="form-control" id="site_description" name="site_description" value="<?php echo isset($settings['site_description']) ? htmlspecialchars($settings['site_description']['setting_value']) : ''; ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Contact Information -->
                                <div class="tab-pane fade" id="contact" role="tabpanel">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="contact_email">البريد الإلكتروني للاتصال</label>
                                                <input type="email" class="form-control" id="contact_email" name="contact_email" value="<?php echo isset($settings['contact_email']) ? htmlspecialchars($settings['contact_email']['setting_value']) : ''; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="contact_phone">رقم الهاتف للاتصال</label>
                                                <input type="text" class="form-control" id="contact_phone" name="contact_phone" value="<?php echo isset($settings['contact_phone']) ? htmlspecialchars($settings['contact_phone']['setting_value']) : ''; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="address">عنوان المتجر الفعلي</label>
                                                <textarea class="form-control" id="address" name="address" rows="2"><?php echo isset($settings['address']) ? htmlspecialchars($settings['address']['setting_value']) : ''; ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Payment & Shipping -->
                                <div class="tab-pane fade" id="payment" role="tabpanel">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="currency">العملة</label>
                                                <select class="form-control" id="currency" name="currency">
                                                    <option value="USD" <?php echo (isset($settings['currency']) && $settings['currency']['setting_value'] === 'USD') ? 'selected' : ''; ?>>دولار أمريكي (USD)</option>
                                                    <option value="EUR" <?php echo (isset($settings['currency']) && $settings['currency']['setting_value'] === 'EUR') ? 'selected' : ''; ?>>يورو (EUR)</option>
                                                    <option value="SAR" <?php echo (isset($settings['currency']) && $settings['currency']['setting_value'] === 'SAR') ? 'selected' : ''; ?>>ريال سعودي (SAR)</option>
                                                    <option value="AED" <?php echo (isset($settings['currency']) && $settings['currency']['setting_value'] === 'AED') ? 'selected' : ''; ?>>درهم إماراتي (AED)</option>
                                                    <option value="EGP" <?php echo (isset($settings['currency']) && $settings['currency']['setting_value'] === 'EGP') ? 'selected' : ''; ?>>جنيه مصري (EGP)</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="currency_symbol">رمز العملة</label>
                                                <input type="text" class="form-control" id="currency_symbol" name="currency_symbol" value="<?php echo isset($settings['currency_symbol']) ? htmlspecialchars($settings['currency_symbol']['setting_value']) : ''; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="tax_rate">نسبة الضريبة المضافة (%)</label>
                                                <input type="number" step="0.01" min="0" max="100" class="form-control" id="tax_rate" name="tax_rate" value="<?php echo isset($settings['tax_rate']) ? htmlspecialchars($settings['tax_rate']['setting_value']) : ''; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="shipping_cost">تكلفة الشحن الافتراضية</label>
                                                <input type="number" step="0.01" min="0" class="form-control" id="shipping_cost" name="shipping_cost" value="<?php echo isset($settings['shipping_cost']) ? htmlspecialchars($settings['shipping_cost']['setting_value']) : ''; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="free_shipping_min">الحد الأدنى للطلب للحصول على شحن مجاني</label>
                                                <input type="number" step="0.01" min="0" class="form-control" id="free_shipping_min" name="free_shipping_min" value="<?php echo isset($settings['free_shipping_min']) ? htmlspecialchars($settings['free_shipping_min']['setting_value']) : ''; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <div class="custom-control custom-switch">
                                                    <input type="checkbox" class="custom-control-input" id="enable_guest_checkout" name="enable_guest_checkout" value="1" <?php echo (isset($settings['enable_guest_checkout']) && $settings['enable_guest_checkout']['setting_value'] == 1) ? 'checked' : ''; ?>>
                                                    <label class="custom-control-label" for="enable_guest_checkout">تمكين الدفع للزوار (بدون تسجيل)</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Social Media -->
                                <div class="tab-pane fade" id="social" role="tabpanel">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="social_facebook">رابط صفحة فيسبوك</label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i class="fab fa-facebook-f"></i></span>
                                                    </div>
                                                    <input type="url" class="form-control" id="social_facebook" name="social_facebook" value="<?php echo isset($settings['social_facebook']) ? htmlspecialchars($settings['social_facebook']['setting_value']) : ''; ?>">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="social_twitter">رابط صفحة تويتر</label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i class="fab fa-twitter"></i></span>
                                                    </div>
                                                    <input type="url" class="form-control" id="social_twitter" name="social_twitter" value="<?php echo isset($settings['social_twitter']) ? htmlspecialchars($settings['social_twitter']['setting_value']) : ''; ?>">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="social_instagram">رابط صفحة انستجرام</label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i class="fab fa-instagram"></i></span>
                                                    </div>
                                                    <input type="url" class="form-control" id="social_instagram" name="social_instagram" value="<?php echo isset($settings['social_instagram']) ? htmlspecialchars($settings['social_instagram']['setting_value']) : ''; ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- System Settings -->
                                <div class="tab-pane fade" id="system" role="tabpanel">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <div class="custom-control custom-switch">
                                                    <input type="checkbox" class="custom-control-input" id="maintenance_mode" name="maintenance_mode" value="1" <?php echo (isset($settings['maintenance_mode']) && $settings['maintenance_mode']['setting_value'] == 1) ? 'checked' : ''; ?>>
                                                    <label class="custom-control-label" for="maintenance_mode">وضع الصيانة</label>
                                                </div>
                                                <small class="form-text text-muted">عند تفعيل وضع الصيانة، سيتم عرض صفحة صيانة للزوار، بينما ستظل لوحة التحكم متاحة للمديرين.</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center mt-4">
                        <button type="submit" name="update_settings" class="btn btn-primary btn-lg">
                            <i class="fas fa-save mr-1"></i> حفظ الإعدادات
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include 'includes/footer.php';
?>
