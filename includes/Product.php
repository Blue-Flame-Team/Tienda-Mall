<?php
/**
 * Product Class
 * Handles product-related operations
 */

require_once 'db.php';
require_once 'functions.php';

class Product {
    private $db;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get all products
     * @param int $limit Number of products to get
     * @param int $offset Offset for pagination
     * @param array $filters Filters to apply (category, price range, etc.)
     * @return array Products
     */
    public function getProducts($limit = 12, $offset = 0, $filters = []) {
        $sql = "SELECT p.*, pi.image_url, i.quantity 
                FROM product p 
                LEFT JOIN (
                    SELECT product_id, image_url 
                    FROM product_image 
                    WHERE is_primary = 'YES'
                ) pi ON p.product_id = pi.product_id 
                LEFT JOIN inventory i ON p.product_id = i.product_id 
                WHERE p.is_active = 'Y' ";
        
        $params = [];
        
        // Apply filters
        if (!empty($filters)) {
            // Category filter
            if (isset($filters['category_id'])) {
                $sql .= "AND p.product_id IN (
                    SELECT product_id FROM product_categories WHERE category_id = ?
                ) ";
                $params[] = $filters['category_id'];
            }
            
            // Price range filter
            if (isset($filters['min_price'])) {
                $sql .= "AND p.price >= ? ";
                $params[] = $filters['min_price'];
            }
            
            if (isset($filters['max_price'])) {
                $sql .= "AND p.price <= ? ";
                $params[] = $filters['max_price'];
            }
            
            // Search term filter
            if (isset($filters['search'])) {
                $searchTerm = "%" . $filters['search'] . "%";
                $sql .= "AND (p.title LIKE ? OR p.description LIKE ? OR p.meta_keywords LIKE ?) ";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            // In stock filter
            if (isset($filters['in_stock']) && $filters['in_stock']) {
                $sql .= "AND i.quantity > i.reserved_quantity ";
            }
            
            // Featured filter
            if (isset($filters['featured']) && $filters['featured']) {
                $sql .= "AND p.featured = 1 ";
            }
        }
        
        // Sort order
        $sortColumn = 'p.created_at';
        $sortDirection = 'DESC';
        
        if (isset($filters['sort'])) {
            switch ($filters['sort']) {
                case 'price_asc':
                    $sortColumn = 'p.price';
                    $sortDirection = 'ASC';
                    break;
                case 'price_desc':
                    $sortColumn = 'p.price';
                    $sortDirection = 'DESC';
                    break;
                case 'name_asc':
                    $sortColumn = 'p.title';
                    $sortDirection = 'ASC';
                    break;
                case 'name_desc':
                    $sortColumn = 'p.title';
                    $sortDirection = 'DESC';
                    break;
                case 'newest':
                    $sortColumn = 'p.created_at';
                    $sortDirection = 'DESC';
                    break;
                case 'oldest':
                    $sortColumn = 'p.created_at';
                    $sortDirection = 'ASC';
                    break;
            }
        }
        
        $sql .= "ORDER BY $sortColumn $sortDirection LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->db->query($sql, $params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get product by ID
     * @param int $productId Product ID
     * @return array|bool Product data if found, false otherwise
     */
    public function getProductById($productId) {
        $sql = "SELECT p.*, i.quantity 
                FROM product p 
                LEFT JOIN inventory i ON p.product_id = i.product_id 
                WHERE p.product_id = ? AND p.is_active = 'Y'";
        
        $stmt = $this->db->query($sql, [$productId]);
        
        if ($stmt->rowCount() > 0) {
            $product = $stmt->fetch();
            
            // Get product images
            $product['images'] = $this->getProductImages($productId);
            
            // Get product categories
            $product['categories'] = $this->getProductCategories($productId);
            
            return $product;
        }
        
        return false;
    }
    
    /**
     * Get product images
     * @param int $productId Product ID
     * @return array Product images
     */
    public function getProductImages($productId) {
        $sql = "SELECT * FROM product_image WHERE product_id = ? ORDER BY sort_order, CASE WHEN is_primary = 'YES' THEN 0 ELSE 1 END";
        $stmt = $this->db->query($sql, [$productId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get product categories
     * @param int $productId Product ID
     * @return array Product categories
     */
    public function getProductCategories($productId) {
        $sql = "SELECT c.* 
                FROM category c 
                JOIN categorized_in ci ON c.category_id = ci.category_id 
                WHERE ci.product_id = ? AND c.is_active = 'YES'";
        
        $stmt = $this->db->query($sql, [$productId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Add new product
     * @param array $productData Product data
     * @return bool|int Product ID if successful, false otherwise
     */
    public function addProduct($productData) {
        // Start transaction
        $this->db->getConnection()->beginTransaction();
        
        try {
            // Insert product
            $sql = "INSERT INTO products (
                        sku, title, description, price, old_price, cost_price, 
                        featured, is_active, meta_title, meta_description, 
                        meta_keywords, weight, dimensions, created_at, updated_at
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()
                    )";
            
            $params = [
                $productData['sku'],
                $productData['title'],
                $productData['description'] ?? null,
                $productData['price'],
                $productData['old_price'] ?? null,
                $productData['cost_price'] ?? null,
                $productData['featured'] ?? 0,
                $productData['is_active'] ?? 1,
                $productData['meta_title'] ?? null,
                $productData['meta_description'] ?? null,
                $productData['meta_keywords'] ?? null,
                $productData['weight'] ?? null,
                $productData['dimensions'] ?? null
            ];
            
            $stmt = $this->db->query($sql, $params);
            
            if ($stmt->rowCount() > 0) {
                $productId = $this->db->getConnection()->lastInsertId();
                
                // Add inventory record
                $sql = "INSERT INTO inventory (
                            product_id, quantity, reserved_quantity, 
                            warehouse_location, low_stock_threshold, last_stock_update
                        ) VALUES (
                            ?, ?, ?, ?, ?, NOW()
                        )";
                
                $params = [
                    $productId,
                    $productData['quantity'] ?? 0,
                    0, // Default reserved_quantity
                    $productData['warehouse_location'] ?? null,
                    $productData['low_stock_threshold'] ?? 5
                ];
                
                $this->db->query($sql, $params);
                
                // Add product categories
                if (isset($productData['categories']) && !empty($productData['categories'])) {
                    foreach ($productData['categories'] as $categoryId) {
                        $sql = "INSERT INTO product_categories (product_id, category_id) VALUES (?, ?)";
                        $this->db->query($sql, [$productId, $categoryId]);
                    }
                }
                
                // Add product images
                if (isset($productData['images']) && !empty($productData['images'])) {
                    $sortOrder = 0;
                    foreach ($productData['images'] as $image) {
                        $sql = "INSERT INTO product_images (
                                    product_id, image_url, sort_order, is_primary, created_at
                                ) VALUES (
                                    ?, ?, ?, ?, NOW()
                                )";
                        
                        $isPrimary = ($sortOrder === 0) ? 1 : 0;
                        $this->db->query($sql, [$productId, $image, $sortOrder, $isPrimary]);
                        $sortOrder++;
                    }
                }
                
                // Commit transaction
                $this->db->getConnection()->commit();
                
                return $productId;
            }
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->db->getConnection()->rollBack();
            error_log("Error adding product: " . $e->getMessage());
        }
        
        return false;
    }
    
    /**
     * Update product
     * @param int $productId Product ID
     * @param array $productData Product data
     * @return bool True if successful, false otherwise
     */
    public function updateProduct($productId, $productData) {
        // Start transaction
        $this->db->getConnection()->beginTransaction();
        
        try {
            // Update product
            $sql = "UPDATE products SET ";
            $params = [];
            
            foreach ($productData as $key => $value) {
                // Skip product_id, images, categories fields
                if (in_array($key, ['product_id', 'images', 'categories', 'quantity', 'warehouse_location', 'low_stock_threshold'])) {
                    continue;
                }
                
                $sql .= "$key = ?, ";
                $params[] = $value;
            }
            
            $sql .= "updated_at = NOW() WHERE product_id = ?";
            $params[] = $productId;
            
            $stmt = $this->db->query($sql, $params);
            
            // Update inventory if provided
            if (isset($productData['quantity']) || isset($productData['warehouse_location']) || isset($productData['low_stock_threshold'])) {
                $sql = "UPDATE inventory SET ";
                $inventoryParams = [];
                
                if (isset($productData['quantity'])) {
                    $sql .= "quantity = ?, ";
                    $inventoryParams[] = $productData['quantity'];
                }
                
                if (isset($productData['warehouse_location'])) {
                    $sql .= "warehouse_location = ?, ";
                    $inventoryParams[] = $productData['warehouse_location'];
                }
                
                if (isset($productData['low_stock_threshold'])) {
                    $sql .= "low_stock_threshold = ?, ";
                    $inventoryParams[] = $productData['low_stock_threshold'];
                }
                
                $sql .= "last_stock_update = NOW() WHERE product_id = ?";
                $inventoryParams[] = $productId;
                
                $this->db->query($sql, $inventoryParams);
            }
            
            // Update product categories if provided
            if (isset($productData['categories'])) {
                // Remove existing categories
                $this->db->query("DELETE FROM product_categories WHERE product_id = ?", [$productId]);
                
                // Add new categories
                foreach ($productData['categories'] as $categoryId) {
                    $sql = "INSERT INTO product_categories (product_id, category_id) VALUES (?, ?)";
                    $this->db->query($sql, [$productId, $categoryId]);
                }
            }
            
            // Update product images if provided
            if (isset($productData['images'])) {
                // Handle images separately in a more sophisticated way
                // For simplicity, this implementation assumes $productData['images'] is
                // an array of complete new images, which will replace all existing ones
                
                // Remove existing images
                $this->db->query("DELETE FROM product_images WHERE product_id = ?", [$productId]);
                
                // Add new images
                $sortOrder = 0;
                foreach ($productData['images'] as $image) {
                    $sql = "INSERT INTO product_images (
                                product_id, image_url, sort_order, is_primary, created_at
                            ) VALUES (
                                ?, ?, ?, ?, NOW()
                            )";
                    
                    $isPrimary = ($sortOrder === 0) ? 1 : 0;
                    $this->db->query($sql, [$productId, $image, $sortOrder, $isPrimary]);
                    $sortOrder++;
                }
            }
            
            // Commit transaction
            $this->db->getConnection()->commit();
            
            return true;
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->db->getConnection()->rollBack();
            error_log("Error updating product: " . $e->getMessage());
        }
        
        return false;
    }
    
    /**
     * Delete product
     * @param int $productId Product ID
     * @return bool True if successful, false otherwise
     */
    public function deleteProduct($productId) {
        // Check if there are any orders for this product
        $sql = "SELECT COUNT(*) as count FROM order_items WHERE product_id = ?";
        $stmt = $this->db->query($sql, [$productId]);
        $result = $stmt->fetch();
        
        // If there are orders, deactivate the product instead of deleting
        if ($result['count'] > 0) {
            $sql = "UPDATE products SET is_active = 0 WHERE product_id = ?";
            $stmt = $this->db->query($sql, [$productId]);
            return $stmt->rowCount() > 0;
        }
        
        // If no orders, delete the product and related data
        $this->db->getConnection()->beginTransaction();
        
        try {
            // Delete product images
            $this->db->query("DELETE FROM product_images WHERE product_id = ?", [$productId]);
            
            // Delete product categories
            $this->db->query("DELETE FROM product_categories WHERE product_id = ?", [$productId]);
            
            // Delete inventory
            $this->db->query("DELETE FROM inventory WHERE product_id = ?", [$productId]);
            
            // Delete reviews
            $this->db->query("DELETE FROM reviews WHERE product_id = ?", [$productId]);
            
            // Delete product
            $stmt = $this->db->query("DELETE FROM products WHERE product_id = ?", [$productId]);
            
            $this->db->getConnection()->commit();
            
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            $this->db->getConnection()->rollBack();
            error_log("Error deleting product: " . $e->getMessage());
        }
        
        return false;
    }
    
    /**
     * Get product count
     * @param array $filters Filters to apply
     * @return int Product count
     */
    public function getProductCount($filters = []) {
        $sql = "SELECT COUNT(*) as count 
                FROM products p 
                LEFT JOIN inventory i ON p.product_id = i.product_id 
                WHERE p.is_active = 1 ";
        
        $params = [];
        
        // Apply filters
        if (!empty($filters)) {
            // Category filter
            if (isset($filters['category_id'])) {
                $sql .= "AND p.product_id IN (
                    SELECT product_id FROM product_categories WHERE category_id = ?
                ) ";
                $params[] = $filters['category_id'];
            }
            
            // Price range filter
            if (isset($filters['min_price'])) {
                $sql .= "AND p.price >= ? ";
                $params[] = $filters['min_price'];
            }
            
            if (isset($filters['max_price'])) {
                $sql .= "AND p.price <= ? ";
                $params[] = $filters['max_price'];
            }
            
            // Search term filter
            if (isset($filters['search'])) {
                $searchTerm = "%" . $filters['search'] . "%";
                $sql .= "AND (p.title LIKE ? OR p.description LIKE ? OR p.meta_keywords LIKE ?) ";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            // In stock filter
            if (isset($filters['in_stock']) && $filters['in_stock']) {
                $sql .= "AND i.quantity > i.reserved_quantity ";
            }
            
            // Featured filter
            if (isset($filters['featured']) && $filters['featured']) {
                $sql .= "AND p.featured = 1 ";
            }
        }
        
        $stmt = $this->db->query($sql, $params);
        $result = $stmt->fetch();
        
        return $result['count'];
    }
}
?>
