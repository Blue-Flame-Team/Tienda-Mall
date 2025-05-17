<?php
// Database configuration
$host = 'localhost';
$dbname = 'tienda_mall';
$username = 'root';
$password = '';

// Initialize error and success messages
$errors = [];
$success = [];

try {
    // Connect to database
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check which tables exist
    $admin_table_exists = $conn->query("SHOW TABLES LIKE 'admins'")->rowCount() > 0;
    $admin_users_table_exists = $conn->query("SHOW TABLES LIKE 'admin_users'")->rowCount() > 0;
    
    // 1. Fix admin_users table if it exists but lacks status column
    if ($admin_users_table_exists) {
        // Check if status column exists
        $result = $conn->query("SHOW COLUMNS FROM admin_users LIKE 'status'");
        $status_column_exists = $result->rowCount() > 0;
        
        if (!$status_column_exists) {
            // Add status column
            $conn->exec("ALTER TABLE admin_users ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active'");
            $success[] = "Agregada columna 'status' a la tabla admin_users";
            
            // Update status based on is_active
            $conn->exec("UPDATE admin_users SET status = CASE WHEN is_active = 1 THEN 'active' ELSE 'inactive' END");
            $success[] = "Actualizada columna 'status' en base a 'is_active'";
        }
    }
    
    // 2. Create admin_users table if it doesn't exist
    if (!$admin_users_table_exists) {
        $sql = "CREATE TABLE admin_users (
            admin_id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            full_name VARCHAR(100) NOT NULL,
            role ENUM('admin', 'super_admin', 'editor') DEFAULT 'admin',
            is_active TINYINT(1) DEFAULT 1,
            status ENUM('active', 'inactive') DEFAULT 'active',
            last_login DATETIME DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        $conn->exec($sql);
        $success[] = "Creada tabla 'admin_users'";
        
        // Create default admin user
        $admin_username = 'admin';
        $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
        $admin_email = 'admin@tienda.com';
        $admin_name = 'مدير النظام';
        $admin_role = 'super_admin';
        
        $stmt = $conn->prepare("INSERT INTO admin_users (username, password_hash, email, full_name, role, is_active, status) VALUES (:username, :password_hash, :email, :full_name, :role, 1, 'active')");
        $stmt->bindParam(':username', $admin_username);
        $stmt->bindParam(':password_hash', $admin_password);
        $stmt->bindParam(':email', $admin_email);
        $stmt->bindParam(':full_name', $admin_name);
        $stmt->bindParam(':role', $admin_role);
        $stmt->execute();
        
        $success[] = "Creado usuario administrador predeterminado (admin/admin123)";
    }
    
    // 3. Migrate data from admins to admin_users if both exist
    if ($admin_table_exists && $admin_users_table_exists) {
        $stmt = $conn->query("SELECT * FROM admins");
        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($admins as $admin) {
            // Check if user already exists in admin_users
            $check = $conn->prepare("SELECT COUNT(*) FROM admin_users WHERE email = :email");
            $check->bindParam(':email', $admin['email']);
            $check->execute();
            
            if ($check->fetchColumn() == 0) {
                // Insert admin into admin_users
                $username = explode('@', $admin['email'])[0]; // Use first part of email as username
                $role = $admin['is_super_admin'] == 1 ? 'super_admin' : 'admin';
                $status = $admin['is_active'] == 1 ? 'active' : 'inactive';
                
                $stmt = $conn->prepare("INSERT INTO admin_users (username, password_hash, email, full_name, role, is_active, status, last_login, created_at) VALUES (:username, :password_hash, :email, :full_name, :role, :is_active, :status, :last_login, :created_at)");
                
                $stmt->bindValue(':username', $username);
                $stmt->bindValue(':password_hash', $admin['password']);
                $stmt->bindValue(':email', $admin['email']);
                $stmt->bindValue(':full_name', $admin['name']);
                $stmt->bindValue(':role', $role);
                $stmt->bindValue(':is_active', $admin['is_active']);
                $stmt->bindValue(':status', $status);
                $stmt->bindValue(':last_login', $admin['last_login']);
                $stmt->bindValue(':created_at', $admin['created_at']);
                $stmt->execute();
                
                $success[] = "Migrado usuario {$admin['name']} ({$admin['email']}) de 'admins' a 'admin_users'";
            }
        }
    }
    
    // 4. Update admin_users.php to use the correct table
    $file_path = __DIR__ . '/admin_users.php';
    if (file_exists($file_path)) {
        $content = file_get_contents($file_path);
        $updated_content = str_replace(
            ["admins", "admin_id", "is_super_admin"],
            ["admin_users", "admin_id", "role = 'super_admin'"],
            $content
        );
        
        // Modify the query to use status
        $updated_content = str_replace(
            "WHERE is_active = 1",
            "WHERE status = 'active'",
            $updated_content
        );
        
        file_put_contents($file_path, $updated_content);
        $success[] = "Actualizado archivo admin_users.php para usar la tabla correcta";
    } else {
        $errors[] = "No se encontró el archivo admin_users.php";
    }
    
} catch (PDOException $e) {
    $errors[] = "Error de base de datos: " . $e->getMessage();
}

// Output results
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إصلاح جداول المديرين</title>
    <link rel="stylesheet" href="https://cdn.rtlcss.com/bootstrap/v4.5.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background-color: #f4f6f9;
            padding: 20px;
        }
        .container {
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
        h1 {
            color: #3f51b5;
            margin-bottom: 20px;
        }
        .alert {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-tools"></i> إصلاح جداول المديرين</h1>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <h4><i class="fas fa-exclamation-triangle"></i> حدثت أخطاء:</h4>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <h4><i class="fas fa-check-circle"></i> تم التنفيذ بنجاح:</h4>
                <ul>
                    <?php foreach ($success as $message): ?>
                        <li><?php echo $message; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="mt-4">
            <a href="index.php" class="btn btn-primary"><i class="fas fa-home"></i> العودة إلى لوحة التحكم</a>
            <a href="admin_users.php" class="btn btn-info ml-2"><i class="fas fa-users-cog"></i> إدارة المديرين</a>
        </div>
    </div>
</body>
</html>
