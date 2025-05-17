<?php
// Cargar configuración para acceder a la base de datos
require_once 'includes/config.php';

// Función para verificar si una tabla existe
function tableExists($conn, $tableName) {
    try {
        $result = $conn->query("SHOW TABLES LIKE '{$tableName}'");
        return $result->rowCount() > 0;
    } catch (PDOException $e) {
        echo "Error verificando tabla {$tableName}: " . $e->getMessage();
        return false;
    }
}

// Función para crear la tabla order_items si no existe
function createOrderItemsTable($conn) {
    try {
        $conn->exec("CREATE TABLE IF NOT EXISTS order_items (
            item_id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            product_id INT NOT NULL,
            name VARCHAR(255) DEFAULT NULL,
            quantity INT NOT NULL DEFAULT 1,
            price DECIMAL(10,2) NOT NULL,
            discount DECIMAL(10,2) DEFAULT 0,
            total DECIMAL(10,2) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE
        )");
        echo "<p style='color:green'>Tabla order_items creada o ya existente.</p>";
        return true;
    } catch (PDOException $e) {
        echo "<p style='color:red'>Error al crear order_items: " . $e->getMessage() . "</p>";
        return false;
    }
}

// Inicio de HTML
echo "<!DOCTYPE html>
<html>
<head>
    <title>Diagnóstico de Órdenes</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        th { background-color: #f2f2f2; }
        .error { color: red; }
        .success { color: green; }
        .container { max-width: 1200px; margin: 0 auto; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Diagnóstico de Sistema de Órdenes</h1>";

// 1. Verificar existencia de tablas
echo "<h2>1. Comprobando tablas</h2>";

$requiredTables = ['orders', 'order_items', 'products'];
$missingTables = [];

foreach ($requiredTables as $table) {
    if (tableExists($conn, $table)) {
        echo "<p class='success'>✅ Tabla '{$table}' existe.</p>";
    } else {
        echo "<p class='error'>❌ Tabla '{$table}' NO existe.</p>";
        $missingTables[] = $table;
    }
}

// 2. Crear tablas faltantes
if (!empty($missingTables)) {
    echo "<h2>2. Creando tablas faltantes</h2>";
    
    if (in_array('order_items', $missingTables)) {
        createOrderItemsTable($conn);
    }
}

// 3. Mostrar estructura de la tabla orders
echo "<h2>3. Estructura actual de la tabla orders</h2>";
try {
    $stmt = $conn->query("DESCRIBE orders");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table>
        <tr>
            <th>Campo</th>
            <th>Tipo</th>
            <th>Nulo</th>
            <th>Llave</th>
            <th>Default</th>
            <th>Extra</th>
        </tr>";
    
    foreach ($columns as $column) {
        echo "<tr>
            <td>{$column['Field']}</td>
            <td>{$column['Type']}</td>
            <td>{$column['Null']}</td>
            <td>{$column['Key']}</td>
            <td>{$column['Default']}</td>
            <td>{$column['Extra']}</td>
        </tr>";
    }
    
    echo "</table>";
} catch (PDOException $e) {
    echo "<p class='error'>Error al consultar estructura de orders: " . $e->getMessage() . "</p>";
}

// 4. Si order_items existe, mostrar su estructura
if (tableExists($conn, 'order_items')) {
    echo "<h2>4. Estructura actual de la tabla order_items</h2>";
    try {
        $stmt = $conn->query("DESCRIBE order_items");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table>
            <tr>
                <th>Campo</th>
                <th>Tipo</th>
                <th>Nulo</th>
                <th>Llave</th>
                <th>Default</th>
                <th>Extra</th>
            </tr>";
        
        foreach ($columns as $column) {
            echo "<tr>
                <td>{$column['Field']}</td>
                <td>{$column['Type']}</td>
                <td>{$column['Null']}</td>
                <td>{$column['Key']}</td>
                <td>{$column['Default']}</td>
                <td>{$column['Extra']}</td>
            </tr>";
        }
        
        echo "</table>";
    } catch (PDOException $e) {
        echo "<p class='error'>Error al consultar estructura de order_items: " . $e->getMessage() . "</p>";
    }
}

// 5. Prueba de inserción básica de orden
echo "<h2>5. Prueba de inserción</h2>";

echo "<p>Intentando crear una orden de prueba...</p>";

try {
    // Iniciar transacción
    $conn->beginTransaction();
    
    // Generar datos de prueba
    $order_number = 'TEST-' . date('Ymd') . '-' . mt_rand(1000, 9999);
    $total_amount = 199.99;
    $shipping_address = json_encode(['address' => 'Test Address']);
    $billing_address = $shipping_address;
    $payment_method = 'cash_on_delivery';
    $status = 'pending';
    $payment_status = 'pending';
    
    // Insertar orden
    $stmt = $conn->prepare("INSERT INTO orders (
        order_number, total_amount, shipping_address, billing_address,
        payment_method, status, payment_status
    ) VALUES (
        :order_number, :total_amount, :shipping_address, :billing_address,
        :payment_method, :status, :payment_status
    )");
    
    $stmt->bindParam(':order_number', $order_number);
    $stmt->bindParam(':total_amount', $total_amount);
    $stmt->bindParam(':shipping_address', $shipping_address);
    $stmt->bindParam(':billing_address', $billing_address);
    $stmt->bindParam(':payment_method', $payment_method);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':payment_status', $payment_status);
    
    $stmt->execute();
    
    $order_id = $conn->lastInsertId();
    
    // Si order_items existe, insertar un item de prueba
    if (tableExists($conn, 'order_items')) {
        $stmt = $conn->prepare("INSERT INTO order_items (
            order_id, product_id, name, quantity, price, total
        ) VALUES (
            :order_id, 1, 'Producto de Prueba', 1, 199.99, 199.99
        )");
        
        $stmt->bindParam(':order_id', $order_id);
        
        try {
            $stmt->execute();
            echo "<p class='success'>✅ Item de orden insertado correctamente.</p>";
        } catch (PDOException $e) {
            echo "<p class='error'>❌ Error al insertar item: " . $e->getMessage() . "</p>";
            // No revertir la transacción aquí para ver si la orden se crea correctamente al menos
        }
    }
    
    // Confirmar transacción
    $conn->commit();
    
    echo "<p class='success'>✅ Orden de prueba creada correctamente con ID: {$order_id}</p>";
    
} catch (PDOException $e) {
    // Revertir transacción
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    echo "<p class='error'>❌ Error al crear orden de prueba: " . $e->getMessage() . "</p>";
    
    // Mostrar detalles adicionales
    echo "<pre>Código de error: " . $e->getCode() . "\n";
    if (isset($e->errorInfo)) {
        echo "SQL State: " . $e->errorInfo[0] . "\n";
        echo "Error específico: " . $e->errorInfo[2] . "\n";
    }
    echo "</pre>";
}

// Finalizar HTML
echo "</div></body></html>";
?>
