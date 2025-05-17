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
$payment_methods = [];
$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new payment method
    if (isset($_POST['add_payment'])) {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $instructions = trim($_POST['instructions']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($name)) {
            $error = 'يرجى إدخال اسم طريقة الدفع';
        } else {
            try {
                $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Insert new payment method
                $stmt = $conn->prepare(
                    "INSERT INTO payment_methods 
                     (name, description, instructions, is_active, created_at) 
                     VALUES 
                     (:name, :description, :instructions, :is_active, NOW())");
                
                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':description', $description);
                $stmt->bindParam(':instructions', $instructions);
                $stmt->bindParam(':is_active', $is_active);
                $stmt->execute();
                
                $success = 'تم إضافة طريقة الدفع بنجاح';
            } catch (PDOException $e) {
                $error = 'حدث خطأ: ' . $e->getMessage();
            }
        }
    }
    
    // Edit payment method
    if (isset($_POST['edit_payment'])) {
        $payment_id = (int)$_POST['payment_id'];
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $instructions = trim($_POST['instructions']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($name)) {
            $error = 'يرجى إدخال اسم طريقة الدفع';
        } else {
            try {
                $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Update payment method
                $stmt = $conn->prepare(
                    "UPDATE payment_methods 
                     SET name = :name, 
                         description = :description, 
                         instructions = :instructions, 
                         is_active = :is_active, 
                         updated_at = NOW() 
                     WHERE payment_id = :payment_id");
                
                $stmt->bindParam(':payment_id', $payment_id);
                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':description', $description);
                $stmt->bindParam(':instructions', $instructions);
                $stmt->bindParam(':is_active', $is_active);
                $stmt->execute();
                
                $success = 'تم تحديث طريقة الدفع بنجاح';
            } catch (PDOException $e) {
                $error = 'حدث خطأ: ' . $e->getMessage();
            }
        }
    }
}

// Handle payment method delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $payment_id = (int)$_GET['id'];
    
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Check if payment method is used in orders
        $stmt = $conn->prepare("SELECT COUNT(*) FROM orders WHERE payment_method_id = :payment_id");
        $stmt->bindParam(':payment_id', $payment_id);
        $stmt->execute();
        
        if ($stmt->fetchColumn() > 0) {
            $error = 'لا يمكن حذف طريقة الدفع هذه لأنها مستخدمة في طلبات. يمكنك تعطيلها بدلاً من ذلك.';
        } else {
            // Delete payment method
            $stmt = $conn->prepare("DELETE FROM payment_methods WHERE payment_id = :payment_id");
            $stmt->bindParam(':payment_id', $payment_id);
            $stmt->execute();
            
            $success = 'تم حذف طريقة الدفع بنجاح';
        }
    } catch (PDOException $e) {
        $error = 'حدث خطأ: ' . $e->getMessage();
    }
}

// Handle payment method status toggle
if (isset($_GET['action']) && $_GET['action'] === 'toggle' && isset($_GET['id'])) {
    $payment_id = (int)$_GET['id'];
    
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Toggle status
        $stmt = $conn->prepare("UPDATE payment_methods SET is_active = NOT is_active WHERE payment_id = :payment_id");
        $stmt->bindParam(':payment_id', $payment_id);
        $stmt->execute();
        
        $success = 'تم تغيير حالة طريقة الدفع بنجاح';
    } catch (PDOException $e) {
        $error = 'حدث خطأ: ' . $e->getMessage();
    }
}

