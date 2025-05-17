<?php
/**
 * Página principal de Tienda Mall
 * Esta página muestra los productos destacados, categorías y ofertas
 */

// Cargar el bootstrap del sistema que inicializa todas las dependencias
require_once 'includes/bootstrap.php';

// Obtener productos destacados para la sección Flash Sale
$flashSaleProducts = getFeaturedProducts(8);

// Obtener todas las categorías
$categories = getAllCategories();

// Filtrar categorías con productos (máximo 8)
$categories_with_products = [];
foreach ($categories as $category) {
    // Contar productos en esta categoría
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE category_id = :category_id AND is_active = 1");
        $stmt->bindParam(':category_id', $category['category_id']);
        $stmt->execute();
        $product_count = $stmt->fetchColumn();
        
        if ($product_count > 0) {
            $category['product_count'] = $product_count;
            $categories_with_products[] = $category;
            
            // Limitar a 8 categorías
            if (count($categories_with_products) >= 8) {
                break;
            }
        }
    } catch (PDOException $e) {
        error_log('Error contando productos por categoría: ' . $e->getMessage());
    }
}

// Obtener productos más vendidos
$bestSellingProducts = [];
try {
    // Consultar productos con más ventas
    $stmt = $conn->prepare(
        "SELECT p.product_id, COUNT(oi.order_item_id) as sales_count
         FROM products p 
         LEFT JOIN order_items oi ON p.product_id = oi.product_id 
         WHERE p.is_active = 1 
         GROUP BY p.product_id 
         ORDER BY sales_count DESC, p.created_at DESC 
         LIMIT 8"
    );
    $stmt->execute();
    $best_selling_ids = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    
    // Si no hay ventas suficientes, complementar con productos recientes
    if (count($best_selling_ids) < 8) {
        $stmt = $conn->prepare(
            "SELECT product_id FROM products 
             WHERE is_active = 1 AND product_id NOT IN (" . implode(',', $best_selling_ids ?: [0]) . ") 
             ORDER BY created_at DESC 
             LIMIT " . (8 - count($best_selling_ids))
        );
        $stmt->execute();
        $recent_ids = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        $best_selling_ids = array_merge($best_selling_ids, $recent_ids);
    }
    
    // Obtener detalles completos de cada producto
    if (!empty($best_selling_ids)) {
        foreach ($best_selling_ids as $product_id) {
            $product = getProductById($product_id);
            if ($product) {
                $bestSellingProducts[] = $product;
            }
        }
    }
} catch (PDOException $e) {
    error_log('Error obteniendo productos más vendidos: ' . $e->getMessage());
}

// Obtener todos los productos para la sección "Our Products"
// Usando la función getAllProducts
$allProducts = getAllProducts([], 'created_at', 'DESC', 8);

// Asegurarse de que todas las imágenes tienen rutas correctas
foreach ($flashSaleProducts as &$product) {
    if (!isset($product['image_path']) || empty($product['image_path'])) {
        if (!empty($product['primary_image'])) {
            $product['image_path'] = $product['primary_image'];
        } elseif (!empty($product['primary_image_url'])) {
            $product['image_path'] = $product['primary_image_url'];
        } else {
            $product['image_path'] = SITE_URL . '/assets/images/product-placeholder.png';
        }
    }
}

// Compatibilidad con plantillas anteriores que usan 'categorías' en lugar de 'categories_with_products'
$categories = $categories_with_products;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tienda - Modern Ecommerce</title>
    <link rel="stylesheet" href="styles/style.css">
    <link rel="stylesheet" href="styles/mobile-nav.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles/loader.css">
    <!-- إضافة ملفات JavaScript الضرورية لتشغيل سلة التسوق -->
    <!-- إزالة cart.js لمنع التعارض -->
    <script src="scripts/image_path_fix.js" defer></script>
    <script src="scripts/loader.js" defer></script>
    <script src="scripts/mobile-nav.js" defer></script>
    <script src="scripts/tienda-cart.js" defer></script>
</head>
<body>
    <!-- Top Bar -->
    <div class="top-bar">
        <div class="container top-bar-flex">
            <span class="top-bar-message">Summer Sale For All Swim Suits And Free Express Delivery - OFF 50%! <a href="#">ShopNow</a></span>
            <div class="top-bar-right">
                <span>English</span>
                <i class="fa fa-chevron-down"></i>
            </div>
        </div>
    </div>
    <!-- Header / Navbar -->
    <header>
        <div class="container nav-container">
            <div class="logo">Tienda</div>
