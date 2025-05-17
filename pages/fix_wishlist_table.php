<?php
/**
 * Fix Wishlist Table Script
 * Este script verifica y crea la tabla de wishlist si no existe
 */

// Incluir archivos necesarios
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Inicializar conexión a la base de datos
$db = Database::getInstance();

// Función para verificar si una tabla existe
function tableExists($tableName) {
    global $db;
    try {
        $stmt = $db->query("SHOW TABLES LIKE '{$tableName}'");
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        return false;
    }
}

// Mostrar cabecera HTML
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Wishlist Table - Tienda</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .success {
            color: green;
            background: #e7f7e7;
            padding: 10px;
            border-radius: 3px;
            margin: 10px 0;
        }
        .error {
            color: red;
            background: #ffeeee;
            padding: 10px;
            border-radius: 3px;
            margin: 10px 0;
        }
        .info {
            color: #0066cc;
            background: #f0f7ff;
            padding: 10px;
            border-radius: 3px;
            margin: 10px 0;
        }
        pre {
            background: #f5f5f5;
            padding: 10px;
            border-radius: 3px;
            overflow: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Fix Wishlist Table</h1>
        
        <?php
        // Verificar si la tabla wishlist existe
        if (!tableExists('wishlist')) {
            echo '<div class="info">La tabla wishlist no existe. Creando tabla...</div>';
            
            try {
                // Crear la tabla wishlist
                $sql = "CREATE TABLE wishlist (
                    wishlist_id INT(11) NOT NULL AUTO_INCREMENT,
                    user_id INT(11) NOT NULL,
                    product_id INT(11) NOT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (wishlist_id),
                    UNIQUE KEY wishlist_user_product (user_id, product_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
                
                $db->exec($sql);
                echo '<div class="success">¡Tabla wishlist creada correctamente!</div>';
            } catch (Exception $e) {
                echo '<div class="error">Error al crear la tabla wishlist: ' . $e->getMessage() . '</div>';
            }
        } else {
            echo '<div class="success">La tabla wishlist ya existe.</div>';
            
            // Verificar la estructura de la tabla
            try {
                $stmt = $db->query("DESCRIBE wishlist");
                $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                echo '<div class="info">Estructura actual de la tabla wishlist:</div>';
                echo '<pre>';
                $stmt = $db->query("SHOW COLUMNS FROM wishlist");
                $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($columns as $column) {
                    echo $column['Field'] . ' - ' . $column['Type'] . ' - ' . ($column['Null'] === 'NO' ? 'NOT NULL' : 'NULL') . "\n";
                }
                echo '</pre>';
                
                // Verificar si faltan columnas esenciales
                $required_columns = ['wishlist_id', 'user_id', 'product_id', 'created_at'];
                $missing_columns = array_diff($required_columns, array_column($columns, 'Field'));
                
                if (!empty($missing_columns)) {
                    echo '<div class="error">Faltan columnas en la tabla wishlist: ' . implode(', ', $missing_columns) . '</div>';
                    
                    // Agregar columnas faltantes
                    foreach ($missing_columns as $column) {
                        try {
                            $sql = "";
                            if ($column === 'wishlist_id') {
                                $sql = "ALTER TABLE wishlist ADD COLUMN wishlist_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST";
                            } elseif ($column === 'user_id') {
                                $sql = "ALTER TABLE wishlist ADD COLUMN user_id INT(11) NOT NULL AFTER wishlist_id";
                            } elseif ($column === 'product_id') {
                                $sql = "ALTER TABLE wishlist ADD COLUMN product_id INT(11) NOT NULL AFTER user_id";
                            } elseif ($column === 'created_at') {
                                $sql = "ALTER TABLE wishlist ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP";
                            }
                            
                            if (!empty($sql)) {
                                $db->exec($sql);
                                echo '<div class="success">Columna ' . $column . ' agregada correctamente.</div>';
                            }
                        } catch (Exception $e) {
                            echo '<div class="error">Error al agregar la columna ' . $column . ': ' . $e->getMessage() . '</div>';
                        }
                    }
                } else {
                    echo '<div class="success">La estructura de la tabla wishlist es correcta.</div>';
                }
                
                // Verificar si existe el índice único
                $stmt = $db->query("SHOW INDEX FROM wishlist WHERE Key_name = 'wishlist_user_product'");
                if ($stmt->rowCount() === 0) {
                    try {
                        $db->exec("ALTER TABLE wishlist ADD UNIQUE KEY wishlist_user_product (user_id, product_id)");
                        echo '<div class="success">Índice único agregado correctamente.</div>';
                    } catch (Exception $e) {
                        echo '<div class="error">Error al agregar el índice único: ' . $e->getMessage() . '</div>';
                    }
                }
            } catch (Exception $e) {
                echo '<div class="error">Error al verificar la estructura de la tabla: ' . $e->getMessage() . '</div>';
            }
        }
        
        // Verificar datos en la tabla
        try {
            $stmt = $db->query("SELECT COUNT(*) as count FROM wishlist");
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            echo '<div class="info">La tabla wishlist contiene ' . $count . ' registros.</div>';
        } catch (Exception $e) {
            echo '<div class="error">Error al contar registros: ' . $e->getMessage() . '</div>';
        }
        ?>
        
        <p><a href="../index.php">Volver a la página principal</a></p>
    </div>
</body>
</html>
