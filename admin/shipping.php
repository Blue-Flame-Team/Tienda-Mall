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
$shipping_methods = [];
$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new shipping method
    if (isset($_POST['add_shipping'])) {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $price = (float)$_POST['price'];
        $delivery_time = trim($_POST['delivery_time']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $countries = !empty($_POST['countries']) ? trim($_POST['countries']) : null;
        
        if (empty($name)) {
            $error = 'يرجى إدخال اسم طريقة الشحن';
        } else {
            try {
                $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Insert new shipping method
                $stmt = $conn->prepare(
                    "INSERT INTO shipping_methods 
                     (name, description, price, delivery_time, is_active, countries, created_at) 
                     VALUES 
                     (:name, :description, :price, :delivery_time, :is_active, :countries, NOW())");
                
                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':description', $description);
                $stmt->bindParam(':price', $price);
                $stmt->bindParam(':delivery_time', $delivery_time);
                $stmt->bindParam(':is_active', $is_active);
                $stmt->bindParam(':countries', $countries);
                $stmt->execute();
                
                $success = 'تم إضافة طريقة الشحن بنجاح';
            } catch (PDOException $e) {
                $error = 'حدث خطأ: ' . $e->getMessage();
            }
        }
    }
    
    // Edit shipping method
    if (isset($_POST['edit_shipping'])) {
        $shipping_id = (int)$_POST['shipping_id'];
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $price = (float)$_POST['price'];
        $delivery_time = trim($_POST['delivery_time']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $countries = !empty($_POST['countries']) ? trim($_POST['countries']) : null;
        
        if (empty($name)) {
            $error = 'يرجى إدخال اسم طريقة الشحن';
        } else {
            try {
                $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Update shipping method
                $stmt = $conn->prepare(
                    "UPDATE shipping_methods 
                     SET name = :name, 
                         description = :description, 
                         price = :price, 
                         delivery_time = :delivery_time, 
                         is_active = :is_active, 
                         countries = :countries, 
                         updated_at = NOW() 
                     WHERE shipping_id = :shipping_id");
                
                $stmt->bindParam(':shipping_id', $shipping_id);
                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':description', $description);
                $stmt->bindParam(':price', $price);
                $stmt->bindParam(':delivery_time', $delivery_time);
                $stmt->bindParam(':is_active', $is_active);
                $stmt->bindParam(':countries', $countries);
                $stmt->execute();
                
                $success = 'تم تحديث طريقة الشحن بنجاح';
            } catch (PDOException $e) {
                $error = 'حدث خطأ: ' . $e->getMessage();
            }
        }
    }
}

// Handle shipping method delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $shipping_id = (int)$_GET['id'];
    
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Check if shipping method is used in orders
        $stmt = $conn->prepare("SELECT COUNT(*) FROM orders WHERE shipping_method_id = :shipping_id");
        $stmt->bindParam(':shipping_id', $shipping_id);
        $stmt->execute();
        
        if ($stmt->fetchColumn() > 0) {
            $error = 'لا يمكن حذف طريقة الشحن هذه لأنها مستخدمة في طلبات. يمكنك تعطيلها بدلاً من ذلك.';
        } else {
            // Delete shipping method
            $stmt = $conn->prepare("DELETE FROM shipping_methods WHERE shipping_id = :shipping_id");
            $stmt->bindParam(':shipping_id', $shipping_id);
            $stmt->execute();
            
            $success = 'تم حذف طريقة الشحن بنجاح';
        }
    } catch (PDOException $e) {
        $error = 'حدث خطأ: ' . $e->getMessage();
    }
}

// Handle shipping method status toggle
if (isset($_GET['action']) && $_GET['action'] === 'toggle' && isset($_GET['id'])) {
    $shipping_id = (int)$_GET['id'];
    
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Toggle status
        $stmt = $conn->prepare("UPDATE shipping_methods SET is_active = NOT is_active WHERE shipping_id = :shipping_id");
        $stmt->bindParam(':shipping_id', $shipping_id);
        $stmt->execute();
        
        $success = 'تم تغيير حالة طريقة الشحن بنجاح';
    } catch (PDOException $e) {
        $error = 'حدث خطأ: ' . $e->getMessage();
    }
}

