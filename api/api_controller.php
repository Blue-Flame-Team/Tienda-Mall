<?php
/**
 * API Controller for Tienda Mall
 * This file serves as a central handler for API requests to ensure proper routing
 */

// Set content type to JSON
header('Content-Type: application/json');

// Allow cross-origin requests for testing
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

// For OPTIONS requests (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize response array
$response = [
    'success' => false,
    'message' => 'Unknown error occurred',
    'request_info' => [
        'method' => $_SERVER['REQUEST_METHOD'],
        'uri' => $_SERVER['REQUEST_URI'],
        'script' => $_SERVER['SCRIPT_NAME']
    ]
];

try {
    // Include configuration files
    require_once '../includes/config.php';
    require_once '../includes/cart_helper.php';
    
    // Log API request for debugging
    error_log('API Request: ' . $_SERVER['REQUEST_METHOD'] . ' ' . $_SERVER['REQUEST_URI']);
    
    // Extract API endpoint from request URI
    $uri = $_SERVER['REQUEST_URI'];
    $apiBase = '/Tienda/api/';
    
    // Handle endpoints
    if (strpos($uri, $apiBase . 'get_cart.php') !== false) {
        // Get cart contents
        initialize_cart();
        
        $response = [
            'success' => true,
            'message' => 'Cart retrieved successfully',
            'cart' => [
                'items' => is_array($_SESSION['cart']['items'] ?? null) ? array_values($_SESSION['cart']['items'] ?? []) : [],
                'total' => $_SESSION['cart']['total'] ?? 0,
                'count' => $_SESSION['cart']['count'] ?? 0
            ]
        ];
    } elseif (strpos($uri, $apiBase . 'add_to_cart.php') !== false) {
        // Process add to cart request
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            throw new Exception('Method not allowed. POST required.');
        }
        
        // Get input data
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['product_id'])) {
            throw new Exception('Invalid data: product_id is required');
        }
        
        // Extract data
        $product_id = intval($input['product_id']);
        $quantity = isset($input['quantity']) ? intval($input['quantity']) : 1;
        
        // Add to cart using cart_helper function
        $result = add_to_cart($product_id, $quantity);
        
        // Create response
        $response = [
            'success' => $result['success'],
            'message' => $result['message'],
            'cart' => [
                'items' => is_array($_SESSION['cart']['items'] ?? null) ? array_values($_SESSION['cart']['items'] ?? []) : [],
                'total' => $_SESSION['cart']['total'] ?? 0,
                'count' => $_SESSION['cart']['count'] ?? 0
            ]
        ];
    } else {
        $response['message'] = 'Unknown API endpoint';
    }
} catch (Exception $e) {
    // Log error
    error_log('API Error: ' . $e->getMessage());
    
    // Return error response
    $response = [
        'success' => false,
        'message' => $e->getMessage(),
        'cart' => [
            'items' => is_array($_SESSION['cart']['items'] ?? null) ? array_values($_SESSION['cart']['items'] ?? []) : [],
            'total' => $_SESSION['cart']['total'] ?? 0,
            'count' => $_SESSION['cart']['count'] ?? 0
        ]
    ];
}

// Send response
echo json_encode($response);
exit;
