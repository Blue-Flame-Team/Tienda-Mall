<?php
/**
 * Cart API Endpoint - Add to Cart
 * Standalone endpoint that handles both JSON and form data
 */

// Set proper content type for JSON response
header('Content-Type: application/json');

// Allow cross-origin requests for development
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize response
$response = [
    'status' => 'error',
    'message' => 'Unknown error occurred',
    'cart_count' => 0,
    'cart_total' => 0,
    'cart_items' => []
];

try {
    // Only allow POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed. Use POST method.');
    }
    
    // Get input data - handle both JSON and form data
    $input = [];
    $contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
    
    if (strpos($contentType, 'application/json') !== false) {
        // JSON data
        $jsonInput = file_get_contents('php://input');
        $input = json_decode($jsonInput, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON data: ' . json_last_error_msg());
        }
    } else {
        // Form data
        $input = $_POST;
    }
    
    // Validate required parameters
    if (empty($input['product_id'])) {
        throw new Exception('Product ID is required');
    }
    
    // Get parameters
    $productId = intval($input['product_id']);
    $quantity = isset($input['quantity']) ? intval($input['quantity']) : 1;
    
    // Make sure quantity is at least 1
    if ($quantity < 1) {
        $quantity = 1;
    }
    
    // Include required files
    require_once '../includes/config.php';
    require_once '../includes/db.php';
    
    // Connect to database
    $db = Database::getInstance();
    
    // Get product information
    $stmt = $db->query("SELECT * FROM products WHERE product_id = ? AND is_active = 1", [$productId]);
    $product = $stmt->fetch();
    
    if (!$product) {
        throw new Exception('Product not found');
    }
    
    // Check stock if needed
    if (isset($product['quantity'])) {
        $availableStock = $product['quantity'] - ($product['reserved_quantity'] ?? 0);
        
        if ($availableStock < $quantity) {
            throw new Exception("Only {$availableStock} units available in stock");
        }
    }
    
    // Initialize cart if needed
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [
            'items' => [],
            'total' => 0,
            'count' => 0
        ];
    }
    
    // Check if product already exists in cart
    $itemExists = false;
    $itemId = '';
    
    foreach ($_SESSION['cart']['items'] as $index => $item) {
        if ($item['product_id'] == $productId) {
            $itemExists = true;
            $itemId = $index;
            break;
        }
    }
    
    // Prepare product price
    $price = !empty($product['sale_price']) ? $product['sale_price'] : $product['price'];
    
    // Add or update cart item
    if ($itemExists) {
        // Update existing item
        $_SESSION['cart']['items'][$itemId]['quantity'] += $quantity;
    } else {
        // Get product image
        $imagePath = 'assets/images/product-placeholder.png';
        
        $stmt = $db->query("SELECT image_path FROM product_images WHERE product_id = ? AND is_primary = 1 LIMIT 1", [$productId]);
        $image = $stmt->fetch();
        
        if ($image && !empty($image['image_path'])) {
            $imagePath = $image['image_path'];
            // Fix image path if needed
            if (strpos($imagePath, '../') === 0) {
                $imagePath = substr($imagePath, 3);
            }
        }
        
        // Add new item
        $_SESSION['cart']['items'][] = [
            'product_id' => $productId,
            'name' => $product['name'] ?? $product['title'] ?? 'Product',
            'price' => $price,
            'quantity' => $quantity,
            'image' => $imagePath
        ];
    }
    
    // Update cart totals
    $total = 0;
    $count = 0;
    
    foreach ($_SESSION['cart']['items'] as $item) {
        $total += $item['price'] * $item['quantity'];
        $count += $item['quantity'];
    }
    
    $_SESSION['cart']['total'] = $total;
    $_SESSION['cart']['count'] = $count;
    
    // Return success response
    $response = [
        'status' => 'success',
        'message' => 'Product added to cart successfully',
        'cart_count' => $count,
        'cart_total' => $total,
        'cart_items' => array_values($_SESSION['cart']['items'])
    ];
    
} catch (Exception $e) {
    // Log the error
    error_log('Add to cart error: ' . $e->getMessage());
    
    // Set error response
    $response['message'] = $e->getMessage();
    
    // Include current cart data if available
    if (isset($_SESSION['cart'])) {
        $response['cart_count'] = $_SESSION['cart']['count'] ?? 0;
        $response['cart_total'] = $_SESSION['cart']['total'] ?? 0;
        $response['cart_items'] = array_values($_SESSION['cart']['items'] ?? []);
    }
}

// Output JSON response
echo json_encode($response);
exit;
