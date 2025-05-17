<?php
/**
 * Custom header for product pages
 * This includes absolute paths for all assets to ensure proper loading
 */

// Get current user info if available
if (!isset($isLoggedIn)) {
    $isLoggedIn = isLoggedIn();
}
if (!isset($userName) && $isLoggedIn) {
    $user = getCurrentUser();
    $userName = $user ? ($user['first_name'] . ' ' . $user['last_name']) : '';
}

// Base URL for assets if not already set
if (!isset($base_url)) {
    $base_url = "http://" . $_SERVER['HTTP_HOST'] . "/Tienda/";
}

// Page title
if (!isset($pageTitle)) {
    $pageTitle = "Tienda Mall - Modern E-commerce Platform";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="<?php echo $base_url; ?>styles/style.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>styles/mobile-nav.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base_url; ?>styles/loader.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>styles/profile-dropdown.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>assets/css/toast.css">
    <script src="<?php echo $base_url; ?>scripts/cart.js"></script>
    <?php if (isset($extraCSS) && is_array($extraCSS)): ?>
        <?php foreach ($extraCSS as $css): ?>
        <link rel="stylesheet" href="<?php echo $css; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
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
            <div class="logo"><a href="<?php echo $base_url; ?>">Tienda</a></div>
            <button class="mobile-menu-btn" aria-label="Open Menu"><i class="fa fa-bars"></i></button>
            <nav>
                <a href="<?php echo $base_url; ?>index.php">Home</a>
                <a href="<?php echo $base_url; ?>pages/contact.php">Contact</a>
                <a href="<?php echo $base_url; ?>pages/about.php">About</a>
                <a href="<?php echo $base_url . ($isLoggedIn ? 'pages/account.php' : 'pages/login.php'); ?>"><?php echo $isLoggedIn ? 'Account' : 'Login'; ?></a>
                <a href="<?php echo $base_url . ($isLoggedIn ? 'pages/logout.php' : 'pages/signup.php'); ?>"><?php echo $isLoggedIn ? 'Logout' : 'Sign Up'; ?></a>
            </nav>
            <div class="header-actions">
                <div class="search-icon">
                    <i class="fa fa-search"></i>
                </div>
                <div class="cart-icon">
                    <a href="<?php echo $base_url; ?>pages/cart.php">
                        <i class="fa fa-shopping-cart"></i>
                        <span class="cart-count">0</span>
                    </a>
                </div>
                <?php if ($isLoggedIn): ?>
                <div class="profile-dropdown">
                    <div class="profile-toggle">
                        <i class="fa fa-user"></i>
                        <span class="user-name"><?php echo htmlspecialchars($userName); ?></span>
                    </div>
                    <div class="dropdown-menu">
                        <a href="<?php echo $base_url; ?>pages/account.php">My Account</a>
                        <a href="<?php echo $base_url; ?>pages/orders.php">My Orders</a>
                        <a href="<?php echo $base_url; ?>pages/settings.php">Settings</a>
                        <a href="<?php echo $base_url; ?>pages/logout.php">Logout</a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </header>
    
    <!-- Mobile Navigation -->
    <div class="mobile-nav-dropdown">
        <button class="close-mobile-nav" aria-label="Close Menu"><i class="fa fa-times"></i></button>
        <nav>
            <a href="<?php echo $base_url; ?>index.php">Home</a>
            <a href="<?php echo $base_url; ?>pages/contact.php">Contact</a>
            <a href="<?php echo $base_url; ?>pages/about.php">About</a>
            <a href="<?php echo $base_url . ($isLoggedIn ? 'pages/account.php' : 'pages/login.php'); ?>"><?php echo $isLoggedIn ? 'Account' : 'Login'; ?></a>
            <a href="<?php echo $base_url . ($isLoggedIn ? 'pages/logout.php' : 'pages/signup.php'); ?>"><?php echo $isLoggedIn ? 'Logout' : 'Sign Up'; ?></a>
        </nav>
    </div>
