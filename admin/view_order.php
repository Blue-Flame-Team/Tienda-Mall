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
$order = [];
$order_items = [];
$customer = [];
$error = '';
$success = '';

// Check if order ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: orders.php');
    exit;
}

$order_id = (int)$_GET['id'];

// Process order status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    $admin_note = trim($_POST['admin_note']);
    
    try {
        $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Update order status
        $stmt = $conn->prepare("UPDATE orders SET status = :status, admin_note = :admin_note, updated_at = NOW() WHERE order_id = :order_id");
        $stmt->bindParam(':status', $new_status);
        $stmt->bindParam(':admin_note', $admin_note);
        $stmt->bindParam(':order_id', $order_id);
        $stmt->execute();
        
        $success = 'تم تحديث حالة الطلب بنجاح';
    } catch (PDOException $e) {
        $error = 'حدث خطأ: ' . $e->getMessage();
    }
}

// Get order details
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get order
    $stmt = $conn->prepare(
        "SELECT o.*, 
                u.first_name, 
                u.last_name, 
                u.email, 
                u.phone
         FROM orders o
         LEFT JOIN users u ON o.user_id = u.user_id
         WHERE o.order_id = :order_id"
    );
    $stmt->bindParam(':order_id', $order_id);
    $stmt->execute();
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        header('Location: orders.php');
        exit;
    }
    
    // Parse shipping address from JSON
    $shipping_data = [];
    if (!empty($order['shipping_address'])) {
        $shipping_data = json_decode($order['shipping_address'], true) ?: [];
    }
    
    // Set up default values for missing fields
    $order['subtotal_price'] = isset($order['subtotal']) ? $order['subtotal'] : 0;
    $order['shipping_price'] = isset($order['shipping_cost']) ? $order['shipping_cost'] : 0;
    $order['tax_price'] = isset($order['tax_amount']) ? $order['tax_amount'] : 0;
    $order['currency'] = 'SAR'; // Default currency
    
    // Extract shipping details
    $order['shipping_address_line'] = isset($shipping_data['address']) ? $shipping_data['address'] : '';
    $order['shipping_city'] = isset($shipping_data['city']) ? $shipping_data['city'] : '';
    $order['shipping_state'] = isset($shipping_data['state']) ? $shipping_data['state'] : '';
    $order['shipping_postal_code'] = isset($shipping_data['postal_code']) ? $shipping_data['postal_code'] : '';
    $order['shipping_country'] = isset($shipping_data['country']) ? $shipping_data['country'] : 'Saudi Arabia';
    
    // If we don't have first/last name from users table, check shipping data
    if (empty($order['first_name']) && isset($shipping_data['full_name'])) {
        $name_parts = explode(' ', $shipping_data['full_name'], 2);
        $order['first_name'] = $name_parts[0];
        $order['last_name'] = isset($name_parts[1]) ? $name_parts[1] : '';
    }
    
    // If email is missing, check shipping data
    if (empty($order['email']) && isset($shipping_data['email'])) {
        $order['email'] = $shipping_data['email'];
    }
    
    // If phone is missing, check shipping data
    if (empty($order['phone']) && isset($shipping_data['phone'])) {
        $order['phone'] = $shipping_data['phone'];
    }
    
    // Make sure admin_note exists
    if (!isset($order['admin_note'])) {
        $order['admin_note'] = '';  
    }
    
    // Get order items
    $stmt = $conn->prepare(
        "SELECT oi.*, p.name as product_name, 
                (SELECT image_path FROM product_images WHERE product_id = oi.product_id AND is_primary = 1 LIMIT 1) as product_image
         FROM order_items oi 
         LEFT JOIN products p ON oi.product_id = p.product_id
         WHERE oi.order_id = :order_id"
    );
    $stmt->bindParam(':order_id', $order_id);
    $stmt->execute();
    $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get customer details if registered user
    if ($order['user_id']) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $order['user_id']);
        $stmt->execute();
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
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
                <h1 class="m-0">
                    <i class="fas fa-shopping-bag text-primary mr-2"></i>
                    تفاصيل الطلب #<?php echo htmlspecialchars($order['order_number']); ?>
                </h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="index.php">الرئيسية</a></li>
                    <li class="breadcrumb-item"><a href="orders.php">الطلبات</a></li>
                    <li class="breadcrumb-item active">تفاصيل الطلب #<?php echo htmlspecialchars($order['order_number']); ?></li>
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
            <!-- Order Information -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-info-circle mr-2"></i>
                            معلومات الطلب
                        </h3>
                        <div class="card-tools">
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
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <dl class="row">
                                    <dt class="col-sm-5">رقم الطلب:</dt>
                                    <dd class="col-sm-7"><?php echo htmlspecialchars($order['order_number']); ?></dd>
                                    
                                    <dt class="col-sm-5">تاريخ الطلب:</dt>
                                    <dd class="col-sm-7"><?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?></dd>
                                    
                                    <dt class="col-sm-5">طريقة الدفع:</dt>
                                    <dd class="col-sm-7"><?php echo htmlspecialchars($order['payment_method']); ?></dd>
                                    
                                    <dt class="col-sm-5">حالة الدفع:</dt>
                                    <dd class="col-sm-7">
                                        <?php if ($order['payment_status'] === 'paid'): ?>
                                        <span class="badge badge-success">مدفوع</span>
                                        <?php else: ?>
                                        <span class="badge badge-danger">غير مدفوع</span>
                                        <?php endif; ?>
                                    </dd>
                                </dl>
                            </div>
                            <div class="col-md-6">
                                <dl class="row">
                                    <dt class="col-sm-5">المجموع الفرعي:</dt>
                                    <dd class="col-sm-7"><?php echo number_format($order['subtotal_price'], 2); ?> <?php echo $order['currency']; ?></dd>
                                    
                                    <dt class="col-sm-5">رسوم الشحن:</dt>
                                    <dd class="col-sm-7"><?php echo number_format($order['shipping_price'], 2); ?> <?php echo $order['currency']; ?></dd>
                                    
                                    <dt class="col-sm-5">الضريبة:</dt>
                                    <dd class="col-sm-7"><?php echo number_format($order['tax_price'], 2); ?> <?php echo $order['currency']; ?></dd>
                                    
                                    <dt class="col-sm-5">المجموع:</dt>
                                    <dd class="col-sm-7"><strong><?php echo number_format($order['total_amount'], 2); ?> <?php echo $order['currency']; ?></strong></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Order Items -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-box mr-2"></i>
                            العناصر المطلوبة
                        </h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th style="width: 60px">الصورة</th>
                                        <th>المنتج</th>
                                        <th>السعر</th>
                                        <th>الكمية</th>
                                        <th>المجموع</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($order_items as $item): ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($item['product_image'])): ?>
                                            <img src="<?php echo htmlspecialchars($item['product_image']); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" width="50" height="50" style="object-fit: cover;">
                                            <?php else: ?>
                                            <div class="text-center"><i class="fas fa-image text-muted"></i></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="edit_product.php?id=<?php echo $item['product_id']; ?>">
                                                <?php echo htmlspecialchars($item['product_name']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo number_format($item['price'], 2); ?> <?php echo $order['currency']; ?></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td><?php echo number_format($item['price'] * $item['quantity'], 2); ?> <?php echo $order['currency']; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Customer and Shipping Information -->
            <div class="col-md-4">
                <!-- Customer Information -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-user mr-2"></i>
                            معلومات العميل
                        </h3>
                    </div>
                    <div class="card-body">
                        <p><strong>الاسم:</strong> <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></p>
                        <p><strong>البريد الإلكتروني:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
                        <p><strong>الهاتف:</strong> <?php echo !empty($order['phone']) ? htmlspecialchars($order['phone']) : 'غير متوفر'; ?></p>
                        <?php if ($order['user_id']): ?>
                        <p><strong>نوع العميل:</strong> <span class="badge badge-info">مستخدم مسجل</span></p>
                        <p><a href="users.php?action=view&id=<?php echo $order['user_id']; ?>" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-user mr-1"></i> عرض ملف العميل
                        </a></p>
                        <?php else: ?>
                        <p><strong>نوع العميل:</strong> <span class="badge badge-secondary">ضيف</span></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Shipping Information -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-shipping-fast mr-2"></i>
                            معلومات الشحن
                        </h3>
                    </div>
                    <div class="card-body">
                        <p><strong>الاسم الكامل:</strong> <?php echo isset($shipping_data['full_name']) ? htmlspecialchars($shipping_data['full_name']) : htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></p>
                        <p><strong>العنوان:</strong> <?php echo htmlspecialchars($order['shipping_address_line']); ?></p>
                        <p><strong>المدينة:</strong> <?php echo htmlspecialchars($order['shipping_city']); ?></p>
                        <p><strong>الولاية/المنطقة:</strong> <?php echo htmlspecialchars($order['shipping_state']); ?></p>
                        <p><strong>الرمز البريدي:</strong> <?php echo htmlspecialchars($order['shipping_postal_code']); ?></p>
                        <p><strong>البلد:</strong> <?php echo htmlspecialchars($order['shipping_country']); ?></p>
                        <p><strong>البريد الإلكتروني:</strong> <?php echo isset($shipping_data['email']) ? htmlspecialchars($shipping_data['email']) : htmlspecialchars($order['email']); ?></p>
                        <p><strong>الهاتف:</strong> <?php echo isset($shipping_data['phone']) ? htmlspecialchars($shipping_data['phone']) : (!empty($order['phone']) ? htmlspecialchars($order['phone']) : 'غير متوفر'); ?></p>
                    </div>
                </div>
                
                <!-- Order Actions -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-cog mr-2"></i>
                            إجراءات الطلب
                        </h3>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="form-group">
                                <label for="status">تحديث حالة الطلب</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>قيد الانتظار</option>
                                    <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>قيد المعالجة</option>
                                    <option value="shipped" <?php echo $order['status'] === 'shipped' ? 'selected' : ''; ?>>تم الشحن</option>
                                    <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>>تم التسليم</option>
                                    <option value="canceled" <?php echo $order['status'] === 'canceled' ? 'selected' : ''; ?>>ملغي</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="admin_note">ملاحظات إدارية</label>
                                <textarea class="form-control" id="admin_note" name="admin_note" rows="3"><?php echo htmlspecialchars($order['admin_note']); ?></textarea>
                            </div>
                            <button type="submit" name="update_status" class="btn btn-primary btn-block">
                                <i class="fas fa-save mr-1"></i> حفظ التغييرات
                            </button>
                        </form>
                        <hr>
                        <a href="print_invoice.php?id=<?php echo $order_id; ?>" target="_blank" class="btn btn-info btn-block">
                            <i class="fas fa-print mr-1"></i> طباعة الفاتورة
                        </a>
                        <a href="orders.php" class="btn btn-secondary btn-block mt-2">
                            <i class="fas fa-arrow-right ml-1"></i> العودة للطلبات
                        </a>
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