// Get all shipping methods
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if table exists
    $tables = $conn->query("SHOW TABLES LIKE 'shipping_methods'")->fetchAll();
    if (count($tables) === 0) {
        // Create shipping_methods table if it doesn't exist
        $conn->exec("CREATE TABLE shipping_methods (
            shipping_id INT(11) AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            price DECIMAL(10,2) NOT NULL DEFAULT 0,
            delivery_time VARCHAR(100),
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            countries TEXT,
            created_at DATETIME NOT NULL,
            updated_at DATETIME DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // Add default shipping methods
        $conn->exec("INSERT INTO shipping_methods (name, description, price, delivery_time, is_active, created_at) VALUES 
            ('التوصيل القياسي', 'توصيل عادي إلى العنوان المحدد', 10.00, '3-5 أيام عمل', 1, NOW()),
            ('التوصيل السريع', 'توصيل سريع في يوم العمل التالي', 25.00, '1-2 أيام عمل', 1, NOW()),
            ('الاستلام من المتجر', 'استلام الطلب مباشرة من متجرنا', 0.00, 'فوري عند الجاهزية', 1, NOW())");
        
        $success = 'تم إنشاء جدول طرق الشحن وإضافة الطرق الافتراضية بنجاح';
    } else {
        // Get all shipping methods
        $stmt = $conn->query("SELECT * FROM shipping_methods ORDER BY name");
        $shipping_methods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $error = 'حدث خطأ: ' . $e->getMessage();
}

// Set current page for navigation
$current_page = 'shipping';

// Include header
include 'includes/header.php';
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-truck text-primary mr-2"></i>إدارة طرق الشحن</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="index.php">الرئيسية</a></li>
                    <li class="breadcrumb-item active">طرق الشحن</li>
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
            <!-- Add Shipping Method Form -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-plus mr-2"></i>إضافة طريقة شحن جديدة</h3>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="form-group">
                                <label for="name">اسم طريقة الشحن <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="form-group">
                                <label for="description">الوصف</label>
                                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                            </div>
                            <div class="form-group">
                                <label for="price">سعر الشحن <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">$</span>
                                    </div>
                                    <input type="number" class="form-control" id="price" name="price" min="0" step="0.01" value="0.00" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="delivery_time">وقت التوصيل المتوقع</label>
                                <input type="text" class="form-control" id="delivery_time" name="delivery_time" placeholder="مثال: 3-5 أيام عمل">
                            </div>
                            <div class="form-group">
                                <label for="countries">الدول المتاحة</label>
                                <input type="text" class="form-control" id="countries" name="countries" placeholder="اترك فارغاً للكل، أو أدخل أسماء الدول مفصولة بفواصل">
                                <small class="form-text text-muted">اترك هذا الحقل فارغاً لجعل طريقة الشحن متاحة لجميع الدول.</small>
                            </div>
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" checked>
                                    <label class="custom-control-label" for="is_active">نشط</label>
                                </div>
                            </div>
                            <button type="submit" name="add_shipping" class="btn btn-primary btn-block">
                                <i class="fas fa-plus mr-1"></i> إضافة طريقة الشحن
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Shipping Methods List -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title"><i class="fas fa-list mr-2"></i>قائمة طرق الشحن</h3>
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
                                        <th>اسم طريقة الشحن</th>
                                        <th>السعر</th>
                                        <th>وقت التوصيل</th>
                                        <th>الحالة</th>
                                        <th style="width: 150px">الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($shipping_methods)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">لا توجد طرق شحن حتى الآن</td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($shipping_methods as $index => $method): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($method['name']); ?>
                                            <?php if (!empty($method['description'])): ?>
                                                <small class="d-block text-muted"><?php echo htmlspecialchars($method['description']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($method['price'] > 0): ?>
                                                <?php echo number_format($method['price'], 2); ?> $
                                            <?php else: ?>
                                                <span class="text-success">مجاني</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($method['delivery_time']); ?></td>
                                        <td>
                                            <?php if ($method['is_active']): ?>
                                                <span class="badge badge-success">نشط</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">غير نشط</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="?action=toggle&id=<?php echo $method['shipping_id']; ?>" class="btn btn-sm <?php echo $method['is_active'] ? 'btn-outline-warning' : 'btn-outline-success'; ?>" title="<?php echo $method['is_active'] ? 'تعطيل' : 'تفعيل'; ?>">
                                                    <i class="fas <?php echo $method['is_active'] ? 'fa-toggle-off' : 'fa-toggle-on'; ?>"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#editModal<?php echo $method['shipping_id']; ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <a href="?action=delete&id=<?php echo $method['shipping_id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('هل أنت متأكد من حذف طريقة الشحن هذه؟ هذا الإجراء لا يمكن التراجع عنه.')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                            
                                            <!-- Edit Modal -->
                                            <div class="modal fade" id="editModal<?php echo $method['shipping_id']; ?>" tabindex="-1" role="dialog" aria-labelledby="editModalLabel<?php echo $method['shipping_id']; ?>" aria-hidden="true">
                                                <div class="modal-dialog" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="editModalLabel<?php echo $method['shipping_id']; ?>">تعديل طريقة الشحن</h5>
                                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>
                                                        <form method="post">
                                                            <div class="modal-body">
                                                                <input type="hidden" name="shipping_id" value="<?php echo $method['shipping_id']; ?>">
                                                                <div class="form-group">
                                                                    <label for="edit_name<?php echo $method['shipping_id']; ?>">اسم طريقة الشحن <span class="text-danger">*</span></label>
                                                                    <input type="text" class="form-control" id="edit_name<?php echo $method['shipping_id']; ?>" name="name" value="<?php echo htmlspecialchars($method['name']); ?>" required>
                                                                </div>
                                                                <div class="form-group">
                                                                    <label for="edit_description<?php echo $method['shipping_id']; ?>">الوصف</label>
                                                                    <textarea class="form-control" id="edit_description<?php echo $method['shipping_id']; ?>" name="description" rows="3"><?php echo htmlspecialchars($method['description']); ?></textarea>
                                                                </div>
                                                                <div class="form-group">
                                                                    <label for="edit_price<?php echo $method['shipping_id']; ?>">سعر الشحن <span class="text-danger">*</span></label>
                                                                    <div class="input-group">
                                                                        <div class="input-group-prepend">
                                                                            <span class="input-group-text">$</span>
                                                                        </div>
                                                                        <input type="number" class="form-control" id="edit_price<?php echo $method['shipping_id']; ?>" name="price" min="0" step="0.01" value="<?php echo $method['price']; ?>" required>
                                                                    </div>
                                                                </div>
                                                                <div class="form-group">
                                                                    <label for="edit_delivery_time<?php echo $method['shipping_id']; ?>">وقت التوصيل المتوقع</label>
                                                                    <input type="text" class="form-control" id="edit_delivery_time<?php echo $method['shipping_id']; ?>" name="delivery_time" value="<?php echo htmlspecialchars($method['delivery_time']); ?>">
                                                                </div>
                                                                <div class="form-group">
                                                                    <label for="edit_countries<?php echo $method['shipping_id']; ?>">الدول المتاحة</label>
                                                                    <input type="text" class="form-control" id="edit_countries<?php echo $method['shipping_id']; ?>" name="countries" value="<?php echo htmlspecialchars($method['countries']); ?>" placeholder="اترك فارغاً للكل، أو أدخل أسماء الدول مفصولة بفواصل">
                                                                    <small class="form-text text-muted">اترك هذا الحقل فارغاً لجعل طريقة الشحن متاحة لجميع الدول.</small>
                                                                </div>
                                                                <div class="form-group">
                                                                    <div class="custom-control custom-switch">
                                                                        <input type="checkbox" class="custom-control-input" id="edit_is_active<?php echo $method['shipping_id']; ?>" name="is_active" <?php echo $method['is_active'] ? 'checked' : ''; ?>>
                                                                        <label class="custom-control-label" for="edit_is_active<?php echo $method['shipping_id']; ?>">نشط</label>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">إلغاء</button>
                                                                <button type="submit" name="edit_shipping" class="btn btn-primary">حفظ التغييرات</button>
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