<button class="mobile-menu-btn" aria-label="Open Menu"><i class="fa fa-bars"></i></button>
            <nav>
                <a href="index.php" class="active-link">Home</a>
                <a href="pages/contact.php">Contact</a>
                <a href="pages/about.php">About</a>
                <?php if (isset($_SESSION['user'])): ?>
                <a href="pages/account.php">My Account</a>
                <a href="pages/logout.php">Logout</a>
                <?php else: ?>
                <a href="pages/login.php">Login</a>
                <a href="pages/signup.php">Sign Up</a>
                <?php endif; ?>
            </nav>
            <div class="search-cart">
                <div class="search-box">
                    <input type="text" placeholder="What are you looking for?">
                    <span class="search-icon"><img src="assets/icons/Saearch.png" alt="Search" class="icon-img"></span>
                </div>
                <a href="pages/wishlist.php" class="icon-link"><img src="assets/icons/Wishlist.png" alt="Wishlist" class="icon-img"></a>
                <a href="pages/cart.php" class="icon-link" style="position:relative;">
                  <img src="assets/icons/Cart1.png" alt="Cart" class="icon-img">
                  <span class="nav-cart-after" style="position:absolute;top:-8px;right:-8px;background:#DB4444;color:#fff;font-size:0.9em;padding:2px 7px;border-radius:50%;display:none;z-index:2;">0</span>
                </a>
                <?php if (isset($_SESSION['user'])): ?>
                <div class="profile-menu-container" style="display:block; position:relative;">
                  <div class="profile-icon" id="profileIcon" style="width:40px;height:40px;border-radius:50%;background:#DB4444;display:flex;align-items:center;justify-content:center;cursor:pointer;">
                    <svg width="22" height="22" fill="#fff" viewBox="0 0 24 24"><path d="M12 12c2.7 0 5-2.3 5-5s-2.3-5-5-5-5 2.3-5 5 2.3 5 5 5zm0 2c-3.3 0-10 1.7-10 5v3h20v-3c0-3.3-6.7-5-10-5z"/></svg>
                  </div>
                  <div class="profile-dropdown" id="profileDropdown" style="display:none;position:absolute;top:48px;right:0;background:linear-gradient(135deg,#444 60%,#b47cff 100%);border-radius:16px;box-shadow:0 2px 16px rgba(0,0,0,0.18);padding:18px 0 10px 0;min-width:220px;z-index:100;">
                     <a href="pages/account.php" class="profile-dropdown-item" style="display:flex;align-items:center;padding:10px 22px;color:#fff;text-decoration:none;font-size:1.1rem;gap:14px;"><span style="font-size:1.4em;">👤</span>Manage My Account</a>
                     <a href="pages/orders.php" class="profile-dropdown-item" style="display:flex;align-items:center;padding:10px 22px;color:#fff;text-decoration:none;font-size:1.1rem;gap:14px;"><span style="font-size:1.4em;">📦</span>My Orders</a>
                     <a href="pages/wishlist.php" class="profile-dropdown-item" style="display:flex;align-items:center;padding:10px 22px;color:#fff;text-decoration:none;font-size:1.1rem;gap:14px;"><span style="font-size:1.4em;">❤️</span>My Wishlist</a>
                     <a href="pages/account.php?section=reviews" class="profile-dropdown-item" style="display:flex;align-items:center;padding:10px 22px;color:#fff;text-decoration:none;font-size:1.1rem;gap:14px;"><span style="font-size:1.4em;">⭐</span>My Reviews</a>
                     <a href="pages/logout.php" class="profile-dropdown-item" style="display:flex;align-items:center;padding:10px 22px;color:#fff;text-decoration:none;font-size:1.1rem;gap:14px;"><span style="font-size:1.4em;">↩️</span>Logout</a>
                   </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </header>
     <!-- Mobile Nav Overlay & Dropdown -->
     <div class="mobile-nav-overlay"></div>
    <div class="mobile-nav-dropdown">
      <button class="close-mobile-nav" aria-label="Close Menu"><i class="fa fa-times"></i></button>
      <nav>
        <a href="index.php">Home</a>
        <a href="pages/contact.php">Contact</a>
        <a href="pages/about.php">About</a>
        <?php if (isset($_SESSION['user'])): ?>
        <a href="pages/account.php">My Account</a>
        <a href="pages/logout.php">Logout</a>
        <?php else: ?>
        <a href="pages/login.php">Login</a>
        <a href="pages/signup.php">Sign Up</a>
        <?php endif; ?>
      </nav>
      <div class="search-cart">
        <div class="search-box">
          <input type="text" placeholder="What are you looking for?">
          <span class="search-icon"><img src="assets/icons/Saearch.png" alt="Search" class="icon-img"></span>
        </div>
        <a href="pages/wishlist.html" class="icon-link"><img src="assets/icons/Wishlist.png" alt="Wishlist" class="icon-img"></a>
        <a href="pages/cart.html" class="icon-link" style="position:relative;">
          <img src="assets/icons/Cart1.png" alt="Cart" class="icon-img">
          <span class="nav-cart-after" style="position:absolute;top:-8px;right:-8px;background:#DB4444;color:#fff;font-size:0.9em;padding:2px 7px;border-radius:50%;display:none;z-index:2;">0</span>
        </a>
      </div>
    </div>
    <main>
        <div class="container main-content">
            <!-- Sidebar -->
            <aside class="sidebar">
                <ul>
                    <li>Woman's Fashion</li>
                    <li>Men's Fashion</li>
                    <li>Electronics</li>
                    <li>Home & Lifestyle</li>
                    <li>Medicine</li>
                    <li>Sports & Outdoor</li>
                    <li>Baby's & Toys</li>
                    <li>Groceries & Pets</li>
                    <li>Health & Beauty</li>
                </ul>
            </aside>
            <!-- Hero Banner -->
            <section class="hero-banner-slider">
    <div class="hero-slider-track">
        <!-- Slide 1 -->
        <div class="hero-slide active">
            <div class="hero-slide-content">
                <img src="assets/images/apple logo.png" alt="Apple Logo" class="hero-apple-logo">
                <div class="hero-slide-title">iPhone 14 Series</div>
                <h1 class="hero-slide-main">Up to 10%<br>off Voucher</h1>
                <a href="#" class="hero-shop-now">Shop Now <span class="arrow">&rarr;</span></a>
            </div>
            <img src="assets/images/hero_endframe.png" alt="iPhone 14" class="hero-slide-img">
        </div>
        <!-- Slide 2: Example, you can add more slides here if you want -->
        <div class="hero-slide">
            <div class="hero-slide-content">
                <img src="assets/images/apple logo.png" alt="Apple Logo" class="hero-apple-logo">
                <div class="hero-slide-title">iPhone 14 Series</div>
                <h1 class="hero-slide-main">Special Offer<br>on Accessories</h1>
                <a href="#" class="hero-shop-now">Shop Now <span class="arrow">&rarr;</span></a>
            </div>
            <img src="assets/images/hero_endframe.png" alt="iPhone 14" class="hero-slide-img">
        </div>
        <!-- Slide 3: Example -->
        <div class="hero-slide">
            <div class="hero-slide-content">
                <img src="assets/images/apple logo.png" alt="Apple Logo" class="hero-apple-logo">
                <div class="hero-slide-title">iPhone 14 Series</div>
                <h1 class="hero-slide-main">Free Shipping<br>on All Orders</h1>
                <a href="#" class="hero-shop-now">Shop Now <span class="arrow">&rarr;</span></a>
            </div>
            <img src="assets/images/hero_endframe.png" alt="iPhone 14" class="hero-slide-img">
        </div>
    </div>
    <div class="hero-slider-dots">
        <span class="hero-dot active" data-slide="0"></span>
        <span class="hero-dot" data-slide="1"></span>
        <span class="hero-dot" data-slide="2"></span>
    </div>
