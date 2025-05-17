<?php
/**
 * Database Helper Functions
 * Funciones de utilidad para manejar la base de datos y evitar errores comunes
 */

/**
 * Verifica si una tabla existe en la base de datos
 * 
 * @param string $tableName Nombre de la tabla a verificar
 * @return bool True si la tabla existe, false en caso contrario
 */
function tableExists($tableName) {
    try {
        $db = Database::getInstance();
        $stmt = $db->query("SHOW TABLES LIKE '{$tableName}'");
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Verifica si una columna existe en una tabla
 * 
 * @param string $tableName Nombre de la tabla
 * @param string $columnName Nombre de la columna a verificar
 * @return bool True si la columna existe, false en caso contrario
 */
function columnExists($tableName, $columnName) {
    try {
        $db = Database::getInstance();
        $stmt = $db->query("SHOW COLUMNS FROM {$tableName} LIKE '{$columnName}'");
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Obtiene la estructura completa de una tabla
 * 
 * @param string $tableName Nombre de la tabla
 * @return array Array con la estructura de la tabla
 */
function getTableStructure($tableName) {
    try {
        $db = Database::getInstance();
        $stmt = $db->query("DESCRIBE {$tableName}");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Construye una consulta SELECT segura que verifica la existencia de columnas
 * 
 * @param string $tableName Nombre de la tabla principal
 * @param array $conditions Condiciones WHERE (clave => valor)
 * @param string $orderBy Columna para ordenar los resultados
 * @param string $orderDirection Dirección de ordenamiento (ASC o DESC)
 * @param int $limit Límite de resultados
 * @param array $joins Array de joins (formato: ['table' => 'tabla', 'on' => 'condición', 'type' => 'INNER|LEFT|RIGHT'])
 * @return array Array con la consulta SQL y los parámetros
 */
function buildSafeQuery($tableName, $conditions = [], $orderBy = null, $orderDirection = 'ASC', $limit = null, $joins = []) {
    $sql = "SELECT * FROM {$tableName}";
    $params = [];
    
    // Aplicar JOINs si existen
    if (!empty($joins)) {
        foreach ($joins as $join) {
            if (tableExists($join['table'])) {
                $sql .= " {$join['type']} JOIN {$join['table']} ON {$join['on']}";
            }
        }
    }
    
    // Verificar y aplicar condiciones WHERE solo si las columnas existen
    $validConditions = [];
    foreach ($conditions as $column => $value) {
        if (columnExists($tableName, $column)) {
            // Usar ? como marcador de posición para compatibilidad con el método query de Database
            $validConditions[] = "{$column} = ?";
            $params[] = $value; // Añadir valor directo al array de parámetros
        }
    }
    
    if (!empty($validConditions)) {
        $sql .= " WHERE " . implode(' AND ', $validConditions);
    }
    
    // Verificar y aplicar ORDER BY solo si la columna existe
    if ($orderBy && columnExists($tableName, $orderBy)) {
        $sql .= " ORDER BY {$orderBy} {$orderDirection}";
    } else if ($orderBy === 'RAND()') {
        $sql .= " ORDER BY RAND()";
    }
    
    // Aplicar LIMIT si existe
    if ($limit) {
        $sql .= " LIMIT {$limit}";
    }
    
    return [
        'sql' => $sql,
        'params' => $params
    ];
}

/**
 * Ejecuta una consulta segura que verifica la existencia de columnas
 * 
 * @param string $tableName Nombre de la tabla principal
 * @param array $conditions Condiciones WHERE (clave => valor)
 * @param string $orderBy Columna para ordenar los resultados
 * @param string $orderDirection Dirección de ordenamiento (ASC o DESC)
 * @param int $limit Límite de resultados
 * @param array $joins Array de joins
 * @return array Resultados de la consulta
 */
function safeQuery($tableName, $conditions = [], $orderBy = null, $orderDirection = 'ASC', $limit = null, $joins = []) {
    try {
        $db = Database::getInstance();
        $query = buildSafeQuery($tableName, $conditions, $orderBy, $orderDirection, $limit, $joins);
        
        // La clase Database combina prepare y execute en el método query
        $stmt = $db->query($query['sql'], $query['params']);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        // Registrar el error pero devolver un array vacío para no romper la aplicación
        error_log("Error en safeQuery: " . $e->getMessage());
        return [];
    }
}

/**
 * Obtiene productos de manera segura verificando las columnas existentes
 * 
 * @param array $conditions Condiciones adicionales
 * @param int $limit Número máximo de productos a devolver
 * @param bool $random Si se debe ordenar aleatoriamente
 * @return array Array de productos
 */
function getProductsSafely($conditions = [], $limit = null, $random = false) {
    $standardConditions = [];
    
    // Solo añadir condiciones si existen las columnas
    if (columnExists('product', 'is_active')) {
        $standardConditions['is_active'] = 'yes';
    }
    
    if (columnExists('product', 'is_deleted')) {
        $standardConditions['is_deleted'] = 'no';
    }
    
    // Combinar con condiciones personalizadas
    $allConditions = array_merge($standardConditions, $conditions);
    
    // Determinar orden
    $orderBy = $random ? 'RAND()' : null;
    
    return safeQuery('product', $allConditions, $orderBy, 'ASC', $limit);
}

/**
 * Obtiene un producto por ID de manera segura
 * 
 * @param int $productId ID del producto
 * @return array|null Datos del producto o null si no se encuentra
 */
function getProductByIdSafely($productId) {
    $results = safeQuery('product', ['product_id' => $productId]);
    return !empty($results) ? $results[0] : null;
}
?>
