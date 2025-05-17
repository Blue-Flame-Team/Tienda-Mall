/**
 * product-details.js
 * JavaScript functions for the product details page
 */

document.addEventListener('DOMContentLoaded', function() {
    // Base URL for API calls
    const baseUrl = document.querySelector('head link').href.split('pages')[0];
    let cartCount = document.querySelector('.cart-count');
    
    // Thumbnail image switcher
    const thumbnails = document.querySelectorAll('.thumb-img');
    const mainImg = document.querySelector('.main-img');
    
    thumbnails.forEach(thumb => {
        thumb.addEventListener('click', function() {
            // Remove active class from all thumbnails
            thumbnails.forEach(t => t.classList.remove('active'));
            
            // Add active class to clicked thumbnail
            this.classList.add('active');
            
            // Update main image
            if (this.getAttribute('data-main-img')) {
                mainImg.src = this.getAttribute('data-main-img');
            } else {
                mainImg.src = this.src;
            }
        });
    });
    
    // Product quantity controls
    const qtyMinus = document.querySelector('.qty-minus');
    const qtyPlus = document.querySelector('.qty-plus');
    const qtyNumber = document.querySelector('.qty-number');
    
    if (qtyMinus && qtyPlus && qtyNumber) {
        qtyMinus.addEventListener('click', function() {
            let currentQty = parseInt(qtyNumber.textContent);
            if (currentQty > 1) {
                qtyNumber.textContent = currentQty - 1;
            }
        });
        
        qtyPlus.addEventListener('click', function() {
            let currentQty = parseInt(qtyNumber.textContent);
            // You can add a check against available stock here
            qtyNumber.textContent = currentQty + 1;
        });
    }
    
    // Color selection
    const colorDots = document.querySelectorAll('.color-dot');
    colorDots.forEach(dot => {
        dot.addEventListener('click', function() {
            colorDots.forEach(d => d.classList.remove('selected'));
            this.classList.add('selected');
        });
    });
    
    // Size selection
    const sizeBtns = document.querySelectorAll('.size-btn');
    sizeBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            sizeBtns.forEach(b => b.classList.remove('selected'));
            this.classList.add('selected');
        });
    });
    
    // Add to cart functionality for main product
    const addToCartBtn = document.querySelector('.add-to-cart-btn');
    if (addToCartBtn) {
        addToCartBtn.addEventListener('click', function() {
            const productId = this.getAttribute('data-product-id');
            const quantity = parseInt(qtyNumber ? qtyNumber.textContent : 1);
            
            // Get selected color and size if available
            let selectedColor = '';
            let selectedSize = '';
            
            const selectedColorDot = document.querySelector('.color-dot.selected');
            if (selectedColorDot) {
                selectedColor = selectedColorDot.style.background;
            }
            
            const selectedSizeBtn = document.querySelector('.size-btn.selected');
            if (selectedSizeBtn) {
                selectedSize = selectedSizeBtn.textContent;
            }
            
            // Add to cart
            fetch(baseUrl + 'api/add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `product_id=${productId}&quantity=${quantity}&color=${selectedColor}&size=${selectedSize}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Update cart count
                    if (cartCount) {
                        cartCount.textContent = data.cart_count || parseInt(cartCount.textContent) + quantity;
                    }
                    
                    // Show success message
                    alert('Product added to cart successfully!');
                } else {
                    // Show error message
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        });
    }
    
    // Add to cart for related products
    const relatedAddButtons = document.querySelectorAll('.related-card-add');
    relatedAddButtons.forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-product-id');
            
            // Add to cart with quantity 1
            fetch(baseUrl + 'api/add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `product_id=${productId}&quantity=1`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Update cart count
                    if (cartCount) {
                        cartCount.textContent = data.cart_count || parseInt(cartCount.textContent) + 1;
                    }
                    
                    // Show success message
                    alert('Product added to cart successfully!');
                } else {
                    // Show error message
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        });
    });
});
