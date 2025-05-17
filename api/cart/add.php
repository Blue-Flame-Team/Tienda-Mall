<?php
/**
 * Add to Cart API Endpoint
 * This file handles adding products to the cart via AJAX
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
if (!isset($data['product_id']) || !is_numeric($data['product_id'])) {
    $response['message'] = 'Invalid product ID';
    echo json_encode($response);
    exit;
}

$productId = (int)$data['product_id'];
$quantity = isset($data['quantity']) && is_numeric($data['quantity']) ? (int)$data['quantity'] : 1;

// Ensure quantity is positive
if ($quantity <= 0) {
    $quantity = 1;
}

// Initialize Product object
$productObj = new Product();

// Check if product exists
$product = $productObj->getProductById($productId);

if (!$product) {
    $response['message'] = 'Product not found';
    echo json_encode($response);
    exit;
}

// Check if product is in stock
if (isset($product['quantity']) && $product['quantity'] < $quantity) {
    $response['message'] = 'Not enough stock available';
    echo json_encode($response);
    exit;
}

// Initialize cart if it doesn't exist
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Check if product is already in cart
$productExists = false;
foreach ($_SESSION['cart'] as &$item) {
    if ($item['product_id'] == $productId) {
        // Update quantity
        $item['quantity'] += $quantity;
        $productExists = true;
        break;
    }
}

// Add product to cart if it doesn't exist
if (!$productExists) {
    $_SESSION['cart'][] = [
        'product_id' => $productId,
        'quantity' => $quantity,
        'price' => $product['price'],
        'title' => $product['title'],
        'image_url' => $product['image_url'],
        'added_at' => time()
    ];
}

// Calculate total items in cart
$totalItems = 0;
foreach ($_SESSION['cart'] as $item) {
    $totalItems += $item['quantity'];
}

// Return success response
$response = [
    'status' => 'success',
    'message' => 'Product added to cart successfully',
    'data' => [
        'item_count' => $totalItems,
        'cart' => $_SESSION['cart']
    ]
];

echo json_encode($response);
exit;
?>
