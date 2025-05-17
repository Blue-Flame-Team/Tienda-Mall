<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is already logged in
if (isset($_SESSION['user'])) {
    // If logged in, redirect to index
    header('Location: ../index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Tienda</title>
    <link rel="stylesheet" href="../styles/login.css">
    <link rel="stylesheet" href="../styles/style.css">
    <link rel="stylesheet" href="../styles/mobile-nav.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="../scripts/mobile-nav.js" defer></script>
    <style>
        .btn-guest {
            background-color: #f8f8f8;
            color: #333;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 10px 20px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            width: 100%;
            margin-top: 15px;
            transition: all 0.3s ease;
        }
        .btn-guest:hover {
            background-color: #eaeaea;
        }
        .guest-login {
            margin-top: 20px;
            text-align: center;
        }
        .or-divider {
            display: flex;
            align-items: center;
            margin: 15px 0;
            color: #777;
        }
        .or-divider::before, .or-divider::after {
            content: "";
            flex: 1;
            border-bottom: 1px solid #ddd;
        }
        .or-divider span {
            padding: 0 10px;
        }
    </style>
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
                <a href="signup.php">Sign Up</a>
            </nav>
            <div class="search-cart">
                <div class="search-box">
                    <input type="text" placeholder="What are you looking for?">
                    <span class="search-icon"><img src="../assets/icons/Saearch.png" alt="Search" class="icon-img"></span>
                </div>
                <a href="wishlist.php" class="icon-link"><img src="../assets/icons/Wishlist.png" alt="Wishlist" class="icon-img"></a>
                <a href="cart.php" class="icon-link"><img src="../assets/icons/Cart1.png" alt="Cart" class="icon-img"></a>
            </div>
        </div>
    </header>

    <!-- Mobile Nav Overlay & Dropdown -->
    <div class="mobile-nav-overlay"></div>
    <div class="mobile-nav-dropdown">
      <button class="close-mobile-nav" aria-label="Close Menu"><i class="fa fa-times"></i></button>
      <nav>
        <a href="../index.php">Home</a>
        <a href="./contact.php">Contact</a>
        <a href="./about.php">About</a>
        <a href="./account.php">Account</a>
        <a href="./signup.php">Sign Up</a>
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
    
    <!-- Main Login Content -->
    <main>
  <div class="login-container">
    <div class="login-image">
      <img src="../assets/images/Side Image.png" alt="Login Visual">
    </div>
    <div class="login-form-section">
      <form class="login-form" id="login-form" action="../api/login.php" method="post">
        <h2>Log in to Exclusive</h2>
        <p>Enter your details below</p>
        <input type="text" id="login-email" name="email" placeholder="Email or Phone Number" required>
        <input type="password" id="login-password" name="password" placeholder="Password" required>
        <div class="form-actions">
          <button class="btn-login" type="submit">Log In</button>
          <a class="forgot-link" href="#">Forget Password?</a>
        </div>
        <div class="guest-login">
          <button class="btn-guest" type="button" id="guest-login-btn">Continue as Guest</button>
          <p style="margin-top:8px;font-size:12px;color:#666;">No necesitas crear una cuenta para comprar</p>
        </div>
        <div id="login-error" style="color:red;margin-top:10px;"></div>
      </form>
    </div>
  </div>
</main>
    <!-- Footer -->
    
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
<script>
document.addEventListener('DOMContentLoaded', function() {
  const loginForm = document.getElementById('login-form');
  const emailInput = document.getElementById('login-email');
  const passwordInput = document.getElementById('login-password');
  const errorElement = document.getElementById('login-error');
  const guestLoginBtn = document.getElementById('guest-login-btn');
  
  // Iniciar sesión como invitado
  guestLoginBtn.addEventListener('click', function() {
    // Crear objeto para sesión de invitado
    const guestData = {
      is_guest: true
    };
    
    // Enviar la solicitud para iniciar sesión como invitado
    fetch('../api/guest_login.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(guestData)
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Redirigir al usuario según de dónde vino
        const urlParams = new URLSearchParams(window.location.search);
        const redirect = urlParams.get('redirect');
        
        if (redirect) {
          window.location.href = redirect;
        } else {
          // Si no hay redirección especificada, ir a la página principal
          window.location.href = '../index.php';
        }
      } else {
        errorElement.textContent = data.message || 'Error al iniciar sesión como invitado';
      }
    })
    .catch(error => {
      console.error('Error:', error);
      errorElement.textContent = 'Error de conexión. Por favor, inténtalo de nuevo.';
    });
  });
  
  // Manejar el envío del formulario normal de inicio de sesión
  loginForm.addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Validate form
    const email = emailInput.value.trim();
    const password = passwordInput.value.trim();
    
    if (!email || !password) {
      errorElement.textContent = 'Please enter both email and password';
      return;
    }
  
  // Create form data
  const formData = {
    email: email,
    password: password
  };
  
  // Send AJAX request (Corregido el nombre del archivo)
  fetch('../api/login.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(formData)
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      // Save login data in localStorage for front-end use
      localStorage.setItem('isLoggedIn', 'true');
      localStorage.setItem('userEmail', email);
      localStorage.setItem('userName', data.user.name);
      localStorage.setItem('userId', data.user.id);
      
      // Redirect to homepage
      window.location.href = '../index.php';
    } else {
      document.getElementById('login-error').textContent = data.message || 'Login failed';
    }
  })
  .catch(error => {
    console.error('Error:', error);
    document.getElementById('login-error').textContent = 'Connection error. Please try again.';
  });
  });
});
</script>
</body>
</html>
