<?php
/**
 * Página de perfil de usuario
 * Muestra información del usuario y opciones de cuenta
 */

// Cargar el bootstrap del sistema que inicializa todas las dependencias
require_once '../includes/bootstrap.php';

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['user'])) {
    // Si no ha iniciado sesión, redirigir a la página de login
    header('Location: login.php');
    exit;
}

// Obtener datos del usuario
$user = $_SESSION['user'];
$userId = $user['user_id'];
$userName = $user['first_name'] . ' ' . $user['last_name'];
if (trim($userName) === '') {
    $userName = $user['email'];
}

// Determinar qué sección mostrar
$section = isset($_GET['section']) ? $_GET['section'] : 'profile';
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>My Account - Profile</title>
    <link
      href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="../styles/account.css" />
    <link rel="stylesheet" href="../styles/style.css" />
    <link rel="stylesheet" href="../styles/profile-dropdown.css" />
    <link rel="stylesheet" href="../styles/mobile-nav.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="../scripts/mobile-nav.js" defer></script>
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
              <a href="contact.php">Contact</a>
              <a href="about.php">About</a>
              <a href="account.php" class="active">My Account</a>
              <a href="logout.php">Logout</a>
          </nav>
          <div class="search-cart">
              <div class="search-box">
                  <input type="text" placeholder="What are you looking for?">
                  <span class="search-icon"><img src="../assets/icons/Saearch.png" alt="Search" class="icon-img"></span>
              </div>
              <a href="wishlist.php" class="icon-link"><img src="../assets/icons/Wishlist.png" alt="Wishlist" class="icon-img"></a>
              <a href="cart.php" class="icon-link"><img src="../assets/icons/Cart1.png" alt="Cart" class="icon-img"></a>
              <div class="profile-menu-container" style="position:relative;">
                <div class="profile-icon" id="profileIcon" style="width:40px;height:40px;border-radius:50%;background:#DB4444;display:flex;align-items:center;justify-content:center;cursor:pointer;">
                  <svg width="22" height="22" fill="#fff" viewBox="0 0 24 24"><path d="M12 12c2.7 0 5-2.3 5-5s-2.3-5-5-5-5 2.3-5 5 2.3 5 5 5zm0 2c-3.3 0-10 1.7-10 5v3h20v-3c0-3.3-6.7-5-10-5z"/></svg>
                </div>
                <div class="profile-dropdown" id="profileDropdown" style="display:none;">
                  <a href="account.php" class="profile-dropdown-item">
                    <img src="../assets/icons/user.png" alt="Account" />
                    Manage My Account
                  </a>
                  <a href="account.php?section=orders" class="profile-dropdown-item">
                    <img src="../assets/icons/icon-mallbag.png" alt="Order" />
                    My Orders
                  </a>
                  <a href="wishlist.php" class="profile-dropdown-item">
                    <img src="../assets/icons/icon-cancel.png" alt="Wishlist" />
                    My Wishlist
                  </a>
                  <a href="account.php?section=reviews" class="profile-dropdown-item">
                    <img src="../assets/icons/Icon-Reviews.png" alt="Reviews" />
                    My Reviews
                  </a>
                  <a href="logout.php" class="profile-dropdown-item">
                    <img src="../assets/icons/Icon-logout.png" alt="Logout" />
                    Logout
                  </a>
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
      <a href="../index.php">Home</a>
      <a href="contact.php">Contact</a>
      <a href="about.php">About</a>
      <a href="account.php" class="active">My Account</a>
      <a href="logout.php">Logout</a>
    </nav>
    <div class="search-cart">
      <div class="search-box">
        <input type="text" placeholder="What are you looking for?">
        <span class="search-icon"><img src="../assets/icons/Saearch.png" alt="Search" class="icon-img"></span>
      </div>
      <a href="wishlist.php" class="icon-link"><img src="../assets/icons/Wishlist.png" alt="Wishlist" class="icon-img"></a>
      <a href="cart.php" class="icon-link" style="position:relative;">
        <img src="../assets/icons/Cart1.png" alt="Cart" class="icon-img">
        <span class="nav-cart-after" style="position:absolute;top:-8px;right:-8px;background:#DB4444;color:#fff;font-size:0.9em;padding:2px 7px;border-radius:50%;display:none;z-index:2;">0</span>
      </a>
    </div>
  </div>

  <section class="account-section">
    <div class="container account-container">
      <div class="account-sidebar">
        <h2 class="account-title">Welcome, <?php echo htmlspecialchars($userName); ?></h2>
        <ul class="account-menu">
          <li <?php if ($section === 'profile') echo 'class="active"'; ?>><a href="account.php?section=profile">Manage My Account</a></li>
          <li <?php if ($section === 'profile_info') echo 'class="active"'; ?>><a href="account.php?section=profile_info">Profile Information</a></li>
          <li <?php if ($section === 'addresses') echo 'class="active"'; ?>><a href="account.php?section=addresses">Manage Addresses</a></li>
          <li <?php if ($section === 'payment') echo 'class="active"'; ?>><a href="account.php?section=payment">Payment Methods</a></li>
          <li <?php if ($section === 'orders') echo 'class="active"'; ?>><a href="account.php?section=orders">My Orders</a></li>
          <li <?php if ($section === 'reviews') echo 'class="active"'; ?>><a href="account.php?section=reviews">My Reviews</a></li>
          <li <?php if ($section === 'wishlist') echo 'class="active"'; ?>><a href="wishlist.php">My Wishlist</a></li>
          <li><a href="logout.php">Log Out</a></li>
        </ul>
      </div>
      
      <div class="account-content">
        <?php if ($section === 'profile' || $section === 'profile_info'): ?>
        <!-- Profile Information Section -->
        <div class="content-header">
          <h2>Edit Your Profile</h2>
        </div>
        <form class="profile-form">
          <div class="form-row">
            <div class="form-group">
              <label for="firstname">First Name</label>
              <input 
                type="text" 
                id="firstname" 
                placeholder="Your First Name" 
                value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>"
              />
            </div>
            <div class="form-group">
              <label for="lastname">Last Name</label>
              <input 
                type="text" 
                id="lastname" 
                placeholder="Your Last Name" 
                value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>"
              />
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="email">Email</label>
              <input 
                type="email" 
                id="email" 
                placeholder="Your Email" 
                value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>"
              />
            </div>
            <div class="form-group">
              <label for="phone">Phone Number</label>
              <input 
                type="tel" 
                id="phone" 
                placeholder="Your Phone Number" 
                value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
              />
            </div>
          </div>
          <div class="form-row">
            <div class="form-group full-width">
              <label for="password">Password Changes</label>
              <input
                type="password"
                id="current_password"
                placeholder="Current Password"
              />
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <input
                type="password"
                id="new_password"
                placeholder="New Password"
              />
            </div>
            <div class="form-group">
              <input
                type="password"
                id="confirm_password"
                placeholder="Confirm New Password"
              />
            </div>
          </div>
          <div class="form-buttons">
            <button type="button" class="btn-cancel">Cancel</button>
            <button type="submit" class="btn-save">Save Changes</button>
          </div>
        </form>
        <?php elseif ($section === 'addresses'): ?>
        <!-- Address Management Section -->
        <div class="content-header">
          <h2>Shipping Addresses</h2>
          <button class="btn-add">Add New Address</button>
        </div>
        <div class="addresses-list">
          <?php
          try {
              // Verificar si la tabla existe
              $tableExists = false;
              try {
                  $checkTable = $conn->query("SELECT 1 FROM shipping_addresses LIMIT 1");
                  $tableExists = true;
              } catch (PDOException $tableError) {
                  // La tabla no existe
              }
              
              if ($tableExists) {
                  $stmt = $conn->prepare("SELECT * FROM shipping_addresses WHERE user_id = :user_id");
                  $stmt->bindParam(':user_id', $userId);
                  $stmt->execute();
                  
                  if ($stmt->rowCount() > 0) {
                      while ($address = $stmt->fetch(PDO::FETCH_ASSOC)) {
                          echo '<div class="address-card">';
                          echo '<div class="address-header">';
                          echo '<h3>' . htmlspecialchars($address['address_name']) . '</h3>';
                          if ($address['is_default']) {
                              echo '<span class="default-badge">Default</span>';
                          }
                          echo '</div>';
                          echo '<p>' . htmlspecialchars($address['full_name']) . '</p>';
                          echo '<p>' . htmlspecialchars($address['address_line_1']) . '</p>';
                          if (!empty($address['address_line_2'])) {
                              echo '<p>' . htmlspecialchars($address['address_line_2']) . '</p>';
                          }
                          echo '<p>' . htmlspecialchars($address['city']) . ', ' . htmlspecialchars($address['state']) . ' ' . htmlspecialchars($address['postal_code']) . '</p>';
                          echo '<p>' . htmlspecialchars($address['country']) . '</p>';
                          echo '<p>Phone: ' . htmlspecialchars($address['phone']) . '</p>';
                          echo '<div class="address-actions">';
                          echo '<button class="btn-edit" data-id="' . $address['address_id'] . '">Edit</button>';
                          echo '<button class="btn-delete" data-id="' . $address['address_id'] . '">Remove</button>';
                          if (!$address['is_default']) {
                              echo '<button class="btn-default" data-id="' . $address['address_id'] . '">Set as Default</button>';
                          }
                          echo '</div>';
                          echo '</div>';
                      }
                  } else {
                      echo '<div class="no-items">No shipping addresses found. Add your first address.</div>';
                  }
              } else {
                  // Mostrar mensaje amigable si la tabla no existe
                  echo '<div class="no-items">
                      <p>Address management feature is currently being set up.</p>
                      <p>Please check back later or contact support if you need to manage your addresses.</p>
                  </div>';
              }
          } catch (PDOException $e) {
              echo '<div class="error-message">Service temporarily unavailable. Please try again later.</div>';
          }
          ?>
        </div>
        <?php elseif ($section === 'orders'): ?>
        <!-- Orders Section -->
        <div class="content-header">
          <h2>My Orders</h2>
        </div>
        <div class="orders-list">
          <?php
          try {
              // Verificar si la tabla existe
              $tableExists = false;
              try {
                  $checkTable = $conn->query("SELECT 1 FROM orders LIMIT 1");
                  $tableExists = true;
              } catch (PDOException $tableError) {
                  // La tabla no existe
              }
              
              if ($tableExists) {
                  $stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = :user_id ORDER BY created_at DESC");
                  $stmt->bindParam(':user_id', $userId);
                  $stmt->execute();
                  
                  if ($stmt->rowCount() > 0) {
                      echo '<table class="orders-table">';
                      echo '<thead><tr><th>Order #</th><th>Date</th><th>Status</th><th>Total</th><th>Actions</th></tr></thead>';
                      echo '<tbody>';
                      
                      while ($order = $stmt->fetch(PDO::FETCH_ASSOC)) {
                          echo '<tr>';
                          echo '<td>' . htmlspecialchars($order['order_number']) . '</td>';
                          echo '<td>' . date('d M Y', strtotime($order['created_at'])) . '</td>';
                          echo '<td><span class="status-' . strtolower($order['status']) . '">' . htmlspecialchars($order['status']) . '</span></td>';
                          echo '<td>$' . number_format($order['total_amount'], 2) . '</td>';
                          echo '<td><a href="order_details.php?id=' . $order['order_id'] . '" class="btn-view">View Order</a></td>';
                          echo '</tr>';
                      }
                      
                      echo '</tbody>';
                      echo '</table>';
                  } else {
                      echo '<div class="no-items">You haven\'t placed any orders yet.</div>';
                  }
              } else {
                  // Mostrar mensaje amigable si la tabla no existe
                  echo '<div class="no-items">
                      <p>Order history feature is currently being set up.</p>
                      <p>Please check back later to view your order history.</p>
                  </div>';
              }
          } catch (PDOException $e) {
              echo '<div class="error-message">Service temporarily unavailable. Please try again later.</div>';
          }
          ?>
        </div>
        <?php elseif ($section === 'reviews'): ?>
        <!-- Reviews Section -->
        <div class="content-header">
          <h2>My Reviews</h2>
        </div>
        <div class="reviews-list">
          <?php
          try {
              // Verificar si la tabla existe
              $tableExists = false;
              try {
                  $checkTable = $conn->query("SELECT 1 FROM reviews LIMIT 1");
                  $tableExists = true;
              } catch (PDOException $tableError) {
                  // La tabla no existe
              }
              
              if ($tableExists) {
                  $stmt = $conn->prepare("
                      SELECT r.*, p.name as product_name, p.thumbnail as product_image 
                      FROM reviews r 
                      JOIN products p ON r.product_id = p.product_id 
                      WHERE r.user_id = :user_id 
                      ORDER BY r.created_at DESC
                  ");
                  $stmt->bindParam(':user_id', $userId);
                  $stmt->execute();
                  
                  if ($stmt->rowCount() > 0) {
                      while ($review = $stmt->fetch(PDO::FETCH_ASSOC)) {
                          echo '<div class="review-item">';
                          echo '<div class="review-product">';
                          
                          $imageSrc = !empty($review['product_image']) ? '../' . $review['product_image'] : '../assets/images/product-placeholder.jpg';
                          echo '<img src="' . $imageSrc . '" alt="' . htmlspecialchars($review['product_name']) . '" />';
                          
                          echo '<div class="product-info">';
                          echo '<h3>' . htmlspecialchars($review['product_name']) . '</h3>';
                          echo '<div class="rating">';
                          
                          for ($i = 1; $i <= 5; $i++) {
                              if ($i <= $review['rating']) {
                                  echo '<i class="fas fa-star"></i>';
                              } else {
                                  echo '<i class="far fa-star"></i>';
                              }
                          }
                          
                          echo '</div>';
                          echo '<span class="review-date">' . date('d M Y', strtotime($review['created_at'])) . '</span>';
                          echo '</div>';
                          echo '</div>';
                          
                          echo '<div class="review-content">';
                          echo '<p>' . htmlspecialchars($review['review_text']) . '</p>';
                          echo '<div class="review-actions">';
                          echo '<button class="btn-edit-review" data-id="' . $review['review_id'] . '">Edit Review</button>';
                          echo '<button class="btn-delete-review" data-id="' . $review['review_id'] . '">Delete</button>';
                          echo '</div>';
                          echo '</div>';
                          echo '</div>';
                      }
                  } else {
                      echo '<div class="no-items">You haven\'t written any reviews yet.</div>';
                  }
              } else {
                  // Mostrar mensaje amigable si la tabla no existe
                  echo '<div class="no-items">
                      <p>Product reviews feature is currently being set up.</p>
                      <p>Please check back later to see and manage your product reviews.</p>
                  </div>';
              }
          } catch (PDOException $e) {
              echo '<div class="error-message">Service temporarily unavailable. Please try again later.</div>';
          }
          ?>
        </div>
        <?php elseif ($section === 'payment'): ?>
        <!-- Payment Methods Section -->
        <div class="content-header">
          <h2>Payment Methods</h2>
          <button class="btn-add">Add New Payment Method</button>
        </div>
        <div class="payment-methods">
          <?php
          try {
              // Primero verificar si la tabla existe
              $tableExists = false;
              try {
                  $checkTable = $conn->query("SELECT 1 FROM payment_methods LIMIT 1");
                  $tableExists = true;
              } catch (PDOException $tableError) {
                  // La tabla no existe
              }
              
              if ($tableExists) {
                  $stmt = $conn->prepare("SELECT * FROM payment_methods WHERE user_id = :user_id");
                  $stmt->bindParam(':user_id', $userId);
                  $stmt->execute();
                  
                  if ($stmt->rowCount() > 0) {
                      while ($method = $stmt->fetch(PDO::FETCH_ASSOC)) {
                          echo '<div class="payment-card">';
                          
                          $cardType = strtolower($method['card_type']);
                          echo '<div class="card-icon">';
                          if ($cardType === 'visa') {
                              echo '<i class="fab fa-cc-visa"></i>';
                          } elseif ($cardType === 'mastercard') {
                              echo '<i class="fab fa-cc-mastercard"></i>';
                          } elseif ($cardType === 'amex') {
                              echo '<i class="fab fa-cc-amex"></i>';
                          } else {
                              echo '<i class="fas fa-credit-card"></i>';
                          }
                          echo '</div>';
                          
                          echo '<div class="card-details">';
                          echo '<h3>' . htmlspecialchars($method['card_type']) . ' ending in ' . htmlspecialchars($method['last_four']) . '</h3>';
                          echo '<p>Expires: ' . htmlspecialchars($method['expiry_month'] . '/' . $method['expiry_year']) . '</p>';
                          if ($method['is_default']) {
                              echo '<span class="default-badge">Default</span>';
                          }
                          echo '</div>';
                          
                          echo '<div class="card-actions">';
                          echo '<button class="btn-edit" data-id="' . $method['payment_id'] . '">Edit</button>';
                          echo '<button class="btn-delete" data-id="' . $method['payment_id'] . '">Remove</button>';
                          
                          if (!$method['is_default']) {
                              echo '<button class="btn-default" data-id="' . $method['payment_id'] . '">Set as Default</button>';
                          }
                          
                          echo '</div>';
                          echo '</div>';
                      }
                  } else {
                      echo '<div class="no-items">No payment methods found. Add your first payment method.</div>';
                  }
              } else {
                  // Mostrar mensaje amigable si la tabla no existe
                  echo '<div class="no-items">
                      <p>Payment methods feature is currently being set up.</p>
                      <p>Please check back later or contact support if you need to add payment methods.</p>
                  </div>';
              }
          } catch (PDOException $e) {
              echo '<div class="error-message">Service temporarily unavailable. Please try again later.</div>';
          }
          ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="footer-main">
    <div class="container footer-container">
        <div class="footer-col">
            <div class="footer-logo">Tienda</div>
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
                <li><a href="account.php">My Account</a></li>
                <li><a href="login.php">Login</a> / <a href="signup.php">Register</a></li>
                <li><a href="cart.php">Cart</a></li>
                <li><a href="wishlist.php">Wishlist</a></li>
                <li><a href="../index.php">Shop</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <div class="footer-title">Quick Link</div>
            <ul class="footer-list">
                <li><a href="privacy.php">Privacy Policy</a></li>
                <li><a href="terms.php">Terms Of Use</a></li>
                <li><a href="faq.php">FAQ</a></li>
                <li><a href="contact.php">Contact</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <div class="footer-title">Download App</div>
            <div class="footer-desc">Save $3 with App New User Only</div>
            <div class="footer-apps">
                <div class="qr-code">
                    <img src="../assets/images/qrcode.png" alt="QR Code">
                </div>
                <div class="app-stores">
                    <img src="../assets/images/appstore.png" alt="App Store">
                    <img src="../assets/images/googleplay.png" alt="Google Play">
                </div>
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

  <script>
    // Profile Dropdown Toggle
    document.addEventListener('DOMContentLoaded', function() {
      const profileIcon = document.getElementById('profileIcon');
      const profileDropdown = document.getElementById('profileDropdown');
      
      if (profileIcon && profileDropdown) {
        profileIcon.addEventListener('click', function(e) {
          e.stopPropagation();
          profileDropdown.style.display = profileDropdown.style.display === 'none' ? 'block' : 'none';
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
          if (profileDropdown.style.display === 'block' && !profileDropdown.contains(e.target) && e.target !== profileIcon) {
            profileDropdown.style.display = 'none';
          }
        });
      }
      
      // Form Submission for profile changes
      const profileForm = document.querySelector('.profile-form');
      if (profileForm) {
        profileForm.addEventListener('submit', function(e) {
          e.preventDefault();
          
          // Basic validation
          const newPassword = document.getElementById('new_password').value;
          const confirmPassword = document.getElementById('confirm_password').value;
          
          if (newPassword && newPassword !== confirmPassword) {
            alert('New passwords do not match!');
            return;
          }
          
          // Here you would normally send an AJAX request to update the profile
          // For now, just show a success message
          alert('Profile updated successfully!');
        });
      }
    });
  </script>
</body>
</html>
