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
$categories = [];
$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new category
    if (isset($_POST['add_category'])) {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        
        if (empty($name)) {
            $error = 'يرجى إدخال اسم التصنيف';
        } else {
            try {
                $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Check if category already exists
                $stmt = $conn->prepare("SELECT COUNT(*) FROM categories WHERE name = :name");
                $stmt->bindParam(':name', $name);
                $stmt->execute();
                
                if ($stmt->fetchColumn() > 0) {
                    $error = 'هذا التصنيف موجود بالفعل';
                } else {
                    // Insert new category
                    $stmt = $conn->prepare("INSERT INTO categories (name, description, created_at) VALUES (:name, :description, NOW())");
                    $stmt->bindParam(':name', $name);
                    $stmt->bindParam(':description', $description);
                    $stmt->execute();
                    
                    $success = 'تم إضافة التصنيف بنجاح';
                }
            } catch (PDOException $e) {
                $error = 'حدث خطأ: ' . $e->getMessage();
            }
        }
    }
    
    // Edit category
    if (isset($_POST['edit_category'])) {
        $category_id = (int)$_POST['category_id'];
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        
        if (empty($name)) {
            $error = 'يرجى إدخال اسم التصنيف';
        } else {
            try {
                $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Check if category already exists with this name (excluding current one)
                $stmt = $conn->prepare("SELECT COUNT(*) FROM categories WHERE name = :name AND category_id != :category_id");
                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':category_id', $category_id);
                $stmt->execute();
                
                if ($stmt->fetchColumn() > 0) {
                    $error = 'هذا التصنيف موجود بالفعل';
                } else {
                    // Update category
                    $stmt = $conn->prepare("UPDATE categories SET name = :name, description = :description WHERE category_id = :category_id");
                    $stmt->bindParam(':name', $name);
                    $stmt->bindParam(':description', $description);
                    $stmt->bindParam(':category_id', $category_id);
                    $stmt->execute();
                    
                    $success = 'تم تحديث التصنيف بنجاح';
                }
            } catch (PDOException $e) {
                $error = 'حدث خطأ: ' . $e->getMessage();
            }
        }
    }
}

// Handle category delete
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $category_id = (int)$_GET['id'];
    
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Check if category has associated products
        $stmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE category_id = :category_id");
        $stmt->bindParam(':category_id', $category_id);
        $stmt->execute();
        
        if ($stmt->fetchColumn() > 0) {
            $error = 'لا يمكن حذف هذا التصنيف لأنه يحتوي على منتجات. قم بنقل المنتجات إلى تصنيف آخر أولاً.';
        } else {
            // Delete category
            $stmt = $conn->prepare("DELETE FROM categories WHERE category_id = :category_id");
            $stmt->bindParam(':category_id', $category_id);
            $stmt->execute();
            
            $success = 'تم حذف التصنيف بنجاح';
        }
    } catch (PDOException $e) {
        $error = 'حدث خطأ: ' . $e->getMessage();
    }
}

// Get all categories
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if table exists
    $tables = $conn->query("SHOW TABLES LIKE 'categories'")->fetchAll();
    if (count($tables) > 0) {
        // Get categories with product counts
        $stmt = $conn->query(
            "SELECT c.*, COUNT(p.product_id) as product_count 
             FROM categories c 
             LEFT JOIN products p ON c.category_id = p.category_id 
             GROUP BY c.category_id 
             ORDER BY c.name"
        );
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Create categories table if it doesn't exist
        $conn->exec("CREATE TABLE categories (
            category_id INT(11) AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            created_at DATETIME NOT NULL,
            updated_at DATETIME DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        $success = 'تم إنشاء جدول التصنيفات بنجاح. يمكنك الآن إضافة تصنيفات جديدة.';
    }
} catch (PDOException $e) {
    $error = 'حدث خطأ: ' . $e->getMessage();
}

// Set current page for navigation
$current_page = 'categories';

// Include header
include 'includes/header.php';
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-tags text-primary mr-2"></i>إدارة التصنيفات</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="index.php">الرئيسية</a></li>
                    <li class="breadcrumb-item active">التصنيفات</li>
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
            <!-- Add Category Form -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-plus mr-2"></i>إضافة تصنيف جديد</h3>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="form-group">
                                <label for="name">اسم التصنيف <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="form-group">
                                <label for="description">الوصف</label>
                                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                            </div>
                            <button type="submit" name="add_category" class="btn btn-primary btn-block">
                                <i class="fas fa-plus mr-1"></i> إضافة تصنيف
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Categories List -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title"><i class="fas fa-list mr-2"></i>قائمة التصنيفات</h3>
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
                                        <th>اسم التصنيف</th>
                                        <th>الوصف</th>
                                        <th>عدد المنتجات</th>
                                        <th style="width: 150px">الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($categories)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center">لا توجد تصنيفات حتى الآن</td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($categories as $index => $category): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo htmlspecialchars($category['name']); ?></td>
                                        <td><?php echo !empty($category['description']) ? htmlspecialchars($category['description']) : '<span class="text-muted">لا يوجد وصف</span>'; ?></td>
                                        <td>
                                            <span class="badge badge-info"><?php echo $category['product_count']; ?></span>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#editModal<?php echo $category['category_id']; ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <a href="?action=delete&id=<?php echo $category['category_id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('هل أنت متأكد من حذف هذا التصنيف؟ هذا الإجراء لا يمكن التراجع عنه.')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                            
                                            <!-- Edit Modal -->
                                            <div class="modal fade" id="editModal<?php echo $category['category_id']; ?>" tabindex="-1" role="dialog" aria-labelledby="editModalLabel<?php echo $category['category_id']; ?>" aria-hidden="true">
                                                <div class="modal-dialog" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="editModalLabel<?php echo $category['category_id']; ?>">تعديل التصنيف</h5>
                                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>
                                                        <form method="post">
                                                            <div class="modal-body">
                                                                <input type="hidden" name="category_id" value="<?php echo $category['category_id']; ?>">
                                                                <div class="form-group">
                                                                    <label for="edit_name<?php echo $category['category_id']; ?>">اسم التصنيف <span class="text-danger">*</span></label>
                                                                    <input type="text" class="form-control" id="edit_name<?php echo $category['category_id']; ?>" name="name" value="<?php echo htmlspecialchars($category['name']); ?>" required>
                                                                </div>
                                                                <div class="form-group">
                                                                    <label for="edit_description<?php echo $category['category_id']; ?>">الوصف</label>
                                                                    <textarea class="form-control" id="edit_description<?php echo $category['category_id']; ?>" name="description" rows="3"><?php echo htmlspecialchars($category['description']); ?></textarea>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">إلغاء</button>
                                                                <button type="submit" name="edit_category" class="btn btn-primary">حفظ التغييرات</button>
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
