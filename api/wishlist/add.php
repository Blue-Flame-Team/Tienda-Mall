<?php
/**
 * API endpoint to add a product to the user's wishlist
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
    $response['message'] = 'You must be logged in to add items to your wishlist';
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

// Check if product exists
$stmt = $db->query("SELECT product_id, title FROM product WHERE product_id = ?", [$productId]);
$product = $stmt->fetch();

if (!$product) {
    $response['message'] = 'Product not found';
    echo json_encode($response);
    exit;
}

// Check if product is already in wishlist
$stmt = $db->query("SELECT * FROM wishlist WHERE user_id = ? AND product_id = ?", [$userId, $productId]);
if ($stmt->rowCount() > 0) {
    $response['status'] = 'success';
    $response['message'] = 'Product is already in your wishlist';
    echo json_encode($response);
    exit;
}

// Add product to wishlist
try {
    $stmt = $db->query(
        "INSERT INTO wishlist (user_id, product_id, added_date) VALUES (?, ?, NOW())",
        [$userId, $productId]
    );
    
    if ($stmt->rowCount() > 0) {
        $response['status'] = 'success';
        $response['message'] = 'Product added to wishlist successfully';
        $response['data'] = [
            'product_id' => $productId,
            'product_title' => $product['title']
        ];
    } else {
        $response['message'] = 'Failed to add product to wishlist';
    }
} catch (PDOException $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
