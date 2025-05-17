<?php
/**
 * API endpoint to delete a shipping address
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
    $response['message'] = 'You must be logged in to manage addresses';
    echo json_encode($response);
    exit;
}

// Validate address ID
$addressId = isset($postData['address_id']) ? (int)$postData['address_id'] : 0;

if ($addressId <= 0) {
    $response['message'] = 'Invalid address ID';
    echo json_encode($response);
    exit;
}

// Get user ID from session
$userId = $_SESSION['user_id'];

// Initialize database
$db = Database::getInstance();

// Check if address belongs to the user
$stmt = $db->query("SELECT * FROM shipping_addresses WHERE address_id = ? AND user_id = ?", [$addressId, $userId]);
$address = $stmt->fetch();

if (!$address) {
    $response['message'] = 'Address not found or does not belong to you';
    echo json_encode($response);
    exit;
}

// Delete the address
try {
    $stmt = $db->query("DELETE FROM shipping_addresses WHERE address_id = ? AND user_id = ?", [$addressId, $userId]);
    
    if ($stmt->rowCount() > 0) {
        // If deleted address was the default, set a new default if other addresses exist
        if ($address['is_default']) {
            $stmt = $db->query("SELECT address_id FROM shipping_addresses WHERE user_id = ? ORDER BY address_id ASC LIMIT 1", [$userId]);
            $newDefault = $stmt->fetch();
            
            if ($newDefault) {
                $db->query("UPDATE shipping_addresses SET is_default = 1 WHERE address_id = ?", [$newDefault['address_id']]);
            }
        }
        
        $response['status'] = 'success';
        $response['message'] = 'Address has been deleted successfully';
    } else {
        $response['message'] = 'Failed to delete address';
    }
} catch (PDOException $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
