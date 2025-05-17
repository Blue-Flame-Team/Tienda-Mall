// cart.js - Centralized cart logic for Tienda
// Updated to work with server-side PHP cart implementation

async function renderCartTable() {
  const tbody = document.getElementById('cart-table-body');
  const subtotalSpan = document.getElementById('cart-subtotal');
  const totalSpan = document.getElementById('cart-total');
  if (!tbody) return;
  
  // Show loading indicator
  tbody.innerHTML = `<tr><td colspan="4" style="text-align:center;padding:32px 0;"><div class="loader"></div></td></tr>`;
  
  // Get cart data asynchronously
  const cart = await getCart();
  
  if (!cart.length) {
    tbody.innerHTML = `<tr><td colspan="4" style="text-align:center;color:#DB4444;padding:32px 0;">Your cart is empty.</td></tr>`;
    if (subtotalSpan) subtotalSpan.textContent = '$0';
    if (totalSpan) totalSpan.textContent = '$0';
    return;
  }

  let total = 0;
  tbody.innerHTML = cart.map(item => {
    const itemSubtotal = item.price * item.quantity;
    total += itemSubtotal;
    // Fix image path for /pages/ context
    let imgSrc = item.image_url || '';
    if (window.location.pathname.includes('/pages/') && imgSrc && !imgSrc.startsWith('..')) {
      if (imgSrc.startsWith('assets/')) imgSrc = '../' + imgSrc;
      else if (imgSrc.startsWith('/assets/')) imgSrc = '..' + imgSrc;
    }
    return `
      <tr>
        <td>
          <div class="cart-product">
            <img src="${imgSrc}" alt="${item.title}">
            <span>${item.title}</span>
          </div>
        </td>
        <td>$${item.price}</td>
        <td>
          <div class="quantity-controls">
            <button type="button" class="quantity-decrease" data-id="${item.product_id}">-</button>
            <input type="number" class="cart-qty-input" data-id="${item.product_id}" value="${item.quantity}" min="1" max="99">
            <button type="button" class="quantity-increase" data-id="${item.product_id}">+</button>
          </div>
        </td>
        <td>$${itemSubtotal.toFixed(2)}</td>
        <td>
          <button type="button" class="cart-remove-btn" data-id="${item.product_id}">
            <i class="fa fa-times"></i>
          </button>
        </td>
      </tr>
    `;
  }).join('');

  if (subtotalSpan) subtotalSpan.textContent = `$${total.toFixed(2)}`;
  if (totalSpan) totalSpan.textContent = `$${total.toFixed(2)}`;

  // Attach quantity decrease events
  tbody.querySelectorAll('.quantity-decrease').forEach(btn => {
    btn.addEventListener('click', async function() {
      const id = this.getAttribute('data-id');
      const input = tbody.querySelector(`.cart-qty-input[data-id="${id}"]`);
      let qty = parseInt(input.value) - 1;
      if (qty < 1) qty = 1;
      
      input.value = qty;
      await updateCartItem(id, qty);
      renderCartTable();
    });
  });
  
  // Attach quantity increase events
  tbody.querySelectorAll('.quantity-increase').forEach(btn => {
    btn.addEventListener('click', async function() {
      const id = this.getAttribute('data-id');
      const input = tbody.querySelector(`.cart-qty-input[data-id="${id}"]`);
      let qty = parseInt(input.value) + 1;
      
      input.value = qty;
      await updateCartItem(id, qty);
      renderCartTable();
    });
  });
  
  // Attach quantity input events
  tbody.querySelectorAll('.cart-qty-input').forEach(input => {
    input.addEventListener('change', async function() {
      const id = this.getAttribute('data-id');
      let qty = parseInt(this.value);
      if (isNaN(qty) || qty < 1) qty = 1;
      
      this.value = qty;
      await updateCartItem(id, qty);
      renderCartTable();
    });
  });
  
  // Attach remove button events
  tbody.querySelectorAll('.cart-remove-btn').forEach(btn => {
    btn.addEventListener('click', async function() {
      const id = this.getAttribute('data-id');
      if (confirm('Are you sure you want to remove this item from your cart?')) {
        await removeCartItem(id);
        renderCartTable();
      }
    });
  });
}

