<?php
// Script para corregir la estructura de la tabla orders

// Database configuration
$host = 'localhost';
$dbname = 'tienda_mall';
$username = 'root';
$password = '';

try {
    // Connect to database
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h1>Corrección de la tabla de órdenes</h1>";
    
    // Check if columns exist before adding them
    $columnsToAdd = [];
    
    // Check each column
    $columns = $conn->query("SHOW COLUMNS FROM orders")->fetchAll(PDO::FETCH_COLUMN);
    
    // Store existing columns
    echo "<p>Columnas existentes: " . implode(", ", $columns) . "</p>";
    
    // Check if subtotal_price exists
    if (!in_array('subtotal_price', $columns)) {
        $columnsToAdd[] = "ADD COLUMN subtotal_price DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER total_amount";
    }
    
    // Check if shipping_price exists
    if (!in_array('shipping_price', $columns)) {
        $columnsToAdd[] = "ADD COLUMN shipping_price DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER subtotal_price";
    }
    
    // Check if tax_price exists
    if (!in_array('tax_price', $columns)) {
        $columnsToAdd[] = "ADD COLUMN tax_price DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER shipping_price";
    }
    
    // Check if currency exists
    if (!in_array('currency', $columns)) {
        $columnsToAdd[] = "ADD COLUMN currency VARCHAR(10) NOT NULL DEFAULT 'USD' AFTER tax_price";
    }
    
    // If there are columns to add, execute ALTER TABLE
    if (!empty($columnsToAdd)) {
        $sql = "ALTER TABLE orders " . implode(", ", $columnsToAdd);
        $conn->exec($sql);
        echo "<p>✅ Se han agregado las siguientes columnas: " . implode(", ", $columnsToAdd) . "</p>";
        
        // Update existing records
        $conn->exec("UPDATE orders SET subtotal_price = total_amount, currency = 'USD' WHERE subtotal_price = 0");
        echo "<p>✅ Se han actualizado los registros existentes</p>";
    } else {
        echo "<p>✅ Todas las columnas necesarias ya existen</p>";
    }
    
    // Set total_amount from subtotal_price + shipping_price + tax_price for backwards compatibility 
    $conn->exec("UPDATE orders SET total_amount = subtotal_price + shipping_price + tax_price WHERE total_amount = 0");
    
    echo "<p>✅ Estructura de tabla corregida con éxito</p>";
    echo "<p><a href='orders.php'>Volver a órdenes</a></p>";
    
} catch (PDOException $e) {
    echo "<p>❌ Error de base de datos: " . $e->getMessage() . "</p>";
}
?>
