<?php
/**
 * Product Controller
 * Handles all product-related API endpoints
 */

require_once '../includes/Controller.php';
require_once '../includes/Product.php';

class ProductController extends Controller {
    private $product;
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->product = new Product();
    }
    
    /**
     * Route the request to the appropriate handler
     */
    public function handleRequest($action = '', $id = null) {
        switch ($this->method) {
            case 'GET':
                if ($id) {
                    $this->getProduct($id);
                } else if ($action === 'featured') {
                    $this->getFeaturedProducts();
                } else if ($action === 'categories') {
                    $this->getProductsByCategory();
                } else if ($action === 'search') {
                    $this->searchProducts();
                } else {
                    $this->getAllProducts();
                }
                break;
                
            case 'POST':
                $this->requireAuth();
                $this->createProduct();
                break;
                
            case 'PUT':
                $this->requireAuth();
                if ($id) {
                    $this->updateProduct($id);
                } else {
                    $this->respondError('Product ID is required');
                }
                break;
                
            case 'DELETE':
                $this->requireAuth();
                if ($id) {
                    $this->deleteProduct($id);
                } else {
                    $this->respondError('Product ID is required');
                }
                break;
                
            default:
                $this->respondError('Method not allowed', null, 405);
        }
    }
    
    /**
     * Get all products
     */
    private function getAllProducts() {
        $page = (int)$this->getParam('page', 1);
        $perPage = (int)$this->getParam('per_page', 12);
        
        // Prepare filters
        $filters = [];
        
        if ($this->hasParam('category_id')) {
            $filters['category_id'] = (int)$this->getParam('category_id');
        }
        
        if ($this->hasParam('min_price')) {
            $filters['min_price'] = (float)$this->getParam('min_price');
        }
        
        if ($this->hasParam('max_price')) {
            $filters['max_price'] = (float)$this->getParam('max_price');
        }
        
        if ($this->hasParam('search')) {
            $filters['search'] = $this->getParam('search');
        }
        
        if ($this->hasParam('sort')) {
            $filters['sort'] = $this->getParam('sort');
        }
        
        if ($this->hasParam('in_stock')) {
            $filters['in_stock'] = (bool)$this->getParam('in_stock');
        }
        
        $offset = ($page - 1) * $perPage;
        $products = $this->product->getProducts($perPage, $offset, $filters);
        $totalCount = $this->product->getProductCount($filters);
        $totalPages = ceil($totalCount / $perPage);
        
        $this->respondSuccess([
            'products' => $products,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_items' => $totalCount,
                'total_pages' => $totalPages,
                'has_more' => $page < $totalPages
            ]
        ]);
    }
    
    /**
     * Get product by ID
     */
    private function getProduct($id) {
        $product = $this->product->getProductById($id);
        
        if ($product) {
            $this->respondSuccess($product);
        } else {
            $this->respondNotFound('Product not found');
        }
    }
    
    /**
     * Get featured products
     */
    private function getFeaturedProducts() {
        $limit = (int)$this->getParam('limit', 6);
        $products = $this->product->getFeaturedProducts($limit);
        
        $this->respondSuccess($products);
    }
    
    /**
     * Get products by category
     */
    private function getProductsByCategory() {
        $categoryId = (int)$this->getParam('category_id');
        
        if (!$categoryId) {
            $this->respondError('Category ID is required');
            return;
        }
        
        $page = (int)$this->getParam('page', 1);
        $perPage = (int)$this->getParam('per_page', 12);
        $offset = ($page - 1) * $perPage;
        
        $products = $this->product->getProductsByCategory($categoryId, $perPage, $offset);
        $totalCount = $this->product->getProductCountByCategory($categoryId);
        $totalPages = ceil($totalCount / $perPage);
        
        $this->respondSuccess([
            'products' => $products,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_items' => $totalCount,
                'total_pages' => $totalPages,
                'has_more' => $page < $totalPages
            ]
        ]);
    }
    
    /**
     * Search products
     */
    private function searchProducts() {
        $query = $this->getParam('q', '');
        
        if (!$query) {
            $this->respondError('Search query is required');
            return;
        }
        
        $page = (int)$this->getParam('page', 1);
        $perPage = (int)$this->getParam('per_page', 12);
        $offset = ($page - 1) * $perPage;
        
        $products = $this->product->searchProducts($query, $perPage, $offset);
        $totalCount = $this->product->getSearchProductCount($query);
        $totalPages = ceil($totalCount / $perPage);
        
        $this->respondSuccess([
            'products' => $products,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_items' => $totalCount,
                'total_pages' => $totalPages,
                'has_more' => $page < $totalPages
            ]
        ]);
    }
    
    /**
     * Create a new product
     */
    private function createProduct() {
        $rules = [
            'title' => 'required|min:3|max:70',
            'sku' => 'required|min:3|max:100',
            'price' => 'required|numeric|min:0',
            'description' => 'max:500',
            'cost_price' => 'numeric|min:0',
            'is_active' => 'in:Y,N',
        ];
        
        $errors = $this->validate($rules);
        
        if (!empty($errors)) {
            $this->respondValidationError($errors);
            return;
        }
        
        $productData = [
            'title' => $this->getParam('title'),
            'sku' => $this->getParam('sku'),
            'description' => $this->getParam('description', ''),
            'price' => (float)$this->getParam('price'),
            'cost_price' => (float)$this->getParam('cost_price', 0),
            'is_active' => $this->getParam('is_active', 'Y'),
            'meta_title' => $this->getParam('meta_title', ''),
            'meta_description' => $this->getParam('meta_description', ''),
            'weight' => (float)$this->getParam('weight', 0),
            'width' => (float)$this->getParam('width', 0),
            'height' => (float)$this->getParam('height', 0)
        ];
        
        $productId = $this->product->addProduct($productData);
        
        if ($productId) {
            // Add product categories if provided
            if ($this->hasParam('categories')) {
                $categories = $this->getParam('categories', []);
                if (is_array($categories)) {
                    foreach ($categories as $categoryId) {
                        $this->product->addProductCategory($productId, $categoryId);
                    }
                }
            }
            
            // Add product inventory if provided
            if ($this->hasParam('quantity')) {
                $inventoryData = [
                    'quantity' => (int)$this->getParam('quantity', 0),
                    'low_stock_threshold' => (int)$this->getParam('low_stock_threshold', 5),
                    'warehous_location' => $this->getParam('warehous_location', '')
                ];
                
                $this->product->updateInventory($productId, $inventoryData);
            }
            
            $this->respondSuccess(
                ['product_id' => $productId],
                'Product created successfully',
                201
            );
        } else {
            $this->respondError('Failed to create product');
        }
    }
    
    /**
     * Update an existing product
     */
    private function updateProduct($id) {
        $product = $this->product->getProductById($id);
        
        if (!$product) {
            $this->respondNotFound('Product not found');
            return;
        }
        
        $rules = [
            'title' => 'min:3|max:70',
            'sku' => 'min:3|max:100',
            'price' => 'numeric|min:0',
            'description' => 'max:500',
            'cost_price' => 'numeric|min:0',
            'is_active' => 'in:Y,N',
        ];
        
        $errors = $this->validate($rules);
        
        if (!empty($errors)) {
            $this->respondValidationError($errors);
            return;
        }
        
        $updateData = [];
        
        if ($this->hasParam('title')) {
            $updateData['title'] = $this->getParam('title');
        }
        
        if ($this->hasParam('sku')) {
            $updateData['sku'] = $this->getParam('sku');
        }
        
        if ($this->hasParam('description')) {
            $updateData['description'] = $this->getParam('description');
        }
        
        if ($this->hasParam('price')) {
            $updateData['price'] = (float)$this->getParam('price');
        }
        
        if ($this->hasParam('cost_price')) {
            $updateData['cost_price'] = (float)$this->getParam('cost_price');
        }
        
        if ($this->hasParam('is_active')) {
            $updateData['is_active'] = $this->getParam('is_active');
        }
        
        if ($this->hasParam('meta_title')) {
            $updateData['meta_title'] = $this->getParam('meta_title');
        }
        
        if ($this->hasParam('meta_description')) {
            $updateData['meta_description'] = $this->getParam('meta_description');
        }
        
        if ($this->hasParam('weight')) {
            $updateData['weight'] = (float)$this->getParam('weight');
        }
        
        if ($this->hasParam('width')) {
            $updateData['width'] = (float)$this->getParam('width');
        }
        
        if ($this->hasParam('height')) {
            $updateData['height'] = (float)$this->getParam('height');
        }
        
        if (empty($updateData)) {
            $this->respondError('No data provided for update');
            return;
        }
        
        $result = $this->product->updateProduct($id, $updateData);
        
        if ($result) {
            // Update product categories if provided
            if ($this->hasParam('categories')) {
                $categories = $this->getParam('categories', []);
                if (is_array($categories)) {
                    $this->product->clearProductCategories($id);
                    foreach ($categories as $categoryId) {
                        $this->product->addProductCategory($id, $categoryId);
                    }
                }
            }
            
            // Update product inventory if provided
            if ($this->hasParam('quantity') || $this->hasParam('low_stock_threshold') || $this->hasParam('warehous_location')) {
                $inventoryData = [];
                
                if ($this->hasParam('quantity')) {
                    $inventoryData['quantity'] = (int)$this->getParam('quantity');
                }
                
                if ($this->hasParam('low_stock_threshold')) {
                    $inventoryData['low_stock_threshold'] = (int)$this->getParam('low_stock_threshold');
                }
                
                if ($this->hasParam('warehous_location')) {
                    $inventoryData['warehous_location'] = $this->getParam('warehous_location');
                }
                
                if (!empty($inventoryData)) {
                    $this->product->updateInventory($id, $inventoryData);
                }
            }
            
            $this->respondSuccess(null, 'Product updated successfully');
        } else {
            $this->respondError('Failed to update product');
        }
    }
    
    /**
     * Delete a product
     */
    private function deleteProduct($id) {
        $product = $this->product->getProductById($id);
        
        if (!$product) {
            $this->respondNotFound('Product not found');
            return;
        }
        
        $result = $this->product->deleteProduct($id);
        
        if ($result) {
            $this->respondSuccess(null, 'Product deleted successfully');
        } else {
            $this->respondError('Failed to delete product');
        }
    }
}
?>