</section>
        </div>

        <!-- Flash Sales -->
<section class="flash-sales">
    <div class="container">
        <div class="flash-header-2025">
            <div class="flash-bar"></div>
            <div class="flash-label-row">
                <span class="flash-label">Today's</span>
            </div>
            <div class="flash-main-row">
                <span class="flash-title">Flash Sales</span>
                <div class="flash-timer-2025" id="flash-timer">
                    <div class="timer-segment">
                        <span class="timer-value" id="timer-days">03</span>
                        <span class="timer-label">Days</span>
                    </div>
                    <span class="timer-dot">:</span>
                    <div class="timer-segment">
                        <span class="timer-value" id="timer-hours">23</span>
                        <span class="timer-label">Hours</span>
                    </div>
                    <span class="timer-dot">:</span>
                    <div class="timer-segment">
                        <span class="timer-value" id="timer-minutes">19</span>
                        <span class="timer-label">Minutes</span>
                    </div>
                    <span class="timer-dot">:</span>
                    <div class="timer-segment">
                        <span class="timer-value" id="timer-seconds">56</span>
                        <span class="timer-label">Seconds</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="product-carousel">
            <!-- Mostrar productos de Flash Sale desde la base de datos -->
            <?php if (!empty($flashSaleProducts)): ?>
                <?php foreach ($flashSaleProducts as $product): ?>
                <div class="product-card">
                    <?php if (!empty($product['sale_price']) && $product['sale_price'] < $product['price']): ?>
                        <span class="product-badge sale">
                            <?php 
                                $discount = round(($product['price'] - $product['sale_price']) / $product['price'] * 100);
                                echo "-{$discount}%"; 
                            ?>
                        </span>
                    <?php else: ?>
                        <span class="product-badge new">NEW</span>
                    <?php endif; ?>
                    <div class="product-card-icons-row">
                        <button class="icon-btn wishlist-icon" title="Add to Wishlist" data-product-id="<?php echo $product['product_id']; ?>"><i class="fa fa-heart"></i></button>
                        <button class="icon-btn view-icon" title="Quick View" data-product-id="<?php echo $product['product_id']; ?>"><i class="fa fa-eye"></i></button>
                    </div>
                    <img src="<?php echo !empty($product['primary_image']) ? htmlspecialchars(fix_image_path($product['primary_image'])) : 'assets/images/product-placeholder.png'; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" loading="lazy">
                    <div class="product-details">
                        <div class="product-title"><?php echo htmlspecialchars($product['name']); ?></div>
                        <div class="product-price-rating">
                            <?php if (!empty($product['sale_price']) && $product['sale_price'] < $product['price']): ?>
                                <span class="price">$<?php echo number_format($product['sale_price'], 2); ?></span>
                                <span class="old-price">$<?php echo number_format($product['price'], 2); ?></span>
                            <?php else: ?>
                                <span class="price">$<?php echo number_format($product['price'], 2); ?></span>
                            <?php endif; ?>
                        </div>
                        <button class="add-to-cart-btn" 
    data-product-id="<?php echo $product['product_id']; ?>"
    data-product-name="<?php echo htmlspecialchars($product['name']); ?>"
    data-product-price="<?php echo !empty($product['sale_price']) && $product['sale_price'] < $product['price'] ? $product['sale_price'] : $product['price']; ?>"
    data-product-image="<?php echo $imagePath; ?>">
    Add To Cart
