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

// Validar y establecer los campos necesarios para el admin actual
if (!isset($admin['admin_id'])) {
    $admin['admin_id'] = isset($admin['id']) ? $admin['id'] : 0;
}

if (!isset($admin['is_super_admin'])) {
    $admin['is_super_admin'] = isset($admin['role']) && $admin['role'] === 'super_admin' ? 1 : 0;
}

// Check if admin has appropriate permissions
if (!$admin['is_super_admin']) {
    header('Location: index.php?error=permission');
    exit;
}

// Include bootstrap file to load all dependencies
require_once '../includes/bootstrap.php';

// Initialize variables
$admins = [];
$error = '';
$success = '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$total_admins = 0;
$total_super_admins = 0;
$total_active_admins = 0;

// Get database connection using the Database class
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Handle admin status change
    if (isset($_GET['action']) && isset($_GET['id'])) {
        $action = $_GET['action'];
        $admin_id = (int)$_GET['id'];
        
        // Prevent self-modification
        if ($admin_id === (int)$admin['admin_id'] && ($action === 'deactivate' || $action === 'delete')) {
            $error = 'لا يمكنك تعديل حسابك الشخصي بهذه الطريقة';
        } else {
            if ($action === 'activate') {
                $stmt = $conn->prepare("UPDATE admins SET is_active = 1 WHERE admin_id = :admin_id");
                $stmt->bindParam(':admin_id', $admin_id);
                $stmt->execute();
                $success = 'تم تنشيط المدير بنجاح';
            } elseif ($action === 'deactivate') {
                $stmt = $conn->prepare("UPDATE admins SET is_active = 0 WHERE admin_id = :admin_id");
                $stmt->bindParam(':admin_id', $admin_id);
                $stmt->execute();
                $success = 'تم إلغاء تنشيط المدير بنجاح';
            } elseif ($action === 'delete') {
                $stmt = $conn->prepare("DELETE FROM admins WHERE admin_id = :admin_id");
                $stmt->bindParam(':admin_id', $admin_id);
                $stmt->execute();
                $success = 'تم حذف المدير بنجاح';
            } elseif ($action === 'promote') {
                $stmt = $conn->prepare("UPDATE admins SET is_super_admin = 1 WHERE admin_id = :admin_id");
                $stmt->bindParam(':admin_id', $admin_id);
                $stmt->execute();
                $success = 'تم ترقية المدير إلى مدير عام';
            } elseif ($action === 'demote') {
                $stmt = $conn->prepare("UPDATE admins SET is_super_admin = 0 WHERE admin_id = :admin_id");
                $stmt->bindParam(':admin_id', $admin_id);
                $stmt->execute();
                $success = 'تم تخفيض صلاحيات المدير';
            }
        }
    }
    
    // Handle new admin creation
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_admin'])) {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        $is_super_admin = isset($_POST['is_super_admin']) ? 1 : 0;
        
        if (empty($name) || empty($email) || empty($password)) {
            $error = 'جميع الحقول مطلوبة';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'البريد الإلكتروني غير صالح';
        } else {
            // Check if email already exists
            $stmt = $conn->prepare("SELECT COUNT(*) FROM admins WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->fetchColumn() > 0) {
                $error = 'البريد الإلكتروني مستخدم بالفعل';
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new admin
                $stmt = $conn->prepare("INSERT INTO admins (name, email, password, is_super_admin, is_active, created_at) VALUES (:name, :email, :password, :is_super_admin, 1, NOW())");
                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':password', $hashed_password);
                $stmt->bindParam(':is_super_admin', $is_super_admin);
                $stmt->execute();
                
                $success = 'تم إضافة المدير بنجاح';
            }
        }
    }
    
    // Check if admins table exists, if not create it
    try {
        $stmt = $conn->query("SHOW TABLES LIKE 'admins'");
        $admins_table_exists = $stmt->rowCount() > 0;
        
        if (!$admins_table_exists) {
            // Create admins table
            $sql = "CREATE TABLE admins (
                admin_id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                is_super_admin TINYINT(1) DEFAULT 0,
                is_active TINYINT(1) DEFAULT 1,
                last_login DATETIME DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
            
            $conn->exec($sql);
            
            // Create first admin user if table was just created
            $admin_name = 'مدير النظام';
            $admin_email = 'admin@tienda.com';
            $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("INSERT INTO admins (name, email, password, is_super_admin, is_active, created_at) VALUES (:name, :email, :password, 1, 1, NOW())");
            $stmt->bindParam(':name', $admin_name);
            $stmt->bindParam(':email', $admin_email);
            $stmt->bindParam(':password', $admin_password);
            $stmt->execute();
            
            $success = 'تم إنشاء جدول المديرين وإضافة مدير النظام الافتراضي. البريد الإلكتروني: admin@tienda.com، كلمة المرور: admin123';
        }
        
        // Get admins data with search functionality
        try {
            $query = "SELECT * FROM admins WHERE 1=1";
            $params = [];
            
            if (!empty($search)) {
                $query .= " AND (name LIKE :search OR email LIKE :search)";
                $search_param = "%$search%";
                $params[':search'] = $search_param;
            }
            
            $query .= " ORDER BY created_at DESC";
            
            $stmt = $conn->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $inner_e) {
            $error = 'خطأ في استعلام البيانات: ' . $inner_e->getMessage();
            $admins = [];
        }
        
        // Get total counts safely
        try {
            $stmt = $conn->query("SELECT COUNT(*) FROM admins");
            $total_admins = $stmt->fetchColumn();
        } catch (PDOException $e) {
            $total_admins = 0;
        }
        
        try {
            $stmt = $conn->query("SELECT COUNT(*) FROM admins WHERE is_super_admin = 1");
            $total_super_admins = $stmt->fetchColumn();
        } catch (PDOException $e) {
            $total_super_admins = 0;
        }
        
        try {
            $stmt = $conn->query("SELECT COUNT(*) FROM admins WHERE is_active = 1");
            $total_active_admins = $stmt->fetchColumn();
        } catch (PDOException $e) {
            $total_active_admins = 0;
        }
    } catch (PDOException $table_e) {
        $error = 'خطأ في التحقق من جدول المديرين: ' . $table_e->getMessage();
    }
    
} catch (PDOException $e) {
    $error = 'حدث خطأ في الاتصال بقاعدة البيانات: ' . $e->getMessage();
}

