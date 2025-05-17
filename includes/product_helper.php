<?php
/**
 * Funciones para manejo de productos
 * Este archivo contiene funciones para obtener, filtrar y modificar productos
 */

// Incluir archivos necesarios si no están ya incluidos
if (!defined('BASE_PATH')) {
    require_once 'config.php';
}

/**
 * Obtener todos los productos con opcional filtrado y ordenamiento
 * 
 * @param array $filters Filtros para los productos (categoría, marca, etc)
 * @param string $order_by Campo por el cual ordenar
 * @param string $order_dir Dirección de ordenamiento (ASC, DESC)
 * @param int $limit Límite de resultados
 * @param int $offset Offset para paginación
 * @return array Array de productos
 */
function getAllProducts($filters = [], $order_by = 'created_at', $order_dir = 'DESC', $limit = 0, $offset = 0) {
    global $conn;
    
    // Construir la consulta SQL
    $sql = "SELECT p.*, c.name as category_name, b.name as brand_name, 
            (SELECT image_path FROM product_images WHERE product_id = p.product_id AND is_primary = 1 LIMIT 1) as primary_image,
            (SELECT image_url FROM product_images WHERE product_id = p.product_id AND is_primary = 1 LIMIT 1) as primary_image_url
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.category_id 
            LEFT JOIN brands b ON p.brand_id = b.brand_id 
            WHERE p.is_active = 1";
    
    $params = [];
    
    // Añadir filtros si existen
    if (!empty($filters)) {
        if (isset($filters['category_id']) && $filters['category_id'] > 0) {
            $sql .= " AND p.category_id = :category_id";
            $params[':category_id'] = $filters['category_id'];
        }
        
        if (isset($filters['brand_id']) && $filters['brand_id'] > 0) {
            $sql .= " AND p.brand_id = :brand_id";
            $params[':brand_id'] = $filters['brand_id'];
        }
        
        if (isset($filters['is_featured']) && $filters['is_featured'] == 1) {
            $sql .= " AND p.is_featured = 1";
        }
        
        if (isset($filters['search']) && !empty($filters['search'])) {
            $sql .= " AND (p.name LIKE :search OR p.description LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        if (isset($filters['min_price']) && $filters['min_price'] > 0) {
            $sql .= " AND p.price >= :min_price";
            $params[':min_price'] = $filters['min_price'];
        }
        
        if (isset($filters['max_price']) && $filters['max_price'] > 0) {
            $sql .= " AND p.price <= :max_price";
            $params[':max_price'] = $filters['max_price'];
        }
    }
    
    // Añadir ordenamiento
    $sql .= " ORDER BY p.$order_by $order_dir";
    
    // Añadir límite y offset si existen
    if ($limit > 0) {
        $sql .= " LIMIT :limit";
        $params[':limit'] = (int)$limit;
        
        if ($offset > 0) {
            $sql .= " OFFSET :offset";
            $params[':offset'] = (int)$offset;
        }
    }
    
    try {
        $stmt = $conn->prepare($sql);
        
        // Bind parámetros
        foreach ($params as $key => $value) {
            if (is_int($value)) {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value, PDO::PARAM_STR);
            }
        }
        
        $stmt->execute();
        $products = $stmt->fetchAll();
        
        // Arreglar URLs de imágenes
        foreach ($products as &$product) {
            if (!empty($product['primary_image'])) {
                $product['image_path'] = fix_image_path($product['primary_image']);
            } elseif (!empty($product['primary_image_url'])) {
                $product['image_path'] = $product['primary_image_url'];
            } else {
                $product['image_path'] = SITE_URL . '/assets/images/no-image.jpg';
            }
        }
        
        return $products;
    } catch (PDOException $e) {
        error_log("Error obteniendo productos: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtener un producto por su ID
 * 
 * @param int $product_id ID del producto
 * @return array|bool Datos del producto o false si no se encuentra
 */
function getProductById($product_id) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT p.*, c.name as category_name, b.name as brand_name 
                               FROM products p 
                               LEFT JOIN categories c ON p.category_id = c.category_id 
                               LEFT JOIN brands b ON p.brand_id = b.brand_id 
                               WHERE p.product_id = :product_id");
        $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $stmt->execute();
        
        $product = $stmt->fetch();
        
        if ($product) {
            // Obtener imágenes del producto
            $stmt = $conn->prepare("SELECT * FROM product_images WHERE product_id = :product_id ORDER BY is_primary DESC");
            $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
            $stmt->execute();
            $images = $stmt->fetchAll();
            
            $product['images'] = $images;
            
            // Asignar la imagen principal
            if (!empty($images)) {
                foreach ($images as $image) {
                    if ($image['is_primary'] == 1) {
                        if (!empty($image['image_path'])) {
                            $product['primary_image'] = fix_image_path($image['image_path']);
                        } elseif (!empty($image['image_url'])) {
                            $product['primary_image'] = $image['image_url'];
                        }
                        break;
                    }
                }
                
                // Si no hay imagen principal, usar la primera
                if (!isset($product['primary_image']) && !empty($images[0])) {
                    if (!empty($images[0]['image_path'])) {
                        $product['primary_image'] = fix_image_path($images[0]['image_path']);
                    } elseif (!empty($images[0]['image_url'])) {
                        $product['primary_image'] = $images[0]['image_url'];
                    }
                }
            }
            
            // Si no hay imagen asignada, usar la imagen por defecto
            if (!isset($product['primary_image'])) {
                $product['primary_image'] = SITE_URL . '/assets/images/no-image.jpg';
            }
        }
        
        return $product;
    } catch (PDOException $e) {
        error_log("Error obteniendo producto: " . $e->getMessage());
        return false;
    }
}

