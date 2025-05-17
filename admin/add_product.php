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
$brands = [];
$error = '';
$success = '';

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
    
} catch (PDOException $e) {
    $error = 'حدث خطأ: ' . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    // Get form data
    $name = trim($_POST['name']);
    $sku = trim($_POST['sku']);
    $description = trim($_POST['description']);
    $short_description = trim($_POST['short_description']);
    $price = (float)$_POST['price'];
    $sale_price = !empty($_POST['sale_price']) ? (float)$_POST['sale_price'] : null;
    $category_id = (int)$_POST['category_id'];
    $brand_id = !empty($_POST['brand_id']) ? (int)$_POST['brand_id'] : null;
    $stock_quantity = (int)$_POST['stock_quantity'];
    $weight = !empty($_POST['weight']) ? (float)$_POST['weight'] : null;
    $dimensions = trim($_POST['dimensions']);
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $meta_title = trim($_POST['meta_title']);
    $meta_description = trim($_POST['meta_description']);
    $meta_keywords = trim($_POST['meta_keywords']);
    
    // Validation
    if (empty($name)) {
        $error = 'يرجى إدخال اسم المنتج';
    } else if (empty($price) || $price <= 0) {
        $error = 'يرجى إدخال سعر صحيح للمنتج';
    } else if (empty($category_id)) {
        $error = 'يرجى اختيار فئة للمنتج';
    } else {
        try {
            // Check if SKU exists
            if (!empty($sku)) {
                $stmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE sku = :sku");
                $stmt->bindParam(':sku', $sku);
                $stmt->execute();
                
                if ($stmt->fetchColumn() > 0) {
                    $error = 'رقم SKU موجود بالفعل. يرجى استخدام رقم آخر';
                }
            }
            
            if (empty($error)) {
                // Begin transaction
                $conn->beginTransaction();
                
                // Insert product
                $stmt = $conn->prepare(
                    "INSERT INTO products 
                    (name, sku, description, short_description, price, sale_price, 
                    category_id, brand_id, stock_quantity, 
                    is_featured, is_active, meta_title, meta_description, meta_keywords, created_at) 
                    VALUES 
                    (:name, :sku, :description, :short_description, :price, :sale_price, 
                    :category_id, :brand_id, :stock_quantity, 
                    :is_featured, :is_active, :meta_title, :meta_description, :meta_keywords, NOW())"
                );
                
                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':sku', $sku);
                $stmt->bindParam(':description', $description);
                $stmt->bindParam(':short_description', $short_description);
                $stmt->bindParam(':price', $price);
                $stmt->bindParam(':sale_price', $sale_price);
                $stmt->bindParam(':category_id', $category_id);
                $stmt->bindParam(':brand_id', $brand_id);
                $stmt->bindParam(':stock_quantity', $stock_quantity);
                $stmt->bindParam(':is_featured', $is_featured);
                $stmt->bindParam(':is_active', $is_active);
                $stmt->bindParam(':meta_title', $meta_title);
                $stmt->bindParam(':meta_description', $meta_description);
                $stmt->bindParam(':meta_keywords', $meta_keywords);
                
                $stmt->execute();
                $product_id = $conn->lastInsertId();
                
                // Handle image uploads
                if (!empty($_FILES['images']['name'][0])) {
                    $upload_dir = '../uploads/products/';
                    
                    // Create directory if it doesn't exist
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    // Loop through each uploaded image
                    foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                        if ($_FILES['images']['error'][$key] === 0) {
                            $filename = $_FILES['images']['name'][$key];
                            $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                            $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                            
                            // Validate file extension
                            if (in_array($file_ext, $allowed_exts)) {
                                // Generate unique filename
                                $new_filename = 'product_' . $product_id . '_' . uniqid() . '.' . $file_ext;
                                $file_path = $upload_dir . $new_filename;
                                
                                // Move uploaded file
                                if (move_uploaded_file($tmp_name, $file_path)) {
                                    // Insert image record into database
                                    $sort_order = $key + 1;
                                    $is_main = ($key === 0) ? 1 : 0;
                                    
                                    $stmt = $conn->prepare(
                                        "INSERT INTO product_images 
                                        (product_id, image_url, sort_order, is_main, created_at) 
                                        VALUES 
                                        (:product_id, :image_url, :sort_order, :is_main, NOW())"
                                    );
                                    
                                    $image_url = 'uploads/products/' . $new_filename;
                                    $stmt->bindParam(':product_id', $product_id);
                                    $stmt->bindParam(':image_url', $image_url);
                                    $stmt->bindParam(':sort_order', $sort_order);
                                    $stmt->bindParam(':is_main', $is_main);
                                    $stmt->execute();
                                }
                            }
                        }
                    }
                }
                
                // Commit transaction
                $conn->commit();
                
                $success = 'تم إضافة المنتج بنجاح';
                
                // Redirect to edit product page
                header("Location: edit_product.php?id=$product_id&success=created");
                exit;
            }
        } catch (PDOException $e) {
            // Rollback transaction on error if active
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
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
                <h1 class="m-0"><i class="fas fa-plus-circle text-primary mr-2"></i>إضافة منتج جديد</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="index.php">الرئيسية</a></li>
                    <li class="breadcrumb-item"><a href="products.php">المنتجات</a></li>
                    <li class="breadcrumb-item active">إضافة منتج</li>
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
        
        <form action="" method="post" enctype="multipart/form-data" id="productForm">
            <div class="row">
                <div class="col-lg-8">
                    <!-- Main Product Information -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>معلومات المنتج الأساسية</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="name">اسم المنتج <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="form-group">
                                <label for="sku">رقم المنتج (SKU)</label>
                                <input type="text" class="form-control" id="sku" name="sku">
                                <small class="form-text text-muted">رمز فريد للمنتج (اختياري)</small>
                            </div>
                            <div class="form-group">
                                <label for="short_description">وصف مختصر</label>
                                <textarea class="form-control" id="short_description" name="short_description" rows="3"></textarea>
                            </div>
                            <div class="form-group">
                                <label for="description">الوصف التفصيلي</label>
                                <textarea class="form-control" id="description" name="description" rows="8"></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Product Images -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-images mr-2"></i>صور المنتج</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>صور المنتج</label>
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="images" name="images[]" multiple accept="image/*">
                                    <label class="custom-file-label" for="images">اختر الصور</label>
                                </div>
                                <small class="form-text text-muted">يمكنك تحميل صور متعددة. الصورة الأولى ستكون الصورة الرئيسية.</small>
                                <div id="image-preview" class="mt-3 row"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Product Description Tab -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-search mr-2"></i>بيانات SEO</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="meta_title">عنوان الصفحة (Meta Title)</label>
                                <input type="text" class="form-control" id="meta_title" name="meta_title">
                            </div>
                            <div class="form-group">
                                <label for="meta_description">وصف الصفحة (Meta Description)</label>
                                <textarea class="form-control" id="meta_description" name="meta_description" rows="3"></textarea>
                            </div>
                            <div class="form-group">
                                <label for="meta_keywords">الكلمات المفتاحية (Meta Keywords)</label>
                                <input type="text" class="form-control" id="meta_keywords" name="meta_keywords">
                                <small class="form-text text-muted">افصل بين الكلمات المفتاحية باستخدام الفواصل</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <!-- Publish Card -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-check mr-2"></i>النشر</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" checked>
                                    <label class="custom-control-label" for="is_active">نشط</label>
                                </div>
                                <small class="form-text text-muted">تفعيل أو تعطيل ظهور المنتج في المتجر</small>
                            </div>
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="is_featured" name="is_featured">
                                    <label class="custom-control-label" for="is_featured">منتج مميز</label>
                                </div>
                                <small class="form-text text-muted">ظهور المنتج في قائمة المنتجات المميزة</small>
                            </div>
                            <button type="submit" name="add_product" class="btn btn-primary btn-block mt-4">
                                <i class="fas fa-save mr-1"></i> حفظ المنتج
                            </button>
                            <a href="products.php" class="btn btn-outline-secondary btn-block">
                                <i class="fas fa-times mr-1"></i> إلغاء
                            </a>
                        </div>
                    </div>
                    
                    <!-- Product Categories & Brands -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-tag mr-2"></i>التصنيف والعلامة التجارية</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="category_id">فئة المنتج <span class="text-danger">*</span></label>
                                <select class="form-control" id="category_id" name="category_id" required>
                                    <option value="">اختر الفئة</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['category_id']; ?>">
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
                                        <option value="<?php echo $brand['brand_id']; ?>">
                                            <?php echo htmlspecialchars($brand['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Product Pricing -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-dollar-sign mr-2"></i>التسعير والمخزون</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="price">السعر <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">$</span>
                                    </div>
                                    <input type="number" step="0.01" min="0" class="form-control" id="price" name="price" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="sale_price">سعر الخصم</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">$</span>
                                    </div>
                                    <input type="number" step="0.01" min="0" class="form-control" id="sale_price" name="sale_price">
                                </div>
                                <small class="form-text text-muted">اترك فارغًا إذا لم يكن هناك خصم</small>
                            </div>
                            <div class="form-group">
                                <label for="stock_quantity">كمية المخزون</label>
                                <input type="number" min="0" class="form-control" id="stock_quantity" name="stock_quantity" value="0">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Product Dimensions -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-box mr-2"></i>الأبعاد والوزن</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="weight">الوزن (كغم)</label>
                                <input type="number" step="0.01" min="0" class="form-control" id="weight" name="weight">
                            </div>
                            <div class="form-group">
                                <label for="dimensions">الأبعاد</label>
                                <input type="text" class="form-control" id="dimensions" name="dimensions" placeholder="الطول × العرض × الارتفاع">
                                <small class="form-text text-muted">مثال: 10 × 5 × 3 سم</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Include CKEditor 5 -->
<script src="https://cdn.ckeditor.com/ckeditor5/35.0.1/classic/ckeditor.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize CKEditor for product description
        if (document.getElementById('description')) {
            ClassicEditor
                .create(document.getElementById('description'), {
                    language: 'ar', // Arabic language
                    toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', '|', 'indent', 'outdent', '|', 'blockQuote', 'insertTable', '|', 'undo', 'redo']
                })
                .catch(error => {
                    console.error(error);
                });
        }
        
        // Image preview
        const fileInput = document.getElementById('images');
        const imagePreview = document.getElementById('image-preview');
        
        if (fileInput && imagePreview) {
            fileInput.addEventListener('change', function() {
                imagePreview.innerHTML = '';
                
                if (this.files) {
                    Array.from(this.files).forEach(file => {
                        if (file.type.match('image.*')) {
                            const reader = new FileReader();
                            
                            reader.onload = function(e) {
                                const col = document.createElement('div');
                                col.className = 'col-md-3 col-sm-4 col-6 mb-3';
                                
                                const img = document.createElement('img');
                                img.src = e.target.result;
                                img.className = 'img-fluid img-thumbnail';
                                img.alt = 'Product Image Preview';
                                
                                col.appendChild(img);
                                imagePreview.appendChild(col);
                            };
                            
                            reader.readAsDataURL(file);
                        }
                    });
                }
            });
        }
        
        // Customize file input label
        document.querySelectorAll('.custom-file-input').forEach(function(input) {
            input.addEventListener('change', function() {
                let fileName = this.files.length > 1 ? this.files.length + ' ملفات مختارة' : this.files[0].name;
                this.nextElementSibling.textContent = fileName;
            });
        });
    });
</script>

<?php
// Include footer
include 'includes/footer.php';
?>
