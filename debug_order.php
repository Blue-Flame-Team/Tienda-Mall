<?php
/**
 * Script de depuración de órdenes
 * Este script verifica la estructura de las tablas relacionadas con órdenes y prueba la inserción
 */

// Cargar bootstrap para tener acceso al entorno completo
require_once 'includes/bootstrap.php';

// Asegurarse que estamos en modo desarrollo
if (!defined('DEV_MODE') || !DEV_MODE) {
    die("Este script solo debe ejecutarse en modo desarrollo");
}

echo "<h1>Depuración de Sistema de Órdenes</h1>";

// 1. Verificar estructura de la tabla orders
echo "<h2>1. Verificando estructura de la tabla orders</h2>";
try {
    $stmt = $conn->query("DESCRIBE orders");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Llave</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($col['Extra'] ?? '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p style='color:green'>✓ Tabla 'orders' existe y se ha verificado su estructura.</p>";
} catch (PDOException $e) {
    echo "<p style='color:red'>✗ Error verificando tabla 'orders': " . htmlspecialchars($e->getMessage()) . "</p>";
}

// 2. Verificar estructura de la tabla order_items
echo "<h2>2. Verificando estructura de la tabla order_items</h2>";
try {
    $stmt = $conn->query("DESCRIBE order_items");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Llave</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($col['Extra'] ?? '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p style='color:green'>✓ Tabla 'order_items' existe y se ha verificado su estructura.</p>";
} catch (PDOException $e) {
    echo "<p style='color:red'>✗ Error verificando tabla 'order_items': " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Intentando crear la tabla order_items...</p>";
    
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
            FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE SET NULL
        )");
        
        echo "<p style='color:green'>✓ Tabla 'order_items' creada correctamente.</p>";
    } catch (PDOException $e2) {
        echo "<p style='color:red'>✗ Error creando tabla 'order_items': " . htmlspecialchars($e2->getMessage()) . "</p>";
    }
}

// 3. Probar inserción de orden
echo "<h2>3. Probando inserción de orden</h2>";

// Generar datos de prueba
$order_number = 'TEST-' . date('Ymd') . '-' . mt_rand(1000, 9999);
$total_amount = 150.75;
$shipping_address = json_encode([
    'full_name' => 'Usuario Prueba',
    'email' => 'test@example.com',
    'phone' => '12345678901',
    'address' => 'Dirección de prueba 123',
    'city' => 'Ciudad Prueba',
    'country' => 'Saudi Arabia'
], JSON_UNESCAPED_UNICODE);

$billing_address = $shipping_address;
$payment_method = 'cash_on_delivery';
$status = 'pending';
$payment_status = 'pending';

echo "<p>Preparando inserción de orden con datos de prueba:</p>";
echo "<pre>";
echo "order_number: $order_number\n";
echo "total_amount: $total_amount\n";
echo "payment_method: $payment_method\n";
echo "status: $status\n";
echo "payment_status: $payment_status\n";
echo "</pre>";

try {
    // Iniciar transacción
    $conn->beginTransaction();
    
    // Insertar orden de prueba
    $stmt = $conn->prepare("INSERT INTO orders (
        user_id, order_number, total_amount, shipping_address, billing_address,
        payment_method, status, payment_status
    ) VALUES (
        NULL, :order_number, :total_amount, :shipping_address, :billing_address,
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
    
    // Insertar un item de prueba
    $stmt = $conn->prepare("INSERT INTO order_items (
        order_id, product_id, name, quantity, price, total
    ) VALUES (
        :order_id, 1, 'Producto de prueba', 1, 100.50, 100.50
    )");
    
    $stmt->bindParam(':order_id', $order_id);
    $stmt->execute();
    
    // Confirmar transacción
    $conn->commit();
    
    echo "<p style='color:green'>✓ Orden de prueba creada correctamente. ID: " . htmlspecialchars($order_id) . "</p>";
    
    // Recuperar la orden para verificar
    $stmt = $conn->prepare("SELECT * FROM orders WHERE order_id = :order_id");
    $stmt->bindParam(':order_id', $order_id);
    $stmt->execute();
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h3>Datos de la orden creada:</h3>";
    echo "<pre>";
    print_r($order);
    echo "</pre>";
    
} catch (PDOException $e) {
    // Revertir transacción
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    echo "<p style='color:red'>✗ Error al insertar orden de prueba:</p>";
    echo "<pre style='color:red'>";
    echo "Mensaje: " . $e->getMessage() . "\n";
    echo "Código: " . $e->getCode() . "\n";
    echo "SQL State: " . $e->errorInfo[0] . "\n";
    echo "</pre>";
    
    // Verificar si el error está relacionado con restricciones o tipos de datos
    if ($e->getCode() == 23000) {
        echo "<p>El error parece estar relacionado con una restricción de integridad referencial o una clave única.</p>";
    } elseif ($e->getCode() == 42000) {
        echo "<p>El error parece ser de sintaxis SQL o permisos.</p>";
    } elseif ($e->getCode() == 22001) {
        echo "<p>El error parece estar relacionado con un valor demasiado largo para una columna.</p>";
    }
}

// 4. Mostrar recomendaciones
echo "<h2>4. Recomendaciones</h2>";
echo "<ul>";
echo "<li>Si todas las verificaciones son correctas pero sigue habiendo problemas al crear órdenes, verificar que los datos enviados desde el formulario de checkout sean consistentes.</li>";
echo "<li>Revisar los valores de estado y método de pago para asegurar que coinciden con los valores permitidos en la tabla.</li>";
echo "<li>Verificar que el formato JSON usado para las direcciones de envío y facturación sea válido.</li>";
echo "</ul>";

echo "<p><a href='index.php'>Volver al inicio</a></p>";
?>
