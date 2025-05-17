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
$reviews = [];
$error = '';
$success = '';
$pending_count = 0;
$approved_count = 0;
$rejected_count = 0;

// Handle actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $review_id = (int)$_GET['id'];
    $action = $_GET['action'];
    
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        if ($action === 'approve') {
            // Approve review
            $stmt = $conn->prepare("UPDATE reviews SET status = 'approved' WHERE review_id = :review_id");
            $stmt->bindParam(':review_id', $review_id);
            $stmt->execute();
            $success = 'تم الموافقة على المراجعة بنجاح';
        } else if ($action === 'reject') {
            // Reject review
            $stmt = $conn->prepare("UPDATE reviews SET status = 'rejected' WHERE review_id = :review_id");
            $stmt->bindParam(':review_id', $review_id);
            $stmt->execute();
            $success = 'تم رفض المراجعة بنجاح';
        } else if ($action === 'delete') {
            // Delete review
            $stmt = $conn->prepare("DELETE FROM reviews WHERE review_id = :review_id");
            $stmt->bindParam(':review_id', $review_id);
            $stmt->execute();
            $success = 'تم حذف المراجعة بنجاح';
        }
    } catch (PDOException $e) {
        $error = 'حدث خطأ: ' . $e->getMessage();
    }
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$product_filter = isset($_GET['product']) ? (int)$_GET['product'] : 0;

// Get reviews
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if reviews table exists
    $tables = $conn->query("SHOW TABLES LIKE 'reviews'")->fetchAll();
    if (count($tables) === 0) {
        // Create reviews table if it doesn't exist
        $conn->exec("CREATE TABLE reviews (
            review_id INT(11) AUTO_INCREMENT PRIMARY KEY,
            product_id INT(11) NOT NULL,
            user_id INT(11) DEFAULT NULL,
            guest_name VARCHAR(255) DEFAULT NULL,
            guest_email VARCHAR(255) DEFAULT NULL,
            rating INT(1) NOT NULL,
            comment TEXT,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            created_at DATETIME NOT NULL,
            updated_at DATETIME DEFAULT NULL,
            FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        $success = 'تم إنشاء جدول المراجعات بنجاح';
    }
    
    // Count reviews by status
    $stmt = $conn->query("SELECT status, COUNT(*) as count FROM reviews GROUP BY status");
    $status_counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($status_counts as $count) {
        if ($count['status'] === 'pending') {
            $pending_count = $count['count'];
        } else if ($count['status'] === 'approved') {
            $approved_count = $count['count'];
        } else if ($count['status'] === 'rejected') {
            $rejected_count = $count['count'];
        }
    }
    
    // Get all products for filter
    $stmt = $conn->query("SELECT product_id, name FROM products ORDER BY name");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Build query for filtered reviews
    $query = "SELECT r.*, p.name as product_name, 
               COALESCE(u.first_name, r.guest_name) as reviewer_name, 
               COALESCE(u.email, r.guest_email) as reviewer_email 
             FROM reviews r 
             LEFT JOIN products p ON r.product_id = p.product_id 
             LEFT JOIN users u ON r.user_id = u.user_id ";
    $params = [];
    
    // Add filters
    $whereConditions = [];
    if ($status_filter !== 'all') {
        $whereConditions[] = "r.status = :status";
        $params[':status'] = $status_filter;
    }
    if ($product_filter > 0) {
        $whereConditions[] = "r.product_id = :product_id";
        $params[':product_id'] = $product_filter;
    }
    
    if (!empty($whereConditions)) {
        $query .= " WHERE " . implode(" AND ", $whereConditions);
    }
    
    // Add order by
    $query .= " ORDER BY r.created_at DESC";
    
    $stmt = $conn->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'حدث خطأ: ' . $e->getMessage();
}

// Set current page for navigation
$current_page = 'reviews';

