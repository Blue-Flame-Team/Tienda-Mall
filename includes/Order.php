<?php
/**
 * Order Class
 * Handles order-related operations
 */

require_once 'db.php';
require_once 'functions.php';
require_once 'Cart.php';

class Order {
    private $db;
    private $cart;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->db = Database::getInstance();
        $this->cart = new Cart();
    }
    
    /**
     * Create a new order
     * @param array $orderData Order data
     * @return bool|int Order ID if successful, false otherwise
     */
    public function createOrder($orderData) {
        // Check if cart is empty
        $cartItems = $this->cart->getItems();
        if (empty($cartItems)) {
            return false;
        }
        
        // Check required fields
        if (empty($orderData['user_id']) || empty($orderData['shipping_address_id'])) {
            return false;
        }
        
        // Generate unique order number
        $orderNumber = $this->generateOrderNumber();
        
        // Start transaction
        $this->db->getConnection()->beginTransaction();
        
        try {
            // Insert order
            $sql = "INSERT INTO orders (
                        user_id, order_number, order_date, status, 
                        subtotal, tax, shipping_fee, discount, total,
                        payment_method_id, shipping_address_id, billing_address_id,
                        notes, coupon_code
                    ) VALUES (
                        ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                    )";
            
            $params = [
                $orderData['user_id'],
                $orderNumber,
                'pending', // Default status
                $orderData['subtotal'],
                $orderData['tax'],
                $orderData['shipping_fee'],
                $orderData['discount'] ?? 0,
                $orderData['total'],
                $orderData['payment_method_id'] ?? null,
                $orderData['shipping_address_id'],
                $orderData['billing_address_id'] ?? $orderData['shipping_address_id'],
                $orderData['notes'] ?? null,
                $orderData['coupon_code'] ?? null
            ];
            
            $stmt = $this->db->query($sql, $params);
            
            if ($stmt->rowCount() > 0) {
                $orderId = $this->db->getConnection()->lastInsertId();
                
                // Insert order items
                foreach ($cartItems as $item) {
                    $sql = "INSERT INTO order_items (
                                order_id, product_id, quantity, unit_price, subtotal
                            ) VALUES (
                                ?, ?, ?, ?, ?
                            )";
                    
                    $itemSubtotal = $item['price'] * $item['quantity'];
                    $params = [
                        $orderId,
                        $item['product_id'],
                        $item['quantity'],
                        $item['price'],
                        $itemSubtotal
                    ];
                    
                    $this->db->query($sql, $params);
                    
                    // Update inventory (reduce available quantity and increase reserved)
                    $sql = "UPDATE inventory 
                            SET reserved_quantity = reserved_quantity + ?, 
                                last_stock_update = NOW() 
                            WHERE product_id = ?";
                    
                    $this->db->query($sql, [$item['quantity'], $item['product_id']]);
                }
                
                // Update coupon usage if used
                if (!empty($orderData['coupon_code'])) {
                    $sql = "UPDATE coupons 
                            SET usage_count = usage_count + 1 
                            WHERE code = ?";
                    
                    $this->db->query($sql, [$orderData['coupon_code']]);
                }
                
                // Clear cart
                $this->cart->clearCart();
                
                // Commit transaction
                $this->db->getConnection()->commit();
                
                return $orderId;
            }
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->db->getConnection()->rollBack();
            error_log("Error creating order: " . $e->getMessage());
        }
        
        return false;
    }
    
    /**
     * Generate unique order number
     * @return string Order number
     */
    private function generateOrderNumber() {
        // Format: TND-YEAR-RANDOM (e.g., TND-2025-123456)
        $prefix = 'TND';
        $year = date('Y');
        $random = mt_rand(100000, 999999);
        
        return $prefix . '-' . $year . '-' . $random;
    }
    
    /**
     * Get order by ID
     * @param int $orderId Order ID
     * @return array|bool Order data if found, false otherwise
     */
    public function getOrderById($orderId) {
        $sql = "SELECT o.*, 
                    sa.address_line1 as shipping_address_line1, 
                    sa.address_line2 as shipping_address_line2,
                    sa.city as shipping_city, 
                    sa.state as shipping_state, 
                    sa.postal_code as shipping_postal_code,
                    sa.country as shipping_country,
                    ba.address_line1 as billing_address_line1, 
                    ba.address_line2 as billing_address_line2,
                    ba.city as billing_city, 
                    ba.state as billing_state, 
                    ba.postal_code as billing_postal_code,
                    ba.country as billing_country,
                    pm.payment_type, 
                    pm.provider, 
                    pm.account_number_last4
                FROM orders o
                LEFT JOIN shipping_addresses sa ON o.shipping_address_id = sa.address_id
                LEFT JOIN shipping_addresses ba ON o.billing_address_id = ba.address_id
                LEFT JOIN payment_methods pm ON o.payment_method_id = pm.payment_method_id
                WHERE o.order_id = ?";
        
        $stmt = $this->db->query($sql, [$orderId]);
        
        if ($stmt->rowCount() > 0) {
            $order = $stmt->fetch();
            
            // Get order items
            $order['items'] = $this->getOrderItems($orderId);
            
            return $order;
        }
        
        return false;
    }
    
    /**
     * Get order items
     * @param int $orderId Order ID
     * @return array Order items
     */
    public function getOrderItems($orderId) {
        $sql = "SELECT oi.*, p.title, p.sku, pi.image_url
                FROM order_items oi
                JOIN products p ON oi.product_id = p.product_id
                LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
                WHERE oi.order_id = ?";
        
        $stmt = $this->db->query($sql, [$orderId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get orders by user ID
     * @param int $userId User ID
     * @param int $limit Number of orders to get
     * @param int $offset Offset for pagination
     * @return array Orders
     */
    public function getOrdersByUserId($userId, $limit = 10, $offset = 0) {
        $sql = "SELECT o.*, 
                    (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) as item_count
                FROM orders o
                WHERE o.user_id = ?
                ORDER BY o.order_date DESC
                LIMIT ? OFFSET ?";
        
        $stmt = $this->db->query($sql, [$userId, $limit, $offset]);
        return $stmt->fetchAll();
    }
    
    /**
     * Update order status
     * @param int $orderId Order ID
     * @param string $status New status
     * @param string $trackingNumber Tracking number (optional)
     * @return bool True if successful, false otherwise
     */
    public function updateOrderStatus($orderId, $status, $trackingNumber = null) {
        $sql = "UPDATE orders SET status = ?";
        $params = [$status];
        
        if ($trackingNumber !== null) {
            $sql .= ", tracking_number = ?";
            $params[] = $trackingNumber;
        }
        
        $sql .= " WHERE order_id = ?";
        $params[] = $orderId;
        
        $stmt = $this->db->query($sql, $params);
        
        if ($stmt->rowCount() > 0) {
            // If order is shipped or delivered, update inventory
            if ($status === 'shipped' || $status === 'delivered') {
                $this->updateInventoryAfterShipment($orderId);
            }
            
            // If order is cancelled, update inventory
            if ($status === 'cancelled') {
                $this->restoreInventoryAfterCancellation($orderId);
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Update inventory after order shipment
     * @param int $orderId Order ID
     */
    private function updateInventoryAfterShipment($orderId) {
        // Get order items
        $items = $this->getOrderItems($orderId);
        
        foreach ($items as $item) {
            // Reduce quantity and reserved quantity
            $sql = "UPDATE inventory 
                    SET quantity = quantity - ?, 
                        reserved_quantity = reserved_quantity - ?,
                        last_stock_update = NOW()
                    WHERE product_id = ?";
            
            $this->db->query($sql, [$item['quantity'], $item['quantity'], $item['product_id']]);
        }
    }
    
    /**
     * Restore inventory after order cancellation
     * @param int $orderId Order ID
     */
    private function restoreInventoryAfterCancellation($orderId) {
        // Get order items
        $items = $this->getOrderItems($orderId);
        
        foreach ($items as $item) {
            // Reduce reserved quantity (but keep actual quantity the same)
            $sql = "UPDATE inventory 
                    SET reserved_quantity = reserved_quantity - ?,
                        last_stock_update = NOW()
                    WHERE product_id = ?";
            
            $this->db->query($sql, [$item['quantity'], $item['product_id']]);
        }
    }
    
    /**
     * Get order count by user ID
     * @param int $userId User ID
     * @return int Order count
     */
    public function getOrderCountByUserId($userId) {
        $sql = "SELECT COUNT(*) as count FROM orders WHERE user_id = ?";
        $stmt = $this->db->query($sql, [$userId]);
        $result = $stmt->fetch();
        return $result['count'];
    }
    
    /**
     * Get all orders (admin use)
     * @param array $filters Filters to apply
     * @param int $limit Number of orders to get
     * @param int $offset Offset for pagination
     * @return array Orders
     */
    public function getAllOrders($filters = [], $limit = 20, $offset = 0) {
        $sql = "SELECT o.*, 
                    u.email, u.first_name, u.last_name,
                    (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) as item_count
                FROM orders o
                JOIN users u ON o.user_id = u.user_id
                WHERE 1=1 ";
        
        $params = [];
        
        // Apply filters
        if (!empty($filters)) {
            // Status filter
            if (isset($filters['status']) && !empty($filters['status'])) {
                $sql .= "AND o.status = ? ";
                $params[] = $filters['status'];
            }
            
            // Date range filter
            if (isset($filters['start_date']) && !empty($filters['start_date'])) {
                $sql .= "AND o.order_date >= ? ";
                $params[] = $filters['start_date'];
            }
            
            if (isset($filters['end_date']) && !empty($filters['end_date'])) {
                $sql .= "AND o.order_date <= ? ";
                $params[] = $filters['end_date'];
            }
            
            // Search by order number or customer email/name
            if (isset($filters['search']) && !empty($filters['search'])) {
                $searchTerm = "%" . $filters['search'] . "%";
                $sql .= "AND (o.order_number LIKE ? OR u.email LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?) ";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
        }
        
        $sql .= "ORDER BY o.order_date DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->db->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get order count (admin use)
     * @param array $filters Filters to apply
     * @return int Order count
     */
    public function getOrderCount($filters = []) {
        $sql = "SELECT COUNT(*) as count 
                FROM orders o
                JOIN users u ON o.user_id = u.user_id
                WHERE 1=1 ";
        
        $params = [];
        
        // Apply filters
        if (!empty($filters)) {
            // Status filter
            if (isset($filters['status']) && !empty($filters['status'])) {
                $sql .= "AND o.status = ? ";
                $params[] = $filters['status'];
            }
            
            // Date range filter
            if (isset($filters['start_date']) && !empty($filters['start_date'])) {
                $sql .= "AND o.order_date >= ? ";
                $params[] = $filters['start_date'];
            }
            
            if (isset($filters['end_date']) && !empty($filters['end_date'])) {
                $sql .= "AND o.order_date <= ? ";
                $params[] = $filters['end_date'];
            }
            
            // Search by order number or customer email/name
            if (isset($filters['search']) && !empty($filters['search'])) {
                $searchTerm = "%" . $filters['search'] . "%";
                $sql .= "AND (o.order_number LIKE ? OR u.email LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?) ";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
        }
        
        $stmt = $this->db->query($sql, $params);
        $result = $stmt->fetch();
        return $result['count'];
    }
}
?>
