<?php
// Database configuration
$host = 'localhost';
$dbname = 'tienda_mall';
$username = 'root';
$password = '';

// Initialize variables
$errors = [];
$success = [];

try {
    // Connect to database
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if contact_messages table exists
    $contact_table_exists = $conn->query("SHOW TABLES LIKE 'contact_messages'")->rowCount() > 0;
    
    if ($contact_table_exists) {
        // Check if status column exists
        $status_column_exists = $conn->query("SHOW COLUMNS FROM contact_messages LIKE 'status'")->rowCount() > 0;
        
        if (!$status_column_exists) {
            // Add status column to contact_messages table
            $conn->exec("ALTER TABLE contact_messages ADD COLUMN status ENUM('new', 'read', 'responded') DEFAULT 'new'");
            $success[] = "Agregada columna 'status' a la tabla contact_messages con valor predeterminado 'new'";
            
            // Update existing messages to be 'new'
            $conn->exec("UPDATE contact_messages SET status = 'new'");
            $success[] = "Actualizado el estado de todos los mensajes existentes a 'new'";
        } else {
            $success[] = "La columna 'status' ya existe en la tabla contact_messages";
        }
    } else {
        // Create contact_messages table with status column
        $sql = "CREATE TABLE contact_messages (
            id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            phone VARCHAR(20),
            message TEXT NOT NULL,
            status ENUM('new', 'read', 'responded') DEFAULT 'new',
            admin_response TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        $conn->exec($sql);
        $success[] = "Creada tabla 'contact_messages' con columna 'status'";
    }
    
} catch (PDOException $e) {
    $errors[] = "Error de base de datos: " . $e->getMessage();
}

// Fix view_messages.php file to handle null status
try {
    $view_messages_file = __DIR__ . '/view_messages.php';
    if (file_exists($view_messages_file)) {
        $content = file_get_contents($view_messages_file);
        
        // Replace WHERE status = with WHERE (status = or IFNULL(status, 'new') =
        $content = str_replace(
            "WHERE status = 'new'",
            "WHERE (status = 'new' OR status IS NULL)",
            $content
        );
        
        $content = str_replace(
            "WHERE status = 'read'",
            "WHERE status = 'read'",
            $content
        );
        
        $content = str_replace(
            "WHERE status = 'responded'",
            "WHERE status = 'responded'",
            $content
        );
        
        file_put_contents($view_messages_file, $content);
        $success[] = "Actualizado archivo view_messages.php para manejar estado nulo o faltante";
    }
} catch (Exception $e) {
    $errors[] = "Error actualizando archivo: " . $e->getMessage();
}

// Output results
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إصلاح جدول الرسائل</title>
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
        <h1><i class="fas fa-tools"></i> إصلاح جدول الرسائل</h1>
        
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
            <a href="view_messages.php" class="btn btn-info ml-2"><i class="fas fa-envelope"></i> عرض الرسائل</a>
        </div>
    </div>
</body>
</html>
