<?php
/**
 * Product by Name - Obtener producto por nombre/slug sin necesitar ID
 * Esta versión alternativa muestra cómo se puede acceder a un producto sin ID
 */

// Incluir bootstrap para cargar todas las dependencias necesarias
require_once '../includes/bootstrap.php';

// Verificar si la sesión está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
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
$isLoggedIn = isset($_SESSION['user']) && !empty($_SESSION['user']);
$userName = '';

// Determinar el método de búsqueda de producto
$productName = isset($_GET['name']) ? trim($_GET['name']) : '';
$productSlug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
$searchKeyword = isset($_GET['q']) ? trim($_GET['q']) : '';

// Métodos adicionales de búsqueda
$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$minPrice = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$maxPrice = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 0;
$sku = isset($_GET['sku']) ? trim($_GET['sku']) : '';
$barcode = isset($_GET['barcode']) ? trim($_GET['barcode']) : '';
$productFeature = isset($_GET['feature']) ? trim($_GET['feature']) : '';

// Si no hay ningún parámetro, mostrar un formulario de búsqueda
if (empty($productName) && empty($productSlug) && empty($searchKeyword) && 
    $categoryId == 0 && $minPrice == 0 && $maxPrice == 0 && 
    empty($sku) && empty($barcode) && empty($productFeature)) {
    // Mostrar formulario de búsqueda
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Buscar Producto - Tienda Mall</title>
        <link rel="stylesheet" href="../assets/css/style.css">
        <style>
            .search-container {
                max-width: 600px;
                margin: 50px auto;
                padding: 20px;
                background-color: #f8f9fa;
                border-radius: 10px;
                box-shadow: 0 0 10px rgba(0,0,0,0.1);
            }
            h1 {
                text-align: center;
                color: #333;
                margin-bottom: 20px;
            }
            .search-form {
                display: flex;
                flex-direction: column;
                gap: 15px;
            }
            .form-group {
                display: flex;
                flex-direction: column;
                gap: 5px;
            }
            .form-row {
                display: flex;
                gap: 15px;
            }
            .half {
                flex: 1;
            }
            h2 {
                margin-top: 20px;
                margin-bottom: 10px;
                color: #555;
                border-bottom: 1px solid #ddd;
                padding-bottom: 5px;
            }
            label {
                font-weight: bold;
                color: #555;
            }
            input {
                padding: 10px;
                border: 1px solid #ddd;
                border-radius: 5px;
            }
            button {
                padding: 12px;
                background-color: #DB4444;
                color: white;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                font-weight: bold;
                transition: background-color 0.3s;
            }
            button:hover {
                background-color: #c13333;
            }
            .hint {
                font-size: 0.9em;
                color: #666;
                margin-top: 5px;
            }
            .back-link {
                display: block;
                text-align: center;
                margin-top: 20px;
                color: #555;
                text-decoration: none;
            }
            .back-link:hover {
                text-decoration: underline;
            }
        </style>
    </head>
    <body>
        <div class="search-container">
            <h1>Buscar Productos</h1>
            <form class="search-form" method="GET" action="">
                <h2>Búsqueda Básica</h2>
                <div class="form-group">
                    <label for="name">Buscar por nombre exacto:</label>
                    <input type="text" id="name" name="name" placeholder="Nombre exacto del producto">
                    <span class="hint">Ejemplo: "Smartphone Samsung Galaxy S21"</span>
                </div>
                
                <div class="form-group">
                    <label for="slug">Buscar por slug:</label>
                    <input type="text" id="slug" name="slug" placeholder="slug-del-producto">
                    <span class="hint">Ejemplo: "smartphone-samsung-galaxy-s21"</span>
                </div>
                
                <div class="form-group">
                    <label for="q">Búsqueda por palabra clave:</label>
                    <input type="text" id="q" name="q" placeholder="Palabra clave">
                    <span class="hint">Ejemplo: "smartphone" o "samsung"</span>
                </div>
                
                <h2>Búsqueda Avanzada</h2>
                <div class="form-group">
                    <label for="category">Buscar por categoría:</label>
                    <input type="number" id="category" name="category" placeholder="ID de la categoría">
                    <span class="hint">Ejemplo: 5 (para Electrónicos)</span>
                </div>
                
                <div class="form-row">
                    <div class="form-group half">
                        <label for="min_price">Precio mínimo:</label>
                        <input type="number" id="min_price" name="min_price" placeholder="0" step="0.01">
                    </div>
                    <div class="form-group half">
                        <label for="max_price">Precio máximo:</label>
                        <input type="number" id="max_price" name="max_price" placeholder="9999" step="0.01">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="sku">Código SKU:</label>
                    <input type="text" id="sku" name="sku" placeholder="SKU-12345">
                </div>
                
                <div class="form-group">
                    <label for="barcode">Código de barras:</label>
                    <input type="text" id="barcode" name="barcode" placeholder="123456789012">
                </div>
                
                <div class="form-group">
                    <label for="feature">Característica del producto:</label>
                    <input type="text" id="feature" name="feature" placeholder="8GB RAM, Cámara 48MP, etc">
                </div>
                
                <button type="submit">Buscar Producto</button>
            </form>
            
            <a href="../index.php" class="back-link">← Volver a la página principal</a>
        </div>
    </body>
    </html>
    <?php
    exit;
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

// Obtener el producto de la base de datos
$product = null;
$productId = null;

// Determinar la condición de búsqueda según los parámetros proporcionados
if ($table_products) {
    try {
        if (!empty($productName)) {
            // Buscar por nombre exacto
            $stmt = $db->query("SELECT * FROM {$table_products} WHERE name = ?", [$productName]);
            $product = $stmt->fetch();
        } elseif (!empty($productSlug)) {
            // Buscar por slug (verificar primero si existe la columna slug)
            $hasSlug = false;
            try {
                $columnCheck = $db->query("SHOW COLUMNS FROM {$table_products} LIKE 'slug'");
                $hasSlug = ($columnCheck->rowCount() > 0);
            } catch (Exception $e) {
                // Ignorar errores
            }
            
            if ($hasSlug) {
                $stmt = $db->query("SELECT * FROM {$table_products} WHERE slug = ?", [$productSlug]);
                $product = $stmt->fetch();
            } else {
                // Si no hay columna slug, generar un slug a partir del nombre y buscar
                $stmt = $db->query("SELECT * FROM {$table_products} WHERE REPLACE(LOWER(name), ' ', '-') = ?", [$productSlug]);
                $product = $stmt->fetch();
            }
        } elseif (!empty($searchKeyword)) {
            // Buscar por palabra clave (LIKE search)
            $searchTerm = "%{$searchKeyword}%";
            $stmt = $db->query("SELECT * FROM {$table_products} WHERE name LIKE ? OR description LIKE ? LIMIT 1", [$searchTerm, $searchTerm]);
            $product = $stmt->fetch();
        } elseif ($categoryId > 0) {
            // Buscar por categoría
            if (!empty($table_product_categories)) {
                $stmt = $db->query("SELECT p.* FROM {$table_products} p 
                               JOIN {$table_product_categories} pc ON p.product_id = pc.product_id 
                               WHERE pc.category_id = ? LIMIT 1", [$categoryId]);
                $product = $stmt->fetch();
            } else {
                // Intentar buscar directamente en la tabla de productos si tiene columna category_id
                $hasCategoryColumn = false;
                try {
                    $columnCheck = $db->query("SHOW COLUMNS FROM {$table_products} LIKE 'category_id'");
                    $hasCategoryColumn = ($columnCheck->rowCount() > 0);
                } catch (Exception $e) {
                    // Ignorar errores
                }
                
                if ($hasCategoryColumn) {
                    $stmt = $db->query("SELECT * FROM {$table_products} WHERE category_id = ? LIMIT 1", [$categoryId]);
                    $product = $stmt->fetch();
                }
            }
        } elseif ($minPrice > 0 || $maxPrice > 0) {
            // Buscar por rango de precios
            $conditions = [];
            $params = [];
            
            if ($minPrice > 0) {
                $conditions[] = "price >= ?"; 
                $params[] = $minPrice;
            }
            
            if ($maxPrice > 0) {
                $conditions[] = "price <= ?"; 
                $params[] = $maxPrice;
            }
            
            if (!empty($conditions)) {
                $sql = "SELECT * FROM {$table_products} WHERE " . implode(" AND ", $conditions) . " LIMIT 1";
                $stmt = $db->query($sql, $params);
                $product = $stmt->fetch();
            }
        } elseif (!empty($sku)) {
            // Buscar por SKU
            $hasSkuColumn = false;
            try {
                $columnCheck = $db->query("SHOW COLUMNS FROM {$table_products} LIKE 'sku'");
                $hasSkuColumn = ($columnCheck->rowCount() > 0);
            } catch (Exception $e) {
                // Ignorar errores
            }
            
            if ($hasSkuColumn) {
                $stmt = $db->query("SELECT * FROM {$table_products} WHERE sku = ?", [$sku]);
                $product = $stmt->fetch();
            }
        } elseif (!empty($barcode)) {
            // Buscar por código de barras
            $hasBarcodeColumn = false;
            $possibleColumns = ['barcode', 'upc', 'ean', 'isbn', 'code'];
            
            foreach ($possibleColumns as $column) {
                try {
                    $columnCheck = $db->query("SHOW COLUMNS FROM {$table_products} LIKE '{$column}'");
                    if ($columnCheck->rowCount() > 0) {
                        $stmt = $db->query("SELECT * FROM {$table_products} WHERE {$column} = ?", [$barcode]);
                        $product = $stmt->fetch();
                        if ($product) break;
                    }
                } catch (Exception $e) {
                    // Ignorar errores
                }
            }
        } elseif (!empty($productFeature)) {
            // Buscar por característica
            $searchFeature = "%{$productFeature}%";
            $possibleColumns = ['features', 'specifications', 'description', 'details'];
            
            foreach ($possibleColumns as $column) {
                try {
                    $columnCheck = $db->query("SHOW COLUMNS FROM {$table_products} LIKE '{$column}'");
                    if ($columnCheck->rowCount() > 0) {
                        $stmt = $db->query("SELECT * FROM {$table_products} WHERE {$column} LIKE ?", [$searchFeature]);
                        $product = $stmt->fetch();
                        if ($product) break;
                    }
                } catch (Exception $e) {
                    // Ignorar errores
                }
            }
        }
    } catch (Exception $e) {
        error_log("Error al buscar producto por nombre/slug: " . $e->getMessage());
    }
}

// Si encontramos un producto, obtener su ID para usarlo en las consultas de relaciones
if ($product && isset($product['product_id'])) {
    $productId = $product['product_id'];
} else {
    // Si no se encontró el producto, intentar listar productos que coincidan con los criterios
    $matchingProducts = [];
    try {
        $conditions = [];
        $params = [];
        $searchTermUsed = false;
        
        // Construir condiciones según los parámetros proporcionados
        if (!empty($productName) || !empty($searchKeyword)) {
            $searchTerm = !empty($productName) ? "%{$productName}%" : "%{$searchKeyword}%";
            $conditions[] = "name LIKE ?";
            $params[] = $searchTerm;
            $searchTermUsed = true;
        }
        
        if ($categoryId > 0) {
            // Si tiene tabla de relación product_categories
            if (!empty($table_product_categories)) {
                try {
                    $stmt = $db->query("SELECT p.product_id, p.name FROM {$table_products} p 
                                   JOIN {$table_product_categories} pc ON p.product_id = pc.product_id 
                                   WHERE pc.category_id = ? LIMIT 10", [$categoryId]);
                    $matchingProducts = $stmt->fetchAll();
                } catch (Exception $e) {
                    // Ignorar errores
                }
            } else {
                // Intentar con columna category_id en products
                $hasCategoryColumn = false;
                try {
                    $columnCheck = $db->query("SHOW COLUMNS FROM {$table_products} LIKE 'category_id'");
                    $hasCategoryColumn = ($columnCheck->rowCount() > 0);
                } catch (Exception $e) {
                    // Ignorar errores
                }
                
                if ($hasCategoryColumn) {
                    $conditions[] = "category_id = ?";
                    $params[] = $categoryId;
                }
            }
        }
        
        if ($minPrice > 0) {
            $conditions[] = "price >= ?";
            $params[] = $minPrice;
        }
        
        if ($maxPrice > 0) {
            $conditions[] = "price <= ?";
            $params[] = $maxPrice;
        }
        
        if (!empty($sku)) {
            $hasSkuColumn = false;
            try {
                $columnCheck = $db->query("SHOW COLUMNS FROM {$table_products} LIKE 'sku'");
                $hasSkuColumn = ($columnCheck->rowCount() > 0);
            } catch (Exception $e) {
                // Ignorar errores
            }
            
            if ($hasSkuColumn) {
                $conditions[] = "sku = ?";
                $params[] = $sku;
            }
        }
        
        if (!empty($barcode)) {
            $barcodeColumn = null;
            $possibleColumns = ['barcode', 'upc', 'ean', 'isbn', 'code'];
            
            foreach ($possibleColumns as $column) {
                try {
                    $columnCheck = $db->query("SHOW COLUMNS FROM {$table_products} LIKE '{$column}'");
                    if ($columnCheck->rowCount() > 0) {
                        $barcodeColumn = $column;
                        break;
                    }
                } catch (Exception $e) {
                    // Ignorar errores
                }
            }
            
            if ($barcodeColumn) {
                $conditions[] = "{$barcodeColumn} = ?";
                $params[] = $barcode;
            }
        }
        
        if (!empty($productFeature)) {
            $featureColumn = null;
            $possibleColumns = ['features', 'specifications', 'description', 'details'];
            
            foreach ($possibleColumns as $column) {
                try {
                    $columnCheck = $db->query("SHOW COLUMNS FROM {$table_products} LIKE '{$column}'");
                    if ($columnCheck->rowCount() > 0) {
                        $featureColumn = $column;
                        break;
                    }
                } catch (Exception $e) {
                    // Ignorar errores
                }
            }
            
            if ($featureColumn) {
                $conditions[] = "{$featureColumn} LIKE ?";
                $params[] = "%{$productFeature}%";
            }
        }
        
        // Si hay condiciones, construir la consulta
        if (!empty($conditions) && (count($conditions) > 1 || !$searchTermUsed || count($matchingProducts) == 0)) {
            $sql = "SELECT product_id, name FROM {$table_products} WHERE " . implode(" AND ", $conditions) . " LIMIT 10";
            $stmt = $db->query($sql, $params);
            $matchingProducts = $stmt->fetchAll();
        } elseif (empty($matchingProducts) && $searchTermUsed) {
            // Si aún no hay resultados y hay un término de búsqueda, intentar con la consulta simple
            $stmt = $db->query("SELECT product_id, name FROM {$table_products} WHERE name LIKE ? LIMIT 10", [$searchTerm]);
            $matchingProducts = $stmt->fetchAll();
        }
    } catch (Exception $e) {
        // Ignorar errores
    }
    
    // Mostrar resultados de búsqueda o error
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Resultados de Búsqueda - Tienda Mall</title>
        <link rel="stylesheet" href="../assets/css/style.css">
        <style>
            .results-container {
                max-width: 800px;
                margin: 50px auto;
                padding: 20px;
                background-color: #f8f9fa;
                border-radius: 10px;
                box-shadow: 0 0 10px rgba(0,0,0,0.1);
            }
            h1 {
                text-align: center;
                color: #333;
                margin-bottom: 20px;
            }
            .results-list {
                list-style: none;
                padding: 0;
            }
            .results-list li {
                padding: 15px;
                border-bottom: 1px solid #eee;
            }
            .results-list li:last-child {
                border-bottom: none;
            }
            .results-list a {
                color: #DB4444;
                font-weight: bold;
                text-decoration: none;
            }
            .results-list a:hover {
                text-decoration: underline;
            }
            .no-results {
                text-align: center;
                color: #666;
                padding: 20px;
            }
            .back-link {
                display: block;
                text-align: center;
                margin-top: 20px;
                color: #555;
                text-decoration: none;
            }
            .back-link:hover {
                text-decoration: underline;
            }
            .search-term {
                font-style: italic;
                color: #DB4444;
            }
        </style>
    </head>
    <body>
        <div class="results-container">
            <h1>Resultados de Búsqueda</h1>
            
            <?php if (!empty($matchingProducts)): ?>
                <p>Se encontraron los siguientes productos para <span class="search-term"><?php echo htmlspecialchars(!empty($productName) ? $productName : $searchKeyword); ?></span>:</p>
                <ul class="results-list">
                    <?php foreach ($matchingProducts as $matchingProduct): ?>
                        <li>
                            <a href="ProductDetails.php?id=<?php echo $matchingProduct['product_id']; ?>">
                                <?php echo htmlspecialchars($matchingProduct['name']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="no-results">No se encontraron productos que coincidan con tu búsqueda.</p>
            <?php endif; ?>
            
            <a href="product_by_name.php" class="back-link">← Volver a la búsqueda</a>
            <a href="../index.php" class="back-link">← Volver a la página principal</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Si llegamos a este punto, tenemos un producto válido con su ID
// Redirigir a ProductDetails.php con el ID para aprovechar toda la lógica ya implementada
header("Location: ProductDetails.php?id={$productId}");
exit;
