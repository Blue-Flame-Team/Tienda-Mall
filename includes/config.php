<?php
/* 
 * Database Configuration File
 * This file will be used to connect to the database
 */

// Database credentials
// Si utilizas WAMP, el puerto estándar es 3306
// Si utilizas XAMPP, verifica el puerto en el panel de control de XAMPP
define('DB_HOST', '127.0.0.1:3306'); // Usando el puerto estándar de MySQL
define('DB_USER', 'root'); // Nombre de usuario por defecto
define('DB_PASS', ''); // Contraseña vacía por defecto

// Cambia esto al nombre de tu base de datos existente
// o crea una nueva base de datos con este nombre usando phpMyAdmin
define('DB_NAME', 'tienda_mall');

// Set database connection charset
define('DB_CHARSET', 'utf8mb4');

// Site URL - مستضاف على منفذ افتراضي بدون رقم منفذ
define('BASE_URL', 'http://localhost/Tienda');

// Define SITE_URL como alias de BASE_URL para mantener compatibilidad
define('SITE_URL', BASE_URL);

// Enable development mode to show detailed errors
define('DEV_MODE', true);

// Error reporting in development, disable in production
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Define timezone
date_default_timezone_set('UTC');

// Session configuration - only set if no session is active
if (session_status() == PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    session_start();
}

// Inicializar la conexión global a la base de datos
// Esto asegura que $conn esté disponible para todos los archivos que lo necesiten
global $conn;
if (!isset($conn)) {
    try {
        $conn = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    } catch (PDOException $e) {
        error_log('Connection Error: ' . $e->getMessage());
        
        if (defined('DEV_MODE') && DEV_MODE === true) {
            die("Database connection failed: " . $e->getMessage());
        } else {
            die("Database connection failed. Please try again later.");
        }
    }
}
?>
