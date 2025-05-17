<?php
/**
 * ProductDetails.php
 * Product details page styled to match the HTML template
 */

// Incluir bootstrap para cargar todas las dependencias necesarias
require_once '../includes/bootstrap.php';

// Verificar si la sesión está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Definir función isLoggedIn si no existe
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user']) && !empty($_SESSION['user']);
    }
}

// Función para registrar errores en el log y mostrar mensajes amigables
function logErrorAndRedirect($message, $errorDetails = null) {
    // Registrar error detallado para administradores
    if ($errorDetails) {
        error_log("ProductDetails Error: {$message} - Detalles: {$errorDetails}");
    }
    
    // Almacenar mensaje de error para el usuario
    $_SESSION['error_message'] = $message;
    
    // Redirigir a la página principal
    header('Location: ../index.php');
    exit;
}

// Función para detectar la tabla correcta
function detectCorrectTable($db, $options) {
    foreach ($options as $table) {
        try {
            // MySQL no permite parámetros preparados con SHOW TABLES LIKE
            // Usar comillas directamente, asegurándonos de escapar el nombre de la tabla
            $tableSafe = str_replace(['\\', "'"], ['\\\\', "\\'"], $table);
            $result = $db->query("SHOW TABLES LIKE '{$tableSafe}'");
            if ($result && $result->rowCount() > 0) {
                return $table;
            }
        } catch (Exception $e) {
            // Continuar con la siguiente opción
        }
    }
    return null;
}

// Initialize variables
$product = null;
$relatedProducts = [];
$reviews = [];
$averageRating = 0;
$isLoggedIn = isLoggedIn();
$userName = '';

// Get product ID from URL
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($productId <= 0) {
    logErrorAndRedirect('ID de producto inválido');
}

// Initialize DB connection
$db = Database::getInstance();

// Detectar los nombres de tabla correctos en la base de datos
$table_products = detectCorrectTable($db, ['products', 'product']);
$table_images = detectCorrectTable($db, ['product_images', 'product_image']);
$table_categories = detectCorrectTable($db, ['categories', 'category']);
$table_product_categories = detectCorrectTable($db, ['product_categories', 'product_category']);
$table_reviews = detectCorrectTable($db, ['reviews', 'review']);
$table_users = detectCorrectTable($db, ['users', 'user']);

// Si existe un archivo de configuración personalizado, usarlo
$configFile = __DIR__ . '/product_tables_config.php';
if (file_exists($configFile)) {
    include $configFile;
}

// Verificar y guardar configuración de tabla si no existe
if (!file_exists($configFile) && $table_products) {
    $configContent = "<?php\n// Configuración de tablas para ProductDetails.php\n";
    $configContent .= "\$table_products = '{$table_products}';\n";
    $configContent .= "\$table_images = '{$table_images}';\n";
    $configContent .= "\$table_categories = '{$table_categories}';\n";
    $configContent .= "\$table_product_categories = '{$table_product_categories}';\n";
    $configContent .= "\$table_reviews = '{$table_reviews}';\n";
    $configContent .= "\$table_users = '{$table_users}';\n";
    
    // Guardar el archivo de configuración
    file_put_contents($configFile, $configContent);
}

// Obtener el producto de la base de datos
$product = null;

// Si hemos detectado la tabla de productos, intentar obtener el producto
if ($table_products) {
    try {
        $stmt = $db->query("SELECT * FROM {$table_products} WHERE product_id = ?", [$productId]);
        $product = $stmt->fetch();
    } catch (Exception $e) {
        error_log("Error al obtener producto: " . $e->getMessage());
    }
}

// Si no se encontró usando la configuración actual, intentar con nombres de tabla alternativos
if (!$product) {
    // Intentar con tabla 'products' si no se ha intentado ya
    if ($table_products !== 'products') {
        try {
            $stmt = $db->query("SELECT * FROM products WHERE product_id = ?", [$productId]);
            $product = $stmt->fetch();
            if ($product) {
                $table_products = 'products';
            }
        } catch (Exception $e) {
            // Ignorar errores y continuar
        }
    }
    
    // Intentar con tabla 'product' si aún no se encuentra
    if (!$product && $table_products !== 'product') {
        try {
            $stmt = $db->query("SELECT * FROM product WHERE product_id = ?", [$productId]);
            $product = $stmt->fetch();
            if ($product) {
                $table_products = 'product';
            }
        } catch (Exception $e) {
            // Ignorar errores y continuar
        }
    }
}

