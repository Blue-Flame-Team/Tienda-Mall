<?php
/**
 * Header Helper Functions
 * Este archivo contiene funciones comunes que son necesarias para todas las páginas
 * que incluyen el header.php
 */

// Asegurarse de que se incluyan los archivos necesarios
require_once __DIR__ . '/cart_helper.php';

/**
 * Prepara todas las variables necesarias para el header
 * 
 * @return array Arreglo con todas las variables necesarias para el header
 */
function prepare_header_variables() {
    $variables = [];
    
    // Verificar si el usuario está logueado
    $variables['isLoggedIn'] = isLoggedIn();
    
    // Contador del carrito
    $variables['cartCount'] = get_cart_count();
    
    return $variables;
}

/**
 * Incluye el header y configura todas las variables necesarias
 * 
 * @param string $pageTitle Título de la página
 * @param array $extraCSS Archivos CSS adicionales
 * @param array $extraJS Archivos JS adicionales
 */
function include_header($pageTitle = 'Tienda Mall', $extraCSS = [], $extraJS = []) {
    // Preparar variables para el header
    $headerVars = prepare_header_variables();
    
    // Extraer variables para que estén disponibles directamente
    extract($headerVars);
    
    // Incluir el header
    include_once __DIR__ . '/../templates/header.php';
}
