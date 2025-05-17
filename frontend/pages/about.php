<?php
/**
 * Tienda Mall E-commerce Platform
 * About Us Page
 */

// Include necessary files
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Initialize variables
$cartCount = 0;
$isLoggedIn = isLoggedIn();
$userName = '';

// Get cart information if session exists
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += $item['quantity'];
    }
}

// Check if user is logged in
if ($isLoggedIn) {
    $userName = $_SESSION['user_name'] ?? '';
}

// Extra CSS files to include
$extraCSS = ['../styles/about.css'];

// Include the header with proper path adjustments
include '../templates/header.php';
?>

<!-- About Us Content -->
<main class="about-main">
    <div class="container">
        <!-- Breadcrumb -->
        <div class="breadcrumb-container">
            <div class="breadcrumb">
                <a href="../index.php">Home</a> / <span>About Us</span>
            </div>
        </div>
        
        <!-- Hero Section -->
        <section class="about-hero">
            <div class="about-hero-content">
                <h1>Our Story</h1>
                <p class="subtitle">Discover who we are and what makes Tienda Mall special</p>
            </div>
            <div class="about-hero-image">
                <img src="../assets/images/about-hero.jpg" alt="About Tienda Mall">
            </div>
        </section>
        
        <!-- Mission Section -->
        <section class="about-mission">
            <div class="mission-box">
                <h2>Our Mission</h2>
                <p>At Tienda Mall, our mission is to make quality products accessible to everyone. We strive to provide an exceptional shopping experience through a thoughtfully curated selection of products, competitive pricing, and outstanding customer service.</p>
            </div>
            
            <div class="mission-box">
                <h2>Our Vision</h2>
                <p>To become the most trusted e-commerce platform where customers can discover products they love, delivered with speed and reliability, while creating a positive impact in our communities.</p>
            </div>
        </section>
        
        <!-- Team Section -->
        <section class="about-team">
            <h2 class="section-title">Meet Our Team</h2>
            <div class="team-grid">
                <div class="team-member">
                    <div class="member-image">
                        <img src="../assets/images/team-1.jpg" alt="Team Member">
                    </div>
                    <h3>Ahmed Hassan</h3>
                    <p class="member-title">CEO & Founder</p>
                    <p class="member-bio">With over 15 years of experience in retail, Ahmed leads our team with passion and vision.</p>
                </div>
                
                <div class="team-member">
                    <div class="member-image">
                        <img src="../assets/images/team-2.jpg" alt="Team Member">
                    </div>
                    <h3>Sarah Johnson</h3>
                    <p class="member-title">Head of Marketing</p>
                    <p class="member-bio">Sarah brings creativity and data-driven strategies to help our brand reach new customers.</p>
                </div>
                
                <div class="team-member">
                    <div class="member-image">
                        <img src="../assets/images/team-3.jpg" alt="Team Member">
                    </div>
                    <h3>Mohammed Ali</h3>
                    <p class="member-title">Operations Manager</p>
                    <p class="member-bio">Mohammed ensures smooth operations and timely delivery for every customer order.</p>
                </div>
                
                <div class="team-member">
                    <div class="member-image">
                        <img src="../assets/images/team-4.jpg" alt="Team Member">
                    </div>
                    <h3>Leila Ahmed</h3>
                    <p class="member-title">Customer Experience</p>
                    <p class="member-bio">Leila leads our customer service team with a focus on creating delightful experiences.</p>
                </div>
            </div>
        </section>
        
        <!-- Values Section -->
        <section class="about-values">
            <h2 class="section-title">Our Values</h2>
            <div class="values-grid">
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-heart"></i>
                    </div>
                    <h3>Customer First</h3>
                    <p>We put our customers at the center of everything we do, focusing on their needs and satisfaction.</p>
                </div>
                
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-gem"></i>
                    </div>
                    <h3>Quality</h3>
                    <p>We carefully select products that meet our high standards for quality, durability, and value.</p>
                </div>
                
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-leaf"></i>
                    </div>
                    <h3>Sustainability</h3>
                    <p>We are committed to reducing our environmental footprint and promoting sustainable practices.</p>
                </div>
                
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <h3>Integrity</h3>
                    <p>We operate with honesty, transparency, and fairness in all our business dealings.</p>
                </div>
            </div>
        </section>
        
        <!-- Call to Action -->
        <section class="about-cta">
            <div class="cta-content">
                <h2>Join Our Journey</h2>
                <p>Discover amazing products and be part of our growing community of happy customers.</p>
                <a href="../products.php" class="btn btn-primary">Shop Now</a>
            </div>
        </section>
    </div>
