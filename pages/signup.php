<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear any problematic session data to avoid automatic redirects
unset($_SESSION['user']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> Exclusive </title>
    <link rel="stylesheet" href="../styles/signup.css">
    <link rel="stylesheet" href="../styles/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
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
            <nav>
                <a href="../index.php">Home</a>
                <a href="contact.php">Contact</a>
                <a href="#">About</a>
                <a href="login.php">Log In</a>
            </nav>
            <div class="search-cart">
                <div class="search-box">
                    <input type="text" placeholder="What are you looking for?">
                    <span class="search-icon"><img src="../assets/icons/Saearch.png" alt="Search" class="icon-img"></span>
                </div>
                <a href="wishlist.php" class="icon-link"><img src="../assets/icons/Wishlist.png" alt="Wishlist" class="icon-img"></a>
                <a href="cart.php" class="icon-link"><img src="../assets/icons/Cart1.png" alt="Cart" class="icon-img"></a>
                <div class="profile-menu-container" style="display:none; position:relative;">
                  <div class="profile-icon" id="profileIcon" style="width:40px;height:40px;border-radius:50%;background:#DB4444;display:flex;align-items:center;justify-content:center;cursor:pointer;">
                    <svg width="22" height="22" fill="#fff" viewBox="0 0 24 24"><path d="M12 12c2.7 0 5-2.3 5-5s-2.3-5-5-5-5 2.3-5 5 2.3 5 5 5zm0 2c-3.3 0-10 1.7-10 5v3h20v-3c0-3.3-6.7-5-10-5z"/></svg>
                  </div>
                  <div class="profile-dropdown" id="profileDropdown" style="display:none;position:absolute;top:48px;right:0;background:linear-gradient(135deg,#444 60%,#b47cff 100%);border-radius:16px;box-shadow:0 2px 16px rgba(0,0,0,0.18);padding:18px 0 10px 0;min-width:220px;z-index:100;">
                    <a href="account.php" class="profile-dropdown-item" style="display:flex;align-items:center;padding:10px 22px;color:#fff;text-decoration:none;font-size:1.1rem;gap:14px;"><span style="font-size:1.4em;">👤</span>Manage My Account</a>
                    <a href="#" class="profile-dropdown-item" style="display:flex;align-items:center;padding:10px 22px;color:#fff;text-decoration:none;font-size:1.1rem;gap:14px;"><span style="font-size:1.4em;">📦</span>My Order</a>
                    <a href="#" class="profile-dropdown-item" style="display:flex;align-items:center;padding:10px 22px;color:#fff;text-decoration:none;font-size:1.1rem;gap:14px;"><span style="font-size:1.4em;">❌</span>My Cancellations</a>
                    <a href="#" class="profile-dropdown-item" style="display:flex;align-items:center;padding:10px 22px;color:#fff;text-decoration:none;font-size:1.1rem;gap:14px;"><span style="font-size:1.4em;">⭐</span>My Reviews</a>
                    <a href="#" id="logoutBtn" class="profile-dropdown-item" style="display:flex;align-items:center;padding:10px 22px;color:#fff;text-decoration:none;font-size:1.1rem;gap:14px;"><span style="font-size:1.4em;">↩️</span>Logout</a>
                  </div>
                </div>
            </div>
        </div>
    </header>

    <main>
  <div class="signup-section">
    <div class="image-section-original">
      <img src="../assets/images/Side Image.png" alt="Sign up visual">
    </div>
    <div class="form-section">
      <h2>Create an account</h2>
                <p>Enter your details below</p>
                <form id="signup-form">
                    <input type="text" id="name" name="name" placeholder="Name" required>
                    <input type="email" id="email" name="email" placeholder="Email or Phone Number" required>
                    <input type="password" id="password" name="password" placeholder="Password" required>
                    <button type="submit" class="create-account">Create Account</button>
                    <button type="button" class="google-signup-btn">
  <span class="google-icon">
    <svg width="20" height="20" viewBox="0 0 48 48"><g><path fill="#4285F4" d="M43.611 20.083H42V20H24v8h11.303c-1.649 4.657-6.08 8-11.303 8-6.627 0-12-5.373-12-12s5.373-12 12-12c2.657 0 5.104.868 7.099 2.313l6.062-6.062C33.084 6.163 28.761 4 24 4 12.954 4 4 12.954 4 24s8.954 20 20 20c11.046 0 20-8.954 20-20 0-1.341-.138-2.651-.389-3.917z"/><path fill="#34A853" d="M6.306 14.691l6.571 4.819C14.655 16.108 19.004 13 24 13c2.657 0 5.104.868 7.099 2.313l6.062-6.062C33.084 6.163 28.761 4 24 4c-7.732 0-14.39 4.41-17.694 10.691z"/><path fill="#FBBC05" d="M24 44c4.522 0 8.664-1.477 11.895-4.014l-6.966-5.705C27.095 35.084 25.574 35.5 24 35.5c-5.193 0-9.621-3.336-11.276-7.98l-6.587 5.088C9.654 41.205 16.327 44 24 44z"/><path fill="#EA4335" d="M43.611 20.083H42V20H24v8h11.303c-.717 2.027-2.021 3.757-3.908 4.995l6.966 5.705C41.798 39.07 44 31.999 44 24c0-1.341-.138-2.651-.389-3.917z"/></g></svg>
  </span>
  <span>Sign up with Google</span>
</button>
                    <p>Already have account? <a href="login.php">Log in</a></p>
                    <div id="signup-message" style="margin-top:10px;"></div>
                </form>
            </div>
        </div>
    </main>


   
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


<script>
// Check login status and display profile menu if logged in
function checkLoginProfileMenu() {
  const isLoggedIn = localStorage.getItem('isLoggedIn') === 'true';
  const profileMenu = document.querySelector('.profile-menu-container');
  if (profileMenu) profileMenu.style.display = isLoggedIn ? 'block' : 'none';
}
checkLoginProfileMenu();

// Profile dropdown functionality
const profileIcon = document.getElementById('profileIcon');
const profileDropdown = document.getElementById('profileDropdown');
if (profileIcon && profileDropdown) {
  profileIcon.addEventListener('click', function(e) {
    e.stopPropagation();
    profileDropdown.style.display = (profileDropdown.style.display === 'block') ? 'none' : 'block';
  });
  document.body.addEventListener('click', function() {
    profileDropdown.style.display = 'none';
  });
  profileDropdown.addEventListener('click', function(e){e.stopPropagation();});
}

// Logout functionality
const logoutBtn = document.getElementById('logoutBtn');
if (logoutBtn) {
  logoutBtn.addEventListener('click', function(e) {
    e.preventDefault();
    localStorage.removeItem('isLoggedIn');
    localStorage.removeItem('userEmail');
    localStorage.removeItem('userName');
    localStorage.removeItem('userId');
    window.location.reload();
  });
}

// Signup form submission
document.getElementById('signup-form').addEventListener('submit', function(e) {
  e.preventDefault();
  
  // Get form values
  const name = document.getElementById('name').value.trim();
  const email = document.getElementById('email').value.trim();
  const password = document.getElementById('password').value;
  
  // Validate form data
  if (!name || !email || !password) {
    document.getElementById('signup-message').textContent = 'All fields are required';
    document.getElementById('signup-message').style.color = 'red';
    return;
  }
  
  // Create form data
  const formData = {
    name: name,
    email: email,
    password: password
  };
  
  // Show loading message
  document.getElementById('signup-message').textContent = 'Processing...';
  document.getElementById('signup-message').style.color = 'blue';
  
  // Send AJAX request to sign up
  fetch('../api/signup.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(formData)
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      // Show success message
      document.getElementById('signup-message').textContent = data.message;
      document.getElementById('signup-message').style.color = 'green';
      
      // Clear the form
      document.getElementById('signup-form').reset();
      
      // Redirect to login page after a short delay
      setTimeout(() => {
        window.location.href = 'login.php';
      }, 2000);
    } else {
      // Show error message
      document.getElementById('signup-message').textContent = data.message;
      document.getElementById('signup-message').style.color = 'red';
    }
  })
  .catch(error => {
    console.error('Error:', error);
    document.getElementById('signup-message').textContent = 'Connection error. Please try again.';
    document.getElementById('signup-message').style.color = 'red';
  });
});
</script>
</body>

</html>