// Checkout button logic
async function setupCheckoutBtn() {
  var btn = document.querySelector('.cart-checkout-btn');
  if (btn) {
    btn.onclick = function() {
      // Redirect to checkout page - cart is already saved in session on server
      window.location.href = 'checkout.php';
    };
  } else {
    console.warn('No .cart-checkout-btn found on this page.');
  }
}

document.addEventListener('DOMContentLoaded', async function() {
  await renderCartTable();
  await setupCheckoutBtn();
  
  // Setup clear cart button if it exists
  const clearCartBtn = document.getElementById('clear-cart');
  if (clearCartBtn) {
    clearCartBtn.addEventListener('click', async function() {
      if (confirm('Are you sure you want to clear your cart?')) {
        await clearCart();
        renderCartTable();
      }
    });
  }
  
  // Setup coupon application if the form exists
  const couponForm = document.querySelector('form[action*="apply_coupon"]');
  if (couponForm) {
    couponForm.addEventListener('submit', function(e) {
      // The form will be handled server-side with a normal form submission
      // No need to prevent default or add AJAX handling here
    });
  }
});

// Update the cart badge with the current item count when the page loads
async function updateCartBadge() {
  const count = await getCartCount();
  const cartBadges = document.querySelectorAll('.nav-cart-after');
  
  cartBadges.forEach(badge => {
    badge.textContent = count || 0;
    badge.style.display = count > 0 ? 'block' : 'none';
  });
}

// Call updateCartBadge when the page loads
document.addEventListener('DOMContentLoaded', updateCartBadge);

// Get cart from server-side session
function getCart() {
  return new Promise((resolve) => {
    fetch('/api/cart/get.php')
      .then(response => response.json())
      .then(data => {
        if (data.status === 'success') {
          resolve(data.data.cart_items || []);
        } else {
          console.error('Error fetching cart:', data.message);
          resolve([]);
        }
      })
      .catch(error => {
        console.error('Error fetching cart:', error);
        resolve([]);
      });
  });
}

// Add item to cart
function addToCart(product, quantity = 1) {
  return fetch('/api/cart/add.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      product_id: product.id,
      quantity: quantity
    })
  })
  .then(response => response.json())
  .then(data => {
    if (data.status === 'success') {
      updateCartBadge(data.data.item_count);
      return true;
    } else {
      console.error('Error adding to cart:', data.message);
      return false;
    }
  })
  .catch(error => {
    console.error('Error adding to cart:', error);
    return false;
  });
}

// Update cart item quantity
function updateCartItem(productId, newQuantity) {
  return fetch('/api/cart/update.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      product_id: productId,
      quantity: newQuantity
    })
  })
  .then(response => response.json())
  .then(data => {
    if (data.status === 'success') {
      updateCartBadge(data.data.item_count);
      return true;
    } else {
      console.error('Error updating cart:', data.message);
      return false;
    }
  })
  .catch(error => {
    console.error('Error updating cart:', error);
    return false;
  });
}

// Remove item from cart
function removeCartItem(productId) {
  return fetch('/api/cart/remove.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      product_id: productId
    })
  })
  .then(response => response.json())
  .then(data => {
    if (data.status === 'success') {
      updateCartBadge(data.data.item_count);
      return true;
    } else {
      console.error('Error removing item from cart:', data.message);
      return false;
    }
  })
  .catch(error => {
    console.error('Error removing item from cart:', error);
    return false;
  });
}

// Clear cart
function clearCart() {
  return fetch('/api/cart/clear.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    }
  })
  .then(response => response.json())
  .then(data => {
    if (data.status === 'success') {
      updateCartBadge(0);
      return true;
    } else {
      console.error('Error clearing cart:', data.message);
      return false;
    }
  })
  .catch(error => {
    console.error('Error clearing cart:', error);
    return false;
  });
}

// Get cart item count
function getCartCount() {
  return new Promise((resolve) => {
    fetch('/api/cart/get.php')
      .then(response => response.json())
      .then(data => {
        if (data.status === 'success') {
          resolve(data.data.item_count || 0);
        } else {
          console.error('Error fetching cart count:', data.message);
          resolve(0);
        }
      })
      .catch(error => {
        console.error('Error fetching cart count:', error);
        resolve(0);
      });
  });
}

// Expose globally for use in inline event handlers if needed
window.cartAPI = {
  getCart,
  addToCart,
  updateCartItem,
  removeCartItem,
  clearCart,
  getCartCount,
  updateCartBadge
};
