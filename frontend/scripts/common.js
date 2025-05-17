/**
 * Common JavaScript functionality for Tienda Mall
 * Handles authentication state and global site elements
 */

// DOM Elements
const userActionsEl = document.getElementById('user-actions');
const guestActionsEl = document.getElementById('guest-actions');
const userWelcomeEl = document.getElementById('user-welcome');
const cartCountEl = document.getElementById('cart-count');
const logoutBtn = document.getElementById('logout-btn');

// Current user state
let currentUser = null;

// Check authentication status when page loads
document.addEventListener('DOMContentLoaded', () => {
    checkAuthStatus();
    setupEventListeners();
    loadCartCount();
});

/**
 * Check if user is logged in and update UI accordingly
 */
async function checkAuthStatus() {
    try {
        const response = await api.auth.checkStatus();
        
        if (response.status === 'success' && response.data && response.data.logged_in) {
            // User is logged in
            currentUser = response.data.user;
            updateUIForLoggedInUser(currentUser);
        } else {
            // User is not logged in
            updateUIForGuest();
        }
    } catch (error) {
        console.error('Error checking auth status:', error);
        updateUIForGuest();
    }
}

/**
 * Set up global event listeners
 */
function setupEventListeners() {
    // Logout button
    if (logoutBtn) {
        logoutBtn.addEventListener('click', handleLogout);
    }
}

/**
 * Update UI for logged in users
 * @param {Object} user - User object
 */
function updateUIForLoggedInUser(user) {
    if (guestActionsEl) guestActionsEl.style.display = 'none';
    if (userActionsEl) userActionsEl.style.display = 'flex';
    
    if (userWelcomeEl) {
        userWelcomeEl.textContent = `Welcome, ${user.first_name}!`;
    }
}

/**
 * Update UI for guests (not logged in)
 */
function updateUIForGuest() {
    if (guestActionsEl) guestActionsEl.style.display = 'flex';
    if (userActionsEl) userActionsEl.style.display = 'none';
}

/**
 * Handle logout button click
 */
async function handleLogout() {
    try {
        const response = await api.auth.logout();
        
        if (response.status === 'success') {
            // Refresh page or redirect to home
            window.location.href = '/';
        } else {
            alert('Logout failed: ' + response.message);
        }
    } catch (error) {
        console.error('Error during logout:', error);
    }
}

/**
 * Load cart count from API
 */
async function loadCartCount() {
    try {
        if (cartCountEl) {
            const response = await api.cart.get();
            
            if (response.status === 'success' && response.data) {
                const count = response.data.total_items || 0;
                cartCountEl.textContent = count;
                
                // Hide count if zero
                cartCountEl.style.display = count > 0 ? 'inline-block' : 'none';
            }
        }
    } catch (error) {
        console.error('Error loading cart count:', error);
    }
}

/**
 * Format price with currency
 * @param {number} price - Price to format
 * @param {string} currency - Currency code (default: USD)
 * @returns {string} - Formatted price
 */
function formatPrice(price, currency = 'USD') {
    return new Intl.NumberFormat('en-US', { 
        style: 'currency', 
        currency 
    }).format(price);
}

/**
 * Create product card HTML
 * @param {Object} product - Product data
 * @returns {string} - HTML for product card
 */
function createProductCard(product) {
    const imageUrl = product.image_url || 'assets/images/placeholder.jpg';
    
    return `
        <div class="product-card" data-id="${product.product_id}">
            <div class="product-image">
                <img src="${imageUrl}" alt="${product.title}">
                ${product.discount_percent > 0 ? 
                    `<span class="discount-badge">-${product.discount_percent}%</span>` : ''}
            </div>
            <div class="product-info">
                <h3 class="product-title">
                    <a href="pages/product.html?id=${product.product_id}">${product.title}</a>
                </h3>
                <div class="product-price">
                    ${product.discount_price ? 
                        `<span class="current-price">${formatPrice(product.discount_price)}</span>
                        <span class="original-price">${formatPrice(product.price)}</span>` : 
                        `<span class="current-price">${formatPrice(product.price)}</span>`
                    }
                </div>
                <div class="product-actions">
                    <button class="btn add-to-cart" data-id="${product.product_id}">
                        Add to Cart
                    </button>
                    <button class="btn wishlist-btn" data-id="${product.product_id}">
                        ♡
                    </button>
                </div>
            </div>
        </div>
    `;
}

/**
 * Load categories for menus
 * @param {string} targetElementId - Target element ID to populate
 */
async function loadCategories(targetElementId) {
    try {
        const targetElement = document.getElementById(targetElementId);
        if (!targetElement) return;
        
        const response = await api.get('/categories');
        
        if (response.status === 'success' && response.data && response.data.categories) {
            const categories = response.data.categories;
            let html = '';
            
            categories.forEach(category => {
                html += `<li><a href="pages/category.html?id=${category.category_id}">${category.name}</a></li>`;
            });
            
            targetElement.innerHTML = html;
        }
    } catch (error) {
        console.error('Error loading categories:', error);
    }
}

/**
 * Add product to cart
 * @param {number} productId - Product ID
 * @param {number} quantity - Quantity to add
 */
async function addToCart(productId, quantity = 1) {
    try {
        const response = await api.cart.addItem(productId, quantity);
        
        if (response.status === 'success') {
            // Update cart count
            if (cartCountEl && response.data && response.data.total_items) {
                cartCountEl.textContent = response.data.total_items;
                cartCountEl.style.display = 'inline-block';
            }
            
            // Show success message
            alert('Product added to cart!');
        } else {
            alert('Error: ' + response.message);
        }
    } catch (error) {
        console.error('Error adding to cart:', error);
        alert('Failed to add product to cart');
    }
}

// Add event delegation for add to cart buttons
document.addEventListener('click', function(e) {
    if (e.target && e.target.classList.contains('add-to-cart')) {
        const productId = e.target.getAttribute('data-id');
        if (productId) {
            addToCart(productId);
        }
    }
});
