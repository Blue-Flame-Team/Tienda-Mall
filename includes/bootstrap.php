<?php
/**
 * Bootstrap - Archivo principal de inicialización
 * Este archivo carga todas las dependencias y configuraciones necesarias
 */

// Definir constante de ruta base si no está definida
if (!defined('BASE_PATH')) {
    define('BASE_PATH', realpath(dirname(__FILE__) . '/../'));
}

// Cargar configuración principal
require_once __DIR__ . '/config.php';

// Cargar clases y funciones del sistema - IMPORTANTE: El orden importa para evitar conflictos
require_once __DIR__ . '/database.php';

// Los archivos helper contienen funciones específicas y tienen prioridad sobre functions.php
require_once __DIR__ . '/database_helper.php';
require_once __DIR__ . '/product_helper.php';
require_once __DIR__ . '/order_helper.php';
require_once __DIR__ . '/image_helper.php';
require_once __DIR__ . '/cart_helper.php';
require_once __DIR__ . '/checkout_helper.php';
require_once __DIR__ . '/currency_helper.php';
require_once __DIR__ . '/user.php';

// Este archivo debe cargarse DESPUÉS de todos los helper para evitar redefiniciones
// Ya que functions.php tiene funciones genéricas que pueden estar en los helper
require_once __DIR__ . '/functions.php';

// Registrar manejador de errores
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        // Este código de error no está incluido en error_reporting
        return false;
    }

    // Log del error
    $error_message = "Error [$errno] $errstr en $errfile línea $errline";
    error_log($error_message);
    
    // Si estamos en modo desarrollo, mostrar errores
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        echo "<div style='color:red; background:#ffeeee; padding:10px; margin:10px; border:1px solid #ff0000;'>";
        echo "<h3>Error:</h3><p>$error_message</p>";
        echo "</div>";
    }
    
    // No ejecutar el gestor de errores interno de PHP
    return true;
}, E_ALL);

// Funciones globales útiles

/**
 * Redirigir a una URL
 * 
 * @param string $url URL a la que redirigir
 * @param bool $permanent Si es redirección permanente (301) o temporal (302)
 */
function redirect($url, $permanent = false) {
    header('Location: ' . $url, true, $permanent ? 301 : 302);
    exit;
}

/**
 * Validar si el usuario está autenticado, redirigir si no lo está
 * 
 * @param string $redirect_url URL a la que redirigir si no está autenticado
 * @return bool True si está autenticado, false si no
 */
function require_login($redirect_url = '/login.php') {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['login_redirect'] = $_SERVER['REQUEST_URI'];
        redirect(SITE_URL . $redirect_url);
        return false;
    }
    return true;
}

/**
 * Validar si el usuario es administrador, redirigir si no lo es
 * 
 * @param string $redirect_url URL a la que redirigir si no es administrador
 * @return bool True si es administrador, false si no
 */
function require_admin($redirect_url = '/admin/login.php') {
    if (!isset($_SESSION['admin'])) {
        redirect(SITE_URL . $redirect_url);
        return false;
    }
    return true;
}

/**
 * Obtener mensajes flash de la sesión
 * 
 * @param string $type Tipo de mensaje (success, error, warning, info)
 * @param bool $clear Si se deben borrar los mensajes después de obtenerlos
 * @return array Array con los mensajes
 */
function get_flash_messages($type = null, $clear = true) {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [
            'success' => [],
            'error' => [],
            'warning' => [],
            'info' => []
        ];
    }
    
    if ($type) {
        $messages = $_SESSION['flash_messages'][$type] ?? [];
        if ($clear) {
            $_SESSION['flash_messages'][$type] = [];
        }
        return $messages;
    }
    
    $all_messages = $_SESSION['flash_messages'];
    
    if ($clear) {
        $_SESSION['flash_messages'] = [
            'success' => [],
            'error' => [],
            'warning' => [],
            'info' => []
        ];
    }
    
    return $all_messages;
}

/**
 * Añadir un mensaje flash a la sesión
 * 
 * @param string $message Mensaje a añadir
 * @param string $type Tipo de mensaje (success, error, warning, info)
 */
function add_flash_message($message, $type = 'info') {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [
            'success' => [],
            'error' => [],
            'warning' => [],
            'info' => []
        ];
    }
    
    $_SESSION['flash_messages'][$type][] = $message;
}

/**
 * Sanitizar salida para evitar XSS
 * 
 * @param string $text Texto a sanitizar
 * @return string Texto sanitizado
 */
function escape($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Cargar la plantilla de cabecera del sitio
 * 
 * @param string $title Título de la página
 * @param array $options Opciones adicionales para la cabecera
 */
function load_header($title = 'Tienda Mall', $options = []) {
    $default_options = [
        'css' => [],
        'js' => [],
        'active_page' => '',
        'meta_description' => 'Tienda Mall - Tu centro comercial en línea',
        'canonical' => SITE_URL . $_SERVER['REQUEST_URI']
    ];
    
    $options = array_merge($default_options, $options);
    
    include BASE_PATH . '/templates/header.php';
}

/**
 * Cargar la plantilla de pie de página del sitio
 * 
 * @param array $options Opciones adicionales para el pie de página
 */
function load_footer($options = []) {
    $default_options = [
        'js' => []
    ];
    
    $options = array_merge($default_options, $options);
    
    include BASE_PATH . '/templates/footer.php';
}

// Inicialización de componentes adicionales si es necesario
// Por ejemplo, inicializar carrito de compras
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [
        'items' => [],
        'total' => 0,
        'count' => 0
    ];
}

// Verificar y crear tablas necesarias si no existen
function ensure_database_tables() {
    global $conn;
    
    try {
        // Verificar si existen las tablas principales
        $required_tables = [
            'users', 'products', 'categories', 'brands', 
            'orders', 'order_items', 'product_images', 'contact_messages'
        ];
        
        $missing_tables = [];
        
        foreach ($required_tables as $table) {
            $result = $conn->query("SHOW TABLES LIKE '$table'");
            if ($result->rowCount() == 0) {
                $missing_tables[] = $table;
            }
        }
        
        if (!empty($missing_tables)) {
            error_log("Tablas faltantes en la base de datos: " . implode(', ', $missing_tables));
            
            // Esto sería una versión básica, para un setup completo se debería usar un script de migración
            if (in_array('contact_messages', $missing_tables)) {
                // Crear tabla contact_messages si no existe
                $sql = "CREATE TABLE contact_messages (
                    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(100) NOT NULL,
                    email VARCHAR(100) NOT NULL,
                    phone VARCHAR(20),
                    message TEXT NOT NULL,
                    status ENUM('new', 'read', 'responded') DEFAULT 'new',
                    admin_response TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
                
                $conn->exec($sql);
                error_log("Tabla contact_messages creada");
            }
        }
    } catch (PDOException $e) {
        error_log("Error verificando tablas de base de datos: " . $e->getMessage());
    }
}

// Verificar tablas al inicio solo en entorno de desarrollo
if (defined('DEBUG_MODE') && DEBUG_MODE) {
    ensure_database_tables();
}
