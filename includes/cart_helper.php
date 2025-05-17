<?php
/**
 * وظائف إدارة سلة التسوق
 * يحتوي هذا الملف على وظائف التعامل مع سلة التسوق
 */

// تضمين الملفات اللازمة إذا لم تكن مضمنة بالفعل
if (!defined('BASE_PATH')) {
    require_once 'config.php';
}

/**
 * تهيئة سلة التسوق
 */
function initialize_cart() {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [
            'items' => [],
            'total' => 0,
            'count' => 0
        ];
    }
}

/**
 * إضافة منتج إلى السلة
 * 
 * @param int $product_id معرف المنتج
 * @param int $quantity الكمية
 * @param array $options خيارات إضافية (اللون، الحجم، إلخ)
 * @return array نتيجة العملية (success, message)
 */
function add_to_cart($product_id, $quantity = 1, $options = []) {
    initialize_cart();
    
    $product = getProductById($product_id);
    
    if (!$product) {
        return [
            'success' => false,
            'message' => 'المنتج غير موجود'
        ];
    }
    
    // التحقق من المخزون
    if ($product['stock_quantity'] < $quantity) {
        return [
            'success' => false,
            'message' => 'الكمية المطلوبة غير متوفرة في المخزون. المتوفر: ' . $product['stock_quantity']
        ];
    }
    
    // تحديد معرف فريد للمنتج مع الخيارات
    $item_id = $product_id;
    if (!empty($options)) {
        $item_id .= '_' . md5(json_encode($options));
    }
    
    // التحقق مما إذا كان المنتج موجودًا بالفعل في السلة
    if (isset($_SESSION['cart']['items'][$item_id])) {
        // زيادة الكمية
        $_SESSION['cart']['items'][$item_id]['quantity'] += $quantity;
    } else {
        // إضافة منتج جديد
        $price = isset($product['discount_price']) && $product['discount_price'] > 0 ? $product['discount_price'] : $product['price'];
        
        $_SESSION['cart']['items'][$item_id] = [
            'product_id' => $product_id,
            'name' => $product['name'],
            'price' => $price,
            'original_price' => $product['price'],
            'quantity' => $quantity,
            'image' => !empty($product['primary_image']) ? $product['primary_image'] : SITE_URL . '/assets/images/product-placeholder.png',
            'options' => $options
        ];
    }
    
    // تحديث إجماليات السلة
    update_cart_totals();
    
    return [
        'success' => true,
        'message' => 'تمت إضافة المنتج إلى السلة',
        'cart' => $_SESSION['cart']
    ];
}

/**
 * تحديث كمية منتج في السلة
 * 
 * @param string $item_id معرف العنصر في السلة
 * @param int $quantity الكمية الجديدة
 * @return array نتيجة العملية
 */
function update_cart_quantity($item_id, $quantity) {
    initialize_cart();
    
    if (!isset($_SESSION['cart']['items'][$item_id])) {
        return [
            'success' => false,
            'message' => 'العنصر غير موجود في السلة'
        ];
    }
    
    if ($quantity <= 0) {
        // إزالة المنتج من السلة إذا كانت الكمية 0 أو أقل
        return remove_from_cart($item_id);
    }
    
    // التحقق من المخزون
    $product_id = $_SESSION['cart']['items'][$item_id]['product_id'];
    $product = getProductById($product_id);
    
    if (!$product) {
        return [
            'success' => false,
            'message' => 'المنتج غير موجود'
        ];
    }
    
    if ($product['stock_quantity'] < $quantity) {
        return [
            'success' => false,
            'message' => 'الكمية المطلوبة غير متوفرة في المخزون. المتوفر: ' . $product['stock_quantity']
        ];
    }
    
    // تحديث الكمية
    $_SESSION['cart']['items'][$item_id]['quantity'] = $quantity;
    
    // تحديث إجماليات السلة
    update_cart_totals();
    
    return [
        'success' => true,
        'message' => 'تم تحديث الكمية',
        'cart' => $_SESSION['cart']
    ];
}

/**
 * إزالة منتج من السلة
 * 
 * @param string $item_id معرف العنصر في السلة
 * @return array نتيجة العملية
 */
