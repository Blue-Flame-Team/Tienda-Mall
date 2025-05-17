<!-- Footer Start -->
<footer class="footer-container">
    <div class="container">
        <div class="footer-top">
            <div class="footer-col exclusive-col">
                <div class="footer-heading">Exclusive</div>
                <div class="footer-text">Subscribe</div>
                <div class="footer-text">Get 10% off your first order</div>
                <div class="footer-email-input">
                    <input type="email" placeholder="Enter your email">
                    <i class="fa fa-paper-plane"></i>
                </div>
            </div>
            <div class="footer-col">
                <div class="footer-heading">Support</div>
                <div class="footer-text">111 Bijoy sarani, Dhaka,<br> DH 1515, Bangladesh.</div>
                <div class="footer-text">exclusive@gmail.com</div>
                <div class="footer-text">+88015-88888-9999</div>
            </div>
            <div class="footer-col">
                <div class="footer-heading">Account</div>
                <a href="<?php echo $isLoggedIn ? 'pages/account.php' : 'login.php'; ?>" class="footer-link">My Account</a>
                <a href="login.php" class="footer-link">Login</a>
                <a href="cart.php" class="footer-link">Cart</a>
                <a href="pages/wishlist.php" class="footer-link">Wishlist</a>
                <a href="pages/shop.php" class="footer-link">Shop</a>
            </div>
            <div class="footer-col">
                <div class="footer-heading">Quick Link</div>
                <a href="pages/privacy.php" class="footer-link">Privacy Policy</a>
                <a href="pages/terms.php" class="footer-link">Terms Of Use</a>
                <a href="pages/faq.php" class="footer-link">FAQ</a>
                <a href="pages/contact.php" class="footer-link">Contact</a>
            </div>
            <div class="footer-col download-col">
                <div class="footer-heading">Download App</div>
                <div class="small-text">Save $3 with App New User Only</div>
                <div class="download-app-row">
                    <div class="qr-code-container">
                        <img src="assets/icons/Qrcode.png" alt="QR Code" class="qr-code">
                    </div>
                    <div class="app-images-container">
                        <a href="#" target="_blank"><img src="assets/icons/GooglePlay.png" alt="Google Play"></a>
                        <a href="#" target="_blank"><img src="assets/icons/AppStore.png" alt="App Store"></a>
                    </div>
                </div>
                <div class="social-icons-footer">
                    <a href="#" class="social-icon-link">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="#" class="social-icon-link">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="#" class="social-icon-link">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="#" class="social-icon-link">
                        <i class="fab fa-linkedin-in"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="copyright">
        <i class="fa fa-copyright"></i> Copyright Rimel 2022. All right reserved
    </div>
</footer>
<!-- Add Scripts -->
<script src="scripts/loader.js"></script>
<?php if (isset($extraJS) && is_array($extraJS)): ?>
    <?php foreach ($extraJS as $js): ?>
    <script src="<?php echo $js; ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>
<script src="scripts/main.js"></script>
<script src="scripts/mobile-nav.js"></script>
                </div>
                <div class="footer-payment">
                    <img src="assets/icons/Visa.png" alt="Visa">
                    <img src="assets/icons/Mastercard.png" alt="Mastercard">
                    <img src="assets/icons/Bkash.png" alt="Bkash">
                    <img src="assets/icons/Nagad.png" alt="Nagad">
                </div>
                <div class="footer-copyright">
                    <p>&copy; <?php echo date('Y'); ?> Tienda Mall. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- JavaScript -->
    <script>
        // Common JavaScript for all pages
        document.addEventListener('DOMContentLoaded', function() {
            // Handle user dropdown
            const userButton = document.querySelector('.user-button');
            if (userButton) {
                userButton.addEventListener('click', function() {
                    const dropdownContent = document.querySelector('.dropdown-content');
                    dropdownContent.classList.toggle('show');
                });
                
                // Close dropdown when clicking outside
                window.addEventListener('click', function(event) {
                    if (!event.target.matches('.user-button') && !event.target.closest('.user-button')) {
                        const dropdowns = document.querySelectorAll('.dropdown-content');
                        dropdowns.forEach(dropdown => {
                            if (dropdown.classList.contains('show')) {
                                dropdown.classList.remove('show');
                            }
                        });
                    }
                });
            }
        });
    </script>
</body>
</html>
