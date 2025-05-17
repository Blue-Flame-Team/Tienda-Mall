<?php
/**
 * Orders API
 * Handles order-related API requests
 */

$orderObj = new Order();

switch ($method) {
    case 'GET':
        // Authenticate user
        if (!isLoggedIn()) {
            http_response_code(401);
            $response = [
                'status' => 'error',
                'message' => 'Unauthorized',
                'data' => null
            ];
            break;
        }
        
        $userId = getCurrentUserId();
        
        // Get specific order
        if ($id) {
            $order = $orderObj->getOrderById($id);
            
            if ($order) {
                // Check if order belongs to current user or if admin
                if ($order['user_id'] == $userId || isAdminLoggedIn()) {
                    $response = [
                        'status' => 'success',
                        'message' => 'Order retrieved successfully',
                        'data' => $order
                    ];
                } else {
                    http_response_code(403);
                    $response = [
                        'status' => 'error',
                        'message' => 'You do not have permission to view this order',
                        'data' => null
                    ];
                }
            } else {
                http_response_code(404);
                $response = [
                    'status' => 'error',
                    'message' => 'Order not found',
                    'data' => null
                ];
            }
        } else {
            // Get list of orders for current user
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $offset = ($page - 1) * $limit;
            
            $orders = $orderObj->getOrdersByUserId($userId, $limit, $offset);
            $total = $orderObj->getOrderCountByUserId($userId);
            
            $response = [
                'status' => 'success',
                'message' => 'Orders retrieved successfully',
                'data' => [
                    'orders' => $orders,
                    'pagination' => [
                        'total' => $total,
                        'page' => $page,
                        'limit' => $limit,
                        'total_pages' => ceil($total / $limit)
                    ]
                ]
            ];
        }
        break;
        
    case 'POST':
        // Create new order
        if (!isLoggedIn()) {
            http_response_code(401);
            $response = [
                'status' => 'error',
                'message' => 'Unauthorized',
                'data' => null
            ];
            break;
        }
        
        $userId = getCurrentUserId();
        
        // Set user ID
        $data['user_id'] = $userId;
        
        // Calculate order totals
        $cartObj = new Cart();
        $subtotal = $cartObj->getSubtotal();
        
        // Check if cart is empty
        if ($subtotal <= 0) {
            http_response_code(400);
            $response = [
                'status' => 'error',
                'message' => 'Your cart is empty',
                'data' => null
            ];
            break;
        }
        
        // Calculate tax
        $taxRate = 10; // Default tax rate
        $tax = $cartObj->calculateTax($subtotal, $taxRate);
        
        // Calculate shipping
        $shippingMethod = $data['shipping_method'] ?? 'standard';
        $shipping = $cartObj->calculateShippingFee($subtotal, $shippingMethod);
        
        // Apply coupon if available
        $discount = 0;
        $couponCode = null;
        
        if (isset($_SESSION['coupon'])) {
            $coupon = $_SESSION['coupon'];
            $discount = $coupon['discount_amount'];
            $couponCode = $coupon['code'];
        }
        
        // Calculate total
        $total = $cartObj->calculateTotal($subtotal, $tax, $shipping, $discount);
        
        // Prepare order data
        $orderData = [
            'user_id' => $userId,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'shipping_fee' => $shipping,
            'discount' => $discount,
            'total' => $total,
            'shipping_address_id' => $data['shipping_address_id'],
            'billing_address_id' => $data['billing_address_id'] ?? $data['shipping_address_id'],
            'payment_method_id' => $data['payment_method_id'] ?? null,
            'notes' => $data['notes'] ?? null,
            'coupon_code' => $couponCode
        ];
        
        // Create order
        $orderId = $orderObj->createOrder($orderData);
        
        if ($orderId) {
            // Clear coupon after successful order
            if (isset($_SESSION['coupon'])) {
                unset($_SESSION['coupon']);
            }
            
            $order = $orderObj->getOrderById($orderId);
            
            $response = [
                'status' => 'success',
                'message' => 'Order created successfully',
                'data' => [
                    'order_id' => $orderId,
                    'order' => $order
                ]
            ];
        } else {
            http_response_code(500);
            $response = [
                'status' => 'error',
                'message' => 'Failed to create order',
                'data' => null
            ];
        }
        break;
        
    case 'PUT':
        // Update order status (admin only)
        if (!isAdminLoggedIn()) {
            http_response_code(401);
            $response = [
                'status' => 'error',
                'message' => 'Unauthorized',
                'data' => null
            ];
            break;
        }
        
        if (!$id) {
            http_response_code(400);
            $response = [
                'status' => 'error',
                'message' => 'Order ID is required',
                'data' => null
            ];
            break;
        }
        
        if (empty($data['status'])) {
            http_response_code(400);
            $response = [
                'status' => 'error',
                'message' => 'Status is required',
                'data' => null
            ];
            break;
        }
        
        $trackingNumber = $data['tracking_number'] ?? null;
        $success = $orderObj->updateOrderStatus($id, $data['status'], $trackingNumber);
        
        if ($success) {
            $response = [
                'status' => 'success',
                'message' => 'Order status updated successfully',
                'data' => null
            ];
        } else {
            http_response_code(500);
            $response = [
                'status' => 'error',
                'message' => 'Failed to update order status',
                'data' => null
            ];
        }
        break;
        
    default:
        http_response_code(405);
        $response = [
            'status' => 'error',
            'message' => 'Method not allowed',
            'data' => null
        ];
}
?>
