<?php
/**
 * Tienda Mall E-commerce Platform
 * Checkout Page
 */

// Include necessary files
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/db.php';
require_once 'includes/Cart.php';
require_once 'includes/Order.php';

// Check if user is logged in
if (!isLoggedIn()) {
    // Redirect to login page with return URL
    redirect('login.php?redirect=' . urlencode('checkout.php'));
}

// Initialize variables
$cartObj = new Cart();
$cartItems = $cartObj->getItems();
$subtotal = $cartObj->getSubtotal();
$cartCount = $cartObj->getItemCount();
$userId = getCurrentUserId();
$userName = $_SESSION['user_name'] ?? '';
$isLoggedIn = true;
$errors = [];
$success = false;

// Check if cart is empty
if (empty($cartItems)) {
    redirect('cart.php');
}

// Get shipping addresses
$db = Database::getInstance();
$stmt = $db->query("SELECT * FROM shipping_addresses WHERE user_id = ? ORDER BY is_default DESC", [$userId]);
$shippingAddresses = $stmt->fetchAll();

// Get payment methods
$stmt = $db->query("SELECT * FROM payment_methods WHERE user_id = ? ORDER BY is_default DESC", [$userId]);
$paymentMethods = $stmt->fetchAll();

// Calculate shipping, tax, and total
$shippingMethod = $_SESSION['shipping_method'] ?? 'standard';
$shipping = $cartObj->calculateShippingFee($subtotal, $shippingMethod);
$taxRate = 10; // Default tax rate
$tax = $cartObj->calculateTax($subtotal, $taxRate);

// Apply coupon if available
$discount = 0;
$couponCode = '';

if (isset($_SESSION['coupon'])) {
    $coupon = $_SESSION['coupon'];
    $discount = $coupon['discount_amount'];
    $couponCode = $coupon['code'];
}

// Calculate total
$total = $cartObj->calculateTotal($subtotal, $tax, $shipping, $discount);

// Process checkout form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate shipping address
    if (empty($_POST['shipping_address_id'])) {
        $errors[] = 'Please select a shipping address.';
    } else {
        $shippingAddressId = (int)$_POST['shipping_address_id'];
        
        // Check if shipping address belongs to user
        $stmt = $db->query("SELECT address_id FROM shipping_addresses WHERE address_id = ? AND user_id = ?", [$shippingAddressId, $userId]);
        if ($stmt->rowCount() === 0) {
            $errors[] = 'Invalid shipping address.';
        }
    }
    
    // Validate payment method
    if (empty($_POST['payment_method_id'])) {
        $errors[] = 'Please select a payment method.';
    } else {
        $paymentMethodId = (int)$_POST['payment_method_id'];
        
        // Check if payment method belongs to user
        $stmt = $db->query("SELECT payment_method_id FROM payment_methods WHERE payment_method_id = ? AND user_id = ?", [$paymentMethodId, $userId]);
        if ($stmt->rowCount() === 0) {
            $errors[] = 'Invalid payment method.';
        }
    }
    
    // Process order if no errors
    if (empty($errors)) {
        // Prepare order data
        $orderData = [
            'user_id' => $userId,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'shipping_fee' => $shipping,
            'discount' => $discount,
            'total' => $total,
            'shipping_address_id' => $shippingAddressId,
            'billing_address_id' => $_POST['billing_address_id'] ?? $shippingAddressId,
            'payment_method_id' => $paymentMethodId,
            'notes' => sanitize($_POST['order_notes'] ?? ''),
            'coupon_code' => $couponCode
        ];
        
        // Create order
        $orderObj = new Order();
        $orderId = $orderObj->createOrder($orderData);
        
        if ($orderId) {
            // Clear coupon after successful order
            if (isset($_SESSION['coupon'])) {
                unset($_SESSION['coupon']);
            }
            
            // Redirect to order confirmation page
            redirect('order-confirmation.php?id=' . $orderId);
        } else {
            $errors[] = 'Failed to create order. Please try again.';
        }
    }
}

