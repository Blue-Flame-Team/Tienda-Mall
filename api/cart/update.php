<?php
/**
 * Update Cart API Endpoint
 * This file handles updating product quantities in the cart via AJAX
 */

// Set headers for JSON response
header('Content-Type: application/json');

// Include necessary files
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';
require_once '../../includes/Product.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get JSON data from request
$data = json_decode(file_get_contents('php://input'), true);

// Initialize response
$response = [
    'status' => 'error',
    'message' => 'Unknown error occurred',
    'data' => []
];

// Validate request data
if (!isset($data['product_id']) || !is_numeric($data['product_id']) || 
    !isset($data['quantity']) || !is_numeric($data['quantity'])) {
    $response['message'] = 'Invalid request parameters';
    echo json_encode($response);
    exit;
}

$productId = (int)$data['product_id'];
$quantity = (int)$data['quantity'];

// Check if cart exists
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $response['message'] = 'Cart is empty';
    echo json_encode($response);
    exit;
}

// Handle quantity update
if ($quantity > 0) {
    // Update quantity for the specified product
    $productFound = false;
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['product_id'] == $productId) {
            // Get product to check stock
            $productObj = new Product();
            $product = $productObj->getProductById($productId);
            
            // Check if requested quantity is available
            if (isset($product['quantity']) && $product['quantity'] < $quantity) {
                $response['message'] = 'Not enough stock available';
                echo json_encode($response);
                exit;
            }
            
            $item['quantity'] = $quantity;
            $productFound = true;
            break;
        }
    }
    
    if (!$productFound) {
        $response['message'] = 'Product not found in cart';
        echo json_encode($response);
        exit;
    }
} else {
    // Remove product from cart if quantity is 0 or negative
    $newCart = [];
    $productFound = false;
    
    foreach ($_SESSION['cart'] as $item) {
        if ($item['product_id'] != $productId) {
            $newCart[] = $item;
        } else {
            $productFound = true;
        }
    }
    
    if (!$productFound) {
        $response['message'] = 'Product not found in cart';
        echo json_encode($response);
        exit;
    }
    
    $_SESSION['cart'] = $newCart;
}

// Calculate cart totals
$totalItems = 0;
$subtotal = 0;

foreach ($_SESSION['cart'] as $item) {
    $totalItems += $item['quantity'];
    $subtotal += $item['price'] * $item['quantity'];
}

// Return success response
$response = [
    'status' => 'success',
    'message' => $quantity > 0 ? 'Cart updated successfully' : 'Item removed from cart',
    'data' => [
        'cart_items' => $_SESSION['cart'],
        'item_count' => $totalItems,
        'subtotal' => $subtotal,
        'formatted_subtotal' => '$' . number_format($subtotal, 2)
    ]
];

echo json_encode($response);
exit;
?>
