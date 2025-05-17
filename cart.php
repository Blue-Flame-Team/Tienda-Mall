<?php
/**
 * Tienda Mall E-commerce Platform
 * Shopping Cart Page
 */

// Include necessary files
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/db.php';
require_once 'includes/Cart.php';

// Initialize variables
$cartObj = new Cart();
$cartItems = $cartObj->getItems();
$subtotal = $cartObj->getSubtotal();
$cartCount = $cartObj->getItemCount();
$isLoggedIn = isLoggedIn();
$userName = '';

// Calculate shipping, tax, and total
$shippingMethod = $_SESSION['shipping_method'] ?? 'standard';
$shipping = $cartObj->calculateShippingFee($subtotal, $shippingMethod);
$taxRate = 10; // Default tax rate
$tax = $cartObj->calculateTax($subtotal, $taxRate);

// Apply coupon if available
$discount = 0;
$couponCode = '';
$couponApplied = false;

if (isset($_SESSION['coupon'])) {
    $coupon = $_SESSION['coupon'];
    $discount = $coupon['discount_amount'];
    $couponCode = $coupon['code'];
    $couponApplied = true;
}

// Calculate total
$total = $cartObj->calculateTotal($subtotal, $tax, $shipping, $discount);

// Check if user is logged in
if ($isLoggedIn) {
    $userName = $_SESSION['user_name'] ?? '';
}

// Handle coupon form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_coupon'])) {
    $submittedCouponCode = sanitize($_POST['coupon_code'] ?? '');
    
    if (!empty($submittedCouponCode)) {
        $coupon = $cartObj->applyCoupon($submittedCouponCode);
        
        if ($coupon) {
            $discount = $cartObj->calculateDiscount($subtotal, $coupon);
            
            $_SESSION['coupon'] = [
                'code' => $coupon['code'],
                'discount_type' => $coupon['discount_type'],
                'discount_value' => $coupon['discount_value'],
                'discount_amount' => $discount
            ];
            
            $couponApplied = true;
            $couponCode = $coupon['code'];
            
            // Recalculate total
            $total = $cartObj->calculateTotal($subtotal, $tax, $shipping, $discount);
            
            // Redirect to prevent form resubmission
            redirect('cart.php?coupon_applied=1');
        } else {
            $couponError = 'Invalid or expired coupon code.';
        }
    }
}

// Handle remove coupon
if (isset($_GET['remove_coupon']) && $_GET['remove_coupon'] === '1') {
    if (isset($_SESSION['coupon'])) {
        unset($_SESSION['coupon']);
        $discount = 0;
        $couponApplied = false;
        $couponCode = '';
        
        // Recalculate total
        $total = $cartObj->calculateTotal($subtotal, $tax, $shipping, $discount);
        
        // Redirect to prevent query string persistence
        redirect('cart.php?coupon_removed=1');
    }
}

// Handle shipping method change
if (isset($_GET['shipping_method'])) {
    $newShippingMethod = sanitize($_GET['shipping_method']);
    
    if (in_array($newShippingMethod, ['standard', 'express'])) {
        $_SESSION['shipping_method'] = $newShippingMethod;
        $shippingMethod = $newShippingMethod;
        $shipping = $cartObj->calculateShippingFee($subtotal, $shippingMethod);
        
        // Recalculate total
        $total = $cartObj->calculateTotal($subtotal, $tax, $shipping, $discount);
        
        // Redirect to prevent query string persistence
        redirect('cart.php?shipping_updated=1');
    }
}

// Include the header
include 'templates/header.php';
?>

