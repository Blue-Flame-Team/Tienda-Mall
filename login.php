<?php
/**
 * Tienda Mall E-commerce Platform
 * Login Page
 */

// Include necessary files
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/db.php';
require_once 'includes/User.php';

// Initialize variables
$error = '';
$email = '';
$cartCount = 0;
$isLoggedIn = false;

// Check if user is already logged in
if (isLoggedIn()) {
    // Redirect to home page or account page
    redirect('index.php');
}

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate form data
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        // Initialize User object
        $userObj = new User();
        
        // Attempt login
        $user = $userObj->login($email, $password);
        
        if ($user === 'inactive') {
            $error = 'Your account has been deactivated. Please contact customer support.';
        } elseif ($user) {
            // Set session
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            
            // Get redirect URL if any
            $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'index.php';
            
            // Redirect to previous page or home
            redirect($redirect);
        } else {
            $error = 'Invalid email or password. Please try again.';
        }
    }
}

// Get cart information if session exists
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += $item['quantity'];
    }
}

// Include the header
include 'templates/header.php';
?>

<!-- Main Content -->
<main>
    <section class="auth-section">
        <div class="container">
            <div class="auth-container">
                <h1>Login to Your Account</h1>
                
                <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <?php echo $error; ?>
                </div>
                <?php endif; ?>
                
                <form action="login.php<?php echo isset($_GET['redirect']) ? '?redirect=' . htmlspecialchars($_GET['redirect']) : ''; ?>" method="post" class="auth-form">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    
                    <div class="form-group remember-forgot">
                        <div class="remember-me">
                            <input type="checkbox" id="remember" name="remember">
                            <label for="remember">Remember me</label>
                        </div>
                        <div class="forgot-password">
                            <a href="forgot-password.php">Forgot password?</a>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-block">Login</button>
                    </div>
                </form>
                
                <div class="auth-links">
                    <p>Don't have an account? <a href="register.php<?php echo isset($_GET['redirect']) ? '?redirect=' . htmlspecialchars($_GET['redirect']) : ''; ?>">Register</a></p>
                </div>
            </div>
        </div>
    </section>
</main>

<?php
// Include the footer
include 'templates/footer.php';
?>
