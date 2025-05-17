<?php
/**
 * صفحة تأكيد الطلب
 * تظهر هذه الصفحة بعد إتمام عملية الشراء وتعرض تفاصيل الطلب
 */

// تضمين ملف bootstrap الذي يحتوي على جميع المكونات اللازمة
require_once '../includes/bootstrap.php';

// التحقق من وجود رقم الطلب في الرابط
if (!isset($_GET['order']) || empty($_GET['order'])) {
    // إذا لم يكن هناك رقم طلب، يتم التحويل إلى الصفحة الرئيسية
    redirect(SITE_URL);
    exit;
}

$order_number = $_GET['order'];
$order = null;

try {
    // البحث عن الطلب في قاعدة البيانات
    $stmt = $conn->prepare("SELECT * FROM orders WHERE order_number = :order_number LIMIT 1");
    $stmt->bindParam(':order_number', $order_number);
    $stmt->execute();
    
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // إذا لم يتم العثور على الطلب، يتم التحويل إلى الصفحة الرئيسية
    if (!$order) {
        redirect(SITE_URL);
        exit;
    }
    
    // الحصول على عناصر الطلب
    $stmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = :order_id");
    $stmt->bindParam(':order_id', $order['order_id']);
    $stmt->execute();
    
    $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Error fetching order: " . $e->getMessage());
    redirect(SITE_URL);
    exit;
}

// التحقق من تاريخ التسليم المتوقع (بعد 3-5 أيام من تاريخ الطلب)
$order_date = new DateTime($order['created_at']);
$min_delivery_date = clone $order_date;
$min_delivery_date->modify('+3 days');
$max_delivery_date = clone $order_date;
$max_delivery_date->modify('+5 days');

