<?php
/**
 * Database Check Script
 * Verifica y repara problemas comunes de conexión a la base de datos
 */

// Cargar la configuración
require_once 'includes/config.php';

// Configurar reporte de errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Diagnóstico de Base de Datos - Tienda Mall</h1>";

try {
    // Intentar conectar al servidor MySQL sin especificar base de datos
    $pdo = new PDO(
        "mysql:host=" . DB_HOST,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    
    echo "<p style='color:green'>✅ Conexión al servidor MySQL exitosa.</p>";
    
    // Verificar si la base de datos existe
    $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '" . DB_NAME . "'");
    $dbExists = $stmt->fetch();
    
    if (!$dbExists) {
        echo "<p style='color:orange'>⚠️ La base de datos '" . DB_NAME . "' no existe. Intentando crearla...</p>";
        
        // Crear la base de datos
        $pdo->exec("CREATE DATABASE " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "<p style='color:green'>✅ Base de datos '" . DB_NAME . "' creada exitosamente.</p>";
    } else {
        echo "<p style='color:green'>✅ Base de datos '" . DB_NAME . "' encontrada.</p>";
    }
    
    // Conectar a la base de datos específica
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    
    // Verificar tablas esenciales
    $tablasEsenciales = ['admins', 'admin_users', 'users', 'products', 'categories', 'orders', 'order_items'];
    $tablasExistentes = [];
    $tablasFaltantes = [];
    
    // Obtener todas las tablas de la base de datos
    $stmt = $pdo->query("SHOW TABLES");
    while ($tabla = $stmt->fetch(PDO::FETCH_NUM)) {
        $tablasExistentes[] = $tabla[0];
    }
    
    echo "<h2>Verificación de Tablas</h2>";
    
    // Verificar qué tablas faltan
    foreach ($tablasEsenciales as $tabla) {
        if (!in_array($tabla, $tablasExistentes)) {
            $tablasFaltantes[] = $tabla;
            echo "<p style='color:orange'>⚠️ La tabla '$tabla' no existe.</p>";
        } else {
            echo "<p style='color:green'>✅ Tabla '$tabla' encontrada.</p>";
        }
    }
    
    // Crear tablas faltantes
    if (in_array('admins', $tablasFaltantes)) {
        $pdo->exec("CREATE TABLE admins (
            admin_id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            is_super_admin TINYINT(1) DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            last_login DATETIME DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // Insertar usuario administrador predeterminado
        $admin_name = 'مدير النظام';
        $admin_email = 'admin@tienda.com';
        $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO admins (name, email, password, is_super_admin, is_active, created_at) 
                            VALUES (:name, :email, :password, 1, 1, NOW())");
        $stmt->bindParam(':name', $admin_name);
        $stmt->bindParam(':email', $admin_email);
        $stmt->bindParam(':password', $admin_password);
        $stmt->execute();
        
        echo "<p style='color:green'>✅ Tabla 'admins' creada con usuario administrador predeterminado.</p>";
    }
    
    if (in_array('users', $tablasFaltantes)) {
        $pdo->exec("CREATE TABLE users (
            user_id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            phone VARCHAR(20) DEFAULT NULL,
            address TEXT DEFAULT NULL,
            city VARCHAR(50) DEFAULT NULL,
            state VARCHAR(50) DEFAULT NULL,
            postal_code VARCHAR(20) DEFAULT NULL,
            country VARCHAR(50) DEFAULT NULL,
            is_active TINYINT(1) DEFAULT 1,
            last_login DATETIME DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        echo "<p style='color:green'>✅ Tabla 'users' creada correctamente.</p>";
    }
    
    // Verificar la tabla de órdenes
    if (in_array('orders', $tablasFaltantes)) {
        $pdo->exec("CREATE TABLE orders (
            order_id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT(11) UNSIGNED NOT NULL,
            order_number VARCHAR(50) NOT NULL UNIQUE,
            total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            shipping_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            tax_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            shipping_address TEXT NOT NULL,
            payment_method VARCHAR(50) NOT NULL,
            coupon_code VARCHAR(50) DEFAULT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            payment_status VARCHAR(20) NOT NULL DEFAULT 'pending',
            notes TEXT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY orders_user_id_foreign (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        echo "<p style='color:green'>✅ Tabla 'orders' creada correctamente.</p>";
    }
    
    if (in_array('order_items', $tablasFaltantes)) {
        $pdo->exec("CREATE TABLE order_items (
            item_id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            order_id INT(11) UNSIGNED NOT NULL,
            product_id INT(11) UNSIGNED NOT NULL,
            product_name VARCHAR(255) NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            quantity INT(11) NOT NULL DEFAULT 1,
            subtotal DECIMAL(10,2) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY order_items_order_id_foreign (order_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        echo "<p style='color:green'>✅ Tabla 'order_items' creada correctamente.</p>";
    }
    
    echo "<h2>Diagnóstico Completado</h2>";
    echo "<p>La base de datos parece estar en buen estado o ha sido reparada. Si sigues experimentando problemas, verifica los detalles en el archivo de error.log de PHP.</p>";
    echo "<p><a href='admin/admin_users.php'>Volver a la página de administración</a></p>";
    
} catch (PDOException $e) {
    echo "<h2 style='color:red'>Error de Base de Datos</h2>";
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
    
    // Proporcionar información específica sobre problemas comunes
    if (strpos($e->getMessage(), "Unknown database") !== false) {
        echo "<p>La base de datos '" . DB_NAME . "' no existe. Deberías ejecutar este script con privilegios que permitan crearla.</p>";
    } else if (strpos($e->getMessage(), "Access denied") !== false) {
        echo "<p>Error de acceso. Verifica las credenciales de la base de datos en el archivo config.php:</p>";
        echo "<ul>";
        echo "<li>Host: " . DB_HOST . "</li>";
        echo "<li>Usuario: " . DB_USER . "</li>";
        echo "<li>Contraseña: *****</li>";
        echo "</ul>";
        echo "<p>Asegúrate de que el usuario tenga los permisos necesarios.</p>";
    } else if (strpos($e->getMessage(), "Connection refused") !== false) {
        echo "<p>No se puede conectar al servidor MySQL. Verifica que:</p>";
        echo "<ul>";
        echo "<li>El servidor MySQL esté funcionando</li>";
        echo "<li>La configuración del host y puerto sea correcta</li>";
        echo "<li>No haya firewalls bloqueando la conexión</li>";
        echo "</ul>";
    }
}
?>
