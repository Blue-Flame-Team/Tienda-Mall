<?php
// Este archivo es para depuración
// Start session
session_start();

// Incluir el archivo bootstrap para cargar las dependencias
require_once '../includes/bootstrap.php';

// Inicializar una variable para mostrar los mensajes de depuración
$debug_output = [];

try {
    // Obtener la instancia de la base de datos
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Verificar si la tabla admins existe
    $debug_output[] = "Verificando si la tabla 'admins' existe...";
    $stmt = $conn->query("SHOW TABLES LIKE 'admins'");
    $admins_table_exists = $stmt->rowCount() > 0;
    $debug_output[] = "¿La tabla 'admins' existe? " . ($admins_table_exists ? "SÍ" : "NO");
    
    if (!$admins_table_exists) {
        $debug_output[] = "Creando la tabla 'admins'...";
        
        // Crear la tabla admins
        $sql = "CREATE TABLE admins (
            admin_id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            is_super_admin TINYINT(1) DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            last_login DATETIME DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        $conn->exec($sql);
        $debug_output[] = "Tabla 'admins' creada exitosamente.";
        
        // Crear el primer usuario administrador
        $debug_output[] = "Creando administrador predeterminado...";
        $admin_name = 'مدير النظام';
        $admin_email = 'admin@tienda.com';
        $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO admins (name, email, password, is_super_admin, is_active, created_at) 
                            VALUES (:name, :email, :password, 1, 1, NOW())");
        $stmt->bindParam(':name', $admin_name);
        $stmt->bindParam(':email', $admin_email);
        $stmt->bindParam(':password', $admin_password);
        $stmt->execute();
        $debug_output[] = "Administrador predeterminado creado exitosamente.";
    }
    
    // Verificar si podemos contar registros
    $debug_output[] = "Intentando contar registros...";
    
    try {
        $stmt = $conn->query("SELECT COUNT(*) FROM admins");
        $total_admins = $stmt->fetchColumn();
        $debug_output[] = "Total de admins: " . $total_admins;
    } catch (PDOException $e) {
        $debug_output[] = "Error al contar admins: " . $e->getMessage();
    }
    
    try {
        $stmt = $conn->query("SELECT COUNT(*) FROM admins WHERE is_super_admin = 1");
        $total_super_admins = $stmt->fetchColumn();
        $debug_output[] = "Total de super admins: " . $total_super_admins;
    } catch (PDOException $e) {
        $debug_output[] = "Error al contar super admins: " . $e->getMessage();
    }
    
    try {
        $stmt = $conn->query("SELECT COUNT(*) FROM admins WHERE is_active = 1");
        $total_active_admins = $stmt->fetchColumn();
        $debug_output[] = "Total de admins activos: " . $total_active_admins;
    } catch (PDOException $e) {
        $debug_output[] = "Error al contar admins activos: " . $e->getMessage();
    }
    
    // Intentar consultar todos los administradores
    $debug_output[] = "Intentando consultar todos los administradores...";
    
    try {
        $stmt = $conn->query("SELECT * FROM admins ORDER BY created_at DESC");
        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $debug_output[] = "Número de administradores encontrados: " . count($admins);
        
        // Mostrar información de los administradores
        if (count($admins) > 0) {
            $debug_output[] = "Primer administrador:";
            $debug_output[] = "  - ID: " . $admins[0]['admin_id'];
            $debug_output[] = "  - Nombre: " . $admins[0]['name'];
            $debug_output[] = "  - Email: " . $admins[0]['email'];
        }
    } catch (PDOException $e) {
        $debug_output[] = "Error al consultar administradores: " . $e->getMessage();
    }
    
} catch (PDOException $e) {
    $debug_output[] = "ERROR CRÍTICO: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Depuración - Admin Users</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        .debug-item {
            padding: 8px;
            margin-bottom: 5px;
            border-left: 3px solid #4CAF50;
        }
        .error {
            border-left-color: #F44336;
            background-color: #FFEBEE;
        }
        .warning {
            border-left-color: #FFC107;
            background-color: #FFF8E1;
        }
        .success {
            border-left-color: #4CAF50;
            background-color: #E8F5E9;
        }
        .info {
            border-left-color: #2196F3;
            background-color: #E3F2FD;
        }
        a.btn {
            display: inline-block;
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
        }
        a.btn:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Depuración de Admin Users</h1>
        
        <h2>Resultados de la depuración:</h2>
        <?php foreach($debug_output as $index => $output): ?>
            <?php
                $class = 'info';
                if (strpos($output, 'ERROR') !== false || strpos($output, 'Error') !== false) {
                    $class = 'error';
                } elseif (strpos($output, 'exitosamente') !== false || strpos($output, 'SÍ') !== false) {
                    $class = 'success';
                } elseif (strpos($output, 'NO') !== false || strpos($output, 'Intentando') !== false) {
                    $class = 'warning';
                }
            ?>
            <div class="debug-item <?php echo $class; ?>">
                <?php echo htmlspecialchars($output); ?>
            </div>
        <?php endforeach; ?>
        
        <a href="admin_users.php" class="btn">Volver a Admin Users</a>
    </div>
</body>
</html>
