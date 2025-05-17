<?php
/**
 * Diagnóstico y solución específica para el producto ID=15
 * Este script analizará por qué falla la consulta para este producto
 */

require_once '../includes/bootstrap.php';

// Asegurarse de tener una sesión iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Función para verificar si existe una tabla
function tableExists($db, $tableName) {
    try {
        $result = $db->query("SHOW TABLES LIKE ?", [$tableName]);
        return $result->rowCount() > 0;
    } catch (Exception $e) {
        return false;
    }
}

// Función para verificar si una columna existe en una tabla
function columnExists($db, $tableName, $columnName) {
    try {
        $result = $db->query("SHOW COLUMNS FROM {$tableName} LIKE ?", [$columnName]);
        return $result->rowCount() > 0;
    } catch (Exception $e) {
        return false;
    }
}

// Inicializar conexión a la base de datos
$db = Database::getInstance();

// Obtener información de las tablas potenciales
$productId = 15;
$tables = [
    'products' => tableExists($db, 'products'),
    'product' => tableExists($db, 'product'),
    'product_images' => tableExists($db, 'product_images'),
    'product_image' => tableExists($db, 'product_image'),
    'categories' => tableExists($db, 'categories'),
    'category' => tableExists($db, 'category'),
    'product_categories' => tableExists($db, 'product_categories'),
    'product_category' => tableExists($db, 'product_category')
];

// Determinar qué tablas existen realmente
$productTable = $tables['products'] ? 'products' : ($tables['product'] ? 'product' : null);
$imageTable = $tables['product_images'] ? 'product_images' : ($tables['product_image'] ? 'product_image' : null);
$categoryTable = $tables['categories'] ? 'categories' : ($tables['category'] ? 'category' : null);
$productCategoryTable = $tables['product_categories'] ? 'product_categories' : ($tables['product_category'] ? 'product_category' : null);

