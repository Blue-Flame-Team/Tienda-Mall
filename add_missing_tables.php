<?php
/**
 * Script para añadir tablas faltantes a la base de datos
 * Este script crea las tablas necesarias para la página de perfil del usuario
 */

// Cargar configuración de la base de datos
require_once 'includes/bootstrap.php';

try {
    // Verificar si las tablas ya existen
    $tables_to_check = ['payment_methods', 'shipping_addresses', 'wishlists'];
    $missing_tables = [];
    
    foreach ($tables_to_check as $table) {
        $stmt = $conn->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() === 0) {
            $missing_tables[] = $table;
        }
    }
    
    if (empty($missing_tables)) {
        echo "Todas las tablas necesarias ya existen en la base de datos.";
        exit;
    }
    
    echo "Creando tablas faltantes: " . implode(", ", $missing_tables) . "<br>";
    
    // Tabla payment_methods
    if (in_array('payment_methods', $missing_tables)) {
        $conn->exec("CREATE TABLE payment_methods (
            payment_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            card_type VARCHAR(50) NOT NULL,
            last_four VARCHAR(4) NOT NULL,
            card_holder_name VARCHAR(100) NOT NULL,
            expiry_month VARCHAR(2) NOT NULL,
            expiry_year VARCHAR(4) NOT NULL,
            is_default BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        )");
        echo "Tabla payment_methods creada correctamente.<br>";
    }
    
    // Tabla shipping_addresses (si no existe)
    if (in_array('shipping_addresses', $missing_tables)) {
        $conn->exec("CREATE TABLE shipping_addresses (
            address_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            address_name VARCHAR(100) NOT NULL,
            full_name VARCHAR(100) NOT NULL,
            address_line_1 VARCHAR(255) NOT NULL,
            address_line_2 VARCHAR(255),
            city VARCHAR(100) NOT NULL,
            state VARCHAR(100) NOT NULL,
            postal_code VARCHAR(20) NOT NULL,
            country VARCHAR(100) NOT NULL DEFAULT 'Saudi Arabia',
            phone VARCHAR(20) NOT NULL,
            is_default BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        )");
        echo "Tabla shipping_addresses creada correctamente.<br>";
    }
    
    // Tabla wishlists (si no existe)
    if (in_array('wishlists', $missing_tables)) {
        $conn->exec("CREATE TABLE wishlists (
            wishlist_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            product_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
            UNIQUE KEY unique_user_product (user_id, product_id)
        )");
        echo "Tabla wishlists creada correctamente.<br>";
    }
    
    echo "Todas las tablas faltantes han sido creadas con éxito.";
} catch (PDOException $e) {
    die("Error al crear las tablas: " . $e->getMessage());
}
?>
