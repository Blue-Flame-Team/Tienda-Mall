<?php
/**
 * Category Controller
 * Handles all category-related API endpoints
 */

require_once '../includes/Controller.php';
require_once '../includes/DatabaseHelper.php';

class CategoryController extends Controller {
    private $db;
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->db = new DatabaseHelper();
    }
    
    /**
     * Route the request to the appropriate handler
     */
    public function handleRequest($action = '', $id = null) {
        switch ($this->method) {
            case 'GET':
                if ($id) {
                    $this->getCategory($id);
                } else if ($action === 'tree') {
                    $this->getCategoryTree();
                } else if ($action === 'featured') {
                    $this->getFeaturedCategories();
                } else {
                    $this->getAllCategories();
                }
                break;
                
            case 'POST':
                $this->requireAuth();
                $this->createCategory();
                break;
                
            case 'PUT':
                $this->requireAuth();
                if ($id) {
                    $this->updateCategory($id);
                } else {
                    $this->respondError('Category ID is required');
                }
                break;
                
            case 'DELETE':
                $this->requireAuth();
                if ($id) {
                    $this->deleteCategory($id);
                } else {
                    $this->respondError('Category ID is required');
                }
                break;
                
            default:
                $this->respondError('Method not allowed', null, 405);
        }
    }
    
    /**
     * Get all categories
     */
    private function getAllCategories() {
        $sql = "SELECT * FROM category WHERE is_active = 'YES' ORDER BY name";
        $categories = $this->db->fetchAll($sql);
        
        $this->respondSuccess($categories);
    }
    
    /**
     * Get category by ID
     */
    private function getCategory($id) {
        $sql = "SELECT * FROM category WHERE category_id = ?";
        $category = $this->db->fetchOne($sql, [$id]);
        
        if ($category) {
            // Get subcategories
            $sql = "SELECT c.* FROM category c
                    JOIN has_subcategory hs ON c.category_id = hs.subcategory_id2
                    WHERE hs.category_id1 = ? AND c.is_active = 'YES'
                    ORDER BY c.name";
            $subcategories = $this->db->fetchAll($sql, [$id]);
            $category['subcategories'] = $subcategories;
            
            // Get parent category if exists
            $sql = "SELECT c.* FROM category c
                    JOIN has_subcategory hs ON c.category_id = hs.category_id1
                    WHERE hs.subcategory_id2 = ?";
            $parent = $this->db->fetchOne($sql, [$id]);
            $category['parent'] = $parent;
            
            // Get product count in this category
            $sql = "SELECT COUNT(*) as count FROM categorized_in WHERE category_id = ?";
            $result = $this->db->fetchOne($sql, [$id]);
            $category['product_count'] = (int)$result['count'];
            
            $this->respondSuccess($category);
        } else {
            $this->respondNotFound('Category not found');
        }
    }
    
    /**
     * Get category tree (hierarchical structure)
     */
    private function getCategoryTree() {
        // Get all parent categories (those with no parent)
        $sql = "SELECT * FROM category 
                WHERE category_id NOT IN (SELECT subcategory_id2 FROM has_subcategory)
                AND is_active = 'YES'
                ORDER BY name";
        $parentCategories = $this->db->fetchAll($sql);
        
        // For each parent, get its subcategories
        foreach ($parentCategories as &$parent) {
            $parent['subcategories'] = $this->getSubcategories($parent['category_id']);
        }
        
        $this->respondSuccess($parentCategories);
    }
    
    /**
     * Get featured categories
     */
    private function getFeaturedCategories() {
        $limit = (int)$this->getParam('limit', 4);
        
        $sql = "SELECT * FROM category WHERE is_featured = 'YES' AND is_active = 'YES' ORDER BY sort_order LIMIT ?";
        $categories = $this->db->fetchAll($sql, [$limit]);
        
        $this->respondSuccess($categories);
    }
    
    /**
     * Create a new category
     */
    private function createCategory() {
        $rules = [
            'name' => 'required|min:2|max:100',
            'description' => 'max:500',
            'is_active' => 'in:YES,NO',
            'is_featured' => 'in:YES,NO',
            'parent_id' => 'integer'
        ];
        
        $errors = $this->validate($rules);
        
        if (!empty($errors)) {
            $this->respondValidationError($errors);
            return;
        }
        
        $categoryData = [
            'name' => $this->getParam('name'),
            'description' => $this->getParam('description', ''),
            'is_active' => $this->getParam('is_active', 'YES'),
            'is_featured' => $this->getParam('is_featured', 'NO'),
            'image_url' => $this->getParam('image_url', ''),
            'sort_order' => (int)$this->getParam('sort_order', 0),
            'seo_title' => $this->getParam('seo_title', ''),
            'seo_description' => $this->getParam('seo_description', '')
        ];
        
        $this->db->beginTransaction();
        
        try {
            // Insert category
            $categoryId = $this->db->insert('category', $categoryData);
            
            // If parent ID provided, create relationship
            if ($this->hasParam('parent_id')) {
                $parentId = (int)$this->getParam('parent_id');
                if ($parentId > 0) {
                    // Check if parent exists
                    $sql = "SELECT category_id FROM category WHERE category_id = ?";
                    $parent = $this->db->fetchOne($sql, [$parentId]);
                    
                    if ($parent) {
                        $this->db->insert('has_subcategory', [
                            'category_id1' => $parentId,
                            'subcategory_id2' => $categoryId
                        ]);
                    }
                }
            }
            
            $this->db->commit();
            
            $this->respondSuccess(['category_id' => $categoryId], 'Category created successfully', 201);
        } catch (Exception $e) {
            $this->db->rollback();
            $this->respondError('Failed to create category: ' . $e->getMessage());
        }
    }
    
    /**
     * Update a category
     */
    private function updateCategory($id) {
        // Check if category exists
        $sql = "SELECT * FROM category WHERE category_id = ?";
        $category = $this->db->fetchOne($sql, [$id]);
        
        if (!$category) {
            $this->respondNotFound('Category not found');
            return;
        }
        
        $rules = [
            'name' => 'min:2|max:100',
            'description' => 'max:500',
            'is_active' => 'in:YES,NO',
            'is_featured' => 'in:YES,NO',
            'parent_id' => 'integer'
        ];
        
        $errors = $this->validate($rules);
        
        if (!empty($errors)) {
            $this->respondValidationError($errors);
            return;
        }
        
        $updateData = [];
        
        if ($this->hasParam('name')) {
            $updateData['name'] = $this->getParam('name');
        }
        
        if ($this->hasParam('description')) {
            $updateData['description'] = $this->getParam('description');
        }
        
        if ($this->hasParam('is_active')) {
            $updateData['is_active'] = $this->getParam('is_active');
        }
        
        if ($this->hasParam('is_featured')) {
            $updateData['is_featured'] = $this->getParam('is_featured');
        }
        
        if ($this->hasParam('image_url')) {
            $updateData['image_url'] = $this->getParam('image_url');
        }
        
        if ($this->hasParam('sort_order')) {
            $updateData['sort_order'] = (int)$this->getParam('sort_order');
        }
        
        if ($this->hasParam('seo_title')) {
            $updateData['seo_title'] = $this->getParam('seo_title');
        }
        
        if ($this->hasParam('seo_description')) {
            $updateData['seo_description'] = $this->getParam('seo_description');
        }
        
        if (empty($updateData)) {
            $this->respondError('No data provided for update');
            return;
        }
        
        $this->db->beginTransaction();
        
        try {
            // Update category
            $result = $this->db->update('category', $updateData, 'category_id = ?', [$id]);
            
            // Update parent relationship if provided
            if ($this->hasParam('parent_id')) {
                $parentId = (int)$this->getParam('parent_id');
                
                // Remove existing relationships
                $this->db->delete('has_subcategory', 'subcategory_id2 = ?', [$id]);
                
                // Add new relationship if parent ID is valid
                if ($parentId > 0) {
                    // Check if parent exists
                    $sql = "SELECT category_id FROM category WHERE category_id = ?";
                    $parent = $this->db->fetchOne($sql, [$parentId]);
                    
                    if ($parent) {
                        $this->db->insert('has_subcategory', [
                            'category_id1' => $parentId,
                            'subcategory_id2' => $id
                        ]);
                    }
                }
            }
            
            $this->db->commit();
            
            $this->respondSuccess(null, 'Category updated successfully');
        } catch (Exception $e) {
            $this->db->rollback();
            $this->respondError('Failed to update category: ' . $e->getMessage());
        }
    }
    
    /**
     * Delete a category
     */
    private function deleteCategory($id) {
        // Check if category exists
        $sql = "SELECT * FROM category WHERE category_id = ?";
        $category = $this->db->fetchOne($sql, [$id]);
        
        if (!$category) {
            $this->respondNotFound('Category not found');
            return;
        }
        
        // Check if category has products
        $sql = "SELECT COUNT(*) as count FROM categorized_in WHERE category_id = ?";
        $result = $this->db->fetchOne($sql, [$id]);
        
        if ((int)$result['count'] > 0) {
            $this->respondError('Cannot delete category because it has associated products', null, 400);
            return;
        }
        
        $this->db->beginTransaction();
        
        try {
            // Delete relationships
            $this->db->delete('has_subcategory', 'category_id1 = ? OR subcategory_id2 = ?', [$id, $id]);
            
            // Delete category
            $this->db->delete('category', 'category_id = ?', [$id]);
            
            $this->db->commit();
            
            $this->respondSuccess(null, 'Category deleted successfully');
        } catch (Exception $e) {
            $this->db->rollback();
            $this->respondError('Failed to delete category: ' . $e->getMessage());
        }
    }
    
    /**
     * Helper function to get subcategories recursively
     */
    private function getSubcategories($categoryId) {
        $sql = "SELECT c.* FROM category c
                JOIN has_subcategory hs ON c.category_id = hs.subcategory_id2
                WHERE hs.category_id1 = ? AND c.is_active = 'YES'
                ORDER BY c.name";
        $subcategories = $this->db->fetchAll($sql, [$categoryId]);
        
        // For each subcategory, get its subcategories
        foreach ($subcategories as &$subcategory) {
            $subcategory['subcategories'] = $this->getSubcategories($subcategory['category_id']);
        }
        
        return $subcategories;
    }
}
?>