// La función fix_image_path se ha movido a image_helper.php para evitar duplicación
// y está disponible a través de la inclusión de ese archivo

/**
 * Obtener productos destacados
 * 
 * @param int $limit Límite de productos a obtener
 * @return array Array de productos destacados
 */
function getFeaturedProducts($limit = 8) {
    return getAllProducts(['is_featured' => 1], 'created_at', 'DESC', $limit);
}

/**
 * Obtener productos relacionados a un producto
 * 
 * @param int $product_id ID del producto actual
 * @param int $limit Límite de productos a obtener
 * @return array Array de productos relacionados
 */
function getRelatedProducts($product_id, $limit = 4) {
    global $conn;
    
    try {
        // Obtener categoría y marca del producto actual
        $stmt = $conn->prepare("SELECT category_id, brand_id FROM products WHERE product_id = :product_id");
        $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $stmt->execute();
        $product = $stmt->fetch();
        
        if (!$product) {
            return [];
        }
        
        // Obtener productos de la misma categoría o marca, excluyendo el producto actual
        $sql = "SELECT p.*, 
                (SELECT image_path FROM product_images WHERE product_id = p.product_id AND is_primary = 1 LIMIT 1) as primary_image,
                (SELECT image_url FROM product_images WHERE product_id = p.product_id AND is_primary = 1 LIMIT 1) as primary_image_url
                FROM products p 
                WHERE p.is_active = 1 
                AND p.product_id != :product_id 
                AND (p.category_id = :category_id OR p.brand_id = :brand_id) 
                ORDER BY RAND() 
                LIMIT :limit";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $stmt->bindParam(':category_id', $product['category_id'], PDO::PARAM_INT);
        $stmt->bindParam(':brand_id', $product['brand_id'], PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $related_products = $stmt->fetchAll();
        
        // Arreglar URLs de imágenes
        foreach ($related_products as &$product) {
            if (!empty($product['primary_image'])) {
                $product['image_path'] = fix_image_path($product['primary_image']);
            } elseif (!empty($product['primary_image_url'])) {
                $product['image_path'] = $product['primary_image_url'];
            } else {
                $product['image_path'] = SITE_URL . '/assets/images/no-image.jpg';
            }
        }
        
        return $related_products;
    } catch (PDOException $e) {
        error_log("Error obteniendo productos relacionados: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtener todas las categorías activas
 * 
 * @return array Array de categorías
 */
function getAllCategories() {
    global $conn;
    
    try {
        $stmt = $conn->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY name");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error obteniendo categorías: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtener todas las marcas activas
 * 
 * @return array Array de marcas
 */
function getAllBrands() {
    global $conn;
    
    try {
        $stmt = $conn->query("SELECT * FROM brands WHERE is_active = 1 ORDER BY name");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error obteniendo marcas: " . $e->getMessage());
        return [];
    }
}

/**
 * Buscar productos por término de búsqueda
 * 
 * @param string $search_term Término de búsqueda
 * @param int $limit Límite de resultados
 * @param int $offset Offset para paginación
 * @return array Array de productos encontrados
 */
function searchProducts($search_term, $limit = 12, $offset = 0) {
    return getAllProducts(['search' => $search_term], 'created_at', 'DESC', $limit, $offset);
}

/**
 * Contar total de productos con filtros aplicados
 * 
 * @param array $filters Filtros para los productos
 * @return int Total de productos
 */
function countProducts($filters = []) {
    global $conn;
    
    // Construir la consulta SQL
    $sql = "SELECT COUNT(*) FROM products p WHERE p.is_active = 1";
    
    $params = [];
    
    // Añadir filtros si existen
    if (!empty($filters)) {
        if (isset($filters['category_id']) && $filters['category_id'] > 0) {
            $sql .= " AND p.category_id = :category_id";
            $params[':category_id'] = $filters['category_id'];
        }
        
        if (isset($filters['brand_id']) && $filters['brand_id'] > 0) {
            $sql .= " AND p.brand_id = :brand_id";
            $params[':brand_id'] = $filters['brand_id'];
        }
        
        if (isset($filters['is_featured']) && $filters['is_featured'] == 1) {
            $sql .= " AND p.is_featured = 1";
        }
        
        if (isset($filters['search']) && !empty($filters['search'])) {
            $sql .= " AND (p.name LIKE :search OR p.description LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        if (isset($filters['min_price']) && $filters['min_price'] > 0) {
            $sql .= " AND p.price >= :min_price";
            $params[':min_price'] = $filters['min_price'];
        }
        
        if (isset($filters['max_price']) && $filters['max_price'] > 0) {
            $sql .= " AND p.price <= :max_price";
            $params[':max_price'] = $filters['max_price'];
        }
    }
    
    try {
        $stmt = $conn->prepare($sql);
        
        // Bind parámetros
        foreach ($params as $key => $value) {
            if (is_int($value)) {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value, PDO::PARAM_STR);
            }
        }
        
        $stmt->execute();
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Error contando productos: " . $e->getMessage());
        return 0;
    }
}
