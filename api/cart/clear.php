<?php
/**
 * Clear Cart API Endpoint
 * This file handles clearing the cart via AJAX
 */

// Set headers for JSON response
header('Content-Type: application/json');

// Include necessary files
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/Cart.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize Cart object
$cartObj = new Cart();

// Clear the cart
$cartObj->clearCart();

// Return success response
$response = [
    'status' => 'success',
    'message' => 'Cart cleared successfully',
    'data' => [
        'item_count' => 0,
        'cart_items' => []
    ]
];

echo json_encode($response);
exit;
?>
