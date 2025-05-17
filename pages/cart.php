<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir archivos de ayuda necesarios
require_once '../includes/config.php';
require_once '../includes/cart_helper.php';

// Database configuration
$host = 'localhost';
$dbname = 'tienda_mall';
$username = 'root';
$password = '';

// Initialize cart data
$cart_items = [];
$cart_total = 0;
$cart_count = 0;
$error = '';
$success = '';

// Check if user is logged in
$is_logged_in = isset($_SESSION['user']);
$user_id = $is_logged_in ? $_SESSION['user']['user_id'] : null;

// Handle cart operations (add, update, remove)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Connect to database
        $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Handle cart operations
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'update':
                    if (isset($_POST['item_id']) && isset($_POST['quantity'])) {
                        $item_id = $_POST['item_id'];
                        $quantity = (int)$_POST['quantity'];
                        
                        if ($quantity <= 0) {
                            // If quantity is zero or negative, remove the item
                            $stmt = $conn->prepare("DELETE FROM cart_items WHERE item_id = :item_id");
                            $stmt->bindParam(':item_id', $item_id);
                            $stmt->execute();
                            $success = 'تم حذف المنتج من السلة';
                        } else {
                            // Update quantity
                            $stmt = $conn->prepare("UPDATE cart_items SET quantity = :quantity, updated_at = NOW() WHERE item_id = :item_id");
                            $stmt->bindParam(':quantity', $quantity);
                            $stmt->bindParam(':item_id', $item_id);
                            $stmt->execute();
                            $success = 'تم تحديث السلة';
                        }
                    }
                    break;
                    
                case 'remove':
                    if (isset($_POST['item_id'])) {
                        $item_id = $_POST['item_id'];
                        
                        $stmt = $conn->prepare("DELETE FROM cart_items WHERE item_id = :item_id");
                        $stmt->bindParam(':item_id', $item_id);
                        $stmt->execute();
                        $success = 'تم حذف المنتج من السلة';
                    }
                    break;
                    
                case 'clear':
                    if ($is_logged_in) {
                        $stmt = $conn->prepare("DELETE FROM cart_items WHERE user_id = :user_id");
                        $stmt->bindParam(':user_id', $user_id);
                        $stmt->execute();
                    } else {
                        $session_id = session_id();
                        $stmt = $conn->prepare("DELETE FROM cart_items WHERE cart_id IN (SELECT cart_id FROM guest_carts WHERE session_id = :session_id)");
                        $stmt->bindParam(':session_id', $session_id);
                        $stmt->execute();
                    }
                    $success = 'تم إفراغ السلة';
                    break;
            }
        }
        
    } catch (PDOException $e) {
        $error = 'خطأ في قاعدة البيانات: ' . $e->getMessage();
    }
}

