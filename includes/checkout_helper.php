<?php
/**
 * Funciones para el proceso de checkout
 * Este archivo contiene funciones para validar, calcular y procesar órdenes desde el checkout
 */

// Incluir archivos necesarios si no están ya incluidos
if (!defined('BASE_PATH')) {
    require_once 'config.php';
}

/**
 * Validar datos de checkout
 * 
 * @param array $checkout_data Datos a validar
 * @return array Resultado de validación (valid, errors)
 */
function validate_checkout_data($checkout_data) {
    $errors = [];
    
    // Validar campos requeridos
    $required_fields = ['full_name', 'email', 'phone', 'address', 'city'];
    
    foreach ($required_fields as $field) {
        if (empty($checkout_data[$field])) {
            $errors[] = 'الحقل "' . get_field_name($field) . '" مطلوب';
        }
    }
    
    // Validar formato de email
    if (!empty($checkout_data['email']) && !filter_var($checkout_data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'الرجاء إدخال بريد إلكتروني صحيح';
    }
    
    // Validar número de teléfono (debe tener al menos 10 dígitos)
    if (!empty($checkout_data['phone'])) {
        $phone = preg_replace('/[^0-9]/', '', $checkout_data['phone']);
        if (strlen($phone) < 10) {
            $errors[] = 'الرجاء إدخال رقم هاتف صحيح (10 أرقام على الأقل)';
        }
    }
    
    // Validar que la canasta (carrito) no esté vacía
    $cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
    if (empty($cart['items'])) {
        $errors[] = 'السلة فارغة'; // El carrito está vacío
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Obtener nombre legible para campos de formulario
 * 
 * @param string $field_name Nombre del campo
 * @return string Nombre legible
 */
function get_field_name($field_name) {
    $field_names = [
        'full_name' => 'الاسم الكامل',
        'email' => 'البريد الإلكتروني',
        'phone' => 'رقم الهاتف',
        'address' => 'العنوان',
        'city' => 'المدينة',
        'state' => 'المنطقة',
        'postal_code' => 'الرمز البريدي',
        'country' => 'الدولة',
        'payment_method' => 'طريقة الدفع',
        'notes' => 'الملاحظات'
    ];
    
    return $field_names[$field_name] ?? $field_name;
}

/**
 * Calcular totales para el checkout
 * 
 * @param array $options Opciones para el cálculo (shipping_cost, coupon_code)
 * @return array Totales calculados
 */
function calculate_checkout_totals($options = []) {
    // Obtener el carrito
    $cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [
        'items' => [],
        'totals' => [
            'subtotal' => 0,
            'tax' => 0,
            'shipping' => 0,
            'discount' => 0,
            'total' => 0
        ]
    ];
    
    // Calcular subtotal
    $subtotal = 0;
    foreach ($cart['items'] as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }
    
    // Opciones por defecto
    $shipping_cost = isset($options['shipping_cost']) ? $options['shipping_cost'] : 25; // 25 SAR por defecto
    $coupon_code = isset($options['coupon_code']) ? $options['coupon_code'] : null;
    
    // Aplicar descuento si hay cupón
    $discount_amount = 0;
    $coupon_info = null;
    
    if ($coupon_code && isset($cart['coupon'])) {
        $coupon_info = $cart['coupon'];
        $discount_amount = $coupon_info['amount'];
    }
    
    // Calcular impuestos (15% IVA)
    $tax_rate = 0.15; // 15%
    $tax_amount = ($subtotal - $discount_amount) * $tax_rate;
    
    // Calcular total
    $total_amount = $subtotal - $discount_amount + $tax_amount + $shipping_cost;
    
    return [
        'subtotal' => $subtotal,
        'discount_amount' => $discount_amount,
        'coupon_info' => $coupon_info,
        'tax_rate' => $tax_rate,
        'tax_amount' => $tax_amount,
        'shipping_cost' => $shipping_cost,
        'total_amount' => $total_amount
    ];
}

/**
 * Crear orden a partir de datos de checkout
 * 
 * @param array $checkout_data Datos del checkout
 * @return array Resultado de la operación
 */
function create_checkout_order($checkout_data) {
    global $conn;
    
    try {
        // Comprobar si hay elementos en el carrito
        if (!isset($_SESSION['cart']) || empty($_SESSION['cart']['items'])) {
            return [ 'success' => false, 'message' => 'السلة فارغة' ];
        }
        
        $cart = $_SESSION['cart'];
        
        // Calcular totales
        $totals = calculate_checkout_totals([
            'shipping_cost' => $checkout_data['shipping_cost'],
            'coupon_code' => $checkout_data['coupon_code']
        ]);
        
        // Iniciar transacción
        $conn->beginTransaction();
        
        // Generar número de orden único
        $order_number = 'ORD-' . date('Ymd') . '-' . mt_rand(1000, 9999);
        
        // Crear dirección de envío en formato JSON
        $shipping_address = json_encode([
            'full_name' => $checkout_data['full_name'],
            'email' => $checkout_data['email'],
            'phone' => $checkout_data['phone'],
            'address' => $checkout_data['address'],
            'city' => $checkout_data['city'],
            'state' => $checkout_data['state'] ?? '',
            'postal_code' => $checkout_data['postal_code'] ?? '',
            'country' => $checkout_data['country'] ?? 'Saudi Arabia'
        ], JSON_UNESCAPED_UNICODE);
        
        // Usar la misma dirección para facturación si no se especifica otra
        $billing_address = $shipping_address;
        
        // Insertar la orden en la base de datos
        $stmt = $conn->prepare("INSERT INTO orders (
            user_id, order_number, total_amount, shipping_address, billing_address,
            payment_method, status, payment_status
        ) VALUES (
            :user_id, :order_number, :total_amount, :shipping_address, :billing_address,
            :payment_method, :status, :payment_status
        )");
        
        // ID de usuario (si está autenticado)
        $user_id = isset($_SESSION['user']) && isset($_SESSION['user']['user_id']) ? $_SESSION['user']['user_id'] : null;
        
        // Estado inicial
        $status = 'pending';
        $payment_status = $checkout_data['payment_method'] == 'cash_on_delivery' ? 'pending' : 'pending';
        
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':order_number', $order_number);
        $stmt->bindParam(':total_amount', $totals['total_amount']);
        $stmt->bindParam(':shipping_address', $shipping_address);
        $stmt->bindParam(':billing_address', $billing_address);
        $stmt->bindParam(':payment_method', $checkout_data['payment_method']);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':payment_status', $payment_status);
        
        $stmt->execute();
        
        $order_id = $conn->lastInsertId();
        
        // Insertar items de la orden
        foreach ($cart['items'] as $item) {
            $stmt = $conn->prepare("INSERT INTO order_items (
                order_id, product_id, quantity, price, total
            ) VALUES (
                :order_id, :product_id, :quantity, :price, :total
            )");
            
            // Calculamos el total del item
            $item_total = $item['price'] * $item['quantity'];
            
            $stmt->bindParam(':order_id', $order_id);
            $stmt->bindParam(':product_id', $item['product_id']);
            $stmt->bindParam(':quantity', $item['quantity']);
            $stmt->bindParam(':price', $item['price']);
            $stmt->bindParam(':total', $item_total);
            
            $stmt->execute();
            
            // Actualizar stock del producto
            $stmt = $conn->prepare("UPDATE products 
                               SET stock_quantity = stock_quantity - :quantity 
                               WHERE product_id = :product_id");
            $stmt->bindParam(':quantity', $item['quantity']);
            $stmt->bindParam(':product_id', $item['product_id']);
            $stmt->execute();
        }
        
        // Confirmar transacción
        $conn->commit();
        
        // Vaciar el carrito
        clear_cart();
        
        // Registrar evento de compra (para análisis)
        // log_purchase_event($order_id, $totals['total_amount']);
        
        // Enviar correo de confirmación
        // send_order_confirmation_email($order_id);
        
        return [
            'success' => true,
            'message' => 'تم إنشاء الطلب بنجاح',
            'order_id' => $order_id,
            'order_number' => $order_number
        ];
        
    } catch (PDOException $e) {
        // Revertir transacción en caso de error
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        
        // Mejorar el registro de errores
        $debug_info = "SQL Error: " . $e->getMessage() . 
                     "\nSQL State: " . $e->getCode() . 
                     "\nTrace: " . $e->getTraceAsString();
        
        error_log("Error detallado creando orden desde checkout: " . $debug_info);
        
        // En modo desarrollo, mostrar error detallado
        if (defined('DEV_MODE') && DEV_MODE) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'error' => $debug_info,
                'debug' => [
                    'user_id' => $user_id,
                    'order_number' => $order_number,
                    'total' => $totals['total_amount']
                ]
            ];
        } else {
            // En producción, mensaje amigable
            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء الطلب. الرجاء المحاولة مرة أخرى.',
                'error' => $e->getMessage()
            ];
        }
    }
}