</button>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Mostrar productos estáticos si no hay datos en la BD -->
                <!-- Products will be rendered here by main.js -->
            <?php endif; ?>
        </div>
    </div>
</section>



<!-- Browse By Category Section -->
<section class="category-section">
    <div class="container">
        <div style="display: flex; align-items: flex-start; justify-content: space-between;">
            <div style="display: flex; flex-direction: column; align-items: flex-start; gap: 10px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div class="flash-bar" style="margin-bottom: 0;"></div>
                    <span class="flash-label" style="font-size: 1.1rem;">Categories</span>
                </div>
                <span class="cat-title" style="font-size: 2.2rem; font-weight: 700; margin-left: 0; margin-top: 0;">Browse By Category</span>
            </div>
            <div class="cat-arrows" style="margin-top: 8px;">
                <button class="cat-arrow"><span>&larr;</span></button>
                <button class="cat-arrow"><span>&rarr;</span></button>
            </div>
        </div>
        <div class="category-list" style="margin-top: 28px;">
            <div class="category-card" style="width:170px;height:145px;">
                <img src="assets/images/Category-CellPhone.png" alt="Phones">
                <span>Phones</span>
            </div>
            <div class="category-card" style="width:170px;height:145px;">
                <img src="assets/images/Category-Computer.png" alt="Computers">
                <span>Computers</span>
            </div>
            <div class="category-card" style="width:170px;height:145px;">
                <img src="assets/images/Category-SmartWatch.png" alt="SmartWatch">
                <span>SmartWatch</span>
            </div>
            <div class="category-card active" style="width:170px;height:145px;">
                <img src="assets/images/Category-Camera.png" alt="Camera">
                <span>Camera</span>
            </div>
            <div class="category-card" style="width:170px;height:145px;">
                <img src="assets/images/Category-Headphone.png" alt="HeadPhones">
                <span>HeadPhones</span>
            </div>
            <div class="category-card" style="width:170px;height:145px;">
                <img src="assets/images/Category-Gamepad.png" alt="Gaming">
                <span>Gaming</span>
            </div>
        </div>
    </div>
</section>


<invoke name="grep_search">
<parameter name="SearchPath">c:\wamp64\www\Tienda\index.php</parameter>
<parameter name="Query">img src</parameter>
<parameter name="CaseInsensitive">true</parameter>
<parameter name="MatchPerLine">true</parameter>
</invoke>




<!-- Best Selling Products Section -->
<section class="best-selling-section">
    <div class="container">
        <div style="display: flex; align-items: flex-start; justify-content: space-between;">
            <div style="display: flex; flex-direction: column; align-items: flex-start; gap: 10px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div class="flash-bar" style="margin-bottom: 0;"></div>
                    <span class="flash-label" style="color:#DB4444;font-size: 1.1rem;">This Month</span>
                </div>
                <span class="cat-title" style="font-size: 2.2rem; font-weight: 700; margin-left: 0; margin-top: 0;">Best Selling Products</span>
            </div>
            <button class="view-all-btn" style="margin-top: 8px; background: #DB4444; color: #fff; border: none; border-radius: 6px; padding: 12px 36px; font-size: 1.1rem; font-weight: 600; cursor: pointer;">View All</button>
        </div>
        <div class="product-carousel best-selling-carousel" style="margin-top: 30px; display: flex; gap: 28px;">
            <?php if (!empty($bestSellingProducts)): ?>
                <?php foreach ($bestSellingProducts as $product): ?>
                <div class="product-card best">
                    <div class="product-card-icons-row">
                        <button class="icon-btn wishlist-icon" title="Add to Wishlist" data-product-id="<?php echo $product['product_id']; ?>"><i class="fa fa-heart"></i></button>
                        <button class="icon-btn view-icon" title="View Product" data-product-id="<?php echo $product['product_id']; ?>"><i class="fa fa-eye"></i></button>
                    </div>
                    <?php 
    $imagePath = !empty($product['primary_image']) ? $product['primary_image'] : 'assets/images/product-placeholder.png';
    // Fix image path if it starts with '../'
    if (strpos($imagePath, '../') === 0) {
        $imagePath = substr($imagePath, 3);
    }