<!-- Main Content -->
<main>
    <section class="cart-section">
        <div class="container">
            <h1>Shopping Cart</h1>
            
            <?php if (empty($cartItems)): ?>
            <div class="empty-cart">
                <div class="empty-cart-message">
                    <img src="assets/icons/icon-mallbag.png" alt="Empty Cart">
                    <h2>Your cart is empty</h2>
                    <p>Looks like you haven't added anything to your cart yet.</p>
                    <a href="products.php" class="btn btn-primary">Continue Shopping</a>
                </div>
            </div>
            <?php else: ?>
            <div class="cart-container">
                <div class="cart-items">
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Total</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cartItems as $item): ?>
                            <tr data-product-id="<?php echo $item['product_id']; ?>">
                                <td class="product-info">
                                    <div class="product-image">
                                        <img src="<?php echo $item['image'] ?? 'assets/images/image-product-1-thumbnail.jpg'; ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
                                    </div>
                                    <div class="product-details">
                                        <h3><a href="product.php?id=<?php echo $item['product_id']; ?>"><?php echo htmlspecialchars($item['title']); ?></a></h3>
                                    </div>
                                </td>
                                <td class="product-price"><?php echo formatPrice($item['price']); ?></td>
                                <td class="product-quantity">
                                    <div class="quantity-control">
                                        <button type="button" class="quantity-decrease" data-product-id="<?php echo $item['product_id']; ?>">-</button>
                                        <input type="number" class="quantity-input" value="<?php echo $item['quantity']; ?>" min="1" data-product-id="<?php echo $item['product_id']; ?>">
                                        <button type="button" class="quantity-increase" data-product-id="<?php echo $item['product_id']; ?>">+</button>
                                    </div>
                                </td>
                                <td class="product-total"><?php echo formatPrice($item['price'] * $item['quantity']); ?></td>
                                <td class="product-remove">
                                    <button type="button" class="btn-remove" data-product-id="<?php echo $item['product_id']; ?>">
                                        <img src="assets/icons/icon-cancel.png" alt="Remove">
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div class="cart-actions">
                        <a href="products.php" class="btn btn-outlined">Continue Shopping</a>
                        <button id="clear-cart" class="btn btn-outlined">Clear Cart</button>
                    </div>
                </div>
                
                <div class="cart-sidebar">
                    <div class="cart-summary">
                        <h2>Order Summary</h2>
                        
                        <div class="summary-row">
                            <span>Subtotal</span>
                            <span id="cart-subtotal"><?php echo formatPrice($subtotal); ?></span>
                        </div>
                        
                        <div class="shipping-options">
                            <h3>Shipping</h3>
                            <div class="shipping-option">
                                <input type="radio" id="shipping-standard" name="shipping_method" value="standard" <?php echo $shippingMethod === 'standard' ? 'checked' : ''; ?>>
                                <label for="shipping-standard">Standard Delivery <?php echo $subtotal >= 100 ? '(Free)' : '($8.00)'; ?></label>
                            </div>
                            <div class="shipping-option">
                                <input type="radio" id="shipping-express" name="shipping_method" value="express" <?php echo $shippingMethod === 'express' ? 'checked' : ''; ?>>
                                <label for="shipping-express">Express Delivery ($15.00)</label>
                            </div>
                        </div>
                        
                        <div class="summary-row">
                            <span>Shipping</span>
                            <span id="cart-shipping"><?php echo formatPrice($shipping); ?></span>
                        </div>
                        
                        <div class="summary-row">
                            <span>Tax (<?php echo $taxRate; ?>%)</span>
                            <span id="cart-tax"><?php echo formatPrice($tax); ?></span>
                        </div>
                        
                        <?php if ($couponApplied): ?>
                        <div class="summary-row discount-row">
                            <span>Discount (<?php echo htmlspecialchars($couponCode); ?>)</span>
                            <span id="cart-discount">-<?php echo formatPrice($discount); ?></span>
                            <a href="cart.php?remove_coupon=1" class="remove-coupon">Remove</a>
                        </div>
                        <?php endif; ?>
                        
                        <div class="coupon-form">
                            <form action="cart.php" method="post">
                                <div class="form-group">
                                    <input type="text" name="coupon_code" placeholder="Enter coupon code" required>
                                    <button type="submit" name="apply_coupon" class="btn btn-secondary">Apply</button>
                                </div>
                                <?php if (isset($couponError)): ?>
                                <div class="coupon-error"><?php echo $couponError; ?></div>
                                <?php endif; ?>
                            </form>
                        </div>
                        
                        <div class="summary-row total-row">
                            <span>Total</span>
                            <span id="cart-total"><?php echo formatPrice($total); ?></span>
                        </div>
                        
                        <div class="checkout-button">
                            <a href="checkout.php" class="btn btn-primary btn-block">Proceed to Checkout</a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Update quantity
        function updateQuantity(productId, quantity) {
            fetch('api/cart/update', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    product_id: productId,
                    quantity: quantity
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    updateCartUI(data.data);
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        }
        
        // Remove item from cart
        function removeItem(productId) {
            fetch('api/cart/remove', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    product_id: productId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Remove row from table
                    const row = document.querySelector(`tr[data-product-id="${productId}"]`);
                    if (row) {
                        row.remove();
                    }
                    
                    // Check if cart is empty
                    if (data.data.items.length === 0) {
                        location.reload(); // Reload to show empty cart message
                    } else {
                        updateCartUI(data.data);
                    }
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        }
        
        // Clear cart
        function clearCart() {
            fetch('api/cart/clear', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    location.reload(); // Reload to show empty cart message
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        }
        
        // Update cart UI
        function updateCartUI(data) {
            // Update cart count
            const cartCountElement = document.querySelector('.cart-count');
            if (cartCountElement) {
                cartCountElement.textContent = data.item_count;
            }
            
            // Update subtotal, tax, shipping, discount, and total
            const subtotalElement = document.getElementById('cart-subtotal');
            const taxElement = document.getElementById('cart-tax');
            const shippingElement = document.getElementById('cart-shipping');
            const discountElement = document.getElementById('cart-discount');
            const totalElement = document.getElementById('cart-total');
            
            if (subtotalElement) {
                subtotalElement.textContent = data.subtotal_formatted;
            }
            
            // Recalculate tax
            if (taxElement) {
                const taxRate = <?php echo $taxRate; ?>;
                const tax = (data.subtotal * taxRate) / 100;
                taxElement.textContent = '$' + tax.toFixed(2);
            }
            
            // Get current shipping value
            let shipping = <?php echo $shipping; ?>;
            
            // Calculate discount
            let discount = <?php echo $discount; ?>;
            
            // Calculate total
            const total = data.subtotal + parseFloat(shipping) + parseFloat(tax) - parseFloat(discount);
            
            if (totalElement) {
                totalElement.textContent = '$' + total.toFixed(2);
            }
            
            // Update row totals
            for (const productId in data.items) {
                const item = data.items[productId];
                const row = document.querySelector(`tr[data-product-id="${productId}"]`);
                
                if (row) {
                    const quantityInput = row.querySelector('.quantity-input');
                    const totalCell = row.querySelector('.product-total');
                    
                    if (quantityInput) {
                        quantityInput.value = item.quantity;
                    }
                    
                    if (totalCell) {
                        totalCell.textContent = '$' + (item.price * item.quantity).toFixed(2);
                    }
                }
            }
        }
        
        // Quantity decrease button
        document.querySelectorAll('.quantity-decrease').forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.getAttribute('data-product-id');
                const input = document.querySelector(`.quantity-input[data-product-id="${productId}"]`);
                let value = parseInt(input.value);
                
                if (value > 1) {
                    value--;
                    input.value = value;
                    updateQuantity(productId, value);
                }
            });
        });
        
        // Quantity increase button
        document.querySelectorAll('.quantity-increase').forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.getAttribute('data-product-id');
                const input = document.querySelector(`.quantity-input[data-product-id="${productId}"]`);
                let value = parseInt(input.value);
                
                value++;
                input.value = value;
                updateQuantity(productId, value);
            });
        });
        
        // Quantity input change
        document.querySelectorAll('.quantity-input').forEach(input => {
            input.addEventListener('change', function() {
                const productId = this.getAttribute('data-product-id');
                let value = parseInt(this.value);
                
                if (isNaN(value) || value < 1) {
                    value = 1;
                    this.value = value;
                }
                
                updateQuantity(productId, value);
            });
        });
        
        // Remove item button
        document.querySelectorAll('.btn-remove').forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.getAttribute('data-product-id');
                
                if (confirm('Are you sure you want to remove this item from your cart?')) {
                    removeItem(productId);
                }
            });
        });
        
        // Clear cart button
        const clearCartButton = document.getElementById('clear-cart');
        if (clearCartButton) {
            clearCartButton.addEventListener('click', function() {
                if (confirm('Are you sure you want to clear your cart?')) {
                    clearCart();
                }
            });
        }
        
        // Shipping method change
        document.querySelectorAll('input[name="shipping_method"]').forEach(input => {
            input.addEventListener('change', function() {
                const shippingMethod = this.value;
                window.location.href = `cart.php?shipping_method=${shippingMethod}`;
            });
        });
    });
</script>

<?php
// Include the footer
include 'templates/footer.php';
?>
