<?php
/**
 * Products API
 * Handles product-related API requests
 */

$productObj = new Product();

switch ($method) {
    case 'GET':
        // Get all products or search with filters
        if (!$id) {
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 12;
            $offset = ($page - 1) * $limit;
            
            // Build filters from query parameters
            $filters = [];
            
            if (isset($_GET['category_id'])) {
                $filters['category_id'] = (int)$_GET['category_id'];
            }
            
            if (isset($_GET['min_price'])) {
                $filters['min_price'] = (float)$_GET['min_price'];
            }
            
            if (isset($_GET['max_price'])) {
                $filters['max_price'] = (float)$_GET['max_price'];
            }
            
            if (isset($_GET['search']) && !empty($_GET['search'])) {
                $filters['search'] = sanitize($_GET['search']);
            }
            
            if (isset($_GET['in_stock']) && $_GET['in_stock'] === 'true') {
                $filters['in_stock'] = true;
            }
            
            if (isset($_GET['featured']) && $_GET['featured'] === 'true') {
                $filters['featured'] = true;
            }
            
            if (isset($_GET['sort']) && !empty($_GET['sort'])) {
                $filters['sort'] = sanitize($_GET['sort']);
            }
            
            $products = $productObj->getProducts($limit, $offset, $filters);
            $total = $productObj->getProductCount($filters);
            
            $response = [
                'status' => 'success',
                'message' => 'Products retrieved successfully',
                'data' => [
                    'products' => $products,
                    'pagination' => [
                        'total' => $total,
                        'page' => $page,
                        'limit' => $limit,
                        'total_pages' => ceil($total / $limit)
                    ]
                ]
            ];
        } else {
            // Get specific product by ID
            $product = $productObj->getProductById($id);
            
            if ($product) {
                // If action is 'reviews', get product reviews
                if ($action === 'reviews') {
                    $reviews = getProductReviews($id);
                    $response = [
                        'status' => 'success',
                        'message' => 'Reviews retrieved successfully',
                        'data' => $reviews
                    ];
                } else {
                    $response = [
                        'status' => 'success',
                        'message' => 'Product retrieved successfully',
                        'data' => $product
                    ];
                }
            } else {
                http_response_code(404);
                $response = [
                    'status' => 'error',
                    'message' => 'Product not found',
                    'data' => null
                ];
            }
        }
        break;
    
    case 'POST':
        // Admin only: Create new product
        if (!isAdminLoggedIn()) {
            http_response_code(401);
            $response = [
                'status' => 'error',
                'message' => 'Unauthorized',
                'data' => null
            ];
            break;
        }
        
        // Validate required fields
        if (empty($data['sku']) || empty($data['title']) || empty($data['price'])) {
            http_response_code(400);
            $response = [
                'status' => 'error',
                'message' => 'Missing required fields',
                'data' => null
            ];
            break;
        }
        
        $productId = $productObj->addProduct($data);
        
        if ($productId) {
            http_response_code(201);
            $response = [
                'status' => 'success',
                'message' => 'Product created successfully',
                'data' => [
                    'product_id' => $productId
                ]
            ];
        } else {
            http_response_code(500);
            $response = [
                'status' => 'error',
                'message' => 'Failed to create product',
                'data' => null
            ];
        }
        break;
    
    case 'PUT':
        // Admin only: Update product
        if (!isAdminLoggedIn()) {
            http_response_code(401);
            $response = [
                'status' => 'error',
                'message' => 'Unauthorized',
                'data' => null
            ];
            break;
        }
        
        if (!$id) {
            http_response_code(400);
            $response = [
                'status' => 'error',
                'message' => 'Product ID is required',
                'data' => null
            ];
            break;
        }
        
        $success = $productObj->updateProduct($id, $data);
        
        if ($success) {
            $response = [
                'status' => 'success',
                'message' => 'Product updated successfully',
                'data' => null
            ];
        } else {
            http_response_code(500);
            $response = [
                'status' => 'error',
                'message' => 'Failed to update product',
                'data' => null
            ];
        }
        break;
    
    case 'DELETE':
        // Admin only: Delete product
        if (!isAdminLoggedIn()) {
            http_response_code(401);
            $response = [
                'status' => 'error',
                'message' => 'Unauthorized',
                'data' => null
            ];
            break;
        }
        
        if (!$id) {
            http_response_code(400);
            $response = [
                'status' => 'error',
                'message' => 'Product ID is required',
                'data' => null
            ];
            break;
        }
        
        $success = $productObj->deleteProduct($id);
        
        if ($success) {
            $response = [
                'status' => 'success',
                'message' => 'Product deleted successfully',
                'data' => null
            ];
        } else {
            http_response_code(500);
            $response = [
                'status' => 'error',
                'message' => 'Failed to delete product',
                'data' => null
            ];
        }
        break;
    
    default:
        http_response_code(405);
        $response = [
            'status' => 'error',
            'message' => 'Method not allowed',
            'data' => null
        ];
}
?>
