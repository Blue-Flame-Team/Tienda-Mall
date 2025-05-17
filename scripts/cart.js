// cart.js - Centralized cart logic for Tienda

const CART_KEY = 'cartItems';

// Function to get the base URL for the site with correct Tienda path
function getSiteBaseUrl() {
  // Force the base URL to always include 'Tienda' regardless of current page
  // This ensures API paths are correct even if accessed from different locations
  return window.location.protocol + '//' + window.location.host + '/Tienda/';
}

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
      window.location.href = 'checkout.php';
    };
  } else {
    console.warn('No .cart-checkout-btn found on this page.');
  }
}

// Consolidated DOMContentLoaded event listener
document.addEventListener('DOMContentLoaded', function() {
  renderCartTable();
  if (typeof updateCartBadge === 'function') {
    updateCartBadge();
  } else if (window.cartAPI && window.cartAPI.updateCartCounter) {
    window.cartAPI.updateCartCounter();
  }
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
        
        // Update cart badge using available method
        if (typeof updateCartBadge === 'function') {
          updateCartBadge();
        } else if (window.cartAPI && window.cartAPI.updateCartCounter) {
          window.cartAPI.updateCartCounter();
        }
      }
    });
  }
});

// If cart changes dynamically, re-setup checkout btn
window.addEventListener('storage', setupCheckoutBtn);

// Cleanup any undefined function
if (typeof renderCartPage !== 'undefined') {
  window.renderCartPage = undefined;
}

function getCart() {
  return JSON.parse(localStorage.getItem(CART_KEY) || '[]');
}

function saveCart(cart) {
  localStorage.setItem(CART_KEY, JSON.stringify(cart));
}

function addToCart(product, quantity = 1) {
  // Actualizar el carrito local primero para una experiencia de usuario inmediata
  let cart = getCart();
  const idx = cart.findIndex(item => item.id === product.id);
  if (idx > -1) {
    cart[idx].quantity += quantity;
  } else {
    cart.push({ ...product, quantity });
  }
  saveCart(cart);
  
  // Actualizar el contador del carrito en el DOM
  updateCartCounter();
  
  // Sincronizar con el backend
  const productId = product.id || product.product_id;
  if (productId) {
    // Use absolute URL to prevent path resolution issues
    const apiUrl = window.location.origin + '/Tienda/api/add_to_cart.php';
    console.log('Adding to cart at:', apiUrl);
    
    fetch(apiUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        product_id: productId,
        quantity: quantity,
        options: product.options || {}
      })
    })
    .then(response => response.json())
    .then(data => {
      if (data.status === 'success') {
        console.log('Cart updated successfully:', data.message);
        // Update cart count from the server
        if (data.cart_count) {
          const cartCountEl = document.querySelector('.cart-count');
          if (cartCountEl) {
            cartCountEl.textContent = data.cart_count;
          }
        }
      } else {
        console.error('Error al sincronizar carrito:', data.message);
      }
    })
    .catch(error => {
      console.error('Error al sincronizar carrito:', error);
    });
  }
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

/**
 * Actualiza el contador del carrito en la interfaz
 */
function updateCartCounter() {
  // Obtener el contador del carrito
  const count = getCartCount();
  
  // Actualizar todos los elementos con la clase 'cart-count'
  const cartCounters = document.querySelectorAll('.cart-count');
  cartCounters.forEach(counter => {
    counter.textContent = count;
    // Si el contador es cero, ocultar el elemento, de lo contrario mostrarlo
    if (count === 0) {
      counter.style.display = 'none';
    } else {
      counter.style.display = 'inline-block';
    }
  });
  
  // Si hay un icono de carrito en el header, mostrar un indicador visual
  const cartIcons = document.querySelectorAll('.cart-icon');
  cartIcons.forEach(icon => {
    if (count > 0) {
      icon.classList.add('has-items');
    } else {
      icon.classList.remove('has-items');
    }
  });
}

/**
 * Sincroniza el carrito desde el servidor
 */
function syncCartFromServer() {
  // Use absolute URL to prevent path resolution issues
  const apiUrl = window.location.origin + '/Tienda/api/get_cart.php';
  console.log('Fetching cart from:', apiUrl);
  
  fetch(apiUrl)
    .then(response => response.json())
    .then(data => {
      if (data.success && data.cart && data.cart.items) {
        // Convertir los items del carrito del servidor al formato local
        const serverCart = data.cart.items.map(item => ({
          id: parseInt(item.product_id),
          name: item.name || 'Producto',
          price: parseFloat(item.price) || 0,
          image: item.image || '',
          quantity: parseInt(item.quantity) || 1
        }));
        
        // Combinar con el carrito local
        let localCart = getCart();
        
        // Si el carrito del servidor no está vacío, usarlo
        if (serverCart.length > 0) {
          // Fusionar carritos para no perder items
          const mergedCart = [...localCart];
          
          // Añadir o actualizar items del servidor
          serverCart.forEach(serverItem => {
            const existingItemIndex = mergedCart.findIndex(item => item.id === serverItem.id);
            if (existingItemIndex >= 0) {
              // Actualizar cantidad si ya existe
              mergedCart[existingItemIndex].quantity = Math.max(
                mergedCart[existingItemIndex].quantity,
                serverItem.quantity
              );
            } else {
              // Añadir nuevo item
              mergedCart.push(serverItem);
            }
          });
          
          // Guardar carrito fusionado
          saveCart(mergedCart);
        }
        
        // Actualizar contador
        updateCartCounter();
      }
    })
    .catch(error => {
      console.error('Error al sincronizar carrito desde el servidor:', error);
    });
}

// Cuando se carga la página
document.addEventListener('DOMContentLoaded', () => {
  // Sincronizar carrito desde el servidor
  syncCartFromServer();
  // Actualizar contador del carrito
  updateCartCounter();
});

// Expose globally for use in inline event handlers if needed
window.cartAPI = {
  getCart,
  saveCart,
  addToCart,
  updateCartItem,
  removeCartItem,
  clearCart,
  getCartCount,
  updateCartCounter,
  syncCartFromServer
};
