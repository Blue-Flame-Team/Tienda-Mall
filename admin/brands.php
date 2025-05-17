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
$brands = [];
$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new brand
    if (isset($_POST['add_brand'])) {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        
        if (empty($name)) {
            $error = 'يرجى إدخال اسم العلامة التجارية';
        } else {
            try {
                $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Check if brand already exists
                $stmt = $conn->prepare("SELECT COUNT(*) FROM brands WHERE name = :name");
                $stmt->bindParam(':name', $name);
                $stmt->execute();
                
                if ($stmt->fetchColumn() > 0) {
                    $error = 'هذه العلامة التجارية موجودة بالفعل';
                } else {
                    // Insert new brand
                    $stmt = $conn->prepare("INSERT INTO brands (name, description, created_at) VALUES (:name, :description, NOW())");
                    $stmt->bindParam(':name', $name);
                    $stmt->bindParam(':description', $description);
                    $stmt->execute();
                    
                    $success = 'تم إضافة العلامة التجارية بنجاح';
                }
            } catch (PDOException $e) {
                $error = 'حدث خطأ: ' . $e->getMessage();
            }
        }
    }
    
    // Edit brand
    if (isset($_POST['edit_brand'])) {
        $brand_id = (int)$_POST['brand_id'];
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        
        if (empty($name)) {
            $error = 'يرجى إدخال اسم العلامة التجارية';
        } else {
            try {
                $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Check if brand already exists with this name (excluding current one)
                $stmt = $conn->prepare("SELECT COUNT(*) FROM brands WHERE name = :name AND brand_id != :brand_id");
                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':brand_id', $brand_id);
                $stmt->execute();
                
                if ($stmt->fetchColumn() > 0) {
                    $error = 'هذه العلامة التجارية موجودة بالفعل';
                } else {
                    // Update brand
                    $stmt = $conn->prepare("UPDATE brands SET name = :name, description = :description WHERE brand_id = :brand_id");
                    $stmt->bindParam(':name', $name);
                    $stmt->bindParam(':description', $description);
                    $stmt->bindParam(':brand_id', $brand_id);
                    $stmt->execute();
                    
                    $success = 'تم تحديث العلامة التجارية بنجاح';
                }
            } catch (PDOException $e) {
                $error = 'حدث خطأ: ' . $e->getMessage();
            }
        }
    }
}

// Handle brand delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $brand_id = (int)$_GET['id'];
    
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Check if brand has associated products
        $stmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE brand_id = :brand_id");
        $stmt->bindParam(':brand_id', $brand_id);
        $stmt->execute();
        
        if ($stmt->fetchColumn() > 0) {
            $error = 'لا يمكن حذف هذه العلامة التجارية لأنها تحتوي على منتجات. قم بنقل المنتجات إلى علامة تجارية أخرى أولاً.';
        } else {
            // Delete brand
            $stmt = $conn->prepare("DELETE FROM brands WHERE brand_id = :brand_id");
            $stmt->bindParam(':brand_id', $brand_id);
            $stmt->execute();
            
            $success = 'تم حذف العلامة التجارية بنجاح';
        }
    } catch (PDOException $e) {
        $error = 'حدث خطأ: ' . $e->getMessage();
    }
}

// Get all brands
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if table exists
    $tables = $conn->query("SHOW TABLES LIKE 'brands'")->fetchAll();
    if (count($tables) > 0) {
        // Get brands with product counts
        $stmt = $conn->query(
            "SELECT b.*, COUNT(p.product_id) as product_count 
             FROM brands b 
             LEFT JOIN products p ON b.brand_id = p.brand_id 
             GROUP BY b.brand_id 
             ORDER BY b.name"
        );
        $brands = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Create brands table if it doesn't exist
        $conn->exec("CREATE TABLE brands (
            brand_id INT(11) AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            created_at DATETIME NOT NULL,
            updated_at DATETIME DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        $success = 'تم إنشاء جدول العلامات التجارية بنجاح. يمكنك الآن إضافة علامات تجارية جديدة.';
    }
} catch (PDOException $e) {
    $error = 'حدث خطأ: ' . $e->getMessage();
}

// Set current page for navigation
$current_page = 'brands';

// Include header
include 'includes/header.php';
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-trademark text-primary mr-2"></i>إدارة العلامات التجارية</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="index.php">الرئيسية</a></li>
                    <li class="breadcrumb-item active">العلامات التجارية</li>
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
            <!-- Add Brand Form -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-plus mr-2"></i>إضافة علامة تجارية جديدة</h3>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="form-group">
                                <label for="name">اسم العلامة التجارية <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="form-group">
                                <label for="description">الوصف</label>
                                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                            </div>
                            <button type="submit" name="add_brand" class="btn btn-primary btn-block">
                                <i class="fas fa-plus mr-1"></i> إضافة علامة تجارية
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Brands List -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title"><i class="fas fa-list mr-2"></i>قائمة العلامات التجارية</h3>
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
                                        <th>اسم العلامة التجارية</th>
                                        <th>الوصف</th>
                                        <th>عدد المنتجات</th>
                                        <th style="width: 150px">الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($brands)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center">لا توجد علامات تجارية حتى الآن</td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($brands as $index => $brand): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo htmlspecialchars($brand['name']); ?></td>
                                        <td><?php echo !empty($brand['description']) ? htmlspecialchars($brand['description']) : '<span class="text-muted">لا يوجد وصف</span>'; ?></td>
                                        <td>
                                            <span class="badge badge-info"><?php echo $brand['product_count']; ?></span>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#editModal<?php echo $brand['brand_id']; ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <a href="?action=delete&id=<?php echo $brand['brand_id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('هل أنت متأكد من حذف هذه العلامة التجارية؟ هذا الإجراء لا يمكن التراجع عنه.');">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                            
                                            <!-- Edit Modal -->
                                            <div class="modal fade" id="editModal<?php echo $brand['brand_id']; ?>" tabindex="-1" role="dialog" aria-labelledby="editModalLabel<?php echo $brand['brand_id']; ?>" aria-hidden="true">
                                                <div class="modal-dialog" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="editModalLabel<?php echo $brand['brand_id']; ?>">تعديل العلامة التجارية</h5>
                                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>
                                                        <form method="post">
                                                            <div class="modal-body">
                                                                <input type="hidden" name="brand_id" value="<?php echo $brand['brand_id']; ?>">
                                                                <div class="form-group">
                                                                    <label for="edit_name<?php echo $brand['brand_id']; ?>">اسم العلامة التجارية <span class="text-danger">*</span></label>
                                                                    <input type="text" class="form-control" id="edit_name<?php echo $brand['brand_id']; ?>" name="name" value="<?php echo htmlspecialchars($brand['name']); ?>" required>
                                                                </div>
                                                                <div class="form-group">
                                                                    <label for="edit_description<?php echo $brand['brand_id']; ?>">الوصف</label>
                                                                    <textarea class="form-control" id="edit_description<?php echo $brand['brand_id']; ?>" name="description" rows="3"><?php echo htmlspecialchars($brand['description']); ?></textarea>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">إلغاء</button>
                                                                <button type="submit" name="edit_brand" class="btn btn-primary">حفظ التغييرات</button>
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
