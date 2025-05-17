<?php
/**
 * API endpoint to cancel an order
 */

// Include necessary files
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';
require_once '../../includes/Order.php';

// Initialize response
$response = [
    'status' => 'error',
    'message' => 'An error occurred',
    'data' => []
];

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method';
    echo json_encode($response);
    exit;
}

// Get POST data
$postData = json_decode(file_get_contents('php://input'), true);

if (!$postData) {
    $response['message'] = 'Invalid data format';
    echo json_encode($response);
    exit;
}

// Check if user is logged in
if (!isLoggedIn()) {
    $response['message'] = 'You must be logged in to manage orders';
    echo json_encode($response);
    exit;
}

// Validate order ID
$orderId = isset($postData['order_id']) ? (int)$postData['order_id'] : 0;

if ($orderId <= 0) {
    $response['message'] = 'Invalid order ID';
    echo json_encode($response);
    exit;
}

// Get user ID from session
$userId = $_SESSION['user_id'];

// Initialize database and Order object
$db = Database::getInstance();
$orderObj = new Order();

// Check if order belongs to the user
$order = $orderObj->getOrderById($orderId);

if (!$order || $order['user_id'] != $userId) {
    $response['message'] = 'Order not found or does not belong to you';
    echo json_encode($response);
    exit;
}

// Check if order can be cancelled (not completed or already cancelled)
if ($order['status'] === 'Completed' || $order['status'] === 'Cancelled') {
    $response['message'] = 'This order cannot be cancelled as it is already ' . strtolower($order['status']);
    echo json_encode($response);
    exit;
}

// Cancel the order
try {
    // Update order status
    $stmt = $db->query(
        "UPDATE orders SET status = 'Cancelled', updated_at = NOW() WHERE order_id = ?",
        [$orderId]
    );
    
    if ($stmt->rowCount() > 0) {
        // Add status update to order_status table
        $db->query(
            "INSERT INTO order_status (order_id, status, notes, status_date) 
             VALUES (?, 'Cancelled', 'Order cancelled by customer', NOW())",
            [$orderId]
        );
        
        // Return items to inventory if necessary
        $orderItems = $orderObj->getOrderItems($orderId);
        
        foreach ($orderItems as $item) {
            // Increase product quantity
            $db->query(
                "UPDATE product SET quantity = quantity + ? WHERE product_id = ?",
                [$item['quantity'], $item['product_id']]
            );
        }
        
        $response['status'] = 'success';
        $response['message'] = 'Your order has been cancelled successfully';
    } else {
        $response['message'] = 'Failed to cancel order';
    }
} catch (PDOException $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
