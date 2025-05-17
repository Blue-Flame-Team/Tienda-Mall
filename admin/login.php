<?php
/**
 * Tienda Mall E-commerce Platform
 * Admin Login Page
 */

// Start session if not already started
session_start();

// Include necessary files
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Initialize variables
$error = '';
$email = '';

// Check if admin is already logged in
if (isset($_SESSION['admin_id']) && isset($_SESSION['admin_email'])) {
    // Redirect to admin dashboard
    header('Location: index.php');
    exit;
}

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize input
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    
    // Validate input
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password';
    } else {
        // Initialize database
        $db = Database::getInstance();
        
        // Intentar autenticar en ambas tablas de administradores (admins y admin_users)
        try {
            // Primero intentar con la nueva tabla 'admins'
            $stmt = $db->query(
                "SELECT * FROM admins WHERE email = ? AND is_active = 1 LIMIT 1",
                [$email]
            );
            
            // Si no hay resultados, intentar con la tabla 'admin_users' antigua
            if ($stmt->rowCount() === 0) {
                $stmt = $db->query(
                    "SELECT * FROM admin_users WHERE email = ? AND status = 'active' LIMIT 1",
                    [$email]
                );
            }
        } catch (PDOException $e) {
            // Si hay error con la primera tabla, intentar con la segunda
            try {
                $stmt = $db->query(
                    "SELECT * FROM admin_users WHERE email = ? AND status = 'active' LIMIT 1",
                    [$email]
                );
            } catch (PDOException $e2) {
                $error = 'Database query failed. Please try again later.';
                // Log error for debugging
                error_log('Admin login error: ' . $e2->getMessage());
            }
        }
        
        $admin = $stmt->fetch();
        
        if ($admin) {
            // Determinar el campo de contraseña según la tabla
            $password_field = isset($admin['password_hash']) ? 'password_hash' : 'password';
            
            // Verificar la contraseña
            if (password_verify($password, $admin[$password_field])) {
                // Set admin session
                $_SESSION['admin_id'] = $admin['admin_id'];
                $_SESSION['admin_email'] = $admin['email'];
                
                // Manejar diferente formato de nombre entre tablas
                if (isset($admin['first_name'])) {
                    $_SESSION['admin_name'] = $admin['first_name'] . ' ' . $admin['last_name'];
                } else if (isset($admin['name'])) {
                    $_SESSION['admin_name'] = $admin['name'];
                } else {
                    $_SESSION['admin_name'] = 'Administrator';
                }
                
                // Manejar diferente campo de rol entre tablas
                $_SESSION['admin_role'] = isset($admin['role']) ? $admin['role'] : 
                                        (isset($admin['is_super_admin']) && $admin['is_super_admin'] ? 'super_admin' : 'admin');
                
                // Determinar la tabla correcta para la actualización
                $table = isset($admin['is_active']) ? 'admins' : 'admin_users';
                
                // Update last login time
                try {
                    $db->query(
                        "UPDATE $table SET last_login = NOW() WHERE admin_id = ?",
                        [$admin['admin_id']]
                    );
                } catch (PDOException $e) {
                    // Si falla la actualización, solo registrarlo pero no mostrar error al usuario
                    error_log('Error updating last login: ' . $e->getMessage());
                }
                
                // Redirect to admin dashboard
                header('Location: index.php');
                exit;
            }
        } else {
            $error = 'Invalid email or password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Tienda Mall</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        
        .login-container {
            width: 400px;
            padding: 40px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .store-logo {
            font-size: 24px;
            font-weight: bold;
            color: #23A6F0;
            margin-bottom: 10px;
        }
        
        h1 {
            font-size: 28px;
            color: #252B42;
            margin-bottom: 5px;
        }
        
        .login-subtitle {
            color: #737373;
            font-size: 14px;
        }
        
        .login-form {
            margin-top: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #252B42;
            font-weight: 500;
        }
        
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #E6E6E6;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        input[type="email"]:focus,
        input[type="password"]:focus {
            border-color: #23A6F0;
            outline: none;
        }
        
        .btn-login {
            display: block;
            width: 100%;
            padding: 14px;
            background-color: #23A6F0;
            color: #fff;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn-login:hover {
            background-color: #1a93d6;
        }
        
        .error-message {
            background-color: #FFF0F0;
            color: #E74040;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
            border-left: 4px solid #E74040;
        }
        
        .back-to-site {
            text-align: center;
            margin-top: 30px;
            font-size: 14px;
        }
        
        .back-to-site a {
            color: #23A6F0;
            text-decoration: none;
        }
        
        .back-to-site a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="store-logo">Tienda Mall</div>
            <h1>Admin Login</h1>
            <p class="login-subtitle">Enter your credentials to access the admin panel</p>
        </div>
        
        <?php if (!empty($error)): ?>
        <div class="error-message">
            <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <form action="login.php" method="post" class="login-form">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn-login">Sign In</button>
        </form>
        
        <div class="back-to-site">
            <a href="../index.php"><i class="fas fa-arrow-left"></i> Back to Website</a>
        </div>
    </div>
</body>
</html>
