<?php
/**
 * API endpoint para manejar operaciones de la lista de deseos (wishlist)
 */

// Permitir solicitudes desde cualquier origen (CORS)
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Incluir archivos necesarios
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user'])) {
    echo json_encode([
        'success' => false,
        'error' => 'not_logged_in',
        'message' => 'Debes iniciar sesión para gestionar tu lista de favoritos'
    ]);
    exit;
}

// Obtener la conexión a la BD
$db = Database::getInstance();

// Obtener datos de la solicitud
$requestMethod = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents("php://input"), true);

// Procesar según el método de solicitud
if ($requestMethod === 'POST') {
    // Añadir/Quitar producto de la wishlist
    if (isset($data['product_id']) && isset($data['action'])) {
        $productId = (int)$data['product_id'];
        $action = $data['action'];
        $userId = $_SESSION['user']['guest'] ?? false ? null : $_SESSION['user']['id'];
        
        // Si es un usuario invitado, usar la sesión
        if (isset($_SESSION['user']['guest']) && $_SESSION['user']['guest'] === true) {
            if ($action === 'add') {
                // Asegurarse que la wishlist de sesión existe
                if (!isset($_SESSION['wishlist'])) {
                    $_SESSION['wishlist'] = [];
                }
                
                // Verificar si el producto ya está en la wishlist
                if (!in_array($productId, $_SESSION['wishlist'])) {
                    $_SESSION['wishlist'][] = $productId;
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Producto añadido a favoritos correctamente'
                ]);
                exit;
            } elseif ($action === 'remove') {
                // Eliminar el producto de la wishlist
                if (isset($_SESSION['wishlist']) && ($key = array_search($productId, $_SESSION['wishlist'])) !== false) {
                    unset($_SESSION['wishlist'][$key]);
                    $_SESSION['wishlist'] = array_values($_SESSION['wishlist']); // Reindexar el array
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Producto eliminado de favoritos correctamente'
                ]);
                exit;
            }
        } else {
            // Usuario registrado - usar la base de datos
            try {
                if ($action === 'add') {
                    // Verificar si ya existe en la wishlist
                    $stmt = $db->query("SELECT * FROM wishlist WHERE user_id = ? AND product_id = ?", [$userId, $productId]);
                    if ($stmt->rowCount() === 0) {
                        // Añadir a la wishlist
                        $result = $db->query(
                            "INSERT INTO wishlist (user_id, product_id, created_at) VALUES (?, ?, NOW())",
                            [$userId, $productId]
                        );
                        
                        if ($result) {
                            echo json_encode([
                                'success' => true,
                                'message' => 'Producto añadido a favoritos correctamente'
                            ]);
                            exit;
                        }
                    } else {
                        // Ya existe en la wishlist
                        echo json_encode([
                            'success' => true,
                            'message' => 'Este producto ya está en tu lista de favoritos'
                        ]);
                        exit;
                    }
                } elseif ($action === 'remove') {
                    // Eliminar de la wishlist
                    $result = $db->query(
                        "DELETE FROM wishlist WHERE user_id = ? AND product_id = ?",
                        [$userId, $productId]
                    );
                    
                    if ($result) {
                        echo json_encode([
                            'success' => true,
                            'message' => 'Producto eliminado de favoritos correctamente'
                        ]);
                        exit;
                    }
                }
            } catch (Exception $e) {
                // Error en la base de datos
                echo json_encode([
                    'success' => false,
                    'message' => 'Error al procesar la solicitud: ' . $e->getMessage()
                ]);
                exit;
            }
        }
    }
    
    // Si llegamos aquí, hubo un error
    echo json_encode([
        'success' => false,
        'message' => 'Solicitud inválida'
    ]);
    exit;
} elseif ($requestMethod === 'GET') {
    // Obtener la wishlist del usuario
    $userId = $_SESSION['user']['guest'] ?? false ? null : $_SESSION['user']['id'];
    $wishlistItems = [];
    
    // Si es un usuario invitado, usar la sesión
    if (isset($_SESSION['user']['guest']) && $_SESSION['user']['guest'] === true) {
        if (isset($_SESSION['wishlist']) && !empty($_SESSION['wishlist'])) {
            try {
                // Obtener los datos de los productos en la wishlist
                $placeholders = implode(',', array_fill(0, count($_SESSION['wishlist']), '?'));
                $stmt = $db->query(
                    "SELECT p.*, pi.image_path 
                     FROM products p 
                     LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
                     WHERE p.product_id IN ($placeholders)",
                    $_SESSION['wishlist']
                );
                $wishlistItems = $stmt->fetchAll();
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error al obtener los productos: ' . $e->getMessage()
                ]);
                exit;
            }
        }
    } else {
        // Usuario registrado - usar la base de datos
        try {
            $stmt = $db->query(
                "SELECT p.*, pi.image_path, w.created_at as added_date
                 FROM wishlist w
                 JOIN products p ON w.product_id = p.product_id
                 LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
                 WHERE w.user_id = ?
                 ORDER BY w.created_at DESC",
                [$userId]
            );
            $wishlistItems = $stmt->fetchAll();
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error al obtener la lista de favoritos: ' . $e->getMessage()
            ]);
            exit;
        }
    }
    
    echo json_encode([
        'success' => true,
        'wishlist' => $wishlistItems
    ]);
    exit;
} 

// Método no soportado
echo json_encode([
    'success' => false,
    'message' => 'Método no soportado'
]);
?>