// Get all payment methods
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if table exists
    $tables = $conn->query("SHOW TABLES LIKE 'payment_methods'")->fetchAll();
    if (count($tables) === 0) {
        // Create payment_methods table if it doesn't exist
        $conn->exec("CREATE TABLE payment_methods (
            payment_id INT(11) AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            instructions TEXT,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL,
            updated_at DATETIME DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // Add default payment methods
        $conn->exec("INSERT INTO payment_methods (name, description, instructions, is_active, created_at) VALUES 
            ('الدفع عند الاستلام', 'ادفع نقداً عند استلام طلبك', 'سيتم تحصيل المبلغ من قبل مندوب التوصيل عند استلام الطلب', 1, NOW()),
            ('بطاقة الائتمان', 'ادفع باستخدام بطاقة Visa أو MasterCard', 'سيتم تحويلك إلى بوابة الدفع الآمنة لإتمام عملية الدفع', 1, NOW()),
            ('التحويل البنكي', 'قم بتحويل المبلغ إلى حسابنا البنكي', 'يرجى تحويل المبلغ إلى الحساب التالي:\n\nبنك XYZ\nرقم الحساب: 1234-5678-9012-3456\nاسم المستفيد: متجر تيندا\n\nبعد إتمام التحويل، يرجى إرسال إيصال التحويل إلى info@tienda.com', 1, NOW())");
        
        $success = 'تم إنشاء جدول طرق الدفع وإضافة الطرق الافتراضية بنجاح';
    } else {
        // Get all payment methods
        $stmt = $conn->query("SELECT * FROM payment_methods ORDER BY name");
        $payment_methods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $error = 'حدث خطأ: ' . $e->getMessage();
}

// Set current page for navigation
$current_page = 'payment_methods';

// Include header
include 'includes/header.php';
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-credit-card text-primary mr-2"></i>إدارة طرق الدفع</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="index.php">الرئيسية</a></li>
                    <li class="breadcrumb-item active">طرق الدفع</li>
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
            <!-- Add Payment Method Form -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-plus mr-2"></i>إضافة طريقة دفع جديدة</h3>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="form-group">
                                <label for="name">اسم طريقة الدفع <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="form-group">
                                <label for="description">الوصف المختصر</label>
                                <input type="text" class="form-control" id="description" name="description">
                                <small class="form-text text-muted">وصف قصير يظهر بجانب طريقة الدفع في صفحة الدفع</small>
                            </div>
                            <div class="form-group">
                                <label for="instructions">تعليمات الدفع</label>
                                <textarea class="form-control" id="instructions" name="instructions" rows="5"></textarea>
                                <small class="form-text text-muted">تعليمات مفصلة تظهر للعميل عند اختيار طريقة الدفع هذه</small>
                            </div>
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" checked>
                                    <label class="custom-control-label" for="is_active">نشط</label>
                                </div>
                            </div>
                            <button type="submit" name="add_payment" class="btn btn-primary btn-block">
                                <i class="fas fa-plus mr-1"></i> إضافة طريقة الدفع
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Payment Methods List -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title"><i class="fas fa-list mr-2"></i>قائمة طرق الدفع</h3>
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
                                        <th>اسم طريقة الدفع</th>
                                        <th>الوصف</th>
                                        <th>الحالة</th>
                                        <th style="width: 150px">الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($payment_methods)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center">لا توجد طرق دفع حتى الآن</td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($payment_methods as $index => $method): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($method['name']); ?>
                                        </td>
                                        <td>
                                            <?php echo !empty($method['description']) ? htmlspecialchars($method['description']) : '<span class="text-muted">لا يوجد وصف</span>'; ?>
                                        </td>
                                        <td>
                                            <?php if ($method['is_active']): ?>
                                                <span class="badge badge-success">نشط</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">غير نشط</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="?action=toggle&id=<?php echo $method['payment_id']; ?>" class="btn btn-sm <?php echo $method['is_active'] ? 'btn-outline-warning' : 'btn-outline-success'; ?>" title="<?php echo $method['is_active'] ? 'تعطيل' : 'تفعيل'; ?>">
                                                    <i class="fas <?php echo $method['is_active'] ? 'fa-toggle-off' : 'fa-toggle-on'; ?>"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#editModal<?php echo $method['payment_id']; ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <a href="?action=delete&id=<?php echo $method['payment_id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('هل أنت متأكد من حذف طريقة الدفع هذه؟ هذا الإجراء لا يمكن التراجع عنه.')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                            
                                            <!-- Edit Modal -->
                                            <div class="modal fade" id="editModal<?php echo $method['payment_id']; ?>" tabindex="-1" role="dialog" aria-labelledby="editModalLabel<?php echo $method['payment_id']; ?>" aria-hidden="true">
                                                <div class="modal-dialog" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="editModalLabel<?php echo $method['payment_id']; ?>">تعديل طريقة الدفع</h5>
                                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>
                                                        <form method="post">
                                                            <div class="modal-body">
                                                                <input type="hidden" name="payment_id" value="<?php echo $method['payment_id']; ?>">
                                                                <div class="form-group">
                                                                    <label for="edit_name<?php echo $method['payment_id']; ?>">اسم طريقة الدفع <span class="text-danger">*</span></label>
                                                                    <input type="text" class="form-control" id="edit_name<?php echo $method['payment_id']; ?>" name="name" value="<?php echo htmlspecialchars($method['name']); ?>" required>
                                                                </div>
                                                                <div class="form-group">
                                                                    <label for="edit_description<?php echo $method['payment_id']; ?>">الوصف المختصر</label>
                                                                    <input type="text" class="form-control" id="edit_description<?php echo $method['payment_id']; ?>" name="description" value="<?php echo htmlspecialchars($method['description']); ?>">
                                                                </div>
                                                                <div class="form-group">
                                                                    <label for="edit_instructions<?php echo $method['payment_id']; ?>">تعليمات الدفع</label>
                                                                    <textarea class="form-control" id="edit_instructions<?php echo $method['payment_id']; ?>" name="instructions" rows="5"><?php echo htmlspecialchars($method['instructions']); ?></textarea>
                                                                </div>
                                                                <div class="form-group">
                                                                    <div class="custom-control custom-switch">
                                                                        <input type="checkbox" class="custom-control-input" id="edit_is_active<?php echo $method['payment_id']; ?>" name="is_active" <?php echo $method['is_active'] ? 'checked' : ''; ?>>
                                                                        <label class="custom-control-label" for="edit_is_active<?php echo $method['payment_id']; ?>">نشط</label>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">إلغاء</button>
                                                                <button type="submit" name="edit_payment" class="btn btn-primary">حفظ التغييرات</button>
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
