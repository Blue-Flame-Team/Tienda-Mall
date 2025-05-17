<?php
/**
 * Get Cart API Endpoint
 * This file handles retrieving the current cart contents via AJAX
 */

// Set headers for JSON response
header('Content-Type: application/json');

// Include necessary files
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize response
$response = [
    'status' => 'success',
    'message' => 'Cart retrieved successfully',
    'data' => []
];

// Initialize empty cart if it doesn't exist
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Calculate cart totals
$totalItems = 0;
$subtotal = 0;

foreach ($_SESSION['cart'] as $item) {
    $totalItems += $item['quantity'];
    $subtotal += $item['price'] * $item['quantity'];
}

// Return cart data
$response['data'] = [
    'cart_items' => $_SESSION['cart'],
    'item_count' => $totalItems,
    'subtotal' => $subtotal,
    'formatted_subtotal' => '$' . number_format($subtotal, 2)
];

echo json_encode($response);
exit;
?>
