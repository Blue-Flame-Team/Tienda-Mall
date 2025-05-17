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
$coupons = [];
$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new coupon
    if (isset($_POST['add_coupon'])) {
        $code = trim($_POST['code']);
        $discount_type = $_POST['discount_type'];
        $discount_value = (float)$_POST['discount_value'];
        $min_order_amount = !empty($_POST['min_order_amount']) ? (float)$_POST['min_order_amount'] : 0;
        $max_uses = !empty($_POST['max_uses']) ? (int)$_POST['max_uses'] : null;
        $start_date = $_POST['start_date'];
        $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
        $description = trim($_POST['description']);
        
        if (empty($code)) {
            $error = 'يرجى إدخال رمز الكوبون';
        } else if ($discount_value <= 0) {
            $error = 'يجب أن تكون قيمة الخصم أكبر من الصفر';
        } else if ($discount_type === 'percentage' && $discount_value > 100) {
            $error = 'لا يمكن أن تتجاوز نسبة الخصم 100%';
        } else {
            try {
                $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Check if coupon code already exists
                $stmt = $conn->prepare("SELECT COUNT(*) FROM coupons WHERE code = :code");
                $stmt->bindParam(':code', $code);
                $stmt->execute();
                
                if ($stmt->fetchColumn() > 0) {
                    $error = 'هذا الكود مستخدم بالفعل. يرجى اختيار كود آخر';
                } else {
                    // Insert new coupon
                    $stmt = $conn->prepare(
                        "INSERT INTO coupons 
                         (code, discount_type, discount_value, min_order_amount, max_uses, used_count, start_date, end_date, description, created_at) 
                         VALUES 
                         (:code, :discount_type, :discount_value, :min_order_amount, :max_uses, 0, :start_date, :end_date, :description, NOW())");
                    
                    $stmt->bindParam(':code', $code);
                    $stmt->bindParam(':discount_type', $discount_type);
                    $stmt->bindParam(':discount_value', $discount_value);
                    $stmt->bindParam(':min_order_amount', $min_order_amount);
                    $stmt->bindParam(':max_uses', $max_uses);
                    $stmt->bindParam(':start_date', $start_date);
                    $stmt->bindParam(':end_date', $end_date);
                    $stmt->bindParam(':description', $description);
                    $stmt->execute();
                    
                    $success = 'تم إضافة الكوبون بنجاح';
                }
            } catch (PDOException $e) {
                $error = 'حدث خطأ: ' . $e->getMessage();
            }
        }
    }
    
    // Edit coupon
    if (isset($_POST['edit_coupon'])) {
        $coupon_id = (int)$_POST['coupon_id'];
        $code = trim($_POST['code']);
        $discount_type = $_POST['discount_type'];
        $discount_value = (float)$_POST['discount_value'];
        $min_order_amount = !empty($_POST['min_order_amount']) ? (float)$_POST['min_order_amount'] : 0;
        $max_uses = !empty($_POST['max_uses']) ? (int)$_POST['max_uses'] : null;
        $start_date = $_POST['start_date'];
        $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
        $description = trim($_POST['description']);
        
        if (empty($code)) {
            $error = 'يرجى إدخال رمز الكوبون';
        } else if ($discount_value <= 0) {
            $error = 'يجب أن تكون قيمة الخصم أكبر من الصفر';
        } else if ($discount_type === 'percentage' && $discount_value > 100) {
            $error = 'لا يمكن أن تتجاوز نسبة الخصم 100%';
        } else {
            try {
                $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Check if coupon code already exists (excluding the current one)
                $stmt = $conn->prepare("SELECT COUNT(*) FROM coupons WHERE code = :code AND coupon_id != :coupon_id");
                $stmt->bindParam(':code', $code);
                $stmt->bindParam(':coupon_id', $coupon_id);
                $stmt->execute();
                
                if ($stmt->fetchColumn() > 0) {
                    $error = 'هذا الكود مستخدم بالفعل. يرجى اختيار كود آخر';
                } else {
                    // Update coupon
                    $stmt = $conn->prepare(
                        "UPDATE coupons 
                         SET code = :code, 
                             discount_type = :discount_type, 
                             discount_value = :discount_value, 
                             min_order_amount = :min_order_amount, 
                             max_uses = :max_uses, 
                             start_date = :start_date, 
                             end_date = :end_date, 
                             description = :description, 
                             updated_at = NOW() 
                         WHERE coupon_id = :coupon_id");
                    
                    $stmt->bindParam(':coupon_id', $coupon_id);
                    $stmt->bindParam(':code', $code);
                    $stmt->bindParam(':discount_type', $discount_type);
                    $stmt->bindParam(':discount_value', $discount_value);
                    $stmt->bindParam(':min_order_amount', $min_order_amount);
                    $stmt->bindParam(':max_uses', $max_uses);
                    $stmt->bindParam(':start_date', $start_date);
                    $stmt->bindParam(':end_date', $end_date);
                    $stmt->bindParam(':description', $description);
                    $stmt->execute();
                    
                    $success = 'تم تحديث الكوبون بنجاح';
                }
            } catch (PDOException $e) {
                $error = 'حدث خطأ: ' . $e->getMessage();
            }
        }
    }
}

// Handle coupon delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $coupon_id = (int)$_GET['id'];
    
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $conn->prepare("DELETE FROM coupons WHERE coupon_id = :coupon_id");
        $stmt->bindParam(':coupon_id', $coupon_id);
        $stmt->execute();
        
        $success = 'تم حذف الكوبون بنجاح';
    } catch (PDOException $e) {
        $error = 'حدث خطأ: ' . $e->getMessage();
    }
}