// تهيئة عنوان الشحن من JSON
$shipping_address = json_decode($order['shipping_address'], true);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تأكيد الطلب - متجر تيندا</title>
    
    <!-- فونت أوسوم -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- بوتستراب -->
    <link rel="stylesheet" href="https://cdn.rtlcss.com/bootstrap/v4.5.3/css/bootstrap.min.css">
    
    <!-- الخطوط -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background-color: #f8f9fa;
            color: #333;
        }
        .confirmation-container {
            padding: 50px 0;
        }
        .confirmation-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .confirmation-box {
            background-color: #fff;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .order-status {
            display: inline-block;
            padding: 8px 15px;
            border-radius: 30px;
            font-weight: bold;
            font-size: 14px;
            background-color: #ffeaa7;
            color: #fdcb6e;
        }
        .status-pending {
            background-color: #ffeaa7;
            color: #fdcb6e;
        }
        .status-processing {
            background-color: #81ecec;
            color: #00cec9;
        }
        .status-shipped {
            background-color: #a29bfe;
            color: #6c5ce7;
        }
        .status-delivered {
            background-color: #55efc4;
            color: #00b894;
        }
        .delivery-info {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 15px;
            margin-top: 20px;
        }
        .order-items {
            margin-top: 30px;
        }
        .item-row {
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }
        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
        }
        .btn-shop-more {
            background-color: #d63031;
            border-color: #d63031;
            color: white;
            font-weight: bold;
            padding: 10px 30px;
            border-radius: 30px;
            margin-top: 20px;
        }
        .btn-shop-more:hover {
            background-color: #c72c2c;
            border-color: #c72c2c;
            color: white;
        }
        .thank-you-message {
            text-align: center;
            margin: 40px 0;
        }
        .thank-you-message i {
            font-size: 60px;
            color: #00b894;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div class="d-flex justify-content-between align-items-center py-3">
                <a href="../index.php" class="logo">
                    <h2 class="m-0 text-danger">متجر تيندا</h2>
                </a>
                <nav>
                    <a href="../index.php" class="mr-3">الرئيسية</a>
                    <a href="cart.php" class="mr-3">السلة</a>
                    <a href="contact.php">اتصل بنا</a>
                </nav>
            </div>
        </div>
    </header>
    
    <div class="container confirmation-container">
        <div class="row confirmation-header">
            <div class="col-12 thank-you-message">
                <i class="fas fa-check-circle"></i>
                <h2>شكراً لطلبك!</h2>
                <p>تم استلام طلبك بنجاح وسيتم معالجته في أقرب وقت ممكن.</p>
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-8">
                <div class="confirmation-box">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h4>تفاصيل الطلب</h4>
                            <p class="text-muted mb-0">رقم الطلب: #<?php echo htmlspecialchars($order_number); ?></p>
                            <p class="text-muted">تاريخ الطلب: <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></p>
                        </div>
                        <div>
                            <div class="order-status status-<?php echo $order['status']; ?>">
                                <?php 
                                $status_text = '';
                                switch ($order['status']) {
                                    case 'pending':
                                        $status_text = 'قيد الانتظار';
                                        break;
                                    case 'processing':
                                        $status_text = 'قيد المعالجة';
                                        break;
                                    case 'shipped':
                                        $status_text = 'تم الشحن';
                                        break;
                                    case 'delivered':
                                        $status_text = 'تم التسليم';
                                        break;
                                    default:
                                        $status_text = 'قيد الانتظار';
                                }
                                echo $status_text;
                                ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="delivery-info">
                        <h5>موعد التسليم المتوقع</h5>
                        <p class="mb-0">
                            <?php 
                            echo $min_delivery_date->format('d') . ' - ' . $max_delivery_date->format('d') . ' ' . 
                                 $max_delivery_date->format('F Y');
                            ?>
                        </p>
                    </div>
                    
                    <div class="order-items">
                        <h5 class="mb-3">المنتجات</h5>
                        
                        <?php foreach ($order_items as $item): ?>
                        <div class="item-row">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="mb-0"><?php echo htmlspecialchars($item['name']); ?></h6>
                                    <small class="text-muted">الكمية: <?php echo $item['quantity']; ?></small>
                                </div>
                                <div>
                                    <span class="font-weight-bold"><?php echo number_format($item['total'], 2); ?> ريال</span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="confirmation-box">
                    <h4 class="mb-4">ملخص الطلب</h4>
                    
                    <div class="totals-row">
                        <span>المجموع الفرعي:</span>
                        <span><?php echo number_format($order['subtotal'], 2); ?> ريال</span>
                    </div>
                    
                    <?php if ($order['discount_amount'] > 0): ?>
                    <div class="totals-row text-success">
                        <span>الخصم:</span>
                        <span>-<?php echo number_format($order['discount_amount'], 2); ?> ريال</span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="totals-row">
                        <span>الضريبة:</span>
                        <span><?php echo number_format($order['tax_amount'], 2); ?> ريال</span>
                    </div>
                    
                    <div class="totals-row">
                        <span>الشحن:</span>
                        <span><?php echo number_format($order['shipping_cost'], 2); ?> ريال</span>
                    </div>
                    
                    <div class="totals-row font-weight-bold">
                        <span>الإجمالي:</span>
                        <span><?php echo number_format($order['total_amount'], 2); ?> ريال</span>
                    </div>
                    
                    <hr>
                    
                    <h5>معلومات الشحن</h5>
                    <address>
                        <?php echo htmlspecialchars($shipping_address['full_name']); ?><br>
                        <?php echo htmlspecialchars($shipping_address['address']); ?><br>
                        <?php echo htmlspecialchars($shipping_address['city']); ?>, 
                        <?php echo htmlspecialchars($shipping_address['state'] ?? ''); ?> 
                        <?php echo htmlspecialchars($shipping_address['postal_code'] ?? ''); ?><br>
                        <?php echo htmlspecialchars($shipping_address['country']); ?><br>
                        هاتف: <?php echo htmlspecialchars($shipping_address['phone']); ?>
                    </address>
                    
                    <hr>
                    
                    <h5>طريقة الدفع</h5>
                    <p>
                        <?php 
                        $payment_method = '';
                        switch ($order['payment_method']) {
                            case 'cash_on_delivery':
                                $payment_method = 'الدفع عند الاستلام';
                                break;
                            case 'credit_card':
                                $payment_method = 'بطاقة ائتمان';
                                break;
                            default:
                                $payment_method = 'الدفع عند الاستلام';
                        }
                        echo $payment_method;
                        ?>
                    </p>
                    
                    <div class="text-center mt-4">
                        <a href="../index.php" class="btn btn-shop-more">
                            <i class="fas fa-shopping-cart mr-2"></i> مواصلة التسوق
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <footer class="mt-5 py-4 bg-dark text-white">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>متجر تيندا</h5>
                    <p>تسوق بسهولة وأمان. نوفر لك أفضل المنتجات بأفضل الأسعار.</p>
                </div>
                <div class="col-md-4">
                    <h5>روابط سريعة</h5>
                    <ul class="list-unstyled">
                        <li><a href="../index.php" class="text-white">الرئيسية</a></li>
                        <li><a href="cart.php" class="text-white">السلة</a></li>
                        <li><a href="contact.php" class="text-white">اتصل بنا</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>تواصل معنا</h5>
                    <address>
                        <i class="fas fa-map-marker-alt mr-2"></i> الرياض، المملكة العربية السعودية<br>
                        <i class="fas fa-phone mr-2"></i> +966 50 123 4567<br>
                        <i class="fas fa-envelope mr-2"></i> info@tienda.com
                    </address>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12 text-center">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> متجر تيندا. جميع الحقوق محفوظة.</p>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- جافاسكريبت -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
