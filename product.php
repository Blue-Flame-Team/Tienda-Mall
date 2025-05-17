<?php
/**
 * Tienda Mall E-commerce Platform
 * Product Detail Page
 */

// Include necessary files
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/db.php';
require_once 'includes/Product.php';

// Initialize variables
$product = null;
$relatedProducts = [];
$reviews = [];
$averageRating = 0;
$cartCount = 0;
$isLoggedIn = isLoggedIn();
$userName = '';

// Get product ID from URL
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($productId <= 0) {
    // Invalid product ID, redirect to products page
    redirect('products.php');
}

// Initialize Product object
$productObj = new Product();

// Get product details
$product = $productObj->getProductById($productId);

if (!$product) {
    // Product not found, redirect to products page
    redirect('products.php');
}

// Get product images
$images = $productObj->getProductImages($productId);

// Get product reviews
$db = Database::getInstance();
$stmt = $db->query("SELECT r.*, u.first_name, u.last_name 
                    FROM reviews r 
                    JOIN users u ON r.user_id = u.user_id 
                    WHERE r.product_id = ? AND r.is_approved = 1 
                    ORDER BY r.created_at DESC", [$productId]);
$reviews = $stmt->fetchAll();

// Calculate average rating
$stmt = $db->query("SELECT AVG(rating) as avg_rating FROM reviews WHERE product_id = ? AND is_approved = 1", [$productId]);
$result = $stmt->fetch();
$averageRating = $result ? round($result['avg_rating'], 1) : 0;

// Get related products (from same category)
$categories = $productObj->getProductCategories($productId);
if (!empty($categories)) {
    $categoryId = $categories[0]['category_id'];
    $relatedProducts = $productObj->getProducts(4, 0, ['category_id' => $categoryId]);
    
    // Remove current product from related products
    foreach ($relatedProducts as $key => $relatedProduct) {
        if ($relatedProduct['product_id'] == $productId) {
            unset($relatedProducts[$key]);
            break;
        }
    }
}

// Check if user is logged in
if ($isLoggedIn) {
    $userName = $_SESSION['user_name'] ?? '';
}

// Get cart information
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
    <section class="product-detail">
        <div class="container">
            <div class="product-breadcrumb">
                <a href="index.php">Home</a> &gt; 
                <?php if (!empty($categories)): ?>
                <a href="category.php?id=<?php echo $categories[0]['category_id']; ?>"><?php echo htmlspecialchars($categories[0]['name']); ?></a> &gt; 
                <?php endif; ?>
                <span><?php echo htmlspecialchars($product['title']); ?></span>
            </div>
            
            <div class="product-detail-container">
                <div class="product-images">
                    <div class="main-image">
                        <img id="main-product-image" src="<?php echo !empty($images) ? $images[0]['image_url'] : 'assets/images/image-product-1.jpg'; ?>" alt="<?php echo htmlspecialchars($product['title']); ?>">
                    </div>
                    <?php if (count($images) > 1): ?>
                    <div class="thumbnail-images">
                        <?php foreach ($images as $index => $image): ?>
                        <div class="thumbnail<?php echo $index === 0 ? ' active' : ''; ?>" data-image="<?php echo $image['image_url']; ?>">
                            <img src="<?php echo $image['image_url']; ?>" alt="Thumbnail <?php echo $index + 1; ?>">
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="product-info">
                    <h1><?php echo htmlspecialchars($product['title']); ?></h1>
                    
                    <div class="product-rating">
                        <div class="stars">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="star<?php echo $i <= $averageRating ? ' filled' : ''; ?>">★</span>
                            <?php endfor; ?>
                        </div>
                        <span class="rating-count">(<?php echo count($reviews); ?> reviews)</span>
                    </div>
                    
                    <div class="product-price">
                        <span class="current-price"><?php echo formatPrice($product['price']); ?></span>
                        <?php if (!empty($product['old_price'])): ?>
                        <span class="old-price"><?php echo formatPrice($product['old_price']); ?></span>
                        <span class="discount">
                            <?php echo round((($product['old_price'] - $product['price']) / $product['old_price']) * 100); ?>% OFF
                        </span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="product-description">
                        <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                    </div>
                    
                    <div class="product-availability">
                        <?php
                        $inStock = isset($product['quantity']) && $product['quantity'] > $product['reserved_quantity'];
                        $stockLevel = $inStock ? $product['quantity'] - $product['reserved_quantity'] : 0;
                        ?>
                        <span class="<?php echo $inStock ? 'in-stock' : 'out-of-stock'; ?>">
                            <?php echo $inStock ? 'In Stock' : 'Out of Stock'; ?>
                            <?php if ($inStock && $stockLevel < 10): ?>
                            (Only <?php echo $stockLevel; ?> left)
                            <?php endif; ?>
                        </span>
                    </div>
                    
                    <?php if ($inStock): ?>
                    <form id="add-to-cart-form" class="add-to-cart-form">
                        <div class="quantity-control">
                            <button type="button" class="quantity-decrease">-</button>
                            <input type="number" name="quantity" id="quantity" value="1" min="1" max="<?php echo $stockLevel; ?>">
                            <button type="button" class="quantity-increase">+</button>
                        </div>
                        
                        <button type="submit" class="btn btn-add-to-cart" data-product-id="<?php echo $product['product_id']; ?>">
                            <img src="assets/images/icon-cart.svg" alt="Cart"> Add to Cart
                        </button>
                        
                        <button type="button" class="btn btn-wishlist" data-product-id="<?php echo $product['product_id']; ?>">
                            <img src="assets/icons/Wishlist.png" alt="Wishlist"> Add to Wishlist
                        </button>
                    </form>
                    <?php endif; ?>
                    
                    <div class="product-meta">
                        <p><strong>SKU:</strong> <?php echo htmlspecialchars($product['sku']); ?></p>
                        <?php if (!empty($categories)): ?>
                        <p>
                            <strong>Categories:</strong> 
                            <?php 
                            $categoryLinks = [];
                            foreach ($categories as $category) {
                                $categoryLinks[] = '<a href="category.php?id=' . $category['category_id'] . '">' . htmlspecialchars($category['name']) . '</a>';
                            }
                            echo implode(', ', $categoryLinks);
                            ?>
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Product Tabs: Description, Specifications, Reviews -->
            <div class="product-tabs">
                <div class="tabs-navigation">
                    <button class="tab-button active" data-tab="description">Description</button>
                    <button class="tab-button" data-tab="specifications">Specifications</button>
                    <button class="tab-button" data-tab="reviews">Reviews (<?php echo count($reviews); ?>)</button>
                </div>
                
                <div class="tab-content">
                    <div id="description" class="tab-pane active">
                        <div class="tab-pane-content">
                            <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                        </div>
                    </div>
                    
                    <div id="specifications" class="tab-pane">
                        <div class="tab-pane-content">
                            <table class="specifications-table">
                                <tbody>
                                    <tr>
                                        <th>Weight</th>
                                        <td><?php echo !empty($product['weight']) ? $product['weight'] . ' kg' : 'N/A'; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Dimensions</th>
                                        <td><?php echo !empty($product['dimensions']) ? htmlspecialchars($product['dimensions']) : 'N/A'; ?></td>
                                    </tr>
                                    <!-- Add more specifications as needed -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div id="reviews" class="tab-pane">
                        <div class="tab-pane-content">
                            <div class="reviews-summary">
                                <div class="average-rating">
                                    <h3><?php echo $averageRating; ?></h3>
                                    <div class="stars">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span class="star<?php echo $i <= $averageRating ? ' filled' : ''; ?>">★</span>
                                        <?php endfor; ?>
                                    </div>
                                    <p>Based on <?php echo count($reviews); ?> reviews</p>
                                </div>
                                
                                <?php if ($isLoggedIn): ?>
                                <div class="write-review">
                                    <a href="write-review.php?product_id=<?php echo $product['product_id']; ?>" class="btn btn-secondary">Write a Review</a>
                                </div>
                                <?php else: ?>
                                <div class="login-to-review">
                                    <p>Please <a href="login.php?redirect=<?php echo urlencode('product.php?id=' . $product['product_id']); ?>">login</a> to write a review.</p>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="reviews-list">
                                <?php if (empty($reviews)): ?>
                                <p>No reviews yet. Be the first to review this product!</p>
                                <?php else: ?>
                                <?php foreach ($reviews as $review): ?>
                                <div class="review-item">
                                    <div class="review-header">
                                        <div class="reviewer-info">
                                            <span class="reviewer-name"><?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name'][0] . '.'); ?></span>
                                            <span class="review-date"><?php echo date('F j, Y', strtotime($review['created_at'])); ?></span>
                                        </div>
                                        <div class="review-rating">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <span class="star<?php echo $i <= $review['rating'] ? ' filled' : ''; ?>">★</span>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    
                                    <?php if (!empty($review['title'])): ?>
                                    <h4 class="review-title"><?php echo htmlspecialchars($review['title']); ?></h4>
                                    <?php endif; ?>
                                    
                                    <div class="review-content">
                                        <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                                    </div>
                                    
                                    <?php if ($review['is_verified_purchase']): ?>
                                    <div class="verified-purchase">
                                        <span>Verified Purchase</span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Related Products -->
            <?php if (!empty($relatedProducts)): ?>
            <div class="related-products">
                <h2>Related Products</h2>
                <div class="product-grid">
                    <?php foreach ($relatedProducts as $relatedProduct): ?>
                    <div class="product-card">
                        <div class="product-image">
                            <a href="product.php?id=<?php echo $relatedProduct['product_id']; ?>">
                                <img src="<?php echo $relatedProduct['image_url'] ?? 'assets/images/image-product-1.jpg'; ?>" alt="<?php echo htmlspecialchars($relatedProduct['title']); ?>">
                            </a>
                        </div>
                        <div class="product-info">
                            <h3><a href="product.php?id=<?php echo $relatedProduct['product_id']; ?>"><?php echo htmlspecialchars($relatedProduct['title']); ?></a></h3>
                            <div class="product-price">
                                <span class="current-price"><?php echo formatPrice($relatedProduct['price']); ?></span>
                                <?php if (!empty($relatedProduct['old_price'])): ?>
                                <span class="old-price"><?php echo formatPrice($relatedProduct['old_price']); ?></span>
                                <?php endif; ?>
                            </div>
                            <button class="btn btn-add-to-cart" data-product-id="<?php echo $relatedProduct['product_id']; ?>">Add to Cart</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Product image gallery
        const thumbnails = document.querySelectorAll('.thumbnail');
        const mainImage = document.getElementById('main-product-image');
        
        thumbnails.forEach(thumbnail => {
            thumbnail.addEventListener('click', function() {
                // Update main image
                mainImage.src = this.getAttribute('data-image');
                
                // Update active thumbnail
                thumbnails.forEach(thumb => thumb.classList.remove('active'));
                this.classList.add('active');
            });
        });
        
        // Quantity control
        const quantityInput = document.getElementById('quantity');
        const decreaseBtn = document.querySelector('.quantity-decrease');
        const increaseBtn = document.querySelector('.quantity-increase');
        
        if (quantityInput && decreaseBtn && increaseBtn) {
            decreaseBtn.addEventListener('click', function() {
                let value = parseInt(quantityInput.value);
                if (value > 1) {
                    quantityInput.value = value - 1;
                }
            });
            
            increaseBtn.addEventListener('click', function() {
                let value = parseInt(quantityInput.value);
                let max = parseInt(quantityInput.getAttribute('max'));
                if (value < max) {
                    quantityInput.value = value + 1;
                }
            });
        }
        
        // Add to cart functionality
        const addToCartForm = document.getElementById('add-to-cart-form');
        if (addToCartForm) {
            addToCartForm.addEventListener('submit', function(event) {
                event.preventDefault();
                
                const productId = document.querySelector('.btn-add-to-cart').getAttribute('data-product-id');
                const quantity = parseInt(quantityInput.value);
                
                // AJAX call to add item to cart
                fetch('api/cart/add', {
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
                        // Update cart count in the UI
                        const cartCountElement = document.querySelector('.cart-count');
                        if (cartCountElement) {
                            cartCountElement.textContent = data.data.item_count;
                        }
                        
                        alert('Product added to cart!');
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                });
            });
        }
        
        // Tabs functionality
        const tabButtons = document.querySelectorAll('.tab-button');
        const tabPanes = document.querySelectorAll('.tab-pane');
        
        tabButtons.forEach(button => {
            button.addEventListener('click', function() {
                const tabId = this.getAttribute('data-tab');
                
                // Update active tab button
                tabButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                
                // Update active tab pane
                tabPanes.forEach(pane => {
                    pane.classList.remove('active');
                    if (pane.id === tabId) {
                        pane.classList.add('active');
                    }
                });
            });
        });
        
        // Related products add to cart
        document.querySelectorAll('.product-grid .btn-add-to-cart').forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.getAttribute('data-product-id');
                
                // AJAX call to add item to cart
                fetch('api/cart/add', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        product_id: productId,
                        quantity: 1
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        // Update cart count in the UI
                        const cartCountElement = document.querySelector('.cart-count');
                        if (cartCountElement) {
                            cartCountElement.textContent = data.data.item_count;
                        }
                        
                        alert('Product added to cart!');
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                });
            });
        });
    });
</script>

<?php
// Include the footer
include 'templates/footer.php';
?>

<!-- Cart API Fix Script -->
<script src="product-detail-fix.js"></script>
