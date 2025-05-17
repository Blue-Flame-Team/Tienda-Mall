<?php
/**
 * Página de cierre de sesión
 * Destruye la sesión actual y redirige al usuario a la página principal
 */

// Iniciar la sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Eliminar todas las variables de sesión
$_SESSION = array();

// Si se está usando un cookie de sesión, eliminarlo también
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destruir la sesión
session_destroy();

// Redirigir a la página principal
header("Location: ../index.php");
exit;
?>
