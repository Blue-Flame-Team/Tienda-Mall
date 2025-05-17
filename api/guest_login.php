<?php
/**
 * API para iniciar sesión como invitado
 * Permite a los usuarios usar la tienda sin necesidad de crear una cuenta
 */

// Permitir solicitudes desde cualquier origen (CORS)
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Obtener datos enviados en la solicitud
$data = json_decode(file_get_contents("php://input"), true);

// Verificar que la solicitud es para inicio de sesión como invitado
if (isset($data['is_guest']) && $data['is_guest'] === true) {
    // Crear una sesión de invitado
    $_SESSION['user'] = [
        'guest' => true,
        'guest_id' => uniqid('guest_'), // Generar un ID único para el invitado
        'name' => 'Guest',
        'email' => 'guest@example.com'
    ];
    
    // Establecer la sesión del carrito si no existe
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [
            'items' => [],
            'total' => 0,
            'count' => 0
        ];
    }
    
    // Establecer la sesión de la lista de deseos si no existe
    if (!isset($_SESSION['wishlist'])) {
        $_SESSION['wishlist'] = [];
    }
    
    // Enviar respuesta de éxito
    echo json_encode([
        'success' => true,
        'message' => 'Iniciado sesión como invitado',
        'user' => [
            'guest' => true,
            'name' => 'Guest'
        ]
    ]);
} else {
    // Si la solicitud no es válida, enviar error
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Solicitud inválida'
    ]);
}
?>