</main>

<!-- Additional CSS -->
<style>
.about-main {
    padding: 40px 0;
}

.about-hero {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    margin-bottom: 60px;
    background-color: #f8f9fa;
    border-radius: 10px;
    overflow: hidden;
}

.about-hero-content {
    flex: 1;
    padding: 40px;
    min-width: 300px;
}

.about-hero-content h1 {
    font-size: 2.5rem;
    margin-bottom: 15px;
    color: #212529;
}

.about-hero-content .subtitle {
    font-size: 1.2rem;
    color: #6c757d;
    line-height: 1.6;
}

.about-hero-image {
    flex: 1;
    min-width: 300px;
}

.about-hero-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.about-mission {
    display: flex;
    flex-wrap: wrap;
    gap: 30px;
    margin-bottom: 60px;
}

.mission-box {
    flex: 1;
    min-width: 300px;
    background-color: #fff;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.mission-box h2 {
    font-size: 1.5rem;
    margin-bottom: 15px;
    color: #DB4444;
}

.mission-box p {
    color: #6c757d;
    line-height: 1.6;
}

.section-title {
    text-align: center;
    font-size: 2rem;
    margin-bottom: 40px;
    color: #212529;
    position: relative;
    padding-bottom: 15px;
}

.section-title::after {
    content: "";
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 80px;
    height: 3px;
    background-color: #DB4444;
}

.team-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 30px;
    margin-bottom: 60px;
}

.team-member {
    background-color: #fff;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    transition: transform 0.3s ease;
}

.team-member:hover {
    transform: translateY(-5px);
}

.member-image {
    width: 100%;
    height: 250px;
    overflow: hidden;
}

.member-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.team-member:hover .member-image img {
    transform: scale(1.05);
}

.team-member h3 {
    padding: 15px 20px 5px;
    margin: 0;
    font-size: 1.2rem;
    color: #212529;
}

.member-title {
    padding: 0 20px;
    margin: 0 0 10px;
    font-size: 0.9rem;
    color: #DB4444;
    font-weight: 500;
}

.member-bio {
    padding: 0 20px 20px;
    margin: 0;
    font-size: 0.95rem;
    color: #6c757d;
    line-height: 1.5;
}

.values-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 30px;
    margin-bottom: 60px;
}

.value-card {
    background-color: #fff;
    border-radius: 8px;
    padding: 30px 20px;
    text-align: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    transition: transform 0.3s ease;
}

.value-card:hover {
    transform: translateY(-5px);
}

.value-icon {
    width: 70px;
    height: 70px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: rgba(219, 68, 68, 0.1);
    border-radius: 50%;
    margin: 0 auto 20px;
}

.value-icon i {
    font-size: 30px;
    color: #DB4444;
}

.value-card h3 {
    margin: 0 0 15px;
    font-size: 1.2rem;
    color: #212529;
}

.value-card p {
    margin: 0;
    color: #6c757d;
    line-height: 1.5;
}

.about-cta {
    background-color: #DB4444;
    padding: 60px;
    border-radius: 10px;
    margin-bottom: 30px;
}

.cta-content {
    text-align: center;
    color: white;
}

.cta-content h2 {
    font-size: 2rem;
    margin-bottom: 15px;
}

.cta-content p {
    font-size: 1.1rem;
    margin-bottom: 30px;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}

.btn-primary {
    background-color: white;
    color: #DB4444;
    padding: 12px 30px;
    border-radius: 4px;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.3s ease;
    display: inline-block;
}

.btn-primary:hover {
    background-color: #f8f9fa;
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

@media (max-width: 768px) {
    .about-hero-content,
    .about-hero-image {
        flex: 100%;
    }
    
    .about-cta {
        padding: 40px 20px;
    }
}
</style>

<?php
// Include the footer with proper path adjustments
$pathAdjustment = '../';
include '../templates/footer.php';
?>
