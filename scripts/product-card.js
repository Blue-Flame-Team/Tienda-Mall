// JavaScript for handling product card functionality
document.addEventListener('DOMContentLoaded', function() {
    // Add to cart button functionality
    const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');
    
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Get product data from button attributes
            const productId = this.getAttribute('data-product-id');
            const productName = this.getAttribute('data-product-name');
            const productPrice = parseFloat(this.getAttribute('data-product-price'));
            const productImage = this.getAttribute('data-product-image');
            
            // Create product object
            const product = {
                id: productId,
                name: productName,
                price: productPrice,
                image: productImage,
                quantity: 1
            };
            
            // Show success message
            const successMessage = document.createElement('div');
            successMessage.className = 'cart-success-message';
            successMessage.textContent = 'تمت إضافة المنتج إلى السلة';
            successMessage.style.position = 'fixed';
            successMessage.style.top = '20px';
            successMessage.style.right = '20px';
            successMessage.style.backgroundColor = '#4CAF50';
            successMessage.style.color = 'white';
            successMessage.style.padding = '15px';
            successMessage.style.borderRadius = '4px';
            successMessage.style.zIndex = '9999';
            document.body.appendChild(successMessage);
            
            // Remove success message after 3 seconds
            setTimeout(() => {
                successMessage.style.opacity = '0';
                successMessage.style.transition = 'opacity 0.5s';
                setTimeout(() => {
                    document.body.removeChild(successMessage);
                }, 500);
            }, 3000);
            
            // Send directly to API - الطريق الصحيح للـ API
            fetch('/Tienda/api/add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    product_id: productId,
                    quantity: 1
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('تمت إضافة المنتج للعربة بنجاح');
                    
                    // Update cart badge
                    const cartBadge = document.querySelector('.nav-cart-after');
                    if (cartBadge) {
                        cartBadge.textContent = data.cart.count || '0';
                        cartBadge.style.display = 'block';
                    }
                } else {
                    console.error('خطأ: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });
    });
});