function remove_from_cart($item_id) {
    initialize_cart();
    
    if (!isset($_SESSION['cart']['items'][$item_id])) {
        return [
            'success' => false,
            'message' => 'العنصر غير موجود في السلة'
        ];
    }
    
    // إزالة العنصر
    unset($_SESSION['cart']['items'][$item_id]);
    
    // تحديث إجماليات السلة
    update_cart_totals();
    
    return [
        'success' => true,
        'message' => 'تمت إزالة المنتج من السلة',
        'cart' => $_SESSION['cart']
    ];
}

/**
 * تفريغ السلة بالكامل
 * 
 * @return array نتيجة العملية
 */
function clear_cart() {
    $_SESSION['cart'] = [
        'items' => [],
        'total' => 0,
        'count' => 0
    ];
    
    return [
        'success' => true,
        'message' => 'تم تفريغ السلة',
        'cart' => $_SESSION['cart']
    ];
}

/**
 * تحديث إجماليات السلة
 */
function update_cart_totals() {
    $total = 0;
    $count = 0;
    
    foreach ($_SESSION['cart']['items'] as $item) {
        $subtotal = $item['price'] * $item['quantity'];
        $total += $subtotal;
        $count += $item['quantity'];
    }
    
    $_SESSION['cart']['total'] = $total;
    $_SESSION['cart']['count'] = $count;
}

/**
 * الحصول على محتويات السلة
 * 
 * @return array محتويات السلة
 */
function get_cart() {
    initialize_cart();
    return $_SESSION['cart'];
}

/**
 * الحصول على عدد العناصر في السلة
 * 
 * @return int عدد العناصر في السلة
 */
function get_cart_count() {
    initialize_cart();
    return isset($_SESSION['cart']['count']) ? $_SESSION['cart']['count'] : 0;
}

/**
 * التحقق من كوبون خصم
 * 
 * @param string $code كود الكوبون
 * @return array|bool بيانات الكوبون أو false إذا لم يكن موجوداً
 */
function get_coupon($code) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT * FROM coupons WHERE code = :code AND is_active = 1 AND expiry_date >= CURDATE() LIMIT 1");
        $stmt->bindParam(':code', $code);
        $stmt->execute();
        
        $coupon = $stmt->fetch();
        
        if ($coupon) {
            // التحقق من عدد مرات الاستخدام المتبقية
            if ($coupon['usage_limit'] > 0 && $coupon['usage_count'] >= $coupon['usage_limit']) {
                return false;
            }
            
            return $coupon;
        }
        
        return false;
    } catch (PDOException $e) {
        error_log("Error getting coupon: " . $e->getMessage());
        return false;
    }
}

/**
 * تطبيق كوبون خصم
 * 
 * @param string $code كود الكوبون
 * @return array نتيجة العملية
 */
function apply_coupon($code) {
    $coupon = get_coupon($code);
    
    if (!$coupon) {
        return [
            'success' => false,
            'message' => 'كوبون الخصم غير صالح أو منتهي الصلاحية'
        ];
    }
    
    // تخزين الكوبون في الجلسة
    $_SESSION['cart']['coupon'] = $coupon;
    
    // حساب قيمة الخصم
    $cart = get_cart();
    $subtotal = $cart['total'];
    
    if ($coupon['discount_type'] === 'percentage') {
        $discount_amount = $subtotal * ($coupon['discount_value'] / 100);
    } else {
        $discount_amount = $coupon['discount_value'];
    }
    
    // التأكد من أن الخصم لا يتجاوز إجمالي السلة
    if ($discount_amount > $subtotal) {
        $discount_amount = $subtotal;
    }
    
    return [
        'success' => true,
        'message' => 'تم تطبيق كوبون الخصم',
        'discount_type' => $coupon['discount_type'],
        'discount_value' => $coupon['discount_value'],
        'discount_amount' => $discount_amount
    ];
}

/**
 * إنشاء طلب جديد - تم نقل هذه الدالة إلى checkout_helper.php
 * لتجنب التكرار ولتنظيم الكود بشكل أفضل
 */