// Get cart items
try {
    // Connect to database
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check tables exist
    $tables_exist = true;
    $required_tables = ['products', 'cart_items', 'guest_carts'];
    
    foreach ($required_tables as $table) {
        $stmt = $conn->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() === 0) {
            $tables_exist = false;
            break;
        }
    }
    
    if ($tables_exist) {
        if ($is_logged_in) {
            // Get cart items for logged in user
            $stmt = $conn->prepare("
                SELECT ci.*, p.name, p.price, p.sale_price, 
                       (SELECT image_path FROM product_images WHERE product_id = p.product_id AND is_primary = 1 LIMIT 1) as image
                FROM cart_items ci
                JOIN products p ON ci.product_id = p.product_id
                WHERE ci.user_id = :user_id
            ");
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // Get cart items for guest user
            $session_id = session_id();
            
            // Check if guest cart exists
            $stmt = $conn->prepare("SELECT * FROM guest_carts WHERE session_id = :session_id");
            $stmt->bindParam(':session_id', $session_id);
            $stmt->execute();
            $guest_cart = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$guest_cart) {
                // Create new guest cart
                $stmt = $conn->prepare("INSERT INTO guest_carts (session_id) VALUES (:session_id)");
                $stmt->bindParam(':session_id', $session_id);
                $stmt->execute();
                $cart_id = $conn->lastInsertId();
            } else {
                $cart_id = $guest_cart['cart_id'];
            }
            
            // Get cart items
            $stmt = $conn->prepare("
                SELECT ci.*, p.name, p.price, p.sale_price, 
                       (SELECT image_path FROM product_images WHERE product_id = p.product_id AND is_primary = 1 LIMIT 1) as image
                FROM cart_items ci
                JOIN products p ON ci.product_id = p.product_id
                WHERE ci.cart_id = :cart_id
            ");
            $stmt->bindParam(':cart_id', $cart_id);
            $stmt->execute();
            $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        // Calculate cart total and count
        foreach ($cart_items as $item) {
            $price = !empty($item['sale_price']) ? $item['sale_price'] : $item['price'];
            $cart_total += $price * $item['quantity'];
            $cart_count += $item['quantity'];
        }
    }
} catch (PDOException $e) {
    $error = 'Error al conectar con la base de datos: ' . $e->getMessage();
}

// إزالة منتج من السلة
if (isset($_GET['remove']) && !empty($_GET['remove'])) {
    $item_id = $_GET['remove'];
    $result = remove_from_cart($item_id);
    
    if ($result['success']) {
        $message = 'تم إزالة المنتج من السلة';
        $message_type = 'success';
    } else {
        $message = $result['message'];
        $message_type = 'danger';
    }
}

// تفريغ السلة بالكامل
if (isset($_GET['clear_cart'])) {
    $result = clear_cart();
    $message = 'تم تفريغ السلة بنجاح';
    $message_type = 'success';
}

// استخدام كوبون خصم
if (isset($_POST['apply_coupon']) && !empty($_POST['coupon_code'])) {
    $result = apply_coupon($_POST['coupon_code']);
    
    if ($result['success']) {
        $message = $result['message'];
        $message_type = 'success';
    } else {
        $message = $result['message'];
        $message_type = 'danger';
    }
}

// الحصول على محتويات السلة
$cart = get_cart();

// تحويل عناصر السلة إلى صيغة يمكن عرضها في الصفحة
$cart_items = [];
if (isset($cart['items']) && is_array($cart['items'])) {
    foreach ($cart['items'] as $item_id => $item) {
        // إضافة معرف العنصر إلى البيانات
        $item['item_id'] = $item_id;
        $cart_items[] = $item;
    }
}

// حساب إجمالي السلة
$cart_total = isset($cart['total']) ? $cart['total'] : 0;
$cart_count = isset($cart['count']) ? $cart['count'] : 0;

// إذا كانت هناك إضافة منتج من صفحة أخرى عبر AJAX، نعيد JSON response فقط
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => !empty($message_type) && $message_type == 'success',
        'message' => $message,
        'cart' => $cart
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سلة التسوق | متجر Tienda</title>
    <link rel="stylesheet" href="../styles/style.css">
    <link rel="stylesheet" href="../styles/mobile-nav.css">
    <link rel="stylesheet" href="../styles/cart.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .cart-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        .cart-title {
            text-align: center;
            margin-bottom: 30px;
        }
        .cart-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .cart-table th, .cart-table td {
            padding: 15px;
            text-align: right;
            border-bottom: 1px solid #ddd;
        }
        .cart-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .cart-item-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
        }
        .cart-item-quantity {
            display: flex;
            align-items: center;
        }
        .quantity-btn {
            width: 30px;
            height: 30px;
            background: #f0f0f0;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .quantity-input {
            width: 50px;
            height: 30px;
            text-align: center;
            margin: 0 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .cart-actions {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .cart-actions button {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .continue-shopping {
            background-color: #f8f9fa;
            color: #000;
        }
        .clear-cart {
            background-color: #f8d7da;
            color: #721c24;
        }
        .cart-summary {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 4px;
        }
        .cart-summary h3 {
            margin-top: 0;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .summary-total {
            font-weight: bold;
            font-size: 1.2em;
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
        }
        .checkout-btn {
            display: block;
            width: 100%;
            padding: 15px;
            background-color: #db4444;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 1.1em;
            cursor: pointer;
            margin-top: 20px;
        }
        .checkout-btn:hover {
            background-color: #c13333;
        }
        .empty-cart {
            text-align: center;
            padding: 50px 0;
        }
        .empty-cart i {
            font-size: 4em;
            color: #ddd;
            margin-bottom: 20px;
        }
        .remove-item {
            color: #dc3545;
            cursor: pointer;
        }
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .guest-checkout-notice {
            background-color: #e2f0fd;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #bee5eb;
        }
        .guest-checkout-notice p {
            margin: 0 0 10px;
        }
        .guest-checkout-notice a {
            color: #0056b3;
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <!-- Top Bar -->
    <div class="top-bar">
        <div class="container top-bar-flex">
            <span class="top-bar-message">Summer Sale For All Swim Suits And Free Express Delivery - OFF 50%! <a href="#">ShopNow</a></span>
            <div class="top-bar-right">
                <span>English</span>
                <i class="fa fa-chevron-down"></i>
            </div>
        </div>
    </div>
    
    <!-- Header / Navbar -->
    <header>
        <div class="container nav-container">
            <div class="logo">Tienda</div>
            <button class="mobile-menu-btn" aria-label="Open Menu"><i class="fa fa-bars"></i></button>
            <nav>
                <a href="../index.php">الرئيسية</a>
                <a href="./cart.php" class="active">السلة</a>
                <a href="./contact.php">اتصل بنا</a>
                <?php if ($is_logged_in): ?>
                    <a href="./account.php">حسابي</a>
                    <a href="./logout.php">تسجيل الخروج</a>
                <?php else: ?>
                    <a href="./signup.php">تسجيل</a>
                    <a href="./login.php">تسجيل الدخول</a>
                <?php endif; ?>
            </nav>
            <div class="search-cart">
                <div class="search-box">
                    <input type="text" placeholder="ما الذي تبحث عنه؟">
                    <span class="search-icon"><img src="../assets/icons/Saearch.png" alt="Search" class="icon-img"></span>
                </div>
                <a href="./wishlist.html" class="icon-link"><img src="../assets/icons/Wishlist.png" alt="Wishlist" class="icon-img"></a>
                <a href="./cart.php" class="icon-link"><img src="../assets/icons/Cart1.png" alt="Cart" class="icon-img"></a>
            </div>
        </div>
    </header>

    <!-- Mobile Nav Overlay & Dropdown -->
    <div class="mobile-nav-overlay"></div>
    <div class="mobile-nav-dropdown">
        <button class="close-mobile-nav" aria-label="Close Menu"><i class="fa fa-times"></i></button>
        <nav>
            <a href="../index.php">الرئيسية</a>
            <a href="./contact.php">اتصل بنا</a>
            <a href="#">حول</a>
            <?php if ($is_logged_in): ?>
                <a href="./account.php">حسابي</a>
                <a href="./logout.php">تسجيل الخروج</a>
            <?php else: ?>
                <a href="./signup.php">تسجيل</a>
                <a href="./login.php">تسجيل الدخول</a>
            <?php endif; ?>
        </nav>
        <div class="search-cart">
            <div class="search-box">
                <input type="text" placeholder="ما الذي تبحث عنه؟">
                <span class="search-icon"><img src="../assets/icons/Saearch.png" alt="Search" class="icon-img"></span>
            </div>
            <a href="./wishlist.html" class="icon-link"><img src="../assets/icons/Wishlist.png" alt="Wishlist" class="icon-img"></a>
            <a href="./cart.php" class="icon-link"><img src="../assets/icons/Cart1.png" alt="Cart" class="icon-img"></a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="cart-container">
        <h1 class="cart-title">سلة التسوق</h1>
        
        <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
        <div class="alert alert-success">
            <?php echo $success; ?>
        </div>
        <?php endif; ?>
        
        <?php if (!$is_logged_in): ?>
        <div class="guest-checkout-notice">
            <p><i class="fas fa-info-circle"></i> أنت تتسوق حالياً كضيف. يمكنك إتمام عملية الشراء دون الحاجة إلى إنشاء حساب.</p>
            <p>لديك حساب بالفعل؟ <a href="login.php">تسجيل الدخول</a> | جديد في المتجر؟ <a href="signup.php">إنشاء حساب</a></p>
        </div>
        <?php endif; ?>
        
        <?php if (empty($cart_items)): ?>
        <div class="empty-cart">
            <i class="fas fa-shopping-cart"></i>
            <h2>سلة التسوق فارغة</h2>
            <p>استمر في التسوق وأضف بعض المنتجات إلى سلتك.</p>
            <a href="../index.php" class="checkout-btn" style="max-width: 300px; margin: 20px auto">استمر في التسوق</a>
        </div>
        <?php else: ?>
        <div class="row">
            <div class="col-md-8">
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>المنتج</th>
                            <th>السعر</th>
                            <th>الكمية</th>
                            <th>المجموع</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart_items as $item): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center;">
                                    <?php 
                                    // معالجة مسار الصورة للعرض الصحيح
                                    $imagePath = !empty($item['image']) ? $item['image'] : '../assets/images/no-image.png';
                                    
                                    // إذا كان المسار لا يبدأ بـ '../' نضيفه
                                    if ($imagePath != '../assets/images/no-image.png' && strpos($imagePath, '../') !== 0 && strpos($imagePath, '/') !== 0) {
                                        $imagePath = '../' . $imagePath;
                                    }
                                    ?>
                                    <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="cart-item-image">
                                    <div style="margin-right: 15px;">
                                        <h4 style="margin: 0;"><?php echo htmlspecialchars($item['name']); ?></h4>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if (!empty($item['sale_price'])): ?>
                                <span class="text-danger"><?php echo number_format($item['sale_price'], 2); ?> $</span>
                                <small class="text-muted"><s><?php echo number_format($item['price'], 2); ?> $</s></small>
                                <?php else: ?>
                                <?php echo number_format($item['price'], 2); ?> $
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" style="display: flex; align-items: center;">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                    <button type="button" class="quantity-btn minus">-</button>
                                    <input type="number" name="quantity" class="quantity-input" value="<?php echo $item['quantity']; ?>" min="1" max="10">
                                    <button type="button" class="quantity-btn plus">+</button>
                                    <button type="submit" style="margin-right: 10px; background: none; border: none; cursor: pointer;" title="تحديث">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                </form>
                            </td>
                            <td>
                                <?php 
                                $price = !empty($item['sale_price']) ? $item['sale_price'] : $item['price'];
                                $item_total = $price * $item['quantity'];
                                echo number_format($item_total, 2); ?> $
                            </td>
                            <td>
                                <form method="POST">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                    <button type="submit" class="remove-item" title="حذف" style="background: none; border: none;">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="cart-actions">
                    <a href="../index.php" class="continue-shopping" style="text-decoration: none; padding: 10px 20px; border-radius: 4px; background-color: #f8f9fa;">
                        <i class="fas fa-arrow-left"></i> استمر في التسوق
                    </a>
                    
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="clear">
                        <button type="submit" class="clear-cart" onclick="return confirm('هل أنت متأكد من إفراغ السلة؟');">
                            <i class="fas fa-trash"></i> إفراغ السلة
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="cart-summary">
                    <h3>ملخص الطلب</h3>
                    
                    <div class="summary-item">
                        <span>عدد المنتجات:</span>
                        <span><?php echo $cart_count; ?></span>
                    </div>
                    
                    <div class="summary-item">
                        <span>المجموع الفرعي:</span>
                        <span><?php echo number_format($cart_total, 2); ?> $</span>
                    </div>
                    
                    <div class="summary-item">
                        <span>الشحن:</span>
                        <span>مجاني</span>
                    </div>
                    
                    <div class="summary-item summary-total">
                        <span>المجموع:</span>
                        <span><?php echo number_format($cart_total, 2); ?> $</span>
                    </div>
                    
                    <a href="checkout.php" class="checkout-btn">
                        المتابعة إلى الدفع
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="footer-main">
        <div class="container footer-container">
            <div class="footer-col">
                <div class="footer-logo">Tienda</div>
                <div class="footer-title">اشترك في نشرتنا</div>
                <div class="footer-desc">احصل على خصم 10% على أول طلب لك</div>
                <form class="subscribe-form">
                    <input type="email" placeholder="أدخل بريدك الإلكتروني">
                    <button type="submit"><i class="fa fa-paper-plane"></i></button>
                </form>
            </div>
            <div class="footer-col">
                <div class="footer-title">الدعم</div>
                <div class="footer-desc">111 شارع بيجوي ساراني، دكا، بنغلاديش.</div>
                <div class="footer-desc">tienda@example.com</div>
                <div class="footer-desc">+88015-88888-9999</div>
            </div>
            <div class="footer-col">
                <div class="footer-title">حسابي</div>
                <ul class="footer-list">
                    <li><a href="account.php">حسابي</a></li>
                    <li><a href="login.php">تسجيل الدخول</a></li>
                    <li><a href="cart.php">سلة التسوق</a></li>
                    <li><a href="checkout.php">إتمام الشراء</a></li>
                    <li><a href="../index.php">المتجر</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <div class="footer-title">روابط سريعة</div>
                <ul class="footer-list">
                    <li><a href="../index.php">الرئيسية</a></li>
                    <li><a href="checkout.php">إتمام الشراء</a></li>
                    <li><a href="#">المنتجات المميزة</a></li>
                    <li><a href="contact.php">اتصل بنا</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <div class="footer-title">تحميل التطبيق</div>
                <div class="footer-desc">وفر 3$ مع تطبيق المستخدم الجديد فقط</div>
                <div class="footer-apps">
                    <img class="footer-app-img" src="../assets/images/APP.png" alt="تحميل التطبيق">
                </div>
                <div class="footer-socials">
                    <i class="fab fa-facebook-f"></i>
                    <i class="fab fa-twitter"></i>
                    <i class="fab fa-instagram"></i>
                    <i class="fab fa-linkedin-in"></i>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <span>&copy; جميع الحقوق محفوظة Blue Flame 2025.</span>
        </div>
    </footer>

    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <!-- Option 1: Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    
    <!-- كود JavaScript لتحديث الكميات بشكل تفاعلي -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // التعامل مع أزرار زيادة ونقص الكمية
        const minusButtons = document.querySelectorAll('.quantity-btn.minus');
        const plusButtons = document.querySelectorAll('.quantity-btn.plus');
        
        // زر النقص
        minusButtons.forEach(button => {
            button.addEventListener('click', function() {
                const input = this.nextElementSibling;
                let value = parseInt(input.value);
                if (value > 1) {
                    input.value = value - 1;
                    // تقديم النموذج تلقائياً لتحديث السلة
                    this.closest('form').submit();
                }
            });
        });
        
        // زر الزيادة
        plusButtons.forEach(button => {
            button.addEventListener('click', function() {
                const input = this.previousElementSibling;
                let value = parseInt(input.value);
                if (value < 10) {
                    input.value = value + 1;
                    // تقديم النموذج تلقائياً لتحديث السلة
                    this.closest('form').submit();
                }
            });
        });
    });
    </script>

    <script src="../scripts/mobile-nav.js"></script>
    <script>
        // Quantity buttons functionality
        document.addEventListener('DOMContentLoaded', function() {
            const minusBtns = document.querySelectorAll('.quantity-btn.minus');
            const plusBtns = document.querySelectorAll('.quantity-btn.plus');
            
            minusBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const input = this.nextElementSibling;
                    let value = parseInt(input.value);
                    if (value > 1) {
                        input.value = value - 1;
                    }
                });
            });
            
            plusBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const input = this.previousElementSibling;
                    let value = parseInt(input.value);
                    if (value < 10) {
                        input.value = value + 1;
                    }
                });
            });
        });
    </script>
</body>
</html>