// Estilo básico para la página
echo '<!DOCTYPE html>
<html>
<head>
    <title>Diagnóstico para Producto ID=15</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1, h2 { color: #333; }
        .success { background-color: #dff0d8; color: #3c763d; padding: 10px; border-radius: 5px; }
        .error { background-color: #f2dede; color: #a94442; padding: 10px; border-radius: 5px; }
        .warning { background-color: #fcf8e3; color: #8a6d3b; padding: 10px; border-radius: 5px; }
        .info { background-color: #d9edf7; color: #31708f; padding: 10px; border-radius: 5px; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 12px; text-align: left; }
        th { background-color: #f2f2f2; }
        .fix-button { background-color: #4CAF50; color: white; padding: 10px 15px; border: none; cursor: pointer; margin-top: 10px; }
    </style>
</head>
<body>
    <h1>Diagnóstico para Producto ID=15</h1>';

// Verificar si se han enviado acciones
if (isset($_POST['action']) && $_POST['action'] == 'fix') {
    // Crear archivo de configuración
    $configContent = "<?php\n";
    $configContent .= "// Configuración personalizada de tablas para ProductDetails.php\n";
    $configContent .= "// Generado automáticamente por fix_product_id15.php\n\n";
    $configContent .= "\$table_products = " . ($productTable ? "'{$productTable}'" : "''") . ";\n";
    $configContent .= "\$table_images = " . ($imageTable ? "'{$imageTable}'" : "''") . ";\n";
    $configContent .= "\$table_categories = " . ($categoryTable ? "'{$categoryTable}'" : "''") . ";\n";
    $configContent .= "\$table_product_categories = " . ($productCategoryTable ? "'{$productCategoryTable}'" : "''") . ";\n";
    
    // Guardar archivo de configuración
    file_put_contents('product_tables_config.php', $configContent);
    
    echo '<div class="success">Se ha creado el archivo de configuración personalizado con los nombres correctos de tablas.</div>';
    
    // Actualizar ProductDetails.php para usar una consulta más simple
    $productDetailsFile = 'ProductDetails.php';
    $productDetailsContent = file_get_contents($productDetailsFile);
    
    // Hacer la consulta más robusta
    $productDetailsContent = preg_replace(
        '/try \{\s*\/\/ Consulta simple y directa\s*\$stmt = \$db->query\("SELECT \* FROM \{\$table_products\} WHERE product_id = \?", \[\$productId\]\);/',
        'try {
    // Consulta muy simplificada para evitar errores
    $table_to_use = "";
    
    // Intentar determinar qué tabla existe
    try {
        $test = $db->query("SHOW TABLES LIKE \'products\'");
        if ($test->rowCount() > 0) {
            $table_to_use = "products";
        } else {
            $test = $db->query("SHOW TABLES LIKE \'product\'");
            if ($test->rowCount() > 0) {
                $table_to_use = "product";
            }
        }
    } catch (Exception $e) {
        // Ignorar error y usar el valor predeterminado
    }
    
    // Si no se encontró ninguna tabla, usar el valor configurado
    if (empty($table_to_use)) {
        $table_to_use = $table_products;
    }
    
    // Si aún no tenemos una tabla válida, intentar ambas
    if (empty($table_to_use)) {
        try {
            $stmt = $db->query("SELECT * FROM products WHERE product_id = ?", [$productId]);
            $product = $stmt->fetch();
            if ($product) {
                $table_products = "products";
                $table_to_use = "products";
            }
        } catch (Exception $e1) {
            try {
                $stmt = $db->query("SELECT * FROM product WHERE product_id = ?", [$productId]);
                $product = $stmt->fetch();
                if ($product) {
                    $table_products = "product";
                    $table_to_use = "product";
                }
            } catch (Exception $e2) {
                // Ambos intentos fallaron
            }
        }
    }
    
    // Si determinamos una tabla para usar, hacer la consulta
    if (!empty($table_to_use)) {
        $stmt = $db->query("SELECT * FROM {$table_to_use} WHERE product_id = ?", [$productId]);',
        $productDetailsContent
    );
    
    // Guardar cambios en ProductDetails.php
    file_put_contents($productDetailsFile, $productDetailsContent);
    
    echo '<div class="success">Se ha modificado ProductDetails.php para usar una consulta más robusta.</div>';
}

// Mostrar información de tablas
echo '<h2>Tablas detectadas en la base de datos:</h2>';
echo '<table>';
echo '<tr><th>Nombre de tabla</th><th>Existe</th></tr>';

foreach ($tables as $tableName => $exists) {
    echo '<tr>';
    echo '<td>' . htmlspecialchars($tableName) . '</td>';
    echo '<td>' . ($exists ? '✅ Sí' : '❌ No') . '</td>';
    echo '</tr>';
}

echo '</table>';

// Intentar encontrar el producto con ID=15
echo '<h2>Buscando producto con ID=15:</h2>';

$productFound = false;
$productData = null;

// Intentar en las posibles tablas de productos
if ($productTable) {
    try {
        $stmt = $db->query("SELECT * FROM {$productTable} WHERE product_id = ?", [$productId]);
        $productData = $stmt->fetch();
        if ($productData) {
            $productFound = true;
            echo '<div class="success">✅ Producto con ID=15 encontrado en la tabla ' . htmlspecialchars($productTable) . '</div>';
        } else {
            echo '<div class="error">❌ No existe un producto con ID=15 en la tabla ' . htmlspecialchars($productTable) . '</div>';
        }
    } catch (Exception $e) {
        echo '<div class="error">❌ Error al buscar el producto: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
} else {
    echo '<div class="error">❌ No se encontró ninguna tabla de productos válida</div>';
}

// Si no se encontró en la tabla principal, intentar en la alternativa
if (!$productFound) {
    $altProductTable = $tables['products'] ? 'product' : 'products';
    if ($tables[$altProductTable]) {
        try {
            $stmt = $db->query("SELECT * FROM {$altProductTable} WHERE product_id = ?", [$productId]);
            $productData = $stmt->fetch();
            if ($productData) {
                $productFound = true;
                echo '<div class="success">✅ Producto con ID=15 encontrado en la tabla alternativa ' . htmlspecialchars($altProductTable) . '</div>';
            } else {
                echo '<div class="warning">⚠️ No existe un producto con ID=15 en la tabla alternativa ' . htmlspecialchars($altProductTable) . '</div>';
            }
        } catch (Exception $e) {
            echo '<div class="warning">⚠️ Error al buscar el producto en tabla alternativa: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}

// Mostrar datos del producto si se encontró
if ($productFound && $productData) {
    echo '<h2>Datos del Producto ID=15:</h2>';
    echo '<table>';
    echo '<tr><th>Campo</th><th>Valor</th></tr>';
    
    foreach ($productData as $field => $value) {
        if (!is_numeric($field)) {  // Solo mostrar campos nombrados, no índices numéricos
            echo '<tr>';
            echo '<td>' . htmlspecialchars($field) . '</td>';
            echo '<td>' . htmlspecialchars($value) . '</td>';
            echo '</tr>';
        }
    }
    
    echo '</table>';
} else {
    echo '<div class="warning">⚠️ No se pudo encontrar un producto con ID=15 en ninguna tabla.</div>';
    
    // Mostrar los IDs disponibles
    if ($productTable) {
        try {
            $stmt = $db->query("SELECT product_id FROM {$productTable} ORDER BY product_id");
            $availableIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (!empty($availableIds)) {
                echo '<div class="info">IDs de productos disponibles: ' . implode(', ', $availableIds) . '</div>';
            } else {
                echo '<div class="warning">No hay productos en la tabla ' . htmlspecialchars($productTable) . '</div>';
            }
        } catch (Exception $e) {
            echo '<div class="error">Error al buscar IDs disponibles: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}

// Formulario para aplicar correcciones
echo '<form method="post">';
echo '<input type="hidden" name="action" value="fix">';
echo '<button type="submit" class="fix-button">Aplicar correcciones automáticas</button>';
echo '</form>';

echo '<p><a href="../index.php">← Volver a la página principal</a> | <a href="ProductDetails.php?id=' . $productId . '">Ver página de producto</a></p>';

echo '</body></html>';
