<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tienda - Modern Ecommerce</title>
    <?php
    // Get base URL for absolute paths
    $base_url = "http://" . $_SERVER['HTTP_HOST'] . "/Tienda/";
    ?>
    <link rel="stylesheet" href="<?php echo $base_url; ?>styles/style.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>styles/mobile-nav.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>styles/loader.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>styles/profile-dropdown.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>assets/css/toast.css">
    <script src="<?php echo $base_url; ?>scripts/cart.js"></script>
    <script src="<?php echo $base_url; ?>assets/js/cart-api.js" defer></script>
    <script src="<?php echo $base_url; ?>scripts/cart_fix.js" defer></script>
    <script src="<?php echo $base_url; ?>scripts/image_path_fix.js" defer></script>
    <script src="<?php echo $base_url; ?>scripts/loader.js" defer></script>
    <script src="<?php echo $base_url; ?>scripts/main.js" defer></script>
    <script src="<?php echo $base_url; ?>scripts/mobile-nav.js" defer></script>
    <script src="<?php echo $base_url; ?>scripts/tienda-cart.js" defer></script>
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
            <div class="logo">Tienda</div>
            <button class="mobile-menu-btn" aria-label="Open Menu"><i class="fa fa-bars"></i></button>
            <nav>
                <a href="index.php" class="active-link">Home</a>
                <a href="pages/contact.php">Contact</a>
                <a href="pages/about.php">About</a>
                <a href="<?php echo $isLoggedIn ? 'pages/account.php' : 'login.php'; ?>"><?php echo $isLoggedIn ? 'Account' : 'Login'; ?></a>
                <a href="<?php echo $isLoggedIn ? 'logout.php' : 'register.php'; ?>"><?php echo $isLoggedIn ? 'Logout' : 'Sign Up'; ?></a>
            </nav>
            <div class="search-cart">
                <div class="search-box">
                    <form action="search.php" method="get">
                        <input type="text" name="q" placeholder="What are you looking for?" required>
                        <button type="submit" class="search-icon"><img src="assets/icons/Saearch.png" alt="Search" class="icon-img"></button>
                    </form>
                </div>
                <a href="pages/wishlist.php" class="icon-link"><img src="assets/icons/Wishlist.png" alt="Wishlist" class="icon-img"></a>
                <a href="cart.php" class="icon-link" style="position:relative;">
                  <img src="assets/icons/Cart1.png" alt="Cart" class="icon-img">
                  <span class="nav-cart-after" style="position:absolute;top:-8px;right:-8px;background:#DB4444;color:#fff;font-size:0.9em;padding:2px 7px;border-radius:50%;<?php if ($cartCount == 0): ?>display:none;<?php endif; ?>z-index:2;"><?php echo $cartCount; ?></span>
                </a>
                <div class="profile-menu-container" style="<?php echo $isLoggedIn ? '' : 'display:none;'; ?> position:relative;">
                  <div class="profile-icon" id="profileIcon" style="width:40px;height:40px;border-radius:50%;background:#DB4444;display:flex;align-items:center;justify-content:center;cursor:pointer;">
                    <svg width="22" height="22" fill="#fff" viewBox="0 0 24 24"><path d="M12 12c2.7 0 5-2.3 5-5s-2.3-5-5-5-5 2.3-5 5 2.3 5 5 5zm0 2c-3.3 0-10 1.7-10 5v3h20v-3c0-3.3-6.7-5-10-5z"/></svg>
                  </div>
                  <div class="profile-dropdown" id="profileDropdown" style="display:none;position:absolute;top:48px;right:0;background:linear-gradient(135deg,#444 60%,#b47cff 100%);border-radius:16px;box-shadow:0 2px 16px rgba(0,0,0,0.18);padding:18px 0 10px 0;min-width:220px;z-index:100;">
                    <a href="pages/account.php" class="profile-dropdown-item" style="display:flex;align-items:center;padding:10px 22px;color:#fff;text-decoration:none;font-size:1.1rem;gap:14px;"><span style="font-size:1.4em;">👤</span>Manage My Account</a>
                    <a href="#" class="profile-dropdown-item" style="display:flex;align-items:center;padding:10px 22px;color:#fff;text-decoration:none;font-size:1.1rem;gap:14px;"><span style="font-size:1.4em;">📦</span>My Order</a>
                    <a href="#" class="profile-dropdown-item" style="display:flex;align-items:center;padding:10px 22px;color:#fff;text-decoration:none;font-size:1.1rem;gap:14px;"><span style="font-size:1.4em;">❌</span>My Cancellations</a>
                    <a href="#" class="profile-dropdown-item" style="display:flex;align-items:center;padding:10px 22px;color:#fff;text-decoration:none;font-size:1.1rem;gap:14px;"><span style="font-size:1.4em;">⭐</span>My Reviews</a>
                    <a href="logout.php" id="logoutBtn" class="profile-dropdown-item" style="display:flex;align-items:center;padding:10px 22px;color:#fff;text-decoration:none;font-size:1.1rem;gap:14px;"><span style="font-size:1.4em;">↩️</span>Logout</a>
                  </div>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Mobile Nav Overlay & Dropdown -->
    <div class="mobile-nav-overlay"></div>
    <div class="mobile-nav-dropdown">
      <button class="close-mobile-nav" aria-label="Close Menu"><i class="fa fa-times"></i></button>
      <nav>
        <a href="index.php">Home</a>
        <a href="pages/contact.php">Contact</a>
        <a href="pages/about.php">About</a>
        <a href="<?php echo $isLoggedIn ? 'pages/account.php' : 'login.php'; ?>"><?php echo $isLoggedIn ? 'Account' : 'Login'; ?></a>
        <a href="<?php echo $isLoggedIn ? 'logout.php' : 'register.php'; ?>"><?php echo $isLoggedIn ? 'Logout' : 'Sign Up'; ?></a>
      </nav>
      <div class="search-cart">
        <div class="search-box">
          <form action="search.php" method="get">
            <input type="text" name="q" placeholder="What are you looking for?" required>
            <button type="submit" class="search-icon"><img src="assets/icons/Saearch.png" alt="Search" class="icon-img"></button>
          </form>
        </div>
        <a href="pages/wishlist.php" class="icon-link"><img src="assets/icons/Wishlist.png" alt="Wishlist" class="icon-img"></a>
        <a href="cart.php" class="icon-link" style="position:relative;">
          <img src="assets/icons/Cart1.png" alt="Cart" class="icon-img">
          <span class="nav-cart-after" style="position:absolute;top:-8px;right:-8px;background:#DB4444;color:#fff;font-size:0.9em;padding:2px 7px;border-radius:50%;<?php if ($cartCount == 0): ?>display:none;<?php endif; ?>z-index:2;"><?php echo $cartCount; ?></span>
        </a>
      </div>
    </div>