// Include the header
include 'templates/header.php';
?>

<!-- Main Content -->
<main>
    <section class="checkout-section">
        <div class="container">
            <h1>Checkout</h1>
            
            <div class="checkout-container">
                <div class="checkout-form-container">
                    <?php if (!empty($errors)): ?>
                    <div class="alert alert-error">
                        <ul>
                            <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                    
                    <form id="checkout-form" action="checkout.php" method="post">
                        <div class="checkout-section-title">
                            <h2>Shipping Information</h2>
                        </div>
                        
                        <div class="addresses-container">
                            <?php if (empty($shippingAddresses)): ?>
                            <div class="no-addresses">
                                <p>You don't have any saved addresses. Please add one.</p>
                            </div>
                            <?php else: ?>
                            <div class="address-list">
                                <?php foreach ($shippingAddresses as $address): ?>
                                <div class="address-item">
                                    <input type="radio" name="shipping_address_id" id="address-<?php echo $address['address_id']; ?>" value="<?php echo $address['address_id']; ?>" <?php echo $address['is_default'] ? 'checked' : ''; ?>>
                                    <label for="address-<?php echo $address['address_id']; ?>">
                                        <div class="address-details">
                                            <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong>
                                            <p><?php echo htmlspecialchars($address['address_line1']); ?></p>
                                            <?php if (!empty($address['address_line2'])): ?>
                                            <p><?php echo htmlspecialchars($address['address_line2']); ?></p>
                                            <?php endif; ?>
                                            <p><?php echo htmlspecialchars($address['city']) . ', ' . htmlspecialchars($address['state']) . ' ' . htmlspecialchars($address['postal_code']); ?></p>
                                            <p><?php echo htmlspecialchars($address['country']); ?></p>
                                            <?php if (!empty($address['phone'])): ?>
                                            <p>Phone: <?php echo htmlspecialchars($address['phone']); ?></p>
                                            <?php endif; ?>
                                            <?php if ($address['is_default']): ?>
                                            <span class="default-label">Default</span>
                                            <?php endif; ?>
                                        </div>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                            
                            <div class="add-address">
                                <a href="account-addresses.php?redirect=checkout.php" class="btn btn-secondary">Add New Address</a>
                            </div>
                        </div>
                        
                        <div class="checkout-section-title">
                            <h2>Payment Method</h2>
                        </div>
                        
                        <div class="payment-methods-container">
                            <?php if (empty($paymentMethods)): ?>
                            <div class="no-payment-methods">
                                <p>You don't have any saved payment methods. Please add one.</p>
                            </div>
                            <?php else: ?>
                            <div class="payment-method-list">
                                <?php foreach ($paymentMethods as $payment): ?>
                                <div class="payment-method-item">
                                    <input type="radio" name="payment_method_id" id="payment-<?php echo $payment['payment_method_id']; ?>" value="<?php echo $payment['payment_method_id']; ?>" <?php echo $payment['is_default'] ? 'checked' : ''; ?>>
                                    <label for="payment-<?php echo $payment['payment_method_id']; ?>">
                                        <div class="payment-method-details">
                                            <div class="payment-method-icon">
                                                <?php 
                                                $iconFile = strtolower($payment['provider']) . '.png';
                                                if (file_exists('assets/icons/' . $iconFile)): 
                                                ?>
                                                <img src="assets/icons/<?php echo $iconFile; ?>" alt="<?php echo htmlspecialchars($payment['provider']); ?>">
                                                <?php else: ?>
                                                <span class="payment-method-name"><?php echo htmlspecialchars($payment['provider']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="payment-method-info">
                                                <strong><?php echo htmlspecialchars($payment['payment_type']); ?></strong>
                                                <?php if (!empty($payment['account_number_last4'])): ?>
                                                <p>Ending in <?php echo htmlspecialchars($payment['account_number_last4']); ?></p>
                                                <?php endif; ?>
                                                <?php if (!empty($payment['expiry_date'])): ?>
                                                <p>Expires: <?php echo htmlspecialchars($payment['expiry_date']); ?></p>
                                                <?php endif; ?>
                                                <?php if ($payment['is_default']): ?>
                                                <span class="default-label">Default</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                            
                            <div class="add-payment-method">
                                <a href="account-payment-methods.php?redirect=checkout.php" class="btn btn-secondary">Add New Payment Method</a>
                            </div>
                        </div>
                        
                        <div class="checkout-section-title">
                            <h2>Order Notes</h2>
                        </div>
                        
                        <div class="form-group">
                            <textarea name="order_notes" placeholder="Special instructions for your order (optional)"></textarea>
                        </div>
                        
                        <div class="checkout-actions">
                            <a href="cart.php" class="btn btn-outlined">Back to Cart</a>
                            <button type="submit" class="btn btn-primary">Place Order</button>
                        </div>
                    </form>
                </div>
                
                <div class="checkout-summary">
                    <h2>Order Summary</h2>
                    
                    <div class="summary-items">
                        <?php foreach ($cartItems as $item): ?>
                        <div class="summary-item">
                            <div class="item-image">
                                <img src="<?php echo $item['image'] ?? 'assets/images/image-product-1-thumbnail.jpg'; ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
                                <span class="item-quantity"><?php echo $item['quantity']; ?></span>
                            </div>
                            <div class="item-details">
                                <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                                <p><?php echo formatPrice($item['price']); ?> x <?php echo $item['quantity']; ?></p>
                            </div>
                            <div class="item-price">
                                <?php echo formatPrice($item['price'] * $item['quantity']); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="summary-totals">
                        <div class="summary-row">
                            <span>Subtotal</span>
                            <span><?php echo formatPrice($subtotal); ?></span>
                        </div>
                        
                        <div class="summary-row">
                            <span>Shipping</span>
                            <span><?php echo formatPrice($shipping); ?></span>
                        </div>
                        
                        <div class="summary-row">
                            <span>Tax (<?php echo $taxRate; ?>%)</span>
                            <span><?php echo formatPrice($tax); ?></span>
                        </div>
                        
                        <?php if ($discount > 0): ?>
                        <div class="summary-row discount-row">
                            <span>Discount (<?php echo htmlspecialchars($couponCode); ?>)</span>
                            <span>-<?php echo formatPrice($discount); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="summary-row total-row">
                            <span>Total</span>
                            <span><?php echo formatPrice($total); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const checkoutForm = document.getElementById('checkout-form');
        
        if (checkoutForm) {
            checkoutForm.addEventListener('submit', function(event) {
                let isValid = true;
                
                // Check if shipping address is selected
                const shippingAddressRadios = document.querySelectorAll('input[name="shipping_address_id"]');
                if (shippingAddressRadios.length > 0) {
                    let shippingAddressSelected = false;
                    
                    shippingAddressRadios.forEach(radio => {
                        if (radio.checked) {
                            shippingAddressSelected = true;
                        }
                    });
                    
                    if (!shippingAddressSelected) {
                        isValid = false;
                        alert('Please select a shipping address.');
                    }
                } else {
                    isValid = false;
                    alert('Please add a shipping address.');
                }
                
                // Check if payment method is selected
                const paymentMethodRadios = document.querySelectorAll('input[name="payment_method_id"]');
                if (paymentMethodRadios.length > 0) {
                    let paymentMethodSelected = false;
                    
                    paymentMethodRadios.forEach(radio => {
                        if (radio.checked) {
                            paymentMethodSelected = true;
                        }
                    });
                    
                    if (!paymentMethodSelected) {
                        isValid = false;
                        alert('Please select a payment method.');
                    }
                } else {
                    isValid = false;
                    alert('Please add a payment method.');
                }
                
                if (!isValid) {
                    event.preventDefault();
                }
            });
        }
    });
</script>

<?php
// Include the footer
include 'templates/footer.php';
?>
