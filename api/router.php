<?php
/**
 * API Router
 * Routes incoming requests to the appropriate controller
 */

// Include necessary files
require_once '../includes/config.php';
require_once '../includes/Database.php';
require_once '../includes/Controller.php';

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
$id = isset($urlParts[1]) && is_numeric($urlParts[1]) ? (int)$urlParts[1] : null;
$action = isset($urlParts[1]) && !is_numeric($urlParts[1]) ? $urlParts[1] : (isset($urlParts[2]) ? $urlParts[2] : null);

// Initialize response
$response = [
    'status' => 'error',
    'message' => 'Invalid request',
    'data' => null
];

// Route the request to the appropriate controller
try {
    switch ($resource) {
        case 'products':
            require_once 'controllers/ProductController.php';
            $controller = new ProductController();
            $controller->handleRequest($action, $id);
            break;
            
        case 'categories':
            require_once 'controllers/CategoryController.php';
            $controller = new CategoryController();
            $controller->handleRequest($action, $id);
            break;
            
        case 'users':
            require_once 'controllers/UserController.php';
            $controller = new UserController();
            $controller->handleRequest($action, $id);
            break;
            
        case 'auth':
            require_once 'controllers/AuthController.php';
            $controller = new AuthController();
            $controller->handleRequest($action, $id);
            break;
            
        case 'orders':
            require_once 'controllers/OrderController.php';
            $controller = new OrderController();
            $controller->handleRequest($action, $id);
            break;
            
        case 'cart':
            require_once 'controllers/CartController.php';
            $controller = new CartController();
            $controller->handleRequest($action, $id);
            break;
            
        case 'wishlist':
            require_once 'controllers/WishlistController.php';
            $controller = new WishlistController();
            $controller->handleRequest($action, $id);
            break;
            
        case 'addresses':
            require_once 'controllers/AddressController.php';
            $controller = new AddressController();
            $controller->handleRequest($action, $id);
            break;
            
        case 'reviews':
            require_once 'controllers/ReviewController.php';
            $controller = new ReviewController();
            $controller->handleRequest($action, $id);
            break;
            
        default:
            // 404 resource not found
            http_response_code(404);
            echo json_encode([
                'status' => 'error',
                'message' => 'Resource not found',
                'data' => null
            ]);
            exit;
    }
} catch (Exception $e) {
    // Log the error
    error_log('API Error: ' . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => DEV_MODE ? $e->getMessage() : 'Internal server error',
        'data' => DEV_MODE ? [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ] : null
    ]);
}
?>
