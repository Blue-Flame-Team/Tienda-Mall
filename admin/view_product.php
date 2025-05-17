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
$product = [];
$error = '';
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Connect to database
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get product data
    if ($product_id > 0) {
        $stmt = $conn->prepare("SELECT p.*, c.name as category_name, b.name as brand_name 
                               FROM products p 
                               LEFT JOIN categories c ON p.category_id = c.category_id 
                               LEFT JOIN brands b ON p.brand_id = b.brand_id 
                               WHERE p.product_id = :product_id");
        $stmt->bindParam(':product_id', $product_id);
        $stmt->execute();
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            $error = 'المنتج غير موجود';
        } else {
            // Get product images - check for both image_path and image_url columns
            $stmt = $conn->prepare("SHOW COLUMNS FROM product_images LIKE 'image_path'");
            $stmt->execute();
            $hasImagePath = $stmt->rowCount() > 0;
            
            $stmt = $conn->prepare("SHOW COLUMNS FROM product_images LIKE 'image_url'");
            $stmt->execute();
            $hasImageUrl = $stmt->rowCount() > 0;
            
            // Get product images based on available columns
            if ($hasImagePath) {
                $imageColumn = 'image_path';
            } elseif ($hasImageUrl) {
                $imageColumn = 'image_url';
            } else {
                $imageColumn = 'image_path'; // Default to image_path
            }
            
            // Check if is_primary column exists
            $stmt = $conn->prepare("SHOW COLUMNS FROM product_images LIKE 'is_primary'");
            $stmt->execute();
            $hasIsPrimary = $stmt->rowCount() > 0;
            
            // Check if is_main column exists
            $stmt = $conn->prepare("SHOW COLUMNS FROM product_images LIKE 'is_main'");
            $stmt->execute();
            $hasIsMain = $stmt->rowCount() > 0;
            
            // Set the primary image column name
            if ($hasIsPrimary) {
                $primaryColumn = 'is_primary';
            } elseif ($hasIsMain) {
                $primaryColumn = 'is_main';
            } else {
                $primaryColumn = 'is_primary'; // Default to is_primary
            }
            
            // Get product images using appropriate column names
            $imageQuery = "SELECT * FROM product_images WHERE product_id = :product_id ORDER BY $primaryColumn DESC";
            $stmt = $conn->prepare($imageQuery);
            $stmt->bindParam(':product_id', $product_id);
            $stmt->execute();
            $product['images'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } else {
        $error = 'معرف المنتج غير صحيح';
    }
} catch (PDOException $e) {
    $error = 'حدث خطأ في الاتصال بقاعدة البيانات: ' . $e->getMessage();
}

// Fix image paths function
function fix_image_path($path) {
    if (empty($path)) {
        return "../assets/images/product-placeholder.png";
    }
    
    // Remove "../" from the beginning of the path if it exists
    if (strpos($path, "../") === 0) {
        $path = substr($path, 3);
    }
    
    // Add proper prefix if needed
    if (strpos($path, "/") !== 0 && strpos($path, "http") !== 0) {
        $path = "../" . $path;
    }
    
    return $path;
}

// Set current page for navigation
$current_page = 'products';

// Include header
include 'includes/header.php';
?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-eye text-info mr-2"></i>عرض المنتج</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="index.php">الرئيسية</a></li>
                    <li class="breadcrumb-item"><a href="products.php">المنتجات</a></li>
                    <li class="breadcrumb-item active">عرض المنتج</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="content">
    <div class="container-fluid">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle mr-2"></i> <?php echo $error; ?>
            </div>
            <div class="text-center mt-4">
                <a href="products.php" class="btn btn-primary">العودة للمنتجات</a>
            </div>
        <?php elseif (!empty($product)): ?>
            <div class="row">
                <div class="col-lg-6">
                    <!-- Product Images -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-images mr-2"></i>صور المنتج</h3>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($product['images'])): ?>
                                <div class="product-images">
                                    <div class="main-image mb-4 text-center">
                                        <?php 
                                        $mainImage = null;
                                        foreach ($product['images'] as $image) {
                                            if (isset($image[$primaryColumn]) && $image[$primaryColumn] == 1) {
                                                $mainImage = $image;
                                                break;
                                            }
                                        }
                                        
                                        if ($mainImage) {
                                            $imagePath = isset($mainImage[$imageColumn]) ? fix_image_path($mainImage[$imageColumn]) : "../assets/images/product-placeholder.png";
                                        } else {
                                            $imagePath = "../assets/images/product-placeholder.png";
                                        }
                                        ?>
                                        <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="img-fluid mb-2" style="max-height: 400px;">
                                        <p><small class="text-muted">الصورة الرئيسية</small></p>
                                    </div>
                                    
                                    <?php if (count($product['images']) > 1): ?>
                                        <div class="other-images">
                                            <h5>جميع الصور</h5>
                                            <div class="row">
                                                <?php foreach ($product['images'] as $image): ?>
                                                    <div class="col-6 col-md-3 mb-3">
                                                        <?php 
                                                        $imagePath = isset($image[$imageColumn]) ? fix_image_path($image[$imageColumn]) : "../assets/images/product-placeholder.png";
                                                        ?>
                                                        <div class="img-thumbnail">
                                                            <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="img-fluid" style="height: 120px; width: 100%; object-fit: contain;">
                                                            <?php if (isset($image[$primaryColumn]) && $image[$primaryColumn] == 1): ?>
                                                                <div class="text-center mt-1">
                                                                    <span class="badge badge-success">رئيسية</span>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-circle mr-2"></i> لا توجد صور لهذا المنتج
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <!-- Product Details -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>تفاصيل المنتج</h3>
                        </div>
                        <div class="card-body">
                            <h3 class="product-title mb-3"><?php echo htmlspecialchars($product['name']); ?></h3>
                            
                            <div class="product-meta">
                                <table class="table">
                                    <tr>
                                        <th style="width:40%">الحالة</th>
                                        <td>
                                            <?php if ($product['is_active']): ?>
                                                <span class="badge badge-success">منشور</span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">غير منشور</span>
                                            <?php endif; ?>
                                            
                                            <?php if ($product['is_featured']): ?>
                                                <span class="badge badge-primary mr-1">مميز</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>السعر</th>
                                        <td>
                                            <?php if (!empty($product['sale_price']) || !empty($product['discount_price'])): ?>
                                                <?php 
                                                $discountPrice = !empty($product['sale_price']) ? $product['sale_price'] : $product['discount_price'];
                                                ?>
                                                <span class="text-success font-weight-bold"><?php echo number_format($discountPrice, 2); ?></span>
                                                <del class="text-muted ml-2"><?php echo number_format($product['price'], 2); ?></del>
                                                <?php 
                                                $discount = round(($product['price'] - $discountPrice) / $product['price'] * 100);
                                                ?>
                                                <span class="badge badge-danger ml-2">خصم <?php echo $discount; ?>%</span>
                                            <?php else: ?>
                                                <span class="font-weight-bold"><?php echo number_format($product['price'], 2); ?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>القسم</th>
                                        <td><?php echo htmlspecialchars($product['category_name'] ?? 'غير محدد'); ?></td>
                                    </tr>
                                    <tr>
                                        <th>الماركة</th>
                                        <td><?php echo htmlspecialchars($product['brand_name'] ?? 'غير محدد'); ?></td>
                                    </tr>
                                    <tr>
                                        <th>المخزون</th>
                                        <td>
                                            <?php if ($product['stock_quantity'] > 10): ?>
                                                <span class="text-success"><?php echo $product['stock_quantity']; ?> قطعة</span>
                                            <?php elseif ($product['stock_quantity'] > 0): ?>
                                                <span class="text-warning"><?php echo $product['stock_quantity']; ?> قطعة (مخزون منخفض)</span>
                                            <?php else: ?>
                                                <span class="text-danger">نفذت الكمية</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>تاريخ الإضافة</th>
                                        <td><?php echo date('Y-m-d', strtotime($product['created_at'])); ?></td>
                                    </tr>
                                    <tr>
                                        <th>آخر تحديث</th>
                                        <td><?php echo date('Y-m-d', strtotime($product['updated_at'])); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="card">
                        <div class="card-body">
                            <div class="btn-group w-100">
                                <a href="edit_product.php?id=<?php echo $product_id; ?>" class="btn btn-primary">
                                    <i class="fas fa-edit mr-1"></i> تعديل المنتج
                                </a>
                                <a href="products.php" class="btn btn-secondary">
                                    <i class="fas fa-list mr-1"></i> قائمة المنتجات
                                </a>
                                <a href="../index.php?product=<?php echo $product_id; ?>" target="_blank" class="btn btn-info">
                                    <i class="fas fa-eye mr-1"></i> عرض في الموقع
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Product Description -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-file-alt mr-2"></i>وصف المنتج</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($product['description'])): ?>
                        <div class="product-description">
                            <?php echo $product['description']; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-circle mr-2"></i> لا يوجد وصف لهذا المنتج
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