// Get all coupons
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if table exists
    $tables = $conn->query("SHOW TABLES LIKE 'coupons'")->fetchAll();
    if (count($tables) === 0) {
        // Create coupons table if it doesn't exist
        $conn->exec("CREATE TABLE coupons (
            coupon_id INT(11) AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(50) NOT NULL UNIQUE,
            discount_type ENUM('fixed', 'percentage') NOT NULL,
            discount_value DECIMAL(10,2) NOT NULL,
            min_order_amount DECIMAL(10,2) DEFAULT 0,
            max_uses INT(11) DEFAULT NULL,
            used_count INT(11) DEFAULT 0,
            start_date DATE NOT NULL,
            end_date DATE DEFAULT NULL,
            description TEXT,
            created_at DATETIME NOT NULL,
            updated_at DATETIME DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        $success = 'تم إنشاء جدول الكوبونات بنجاح';
    } else {
        // Get all coupons
        $stmt = $conn->query("SELECT * FROM coupons ORDER BY created_at DESC");
        $coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $error = 'حدث خطأ: ' . $e->getMessage();
}

// Get current date for checking if coupon is active
$current_date = date('Y-m-d');

// Set current page for navigation
$current_page = 'coupons';

// Include header
include 'includes/header.php';
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-ticket-alt text-primary mr-2"></i>إدارة الكوبونات والخصومات</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="index.php">الرئيسية</a></li>
                    <li class="breadcrumb-item active">الكوبونات</li>
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
        
        <div class="row">
            <!-- Add Coupon Form -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-plus mr-2"></i>إضافة كوبون جديد</h3>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="form-group">
                                <label for="code">كود الكوبون <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="code" name="code" required>
                            </div>
                            <div class="form-group">
                                <label for="discount_type">نوع الخصم <span class="text-danger">*</span></label>
                                <select class="form-control" id="discount_type" name="discount_type" required>
                                    <option value="fixed">مبلغ ثابت</option>
                                    <option value="percentage">نسبة مئوية</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="discount_value">قيمة الخصم <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="discount_value" name="discount_value" min="0.01" step="0.01" required>
                            </div>
                            <div class="form-group">
                                <label for="min_order_amount">الحد الأدنى للطلب</label>
                                <input type="number" class="form-control" id="min_order_amount" name="min_order_amount" min="0" step="0.01">
                            </div>
                            <div class="form-group">
                                <label for="max_uses">الحد الأقصى للاستخدام</label>
                                <input type="number" class="form-control" id="max_uses" name="max_uses" min="1">
                            </div>
                            <div class="form-group">
                                <label for="start_date">تاريخ البداية <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="start_date" name="start_date" required>
                            </div>
                            <div class="form-group">
                                <label for="end_date">تاريخ الانتهاء</label>
                                <input type="date" class="form-control" id="end_date" name="end_date">
                            </div>
                            <div class="form-group">
                                <label for="description">الوصف</label>
                                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                            </div>
                            <button type="submit" name="add_coupon" class="btn btn-primary btn-block">
                                <i class="fas fa-plus mr-1"></i> إضافة كوبون
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Coupons List -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title"><i class="fas fa-list mr-2"></i>قائمة الكوبونات</h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover text-nowrap datatable">
                                <thead>
                                    <tr>
                                        <th style="width: 40px">#</th>
                                        <th>الكود</th>
                                        <th>الخصم</th>
                                        <th>الصلاحية</th>
                                        <th>الاستخدام</th>
                                        <th>الحالة</th>
                                        <th style="width: 150px">الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($coupons)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">لا توجد كوبونات حتى الآن</td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($coupons as $index => $coupon): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td>
                                            <span class="badge badge-info"><?php echo htmlspecialchars($coupon['code']); ?></span>
                                        </td>
                                        <td>
                                            <?php if ($coupon['discount_type'] === 'fixed'): ?>
                                                <?php echo number_format($coupon['discount_value'], 2); ?> $
                                            <?php else: ?>
                                                <?php echo $coupon['discount_value']; ?>%
                                            <?php endif; ?>
                                            <?php if ($coupon['min_order_amount'] > 0): ?>
                                                <small class="d-block text-muted">الحد الأدنى: <?php echo number_format($coupon['min_order_amount'], 2); ?> $</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span>من: <?php echo date('Y-m-d', strtotime($coupon['start_date'])); ?></span>
                                            <?php if (!empty($coupon['end_date'])): ?>
                                                <span class="d-block">إلى: <?php echo date('Y-m-d', strtotime($coupon['end_date'])); ?></span>
                                            <?php else: ?>
                                                <span class="d-block text-success">غير محدد</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo $coupon['used_count']; ?>
                                            <?php if (!empty($coupon['max_uses'])): ?>
                                                / <?php echo $coupon['max_uses']; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $is_active = true;
                                            $status_text = 'نشط';
                                            $status_class = 'success';
                                            
                                            if ($current_date < $coupon['start_date']) {
                                                $is_active = false;
                                                $status_text = 'لم يبدأ بعد';
                                                $status_class = 'warning';
                                            } else if (!empty($coupon['end_date']) && $current_date > $coupon['end_date']) {
                                                $is_active = false;
                                                $status_text = 'منتهي';
                                                $status_class = 'danger';
                                            } else if (!empty($coupon['max_uses']) && $coupon['used_count'] >= $coupon['max_uses']) {
                                                $is_active = false;
                                                $status_text = 'استنفد';
                                                $status_class = 'danger';
                                            }
                                            ?>
                                            <span class="badge badge-<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#editModal<?php echo $coupon['coupon_id']; ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <a href="?action=delete&id=<?php echo $coupon['coupon_id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('هل أنت متأكد من حذف هذا الكوبون؟ هذا الإجراء لا يمكن التراجع عنه.')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                            
                                            <!-- Edit Modal -->
                                            <div class="modal fade" id="editModal<?php echo $coupon['coupon_id']; ?>" tabindex="-1" role="dialog" aria-labelledby="editModalLabel<?php echo $coupon['coupon_id']; ?>" aria-hidden="true">
                                                <div class="modal-dialog" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="editModalLabel<?php echo $coupon['coupon_id']; ?>">تعديل الكوبون</h5>
                                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>
                                                        <form method="post">
                                                            <div class="modal-body">
                                                                <input type="hidden" name="coupon_id" value="<?php echo $coupon['coupon_id']; ?>">
                                                                <div class="form-group">
                                                                    <label for="edit_code<?php echo $coupon['coupon_id']; ?>">كود الكوبون <span class="text-danger">*</span></label>
                                                                    <input type="text" class="form-control" id="edit_code<?php echo $coupon['coupon_id']; ?>" name="code" value="<?php echo htmlspecialchars($coupon['code']); ?>" required>
                                                                </div>
                                                                <div class="form-group">
                                                                    <label for="edit_discount_type<?php echo $coupon['coupon_id']; ?>">نوع الخصم <span class="text-danger">*</span></label>
                                                                    <select class="form-control" id="edit_discount_type<?php echo $coupon['coupon_id']; ?>" name="discount_type" required>
                                                                        <option value="fixed" <?php echo $coupon['discount_type'] === 'fixed' ? 'selected' : ''; ?>>مبلغ ثابت</option>
                                                                        <option value="percentage" <?php echo $coupon['discount_type'] === 'percentage' ? 'selected' : ''; ?>>نسبة مئوية</option>
                                                                    </select>
                                                                </div>
                                                                <div class="form-group">
                                                                    <label for="edit_discount_value<?php echo $coupon['coupon_id']; ?>">قيمة الخصم <span class="text-danger">*</span></label>
                                                                    <input type="number" class="form-control" id="edit_discount_value<?php echo $coupon['coupon_id']; ?>" name="discount_value" min="0.01" step="0.01" value="<?php echo $coupon['discount_value']; ?>" required>
                                                                </div>
                                                                <div class="form-group">
                                                                    <label for="edit_min_order_amount<?php echo $coupon['coupon_id']; ?>">الحد الأدنى للطلب</label>
                                                                    <input type="number" class="form-control" id="edit_min_order_amount<?php echo $coupon['coupon_id']; ?>" name="min_order_amount" min="0" step="0.01" value="<?php echo $coupon['min_order_amount']; ?>">
                                                                </div>
                                                                <div class="form-group">
                                                                    <label for="edit_max_uses<?php echo $coupon['coupon_id']; ?>">الحد الأقصى للاستخدام</label>
                                                                    <input type="number" class="form-control" id="edit_max_uses<?php echo $coupon['coupon_id']; ?>" name="max_uses" min="1" value="<?php echo $coupon['max_uses']; ?>">
                                                                </div>
                                                                <div class="form-group">
                                                                    <label for="edit_start_date<?php echo $coupon['coupon_id']; ?>">تاريخ البداية <span class="text-danger">*</span></label>
                                                                    <input type="date" class="form-control" id="edit_start_date<?php echo $coupon['coupon_id']; ?>" name="start_date" value="<?php echo $coupon['start_date']; ?>" required>
                                                                </div>
                                                                <div class="form-group">
                                                                    <label for="edit_end_date<?php echo $coupon['coupon_id']; ?>">تاريخ الانتهاء</label>
                                                                    <input type="date" class="form-control" id="edit_end_date<?php echo $coupon['coupon_id']; ?>" name="end_date" value="<?php echo $coupon['end_date']; ?>">
                                                                </div>
                                                                <div class="form-group">
                                                                    <label for="edit_description<?php echo $coupon['coupon_id']; ?>">الوصف</label>
                                                                    <textarea class="form-control" id="edit_description<?php echo $coupon['coupon_id']; ?>" name="description" rows="3"><?php echo htmlspecialchars($coupon['description']); ?></textarea>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">إلغاء</button>
                                                                <button type="submit" name="edit_coupon" class="btn btn-primary">حفظ التغييرات</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include 'includes/footer.php';
?>
