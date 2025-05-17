<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize variables for form
$name = $email = $phone = $message = '';
$success_message = $error_message = '';

// Database configuration
$host = 'localhost';
$dbname = 'tienda_mall';
$username = 'root';
$password = ''; // Default WAMP password is empty

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate inputs
    if (empty($_POST['name']) || empty($_POST['email']) || empty($_POST['phone']) || empty($_POST['message'])) {
        $error_message = 'جميع الحقول مطلوبة';
    } else {
        // Sanitize input data
        $name = htmlspecialchars(trim($_POST['name']));
        $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
        $phone = htmlspecialchars(trim($_POST['phone']));
        $message = htmlspecialchars(trim($_POST['message']));
        
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = 'يرجى إدخال بريد إلكتروني صالح';
        } else {
            try {
                // Check if the contact_messages table exists, if not create it
                try {
                    // Connect to database
                    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
                    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    // Check if contact_messages table exists
                    $result = $conn->query("SHOW TABLES LIKE 'contact_messages'");
                    $tableExists = $result->rowCount() > 0;
                    
                    if (!$tableExists) {
                        // Create the contact_messages table
                        $sql = "CREATE TABLE contact_messages (
                            id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                            name VARCHAR(100) NOT NULL,
                            email VARCHAR(100) NOT NULL,
                            phone VARCHAR(20) NOT NULL,
                            message TEXT NOT NULL,
                            is_read TINYINT(1) DEFAULT 0,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
                        
                        $conn->exec($sql);
                    }
                } catch (PDOException $e) {
                    $error_message = 'حدث خطأ في الاتصال بقاعدة البيانات: ' . $e->getMessage();
                }
                
                if (empty($error_message)) {
                    // Insert message into database
                    $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, phone, message) VALUES (:name, :email, :phone, :message)");
                    
                    $stmt->bindParam(':name', $name);
                    $stmt->bindParam(':email', $email);
                    $stmt->bindParam(':phone', $phone);
                    $stmt->bindParam(':message', $message);
                    
                    $stmt->execute();
                    
                    // Set success message
                    $success_message = 'تم إرسال رسالتك بنجاح! سنتواصل معك قريبًا.';
                    
                    // Clear form data after successful submission
                    $name = $email = $phone = $message = '';
                }
            } catch (PDOException $e) {
                $error_message = 'حدث خطأ أثناء إرسال رسالتك. يرجى المحاولة مرة أخرى.';
                // For debugging:
                // $error_message .= ' Error: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../styles/contact.css">
    <link rel="stylesheet" href="../styles/style.css">
    <link rel="stylesheet" href="../styles/mobile-nav.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <title>اتصل بنا</title>
    <script src="../scripts/mobile-nav.js" defer></script>
    <style>
        .message-alert {
            padding: 15px;
            margin: 10px 0 20px 0;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
            position: relative;
            animation: fadeIn 0.5s ease-in-out;
        }
        .success-message {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .close-message {
            position: absolute;
            right: 10px;
            top: 10px;
            cursor: pointer;
            color: inherit;
            opacity: 0.7;
        }
        .close-message:hover {
            opacity: 1;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .sendbut {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .sendbut:hover {
            background-color: #c13e3e;
            transform: translateY(-2px);
        }
    </style>
    <script>
        // Script to close alert messages
        document.addEventListener('DOMContentLoaded', function() {
            const closeButtons = document.querySelectorAll('.close-message');
            closeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    this.parentElement.style.display = 'none';
                });
            });
        });
    </script>
</head>

