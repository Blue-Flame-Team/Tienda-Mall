<?php
/**
 * Helper functions for the Tienda Mall e-commerce platform
 */

// Include necessary files
require_once 'config.php';
require_once 'db.php';

/**
 * Sanitize user input to prevent XSS attacks
 * @param string $data Input data to sanitize
 * @return string Sanitized data
 */
if (!function_exists('sanitize')) {
    function sanitize($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        return $data;
    }
}

/**
 * Validate email address
 * @param string $email Email to validate
 * @return bool True if valid, false otherwise
 */
if (!function_exists('validateEmail')) {
    function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

/**
 * Generate a secure random token
 * @param int $length Length of token
 * @return string Random token
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Hash password securely using PHP's password_hash
 * @param string $password Plain text password
 * @return string Hashed password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

/**
 * Verify password against hash
 * @param string $password Plain text password
 * @param string $hash Hashed password
 * @return bool True if password matches hash
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Redirect to a different page
 * @param string $location Page to redirect to
 */
if (!function_exists('redirect')) {
    function redirect($location) {
        header("Location: " . $location);
        exit;
    }
}

/**
 * Check if user is logged in
 * @return bool True if logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Get current user ID
 * @return int|null User ID if logged in, null otherwise
 */
function getCurrentUserId() {
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
}

/**
 * Check if admin user is logged in
 * @return bool True if admin is logged in
 */
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

/**
 * Flash message system for displaying one-time messages
 * @param string $name Message name
 * @param string $message Message content
 * @param string $class CSS class for styling
 */
function flashMessage($name = '', $message = '', $class = 'alert alert-success') {
    if (!empty($name)) {
        if (!empty($message) && empty($_SESSION[$name])) {
            $_SESSION[$name] = $message;
            $_SESSION[$name . '_class'] = $class;
        } elseif (empty($message) && !empty($_SESSION[$name])) {
            $class = !empty($_SESSION[$name . '_class']) ? $_SESSION[$name . '_class'] : '';
            echo '<div class="' . $class . '" id="msg-flash">' . $_SESSION[$name] . '</div>';
            unset($_SESSION[$name]);
            unset($_SESSION[$name . '_class']);
        }
    }
}

/**
 * Format price with currency symbol from site settings
 * @param float $price Price to format
 * @param string|null $currency Currency symbol (optional, will use from settings if not provided)
 * @return string Formatted price
 */
function formatPrice($price, $currency = null) {
    if ($currency === null) {
        // Get currency symbol from site settings
        static $cachedCurrencySymbol = null;
        
        if ($cachedCurrencySymbol === null) {
            try {
                $db = Database::getInstance();
                $stmt = $db->query("SELECT setting_value FROM site_settings WHERE setting_key = 'currency_symbol' LIMIT 1");
                $result = $stmt->fetch();
                $cachedCurrencySymbol = $result ? $result['setting_value'] : 'L.E';
            } catch (Exception $e) {
                // Default to L.E if there's an error
                $cachedCurrencySymbol = 'L.E';
            }
        }
        
        $currency = $cachedCurrencySymbol;
    }
    
    // For EGP/L.E, place the currency symbol after the number
    if ($currency == 'L.E' || $currency == 'ج.م') {
        return number_format($price, 2) . ' ' . $currency;
    }
    
    // Other currencies like USD have symbol before the number
    return $currency . number_format($price, 2);
}

/**
 * Get product categories
 * @return array Categories
 */
function getCategories() {
    $db = Database::getInstance();
    $stmt = $db->query("SELECT * FROM category WHERE is_active = 'YES' ORDER BY name");
    return $stmt->fetchAll();
}

/**
 * Get featured products
 * @param int $limit Number of products to get
 * @return array Featured products
 */
if (!function_exists('getFeaturedProducts')) {
    function getFeaturedProducts($limit = 8) {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT p.*, pi.image_url 
                            FROM product p 
                            LEFT JOIN product_image pi ON p.product_id = pi.product_id 
                            WHERE p.is_active = 'Y' AND pi.is_primary = 'YES' 
                            ORDER BY p.created_at DESC 
                            LIMIT ?", [$limit]);
        return $stmt->fetchAll();
    }
}

/**
 * Get product details by ID
 * @param int $productId Product ID
 * @return array|false Product details or false if not found
 */
if (!function_exists('getProductById')) {
    function getProductById($productId) {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT p.*, pi.image_url 
                            FROM products p 
                            LEFT JOIN product_images pi ON p.product_id = pi.product_id 
                            WHERE p.product_id = ? AND pi.is_primary = 1", [$productId]);
        return $stmt->fetch();
    }
}

/**
 * Get product reviews
 * @param int $productId Product ID
 * @return array Product reviews
 */
function getProductReviews($productId) {
    $db = Database::getInstance();
    $stmt = $db->query("SELECT r.*, u.first_name, u.last_name 
                        FROM reviews r 
                        JOIN users u ON r.user_id = u.user_id 
                        WHERE r.product_id = ? AND r.is_approved = 1 
                        ORDER BY r.created_at DESC", [$productId]);
    return $stmt->fetchAll();
}

/**
 * Calculate average rating for a product
 * @param int $productId Product ID
 * @return float Average rating
 */
function getAverageRating($productId) {
    $db = Database::getInstance();
    $stmt = $db->query("SELECT AVG(rating) as avg_rating FROM reviews WHERE product_id = ? AND is_approved = 1", [$productId]);
    $result = $stmt->fetch();
    return $result ? round($result['avg_rating'], 1) : 0;
}

/**
 * Get all product images
 * @param int $productId Product ID
 * @return array Product images
 */
function getProductImages($productId) {
    $db = Database::getInstance();
    $stmt = $db->query("SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order", [$productId]);
    return $stmt->fetchAll();
}

/**
 * Check if product is in stock
 * @param int $productId Product ID
 * @return bool True if in stock
 */
function isProductInStock($productId) {
    $db = Database::getInstance();
    $stmt = $db->query("SELECT quantity, reserved_quantity FROM inventory WHERE product_id = ?", [$productId]);
    $inventory = $stmt->fetch();
    return $inventory && ($inventory['quantity'] - $inventory['reserved_quantity'] > 0);
}
?>
