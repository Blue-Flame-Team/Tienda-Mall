<?php
/**
 * Script para diagnosticar y corregir problemas de enlaces entre productos
 * y la página de detalles de producto.
 */
require_once '../includes/bootstrap.php';

// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Comprobar estructura de tablas
$db = Database::getInstance();
$tables = [];
$errors = [];
$fixes = 0;

// Función para verificar si existe una tabla
function tableExists($db, $tableName) {
    try {
        $result = $db->query("SHOW TABLES LIKE ?", [$tableName]);
        return $result->rowCount() > 0;
    } catch (Exception $e) {
        return false;
    }
}

// Verificar tablas principales relacionadas con productos
$tableNames = [
    'products', 'product', 'product_images', 'product_image', 
    'categories', 'category', 'product_categories', 'product_category'
];

echo "<h1>Diagnóstico de Tablas de Productos</h1>";
echo "<h2>Verificando tablas existentes...</h2>";
echo "<ul>";

foreach ($tableNames as $table) {
    $exists = tableExists($db, $table);
    $tables[$table] = $exists;
    echo "<li><strong>{$table}</strong>: " . ($exists ? "✅ Existe" : "❌ No existe") . "</li>";
}
echo "</ul>";

// Verificar enlaces en la página principal
echo "<h2>Verificando enlaces de productos en index.php...</h2>";

// Comprobar si los enlaces están correctamente formados
$linkPattern = 'pages/ProductDetails.php?id=';
$indexContent = file_get_contents('../index.php');

if (strpos($indexContent, $linkPattern) !== false) {
    echo "<p>✅ Los enlaces en index.php están correctamente configurados para dirigir a ProductDetails.php</p>";
} else {
    echo "<p>❌ No se encontraron enlaces correctos a ProductDetails.php en index.php</p>";
    $errors[] = "Enlaces incorrectos en index.php";
}

// Verificar consistencia en functions.php
echo "<h2>Verificando funciones de productos...</h2>";
$fixed = false;

// Si existe product pero no products, o viceversa, necesitamos arreglar functions.php
if (($tables['product'] && !$tables['products']) || (!$tables['product'] && $tables['products'])) {
    echo "<p>⚠️ Inconsistencia detectada: Las tablas 'product' y 'products' no están sincronizadas</p>";
    
    // Arreglar getFeaturedProducts
    $functionsFile = '../includes/functions.php';
    $functionsContent = file_get_contents($functionsFile);
    
    // Detectar qué tabla existe realmente
    $correctProductTable = $tables['products'] ? 'products' : 'product';
    $correctImageTable = $tables['product_images'] ? 'product_images' : 'product_image';
    
    // Reemplazar referencias a las tablas incorrectas
    $originalContent = $functionsContent;
    
    // Arreglar getFeaturedProducts
    $functionsContent = preg_replace(
        '/FROM product p/',
        'FROM ' . $correctProductTable . ' p',
        $functionsContent
    );
    
    $functionsContent = preg_replace(
        '/JOIN product_image pi/',
        'JOIN ' . $correctImageTable . ' pi',
        $functionsContent
    );
    
    // Arreglar getProductById
    $functionsContent = preg_replace(
        '/FROM products p/',
        'FROM ' . $correctProductTable . ' p',
        $functionsContent
    );
    
    // Guardar cambios si se realizaron
    if ($functionsContent !== $originalContent) {
        file_put_contents($functionsFile, $functionsContent);
        echo "<p>✅ Se ha corregido la función getFeaturedProducts y getProductById para usar las tablas correctas</p>";
        $fixed = true;
        $fixes++;
    }
}

// Verificar includes en ProductDetails.php
echo "<h2>Verificando includes en ProductDetails.php...</h2>";
$productDetailsFile = 'ProductDetails.php';
$productDetailsContent = file_get_contents($productDetailsFile);

// Comprobar si incluye bootstrap.php
if (strpos($productDetailsContent, "require_once '../includes/bootstrap.php'") === false) {
    echo "<p>⚠️ ProductDetails.php no incluye bootstrap.php que contiene funciones esenciales</p>";
    
    // Agregar include de bootstrap.php
    $originalContent = $productDetailsContent;
    $productDetailsContent = str_replace(
        "require_once '../includes/config.php';",
        "require_once '../includes/bootstrap.php';",
        $productDetailsContent
    );
    
    // Guardar cambios si se realizaron
    if ($productDetailsContent !== $originalContent) {
        file_put_contents($productDetailsFile, $productDetailsContent);
        echo "<p>✅ Se ha agregado la inclusión de bootstrap.php en ProductDetails.php</p>";
        $fixes++;
    }
} else {
    echo "<p>✅ ProductDetails.php ya incluye bootstrap.php</p>";
}

// Resumen de los problemas encontrados y soluciones
echo "<h2>Resumen del diagnóstico:</h2>";
if (count($errors) > 0) {
    echo "<h3>Problemas encontrados:</h3><ul>";
    foreach ($errors as $error) {
        echo "<li>{$error}</li>";
    }
    echo "</ul>";
} else {
    echo "<p>✅ No se encontraron problemas graves</p>";
}

if ($fixes > 0) {
    echo "<p>✅ Se realizaron {$fixes} correcciones automáticas</p>";
} else {
    echo "<p>ℹ️ No fue necesario realizar correcciones automáticas</p>";
}

// Botones de acción
echo "<h2>Acciones disponibles:</h2>";
echo "<p><a href='../index.php' style='padding: 10px; background-color: #007bff; color: white; text-decoration: none; margin-right: 10px;'>Volver a la página principal</a>";
echo "<a href='ProductDetails.php?id=1' style='padding: 10px; background-color: #28a745; color: white; text-decoration: none;'>Probar página de detalles</a></p>";