// Include header
include 'includes/header.php';
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-star text-warning mr-2"></i>إدارة مراجعات المنتجات</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="index.php">الرئيسية</a></li>
                    <li class="breadcrumb-item active">مراجعات المنتجات</li>
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
        
        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-lg-4 col-md-6">
                <div class="card mb-4 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">المراجعات قيد الانتظار</h6>
                                <h2 class="mb-0"><?php echo $pending_count; ?></h2>
                            </div>
                            <div class="icon-circle bg-warning">
                                <i class="fas fa-clock"></i>
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="?status=pending" class="text-warning small">
                                <i class="fas fa-eye mr-1"></i> عرض المراجعات قيد الانتظار
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <div class="card mb-4 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">المراجعات المعتمدة</h6>
                                <h2 class="mb-0"><?php echo $approved_count; ?></h2>
                            </div>
                            <div class="icon-circle bg-success">
                                <i class="fas fa-check"></i>
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="?status=approved" class="text-success small">
                                <i class="fas fa-eye mr-1"></i> عرض المراجعات المعتمدة
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6">
                <div class="card mb-4 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2">المراجعات المرفوضة</h6>
                                <h2 class="mb-0"><?php echo $rejected_count; ?></h2>
                            </div>
                            <div class="icon-circle bg-danger">
                                <i class="fas fa-times"></i>
                            </div>
                        </div>
                        <div class="mt-3">
                            <a href="?status=rejected" class="text-danger small">
                                <i class="fas fa-eye mr-1"></i> عرض المراجعات المرفوضة
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filter -->
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-filter mr-2"></i>تصفية المراجعات</h3>
            </div>
            <div class="card-body">
                <form method="get" class="row">
                    <div class="col-md-5">
                        <div class="form-group">
                            <label>الحالة</label>
                            <select name="status" class="form-control">
                                <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>جميع المراجعات</option>
                                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>قيد الانتظار</option>
                                <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>المعتمدة</option>
                                <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>المرفوضة</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="form-group">
                            <label>المنتج</label>
                            <select name="product" class="form-control">
                                <option value="0">جميع المنتجات</option>
                                <?php foreach ($products as $product): ?>
                                <option value="<?php echo $product['product_id']; ?>" <?php echo $product_filter === (int)$product['product_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($product['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label class="d-block">&nbsp;</label>
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-filter mr-1"></i> تصفية
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Reviews List -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">
                    <i class="fas fa-list mr-2"></i>قائمة المراجعات
                    <?php if ($status_filter !== 'all') echo ' - ' . ($status_filter === 'pending' ? 'قيد الانتظار' : ($status_filter === 'approved' ? 'المعتمدة' : 'المرفوضة')); ?>
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover text-nowrap">
                        <thead>
                            <tr>
                                <th style="width: 50px">#</th>
                                <th>المنتج</th>
                                <th>المستخدم</th>
                                <th>التقييم</th>
                                <th>التعليق</th>
                                <th>التاريخ</th>
                                <th>الحالة</th>
                                <th style="width: 160px">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($reviews)): ?>
                            <tr>
                                <td colspan="8" class="text-center">لا توجد مراجعات متاحة</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($reviews as $index => $review): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td>
                                    <a href="edit_product.php?id=<?php echo $review['product_id']; ?>">
                                        <?php echo htmlspecialchars($review['product_name']); ?>
                                    </a>
                                </td>
                                <td>
                                    <?php if ($review['user_id']): ?>
                                    <a href="users.php?action=view&id=<?php echo $review['user_id']; ?>">
                                        <?php echo htmlspecialchars($review['reviewer_name']); ?>
                                    </a>
                                    <?php else: ?>
                                    <?php echo htmlspecialchars($review['reviewer_name']) . ' (زائر)'; ?>
                                    <div class="small text-muted"><?php echo htmlspecialchars($review['reviewer_email']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="text-warning">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <?php if ($i <= $review['rating']): ?>
                                                <i class="fas fa-star"></i>
                                            <?php else: ?>
                                                <i class="far fa-star"></i>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if (strlen($review['comment']) > 50): ?>
                                    <a href="#" data-toggle="modal" data-target="#reviewModal<?php echo $review['review_id']; ?>">
                                        <?php echo htmlspecialchars(substr($review['comment'], 0, 50)) . '...'; ?>
                                    </a>
                                    <?php else: ?>
                                    <?php echo htmlspecialchars($review['comment']); ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo date('Y-m-d', strtotime($review['created_at'])); ?>
                                    <div class="small text-muted"><?php echo date('H:i', strtotime($review['created_at'])); ?></div>
                                </td>
                                <td>
                                    <?php if ($review['status'] === 'pending'): ?>
                                    <span class="badge badge-warning">قيد الانتظار</span>
                                    <?php elseif ($review['status'] === 'approved'): ?>
                                    <span class="badge badge-success">معتمدة</span>
                                    <?php elseif ($review['status'] === 'rejected'): ?>
                                    <span class="badge badge-danger">مرفوضة</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <?php if ($review['status'] === 'pending'): ?>
                                        <a href="?action=approve&id=<?php echo $review['review_id']; ?>&status=<?php echo $status_filter; ?>&product=<?php echo $product_filter; ?>" class="btn btn-sm btn-success" title="الموافقة">
                                            <i class="fas fa-check"></i>
                                        </a>
                                        <a href="?action=reject&id=<?php echo $review['review_id']; ?>&status=<?php echo $status_filter; ?>&product=<?php echo $product_filter; ?>" class="btn btn-sm btn-danger" title="الرفض">
                                            <i class="fas fa-times"></i>
                                        </a>
                                        <?php elseif ($review['status'] === 'approved'): ?>
                                        <a href="?action=reject&id=<?php echo $review['review_id']; ?>&status=<?php echo $status_filter; ?>&product=<?php echo $product_filter; ?>" class="btn btn-sm btn-danger" title="الرفض">
                                            <i class="fas fa-times"></i>
                                        </a>
                                        <?php elseif ($review['status'] === 'rejected'): ?>
                                        <a href="?action=approve&id=<?php echo $review['review_id']; ?>&status=<?php echo $status_filter; ?>&product=<?php echo $product_filter; ?>" class="btn btn-sm btn-success" title="الموافقة">
                                            <i class="fas fa-check"></i>
                                        </a>
                                        <?php endif; ?>
                                        <a href="?action=delete&id=<?php echo $review['review_id']; ?>&status=<?php echo $status_filter; ?>&product=<?php echo $product_filter; ?>" class="btn btn-sm btn-danger" title="حذف" onclick="return confirm('هل أنت متأكد من حذف هذه المراجعة؟ هذا الإجراء لا يمكن التراجع عنه.')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                    
                                    <!-- Review Modal -->
                                    <div class="modal fade" id="reviewModal<?php echo $review['review_id']; ?>" tabindex="-1" role="dialog" aria-labelledby="reviewModalLabel<?php echo $review['review_id']; ?>" aria-hidden="true">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="reviewModalLabel<?php echo $review['review_id']; ?>">تفاصيل المراجعة</h5>
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    <h6>المنتج: <?php echo htmlspecialchars($review['product_name']); ?></h6>
                                                    <h6>المستخدم: <?php echo htmlspecialchars($review['reviewer_name']); ?> <?php echo $review['user_id'] ? '' : '(زائر)'; ?></h6>
                                                    <h6>التقييم: 
                                                        <span class="text-warning">
                                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                                <?php if ($i <= $review['rating']): ?>
                                                                    <i class="fas fa-star"></i>
                                                                <?php else: ?>
                                                                    <i class="far fa-star"></i>
                                                                <?php endif; ?>
                                                            <?php endfor; ?>
                                                        </span>
                                                    </h6>
                                                    <h6>التاريخ: <?php echo date('Y-m-d H:i', strtotime($review['created_at'])); ?></h6>
                                                    <hr>
                                                    <h6>التعليق:</h6>
                                                    <p><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                                                </div>
                                                <div class="modal-footer">
                                                    <?php if ($review['status'] === 'pending'): ?>
                                                    <a href="?action=approve&id=<?php echo $review['review_id']; ?>&status=<?php echo $status_filter; ?>&product=<?php echo $product_filter; ?>" class="btn btn-success">
                                                        <i class="fas fa-check mr-1"></i> الموافقة
                                                    </a>
                                                    <a href="?action=reject&id=<?php echo $review['review_id']; ?>&status=<?php echo $status_filter; ?>&product=<?php echo $product_filter; ?>" class="btn btn-danger">
                                                        <i class="fas fa-times mr-1"></i> الرفض
                                                    </a>
                                                    <?php elseif ($review['status'] === 'approved'): ?>
                                                    <a href="?action=reject&id=<?php echo $review['review_id']; ?>&status=<?php echo $status_filter; ?>&product=<?php echo $product_filter; ?>" class="btn btn-danger">
                                                        <i class="fas fa-times mr-1"></i> الرفض
                                                    </a>
                                                    <?php elseif ($review['status'] === 'rejected'): ?>
                                                    <a href="?action=approve&id=<?php echo $review['review_id']; ?>&status=<?php echo $status_filter; ?>&product=<?php echo $product_filter; ?>" class="btn btn-success">
                                                        <i class="fas fa-check mr-1"></i> الموافقة
                                                    </a>
                                                    <?php endif; ?>
                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">إغلاق</button>
                                                </div>
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

<?php
// Include footer
include 'includes/footer.php';
?>
