<?php
/**
 * Tienda Mall E-commerce Platform
 * Registration Page
 */

// Include necessary files
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/db.php';
require_once 'includes/User.php';

// Initialize variables
$error = '';
$success = '';
$formData = [
    'email' => '',
    'first_name' => '',
    'last_name' => '',
    'phone' => ''
];
$cartCount = 0;
$isLoggedIn = false;

// Check if user is already logged in
if (isLoggedIn()) {
    // Redirect to home page or account page
    redirect('index.php');
}

// Process registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $formData = [
        'email' => sanitize($_POST['email'] ?? ''),
        'first_name' => sanitize($_POST['first_name'] ?? ''),
        'last_name' => sanitize($_POST['last_name'] ?? ''),
        'phone' => sanitize($_POST['phone'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? ''
    ];
    
    // Validate form data
    if (empty($formData['email']) || empty($formData['first_name']) || 
        empty($formData['last_name']) || empty($formData['password'])) {
        $error = 'Please fill in all required fields.';
    } elseif (!validateEmail($formData['email'])) {
        $error = 'Please enter a valid email address.';
    } elseif ($formData['password'] !== $formData['confirm_password']) {
        $error = 'Passwords do not match.';
    } elseif (strlen($formData['password']) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } else {
        // Initialize User object
        $userObj = new User();
        
        // Attempt registration
        $userId = $userObj->register($formData);
        
        if ($userId) {
            $success = 'Registration successful! You can now log in.';
            
            // Clear form data after successful registration
            $formData = [
                'email' => '',
                'first_name' => '',
                'last_name' => '',
                'phone' => ''
            ];
        } else {
            $error = 'Registration failed. Email may already be in use.';
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
                <h1>Create an Account</h1>
                
                <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <?php echo $error; ?>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <?php echo $success; ?>
                    <p>You can <a href="login.php">login here</a>.</p>
                </div>
                <?php else: ?>
                <form action="register.php" method="post" class="auth-form">
                    <div class="form-group">
                        <label for="email">Email <span class="required">*</span></label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($formData['email']); ?>" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">First Name <span class="required">*</span></label>
                            <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($formData['first_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="last_name">Last Name <span class="required">*</span></label>
                            <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($formData['last_name']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($formData['phone']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password <span class="required">*</span></label>
                        <input type="password" id="password" name="password" required minlength="8">
                        <small>Password must be at least 8 characters long.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password <span class="required">*</span></label>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
                    </div>
                    
                    <div class="form-group terms-condition">
                        <input type="checkbox" id="terms" name="terms" required>
                        <label for="terms">I agree to the <a href="terms.php">Terms and Conditions</a> and <a href="privacy.php">Privacy Policy</a></label>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-block">Register</button>
                    </div>
                </form>
                <?php endif; ?>
                
                <div class="auth-links">
                    <p>Already have an account? <a href="login.php<?php echo isset($_GET['redirect']) ? '?redirect=' . htmlspecialchars($_GET['redirect']) : ''; ?>">Login</a></p>
                </div>
            </div>
        </div>
    </section>
</main>

<?php
// Include the footer
include 'templates/footer.php';
?>
