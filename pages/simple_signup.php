<?php
// Simple signup page with direct form submission and error reporting

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database configuration
$host = 'localhost';
$dbname = 'tienda_mall';
$username = 'root';
$password = ''; // Default WAMP password is empty

$message = "";
$message_type = "";

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Connect to database
        $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Get form data
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        
        // Validate data
        if (empty($name) || empty($email) || empty($password)) {
            $message = "كل الحقول مطلوبة";
            $message_type = "error";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "صيغة البريد الإلكتروني غير صحيحة";
            $message_type = "error";
        } else {
            // Check if email already exists
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                $message = "البريد الإلكتروني مسجل بالفعل";
                $message_type = "error";
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Show table structure to debug
                $columns = [];
                $columnsResult = $conn->query("SHOW COLUMNS FROM users");
                while ($column = $columnsResult->fetch(PDO::FETCH_ASSOC)) {
                    $columns[] = $column['Field'];
                }
                
                // Insert new user
                $sql = "INSERT INTO users (first_name, last_name, email, password_hash, created_at, is_active) VALUES (?, ?, ?, ?, NOW(), 1)";
                $stmt = $conn->prepare($sql);
                $result = $stmt->execute([$name, '', $email, $hashed_password]);
                
                if ($result) {
                    $message = "تم التسجيل بنجاح! يمكنك الآن <a href='login.php'>تسجيل الدخول</a>";
                    $message_type = "success";
                } else {
                    $message = "حدث خطأ أثناء التسجيل. حاول مرة أخرى.";
                    $message_type = "error";
                }
            }
        }
    } catch (PDOException $e) {
        $message = "خطأ في قاعدة البيانات: " . $e->getMessage();
        $message_type = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إنشاء حساب | Tienda</title>
    <link rel="stylesheet" href="../styles/signup.css">
    <link rel="stylesheet" href="../styles/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        .message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .error {
            background-color: #ffcccc;
            color: #cc0000;
        }
        .success {
            background-color: #ccffcc;
            color: #006600;
        }
        .debug-info {
            margin-top: 20px;
            padding: 15px;
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <main>
        <div class="signup-section">
            <div class="image-section-original">
                <img src="../assets/images/Side Image.png" alt="Sign up visual">
            </div>
            <div class="form-section">
                <h2>إنشاء حساب جديد</h2>
                <p>أدخل بياناتك أدناه</p>
                
                <?php if (!empty($message)): ?>
                    <div class="message <?php echo $message_type; ?>"><?php echo $message; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <input type="text" id="name" name="name" placeholder="الاسم" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
                    <input type="email" id="email" name="email" placeholder="البريد الإلكتروني" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                    <input type="password" id="password" name="password" placeholder="كلمة المرور" required>
                    <button type="submit" class="create-account">إنشاء حساب</button>
                </form>
                
                <p>لديك حساب بالفعل؟ <a href="login.php">تسجيل الدخول</a></p>
                
                <?php if (isset($columns)): ?>
                <div class="debug-info">
                    <h3>معلومات التشخيص</h3>
                    <p>أعمدة جدول المستخدمين:</p>
                    <pre><?php print_r($columns); ?></pre>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>
