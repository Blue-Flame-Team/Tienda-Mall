<?php
/**
 * صفحة إتمام الشراء
 * هذه الصفحة تسمح للمستخدم بإكمال عملية الشراء وإدخال معلومات الشحن والدفع
 */

// تضمين ملف bootstrap الذي يحتوي على جميع المكونات اللازمة
require_once '../includes/bootstrap.php';

// التأكد من تهيئة السلة
initialize_cart();

// الحصول على محتويات السلة
$cart = get_cart();

// إذا كانت السلة فارغة، يتم التحويل إلى صفحة السلة
if (empty($cart['items'])) {
    redirect(SITE_URL . '/pages/cart.php?empty=1');
    exit;
}

// متغيرات العرض
$error = '';
$success = '';

// معالجة تقديم نموذج إتمام الشراء
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    // جمع بيانات النموذج
    $checkout_data = [
        'full_name' => $_POST['full_name'] ?? '',
        'email' => $_POST['email'] ?? '',
        'phone' => $_POST['phone'] ?? '',
        'address' => $_POST['address'] ?? '',
        'city' => $_POST['city'] ?? '',
        'state' => $_POST['state'] ?? '',
        'postal_code' => $_POST['postal_code'] ?? '',
        'country' => $_POST['country'] ?? 'Saudi Arabia',
        'payment_method' => $_POST['payment_method'] ?? 'cash_on_delivery',
        'shipping_cost' => 25, // رسوم الشحن الافتراضية
        'notes' => $_POST['notes'] ?? '',
        'coupon_code' => isset($_SESSION['cart']['coupon']) ? $_SESSION['cart']['coupon']['code'] : ''
    ];
    
    // التحقق من صحة البيانات
    $validation = validate_checkout_data($checkout_data);
    
    if (!$validation['valid']) {
        $error = implode('<br>', $validation['errors']);
    } else {
        // إنشاء الطلب
        $result = create_checkout_order($checkout_data);
        
        if ($result['success']) {
            // تحويل المستخدم إلى صفحة تأكيد الطلب
            redirect(SITE_URL . '/pages/order_confirmation.php?order=' . $result['order_number']);
            exit;
        } else {
            $error = $result['message'];
            
            // Mostrar información detallada de errores en modo desarrollo
            if (defined('DEV_MODE') && DEV_MODE && isset($result['error'])) {
                $error .= '<br><div style="background-color: #ffeeee; border: 1px solid #ffaaaa; padding: 10px; margin-top: 10px; color: #aa0000; font-family: monospace; white-space: pre-wrap;">';
                $error .= '<strong>Error Técnico (solo visible en modo desarrollo):</strong><br>';
                $error .= htmlspecialchars($result['error']);
                if (isset($result['debug'])) {
                    $error .= '<br><strong>Debug:</strong><br>';
                    $error .= htmlspecialchars(print_r($result['debug'], true));
                }
                $error .= '</div>';
            }
        }
    }
}

