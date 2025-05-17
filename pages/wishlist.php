<?php
/**
 * wishlist.php
 * Página de lista de deseos/favoritos
 */

// Include necessary files
require_once '../includes/bootstrap.php';

// Initialize session if not already done
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirect to login if user is not logged in
if (!isset($_SESSION['user'])) {
    // Save current URL to redirect back after login
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php');
    exit;
}

// Inicializar la base de datos
$db = Database::getInstance();

// Create wishlist table if it doesn't exist
try {
    $db->query("SHOW TABLES LIKE 'wishlist'");
    if ($db->rowCount() === 0) {
        // Crear la tabla wishlist
        $sql = "CREATE TABLE wishlist (
            wishlist_id INT(11) NOT NULL AUTO_INCREMENT,
            user_id INT(11) NOT NULL,
            product_id INT(11) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (wishlist_id),
            UNIQUE KEY wishlist_user_product (user_id, product_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $db->exec($sql);
    }
} catch (Exception $e) {
    // Si hay algún error, simplemente registrarlo y continuar
    error_log("Error al verificar/crear la tabla wishlist: " . $e->getMessage());
}

// Initialize wishlist for guest users if not already done
if (!isset($_SESSION['wishlist']) && isset($_SESSION['user']['guest'])) {
    $_SESSION['wishlist'] = [];
}

// Handle POST requests for wishlist operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // User ID
    $user_id = isset($_SESSION['user']['id']) ? $_SESSION['user']['id'] : null;
    
    // Add product to wishlist
    if (isset($_POST['add_to_wishlist']) && isset($_POST['product_id'])) {
        $product_id = (int)$_POST['product_id'];
        
        // Para usuarios registrados, guardar en la base de datos
        if ($user_id) {
            try {
                // Verificar si ya existe en la wishlist
                $checkStmt = $db->query("SELECT COUNT(*) as count FROM wishlist WHERE user_id = ? AND product_id = ?", [$user_id, $product_id]);
                $exists = $checkStmt->fetch()['count'] > 0;
                
                if (!$exists) {
                    // Insertar en la base de datos
                    $db->query("INSERT INTO wishlist (user_id, product_id, created_at) VALUES (?, ?, NOW())", [$user_id, $product_id]);
                    add_flash_message('¡Producto añadido a favoritos correctamente!', 'success');
                } else {
                    add_flash_message('El producto ya está en tu lista de favoritos', 'info');
                }
            } catch (Exception $e) {
                add_flash_message('Error al añadir producto a favoritos: ' . $e->getMessage(), 'error');
            }
        } 
        // Para usuarios invitados, usar la sesión
        else if (isset($_SESSION['user']['guest'])) {
            // Verificar si el producto ya existe en wishlist
            $exists = false;
            foreach ($_SESSION['wishlist'] as $item) {
                if (isset($item['product_id']) && $item['product_id'] == $product_id) {
                    $exists = true;
                    break;
                }
            }
            
            if (!$exists) {
                try {
                    // Obtener datos del producto
                    $stmt = $db->query("SELECT * FROM products WHERE product_id = ?", [$product_id]);
                    $product = $stmt->fetch();
                    
                    if ($product) {
                        // Obtener imagen del producto
                        $stmt = $db->query("SELECT image_path FROM product_images WHERE product_id = ? AND is_primary = 1 LIMIT 1", [$product_id]);
                        $image = $stmt->fetch();
                        
                        // Añadir a la wishlist de sesión
                        $_SESSION['wishlist'][] = [
                            'product_id' => $product_id,
                            'name' => $product['name'] ?? $product['title'] ?? 'Producto',
                            'price' => $product['price'] ?? 0,
                            'sale_price' => $product['sale_price'] ?? 0,
                            'image' => $image ? $image['image_path'] : '../assets/images/product-placeholder.jpg'
                        ];
                        
                        add_flash_message('¡Producto añadido a favoritos correctamente!', 'success');
                    } else {
                        add_flash_message('Producto no encontrado', 'error');
                    }
                } catch (Exception $e) {
                    add_flash_message('Error al añadir producto a favoritos: ' . $e->getMessage(), 'error');
                }
            } else {
                add_flash_message('El producto ya está en tu lista de favoritos', 'info');
            }
        }
        
        // Redirect to previous page or wishlist page
        if (isset($_SERVER['HTTP_REFERER'])) {
            header('Location: ' . $_SERVER['HTTP_REFERER']);
        } else {
            header('Location: wishlist.php');
        }
        exit;
    }
    
    // Remove from wishlist
    if (isset($_POST['remove_from_wishlist'])) {
        $product_id = $_POST['product_id'];
        
        foreach ($_SESSION['wishlist'] as $key => $item) {
            if ($item['product_id'] == $product_id) {
                unset($_SESSION['wishlist'][$key]);
                break;
            }
        }
        
        // Re-index array
        $_SESSION['wishlist'] = array_values($_SESSION['wishlist']);
        
        add_flash_message('Product removed from wishlist', 'info');
        
        // Redirect back to wishlist page
        header('Location: wishlist.php');
        exit;
    }
    
    // Move all to cart
    if (isset($_POST['move_all_to_cart'])) {
        if (!empty($_SESSION['wishlist'])) {
            foreach ($_SESSION['wishlist'] as $item) {
                // Add product to cart
                $result = add_to_cart($item['product_id'], 1);
            }
            
            // Clear wishlist
            $_SESSION['wishlist'] = [];
            
            add_flash_message('All items moved to cart successfully!', 'success');
        } else {
            add_flash_message('Wishlist is empty', 'info');
        }
        
        // Redirect back to wishlist page
        header('Location: wishlist.php');
        exit;
    }
    
    // Move to cart
    if (isset($_POST['move_to_cart'])) {
        $product_id = $_POST['product_id'];
        
        foreach ($_SESSION['wishlist'] as $key => $item) {
            if ($item['product_id'] == $product_id) {
                // Add product to cart
                $result = add_to_cart($item['product_id'], 1);
                
                // Remove from wishlist
                unset($_SESSION['wishlist'][$key]);
                break;
            }
        }
        
        // Re-index array
        $_SESSION['wishlist'] = array_values($_SESSION['wishlist']);
        
        add_flash_message('Product moved to cart', 'success');
        
        // Redirect back to wishlist page
        header('Location: wishlist.php');
        exit;
    }
}

