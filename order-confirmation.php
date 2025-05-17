<?php
/**
 * Tienda Mall E-commerce Platform
 * Order Confirmation Page
 */

// Include necessary files
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/db.php';
require_once 'includes/Order.php';

// Check if user is logged in
if (!isLoggedIn()) {
    // Redirect to login page
    redirect('login.php');
}

// Initialize variables
$userId = getCurrentUserId();
$userName = $_SESSION['user_name'] ?? '';
$isLoggedIn = true;
$cartCount = 0;
$orderDetails = null;

// Get order ID from URL
$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($orderId <= 0) {
    // Invalid order ID, redirect to orders page
    redirect('orders.php');
}

// Initialize Order object
$orderObj = new Order();

// Get order details
$order = $orderObj->getOrderById($orderId);

// Check if order exists and belongs to the current user
if (!$order || $order['user_id'] != $userId) {
    // Order not found or doesn't belong to current user
    redirect('orders.php');
}

// Get shipping address details
$db = Database::getInstance();
$stmt = $db->query("SELECT * FROM shipping_addresses WHERE address_id = ?", [$order['shipping_address_id']]);
$shippingAddress = $stmt->fetch();

// Get payment method details
$stmt = $db->query("SELECT * FROM payment_methods WHERE payment_method_id = ?", [$order['payment_method_id'] ?? 0]);
$paymentMethod = $stmt->fetch();

// Get cart information for header
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += $item['quantity'];
    }
}

// Include the header
include 'templates/header.php';
?>

<!-- Main Content -->
<main>
    <section class="order-confirmation">
        <div class="container">
            <div class="confirmation-header">
                <div class="confirmation-icon">
                    <img src="assets/icons/icon-check.png" alt="Success">
                </div>
                <h1>Thank You for Your Order!</h1>
                <p>Your order has been placed successfully. We have sent a confirmation email to your registered email address.</p>
            </div>
            
            <div class="order-details">
                <div class="order-info">
                    <h2>Order Information</h2>
                    <div class="info-row">
                        <span>Order Number:</span>
                        <span>#<?php echo htmlspecialchars($order['order_number']); ?></span>
                    </div>
                    <div class="info-row">
                        <span>Order Date:</span>
                        <span><?php echo date('F j, Y, g:i a', strtotime($order['order_date'])); ?></span>
                    </div>
                    <div class="info-row">
                        <span>Order Status:</span>
                        <span class="order-status <?php echo strtolower($order['status']); ?>"><?php echo ucfirst($order['status']); ?></span>
                    </div>
                    <div class="info-row">
                        <span>Payment Method:</span>
                        <span>
                            <?php 
                            if ($paymentMethod) {
                                echo htmlspecialchars($paymentMethod['provider']) . ' - ' . htmlspecialchars($paymentMethod['payment_type']);
                                if (!empty($paymentMethod['account_number_last4'])) {
                                    echo ' (xxxx-' . htmlspecialchars($paymentMethod['account_number_last4']) . ')';
                                }
                            } else {
                                echo 'Not specified';
                            }
                            ?>
                        </span>
                    </div>
                </div>
                
                <div class="shipping-info">
                    <h2>Shipping Information</h2>
                    <?php if ($shippingAddress): ?>
                    <address>
                        <p><?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
                        <p><?php echo htmlspecialchars($shippingAddress['address_line1']); ?></p>
                        <?php if (!empty($shippingAddress['address_line2'])): ?>
                        <p><?php echo htmlspecialchars($shippingAddress['address_line2']); ?></p>
                        <?php endif; ?>
                        <p><?php echo htmlspecialchars($shippingAddress['city']) . ', ' . htmlspecialchars($shippingAddress['state']) . ' ' . htmlspecialchars($shippingAddress['postal_code']); ?></p>
                        <p><?php echo htmlspecialchars($shippingAddress['country']); ?></p>
                        <?php if (!empty($shippingAddress['phone'])): ?>
                        <p>Phone: <?php echo htmlspecialchars($shippingAddress['phone']); ?></p>
                        <?php endif; ?>
                    </address>
                    <?php else: ?>
                    <p>Shipping address not available.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="order-items">
                <h2>Order Items</h2>
                <div class="order-items-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($order['items'] as $item): ?>
                            <tr>
                                <td class="product-info">
                                    <div class="product-image">
                                        <img src="<?php echo $item['image_url'] ?? 'assets/images/image-product-1-thumbnail.jpg'; ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
                                    </div>
                                    <div class="product-details">
                                        <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                                        <p>SKU: <?php echo htmlspecialchars($item['sku']); ?></p>
                                    </div>
                                </td>
                                <td class="product-price"><?php echo formatPrice($item['unit_price']); ?></td>
                                <td class="product-quantity"><?php echo $item['quantity']; ?></td>
                                <td class="product-total"><?php echo formatPrice($item['subtotal']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="order-summary">
                <h2>Order Summary</h2>
                <div class="summary-totals">
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span><?php echo formatPrice($order['subtotal']); ?></span>
                    </div>
                    
                    <div class="summary-row">
                        <span>Shipping</span>
                        <span><?php echo formatPrice($order['shipping_fee']); ?></span>
                    </div>
                    
                    <div class="summary-row">
                        <span>Tax</span>
                        <span><?php echo formatPrice($order['tax']); ?></span>
                    </div>
                    
                    <?php if ($order['discount'] > 0): ?>
                    <div class="summary-row discount-row">
                        <span>Discount <?php echo !empty($order['coupon_code']) ? '(' . htmlspecialchars($order['coupon_code']) . ')' : ''; ?></span>
                        <span>-<?php echo formatPrice($order['discount']); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="summary-row total-row">
                        <span>Total</span>
                        <span><?php echo formatPrice($order['total']); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="order-actions">
                <a href="orders.php" class="btn btn-secondary">View All Orders</a>
                <a href="products.php" class="btn btn-primary">Continue Shopping</a>
            </div>
        </div>
    </section>
</main>

<?php
// Include the footer
include 'templates/footer.php';
?>