// حساب إجماليات الطلب
$totals = calculate_checkout_totals([
    'shipping_cost' => 25, // رسوم الشحن الافتراضية
    'coupon_code' => isset($_SESSION['cart']['coupon']) ? $_SESSION['cart']['coupon']['code'] : ''
]);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إتمام الشراء - متجر تيندا</title>
    
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
        .checkout-container {
            padding: 30px 0;
        }
        .checkout-header {
            margin-bottom: 20px;
        }
        .checkout-form {
            background-color: #fff;
            border-radius: 5px;
            padding: 20px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        .cart-summary {
            background-color: #fff;
            border-radius: 5px;
            padding: 20px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        .form-group label {
            font-weight: 600;
        }
        .item-row {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
        }
        .payment-options {
            margin-top: 20px;
        }
        .payment-option {
            margin-bottom: 15px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
        }
        .payment-option.active {
            border-color: #28a745;
            background-color: #f8fff8;
        }
        .btn-place-order {
            background-color: #d63031;
            border-color: #d63031;
            color: white;
            font-weight: bold;
            padding: 12px 30px;
            border-radius: 30px;
            margin-top: 20px;
        }
        .btn-place-order:hover {
            background-color: #c72c2c;
            border-color: #c72c2c;
            color: white;
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
    
    <div class="container checkout-container">
        <div class="row checkout-header">
            <div class="col-12">
                <h2>إتمام الشراء</h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb bg-transparent p-0">
                        <li class="breadcrumb-item"><a href="../index.php">الرئيسية</a></li>
                        <li class="breadcrumb-item"><a href="cart.php">سلة التسوق</a></li>
                        <li class="breadcrumb-item active" aria-current="page">إتمام الشراء</li>
                    </ol>
                </nav>
            </div>
        </div>
        
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
        
        <form method="post" action="">
            <div class="row">
                <div class="col-md-8">
                    <div class="checkout-form">
                        <h4 class="mb-4">بيانات الشحن</h4>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="full_name">الاسم الكامل <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email">البريد الإلكتروني <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="phone">رقم الهاتف <span class="text-danger">*</span></label>
                                    <input type="tel" class="form-control" id="phone" name="phone" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="address">العنوان <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="address" name="address" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="city">المدينة <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="city" name="city" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="state">المنطقة</label>
                                    <input type="text" class="form-control" id="state" name="state">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="postal_code">الرمز البريدي</label>
                                    <input type="text" class="form-control" id="postal_code" name="postal_code">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="notes">ملاحظات إضافية</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                        </div>
                        
                        <div class="payment-options">
                            <h4 class="mb-3">طرق الدفع</h4>
                            
                            <div class="payment-option active">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" id="cash_on_delivery" value="cash_on_delivery" checked>
                                    <label class="form-check-label" for="cash_on_delivery">
                                        <i class="fas fa-money-bill-wave mr-2"></i> الدفع عند الاستلام
                                    </label>
                                </div>
                                <div class="payment-description mt-2">
                                    <small>يمكنك الدفع نقداً عند استلام طلبك</small>
                                </div>
                            </div>
                            
                            <div class="payment-option">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" id="credit_card" value="credit_card">
                                    <label class="form-check-label" for="credit_card">
                                        <i class="fas fa-credit-card mr-2"></i> بطاقة ائتمان
                                    </label>
                                </div>
                                <div class="payment-description mt-2">
                                    <small>سيتم تحويلك إلى صفحة آمنة لإتمام عملية الدفع</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="cart-summary">
                        <h4 class="mb-4">ملخص الطلب</h4>
                        
                        <?php foreach ($cart['items'] as $item_id => $item): ?>
                        <div class="item-row">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="mb-0"><?php echo htmlspecialchars($item['name']); ?></h6>
                                    <small class="text-muted">الكمية: <?php echo $item['quantity']; ?></small>
                                </div>
                                <div>
                                    <span class="font-weight-bold"><?php echo number_format($item['price'] * $item['quantity'], 2); ?> ريال</span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <hr>
                        
                        <div class="totals-row">
                            <span>المجموع الفرعي:</span>
                            <span><?php echo number_format($totals['subtotal'], 2); ?> ريال</span>
                        </div>
                        
                        <?php if ($totals['discount_amount'] > 0): ?>
                        <div class="totals-row text-success">
                            <span>الخصم:</span>
                            <span>-<?php echo number_format($totals['discount_amount'], 2); ?> ريال</span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="totals-row">
                            <span>الضريبة (15%):</span>
                            <span><?php echo number_format($totals['tax_amount'], 2); ?> ريال</span>
                        </div>
                        
                        <div class="totals-row">
                            <span>الشحن:</span>
                            <span><?php echo number_format($totals['shipping_cost'], 2); ?> ريال</span>
                        </div>
                        
                        <div class="totals-row font-weight-bold">
                            <span>الإجمالي:</span>
                            <span><?php echo number_format($totals['total_amount'], 2); ?> ريال</span>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" name="place_order" class="btn btn-place-order btn-block">
                                <i class="fas fa-check-circle mr-2"></i> إتمام الطلب
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
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
    
    <script>
    $(document).ready(function() {
        // تبديل تنشيط خيارات الدفع
        $('.payment-option').click(function() {
            $('.payment-option').removeClass('active');
            $(this).addClass('active');
            $(this).find('input[type="radio"]').prop('checked', true);
        });
    });
    </script>
</body>
</html>