?>
<a href="pages/ProductDetails.php?id=<?php echo $product['product_id']; ?>" class="product-link">
                        <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    </a>
                    <h4><a href="pages/ProductDetails.php?id=<?php echo $product['product_id']; ?>"><?php echo htmlspecialchars($product['name']); ?></a></h4>
                    <div class="price">
                        <?php if (!empty($product['sale_price']) && $product['sale_price'] < $product['price']): ?>
                            <span class="price-main">$<?php echo number_format($product['sale_price'], 2); ?></span> 
                            <del>$<?php echo number_format($product['price'], 2); ?></del>
                        <?php else: ?>
                            <span class="price-main">$<?php echo number_format($product['price'], 2); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="stars">
                        <span class="star">&#9733;</span><span class="star">&#9733;</span><span class="star">&#9733;</span><span class="star">&#9733;</span><span class="star">&#9733;</span>
                        <span class="count">(<?php echo $product['sales_count'] > 0 ? $product['sales_count'] : rand(5, 65); ?>)</span>
                    </div>
                    <button class="add-to-cart-btn" data-product-id="<?php echo $product['product_id']; ?>" 
                            data-product-name="<?php echo htmlspecialchars($product['name']); ?>" 
                            data-product-price="<?php echo !empty($product['sale_price']) && $product['sale_price'] < $product['price'] ? $product['sale_price'] : $product['price']; ?>" 
                            data-product-image="<?php echo !empty($product['primary_image']) ? htmlspecialchars($product['primary_image']) : 'assets/images/product-placeholder.png'; ?>">
                        <i class="fa fa-shopping-cart"></i> Add to Cart
                    </button>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Productos estáticos de ejemplo -->
                <div class="product-card best">
                    <div class="product-card-icons-row">
                        <button class="icon-btn wishlist-icon" title="Add to Wishlist"><i class="fa fa-heart"></i></button>
                        <button class="icon-btn view-icon" title="View Product"><i class="fa fa-eye"></i></button>
                    </div>
                    <img src="https://dummyimage.com/200x200/ff4444/fff&text=Coat" alt="Coat">
                    <h4>The north coat</h4>
                    <div class="price"><span class="price-main">$260</span> <del>$360</del></div>
                    <div class="stars">
                        <span class="star">&#9733;</span><span class="star">&#9733;</span><span class="star">&#9733;</span><span class="star">&#9733;</span><span class="star">&#9733;</span>
                        <span class="count">(65)</span>
                    </div>
                    <button class="add-to-cart-btn" data-product-id="1" data-product-name="The north coat" data-product-price="260" data-product-image="https://dummyimage.com/200x200/ff4444/fff&text=Coat">
                        <i class="fa fa-shopping-cart"></i> Add to Cart
                    </button>
                </div>
                <div class="product-card best">
                    <div class="product-card-icons-row">
                        <button class="icon-btn wishlist-icon" title="Add to Wishlist"><i class="fa fa-heart"></i></button>
                        <button class="icon-btn view-icon" title="View Product"><i class="fa fa-eye"></i></button>
                    </div>
                    <img src="https://dummyimage.com/200x200/44ff44/fff&text=Bag" alt="Bag">
                    <h4><em>Gucci duffle bag</em></h4>
                    <div class="price"><span class="price-main">$960</span> <del>$1160</del></div>
                    <div class="stars">
                        <span class="star">&#9733;</span><span class="star">&#9733;</span><span class="star">&#9733;</span><span class="star">&#9733;</span><span class="star">&#9733;</span>
                        <span class="count">(65)</span>
                    </div>
                </div>
                <div class="product-card best">
                    <div class="product-card-icons-row">
                        <button class="icon-btn wishlist-icon" title="Add to Wishlist"><i class="fa fa-heart"></i></button>
                        <button class="icon-btn view-icon" title="View Product"><i class="fa fa-eye"></i></button>
                    </div>
                    <img src="https://dummyimage.com/200x200/4444ff/fff&text=CPU+Cooler" alt="CPU Cooler">
                    <h4>RGB liquid CPU Cooler</h4>
                    <div class="price"><span class="price-main">$160</span> <del>$170</del></div>
                    <div class="stars">
                        <span class="star">&#9733;</span><span class="star">&#9733;</span><span class="star">&#9733;</span><span class="star">&#9733;</span><span class="star">&#9733;</span>
                        <span class="count">(65)</span>
                    </div>
                </div>
                <div class="product-card best">
                    <div class="product-card-icons-row">
                        <button class="icon-btn wishlist-icon" title="Add to Wishlist"><i class="fa fa-heart"></i></button>
                        <button class="icon-btn view-icon" title="View Product"><i class="fa fa-eye"></i></button>
                    </div>
                    <img src="https://dummyimage.com/200x200/d2b48c/fff&text=BookSelf" alt="BookSelf">
                    <h4><em>Small BookSelf</em></h4>
                    <div class="price"><span class="price-main">$360</span></div>
                    <div class="stars">
                        <span class="star">&#9733;</span><span class="star">&#9733;</span><span class="star">&#9733;</span><span class="star">&#9733;</span><span class="star">&#9733;</span>
                        <span class="count">(65)</span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

        
<!-- Music Experience Banner -->
<section class="music-experience-banner">
  <div class="music-banner-container">
    <div class="music-banner-left">
      <span class="music-cat-label">Categories</span>
      <h2 class="music-banner-title">Enhance Your<br>Music Experience</h2>
      <div class="music-banner-timer-row">
        <div class="music-timer-circle"><span class="music-timer-num">23</span><span class="music-timer-label">Hours</span></div>
        <div class="music-timer-circle"><span class="music-timer-num">05</span><span class="music-timer-label">Days</span></div>
        <div class="music-timer-circle"><span class="music-timer-num">59</span><span class="music-timer-label">Minutes</span></div>
        <div class="music-timer-circle"><span class="music-timer-num">35</span><span class="music-timer-label">Seconds</span></div>
      </div>
      <button class="music-buy-btn">Buy Now!</button>
    </div>
    <div class="music-banner-right">
      <img src="assets/images/jbl.png" alt="JBL Speaker" class="music-banner-img">
    </div>
  </div>
