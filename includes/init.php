<?php
/**
 * Initialization file
 * Includes necessary files and starts session
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Primero incluimos el archivo de funciones para evitar redeclaraciones
require_once __DIR__ . '/functions.php';

// Luego incluimos el resto de dependencias
require_once __DIR__ . '/db_bridge.php';
require_once __DIR__ . '/user.php';

// No definimos ninguna función aquí - todas las funciones auxiliares están en functions.php
