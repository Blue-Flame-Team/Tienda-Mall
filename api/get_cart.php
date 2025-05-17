<?php
/**
 * Cart API Endpoint - Get Cart
 * Standalone endpoint that returns the cart contents as JSON
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

// Initialize cart if needed
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [
        'items' => [],
        'total' => 0,
        'count' => 0
    ];
}

// Prepare the response
$response = [
    'status' => 'success',
    'message' => 'Cart retrieved successfully',
    'cart_count' => $_SESSION['cart']['count'] ?? 0,
    'cart_total' => $_SESSION['cart']['total'] ?? 0,
    'cart_items' => array_values($_SESSION['cart']['items'] ?? [])
];

// Output JSON response
echo json_encode($response);
exit;