</section>
<!-- Explore Our Products Section -->
<section>
    <div class="container">
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px;">
            <span style="background: #DB4444; width: 20px; height: 20px; border-radius: 4px;"></span>
            <span style="color: #DB4444; font-weight: 500;">Our Products</span>
        </div>
        <h2 style="font-size: 2.2rem; font-weight: 700; margin-bottom: 32px;">Explore Our Products</h2>
        <div class="products-grid">
            <?php if (!empty($allProducts)): ?>
                <?php foreach ($allProducts as $product): ?>
                <div class="product-card">
                    <?php if (!empty($product['sale_price']) && $product['sale_price'] < $product['price']): ?>
                        <span class="product-badge sale">
                            <?php 
                                $discount = round(($product['price'] - $product['sale_price']) / $product['price'] * 100);
                                echo "-{$discount}%"; 
                            ?>
                        </span>
                    <?php else: ?>
                        <span class="product-badge new">NEW</span>
                    <?php endif; ?>
                    <div class="product-card-icons-row">
                        <button class="icon-btn wishlist-icon" title="Add to Wishlist" data-product-id="<?php echo $product['product_id']; ?>"><i class="fa fa-heart"></i></button>
                        <button class="icon-btn view-icon" title="Quick View" data-product-id="<?php echo $product['product_id']; ?>"><i class="fa fa-eye"></i></button>
                    </div>
                    <?php 
    $imagePath = !empty($product['primary_image']) ? $product['primary_image'] : 'assets/images/product-placeholder.png';
    // Fix image path if it starts with '../'
    if (strpos($imagePath, '../') === 0) {
        $imagePath = substr($imagePath, 3);
    }
?>
<a href="pages/ProductDetails.php?id=<?php echo $product['product_id']; ?>" class="product-link">
                        <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    </a>
                    <div class="product-details">
                        <div class="product-title"><a href="pages/ProductDetails.php?id=<?php echo $product['product_id']; ?>"><?php echo htmlspecialchars($product['name']); ?></a></div>
                        <div class="product-price-rating">
                            <?php if (!empty($product['sale_price']) && $product['sale_price'] < $product['price']): ?>
                                <span class="price">$<?php echo number_format($product['sale_price'], 2); ?></span>
                                <span class="old-price">$<?php echo number_format($product['price'], 2); ?></span>
                            <?php else: ?>
                                <span class="price">$<?php echo number_format($product['price'], 2); ?></span>
                            <?php endif; ?>
                            <span class="stars">★★★★★</span>
                            <span class="reviews">(<?php echo rand(5, 95); ?>)</span>
                        </div>
                        <button class="add-to-cart-btn" 
    data-product-id="<?php echo $product['product_id']; ?>"
    data-product-name="<?php echo htmlspecialchars($product['name']); ?>"
    data-product-price="<?php echo !empty($product['sale_price']) && $product['sale_price'] < $product['price'] ? $product['sale_price'] : $product['price']; ?>"
    data-product-image="<?php echo $imagePath; ?>">
    Add To Cart
