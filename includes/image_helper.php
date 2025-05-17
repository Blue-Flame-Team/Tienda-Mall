<?php
/**
 * Funciones para el manejo de imágenes
 * Este archivo contiene funciones para manipular, almacenar y recuperar imágenes
 */

// Incluir archivos necesarios si no están ya incluidos
if (!defined('BASE_PATH')) {
    require_once 'config.php';
}

/**
 * Corrige la ruta de una imagen para que sea accesible desde el frontend
 * 
 * @param string $path Ruta original de la imagen
 * @return string Ruta corregida de la imagen
 */
function fix_image_path($path) {
    if (empty($path)) {
        // Imagen por defecto cuando no hay ruta
        return SITE_URL . "/assets/images/no-image.jpg";
    }
    
    // Si ya es una URL completa, devolverla tal cual
    if (preg_match('~^https?://~i', $path)) {
        return $path;
    }
    
    // Eliminar "../" del principio de la ruta si existe
    if (strpos($path, "../") === 0) {
        $path = substr($path, 3);
    }
    
    // Si comienza con / o \, considerarlo como ruta relativa desde la raíz
    if (preg_match('~^[/\\]~', $path)) {
        return SITE_URL . '/' . ltrim($path, '/\\');
    }
    
    // Si comienza con uploads/, ajustar la ruta
    if (strpos($path, 'uploads/') === 0) {
        return SITE_URL . '/' . $path;
    }
    
    // Por defecto, asumir que es relativa a la carpeta uploads
    return SITE_URL . '/uploads/' . $path;
}

/**
 * Sube una imagen al servidor
 * 
 * @param array $file Array de archivo ($_FILES['nombre'])
 * @param string $destination_folder Carpeta de destino (relativa a la carpeta uploads)
 * @param string $filename Nombre del archivo (opcional, si no se proporciona se genera automáticamente)
 * @param array $options Opciones adicionales (max_size, allowed_types)
 * @return array Información sobre la imagen subida (success, filename, path, url, error)
 */
function upload_image($file, $destination_folder = 'products', $filename = null, $options = []) {
    // Opciones por defecto
    $default_options = [
        'max_size' => 5 * 1024 * 1024, // 5MB
        'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'create_directory' => true,
        'resize' => false,
        'max_width' => 1200,
        'max_height' => 1200
    ];
    
    $options = array_merge($default_options, $options);
    
    // Inicializar respuesta
    $response = [
        'success' => false,
        'filename' => '',
        'path' => '',
        'url' => '',
        'error' => ''
    ];
    
    // Verificar si hay errores en la subida
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño máximo permitido por PHP.',
            UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño máximo permitido por el formulario.',
            UPLOAD_ERR_PARTIAL => 'El archivo fue subido parcialmente.',
            UPLOAD_ERR_NO_FILE => 'No se subió ningún archivo.',
            UPLOAD_ERR_NO_TMP_DIR => 'Falta la carpeta temporal.',
            UPLOAD_ERR_CANT_WRITE => 'No se pudo escribir el archivo en el disco.',
            UPLOAD_ERR_EXTENSION => 'Una extensión de PHP detuvo la subida.'
        ];
        
        $response['error'] = $errors[$file['error']] ?? 'Error desconocido al subir el archivo.';
        return $response;
    }
    
    // Verificar el tamaño del archivo
    if ($file['size'] > $options['max_size']) {
        $response['error'] = 'El archivo es demasiado grande. El tamaño máximo permitido es ' . formatSizeUnits($options['max_size']) . '.';
        return $response;
    }
    
    // Verificar el tipo de archivo
    $file_info = pathinfo($file['name']);
    $extension = strtolower($file_info['extension']);
    
    if (!in_array($extension, $options['allowed_types'])) {
        $response['error'] = 'Tipo de archivo no permitido. Los tipos permitidos son: ' . implode(', ', $options['allowed_types']) . '.';
        return $response;
    }
    
    // Generar nombre de archivo único si no se proporcionó
    if ($filename === null) {
        $filename = md5(uniqid(rand(), true)) . '.' . $extension;
    } else {
        // Asegurarse de que el nombre de archivo sea seguro
        $filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename);
        
        // Añadir extensión si no la tiene
        if (pathinfo($filename, PATHINFO_EXTENSION) === '') {
            $filename .= '.' . $extension;
        }
    }
    
    // Crear carpeta de destino si no existe
    $upload_path = UPLOADS_PATH . '/' . $destination_folder;
    if (!file_exists($upload_path) && $options['create_directory']) {
        mkdir($upload_path, 0755, true);
    }
    
    // Ruta completa del archivo
    $file_path = $upload_path . '/' . $filename;
    
    // Intentar subir el archivo
    if (move_uploaded_file($file['tmp_name'], $file_path)) {
        // Redimensionar imagen si es necesario
        if ($options['resize']) {
            resize_image($file_path, $options['max_width'], $options['max_height']);
        }
        
        $response['success'] = true;
        $response['filename'] = $filename;
        $response['path'] = $destination_folder . '/' . $filename;
        $response['url'] = UPLOADS_URL . '/' . $destination_folder . '/' . $filename;
    } else {
        $response['error'] = 'Error al guardar el archivo en el servidor.';
    }
    
    return $response;
}

/**
 * Elimina una imagen del servidor
 * 
 * @param string $path Ruta de la imagen (relativa a la carpeta uploads)
 * @return bool True si se eliminó correctamente, false si no
 */
