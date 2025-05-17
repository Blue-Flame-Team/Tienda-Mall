<?php
/**
 * API Entry Point
 * 
 * This file handles all API requests and routes them to the appropriate handler
 */

// Include necessary files
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';
require_once '../includes/User.php';
require_once '../includes/Product.php';
require_once '../includes/Cart.php';
require_once '../includes/Order.php';

// Set content type to JSON
header('Content-Type: application/json');

// Allow CORS for all origins (in production, you should restrict this)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get request path
$requestUri = $_SERVER['REQUEST_URI'];
$baseDir = '/api';
$endpoint = str_replace($baseDir, '', $requestUri);
$endpoint = trim($endpoint, '/');

// Parse URL parts
$urlParts = explode('/', $endpoint);
$resource = $urlParts[0] ?? '';
$id = $urlParts[1] ?? null;
$action = $urlParts[2] ?? null;

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Get request data
$data = json_decode(file_get_contents('php://input'), true);
if ($data === null) {
    $data = $_POST;
}

// Initialize response
$response = [
    'status' => 'error',
    'message' => 'Invalid request',
    'data' => null
];

// Route the request
try {
    switch ($resource) {
        case 'products':
            include 'products.php';
            break;
            
        case 'users':
            include 'users.php';
            break;
            
        case 'auth':
            include 'auth.php';
            break;
            
        case 'cart':
            include 'cart.php';
            break;
            
        case 'orders':
            include 'orders.php';
            break;
            
        case 'categories':
            include 'categories.php';
            break;
            
        default:
            http_response_code(404);
            $response['message'] = 'Resource not found';
    }
} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = 'Server error: ' . $e->getMessage();
    
    // Log the error but don't expose details in production
    error_log('API Error: ' . $e->getMessage());
}

// Output the response
echo json_encode($response);
?>