// Get recommended products for "Just For You" section usando la función segura
// Esta función verifica automáticamente qué columnas existen en la tabla product
$recommended_products = getProductsSafely([], 4, true); // true para orden aleatorio

// Count wishlist items
$wishlist_count = count($_SESSION['wishlist']);

// Include header
$page_title = "My Wishlist";
load_header($page_title);
?>

<!-- Add wishlist CSS files -->
<link rel="stylesheet" href="../styles/wishlist.css">
<link rel="stylesheet" href="../styles/wishlist-modern.css">

<!-- Wishlist Section -->
<section class="wishlist-section">
    <div class="wishlist-header">
        <span class="wishlist-title">Wishlist (<?php echo $wishlist_count; ?>)</span>
        <form action="wishlist.php" method="post">
            <button type="submit" class="move-all-btn" name="move_all_to_cart">Move All To Bag</button>
        </form>
    </div>
    
    <div class="wishlist-cards">
        <?php if (empty($_SESSION['wishlist'])): ?>
            <div class="empty-wishlist">
                <i class="fas fa-heart-broken"></i>
                <p>Your wishlist is empty</p>
                <a href="../index.php" class="btn-shop-now">Shop Now</a>
            </div>
        <?php else: ?>
            <?php foreach ($_SESSION['wishlist'] as $item): ?>
                <div class="wishlist-card">
                    <div class="wishlist-card-image">
                        <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                        <div class="wishlist-card-actions">
                            <form action="wishlist.php" method="post">
                                <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                <button type="submit" name="remove_from_wishlist" class="action-btn remove-btn">
                                    <i class="fa fa-times"></i>
                                </button>
                            </form>
                            <form action="wishlist.php" method="post">
                                <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                <button type="submit" name="move_to_cart" class="action-btn cart-btn">
                                    <i class="fa fa-shopping-cart"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="wishlist-card-details">
                        <h3 class="wishlist-card-title"><?php echo htmlspecialchars($item['name']); ?></h3>
                        <div class="wishlist-card-price">
                            <?php if ($item['sale_price'] > 0): ?>
                                <span class="price"><?php echo formatMoney($item['sale_price']); ?></span>
                                <span class="old-price"><?php echo formatMoney($item['price']); ?></span>
                            <?php else: ?>
                                <span class="price"><?php echo formatMoney($item['price']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<!-- Just For You Section -->
<section class="just-for-you-section">
    <div class="just-for-you-header">
        <span class="just-for-you-label"><span class="dot"></span> Just For You</span>
        <a href="../index.php" class="see-all-btn">See All</a>
    </div>
    <div class="just-for-you-cards">
        <?php foreach ($recommended_products as $product): ?>
            <div class="product-card">
                <div class="product-card-image">
                    <img src="<?php echo htmlspecialchars($product['image'] ?? $product['main_image'] ?? $product['image_url'] ?? '../assets/images/product-placeholder.jpg'); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <div class="product-card-actions">
                        <form action="wishlist.php" method="post">
                            <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                            <button type="submit" name="add_to_wishlist" class="action-btn wishlist-btn">
                                <i class="fa fa-heart"></i>
                            </button>
                        </form>
                        <form action="../api/add_to_cart.php" method="post" class="cart-form">
                            <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" class="action-btn cart-btn">
                                <i class="fa fa-shopping-cart"></i>
                            </button>
                        </form>
                    </div>
                </div>
                <div class="product-card-details">
                    <h3 class="product-card-title">
                        <a href="ProductDetails.php?id=<?php echo $product['product_id']; ?>">
                            <?php echo htmlspecialchars($product['name']); ?>
                        </a>
                    </h3>
                    <div class="product-card-price">
                        <?php if ($product['sale_price'] > 0): ?>
                            <span class="price"><?php echo formatMoney($product['sale_price']); ?></span>
                            <span class="old-price"><?php echo formatMoney($product['price']); ?></span>
                        <?php else: ?>
                            <span class="price"><?php echo formatMoney($product['price']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Add wishlist JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle AJAX add to cart for the "Just For You" section
    const cartForms = document.querySelectorAll('.cart-form');
    cartForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(form);
            
            fetch('../api/add_to_cart.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update cart count in header
                    const cartCountElement = document.querySelector('.cart-count');
                    if (cartCountElement) {
                        cartCountElement.textContent = data.cart.count;
                    }
                    
                    // Show success message
                    alert('Product added to cart!');
                } else {
                    // Show error message
                    alert(data.message || 'Error adding product to cart');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error adding product to cart');
            });
        });
    });
});
</script>

<?php
// Include footer
load_footer();
?>