function delete_image($path) {
    // Si es una URL completa, extraer la ruta relativa
    if (preg_match('~^https?://~i', $path)) {
        $uploads_url = UPLOADS_URL;
        $path = str_replace($uploads_url, '', $path);
    }
    
    // Eliminar / inicial si existe
    $path = ltrim($path, '/');
    
    // Añadir directorio uploads si no está incluido
    if (strpos($path, 'uploads/') !== 0) {
        $path = 'uploads/' . $path;
    }
    
    // Ruta completa del archivo
    $file_path = BASE_PATH . '/' . $path;
    
    // Verificar si el archivo existe
    if (file_exists($file_path)) {
        return unlink($file_path);
    }
    
    return false;
}

/**
 * Redimensiona una imagen manteniendo la proporción
 * 
 * @param string $file_path Ruta completa del archivo
 * @param int $max_width Ancho máximo
 * @param int $max_height Alto máximo
 * @return bool True si se redimensionó correctamente, false si no
 */
function resize_image($file_path, $max_width, $max_height) {
    // Verificar si GD está disponible
    if (!extension_loaded('gd') || !function_exists('imagecreatetruecolor')) {
        return false;
    }
    
    // Obtener información del archivo
    list($width, $height, $type) = getimagesize($file_path);
    
    // Verificar si la imagen necesita ser redimensionada
    if ($width <= $max_width && $height <= $max_height) {
        return true;
    }
    
    // Calcular nuevas dimensiones manteniendo la proporción
    $ratio = min($max_width / $width, $max_height / $height);
    $new_width = round($width * $ratio);
    $new_height = round($height * $ratio);
    
    // Crear imagen según su tipo
    switch ($type) {
        case IMAGETYPE_JPEG:
            $source = imagecreatefromjpeg($file_path);
            break;
        case IMAGETYPE_PNG:
            $source = imagecreatefrompng($file_path);
            break;
        case IMAGETYPE_GIF:
            $source = imagecreatefromgif($file_path);
            break;
        case IMAGETYPE_WEBP:
            if (function_exists('imagecreatefromwebp')) {
                $source = imagecreatefromwebp($file_path);
            } else {
                return false;
            }
            break;
        default:
            return false;
    }
    
    // Crear imagen redimensionada
    $destination = imagecreatetruecolor($new_width, $new_height);
    
    // Manejar transparencia para PNG y GIF
    if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_GIF) {
        imagecolortransparent($destination, imagecolorallocate($destination, 0, 0, 0));
        imagealphablending($destination, false);
        imagesavealpha($destination, true);
    }
    
    // Redimensionar
    imagecopyresampled($destination, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    
    // Guardar imagen redimensionada
    switch ($type) {
        case IMAGETYPE_JPEG:
            imagejpeg($destination, $file_path, 90); // 90% de calidad
            break;
        case IMAGETYPE_PNG:
            imagepng($destination, $file_path, 9); // Compresión máxima
            break;
        case IMAGETYPE_GIF:
            imagegif($destination, $file_path);
            break;
        case IMAGETYPE_WEBP:
            if (function_exists('imagewebp')) {
                imagewebp($destination, $file_path, 90);
            } else {
                return false;
            }
            break;
    }
    
    // Liberar memoria
    imagedestroy($source);
    imagedestroy($destination);
    
    return true;
}

/**
 * Formatea un tamaño en bytes a una unidad más legible
 * 
 * @param int $bytes Tamaño en bytes
 * @return string Tamaño formateado con unidad
 */
function formatSizeUnits($bytes) {
    if ($bytes >= 1073741824) {
        $bytes = number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        $bytes = number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        $bytes = number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        $bytes = $bytes . ' bytes';
    } elseif ($bytes == 1) {
        $bytes = $bytes . ' byte';
    } else {
        $bytes = '0 bytes';
    }
    
    return $bytes;
}

/**
 * Verificar y arreglar todas las rutas de imágenes en la base de datos
 * 
 * @return array Resultados de la operación
 */
function fix_all_image_paths() {
    global $conn;
    
    $results = [
        'updated' => 0,
        'errors' => 0,
        'details' => []
    ];
    
    try {
        // Actualizar imágenes de productos
        $stmt = $conn->prepare("SELECT image_id, product_id, image_path FROM product_images WHERE image_path IS NOT NULL AND image_path != ''");
        $stmt->execute();
        $images = $stmt->fetchAll();
        
        foreach ($images as $image) {
            $fixed_path = fix_image_path($image['image_path']);
            
            // Solo actualizar si la ruta ha cambiado
            if ($fixed_path !== $image['image_path']) {
                try {
                    $update = $conn->prepare("UPDATE product_images SET image_url = :fixed_path WHERE image_id = :image_id");
                    $update->bindParam(':fixed_path', $fixed_path);
                    $update->bindParam(':image_id', $image['image_id']);
                    $update->execute();
                    
                    $results['updated']++;
                    $results['details'][] = "Actualizada imagen ID {$image['image_id']} del producto ID {$image['product_id']}";
                } catch (PDOException $e) {
                    $results['errors']++;
                    $results['details'][] = "Error al actualizar imagen ID {$image['image_id']}: " . $e->getMessage();
                }
            }
        }
    } catch (PDOException $e) {
        $results['errors']++;
        $results['details'][] = "Error general: " . $e->getMessage();
    }
    
    return $results;
}