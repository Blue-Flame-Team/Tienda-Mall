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
$users = [];
$error = '';
$success = '';

// Handle user status change
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $user_id = (int)$_GET['id'];
    
    try {
        // Connect to database
        $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        if ($action === 'activate') {
            $stmt = $conn->prepare("UPDATE users SET is_active = 1 WHERE user_id = :user_id");
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            $success = 'تم تنشيط المستخدم بنجاح';
        } elseif ($action === 'deactivate') {
            $stmt = $conn->prepare("UPDATE users SET is_active = 0 WHERE user_id = :user_id");
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            $success = 'تم إلغاء تنشيط المستخدم بنجاح';
        } elseif ($action === 'delete') {
            $stmt = $conn->prepare("DELETE FROM users WHERE user_id = :user_id");
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            $success = 'تم حذف المستخدم بنجاح';
        }
        
        // Log the action
        $admin_id = $admin['id'];
        $log_action = $action;
        $description = "Admin performed $action on user ID: $user_id";
        $ip_address = $_SERVER['REMOTE_ADDR'];
        
        $logStmt = $conn->prepare("INSERT INTO admin_log (admin_id, action, module, description, ip_address) VALUES (:admin_id, :action, 'users', :description, :ip_address)");
        $logStmt->bindParam(':admin_id', $admin_id);
        $logStmt->bindParam(':action', $log_action);
        $logStmt->bindParam(':description', $description);
        $logStmt->bindParam(':ip_address', $ip_address);
        $logStmt->execute();
        
    } catch (PDOException $e) {
        $error = 'حدث خطأ: ' . $e->getMessage();
    }
}

// Get all users
try {
    // Connect to database
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get users
    $stmt = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = 'حدث خطأ في الاتصال بقاعدة البيانات: ' . $e->getMessage();
}

// Set current page for navigation
$current_page = 'users';

// Include header
include 'includes/header.php';
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">إدارة المستخدمين</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="index.php">الرئيسية</a></li>
                    <li class="breadcrumb-item active">المستخدمين</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="content">
    <div class="container-fluid">
        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?php echo count($users); ?></h3>
                        <p>إجمالي المستخدمين</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?php 
                        $active_users = array_filter($users, function($user) {
                            return isset($user['is_active']) && $user['is_active'] == 1;
                        });
                        echo count($active_users);
                        ?></h3>
                        <p>المستخدمين النشطين</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?php 
                        $inactive_users = array_filter($users, function($user) {
                            return !isset($user['is_active']) || $user['is_active'] != 1;
                        });
                        echo count($inactive_users);
                        ?></h3>
                        <p>المستخدمين غير النشطين</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-user-slash"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3><?php 
                        $new_users = array_filter($users, function($user) {
                            $one_week_ago = date('Y-m-d H:i:s', strtotime('-7 days'));
                            return $user['created_at'] >= $one_week_ago;
                        });
                        echo count($new_users);
                        ?></h3>
                        <p>مستخدمين جدد (آخر 7 أيام)</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
        </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">قائمة المستخدمين</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped datatable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>اسم المستخدم</th>
                                <th>البريد الإلكتروني</th>
                                <th>تاريخ التسجيل</th>
                                <th>آخر تسجيل دخول</th>
                                <th>الحالة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="7" class="text-center">لا يوجد مستخدمين</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($users as $index => $user): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <?php if (!empty($user['last_login'])): ?>
                                        <?php echo date('Y-m-d H:i', strtotime($user['last_login'])); ?>
                                    <?php else: ?>
                                        <span class="text-muted">لم يسجل الدخول بعد</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (isset($user['is_active']) && $user['is_active'] == 1): ?>
                                        <span class="badge badge-success">نشط</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">غير نشط</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="view_user.php?id=<?php echo $user['user_id']; ?>" class="btn btn-info btn-sm">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    
                                    <?php if (isset($user['is_active']) && $user['is_active'] == 1): ?>
                                    <a href="?action=deactivate&id=<?php echo $user['user_id']; ?>" class="btn btn-warning btn-sm" onclick="return confirm('هل أنت متأكد من إلغاء تنشيط هذا المستخدم؟');">
                                        <i class="fas fa-ban"></i>
                                    </a>
                                    <?php else: ?>
                                    <a href="?action=activate&id=<?php echo $user['user_id']; ?>" class="btn btn-success btn-sm" onclick="return confirm('هل أنت متأكد من تنشيط هذا المستخدم؟');">
                                        <i class="fas fa-check"></i>
                                    </a>
                                    <?php endif; ?>
                                    
                                    <a href="?action=delete&id=<?php echo $user['user_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('هل أنت متأكد من حذف هذا المستخدم؟ لا يمكن التراجع عن هذا الإجراء.');">
                                        <i class="fas fa-trash"></i>
                                    </a>
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

<?php
// Include footer
include 'includes/footer.php';
?>
