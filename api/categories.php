<?php
/**
 * Categories API
 * Handles category-related API requests
 */

switch ($method) {
    case 'GET':
        // Get all categories or a specific category
        $db = Database::getInstance();
        
        if (!$id) {
            // Get all categories with optional parent filter
            $parentId = isset($_GET['parent_id']) ? (int)$_GET['parent_id'] : null;
            $activeOnly = isset($_GET['active_only']) && $_GET['active_only'] === 'true';
            
            $sql = "SELECT * FROM categories WHERE 1=1";
            $params = [];
            
            if ($parentId !== null) {
                $sql .= " AND parent_id " . ($parentId === 0 ? "IS NULL" : "= ?");
                if ($parentId !== 0) {
                    $params[] = $parentId;
                }
            }
            
            if ($activeOnly) {
                $sql .= " AND is_active = 1";
            }
            
            $sql .= " ORDER BY name";
            
            $stmt = $db->query($sql, $params);
            $categories = $stmt->fetchAll();
            
            $response = [
                'status' => 'success',
                'message' => 'Categories retrieved successfully',
                'data' => $categories
            ];
        } else {
            // Get specific category
            $sql = "SELECT * FROM categories WHERE category_id = ?";
            $stmt = $db->query($sql, [$id]);
            
            if ($stmt->rowCount() > 0) {
                $category = $stmt->fetch();
                
                // Get subcategories
                $sql = "SELECT * FROM categories WHERE parent_id = ? ORDER BY name";
                $stmt = $db->query($sql, [$id]);
                $subcategories = $stmt->fetchAll();
                
                $category['subcategories'] = $subcategories;
                
                // Get category products
                $productObj = new Product();
                $filters = ['category_id' => $id];
                $limit = 8; // Limited number of products for the API
                $products = $productObj->getProducts($limit, 0, $filters);
                
                $category['products'] = $products;
                
                $response = [
                    'status' => 'success',
                    'message' => 'Category retrieved successfully',
                    'data' => $category
                ];
            } else {
                http_response_code(404);
                $response = [
                    'status' => 'error',
                    'message' => 'Category not found',
                    'data' => null
                ];
            }
        }
        break;
        
    case 'POST':
        // Admin only: Create new category
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
        if (empty($data['name'])) {
            http_response_code(400);
            $response = [
                'status' => 'error',
                'message' => 'Category name is required',
                'data' => null
            ];
            break;
        }
        
        $db = Database::getInstance();
        
        // Check if parent_id exists
        if (!empty($data['parent_id'])) {
            $sql = "SELECT category_id FROM categories WHERE category_id = ?";
            $stmt = $db->query($sql, [$data['parent_id']]);
            
            if ($stmt->rowCount() === 0) {
                http_response_code(400);
                $response = [
                    'status' => 'error',
                    'message' => 'Parent category does not exist',
                    'data' => null
                ];
                break;
            }
        }
        
        // Insert category
        $sql = "INSERT INTO categories (name, description, parent_id, image_url, is_active, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())";
        
        $params = [
            $data['name'],
            $data['description'] ?? null,
            $data['parent_id'] ?? null,
            $data['image_url'] ?? null,
            $data['is_active'] ?? 1
        ];
        
        try {
            $stmt = $db->query($sql, $params);
            
            if ($stmt->rowCount() > 0) {
                $categoryId = $db->getConnection()->lastInsertId();
                
                $response = [
                    'status' => 'success',
                    'message' => 'Category created successfully',
                    'data' => [
                        'category_id' => $categoryId
                    ]
                ];
            } else {
                throw new Exception('Failed to create category');
            }
        } catch (Exception $e) {
            http_response_code(500);
            $response = [
                'status' => 'error',
                'message' => 'Failed to create category: ' . $e->getMessage(),
                'data' => null
            ];
        }
        break;
        
    case 'PUT':
        // Admin only: Update category
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
                'message' => 'Category ID is required',
                'data' => null
            ];
            break;
        }
        
        $db = Database::getInstance();
        
        // Check if category exists
        $sql = "SELECT category_id FROM categories WHERE category_id = ?";
        $stmt = $db->query($sql, [$id]);
        
        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            $response = [
                'status' => 'error',
                'message' => 'Category not found',
                'data' => null
            ];
            break;
        }
        
        // Prevent parent_id from creating loop
        if (!empty($data['parent_id'])) {
            // Category cannot be its own parent
            if ($data['parent_id'] == $id) {
                http_response_code(400);
                $response = [
                    'status' => 'error',
                    'message' => 'Category cannot be its own parent',
                    'data' => null
                ];
                break;
            }
            
            // Check if parent exists
            $sql = "SELECT category_id FROM categories WHERE category_id = ?";
            $stmt = $db->query($sql, [$data['parent_id']]);
            
            if ($stmt->rowCount() === 0) {
                http_response_code(400);
                $response = [
                    'status' => 'error',
                    'message' => 'Parent category does not exist',
                    'data' => null
                ];
                break;
            }
            
            // Check for circular reference (parent can't be a child of this category)
            $sql = "WITH RECURSIVE category_path (category_id, parent_id) AS (
                        SELECT category_id, parent_id FROM categories WHERE category_id = ?
                        UNION ALL
                        SELECT c.category_id, c.parent_id FROM categories c
                        JOIN category_path cp ON c.category_id = cp.parent_id
                    )
                    SELECT category_id FROM category_path WHERE category_id = ?";
            
            $stmt = $db->query($sql, [$data['parent_id'], $id]);
            
            if ($stmt->rowCount() > 0) {
                http_response_code(400);
                $response = [
                    'status' => 'error',
                    'message' => 'Circular category reference detected',
                    'data' => null
                ];
                break;
            }
        }
        
        // Build SQL query dynamically
        $sql = "UPDATE categories SET ";
        $params = [];
        
        foreach ($data as $key => $value) {
            // Skip category_id
            if ($key === 'category_id') {
                continue;
            }
            
            if ($key === 'parent_id' && $value === '') {
                $sql .= "$key = NULL, ";
            } else {
                $sql .= "$key = ?, ";
                $params[] = $value;
            }
        }
        
        $sql .= "updated_at = NOW() WHERE category_id = ?";
        $params[] = $id;
        
        try {
            $stmt = $db->query($sql, $params);
            
            if ($stmt->rowCount() > 0) {
                $response = [
                    'status' => 'success',
                    'message' => 'Category updated successfully',
                    'data' => null
                ];
            } else {
                $response = [
                    'status' => 'success',
                    'message' => 'No changes made to category',
                    'data' => null
                ];
            }
        } catch (Exception $e) {
            http_response_code(500);
            $response = [
                'status' => 'error',
                'message' => 'Failed to update category: ' . $e->getMessage(),
                'data' => null
            ];
        }
        break;
        
    case 'DELETE':
        // Admin only: Delete category
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
                'message' => 'Category ID is required',
                'data' => null
            ];
            break;
        }
        
        $db = Database::getInstance();
        
        // Check if category has children
        $sql = "SELECT COUNT(*) as count FROM categories WHERE parent_id = ?";
        $stmt = $db->query($sql, [$id]);
        $result = $stmt->fetch();
        
        if ($result['count'] > 0) {
            http_response_code(400);
            $response = [
                'status' => 'error',
                'message' => 'Cannot delete category with subcategories',
                'data' => null
            ];
            break;
        }
        
        // Check if category has products
        $sql = "SELECT COUNT(*) as count FROM product_categories WHERE category_id = ?";
        $stmt = $db->query($sql, [$id]);
        $result = $stmt->fetch();
        
        if ($result['count'] > 0) {
            http_response_code(400);
            $response = [
                'status' => 'error',
                'message' => 'Cannot delete category with associated products',
                'data' => null
            ];
            break;
        }
        
        // Delete the category
        $sql = "DELETE FROM categories WHERE category_id = ?";
        $stmt = $db->query($sql, [$id]);
        
        if ($stmt->rowCount() > 0) {
            $response = [
                'status' => 'success',
                'message' => 'Category deleted successfully',
                'data' => null
            ];
        } else {
            http_response_code(404);
            $response = [
                'status' => 'error',
                'message' => 'Category not found',
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
