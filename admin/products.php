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
$products = [];
$error = '';
$success = '';

// Handle product status change
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $product_id = (int)$_GET['id'];
    
    try {
        // Connect to database
        $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Check if products table exists
        $tables = $conn->query("SHOW TABLES LIKE 'products'")->fetchAll();
        
        if (count($tables) === 0) {
            $error = 'u062cu062fu0648u0644 u0627u0644u0645u0646u062au062cu0627u062a u063au064au0631 u0645u0648u062cu0648u062f. u064au0631u062cu0649 <a href="setup_admin.php">u0625u0639u062fu0627u062f u0627u0644u0646u0638u0627u0645</a> u0623u0648u0644u0627u064b.';
        } else {
            if ($action === 'activate') {
                $stmt = $conn->prepare("UPDATE products SET is_active = 1 WHERE product_id = :product_id");
                $stmt->bindParam(':product_id', $product_id);
                $stmt->execute();
                $success = 'u062au0645 u062au0646u0634u064au0637 u0627u0644u0645u0646u062au062c u0628u0646u062cu0627u062d';
            } elseif ($action === 'deactivate') {
                $stmt = $conn->prepare("UPDATE products SET is_active = 0 WHERE product_id = :product_id");
                $stmt->bindParam(':product_id', $product_id);
                $stmt->execute();
                $success = 'u062au0645 u0625u0644u063au0627u0621 u062au0646u0634u064au0637 u0627u0644u0645u0646u062au062c u0628u0646u062cu0627u062d';
            } elseif ($action === 'feature') {
                $stmt = $conn->prepare("UPDATE products SET is_featured = 1 WHERE product_id = :product_id");
                $stmt->bindParam(':product_id', $product_id);
                $stmt->execute();
                $success = 'u062au0645 u062au0645u064au064au0632 u0627u0644u0645u0646u062au062c u0628u0646u062cu0627u062d';
            } elseif ($action === 'unfeature') {
                $stmt = $conn->prepare("UPDATE products SET is_featured = 0 WHERE product_id = :product_id");
                $stmt->bindParam(':product_id', $product_id);
                $stmt->execute();
                $success = 'u062au0645 u0625u0644u063au0627u0621 u062au0645u064au064au0632 u0627u0644u0645u0646u062au062c u0628u0646u062cu0627u062d';
            } elseif ($action === 'delete') {
                $stmt = $conn->prepare("DELETE FROM products WHERE product_id = :product_id");
                $stmt->bindParam(':product_id', $product_id);
                $stmt->execute();
                $success = 'u062au0645 u062du0630u0641 u0627u0644u0645u0646u062au062c u0628u0646u062cu0627u062d';
            }
            
            // Log the action
            $logStmt = $conn->prepare("INSERT INTO admin_log (admin_id, action, module, description, ip_address) VALUES (:admin_id, :action, 'products', :description, :ip_address)");
            $logStmt->bindParam(':admin_id', $admin['id']);
            $logStmt->bindParam(':action', $action);
            $logStmt->bindParam(':description', "Admin performed $action on product ID: $product_id");
            $logStmt->bindParam(':ip_address', $_SERVER['REMOTE_ADDR']);
            $logStmt->execute();
        }
        
    } catch (PDOException $e) {
        $error = 'u062du062fu062b u062eu0637u0623: ' . $e->getMessage();
    }
}

