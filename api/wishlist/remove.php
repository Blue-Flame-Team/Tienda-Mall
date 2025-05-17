<?php
/**
 * API endpoint to remove a product from the user's wishlist
 */

// Include necessary files
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/db.php';

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
    $response['message'] = 'You must be logged in to manage your wishlist';
    echo json_encode($response);
    exit;
}

// Validate product ID
$productId = isset($postData['product_id']) ? (int)$postData['product_id'] : 0;

if ($productId <= 0) {
    $response['message'] = 'Invalid product ID';
    echo json_encode($response);
    exit;
}

// Get user ID from session
$userId = $_SESSION['user_id'];

// Initialize database
$db = Database::getInstance();

// Remove product from wishlist
try {
    $stmt = $db->query(
        "DELETE FROM wishlist WHERE user_id = ? AND product_id = ?",
        [$userId, $productId]
    );
    
    if ($stmt->rowCount() > 0) {
        $response['status'] = 'success';
        $response['message'] = 'Product removed from wishlist successfully';
        $response['data'] = [
            'product_id' => $productId
        ];
    } else {
        $response['message'] = 'Product was not in your wishlist';
    }
} catch (PDOException $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