// Set current page for navigation
$current_page = 'admin_users';

// Include header
include 'includes/header.php';
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-user-shield text-primary mr-2"></i>إدارة مديري النظام</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="index.php">الرئيسية</a></li>
                    <li class="breadcrumb-item active">مديري النظام</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="content">
    <div class="container-fluid">
        <!-- Display messages -->
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
        
        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-lg-4 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?php echo $total_admins; ?></h3>
                        <p>إجمالي المديرين</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-users-cog"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?php echo $total_active_admins; ?></h3>
                        <p>المديرين النشطين</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?php echo $total_super_admins; ?></h3>
                        <p>المديرين العامين</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-user-shield"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Add Admin Form -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user-plus mr-2"></i>إضافة مدير جديد</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <form method="post" action="" id="add-admin-form">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="name">الاسم</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="email">البريد الإلكتروني</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="password">كلمة المرور</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="is_super_admin" name="is_super_admin">
                                    <label class="custom-control-label" for="is_super_admin">مدير عام (صلاحيات كاملة)</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 text-right">
                            <button type="submit" name="add_admin" class="btn btn-primary">
                                <i class="fas fa-plus-circle mr-1"></i> إضافة مدير
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Admins List -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-list mr-2"></i>قائمة المديرين</h3>
                <div class="card-tools">
                    <form method="get" action="" class="input-group input-group-sm" style="width: 250px;">
                        <input type="text" name="search" class="form-control float-right" placeholder="بحث..." value="<?php echo htmlspecialchars($search); ?>">
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-default">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th style="width: 50px">#</th>
                                <th>الاسم</th>
                                <th>البريد الإلكتروني</th>
                                <th>النوع</th>
                                <th>الحالة</th>
                                <th>آخر تسجيل دخول</th>
                                <th>تاريخ الإنشاء</th>
                                <th style="width: 200px">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($admins)): ?>
                                <tr>
                                    <td colspan="8" class="text-center">لا يوجد مديرين</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($admins as $index => $admin_user): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo htmlspecialchars($admin_user['name']); ?></td>
                                        <td><?php echo htmlspecialchars($admin_user['email']); ?></td>
                                        <td>
                                            <?php if ($admin_user['is_super_admin'] == 1): ?>
                                                <span class="badge badge-warning">مدير عام</span>
                                            <?php else: ?>
                                                <span class="badge badge-info">مدير</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($admin_user['is_active'] == 1): ?>
                                                <span class="badge badge-success">نشط</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">غير نشط</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo !empty($admin_user['last_login']) ? date('Y-m-d H:i', strtotime($admin_user['last_login'])) : 'لم يسجل الدخول بعد'; ?>
                                        </td>
                                        <td><?php echo date('Y-m-d', strtotime($admin_user['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <?php if ($admin_user['admin_id'] !== (int)$_SESSION['admin']['admin_id']): ?>
                                                    <?php if ($admin_user['is_active'] == 1): ?>
                                                        <a href="?action=deactivate&id=<?php echo $admin_user['admin_id']; ?>" class="btn btn-sm btn-warning" onclick="return confirm('هل أنت متأكد من إلغاء تنشيط هذا المدير؟');">
                                                            <i class="fas fa-user-times"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="?action=activate&id=<?php echo $admin_user['admin_id']; ?>" class="btn btn-sm btn-success">
                                                            <i class="fas fa-user-check"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($admin_user['is_super_admin'] == 1): ?>
                                                        <a href="?action=demote&id=<?php echo $admin_user['admin_id']; ?>" class="btn btn-sm btn-info" onclick="return confirm('هل أنت متأكد من تخفيض صلاحيات هذا المدير؟');">
                                                            <i class="fas fa-level-down-alt"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="?action=promote&id=<?php echo $admin_user['admin_id']; ?>" class="btn btn-sm btn-primary" onclick="return confirm('هل أنت متأكد من ترقية هذا المدير إلى مدير عام؟');">
                                                            <i class="fas fa-level-up-alt"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <a href="?action=delete&id=<?php echo $admin_user['admin_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('هل أنت متأكد من حذف هذا المدير نهائياً؟ لا يمكن التراجع عن هذا الإجراء.');">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">أنت</span>
                                                <?php endif; ?>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const form = document.getElementById('add-admin-form');
    
    if (form) {
        form.addEventListener('submit', function(event) {
            const name = document.getElementById('name').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value.trim();
            let hasError = false;
            
            if (name === '') {
                alert('الرجاء إدخال اسم المدير');
                hasError = true;
            } else if (email === '') {
                alert('الرجاء إدخال البريد الإلكتروني');
                hasError = true;
            } else if (password === '') {
                alert('الرجاء إدخال كلمة المرور');
                hasError = true;
            } else if (password.length < 6) {
                alert('كلمة المرور يجب أن تكون 6 أحرف على الأقل');
                hasError = true;
            }
            
            if (hasError) {
                event.preventDefault();
            }
        });
    }
});
</script>

<?php
// Include footer
include 'includes/footer.php';
?>