// Get all products
try {
    // Connect to database
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if products table exists
    $tables = $conn->query("SHOW TABLES LIKE 'products'")->fetchAll();
    
    if (count($tables) > 0) {
        // Get products with category and brand names
        $query = "SELECT p.*, c.name as category_name, b.name as brand_name, 
                    (SELECT image_path FROM product_images WHERE product_id = p.product_id AND is_primary = 1 LIMIT 1) as primary_image
                  FROM products p
                  LEFT JOIN categories c ON p.category_id = c.category_id
                  LEFT JOIN brands b ON p.brand_id = b.brand_id
                  ORDER BY p.created_at DESC";
        $stmt = $conn->query($query);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
} catch (PDOException $e) {
    $error = 'حدث خطأ في الاتصال بقاعدة البيانات: ' . $e->getMessage();
}

// Set current page for navigation
$current_page = 'products';

// Include header
include 'includes/header.php';
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-box-open text-primary mr-2"></i>المنتجات</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-left">
                    <li class="breadcrumb-item"><a href="index.php"><i class="fas fa-home"></i></a></li>
                    <li class="breadcrumb-item active">المنتجات</li>
                </ol>
            </div>
        </div>
        
        <!-- Stats Cards -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?php echo count($products); ?></h3>
                        <p>إجمالي المنتجات</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-boxes"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?php 
                            $activeCount = 0;
                            foreach($products as $product) {
                                if ($product['is_active'] == 1) $activeCount++;
                            }
                            echo $activeCount;
                        ?></h3>
                        <p>المنتجات النشطة</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?php 
                            $featuredCount = 0;
                            foreach($products as $product) {
                                if (isset($product['is_featured']) && $product['is_featured'] == 1) $featuredCount++;
                            }
                            echo $featuredCount;
                        ?></h3>
                        <p>المنتجات المميزة</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-star"></i>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3><?php 
                            $inactiveCount = 0;
                            foreach($products as $product) {
                                if ($product['is_active'] == 0) $inactiveCount++;
                            }
                            echo $inactiveCount;
                        ?></h3>
                        <p>المنتجات غير النشطة</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-ban"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="content">
    <div class="container-fluid">
        <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <i class="fas fa-check-circle mr-2"></i><?php echo $success; ?>
        </div>
        <?php endif; ?>
        
        <?php if (count($tables) === 0): ?>
        <div class="alert alert-warning alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <i class="fas fa-exclamation-triangle mr-2"></i>جدول المنتجات غير موجود. يرجى <a href="setup_admin.php">إعداد النظام</a> أولاً.
        </div>
        <?php else: ?>
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="input-group">
                    <input type="text" class="form-control" id="searchInput" placeholder="البحث عن منتج (الاسم، الوصف، SKU)..." aria-label="البحث" aria-describedby="basic-addon2">
                    <div class="input-group-append">
                        <button class="btn btn-primary" type="button">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="col-md-4 text-left">
                <a href="add_product.php" class="btn btn-primary" data-toggle="tooltip" title="إضافة منتج جديد">
                    <i class="fas fa-plus mr-1"></i> منتج جديد
                </a>
                
                <div class="btn-group mr-2">
                    <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-filter mr-1"></i> تصفية
                    </button>
                    <div class="dropdown-menu">
                        <a class="dropdown-item" href="?filter=all">كل المنتجات</a>
                        <a class="dropdown-item" href="?filter=active">المنتجات النشطة</a>
                        <a class="dropdown-item" href="?filter=inactive">المنتجات غير النشطة</a>
                        <a class="dropdown-item" href="?filter=featured">المنتجات المميزة</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="?filter=low_stock">مخزون منخفض</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0"><i class="fas fa-box-open text-primary mr-2"></i>قائمة المنتجات</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                    <button type="button" class="btn btn-tool" data-card-widget="maximize">
                        <i class="fas fa-expand"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover text-nowrap datatable">
                        <thead>
                            <tr>
                                <th style="width: 40px">#</th>
                                <th style="width: 70px">الصورة</th>
                                <th>الاسم</th>
                                <th>السعر</th>
                                <th>الفئة</th>
                                <th>المخزون</th>
                                <th>SKU</th>
                                <th style="width: 80px">الحالة</th>
                                <th style="width: 70px">مميز</th>
                                <th style="width: 150px">العمليات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="10" class="text-center">لا يوجد منتجات</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($products as $index => $product): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td>
                                    <?php if (!empty($product['primary_image'])): ?>
                                    <img src="<?php echo htmlspecialchars($product['primary_image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" width="50" height="50" style="object-fit: cover;">
                                    <?php else: ?>
                                    <img src="../assets/images/no-image.png" alt="No Image" width="50" height="50">
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td>
                                    <?php if (!empty($product['sale_price'])): ?>
                                    <span class="text-danger"><?php echo number_format($product['sale_price'], 2); ?> $</span>
                                    <small class="text-muted"><s><?php echo number_format($product['price'], 2); ?> $</s></small>
                                    <?php else: ?>
                                    <?php echo number_format($product['price'], 2); ?> $
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($product['brand_name'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php if ($product['stock_quantity'] > 10): ?>
                                    <span class="badge badge-success"><?php echo $product['stock_quantity']; ?></span>
                                    <?php elseif ($product['stock_quantity'] > 0): ?>
                                    <span class="badge badge-warning"><?php echo $product['stock_quantity']; ?></span>
                                    <?php else: ?>
                                    <span class="badge badge-danger">0</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($product['is_active'] == 1): ?>
                                    <span class="badge badge-success"><i class="fas fa-check-circle mr-1"></i>نشط</span>
                                    <?php else: ?>
                                    <span class="badge badge-danger"><i class="fas fa-ban mr-1"></i>غير نشط</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($product['is_featured']) && $product['is_featured'] == 1): ?>
                                    <span class="badge badge-primary">مميز</span>
                                    <?php else: ?>
                                    <span class="badge badge-secondary">عادي</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="edit_product.php?id=<?php echo $product['product_id']; ?>" class="btn btn-sm btn-outline-info" data-toggle="tooltip" title="تعديل المنتج">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        
                                        <?php if ($product['is_active'] == 1): ?>
                                        <a href="?action=deactivate&id=<?php echo $product['product_id']; ?>" class="btn btn-sm btn-outline-warning" data-toggle="tooltip" title="إلغاء تنشيط" onclick="return confirm('هل أنت متأكد من إلغاء تنشيط هذا المنتج؟')">
                                            <i class="fas fa-ban"></i>
                                        </a>
                                        <?php else: ?>
                                        <a href="?action=activate&id=<?php echo $product['product_id']; ?>" class="btn btn-sm btn-outline-success" data-toggle="tooltip" title="تنشيط المنتج">
                                            <i class="fas fa-check-circle"></i>
                                        </a>
                                        <?php endif; ?>
                                        
                                        <?php if (isset($product['featured']) && $product['featured'] == 1): ?>
                                        <a href="?action=unfeature&id=<?php echo $product['product_id']; ?>" class="btn btn-sm btn-outline-secondary" data-toggle="tooltip" title="إلغاء تمييز">
                                            <i class="far fa-star"></i>
                                        </a>
                                        <?php else: ?>
                                        <a href="?action=feature&id=<?php echo $product['product_id']; ?>" class="btn btn-sm btn-outline-primary" data-toggle="tooltip" title="تمييز المنتج">
                                            <i class="fas fa-star"></i>
                                        </a>
                                        <?php endif; ?>
                                        
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-right">
                                                <a class="dropdown-item" href="view_product.php?id=<?php echo $product['product_id']; ?>">
                                                    <i class="fas fa-eye text-info mr-2"></i> عرض التفاصيل
                                                </a>
                                                <a class="dropdown-item" href="duplicate_product.php?id=<?php echo $product['product_id']; ?>">
                                                    <i class="fas fa-copy text-primary mr-2"></i> نسخ المنتج
                                                </a>
                                                <div class="dropdown-divider"></div>
                                                <a class="dropdown-item text-danger" href="?action=delete&id=<?php echo $product['product_id']; ?>" onclick="return confirm('هل أنت متأكد من حذف هذا المنتج؟')">
                                                    <i class="fas fa-trash mr-2"></i> حذف المنتج
                                                </a>
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
        <?php endif; ?>
    </div>
</div>

<?php
// Include footer
include 'includes/footer.php';
?>
