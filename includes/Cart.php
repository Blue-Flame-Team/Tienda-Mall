<?php
/**
 * Cart Class
 * Handles shopping cart operations
 */

require_once 'db.php';
require_once 'functions.php';
require_once 'Product.php';

class Cart {
    private $db;
    private $items = [];
    private $productObj;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->db = Database::getInstance();
        $this->productObj = new Product();
        
        // Initialize cart from session if it exists
        if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
            $this->items = $_SESSION['cart'];
        } else {
            $_SESSION['cart'] = [];
        }
    }
    
    /**
     * Add item to cart
     * @param int $productId Product ID
     * @param int $quantity Quantity to add
     * @return bool True if successful, false otherwise
     */
    public function addItem($productId, $quantity = 1) {
        // Validate quantity
        $quantity = max(1, intval($quantity));
        
        // Check if product exists and is active
        $product = $this->productObj->getProductById($productId);
        if (!$product) {
            return false;
        }
        
        // Check if product is in stock
        if (!isset($product['quantity']) || ($product['quantity'] - $product['reserved_quantity']) < $quantity) {
            return false;
        }
        
        // If product already in cart, update quantity
        if (isset($this->items[$productId])) {
            $this->items[$productId]['quantity'] += $quantity;
        } else {
            // Add new item to cart
            $this->items[$productId] = [
                'product_id' => $productId,
                'title' => $product['title'],
                'price' => $product['price'],
                'image' => isset($product['images'][0]) ? $product['images'][0]['image_url'] : '',
                'quantity' => $quantity
            ];
        }
        
        // Save cart to session
        $_SESSION['cart'] = $this->items;
        
        return true;
    }
    
    /**
     * Update cart item quantity
     * @param int $productId Product ID
     * @param int $quantity New quantity
     * @return bool True if successful, false otherwise
     */
    public function updateQuantity($productId, $quantity) {
        // Validate quantity
        $quantity = max(1, intval($quantity));
        
        // Check if product exists in cart
        if (!isset($this->items[$productId])) {
            return false;
        }
        
        // Check if product exists and is active
        $product = $this->productObj->getProductById($productId);
        if (!$product) {
            return false;
        }
        
        // Check if requested quantity is available
        if (!isset($product['quantity']) || ($product['quantity'] - $product['reserved_quantity']) < $quantity) {
            return false;
        }
        
        // Update quantity
        $this->items[$productId]['quantity'] = $quantity;
        
        // Save cart to session
        $_SESSION['cart'] = $this->items;
        
        return true;
    }
    
    /**
     * Remove item from cart
     * @param int $productId Product ID
     * @return bool True if successful, false otherwise
     */
    public function removeItem($productId) {
        if (isset($this->items[$productId])) {
            unset($this->items[$productId]);
            
            // Save cart to session
            $_SESSION['cart'] = $this->items;
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Clear cart
     */
    public function clearCart() {
        $this->items = [];
        $_SESSION['cart'] = [];
    }
    
    /**
     * Get cart items
     * @return array Cart items
     */
    public function getItems() {
        return $this->items;
    }
    
    /**
     * Get cart item count
     * @return int Number of items in cart
     */
    public function getItemCount() {
        $count = 0;
        foreach ($this->items as $item) {
            $count += $item['quantity'];
        }
        return $count;
    }
    
    /**
     * Get cart subtotal
     * @return float Cart subtotal
     */
    public function getSubtotal() {
        $subtotal = 0;
        foreach ($this->items as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }
        return $subtotal;
    }
    
    /**
     * Calculate tax amount
     * @param float $subtotal Subtotal amount
     * @param float $taxRate Tax rate percentage
     * @return float Tax amount
     */
    public function calculateTax($subtotal, $taxRate = 10) {
        return ($subtotal * $taxRate) / 100;
    }
    
    /**
     * Calculate shipping fee
     * @param float $subtotal Subtotal amount
     * @param string $shippingMethod Shipping method
     * @return float Shipping fee
     */
    public function calculateShippingFee($subtotal, $shippingMethod = 'standard') {
        // This is a simplified implementation
        // In a real application, you would have a more complex calculation
        // based on weight, dimensions, destination, etc.
        
        switch ($shippingMethod) {
            case 'express':
                return 15.00;
            case 'standard':
                return ($subtotal >= 100) ? 0 : 8.00; // Free shipping for orders over $100
            default:
                return 8.00;
        }
    }
    
    /**
     * Apply coupon discount
     * @param string $couponCode Coupon code
     * @return array|bool Coupon details if valid, false otherwise
     */
    public function applyCoupon($couponCode) {
        $sql = "SELECT * FROM coupons 
                WHERE code = ? 
                AND is_active = 1 
                AND start_date <= NOW() 
                AND end_date >= NOW() 
                AND (usage_limit = 0 OR usage_count < usage_limit)";
        
        $stmt = $this->db->query($sql, [$couponCode]);
        
        if ($stmt->rowCount() > 0) {
            return $stmt->fetch();
        }
        
        return false;
    }
    
    /**
     * Calculate discount amount
     * @param float $subtotal Subtotal amount
     * @param array $coupon Coupon details
     * @return float Discount amount
     */
    public function calculateDiscount($subtotal, $coupon) {
        if (!$coupon) {
            return 0;
        }
        
        if ($coupon['discount_type'] === 'percentage') {
            return ($subtotal * $coupon['discount_value']) / 100;
        } else { // fixed amount
            return min($subtotal, $coupon['discount_value']);
        }
    }
    
    /**
     * Calculate order total
     * @param float $subtotal Subtotal amount
     * @param float $tax Tax amount
     * @param float $shipping Shipping fee
     * @param float $discount Discount amount
     * @return float Order total
     */
    public function calculateTotal($subtotal, $tax, $shipping, $discount) {
        return $subtotal + $tax + $shipping - $discount;
    }
}
?>