<body>

     <!-- Top Bar -->
     <div class="top-bar">
        <div class="container top-bar-flex">
            <span class="top-bar-message">Summer Sale For All Swim Suits And Free Express Delivery - OFF 50%! <a href="#">ShopNow</a></span>
            <div class="top-bar-right">
                <span>English</span>
                <i class="fa fa-chevron-down"></i>
            </div>
        </div>
    </div>
    <!-- Header / Navbar -->
    <header>
        <div class="container nav-container">
            <div class="logo">Tienda</div>
            <button class="mobile-menu-btn" aria-label="Open Menu"><i class="fa fa-bars"></i></button>
            <nav>
                <a href="../index.php">Home</a>
                <a href="./contact.php" class="active">Contact</a>
                <a href="./about.php">About</a>
                <a href="./signup.php">Sign Up</a>
                <a href="./login.php">Login</a>
            </nav>
            <div class="search-cart">
                <div class="search-box">
                    <input type="text" placeholder="What are you looking for?">
                    <span class="search-icon"><img src="../assets/icons/Saearch.png" alt="Search" class="icon-img"></span>
                </div>
                <a href="./wishlist.php" class="icon-link"><img src="../assets/icons/Wishlist.png" alt="Wishlist" class="icon-img"></a>
                <a href="./cart.php" class="icon-link"><img src="../assets/icons/Cart1.png" alt="Cart" class="icon-img"></a>
            </div>
        </div>
    </header>

    <!-- Mobile Nav Overlay & Dropdown -->
    <div class="mobile-nav-overlay"></div>
    <div class="mobile-nav-dropdown">
      <button class="close-mobile-nav" aria-label="Close Menu"><i class="fa fa-times"></i></button>
      <nav>
        <a href="../index.php">Home</a>
        <a href="./contact.php" class="active">Contact</a>
        <a href="./about.php">About</a>
        <a href="./account.php">Account</a>
        <a href="./signup.php">Sign Up</a>
        <a href="./login.php">Login</a>
      </nav>
      <div class="search-cart">
        <div class="search-box">
          <input type="text" placeholder="What are you looking for?">
          <span class="search-icon"><img src="../assets/icons/Saearch.png" alt="Search" class="icon-img"></span>
        </div>
        <a href="./wishlist.php" class="icon-link"><img src="../assets/icons/Wishlist.png" alt="Wishlist" class="icon-img"></a>
        <a href="./cart.php" class="icon-link" style="position:relative;">
          <img src="../assets/icons/Cart1.png" alt="Cart" class="icon-img">
          <span class="nav-cart-after" style="position:absolute;top:-8px;right:-8px;background:#DB4444;color:#fff;font-size:0.9em;padding:2px 7px;border-radius:50%;display:none;z-index:2;">0</span>
        </a>
      </div>
    </div>

    <div class="allpage">
        <!-- Display success or error messages -->
        <?php if (!empty($success_message)): ?>
        <div class="message-alert success-message">
            <?php echo $success_message; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
        <div class="message-alert error-message">
            <?php echo $error_message; ?>
        </div>
        <?php endif; ?>

        <div class="boxleft">
            <h3><i class="fa fa-phone" style="font-size:36px;color: red;"></i> اتصل بنا</h3>
            <p>نحن متاحون على مدار الساعة طوال أيام الأسبوع</p>
            <p>هاتف: +20123456789</p>
            <br>
            <br>
            <hr>

            <h3><i class="fa fa-envelope" style="font-size:36px;color: red;"></i>
                راسلنا</h3>
            <p>املأ النموذج وسنتواصل معك خلال 24 ساعة.</p>
            <p>البريد الإلكتروني: customer@gmail.com</p>
            <p>البريد الإلكتروني: support@gmail.com</p>
        </div>

        <div class="boxright">
            <form method="POST" action="">
                <input class="in" type="text" name="name" placeholder="الاسم *" required value="<?php echo htmlspecialchars($name); ?>">
                <input class="in" type="email" name="email" placeholder="البريد الإلكتروني *" required minlength="5" value="<?php echo htmlspecialchars($email); ?>">
                <input class="in" type="text" name="phone" placeholder="رقم الهاتف *" required value="<?php echo htmlspecialchars($phone); ?>">
                <br>
                <br>
                <br>
                <textarea class="txt" name="message" placeholder="رسالتك" required><?php echo htmlspecialchars($message); ?></textarea>
                <button class="sendbut" type="submit" name="submit_contact">إرسال الرسالة <i class="fa fa-paper-plane" style="margin-right: 5px;"></i></button>
            </form>
        </div>
    </div>

    <footer class="footer-main">
        <div class="container footer-container">
            <div class="footer-col">
                <div class="footer-logo">Exclusive</div>
                <div class="footer-title">Subscribe</div>
                <div class="footer-desc">Get 10% off your first order</div>
                <form class="subscribe-form">
                    <input type="email" placeholder="Enter your email">
                    <button type="submit"><i class="fa fa-paper-plane"></i></button>
                </form>
            </div>
            <div class="footer-col">
                <div class="footer-title">Support</div>
                <div class="footer-desc">111 Bijoy sarani, Dhaka, DH 1515, Bangladesh.</div>
                <div class="footer-desc">exclusive@gmail.com</div>
                <div class="footer-desc">+88015-88888-9999</div>
            </div>
            <div class="footer-col">
                <div class="footer-title">Account</div>
                <ul class="footer-list">
                    <li>My Account</li>
                    <li>Login / Register</li>
                    <li>Cart</li>
                    <li>Wishlist</li>
                    <li>Shop</li>
                </ul>
            </div>
            <div class="footer-col">
                <div class="footer-title">Quick Link</div>
                <ul class="footer-list">
                    <li>Privacy Policy</li>
                    <li>Terms Of Use</li>
                    <li>FAQ</li>
                    <li>Contact</li>
                </ul>
            </div>
            <div class="footer-col">
                <div class="footer-title">Download App</div>
                <div class="footer-desc">Save $3 with App New User Only</div>
                <div class="footer-apps">
                    <img class="footer-app-img" src="../assets/images/APP.png" alt="Download App QR and Badges">
                </div>
                <div class="footer-socials">
                    <i class="fab fa-facebook-f"></i>
                    <i class="fab fa-twitter"></i>
                    <i class="fab fa-instagram"></i>
                    <i class="fab fa-linkedin-in"></i>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <span>&copy; Copyright Blue Flame 2025. All right reserved</span>
        </div>
    </footer>
</body>

</html>
