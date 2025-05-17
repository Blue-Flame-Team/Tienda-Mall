<?php
/**
 * Cart API
 * Handles shopping cart-related API requests
 */

$cartObj = new Cart();

switch ($method) {
    case 'GET':
        // Get cart contents
        $items = $cartObj->getItems();
        $itemCount = $cartObj->getItemCount();
        $subtotal = $cartObj->getSubtotal();
        
        $response = [
            'status' => 'success',
            'message' => 'Cart retrieved successfully',
            'data' => [
                'items' => $items,
                'item_count' => $itemCount,
                'subtotal' => $subtotal,
                'subtotal_formatted' => formatPrice($subtotal)
            ]
        ];
        break;
        
    case 'POST':
        switch ($action) {
            case 'add':
                // Add item to cart
                if (empty($data['product_id'])) {
                    http_response_code(400);
                    $response = [
                        'status' => 'error',
                        'message' => 'Product ID is required',
                        'data' => null
                    ];
                    break;
                }
                
                $quantity = isset($data['quantity']) ? (int)$data['quantity'] : 1;
                $success = $cartObj->addItem($data['product_id'], $quantity);
                
                if ($success) {
                    $items = $cartObj->getItems();
                    $itemCount = $cartObj->getItemCount();
                    $subtotal = $cartObj->getSubtotal();
                    
                    $response = [
                        'status' => 'success',
                        'message' => 'Item added to cart',
                        'data' => [
                            'items' => $items,
                            'item_count' => $itemCount,
                            'subtotal' => $subtotal,
                            'subtotal_formatted' => formatPrice($subtotal)
                        ]
                    ];
                } else {
                    http_response_code(400);
                    $response = [
                        'status' => 'error',
                        'message' => 'Failed to add item to cart. Product may be out of stock.',
                        'data' => null
                    ];
                }
                break;
                
            case 'update':
                // Update cart item quantity
                if (empty($data['product_id']) || !isset($data['quantity'])) {
                    http_response_code(400);
                    $response = [
                        'status' => 'error',
                        'message' => 'Product ID and quantity are required',
                        'data' => null
                    ];
                    break;
                }
                
                $success = $cartObj->updateQuantity($data['product_id'], (int)$data['quantity']);
                
                if ($success) {
                    $items = $cartObj->getItems();
                    $itemCount = $cartObj->getItemCount();
                    $subtotal = $cartObj->getSubtotal();
                    
                    $response = [
                        'status' => 'success',
                        'message' => 'Cart updated successfully',
                        'data' => [
                            'items' => $items,
                            'item_count' => $itemCount,
                            'subtotal' => $subtotal,
                            'subtotal_formatted' => formatPrice($subtotal)
                        ]
                    ];
                } else {
                    http_response_code(400);
                    $response = [
                        'status' => 'error',
                        'message' => 'Failed to update cart. Product may be out of stock.',
                        'data' => null
                    ];
                }
                break;
                
            case 'remove':
                // Remove item from cart
                if (empty($data['product_id'])) {
                    http_response_code(400);
                    $response = [
                        'status' => 'error',
                        'message' => 'Product ID is required',
                        'data' => null
                    ];
                    break;
                }
                
                $success = $cartObj->removeItem($data['product_id']);
                
                if ($success) {
                    $items = $cartObj->getItems();
                    $itemCount = $cartObj->getItemCount();
                    $subtotal = $cartObj->getSubtotal();
                    
                    $response = [
                        'status' => 'success',
                        'message' => 'Item removed from cart',
                        'data' => [
                            'items' => $items,
                            'item_count' => $itemCount,
                            'subtotal' => $subtotal,
                            'subtotal_formatted' => formatPrice($subtotal)
                        ]
                    ];
                } else {
                    http_response_code(400);
                    $response = [
                        'status' => 'error',
                        'message' => 'Failed to remove item from cart',
                        'data' => null
                    ];
                }
                break;
                
            case 'clear':
                // Clear cart
                $cartObj->clearCart();
                
                $response = [
                    'status' => 'success',
                    'message' => 'Cart cleared successfully',
                    'data' => [
                        'items' => [],
                        'item_count' => 0,
                        'subtotal' => 0,
                        'subtotal_formatted' => formatPrice(0)
                    ]
                ];
                break;
                
            case 'apply-coupon':
                // Apply coupon code
                if (empty($data['coupon_code'])) {
                    http_response_code(400);
                    $response = [
                        'status' => 'error',
                        'message' => 'Coupon code is required',
                        'data' => null
                    ];
                    break;
                }
                
                $coupon = $cartObj->applyCoupon($data['coupon_code']);
                
                if ($coupon) {
                    $subtotal = $cartObj->getSubtotal();
                    $discount = $cartObj->calculateDiscount($subtotal, $coupon);
                    
                    $_SESSION['coupon'] = [
                        'code' => $coupon['code'],
                        'discount_type' => $coupon['discount_type'],
                        'discount_value' => $coupon['discount_value'],
                        'discount_amount' => $discount
                    ];
                    
                    $response = [
                        'status' => 'success',
                        'message' => 'Coupon applied successfully',
                        'data' => [
                            'coupon' => $coupon,
                            'discount' => $discount,
                            'discount_formatted' => formatPrice($discount)
                        ]
                    ];
                } else {
                    http_response_code(400);
                    $response = [
                        'status' => 'error',
                        'message' => 'Invalid or expired coupon code',
                        'data' => null
                    ];
                }
                break;
                
            case 'remove-coupon':
                // Remove coupon code
                if (isset($_SESSION['coupon'])) {
                    unset($_SESSION['coupon']);
                }
                
                $response = [
                    'status' => 'success',
                    'message' => 'Coupon removed successfully',
                    'data' => null
                ];
                break;
                
            case 'calculate-totals':
                // Calculate order totals
                $subtotal = $cartObj->getSubtotal();
                $taxRate = 10; // Default tax rate, could be configurable
                $tax = $cartObj->calculateTax($subtotal, $taxRate);
                
                $shippingMethod = $data['shipping_method'] ?? 'standard';
                $shipping = $cartObj->calculateShippingFee($subtotal, $shippingMethod);
                
                $discount = 0;
                $coupon = null;
                
                if (isset($_SESSION['coupon'])) {
                    $coupon = $_SESSION['coupon'];
                    $discount = $coupon['discount_amount'];
                }
                
                $total = $cartObj->calculateTotal($subtotal, $tax, $shipping, $discount);
                
                $response = [
                    'status' => 'success',
                    'message' => 'Totals calculated successfully',
                    'data' => [
                        'subtotal' => $subtotal,
                        'subtotal_formatted' => formatPrice($subtotal),
                        'tax_rate' => $taxRate,
                        'tax' => $tax,
                        'tax_formatted' => formatPrice($tax),
                        'shipping_method' => $shippingMethod,
                        'shipping' => $shipping,
                        'shipping_formatted' => formatPrice($shipping),
                        'discount' => $discount,
                        'discount_formatted' => formatPrice($discount),
                        'coupon' => $coupon,
                        'total' => $total,
                        'total_formatted' => formatPrice($total)
                    ]
                ];
                break;
                
            default:
                http_response_code(400);
                $response = [
                    'status' => 'error',
                    'message' => 'Invalid action',
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
