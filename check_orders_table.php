<?php
/**
 * Script para verificar y corregir la tabla de órdenes
 */

// Incluir el archivo bootstrap
require_once 'includes/bootstrap.php';

// Función para mostrar mensajes
function show_message($message, $type = 'info') {
    echo "<div style='margin: 10px 0; padding: 15px; background-color: " . 
        ($type == 'success' ? '#d4edda' : ($type == 'error' ? '#f8d7da' : '#e2e3e5')) . 
        "; color: " . ($type == 'success' ? '#155724' : ($type == 'error' ? '#721c24' : '#383d41')) . 
        "; border-radius: 5px;'>" . $message . "</div>";
}

// Variables para seguimiento de estado
$table_exists = false;
$table_created = false;
$needed_columns = [
    'order_id' => 'INT AUTO_INCREMENT PRIMARY KEY',
    'user_id' => 'INT NULL',
    'order_number' => 'VARCHAR(50) NOT NULL',
    'total_amount' => 'DECIMAL(10,2) NOT NULL',
    'subtotal' => 'DECIMAL(10,2) NOT NULL',
    'shipping_cost' => 'DECIMAL(10,2) NOT NULL',
    'tax_amount' => 'DECIMAL(10,2) NOT NULL DEFAULT 0',
    'discount_amount' => 'DECIMAL(10,2) NOT NULL DEFAULT 0',
    'shipping_address' => 'TEXT',
    'payment_method' => 'VARCHAR(50) NOT NULL',
    'coupon_code' => 'VARCHAR(50) NULL',
    'status' => 'VARCHAR(50) NOT NULL DEFAULT "pending"',
    'payment_status' => 'VARCHAR(50) NOT NULL DEFAULT "pending"',
    'notes' => 'TEXT NULL',
    'created_at' => 'DATETIME NOT NULL',
    'updated_at' => 'DATETIME NULL'
];

echo "<h1>Verificación y corrección de la tabla 'orders'</h1>";

try {
    // Comprobar si la tabla existe
    $stmt = $conn->query("SHOW TABLES LIKE 'orders'");
    $table_exists = ($stmt->rowCount() > 0);
    
    if ($table_exists) {
        show_message("La tabla 'orders' existe en la base de datos.", 'success');
        
        // Verificar columnas existentes
        $stmt = $conn->query("DESCRIBE orders");
        $existing_columns = [];
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $existing_columns[$row['Field']] = true;
        }
        
        // Comparar con las columnas necesarias
        $missing_columns = [];
        foreach ($needed_columns as $column => $definition) {
            if (!isset($existing_columns[$column])) {
                $missing_columns[$column] = $definition;
            }
        }
        
        if (empty($missing_columns)) {
            show_message("La estructura de la tabla 'orders' está completa.", 'success');
        } else {
            show_message("Faltan algunas columnas en la tabla 'orders'. Intentando corregir...", 'error');
            
            // Agregar columnas faltantes
            foreach ($missing_columns as $column => $definition) {
                try {
                    $sql = "ALTER TABLE orders ADD COLUMN $column $definition";
                    $conn->exec($sql);
                    show_message("Columna '$column' agregada con éxito.", 'success');
                } catch (PDOException $e) {
                    show_message("Error al agregar columna '$column': " . $e->getMessage(), 'error');
                }
            }
        }
    } else {
        show_message("La tabla 'orders' no existe. Creando tabla...", 'error');
        
        // Crear la tabla desde cero
        $sql = "CREATE TABLE orders (
            order_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            order_number VARCHAR(50) NOT NULL,
            total_amount DECIMAL(10,2) NOT NULL,
            subtotal DECIMAL(10,2) NOT NULL,
            shipping_cost DECIMAL(10,2) NOT NULL,
            tax_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
            discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
            shipping_address TEXT NOT NULL,
            payment_method VARCHAR(50) NOT NULL,
            coupon_code VARCHAR(50) NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'pending',
            payment_status VARCHAR(50) NOT NULL DEFAULT 'pending',
            notes TEXT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NULL
        )";
        
        $conn->exec($sql);
        $table_created = true;
        show_message("Tabla 'orders' creada con éxito.", 'success');
    }
    
    // Verificar tabla de items de orden
    $stmt = $conn->query("SHOW TABLES LIKE 'order_items'");
    $order_items_exists = ($stmt->rowCount() > 0);
    
    if ($order_items_exists) {
        show_message("La tabla 'order_items' existe en la base de datos.", 'success');
    } else {
        show_message("La tabla 'order_items' no existe. Creando tabla...", 'error');
        
        // Crear tabla order_items
        $sql = "CREATE TABLE order_items (
            item_id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            product_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            quantity INT NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            discount DECIMAL(10,2) NOT NULL DEFAULT 0,
            total DECIMAL(10,2) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE
        )";
        
        $conn->exec($sql);
        show_message("Tabla 'order_items' creada con éxito.", 'success');
    }
    
    echo "<p><strong>Verificación completada. Ahora debería poder crear pedidos sin problemas.</strong></p>";
    echo "<p><a href='pages/checkout.php' style='display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px;'>Volver a la página de checkout</a></p>";
    
} catch (PDOException $e) {
    show_message("Error de base de datos: " . $e->getMessage(), 'error');
}
?>
