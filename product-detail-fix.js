/**
 * Product Detail Page Cart Functionality Fix
 * 
 * This script updates the cart API endpoints and response handling on product.php
 */

// Wait for page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('Product Detail Cart Fix Script Loaded');
    
    // Fix add to cart form
    const addToCartForm = document.getElementById('add-to-cart-form');
    if (addToCartForm) {
        console.log('Found Add to Cart form - replacing event handler');
        
        // Remove original event listener
        const newForm = addToCartForm.cloneNode(true);
        addToCartForm.parentNode.replaceChild(newForm, addToCartForm);
        
        // Add new event listener with correct API path
        newForm.addEventListener('submit', function(event) {
            event.preventDefault();
            
            // Get product details
            const productId = document.querySelector('.btn-add-to-cart').getAttribute('data-product-id');
            const quantityInput = document.getElementById('quantity');
            const quantity = parseInt(quantityInput.value);
            
            console.log('Adding to cart:', productId, 'qty:', quantity);
            
            // Call the updated API endpoint with absolute path
            fetch(window.location.origin + '/Tienda/api/add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    product_id: productId,
                    quantity: quantity
                })
            })
            .then(response => {
                console.log('API response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('API response data:', data);
                
                // Use updated response format
                if (data.success) {
                    // Update cart count in UI
                    const cartCountElement = document.querySelector('.cart-count');
                    if (cartCountElement) {
                        cartCountElement.textContent = data.cart.count;
                    }
                    
                    alert('Product added to cart!');
                } else {
                    alert(data.message || 'Failed to add product to cart');
                }
            })
            .catch(error => {
                console.error('Error adding to cart:', error);
                alert('An error occurred. Please try again.');
            });
        });
    }
    
    // Fix related products add to cart buttons
    const relatedProductButtons = document.querySelectorAll('.product-grid .btn-add-to-cart');
    if (relatedProductButtons.length > 0) {
        console.log('Found', relatedProductButtons.length, 'related product buttons - updating handlers');
        
        relatedProductButtons.forEach(button => {
            // Remove original click handler
            const newButton = button.cloneNode(true);
            button.parentNode.replaceChild(newButton, button);
            
            // Add new click handler with correct API path
            newButton.addEventListener('click', function() {
                const productId = this.getAttribute('data-product-id');
                console.log('Adding related product to cart:', productId);
                
                // Call the updated API endpoint with absolute path
                fetch(window.location.origin + '/Tienda/api/add_to_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        product_id: productId,
                        quantity: 1
                    })
                })
                .then(response => response.json())
                .then(data => {
                    console.log('API response for related product:', data);
                    
                    // Use updated response format
                    if (data.success) {
                        // Update cart count in UI
                        const cartCountElement = document.querySelector('.cart-count');
                        if (cartCountElement) {
                            cartCountElement.textContent = data.cart.count;
                        }
                        
                        alert('Product added to cart!');
                    } else {
                        alert(data.message || 'Failed to add product to cart');
                    }
                })
                .catch(error => {
                    console.error('Error adding related product to cart:', error);
                    alert('An error occurred. Please try again.');
                });
            });
        });
    }
    
    console.log('Product Detail Cart Fix Script Completed');
});