</button>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Productos estáticos en caso de que no haya datos en la base de datos -->
                <!-- Product Card 1 -->
                <div class="product-card">
                    <span class="product-badge new">NEW</span>
                    <div class="product-card-icons-row">
                        <button class="icon-btn wishlist-icon" title="Add to Wishlist"><i class="fa fa-heart"></i></button>
                        <button class="icon-btn view-icon" title="Quick View"><i class="fa fa-eye"></i></button>
                    </div>
                    <img src="assets/images/dog-food.png" alt="Breed Dry Dog Food">
                    <div class="product-details">
                        <div class="product-title">Breed Dry Dog Food</div>
                        <div class="product-price-rating">
                            <span class="price">$100</span>
                            <span class="stars">★★★☆☆</span>
                            <span class="reviews">(35)</span>
                        </div>
                        <div class="color-options">
                            <span class="color-dot" style="background:#FFD700;"></span>
                            <span class="color-dot" style="background:#C0C0C0;"></span>
                        </div>
                        <button class="add-to-cart-btn" 
                            data-product-id="1" 
                            data-product-name="Breed Dry Dog Food" 
                            data-product-price="100" 
                            data-product-image="assets/images/dog-food.png">
                            Add To Cart
                        </button>
                    </div>
                </div>
                <!-- Product Card 2 -->
                <div class="product-card">
                    <div class="product-card-icons-row">
                        <button class="icon-btn wishlist-icon" title="Add to Wishlist"><i class="fa fa-heart"></i></button>
                        <button class="icon-btn view-icon" title="Quick View"><i class="fa fa-eye"></i></button>
                    </div>
                    <img src="assets/images/camera.png" alt="CANON EOS DSLR Camera">
                    <div class="product-details">
                        <div class="product-title">CANON EOS DSLR Camera</div>
                        <div class="product-price-rating">
                            <span class="price">$360</span>
                            <span class="stars">★★★★★</span>
                            <span class="reviews">(95)</span>
                        </div>
                        <button class="add-to-cart-btn" 
                            data-product-id="1" 
                            data-product-name="Breed Dry Dog Food" 
                            data-product-price="100" 
                            data-product-image="assets/images/dog-food.png">
                            Add To Cart
                        </button>
                    </div>
                </div>
                <!-- Product Card 3 -->
                <div class="product-card">
                    <div class="product-card-icons-row">
                        <button class="icon-btn wishlist-icon" title="Add to Wishlist"><i class="fa fa-heart"></i></button>
                        <button class="icon-btn view-icon" title="Quick View"><i class="fa fa-eye"></i></button>
                    </div>
                    <img src="assets/images/laptop.png" alt="ASUS FHD Gaming Laptop">
                    <div class="product-details">
                        <div class="product-title">ASUS FHD Gaming Laptop</div>
                        <div class="product-price-rating">
                            <span class="price">$700</span>
                            <span class="stars">★★★★★</span>
                            <span class="reviews">(325)</span>
                        </div>
                        <button class="add-to-cart-btn" 
                            data-product-id="1" 
                            data-product-name="Breed Dry Dog Food" 
                            data-product-price="100" 
                            data-product-image="assets/images/dog-food.png">
                            Add To Cart
                        </button>
                    </div>
                </div>
                <!-- Product Card 4 -->
                <div class="product-card">
                    <div class="product-card-icons-row">
                        <button class="icon-btn wishlist-icon" title="Add to Wishlist"><i class="fa fa-heart"></i></button>
                        <button class="icon-btn view-icon" title="Quick View"><i class="fa fa-eye"></i></button>
                    </div>
                    <img src="assets/images/curology.png" alt="Curology Product Set">
                    <div class="product-details">
                        <div class="product-title">Curology Product Set</div>
                        <div class="product-price-rating">
                            <span class="price">$500</span>
                            <span class="stars">★★★★☆</span>
                            <span class="reviews">(145)</span>
                        </div>
                        <button class="add-to-cart-btn" 
                            data-product-id="1" 
                            data-product-name="Breed Dry Dog Food" 
                            data-product-price="100" 
                            data-product-image="assets/images/dog-food.png">
                            Add To Cart
                        </button>
                    </div>
                </div>
                <!-- Product Card 5 -->
                <div class="product-card">
                    <span class="product-badge new">NEW</span>
                    <div class="product-card-icons-row">
                        <button class="icon-btn wishlist-icon" title="Add to Wishlist"><i class="fa fa-heart"></i></button>
                        <button class="icon-btn view-icon" title="Quick View"><i class="fa fa-eye"></i></button>
                    </div>
                    <img src="assets/images/kids-car.png" alt="Kids Electric Car">
                    <div class="product-details">
                        <div class="product-title">Kids Electric Car</div>
                        <div class="product-price-rating">
                            <span class="price">$960</span>
                            <span class="stars">★★★★★</span>
                            <span class="reviews">(65)</span>
                        </div>
                        <button class="add-to-cart-btn" 
                            data-product-id="1" 
                            data-product-name="Breed Dry Dog Food" 
                            data-product-price="100" 
                            data-product-image="assets/images/dog-food.png">
                            Add To Cart
                        </button>
                    </div>
                </div>
                <!-- Product Card 6 -->
                <div class="product-card">
                    <div class="product-card-icons-row">
                        <button class="icon-btn wishlist-icon" title="Add to Wishlist"><i class="fa fa-heart"></i></button>
                        <button class="icon-btn view-icon" title="Quick View"><i class="fa fa-eye"></i></button>
                    </div>
                    <img src="assets/images/cleats.png" alt="Jr. Zoom Soccer Cleats">
                    <div class="product-details">
                        <div class="product-title">Jr. Zoom Soccer Cleats</div>
                        <div class="product-price-rating">
                            <span class="price">$1160</span>
                            <span class="stars">★★★★★</span>
                            <span class="reviews">(35)</span>
                        </div>
                        <button class="add-to-cart-btn" 
                            data-product-id="1" 
                            data-product-name="Breed Dry Dog Food" 
                            data-product-price="100" 
                            data-product-image="assets/images/dog-food.png">
                            Add To Cart
                        </button>
                    </div>
                </div>
                <!-- Product Card 7 -->
                <div class="product-card">
                    <span class="product-badge new">NEW</span>
                    <div class="product-card-icons-row">
                        <button class="icon-btn wishlist-icon" title="Add to Wishlist"><i class="fa fa-heart"></i></button>
                        <button class="icon-btn view-icon" title="Quick View"><i class="fa fa-eye"></i></button>
                    </div>
                    <img src="assets/images/gamepad.png" alt="GP11 Shooter USB Gamepad">
                    <div class="product-details">
                        <div class="product-title">GP11 Shooter USB Gamepad</div>
                        <div class="product-price-rating">
                            <span class="price">$660</span>
                            <span class="stars">★★★★★</span>
                            <span class="reviews">(55)</span>
                        </div>
                        <div class="color-options">
                            <span class="color-dot" style="background:#111;"></span>
                            <span class="color-dot" style="background:#ff3c3c;"></span>
                        </div>
                        <button class="add-to-cart-btn" 
                            data-product-id="1" 
                            data-product-name="Breed Dry Dog Food" 
                            data-product-price="100" 
                            data-product-image="assets/images/dog-food.png">
                            Add To Cart
                        </button>
                    </div>
                </div>
                <!-- Product Card 8 -->
                <div class="product-card">
                    <div class="product-card-icons-row">
                        <button class="icon-btn wishlist-icon" title="Add to Wishlist"><i class="fa fa-heart"></i></button>
                        <button class="icon-btn view-icon" title="Quick View"><i class="fa fa-eye"></i></button>
                    </div>
                    <img src="assets/images/jacket.png" alt="Quilted Satin Jacket">
                    <div class="product-details">
                        <div class="product-title">Quilted Satin Jacket</div>
                        <div class="product-price-rating">
                            <span class="price">$660</span>
                            <span class="stars">★★★★★</span>
                            <span class="reviews">(55)</span>
                        </div>
                        <button class="add-to-cart-btn" 
                            data-product-id="1" 
                            data-product-name="Breed Dry Dog Food" 
                            data-product-price="100" 
                            data-product-image="assets/images/dog-food.png">
                            Add To Cart
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <div style="display: flex; justify-content: center; margin-top: 32px;">
            <button class="view-all-btn">View All Products</button>
        </div>
    </div>
