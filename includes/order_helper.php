<?php
/**
 * Funciones para manejo de órdenes
 * Este archivo contiene funciones para crear, procesar y consultar órdenes
 */

// Incluir archivos necesarios si no están ya incluidos
if (!defined('BASE_PATH')) {
    require_once 'config.php';
}

/**
 * Crear una nueva orden
 * 
 * @param array $order_data Datos de la orden (user_id, shipping_address, etc)
 * @param array $items Items de la orden (product_id, quantity, price)
 * @return int|bool ID de la orden creada o false si hay error
 */
function createOrder($order_data, $items) {
    global $conn;
    
    try {
        $conn->beginTransaction();
        
        // Insertar la orden
        $stmt = $conn->prepare("INSERT INTO orders (
            user_id, order_number, total_amount, shipping_cost, tax_amount, 
            discount_amount, shipping_address, billing_address, payment_method, 
            status, payment_status, notes, created_at
        ) VALUES (
            :user_id, :order_number, :total_amount, :shipping_cost, :tax_amount, 
            :discount_amount, :shipping_address, :billing_address, :payment_method, 
            :status, :payment_status, :notes, NOW()
        )");
        
        // Generar número de orden único
        $order_number = 'ORD-' . date('Ymd') . '-' . mt_rand(1000, 9999);
        
        $stmt->bindParam(':user_id', $order_data['user_id']);
        $stmt->bindParam(':order_number', $order_number);
        $stmt->bindParam(':total_amount', $order_data['total_amount']);
        $stmt->bindParam(':shipping_cost', $order_data['shipping_cost']);
        $stmt->bindParam(':tax_amount', $order_data['tax_amount']);
        $stmt->bindParam(':discount_amount', $order_data['discount_amount']);
        $stmt->bindParam(':shipping_address', $order_data['shipping_address']);
        $stmt->bindParam(':billing_address', $order_data['billing_address']);
        $stmt->bindParam(':payment_method', $order_data['payment_method']);
        $stmt->bindParam(':status', $order_data['status']);
        $stmt->bindParam(':payment_status', $order_data['payment_status']);
        $stmt->bindParam(':notes', $order_data['notes']);
        
        $stmt->execute();
        
        $order_id = $conn->lastInsertId();
        
        // Insertar items de la orden
        foreach ($items as $item) {
            $stmt = $conn->prepare("INSERT INTO order_items (
                order_id, product_id, quantity, price, discount, total
            ) VALUES (
                :order_id, :product_id, :quantity, :price, :discount, :total
            )");
            
            $total = $item['price'] * $item['quantity'] - $item['discount'];
            
            $stmt->bindParam(':order_id', $order_id);
            $stmt->bindParam(':product_id', $item['product_id']);
            $stmt->bindParam(':quantity', $item['quantity']);
            $stmt->bindParam(':price', $item['price']);
            $stmt->bindParam(':discount', $item['discount']);
            $stmt->bindParam(':total', $total);
            
            $stmt->execute();
            
            // Actualizar stock del producto
            $stmt = $conn->prepare("UPDATE products 
                                   SET stock_quantity = stock_quantity - :quantity 
                                   WHERE product_id = :product_id");
            $stmt->bindParam(':quantity', $item['quantity']);
            $stmt->bindParam(':product_id', $item['product_id']);
            $stmt->execute();
        }
        
        $conn->commit();
        
        // Disparar correo de confirmación u otras acciones posteriores a la orden
        // sendOrderConfirmation($order_id);
        
        return $order_id;
    } catch (PDOException $e) {
        $conn->rollBack();
        error_log("Error creando orden: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtener una orden por su ID
 * 
 * @param int $order_id ID de la orden
 * @return array|bool Datos de la orden o false si no se encuentra
 */
function getOrderById($order_id) {
    global $conn;
    
    try {
        // Obtener datos de la orden
        $stmt = $conn->prepare("SELECT o.*, u.first_name, u.last_name, u.email 
                               FROM orders o 
                               LEFT JOIN users u ON o.user_id = u.user_id 
                               WHERE o.order_id = :order_id");
        $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
        $stmt->execute();
        
        $order = $stmt->fetch();
        
        if ($order) {
            // Obtener items de la orden
            $stmt = $conn->prepare("SELECT oi.*, p.name as product_name, p.sku,
                                  (SELECT image_path FROM product_images WHERE product_id = oi.product_id AND is_primary = 1 LIMIT 1) as image_path,
                                  (SELECT image_url FROM product_images WHERE product_id = oi.product_id AND is_primary = 1 LIMIT 1) as image_url
                                  FROM order_items oi 
                                  LEFT JOIN products p ON oi.product_id = p.product_id 
                                  WHERE oi.order_id = :order_id");
            $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
            $stmt->execute();
            
            $items = $stmt->fetchAll();
            
            // Agregar URLs de imágenes arregladas
            foreach ($items as &$item) {
                if (!empty($item['image_path'])) {
                    $item['image'] = fix_image_path($item['image_path']);
                } elseif (!empty($item['image_url'])) {
                    $item['image'] = $item['image_url'];
                } else {
                    $item['image'] = SITE_URL . '/assets/images/no-image.jpg';
                }
            }
            
            $order['items'] = $items;
        }
        
        return $order;
    } catch (PDOException $e) {
        error_log("Error obteniendo orden: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtener órdenes de un usuario
 * 
 * @param int $user_id ID del usuario
 * @param int $limit Límite de órdenes a obtener
 * @param int $offset Offset para paginación
 * @return array Array de órdenes
 */
function getUserOrders($user_id, $limit = 10, $offset = 0) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT o.*, 
                               (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) as items_count
                               FROM orders o 
                               WHERE o.user_id = :user_id 
                               ORDER BY o.created_at DESC 
                               LIMIT :limit OFFSET :offset");
        
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error obteniendo órdenes del usuario: " . $e->getMessage());
        return [];
    }
}

/**
 * Actualizar el estado de una orden
 * 
 * @param int $order_id ID de la orden
 * @param string $status Nuevo estado de la orden
 * @param string $payment_status Nuevo estado de pago (opcional)
 * @param string $notes Notas adicionales (opcional)
 * @return bool True si se actualiza correctamente, false si hay error
 */
function updateOrderStatus($order_id, $status, $payment_status = null, $notes = null) {
    global $conn;
    
    try {
        $sql = "UPDATE orders SET status = :status";
        $params = [':order_id' => $order_id, ':status' => $status];
        
        if ($payment_status !== null) {
            $sql .= ", payment_status = :payment_status";
            $params[':payment_status'] = $payment_status;
        }
        
        if ($notes !== null) {
            $sql .= ", notes = CONCAT(IFNULL(notes, ''), :notes)";
            $params[':notes'] = "\n" . date('Y-m-d H:i:s') . ": " . $notes;
        }
        
        $sql .= ", updated_at = NOW() WHERE order_id = :order_id";
        
        $stmt = $conn->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Error actualizando estado de orden: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtener estadísticas de órdenes para la administración
 * 
 * @param string $period Periodo (today, week, month, year)
 * @return array Estadísticas de órdenes
 */
function getOrderStats($period = 'month') {
    global $conn;
    
    try {
        $date_filter = "";
        
        switch ($period) {
            case 'today':
                $date_filter = "DATE(created_at) = CURDATE()";
                break;
            case 'week':
                $date_filter = "YEARWEEK(created_at) = YEARWEEK(NOW())";
                break;
            case 'month':
                $date_filter = "YEAR(created_at) = YEAR(NOW()) AND MONTH(created_at) = MONTH(NOW())";
                break;
            case 'year':
                $date_filter = "YEAR(created_at) = YEAR(NOW())";
                break;
            default:
                $date_filter = "1=1"; // Sin filtro de fecha
        }
        
        // Total de órdenes
        $stmt = $conn->query("SELECT COUNT(*) FROM orders WHERE $date_filter");
        $total_orders = $stmt->fetchColumn();
        
        // Total de ventas
        $stmt = $conn->query("SELECT SUM(total_amount) FROM orders WHERE payment_status = 'paid' AND $date_filter");
        $total_sales = $stmt->fetchColumn() ?: 0;
        
        // Órdenes por estado
        $stats_by_status = [];
        $stmt = $conn->query("SELECT status, COUNT(*) as count FROM orders WHERE $date_filter GROUP BY status");
        while ($row = $stmt->fetch()) {
            $stats_by_status[$row['status']] = $row['count'];
        }
        
        // Promedio de valor de orden
        $avg_order_value = 0;
        if ($total_orders > 0) {
            $stmt = $conn->query("SELECT AVG(total_amount) FROM orders WHERE $date_filter");
            $avg_order_value = $stmt->fetchColumn() ?: 0;
        }
        
        return [
            'total_orders' => $total_orders,
            'total_sales' => $total_sales,
            'avg_order_value' => $avg_order_value,
            'stats_by_status' => $stats_by_status
        ];
    } catch (PDOException $e) {
        error_log("Error obteniendo estadísticas de órdenes: " . $e->getMessage());
        return [
            'total_orders' => 0,
            'total_sales' => 0,
            'avg_order_value' => 0,
            'stats_by_status' => []
        ];
    }
}
