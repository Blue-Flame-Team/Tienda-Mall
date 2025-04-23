// cart.js - Centralized cart logic for Tienda

const CART_KEY = 'cartItems';

function renderCartTable() {
  const cart = getCart();
  const tbody = document.getElementById('cart-table-body');
  const subtotalSpan = document.getElementById('cart-subtotal');
  const totalSpan = document.getElementById('cart-total');
  if (!tbody) return;

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
    let imgSrc = item.image || '';
    if (window.location.pathname.includes('/pages/') && imgSrc && !imgSrc.startsWith('..')) {
      if (imgSrc.startsWith('assets/')) imgSrc = '../' + imgSrc;
      else if (imgSrc.startsWith('/assets/')) imgSrc = '..' + imgSrc;
    }
    return `
      <tr>
        <td>
          <div class="cart-product">
            <img src="${imgSrc}" alt="${item.name}">
            <span>${item.name}</span>
          </div>
        </td>
        <td>$${item.price}</td>
        <td>
          <select class="cart-qty-select" data-id="${item.id}">
            ${[...Array(10).keys()].map(i => `<option value="${i+1}"${item.quantity===(i+1)?' selected':''}>${(i+1).toString().padStart(2,'0')}</option>`).join('')}
          </select>
        </td>
        <td>$${itemSubtotal}</td>
      </tr>
    `;
  }).join('');

  if (subtotalSpan) subtotalSpan.textContent = `$${total}`;
  if (totalSpan) totalSpan.textContent = `$${total}`;

  // Attach qty change events
  tbody.querySelectorAll('.cart-qty-select').forEach(select => {
    select.addEventListener('change', function() {
      const id = this.getAttribute('data-id');
      const qty = parseInt(this.value);
      updateCartItem(id, qty);
      renderCartTable();
      updateCartBadge();
    });
  });

  // No fallback image: let the browser show a broken image icon if missing.
  // tbody.querySelectorAll('img').forEach(img => {
  //   img.onerror = function() {
  //     this.src = 'assets/images/default-product.png'; // fallback image
  //   };
  // });
}

// Checkout button logic
function setupCheckoutBtn() {
  var btn = document.querySelector('.cart-checkout-btn');
  if (btn) {
    btn.onclick = function() {
      // Save cart data to sessionStorage for checkout page
      var cart = getCart();
      sessionStorage.setItem('checkoutCart', JSON.stringify(cart));
      window.location.href = 'checkout.html';
    };
  } else {
    console.warn('No .cart-checkout-btn found on this page.');
  }
}

document.addEventListener('DOMContentLoaded', function() {
  renderCartTable();
  updateCartBadge && updateCartBadge();
  setupCheckoutBtn();
  // Remove item logic (by clicking remove icon, if added)
  var cartTableBody = document.getElementById('cart-table-body');
  if (cartTableBody) {
    cartTableBody.addEventListener('click', function(e) {
    if (e.target.classList.contains('cart-remove-btn')) {
      const row = e.target.closest('tr');
      const id = row.querySelector('.cart-qty-select').getAttribute('data-id');
      removeCartItem(id);
      renderCartTable();
      updateCartBadge && updateCartBadge();
    }
  });
  }
});

// If cart changes dynamically, re-setup checkout btn
window.addEventListener('storage', setupCheckoutBtn);

if (typeof renderCartPage !== 'undefined') {
  window.renderCartPage = undefined;
}

document.addEventListener('DOMContentLoaded', function() {
  renderCartTable();
  updateCartBadge && updateCartBadge();
  // Remove item logic (by clicking remove icon, if added)
  var cartTableBody = document.getElementById('cart-table-body');
  if (cartTableBody) {
    cartTableBody.addEventListener('click', function(e) {
    if (e.target.classList.contains('cart-remove-btn')) {
      const row = e.target.closest('tr');
      const id = row.querySelector('.cart-qty-select').getAttribute('data-id');
      removeCartItem(id);
      renderCartTable();
      updateCartBadge && updateCartBadge();
    }
    });
  }
});

function getCart() {
  return JSON.parse(localStorage.getItem(CART_KEY) || '[]');
}

function saveCart(cart) {
  localStorage.setItem(CART_KEY, JSON.stringify(cart));
}

function addToCart(product, quantity = 1) {
  let cart = getCart();
  const idx = cart.findIndex(item => item.id === product.id);
  if (idx > -1) {
    cart[idx].quantity += quantity;
  } else {
    cart.push({ ...product, quantity });
  }
  saveCart(cart);
}

function updateCartItem(productId, newQuantity) {
  let cart = getCart();
  cart = cart.map(item => item.id === productId ? { ...item, quantity: newQuantity } : item);
  saveCart(cart);
}

function removeCartItem(productId) {
  let cart = getCart();
  cart = cart.filter(item => item.id !== productId);
  saveCart(cart);
}

function clearCart() {
  saveCart([]);
}

function getCartCount() {
  return getCart().reduce((sum, item) => sum + item.quantity, 0);
}

// Expose globally for use in inline event handlers if needed
window.cartAPI = {
  getCart,
  saveCart,
  addToCart,
  updateCartItem,
  removeCartItem,
  clearCart,
  getCartCount
};