</section>

  

    <!-- Explore Our Products Section End -->
    <!-- Services Section Start -->
    <section class="services-section">
        <div class="container services-container">
            <div class="service-box">
                <div class="service-icon">
                    <img src="assets/icons/Services.png" alt="Delivery">
                </div>
                <div class="service-title">FREE AND FAST DELIVERY</div>
                <div class="service-desc">Free delivery for all orders over $140</div>
            </div>
            <div class="service-box">
                <div class="service-icon">
                    <img src="assets/icons/Services2.png" alt="Customer Service">
                </div>
                <div class="service-title">24/7 CUSTOMER SERVICE</div>
                <div class="service-desc">Friendly 24/7 customer support</div>
            </div>
            <div class="service-box">
                <div class="service-icon">
                    <img src="assets/icons/Services3.png" alt="Money Back Guarantee">
                </div>
                <div class="service-title">MONEY BACK GUARANTEE</div>
                <div class="service-desc">We return money within 30 days</div>
            </div>
        </div>
    </section>
    <!-- Services Section End -->

    <button class="scroll-to-top" onclick="window.scrollTo({top:0,behavior:'smooth'})" title="Back to top">
        <img src="assets/icons/icons_arrow-up.png" alt="Up" />
    </button>


    <footer class="footer-main">
        <div class="container footer-container">
            <div class="footer-col">
                <div class="footer-logo">Exclusive</div>
                <div class="footer-title">Subscribe</div>
                <div class="footer-desc">Get 10% off your first order</div>
                <form class="subscribe-form">
                    <input type="email" placeholder="Enter your email">
                    <button type="submit"><i class="fa fa-paper-plane"></i></button>
                </form>
            </div>
            <div class="footer-col">
                <div class="footer-title">Support</div>
                <div class="footer-desc">111 Bijoy sarani, Dhaka, DH 1515, Bangladesh.</div>
                <div class="footer-desc">exclusive@gmail.com</div>
                <div class="footer-desc">+88015-88888-9999</div>
            </div>
            <div class="footer-col">
                <div class="footer-title">Account</div>
                <ul class="footer-list">
                    <li><a href="pages/account.php">My Account</a></li>
                    <li><a href="pages/signup.php">Login / Register</a></li>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="pages/wishlist.php">Wishlist</a></li>
                    <li><a href="index.php">Shop</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <div class="footer-title">Quick Link</div>
                <ul class="footer-list">
                    <li>Privacy Policy</li>
                    <li>Terms Of Use</li>
                    <li>FAQ</li>
                    <li><a href="pages/contact.html">Contact</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <div class="footer-title">Download App</div>
                <div class="footer-desc">Save $3 with App New User Only</div>
                <div class="footer-apps">
    <img class="footer-app-img" src="assets/images/APP.png" alt="Download App QR and Badges">
</div>
                <div class="footer-socials">
                    <i class="fab fa-facebook-f"></i>
                    <i class="fab fa-twitter"></i>
                    <i class="fab fa-instagram"></i>
                    <i class="fab fa-linkedin-in"></i>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <span>&copy; Copyright Blue Flame 2025. All right reserved</span>
        </div>
    </footer>

    <!-- Incluir archivos JavaScript esenciales -->
    <script src="scripts/cart_fix.js"></script>
    <script src="scripts/cart.js"></script>
    <script src="scripts/main.js"></script>

    <!-- Script para el menú desplegable del perfil -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Código para el menú desplegable del perfil
        const profileIcon = document.getElementById('profileIcon');
        const profileDropdown = document.getElementById('profileDropdown');
        
        if (profileIcon && profileDropdown) {
            profileIcon.addEventListener('click', function(e) {
                e.stopPropagation();
                if (profileDropdown.style.display === 'none') {
                    profileDropdown.style.display = 'block';
                } else {
                    profileDropdown.style.display = 'none';
                }
            });
            
            // Cerrar el menú al hacer clic fuera
            document.addEventListener('click', function(e) {
                if (profileDropdown && profileDropdown.style.display === 'block' && !profileDropdown.contains(e.target) && e.target !== profileIcon) {
                    profileDropdown.style.display = 'none';
                }
            });
        }
    });
    </script>