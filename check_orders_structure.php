<?php
// Incluir el archivo de configuración
require_once 'includes/config.php';

// Establecer la visualización de errores para depuración
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Verificación de Estructura de Tabla - orders</h1>";

try {
    // Conectar a la base de datos
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "<p>Conexión a la base de datos exitosa.</p>";
    
    // Verificar si la tabla orders existe
    $stmt = $conn->query("SHOW TABLES LIKE 'orders'");
    if ($stmt->rowCount() == 0) {
        echo "<p style='color:red'>La tabla 'orders' no existe.</p>";
        exit;
    }
    
    echo "<p style='color:green'>La tabla 'orders' existe.</p>";
    
    // Obtener la estructura de la tabla
    $stmt = $conn->query("DESCRIBE orders");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Estructura de la tabla 'orders':</h2>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Llave</th><th>Default</th><th>Extra</th></tr>";
    
    $statusColumnFound = false;
    $orderStatusColumnFound = false;
    
    foreach ($columns as $column) {
        echo "<tr>";
        foreach ($column as $key => $value) {
            // Verificar si el valor es NULL para evitar advertencias
            $safeValue = ($value === null) ? '&lt;NULL&gt;' : htmlspecialchars($value);
            echo "<td>" . $safeValue . "</td>";
        }
        echo "</tr>";
        
        // Verificar columnas específicas
        if ($column['Field'] == 'status') {
            $statusColumnFound = true;
        }
        if ($column['Field'] == 'order_status') {
            $orderStatusColumnFound = true;
        }
    }
    
    echo "</table>";
    
    if (!$statusColumnFound) {
        echo "<p style='color:red'><strong>La columna 'status' NO existe en la tabla.</strong></p>";
        
        if ($orderStatusColumnFound) {
            echo "<p>Se encontró una columna alternativa 'order_status' que podría tener la misma función.</p>";
        } else {
            echo "<p>Buscando columnas que podrían tener una función similar:</p>";
            
            // Buscar columnas con nombres similares
            $similarColumns = [];
            foreach ($columns as $column) {
                if (strpos(strtolower($column['Field']), 'status') !== false || 
                    strpos(strtolower($column['Field']), 'state') !== false || 
                    strpos(strtolower($column['Field']), 'condition') !== false) {
                    $similarColumns[] = $column['Field'];
                }
            }
            
            if (!empty($similarColumns)) {
                echo "<p>Columnas similares encontradas: " . implode(', ', $similarColumns) . "</p>";
            } else {
                echo "<p>No se encontraron columnas con nombres similares.</p>";
            }
        }
        
        // Verificar si debemos añadir la columna status
        echo "<h2>Corrección Automática</h2>";
        echo "<form method='post' action='check_orders_structure.php'>";
        echo "<input type='hidden' name='fix_structure' value='1'>";
        echo "<button type='submit'>Añadir columna 'status' a la tabla orders</button>";
        echo "</form>";
    } else {
        echo "<p style='color:green'><strong>La columna 'status' existe correctamente.</strong></p>";
    }
    
    if (isset($_POST['fix_structure']) && $_POST['fix_structure'] == 1) {
        if (!$statusColumnFound) {
            // Crear la columna status
            $conn->exec("ALTER TABLE orders ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'pending' AFTER coupon_code");
            echo "<p style='color:green'>Se ha añadido la columna 'status' a la tabla orders.</p>";
            
            // Actualizar index.php para usar la columna correcta
            $indexPhpPath = __DIR__ . '/admin/index.php';
            if (file_exists($indexPhpPath)) {
                $indexPhpContent = file_get_contents($indexPhpPath);
                $updatedContent = str_replace(
                    "SELECT SUM(total_amount) as total FROM orders WHERE status = 'completed' OR status = 'Completed'",
                    "SELECT SUM(total_amount) as total FROM orders",
                    $indexPhpContent
                );
                file_put_contents($indexPhpPath, $updatedContent);
                echo "<p style='color:green'>Se ha actualizado la consulta en admin/index.php para evitar el error.</p>";
            }
        }
    }
    
    // Mostrar algunos datos de ejemplo
    echo "<h2>Datos de ejemplo (primeros 5 registros):</h2>";
    $stmt = $conn->query("SELECT * FROM orders LIMIT 5");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($orders) > 0) {
        echo "<table border='1' cellpadding='5'>";
        
        // Encabezados de tabla
        echo "<tr>";
        foreach (array_keys($orders[0]) as $header) {
            echo "<th>" . htmlspecialchars($header) . "</th>";
        }
        echo "</tr>";
        
        // Datos
        foreach ($orders as $order) {
            echo "<tr>";
            foreach ($order as $value) {
                echo "<td>" . htmlspecialchars($value) . "</td>";
            }
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>No hay datos en la tabla 'orders'.</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

echo "<p><a href='admin/index.php'>Volver al panel de administración</a></p>";
?>
