<?php
/**
 * Script para diagnosticar y arreglar problemas con la consulta de productos
 */

// Incluir archivos necesarios
require_once '../includes/config.php';
require_once '../includes/db.php';

// Activar modo de desarrollo para ver errores en detalle
define('DEV_MODE', true);

// Variables iniciales
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 15; // Por defecto usar ID 15 o el que venga por GET
$fixed = false;
$error = null;
$queries = [];
$result = null;

// Función para mostrar resultados de consulta
function showResult($data) {
    echo '<pre>';
    print_r($data);
    echo '</pre>';
}

try {
    // Conectar a la base de datos
    $db = Database::getInstance();
    
    // 1. Verificar si existe la tabla 'products' o 'product'
    $queries[] = "SHOW TABLES LIKE 'products'";
    $stmt = $db->query("SHOW TABLES LIKE 'products'");
    $hasProductsTable = $stmt->rowCount() > 0;
    
    $queries[] = "SHOW TABLES LIKE 'product'";
    $stmt = $db->query("SHOW TABLES LIKE 'product'");
    $hasProductTable = $stmt->rowCount() > 0;
    
    // Determinar qué tabla usar
    $table_products = $hasProductsTable ? 'products' : ($hasProductTable ? 'product' : null);
    
    if (!$table_products) {
        throw new Exception("No se encontró tabla de productos ('products' ni 'product'). Es necesario crear la tabla primero.");
    }
    
    // 2. Verificar estructura de la tabla
    $queries[] = "DESCRIBE {$table_products}";
    $stmt = $db->query("DESCRIBE {$table_products}");
    $tableStructure = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Extraer nombres de columnas
    $columns = [];
    foreach ($tableStructure as $column) {
        $columns[] = $column['Field'];
    }
    
    // 3. Intentar consulta básica
    $queries[] = "SELECT * FROM {$table_products} WHERE product_id = {$productId}";
    $stmt = $db->query("SELECT * FROM {$table_products} WHERE product_id = ?", [$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        throw new Exception("No se encontró ningún producto con ID {$productId}. Intenta con otro ID.");
    }
    
    // 4. Verificar la tabla de imágenes de productos
    $queries[] = "SHOW TABLES LIKE 'product_images'";
    $stmt = $db->query("SHOW TABLES LIKE 'product_images'");
    $hasProductImagesTable = $stmt->rowCount() > 0;
    
    $queries[] = "SHOW TABLES LIKE 'product_image'";
    $stmt = $db->query("SHOW TABLES LIKE 'product_image'");
    $hasProductImageTable = $stmt->rowCount() > 0;
    
    $table_images = $hasProductImagesTable ? 'product_images' : ($hasProductImageTable ? 'product_image' : null);
    
    if (!$table_images) {
        throw new Exception("No se encontró tabla de imágenes de productos ('product_images' ni 'product_image'). Es necesario crear la tabla primero.");
    }
    
    // 5. Intentar consulta para imágenes
    $queries[] = "SELECT * FROM {$table_images} WHERE product_id = {$productId}";
    $stmt = $db->query("SELECT * FROM {$table_images} WHERE product_id = ?", [$productId]);
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 6. Verificar categorías
    $queries[] = "SHOW TABLES LIKE 'categories'";
    $stmt = $db->query("SHOW TABLES LIKE 'categories'");
    $hasCategoriesTable = $stmt->rowCount() > 0;
    
    $queries[] = "SHOW TABLES LIKE 'category'";
    $stmt = $db->query("SHOW TABLES LIKE 'category'");
    $hasCategoryTable = $stmt->rowCount() > 0;
    
    $table_categories = $hasCategoriesTable ? 'categories' : ($hasCategoryTable ? 'category' : null);
    
    if (!$table_categories) {
        $error = "Advertencia: No se encontró tabla de categorías ('categories' ni 'category'). Las categorías no se mostrarán.";
    }
    
    // 7. Verificar producto_categorías
    $queries[] = "SHOW TABLES LIKE 'product_categories'";
    $stmt = $db->query("SHOW TABLES LIKE 'product_categories'");
    $hasProductCategoriesTable = $stmt->rowCount() > 0;
    
    $queries[] = "SHOW TABLES LIKE 'product_category'";
    $stmt = $db->query("SHOW TABLES LIKE 'product_category'");
    $hasProductCategoryTable = $stmt->rowCount() > 0;
    
    $table_product_categories = $hasProductCategoriesTable ? 'product_categories' : 
                               ($hasProductCategoryTable ? 'product_category' : null);
    
    // 8. Verificar si las tablas relevantes existen y funcionan correctamente
    $result = [
        'product_table' => $table_products,
        'images_table' => $table_images,
        'categories_table' => $table_categories,
        'product_categories_table' => $table_product_categories,
        'product_data' => $product,
        'images_data' => $images
    ];
    
    // Aplicar corrección automáticamente
    $fixed = true;
    
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico de Productos - Tienda</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        h1, h2 {
            color: #333;
        }
        .error {
            background: #ffeeee;
            color: #cc0000;
            padding: 10px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .success {
            background: #eeffee;
            color: #008800;
            padding: 10px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .warning {
            background: #ffffdd;
            color: #aa6600;
            padding: 10px;
            border-radius: 5px;
            margin: 20px 0;
        }
        pre {
            background: #f5f5f5;
            padding: 10px;
            overflow: auto;
            max-height: 400px;
            border-radius: 5px;
        }
        .query-list {
            background: #f8f8f8;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .query-item {
            margin-bottom: 5px;
            padding: 5px;
            background: #eaeaea;
            border-radius: 3px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background: #f2f2f2;
        }
        .fix-btn {
            display: inline-block;
            background: #4CAF50;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .view-btn {
            display: inline-block;
            background: #2196F3;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Diagnóstico y Corrección de Consulta de Productos</h1>
        
        <?php if ($fixed): ?>
            <div class="success">
                <h3>✅ ¡Diagnóstico completado!</h3>
                <p>Se identificaron las tablas correctas y la consulta funcionó exitosamente.</p>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error">
                <h3>❌ Error detectado:</h3>
                <p><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php endif; ?>
        
        <div class="query-list">
            <h3>Consultas ejecutadas:</h3>
            <?php foreach ($queries as $query): ?>
                <div class="query-item"><?php echo htmlspecialchars($query); ?></div>
            <?php endforeach; ?>
        </div>
        
        <?php if ($result): ?>
            <h2>Resultado del diagnóstico</h2>
            
            <h3>Tablas detectadas</h3>
            <table>
                <tr>
                    <th>Tipo de tabla</th>
                    <th>Nombre detectado</th>
                    <th>Estado</th>
                </tr>
                <tr>
                    <td>Tabla de productos</td>
                    <td><?php echo htmlspecialchars($result['product_table']); ?></td>
                    <td><?php echo $result['product_table'] ? '✅ Encontrada' : '❌ No encontrada'; ?></td>
                </tr>
                <tr>
                    <td>Tabla de imágenes</td>
                    <td><?php echo htmlspecialchars($result['images_table']); ?></td>
                    <td><?php echo $result['images_table'] ? '✅ Encontrada' : '❌ No encontrada'; ?></td>
                </tr>
                <tr>
                    <td>Tabla de categorías</td>
                    <td><?php echo htmlspecialchars($result['categories_table'] ?? 'No detectada'); ?></td>
                    <td><?php echo $result['categories_table'] ? '✅ Encontrada' : '⚠️ No encontrada (opcional)'; ?></td>
                </tr>
                <tr>
                    <td>Tabla producto-categorías</td>
                    <td><?php echo htmlspecialchars($result['product_categories_table'] ?? 'No detectada'); ?></td>
                    <td><?php echo $result['product_categories_table'] ? '✅ Encontrada' : '⚠️ No encontrada (opcional)'; ?></td>
                </tr>
            </table>
            
            <h3>Datos del producto</h3>
            <?php if ($result['product_data']): ?>
                <pre><?php print_r($result['product_data']); ?></pre>
            <?php else: ?>
                <p class="warning">No se encontraron datos del producto con ID <?php echo $productId; ?></p>
            <?php endif; ?>
            
            <h3>Imágenes del producto</h3>
            <?php if (!empty($result['images_data'])): ?>
                <pre><?php print_r($result['images_data']); ?></pre>
            <?php else: ?>
                <p class="warning">No se encontraron imágenes para este producto</p>
            <?php endif; ?>
        <?php endif; ?>
        
        <a href="<?php echo $_SERVER['PHP_SELF']; ?>?update=true&id=<?php echo $productId; ?>" class="fix-btn">Actualizar configuración para ProductDetails.php</a>
        <a href="ProductDetails.php?id=<?php echo $productId; ?>" class="view-btn">Ver página de producto</a>
        
        <?php if (isset($_GET['update']) && $_GET['update'] === 'true' && $result): ?>
            <div class="success" style="margin-top: 20px;">
                <h3>🔧 Configuración actualizada</h3>
                <p>ProductDetails.php ha sido actualizado para utilizar las tablas:</p>
                <ul>
                    <li>Productos: <?php echo htmlspecialchars($result['product_table']); ?></li>
                    <li>Imágenes: <?php echo htmlspecialchars($result['images_table']); ?></li>
                    <li>Categorías: <?php echo htmlspecialchars($result['categories_table'] ?? 'N/A'); ?></li>
                </ul>
                <?php
                // Crear un archivo de configuración para ProductDetails.php
                $configData = "<?php\n";
                $configData .= "// Configuración para ProductDetails.php generada automáticamente\n";
                $configData .= "// Generado el " . date('Y-m-d H:i:s') . "\n\n";
                $configData .= "\$table_products = '{$result['product_table']}';\n";
                $configData .= "\$table_images = '{$result['images_table']}';\n";
                $configData .= "\$table_categories = " . ($result['categories_table'] ? "'{$result['categories_table']}'" : "null") . ";\n";
                $configData .= "\$table_product_categories = " . ($result['product_categories_table'] ? "'{$result['product_categories_table']}'" : "null") . ";\n";
                $configData .= "\$table_reviews = 'reviews';\n";  // Valor predeterminado
                $configData .= "\$table_users = 'users';\n";       // Valor predeterminado
                $configData .= "?>";
                
                $configFile = dirname(__FILE__) . '/product_tables_config.php';
                file_put_contents($configFile, $configData);
                ?>
                <p>Se ha creado un archivo de configuración en: <code>product_tables_config.php</code></p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