// Si aún no se encuentra el producto, verificar si realmente existe
if (!$product) {
    // Intentar obtener una lista de productos para diagnóstico
    $availableIds = [];
    try {
        if ($table_products) {
            $stmt = $db->query("SELECT product_id FROM {$table_products} ORDER BY product_id LIMIT 10");
            $availableIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
    } catch (Exception $e) {
        // Ignorar errores
    }
    
    $errorMessage = 'Producto no encontrado';
    if (!empty($availableIds)) {
        $errorMessage .= ". IDs disponibles: " . implode(', ', $availableIds);
    }
    
    logErrorAndRedirect($errorMessage, "ID buscado: {$productId}, Tabla usada: {$table_products}");
}

if (!$product) {
    // Product not found, redirect to products page
    // Reemplazar add_flash_message con session directa para mensaje de error
    $_SESSION['error_message'] = 'Producto no encontrado';
    header('Location: ../index.php');
    exit;
}

// Get product images - método mejorado para manejar diferentes estructuras
$images = [];

// Comprobar que tenemos una tabla de imágenes válida
if ($table_images) {
    try {
        // Verificar si existe la columna is_primary
        $hasIsPrimary = false;
        try {
            // MySQL no permite parámetros preparados con SHOW COLUMNS
            $columnCheck = $db->query("SHOW COLUMNS FROM {$table_images} LIKE 'is_primary'");
            $hasIsPrimary = ($columnCheck->rowCount() > 0);
        } catch (Exception $e) {
            // Ignorar errores en la verificación de columnas
            error_log("Error verificando columna is_primary: " . $e->getMessage());
        }
        
        // Construir la consulta SQL adecuada
        $sql = "SELECT * FROM {$table_images} WHERE product_id = ?";
        if ($hasIsPrimary) {
            $sql .= " ORDER BY is_primary DESC";
        }
        
        // Ejecutar la consulta
        $stmt = $db->query($sql, [$productId]);
        $images = $stmt->fetchAll();
        
        // Procesar rutas de imágenes para corregir rutas relativas
        if (!empty($images)) {
            foreach ($images as &$image) {
                if (isset($image['image_path']) && strpos($image['image_path'], '../') === 0) {
                    // Eliminar el prefijo '../' de las rutas
                    $image['image_path'] = substr($image['image_path'], 3);
                }
            }
            unset($image); // Romper la referencia
        }
    } catch (Exception $e) {
        error_log("Error obteniendo imágenes del producto: " . $e->getMessage());
        // Mantener array vacío
    }
}

// Si no se encontraron imágenes, intentar buscar en la tabla alternativa
if (empty($images)) {
    $alternativeTable = ($table_images == 'product_images') ? 'product_image' : 'product_images';
    try {
        // MySQL no permite parámetros preparados con SHOW TABLES
        $tableSafe = str_replace(['\\', "'"], ['\\\\', "\\'"], $alternativeTable);
        $stmt = $db->query("SHOW TABLES LIKE '{$tableSafe}'");
        if ($stmt->rowCount() > 0) {
            try {
                $stmt = $db->query("SELECT * FROM {$alternativeTable} WHERE product_id = ?", [$productId]);
                $images = $stmt->fetchAll();
                if (!empty($images)) {
                    $table_images = $alternativeTable;
                }
            } catch (Exception $e) {
                // Ignorar errores en la tabla alternativa
            }
        }
    } catch (Exception $e) {
        // Ignorar errores en la verificación de tablas
    }
}

// Get product categories - manejo robusto con alternativas
$categories = [];

// Si tenemos tablas de categorías definidas, intentar obtener las categorías
if (!empty($table_categories) && !empty($table_product_categories)) {
    try {
        $stmt = $db->query("SELECT c.* FROM {$table_categories} c 
                        JOIN {$table_product_categories} pc ON c.category_id = pc.category_id 
                        WHERE pc.product_id = ?", [$productId]);
        $categories = $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Error obteniendo categorías (primer intento): " . $e->getMessage());
    }
}

// Si no se encontraron categorías, intentar con nombres de tabla alternativos
if (empty($categories)) {
    // Comprobar tablas alternativas:
    // Si tenemos 'category' en lugar de 'categories'
    $altCategoryTable = ($table_categories == 'categories') ? 'category' : 'categories';
    $altPCTable = ($table_product_categories == 'product_categories') ? 'product_category' : 'product_categories';
    
    try {
        // Verificar que las tablas alternativas existan
        $catExists = false;
        $pcExists = false;
        
        try {
            // MySQL no permite parámetros preparados con SHOW TABLES
            $tableSafe = str_replace(['\\', "'"], ['\\\\', "\\'"], $altCategoryTable);
            $stmt = $db->query("SHOW TABLES LIKE '{$tableSafe}'");
            $catExists = ($stmt->rowCount() > 0);
        } catch (Exception $e) {}
        
        try {
            // MySQL no permite parámetros preparados con SHOW TABLES
            $tableSafe = str_replace(['\\', "'"], ['\\\\', "\\'"], $altPCTable);
            $stmt = $db->query("SHOW TABLES LIKE '{$tableSafe}'");
            $pcExists = ($stmt->rowCount() > 0);
        } catch (Exception $e) {}
        
        // Si ambas tablas alternativas existen, intentar consulta
        if ($catExists && $pcExists) {
            try {
                $stmt = $db->query("SELECT c.* FROM {$altCategoryTable} c 
                                JOIN {$altPCTable} pc ON c.category_id = pc.category_id 
                                WHERE pc.product_id = ?", [$productId]);
                $categories = $stmt->fetchAll();
                
                // Si funciona, actualizar los nombres de tabla
                if (!empty($categories)) {
                    $table_categories = $altCategoryTable;
                    $table_product_categories = $altPCTable;
                }
            } catch (Exception $e) {
                error_log("Error obteniendo categorías (tablas alternativas): " . $e->getMessage());
            }
        }
    } catch (Exception $e) {
        error_log("Error verificando tablas de categorías alternativas: " . $e->getMessage());
    }
    
    // Si aún no hay categorías, intentar obtener la categoría directamente desde el producto
    if (empty($categories) && isset($product['category_id']) && $product['category_id'] > 0) {
        try {
            // Primero intentar con la tabla principal de categorías
            if (!empty($table_categories)) {
                $stmt = $db->query("SELECT * FROM {$table_categories} WHERE category_id = ?", [$product['category_id']]);
                $cat = $stmt->fetch();
                if ($cat) {
                    $categories = [$cat];
                }
            }
            
            // Si aún no hay resultados, intentar con la tabla alternativa
            if (empty($categories) && $catExists) {
                $stmt = $db->query("SELECT * FROM {$altCategoryTable} WHERE category_id = ?", [$product['category_id']]);
                $cat = $stmt->fetch();
                if ($cat) {
                    $categories = [$cat];
                    $table_categories = $altCategoryTable;
                }
            }
        } catch (Exception $e) {
            error_log("Error obteniendo categoría del producto: " . $e->getMessage());
        }
    }
}

// Get product reviews if tables exist - enfoque simplificado
$reviews = [];
if (!empty($table_reviews) && !empty($table_users)) {
    try {
        // Intentar primero con la condición is_approved
        try {
            $stmt = $db->query("SELECT r.*, u.first_name, u.last_name 
                        FROM {$table_reviews} r 
                        JOIN {$table_users} u ON r.user_id = u.user_id 
                        WHERE r.product_id = ? AND r.is_approved = 1
                        ORDER BY r.created_at DESC", [$productId]);
            $reviews = $stmt->fetchAll();
        } catch (Exception $e1) {
            // Si falla, intentar sin la condición is_approved
            try {
                $stmt = $db->query("SELECT r.*, u.first_name, u.last_name 
                            FROM {$table_reviews} r 
                            JOIN {$table_users} u ON r.user_id = u.user_id 
                            WHERE r.product_id = ? 
                            ORDER BY r.created_at DESC", [$productId]);
                $reviews = $stmt->fetchAll();
            } catch (Exception $e2) {
                // Si aún falla, intentar sin ordenar por created_at
                try {
                    $stmt = $db->query("SELECT r.*, u.first_name, u.last_name 
                                FROM {$table_reviews} r 
                                JOIN {$table_users} u ON r.user_id = u.user_id 
                                WHERE r.product_id = ?", [$productId]);
                    $reviews = $stmt->fetchAll();
                } catch (Exception $e3) {
                    // Si todo falla, simplemente dejar el array vacío
                    error_log("Error obteniendo reseñas después de múltiples intentos: " . $e3->getMessage());
                }
            }
        }
    } catch (Exception $e) {
        error_log("Error general obteniendo reseñas: " . $e->getMessage());
    }
}

// Calculate average rating
$totalRating = 0;
foreach ($reviews as $review) {
    $totalRating += $review['rating'] ?? 0;
}
$averageRating = count($reviews) > 0 ? round($totalRating / count($reviews), 1) : 0;

// Stock es siempre true para simplificar
$inStock = true; // Por defecto, considerar que está en stock

// Get related products from the same category - consulta muy simplificada
$relatedProducts = [];
if (!empty($categories) && isset($categories[0]['category_id'])) {
    $categoryId = $categories[0]['category_id'];
    try {
        // Usar consulta directa y simple
        $sql = "SELECT p.*, '' as image_url, p.price as current_price, NULL as old_price
               FROM {$table_products} p 
               WHERE p.product_id != ? AND p.product_id IN (
                   SELECT product_id FROM {$table_product_categories} WHERE category_id = ?
               ) LIMIT 4";
               
        $stmt = $db->query($sql, [$productId, $categoryId]);
        $relatedProducts = $stmt->fetchAll();
        
        // Si tenemos productos relacionados, buscar sus imágenes por separado
        if (!empty($relatedProducts)) {
            foreach ($relatedProducts as &$relProd) {
                try {
                    // Buscar imágenes para este producto
                    $imgStmt = $db->query("SELECT image_path FROM {$table_images} WHERE product_id = ? LIMIT 1", [$relProd['product_id']]);
                    $img = $imgStmt->fetch();
                    if ($img && isset($img['image_path'])) {
                        // Corregir ruta de imagen si es necesario (solución del problema con las rutas relativas)
                        $imgPath = $img['image_path'];
                        if (strpos($imgPath, '../') === 0) {
                            $imgPath = substr($imgPath, 3);
                        }
                        $relProd['image_url'] = $imgPath;
                    } else {
                        $relProd['image_url'] = 'assets/images/product-placeholder.jpg';
                    }
                    
                    // Calcular precio con descuento si existe
                    if (isset($relProd['sale_price']) && $relProd['sale_price'] > 0) {
                        $relProd['current_price'] = $relProd['sale_price'];
                        $relProd['old_price'] = $relProd['price'];
                    } else {
                        $relProd['current_price'] = $relProd['price'];
                        $relProd['old_price'] = null;
                    }
                } catch (Exception $imgErr) {
                    error_log("Error obteniendo imagen para producto relacionado ID {$relProd['product_id']}: " . $imgErr->getMessage());
                }
            }
            unset($relProd); // Romper la referencia
        }
    } catch (Exception $e) {
        error_log("Error obteniendo productos relacionados: " . $e->getMessage());
        // Mantener array vacío en caso de error
    }
}

// Base URL for assets
$base_url = "http://" . $_SERVER['HTTP_HOST'] . "/Tienda/";

// Page title
$pageTitle = htmlspecialchars($product['name']) . ' | Tienda Mall';

// Calculate any discount percentage if there's a sale price
$discountPercentage = 0;
if (!empty($product['sale_price']) && $product['sale_price'] > 0 && $product['price'] > 0) {
    $discountPercentage = round((($product['price'] - $product['sale_price']) / $product['price']) * 100);
}

// Determine stock status
$inStock = isset($product['quantity']) && $product['quantity'] > ($product['reserved_quantity'] ?? 0);
$stockLevel = $inStock ? $product['quantity'] - ($product['reserved_quantity'] ?? 0) : 0;

// Prepare main image path
$mainImagePath = 'assets/images/product-placeholder.png';
if (!empty($images) && isset($images[0]['image_path'])) {
    $mainImagePath = $images[0]['image_path'];
    // Remove any leading '../' from the path
    if (strpos($mainImagePath, '../') === 0) {
        $mainImagePath = substr($mainImagePath, 3);
    }
}

// Start the HTML
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="<?php echo $base_url; ?>pages/ProductDetails.css" />
  <link rel="stylesheet" href="<?php echo $base_url; ?>styles/style.css" />
  <link rel="stylesheet" href="<?php echo $base_url; ?>styles/mobile-nav.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script src="<?php echo $base_url; ?>scripts/cart.js"></script>
  <script src="<?php echo $base_url; ?>scripts/product-details.js"></script>
  <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
  <title><?php echo $pageTitle; ?></title>
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
            <div class="logo"><a href="<?php echo $base_url; ?>">Tienda</a></div>
            <button class="mobile-menu-btn" aria-label="Open Menu"><i class="fa fa-bars"></i></button>
            <nav>
                <a href="<?php echo $base_url; ?>">Home</a>
                <a href="<?php echo $base_url; ?>pages/contact.php">Contact</a>
                <a href="<?php echo $base_url; ?>pages/about.php">About</a>
                <a href="<?php echo $base_url . ($isLoggedIn ? 'pages/account.php' : 'pages/login.php'); ?>"><?php echo $isLoggedIn ? 'Account' : 'Login'; ?></a>
                <a href="<?php echo $base_url . ($isLoggedIn ? 'pages/logout.php' : 'pages/signup.php'); ?>"><?php echo $isLoggedIn ? 'Logout' : 'Sign Up'; ?></a>
            </nav>
            <div class="search-cart">
                <div class="search-box">
                    <input type="text" placeholder="What are you looking for?">
                    <span class="search-icon"><img src="<?php echo $base_url; ?>assets/icons/Search.png" alt="Search" class="icon-img"></span>
                </div>
                <a href="<?php echo $base_url; ?>pages/wishlist.php" class="icon-link"><img src="<?php echo $base_url; ?>assets/icons/Wishlist.png" alt="Wishlist" class="icon-img"></a>
                <a href="<?php echo $base_url; ?>pages/cart.php" class="icon-link"><img src="<?php echo $base_url; ?>assets/icons/Cart1.png" alt="Cart" class="icon-img"><span class="cart-count">0</span></a>
            </div>
        </div>
    </header>

<main class="product-main-section">
    <div class="container product-details-container">
        <!-- Product Images Section -->
        <div class="product-images-section" style="display: flex; flex-direction: row; align-items: flex-start; gap: 24px;">
            <div class="product-thumbs-vertical">
                <div class="thumbnails">
                    <?php 
                    // Display thumbnails
                    foreach ($images as $index => $image):
                        // Process image path
                        $thumbPath = 'assets/images/product-placeholder.png';
                        if (isset($image['image_path'])) {
                            $thumbPath = $image['image_path'];
                            // Remove any leading '../' from the path
                            if (strpos($thumbPath, '../') === 0) {
                                $thumbPath = substr($thumbPath, 3);
                            }
                        }
                    ?>
                    <img src="<?php echo $base_url . $thumbPath; ?>" alt="thumbnail <?php echo $index + 1; ?>" class="thumb-img <?php echo $index === 0 ? 'active' : ''; ?>" data-main-img="<?php echo $base_url . $thumbPath; ?>" />
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="product-main-img-wrap">
                <img class="main-img" alt="<?php echo htmlspecialchars($product['name'] ?? 'Product'); ?>" src="<?php echo $base_url . $mainImagePath; ?>" />
            </div>
        </div>
        
        <!-- Product Info Section -->
        <div class="product-info-section">
            <h2 class="product-title-detail"><?php echo htmlspecialchars($product['name'] ?? $product['title'] ?? 'Product'); ?></h2>
            <div class="product-rating">
                <span class="product-rating-stars" style="color: #FFA800;">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <ion-icon name="<?php echo $i <= $averageRating ? 'star' : 'star-outline'; ?>"></ion-icon>
                    <?php endfor; ?>
                </span>
                <span class="product-rating-count">(<?php echo count($reviews); ?> Reviews)</span>
                <span class="product-in-stock" style="color: <?php echo $inStock ? '#00B517' : '#FF4B4B'; ?>">
                    <?php echo $inStock ? 'In Stock' : 'Out of Stock'; ?>
                </span>
            </div>
            
            <div class="product-price">
                <?php if (isset($product['sale_price']) && $product['sale_price'] > 0): ?>
                    $<?php echo number_format($product['sale_price'], 2); ?>
                    <span style="text-decoration: line-through; color: #999; margin-left: 10px;">$<?php echo number_format($product['price'], 2); ?></span>
                <?php else: ?>
                    $<?php echo number_format($product['price'], 2); ?>
                <?php endif; ?>
            </div>
            
            <div class="product-desc product-desc-detail">
                <?php echo nl2br(htmlspecialchars($product['description'] ?? '')); ?>
            </div>
            
            <div class="product-actions" style="display: flex; gap: 15px; margin: 20px 0;">
                <!-- Botón de añadir a wishlist -->
                <button class="icon-btn wishlist-icon" title="Add to Wishlist" data-product-id="<?php echo $productId; ?>">
                    <i class="fa fa-heart"></i>
                </button>
                <span style="display: flex; align-items: center; margin-left: 8px; color: #DB4444; font-weight: 500;">Añadir a favoritos</span>
            </div>
            
            <hr style="margin: 10px 0; border: 0.5px solid #eee;" />
            
            <?php if (!empty($product['colors'])): ?>
            <div class="product-options">
                <span class="product-options-label">Colours:</span>
                <div class="product-colors">
                    <?php 
                    $colors = explode(',', $product['colors']);
                    foreach ($colors as $index => $color): 
                        $colorValue = trim($color);
                        if (empty($colorValue)) continue;
                    ?>
                    <span class="color-dot <?php echo $index === 0 ? 'selected' : ''; ?>" style="background: <?php echo $colorValue; ?>"></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($product['sizes'])): ?>
            <div class="product-options">
                <span class="product-options-label">Size:</span>
                <div class="product-sizes">
                    <?php 
                    $sizes = explode(',', $product['sizes']);
                    foreach ($sizes as $index => $size): 
                        $sizeValue = trim($size);
                        if (empty($sizeValue)) continue;
                    ?>
                    <button class="size-btn <?php echo $index === 0 ? 'selected' : ''; ?>"><?php echo $sizeValue; ?></button>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($inStock): ?>
            <div class="product-actions">
                <div class="qty-box">
                    <button class="qty-btn qty-minus">-</button>
                    <span class="qty-number">1</span>
                    <button class="qty-btn qty-plus">+</button>
                </div>
                <button class="buy-now-btn add-to-cart-btn" data-product-id="<?php echo $product['product_id']; ?>">Add to Cart</button>
                <button class="heart-btn" title="Add to wishlist">
                    <ion-icon name="heart-outline"></ion-icon>
                </button>
            </div>
            <?php else: ?>
            <p class="out-of-stock-message">This product is currently out of stock. Please check back later or contact us for more information.</p>
            <?php endif; ?>
            
            <div class="product-delivery-box">
                <div class="product-delivery-row">
                    <ion-icon name="car-outline"></ion-icon>
                    <div>
                        <b>Free Delivery</b><br>
                        Enter your postal code for Delivery Availability
                    </div>
                </div>
                <div class="product-delivery-row">
                    <ion-icon name="refresh-outline"></ion-icon>
                    <div>
                        <b>Return Delivery</b><br>
                        Free 30 Days Delivery Returns. <a href="#">Details</a>
                    </div>
                </div>
            </div>
        </div>
        
</div>
</main>

<!-- Related Item Section Start -->
<?php if (!empty($relatedProducts)): ?>
<section class="related-item-section">
  <div class="container">
    <div class="related-title-row">
      <span class="related-title-icon"></span>
      <span class="related-title-text">Related Item</span>
    </div>
    <div class="related-cards-grid">
      <?php foreach ($relatedProducts as $index => $relatedProduct): 
        // Determine correct image path for related product
        $relatedProductImage = 'assets/images/product-placeholder.png';
        if (isset($relatedProduct['image_url'])) {
            $relatedProductImage = $relatedProduct['image_url'];
        } elseif (isset($relatedProduct['image_path'])) {
            $relatedProductImage = $relatedProduct['image_path'];
        }
        
        // Remove any leading '../' from the path
        if (strpos($relatedProductImage, '../') === 0) {
            $relatedProductImage = substr($relatedProductImage, 3);
        }
        
        // Calculate discount if there's an old price
        $discount = 0;
        if (!empty($relatedProduct['old_price']) && $relatedProduct['old_price'] > $relatedProduct['current_price']) {
            $discount = round((($relatedProduct['old_price'] - $relatedProduct['current_price']) / $relatedProduct['old_price']) * 100);
        }
        
        // Get product name from title or name field
        $productName = htmlspecialchars($relatedProduct['name'] ?? $relatedProduct['title'] ?? 'Product');
      ?>
      <div class="related-card">
        <?php if ($discount > 0): ?>
        <div class="related-badge">-<?php echo $discount; ?>%</div>
        <?php endif; ?>
        <div class="related-card-fav"><span class="icon-heart"><ion-icon name="heart-outline"></ion-icon></span></div>
        <div class="related-card-eye"><span class="icon-eye"><ion-icon name="eye-outline"></ion-icon></span></div>
        <div class="related-card-img">
          <a href="ProductDetails.php?id=<?php echo $relatedProduct['product_id']; ?>">
            <img src="<?php echo $base_url . $relatedProductImage; ?>" alt="<?php echo $productName; ?>" />
          </a>
        </div>
        <div class="related-card-title"><?php echo $productName; ?></div>
        <div class="related-card-price-row">
          <span class="related-card-price">$<?php echo number_format($relatedProduct['current_price'], 2); ?></span>
          <?php if (!empty($relatedProduct['old_price'])): ?>
          <span class="related-card-oldprice">$<?php echo number_format($relatedProduct['old_price'], 2); ?></span>
          <?php endif; ?>
        </div>
        <div class="related-card-rating">
          <?php 
          // Show random rating for demo purposes (in a real app, you'd calculate this from reviews)
          $ratingValue = rand(35, 50) / 10; // Random rating between 3.5-5.0
          $reviewCount = rand(40, 100); // Random review count for demo
          
          for ($i = 1; $i <= 5; $i++): ?>
          <span class="star"><?php echo $i <= $ratingValue ? '★' : '☆'; ?></span>
          <?php endfor; ?>
          <span class="related-card-reviews">(<?php echo $reviewCount; ?>)</span>
        </div>
        <button class="related-card-add" data-product-id="<?php echo $relatedProduct['product_id']; ?>">Add To Cart</button>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
<!-- Related Item Section End -->

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
              <li><a href="#">My Account</a></li>
              <li><a href="#">Login / Register</a></li>
              <li><a href="#">Cart</a></li>
              <li><a href="#">Wishlist</a></li>
              <li><a href="#">Shop</a></li>
          </ul>
      </div>
      <div class="footer-col">
          <div class="footer-title">Quick Link</div>
          <ul class="footer-list">
              <li><a href="#">Privacy Policy</a></li>
              <li><a href="#">Terms Of Use</a></li>
              <li><a href="#">FAQ</a></li>
              <li><a href="#">Contact</a></li>
          </ul>
      </div>
  </div>
  <div class="copyright">
      <div class="container">
          <p>&copy; Copyright Tienda <?php echo date('Y'); ?>. All rights reserved</p>
      </div>
  </div>
</footer>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Manejar clic en el botón de wishlist
    const wishlistButtons = document.querySelectorAll('.wishlist-icon');
    
    wishlistButtons.forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-product-id');
            
            // Añadir efecto visual
            this.classList.add('active');
            const heartIcon = this.querySelector('i.fa-heart');
            heartIcon.style.color = '#DB4444';
            
            // Enviar solicitud AJAX para añadir a la wishlist
            fetch('../api/wishlist.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    product_id: productId,
                    action: 'add'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mostrar mensaje de éxito
                    alert('Producto añadido a favoritos');
                } else if (data.error === 'not_logged_in') {
                    // Redirigir al login
                    window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
                } else {
                    alert(data.message || 'No se pudo añadir a favoritos');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al agregar a favoritos');
            });
        });
    });
});
</script>
<style>
.icon-btn.wishlist-icon {
    background: none;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: #F5F5F5;
    transition: all 0.3s ease;
}

.icon-btn.wishlist-icon:hover {
    background-color: #FFE1E1;
}

.icon-btn.wishlist-icon.active {
    background-color: #FFE1E1;
}

.icon-btn.wishlist-icon .fa-heart {
    font-size: 18px;
    color: #444;
}

.icon-btn.wishlist-icon:hover .fa-heart,
.icon-btn.wishlist-icon.active .fa-heart {
    color: #DB4444;
}
</style>
</body>
</html>
