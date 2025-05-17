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
$categories = [];
$brands = [];
$error = '';
$success = '';
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$is_new = ($product_id === 0);

// Connect to database
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get all categories
    $stmt = $conn->query("SELECT * FROM categories ORDER BY name");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all brands
    $stmt = $conn->query("SELECT * FROM brands ORDER BY name");
    $brands = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If editing existing product, get its data
    if (!$is_new) {
        $stmt = $conn->prepare("SELECT * FROM products WHERE product_id = :product_id");
        $stmt->bindParam(':product_id', $product_id);
        $stmt->execute();
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            header('Location: products.php');
            exit;
        }
        
        // Get product images
        $stmt = $conn->prepare("SELECT * FROM product_images WHERE product_id = :product_id ORDER BY is_primary DESC");
        $stmt->bindParam(':product_id', $product_id);
        $stmt->execute();
        $product['images'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $error = 'حدث خطأ في الاتصال بقاعدة البيانات: ' . $e->getMessage();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = (float)$_POST['price'];
    $discount_price = !empty($_POST['discount_price']) ? (float)$_POST['discount_price'] : null;
    $stock_quantity = (int)$_POST['stock_quantity'];
    $category_id = (int)$_POST['category_id'];
    $brand_id = (int)$_POST['brand_id'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    
    // Validate form
    if (empty($name)) {
        $error = 'يرجى إدخال اسم المنتج';
    } elseif (empty($description)) {
        $error = 'يرجى إدخال وصف المنتج';
    } elseif ($price <= 0) {
        $error = 'يجب أن يكون السعر أكبر من صفر';
    } elseif ($discount_price !== null && $discount_price >= $price) {
        $error = 'يجب أن يكون سعر الخصم أقل من السعر الأصلي';
    } elseif ($stock_quantity < 0) {
        $error = 'يجب أن تكون الكمية المتوفرة صفر أو أكبر';
    } elseif ($category_id <= 0) {
        $error = 'يرجى اختيار تصنيف للمنتج';
    } else {
        try {
            // Start transaction
            $conn->beginTransaction();
            
            if ($is_new) {
                // Insert new product
                $stmt = $conn->prepare("INSERT INTO products (name, description, price, discount_price, stock_quantity, 
                                        category_id, brand_id, is_active, is_featured, created_at, updated_at) 
                                        VALUES (:name, :description, :price, :discount_price, :stock_quantity, 
                                        :category_id, :brand_id, :is_active, :is_featured, NOW(), NOW())");
                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':description', $description);
                $stmt->bindParam(':price', $price);
                $stmt->bindParam(':discount_price', $discount_price);
                $stmt->bindParam(':stock_quantity', $stock_quantity);
                $stmt->bindParam(':category_id', $category_id);
                $stmt->bindParam(':brand_id', $brand_id);
                $stmt->bindParam(':is_active', $is_active);
                $stmt->bindParam(':is_featured', $is_featured);
                $stmt->execute();
                
                $product_id = $conn->lastInsertId();
                $success = 'تم إضافة المنتج بنجاح';
            } else {
                // Update existing product
                $stmt = $conn->prepare("UPDATE products SET name = :name, description = :description, 
                                        price = :price, discount_price = :discount_price, stock_quantity = :stock_quantity, 
                                        category_id = :category_id, brand_id = :brand_id, is_active = :is_active, 
                                        is_featured = :is_featured, updated_at = NOW() 
                                        WHERE product_id = :product_id");
                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':description', $description);
                $stmt->bindParam(':price', $price);
                $stmt->bindParam(':discount_price', $discount_price);
                $stmt->bindParam(':stock_quantity', $stock_quantity);
                $stmt->bindParam(':category_id', $category_id);
                $stmt->bindParam(':brand_id', $brand_id);
                $stmt->bindParam(':is_active', $is_active);
                $stmt->bindParam(':is_featured', $is_featured);
                $stmt->bindParam(':product_id', $product_id);
                $stmt->execute();
                
                $success = 'تم تحديث المنتج بنجاح';
            }
            
            // Handle image uploads
            if (!empty($_FILES['images']['name'][0])) {
                $upload_dir = '../uploads/products/';
                
                // Create directory if it doesn't exist
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $max_size = 5 * 1024 * 1024; // 5MB
                
                foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                    if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                        $file_name = $_FILES['images']['name'][$key];
                        $file_size = $_FILES['images']['size'][$key];
                        $file_type = $_FILES['images']['type'][$key];
                        $file_tmp = $_FILES['images']['tmp_name'][$key];
                        
                        // Validate file
                        if (!in_array($file_type, $allowed_types)) {
                            $error = 'نوع الملف غير مدعوم. الأنواع المدعومة هي: JPEG, PNG, GIF, WEBP';
                            $conn->rollBack();
                            break;
                        }
                        
                        if ($file_size > $max_size) {
                            $error = 'حجم الملف كبير جدًا. الحد الأقصى هو 5 ميجابايت';
                            $conn->rollBack();
                            break;
                        }
                        
                        // Generate unique file name
                        $new_file_name = uniqid('product_') . '_' . time() . '.' . pathinfo($file_name, PATHINFO_EXTENSION);
                        $file_path = $upload_dir . $new_file_name;
                        
                        if (move_uploaded_file($file_tmp, $file_path)) {
                            // Set primary image if it's the first one
                            $is_primary = 0;
                            if ($key === 0) {
                                $is_primary = 1;
                            }
                            
                            // Insert image record
                            $stmt = $conn->prepare("INSERT INTO product_images (product_id, image_path, is_primary, created_at) 
                                                    VALUES (:product_id, :image_path, :is_primary, NOW())");
                            $stmt->bindParam(':product_id', $product_id);
                            $stmt->bindParam(':image_path', $file_path);
                            $stmt->bindParam(':is_primary', $is_primary);
                            $stmt->execute();
                        } else {
                            $error = 'فشل في تحميل الصورة';
                            $conn->rollBack();
                            break;
                        }
                    }
                }
            }
            
            // Commit transaction if no errors
            if (empty($error)) {
                $conn->commit();
                
                // Redirect to products page after successful operation
                if (empty($error)) {
                    header('Location: products.php');
                    exit;
                }
            }
        } catch (PDOException $e) {
            $conn->rollBack();
            $error = 'حدث خطأ: ' . $e->getMessage();
        }
    }
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
                <h1 class="m-0">
                    <i class="fas fa-box-open text-primary mr-2"></i>
                    <?php echo $is_new ? 'إضافة منتج جديد' : 'تعديل المنتج'; ?>
                </h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="index.php">الرئيسية</a></li>
                    <li class="breadcrumb-item"><a href="products.php">المنتجات</a></li>
                    <li class="breadcrumb-item active"><?php echo $is_new ? 'إضافة منتج' : 'تعديل منتج'; ?></li>
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
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-edit mr-2"></i>
                    <?php echo $is_new ? 'إضافة منتج جديد' : 'تعديل المنتج: ' . htmlspecialchars($product['name']); ?>
                </h3>
            </div>
            <div class="card-body">
                <form method="post" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="name">اسم المنتج <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo isset($product['name']) ? htmlspecialchars($product['name']) : ''; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="price">السعر <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">$</span>
                                    </div>
                                    <input type="number" step="0.01" class="form-control" id="price" name="price" value="<?php echo isset($product['price']) ? htmlspecialchars($product['price']) : ''; ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="discount_price">سعر الخصم</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">$</span>
                                    </div>
                                    <input type="number" step="0.01" class="form-control" id="discount_price" name="discount_price" value="<?php echo isset($product['discount_price']) ? htmlspecialchars($product['discount_price']) : ''; ?>">
                                </div>
                                <small class="form-text text-muted">اترك هذا الحقل فارغًا إذا لم يكن هناك خصم</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="stock_quantity">الكمية المتوفرة <span class="text-danger">*</span></label>
                                <input type="number" min="0" class="form-control" id="stock_quantity" name="stock_quantity" value="<?php echo isset($product['stock_quantity']) ? htmlspecialchars($product['stock_quantity']) : '0'; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="category_id">التصنيف <span class="text-danger">*</span></label>
                                <select class="form-control" id="category_id" name="category_id" required>
                                    <option value="">اختر التصنيف</option>
                                    <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['category_id']; ?>" <?php echo isset($product['category_id']) && $product['category_id'] == $category['category_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="brand_id">العلامة التجارية</label>
                                <select class="form-control" id="brand_id" name="brand_id">
                                    <option value="">اختر العلامة التجارية</option>
                                    <?php foreach ($brands as $brand): ?>
                                    <option value="<?php echo $brand['brand_id']; ?>" <?php echo isset($product['brand_id']) && $product['brand_id'] == $brand['brand_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($brand['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="description">وصف المنتج <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="description" name="description" rows="5" required><?php echo isset($product['description']) ? htmlspecialchars($product['description']) : ''; ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="images">صور المنتج</label>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="images" name="images[]" multiple accept="image/*">
                                    <label class="custom-file-label" for="images">اختر الصور</label>
                                </div>
                                <small class="form-text text-muted">يمكنك اختيار عدة صور. الصورة الأولى ستكون الصورة الرئيسية.</small>
                            </div>
                            
                            <?php if (!$is_new && isset($product['images']) && count($product['images']) > 0): ?>
                            <div class="form-group">
                                <label>الصور الحالية</label>
                                <div class="row">
                                    <?php foreach ($product['images'] as $image): ?>
                                    <div class="col-md-4 mb-3">
                                        <div class="card">
                                            <img src="<?php echo htmlspecialchars($image['image_path']); ?>" class="card-img-top" alt="صورة المنتج" style="height: 150px; object-fit: cover;">
                                            <div class="card-body p-2 text-center">
                                                <?php if ($image['is_primary']): ?>
                                                <span class="badge badge-success">الصورة الرئيسية</span>
                                                <?php endif; ?>
                                                <a href="delete_image.php?id=<?php echo $image['image_id']; ?>&product_id=<?php echo $product_id; ?>" class="btn btn-sm btn-danger mt-2" onclick="return confirm('هل أنت متأكد من حذف هذه الصورة؟');">حذف</a>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" <?php echo (!$is_new && isset($product['is_active']) && $product['is_active'] == 1) || $is_new ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="is_active">نشط</label>
                                </div>
                                <small class="form-text text-muted">سيكون المنتج متاحًا للشراء إذا كان نشطًا</small>
                            </div>
                            
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="is_featured" name="is_featured" <?php echo !$is_new && isset($product['is_featured']) && $product['is_featured'] == 1 ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="is_featured">منتج مميز</label>
                                </div>
                                <small class="form-text text-muted">سيتم عرض المنتجات المميزة في الصفحة الرئيسية</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-left mt-4">
                        <a href="products.php" class="btn btn-secondary mr-2">إلغاء</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i> <?php echo $is_new ? 'إضافة المنتج' : 'حفظ التغييرات'; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include 'includes/footer.php';
?>
