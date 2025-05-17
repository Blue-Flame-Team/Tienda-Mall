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
$orders = [];
$error = '';
$success = '';

// Statistics array with default values
$stats = [
    'total' => 0,
    'pending' => 0,
    'processing' => 0,
    'shipped' => 0,
    'delivered' => 0,
    'canceled' => 0,
    'total_revenue' => 0
];

// Handle order status change
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $order_id = (int)$_GET['id'];
    
    try {
        // Connect to database
        $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        if ($action === 'mark_processing') {
            $stmt = $conn->prepare("UPDATE orders SET status = 'processing', updated_at = NOW() WHERE order_id = :order_id");
            $stmt->bindParam(':order_id', $order_id);
            $stmt->execute();
            $success = 'تم تحديث حالة الطلب إلى قيد المعالجة';
        } elseif ($action === 'mark_shipped') {
            $stmt = $conn->prepare("UPDATE orders SET status = 'shipped', updated_at = NOW() WHERE order_id = :order_id");
            $stmt->bindParam(':order_id', $order_id);
            $stmt->execute();
            $success = 'تم تحديث حالة الطلب إلى تم الشحن';
        } elseif ($action === 'mark_delivered') {
            $stmt = $conn->prepare("UPDATE orders SET status = 'delivered', updated_at = NOW() WHERE order_id = :order_id");
            $stmt->bindParam(':order_id', $order_id);
            $stmt->execute();
            $success = 'تم تحديث حالة الطلب إلى تم التسليم';
        } elseif ($action === 'mark_canceled') {
            $stmt = $conn->prepare("UPDATE orders SET status = 'canceled', updated_at = NOW() WHERE order_id = :order_id");
            $stmt->bindParam(':order_id', $order_id);
            $stmt->execute();
            $success = 'تم إلغاء الطلب بنجاح';
        }
    } catch (PDOException $e) {
        $error = 'حدث خطأ: ' . $e->getMessage();
    }
}

// Get orders
try {
    // Connect to database
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if orders table exists
    $tables = $conn->query("SHOW TABLES LIKE 'orders'")->fetchAll();
    if (count($tables) > 0) {
        // Get all orders with user details
        $stmt = $conn->prepare(
            "SELECT o.*, u.first_name, u.last_name, u.email 
             FROM orders o 
             LEFT JOIN users u ON o.user_id = u.user_id 
             ORDER BY o.created_at DESC"
        );
        $stmt->execute();
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Count statistics
        $stmt = $conn->query("SELECT COUNT(*) FROM orders");
        $stats['total'] = $stmt->fetchColumn();
        
        $stmt = $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'");
        $stats['pending'] = $stmt->fetchColumn();
        
        $stmt = $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'processing'");
        $stats['processing'] = $stmt->fetchColumn();
        
        $stmt = $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'shipped'");
        $stats['shipped'] = $stmt->fetchColumn();
        
        $stmt = $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'delivered'");
        $stats['delivered'] = $stmt->fetchColumn();
        
        $stmt = $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'canceled'");
        $stats['canceled'] = $stmt->fetchColumn();
        
        $stmt = $conn->query("SELECT SUM(total_amount) FROM orders WHERE status != 'canceled'");
        $stats['total_revenue'] = $stmt->fetchColumn() ?: 0;
    }
} catch (PDOException $e) {
    $error = 'حدث خطأ: ' . $e->getMessage();
}

// Set current page for navigation
$current_page = 'orders';

// Include header
include 'includes/header.php';
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-shopping-cart text-primary mr-2"></i>الطلبات</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="index.php">الرئيسية</a></li>
                    <li class="breadcrumb-item active">الطلبات</li>
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
                        <h3><?php echo $stats['total']; ?></h3>
                        <p>إجمالي الطلبات</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?php echo $stats['pending'] + $stats['processing']; ?></h3>
                        <p>طلبات قيد المعالجة</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-spinner"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?php echo $stats['delivered']; ?></h3>
                        <p>طلبات تم تسليمها</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3><?php echo number_format($stats['total_revenue'], 2); ?> $</h3>
                        <p>إجمالي الإيرادات</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                </div>
            </div>
        </div>
        
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
        
        <!-- Orders Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title"><i class="fas fa-shopping-cart mr-2"></i>قائمة الطلبات</h3>
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
                                <th>رقم الطلب</th>
                                <th>العميل</th>
                                <th>تاريخ الطلب</th>
                                <th>المبلغ</th>
                                <th>حالة الطلب</th>
                                <th style="width: 150px">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="7" class="text-center">لا توجد طلبات حتى الآن</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($orders as $index => $order): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                <td>
                                    <?php 
                                    if (!empty($order['first_name'])) {
                                        echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) . '<br>';
                                        echo '<small>' . htmlspecialchars($order['email']) . '</small>';
                                    } else {
                                        echo 'ضيف';
                                    }
                                    ?>
                                </td>
                                <td><?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?></td>
                                <td><?php echo number_format($order['total_amount'], 2); ?> $</td>
                                <td>
                                    <?php if ($order['status'] === 'pending'): ?>
                                    <span class="badge badge-secondary">قيد الانتظار</span>
                                    <?php elseif ($order['status'] === 'processing'): ?>
                                    <span class="badge badge-warning">قيد المعالجة</span>
                                    <?php elseif ($order['status'] === 'shipped'): ?>
                                    <span class="badge badge-info">تم الشحن</span>
                                    <?php elseif ($order['status'] === 'delivered'): ?>
                                    <span class="badge badge-success">تم التسليم</span>
                                    <?php elseif ($order['status'] === 'canceled'): ?>
                                    <span class="badge badge-danger">ملغي</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="view_order.php?id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-info" data-toggle="tooltip" title="عرض الطلب">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-toggle="dropdown" aria-expanded="false" data-toggle="tooltip" title="تغيير الحالة">
                                            <i class="fas fa-cog"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            <a class="dropdown-item" href="?action=mark_processing&id=<?php echo $order['order_id']; ?>">
                                                <i class="fas fa-spinner mr-2"></i> قيد المعالجة
                                            </a>
                                            <a class="dropdown-item" href="?action=mark_shipped&id=<?php echo $order['order_id']; ?>">
                                                <i class="fas fa-truck mr-2"></i> تم الشحن
                                            </a>
                                            <a class="dropdown-item" href="?action=mark_delivered&id=<?php echo $order['order_id']; ?>">
                                                <i class="fas fa-check-circle mr-2"></i> تم التسليم
                                            </a>
                                            <div class="dropdown-divider"></div>
                                            <a class="dropdown-item text-danger" href="?action=mark_canceled&id=<?php echo $order['order_id']; ?>" onclick="return confirm('هل أنت متأكد من إلغاء هذا الطلب؟');">
                                                <i class="fas fa-times-circle mr-2"></i> إلغاء الطلب
                                            </a>
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

<?php
// Include footer
include 'includes/footer.php';
?>
