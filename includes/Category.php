<?php
/**
 * Category Class
 * Handles all category-related operations
 */

require_once 'db.php';
require_once 'functions.php';

class Category {
    private $db;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get all active categories
     * @return array List of categories
     */
    public function getAllCategories() {
        $sql = "SELECT * FROM category WHERE is_active = 'YES' ORDER BY name";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    
    /**
     * Get category by ID
     * @param int $categoryId Category ID
     * @return array|bool Category data if found, false otherwise
     */
    public function getCategoryById($categoryId) {
        $sql = "SELECT * FROM category WHERE category_id = ?";
        $stmt = $this->db->query($sql, [$categoryId]);
        
        if ($stmt->rowCount() > 0) {
            return $stmt->fetch();
        }
        return false;
    }
    
    /**
     * Get subcategories for a parent category
     * @param int $parentId Parent category ID
     * @return array List of subcategories
     */
    public function getSubcategories($parentId) {
        $sql = "SELECT * FROM category WHERE parent_id = ? AND is_active = 'YES' ORDER BY name";
        $stmt = $this->db->query($sql, [$parentId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get parent categories (categories with no parent)
     * @return array List of parent categories
     */
    public function getParentCategories() {
        $sql = "SELECT * FROM category WHERE parent_id IS NULL AND is_active = 'YES' ORDER BY name";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
    
    /**
     * Get category path (breadcrumb)
     * @param int $categoryId Category ID
     * @return array Category path
     */
    public function getCategoryPath($categoryId) {
        $path = [];
        $category = $this->getCategoryById($categoryId);
        
        if ($category) {
            $path[] = $category;
            
            while ($category['parent_id']) {
                $category = $this->getCategoryById($category['parent_id']);
                if ($category) {
                    array_unshift($path, $category);
                } else {
                    break;
                }
            }
        }
        
        return $path;
    }
    
    /**
     * Get featured categories to display on homepage
     * @param int $limit Number of categories to return
     * @return array List of featured categories
     */
    public function getFeaturedCategories($limit = 6) {
        $sql = "SELECT * FROM category WHERE is_active = 'YES' AND is_featured = 'YES' ORDER BY sort_order, name LIMIT ?";
        $stmt = $this->db->query($sql, [$limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get category products count
     * @param int $categoryId Category ID
     * @return int Number of products in category
     */
    public function getCategoryProductsCount($categoryId) {
        $sql = "SELECT COUNT(*) as count FROM product_category pc 
                JOIN product p ON pc.product_id = p.product_id 
                WHERE pc.category_id = ? AND p.is_active = 'Y'";
        $stmt = $this->db->query($sql, [$categoryId]);
        $result = $stmt->fetch();
        return $result ? (int)$result['count'] : 0;
    }
    
    /**
     * Get category breadcrumb for navigation
     * @param int $categoryId Category ID
     * @return array Category breadcrumb array
     */
    public function getCategoryBreadcrumb($categoryId) {
        return $this->getCategoryPath($categoryId);
    }
}
?>
