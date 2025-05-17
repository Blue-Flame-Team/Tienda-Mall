<?php
/**
 * Herramienta para diagnosticar y solucionar conflictos de funciones
 * Esta herramienta analiza los archivos principales y protege todas las funciones
 * con verificaciones function_exists() para prevenir errores fatales
 */

// Encabezado HTML
echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Reparación de Conflictos de Funciones</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; margin: 0; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { color: #333; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        .card { background: #f9f9f9; border-left: 4px solid #4CAF50; padding: 15px; margin-bottom: 15px; }
        .error { border-left-color: #F44336; }
        .warning { border-left-color: #FF9800; }
        .success { border-left-color: #4CAF50; }
        code { background: #f5f5f5; padding: 2px 5px; border-radius: 3px; font-family: monospace; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
        .btn { display: inline-block; background: #4CAF50; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px; }
        .btn:hover { background: #45a049; }
        .file-name { font-weight: bold; margin-bottom: 5px; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Herramienta de Reparación de Conflictos de Funciones</h1>";

// Definir archivos a analizar
$files_to_check = [
    'includes/functions.php',
    'includes/product_helper.php',
    'includes/order_helper.php',
    'includes/cart_helper.php',
    'includes/checkout_helper.php',
    'includes/user.php'
];

// Función para extraer funciones declaradas en un archivo
function extractFunctions($filePath) {
    $content = file_get_contents($filePath);
    $functions = [];
    
    // Expresión regular para encontrar declaraciones de funciones
    // Excluye funciones ya protegidas con function_exists
    preg_match_all('/function\s+([a-zA-Z0-9_]+)\s*\(/m', $content, $matches);
    
    if (!empty($matches[1])) {
        foreach ($matches[1] as $functionName) {
            // Verificar si la función no está ya protegida
            $pattern = '/if\s*\(\s*!\s*function_exists\s*\(\s*[\'\"]' . preg_quote($functionName) . '[\'\"]\s*\)\s*\)\s*{/';
            if (!preg_match($pattern, $content)) {
                $functions[] = $functionName;
            }
        }
    }
    
    return $functions;
}

// Recolectar todas las funciones declaradas en los archivos
$all_functions = [];
foreach ($files_to_check as $file) {
    $full_path = __DIR__ . '/' . $file;
    if (file_exists($full_path)) {
        $functions = extractFunctions($full_path);
        $all_functions[$file] = $functions;
    }
}

// Encontrar duplicados
$duplicates = [];
$function_locations = [];

foreach ($all_functions as $file => $functions) {
    foreach ($functions as $function) {
        if (!isset($function_locations[$function])) {
            $function_locations[$function] = [$file];
        } else {
            $function_locations[$function][] = $file;
            $duplicates[$function] = $function_locations[$function];
        }
    }
}

// Mostrar resultados de duplicados
echo "<h2>Funciones Duplicadas Encontradas</h2>";

if (empty($duplicates)) {
    echo "<div class='card success'>No se encontraron funciones duplicadas.</div>";
} else {
    echo "<div class='card warning'><strong>ATENCIÓN:</strong> Se encontraron " . count($duplicates) . " funciones duplicadas:</div>";
    
    foreach ($duplicates as $function => $locations) {
        echo "<div class='card error'>";
        echo "<div class='file-name'>Función: <code>{$function}()</code></div>";
        echo "<p>Declarada en:</p><ul>";
        foreach ($locations as $location) {
            echo "<li>{$location}</li>";
        }
        echo "</ul></div>";
    }
}

// Modificar functions.php para proteger todas las funciones
$functionsFile = __DIR__ . '/includes/functions.php';
$content = file_get_contents($functionsFile);
$modified = false;

echo "<h2>Reparación Automática</h2>";

if (!file_exists($functionsFile)) {
    echo "<div class='card error'>El archivo functions.php no existe en la ruta especificada.</div>";
} else {
    // Copia de seguridad del archivo
    $backupFile = $functionsFile . '.bak.' . date('Y-m-d_H-i-s');
    file_put_contents($backupFile, $content);
    
    // Buscar declaraciones de funciones sin protección
    preg_match_all('/function\s+([a-zA-Z0-9_]+)\s*\((.*?)\)\s*{/s', $content, $matches, PREG_OFFSET_CAPTURE);
    
    // Reemplazar desde el final para no afectar las posiciones
    if (!empty($matches[0])) {
        for ($i = count($matches[0]) - 1; $i >= 0; $i--) {
            $fullDeclaration = $matches[0][$i][0];
            $functionName = $matches[1][$i][0];
            $position = $matches[0][$i][1];
            
            // Verificar si ya está protegida
            $previousContent = substr($content, max(0, $position - 100), 100);
            if (!preg_match('/if\s*\(\s*!\s*function_exists\s*\(\s*[\'\"]' . preg_quote($functionName) . '[\'\"]\s*\)\s*\)\s*{/i', $previousContent)) {
                // Reemplazar la declaración con una versión protegida
                $replacement = "if (!function_exists('$functionName')) {\n    $fullDeclaration";
                $closingBrace = "\n}";
                
                // Encontrar la llave de cierre correspondiente
                $pos = $position;
                $level = 0;
                $found = false;
                $length = strlen($content);
                
                for ($j = $pos + strlen($fullDeclaration); $j < $length; $j++) {
                    if ($content[$j] === '{') {
                        $level++;
                    } elseif ($content[$j] === '}') {
                        if ($level === 0) {
                            // Insertar el cierre de la condición function_exists
                            $content = substr_replace($content, $closingBrace, $j + 1, 0);
                            $found = true;
                            break;
                        }
                        $level--;
                    }
                }
                
                if ($found) {
                    $content = substr_replace($content, $replacement, $position, strlen($fullDeclaration));
                    $modified = true;
                }
            }
        }
    }
    
    if ($modified) {
        file_put_contents($functionsFile, $content);
        echo "<div class='card success'>
            <p>¡Archivo functions.php actualizado correctamente!</p>
            <p>Todas las funciones ahora están protegidas con <code>if (!function_exists())</code> para evitar redeclaraciones.</p>
            <p>Se creó una copia de seguridad en: <code>$backupFile</code></p>
        </div>";
    } else {
        echo "<div class='card success'>
            <p>El archivo functions.php ya está correctamente protegido. No se realizaron cambios.</p>
        </div>";
    }
}

// Arreglar el orden de inclusión en bootstrap.php
$bootstrapFile = __DIR__ . '/includes/bootstrap.php';
if (file_exists($bootstrapFile)) {
    $bootstrapContent = file_get_contents($bootstrapFile);
    
    // Verificar si functions.php se carga después de los helpers
    if (strpos($bootstrapContent, "require_once __DIR__ . '/functions.php';") < strpos($bootstrapContent, "require_once __DIR__ . '/product_helper.php';")) {
        echo "<div class='card warning'>
            <p>El archivo bootstrap.php podría estar cargando functions.php antes de los helpers específicos, lo que puede causar conflictos.</p>
            <p>Recomendación: Modifica bootstrap.php para cargar functions.php DESPUÉS de todos los archivos helper.</p>
        </div>";
    } else {
        echo "<div class='card success'>
            <p>El orden de inclusión en bootstrap.php parece correcto.</p>
        </div>";
    }
}

echo "<h2>Próximos Pasos</h2>
<div class='card'>
    <p>1. Verifica que el sitio funcione correctamente después de estos cambios</p>
    <p>2. Si continúan los errores de redeclaración, asegúrate de que:</p>
    <ul>
        <li>Los archivos helper específicos (product_helper.php, etc.) sólo declaren funciones relacionadas con su dominio</li>
        <li>No incluyas los mismos archivos múltiples veces en diferentes partes de tu código</li>
        <li>Consideres refactorizar tu código para usar clases en lugar de funciones globales</li>
    </ul>
</div>

<div style='margin-top: 20px'>
    <a href='admin/index.php' class='btn'>Ir al Panel de Administración</a>
</div>

</div>
</body>
</html>";
?>
