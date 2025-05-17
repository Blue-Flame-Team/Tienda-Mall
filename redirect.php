<?php
/**
 * Archivo de redirección automática
 * Redirige automáticamente solicitudes .html a sus equivalentes .php
 */

// Obtener la URL solicitada
$requested_uri = $_SERVER['REQUEST_URI'];

// Si la URL contiene .html, redirigir a .php
if (strpos($requested_uri, '.html') !== false) {
    $new_uri = str_replace('.html', '.php', $requested_uri);
    
    // Redirigir a la versión .php
    header('Location: ' . $new_uri, true, 301);
    exit;
}

// Si llegamos aquí, continuar con la solicitud normal
include __DIR__ . '/index.php';
?>
