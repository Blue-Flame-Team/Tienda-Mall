//  PRODUCTS DATA
const wishlistProducts = [
  {
    id: 1,
    name: "Gucci duffle bag",
    price: 960,
    oldPrice: 1160,
    discount: "-35%",
    img: "../assets/products/product1.jpg"
  },
  {
    id: 2,
    name: "RGB liquid CPU Cooler",
    price: 1960,
    img: "../assets/products/product2.jpg"
  },
  {
    id: 3,
    name: "GP11 Shooter USB Gamepad",
    price: 550,
    img: "../assets/products/product3.jpg"
  },
  {
    id: 4,
    name: "Quilted Satin Jacket",
    price: 750,
    img: "../assets/products/product4.jpg"
  }
];

const justForYouProducts = [
  {
    id: 1,
    name: "ASUS FHD Gaming Laptop",
    price: 960,
    oldPrice: 1160,
    discount: "-35%",
    img: "../assets/products/product5.jpg",
    rating: 5,
    reviews: 65
  },
  {
    id: 2,
    name: "IPS LCD Gaming Monitor",
    price: 1160,
    img: "../assets/products/product6.jpg",
    rating: 5,
    reviews: 65
  },
  {
    id: 3,
    name: "HAVIT HV-G92 Gamepad",
    price: 560,
    badge: "NEW",
    img: "../assets/products/product7.jpg",
    rating: 5,
    reviews: 65
  },
  {
    id: 4,
    name: "AK-900 Wired Keyboard",
    price: 200,
    img: "../assets/products/product8.jpg",
    rating: 5,
    reviews: 65
  }
];

function renderWishlist() {
  const wishlistContainer = document.querySelector('.wishlist-cards');
  if (wishlistProducts.length === 0) {
    wishlistContainer.innerHTML = `
      <div style="width:100%;text-align:center;padding:48px 0;color:#888;font-size:1.15rem;">
        <i class='fa fa-heart-o' style='font-size:3.2rem;color:#DB4444;margin-bottom:12px;'></i><br>
        Your wishlist is empty!<br>
        <a href="../index.html" style="display:inline-block;margin-top:22px;background:#DB4444;color:#fff;padding:12px 40px 12px 32px;font-size:1.09rem;border-radius:10px;text-decoration:none;font-weight:600;transition:background 0.18s;box-shadow:0 2px 10px #db44443a;letter-spacing:0.5px;">
          <i class="fa fa-shopping-cart" style="margin-right:10px;font-size:1.15em;"></i>Continue Shopping
        </a>
      </div>
    `;
    return;
  }
  wishlistContainer.innerHTML = wishlistProducts.map((product, idx) => `
    <div class="wishlist-card" data-index="${idx}">
      ${product.discount ? `<span class="discount">${product.discount}</span>` : ''}
      <span class="remove-icon"><i class="fa fa-trash"></i></span>
      <img src="${product.img}" alt="${product.name}">
      <button class="add-to-cart-btn"><i class="fa fa-shopping-cart"></i> Add To Cart</button>
      <div class="product-name">${product.name}</div>
      <div>
        <span class="product-price">$${product.price}</span>
        ${product.oldPrice ? `<span class="old-price">$${product.oldPrice}</span>` : ''}
      </div>
    </div>
  `).join('');
}

function renderJustForYou() {
  const justForYouContainer = document.querySelector('.just-for-you-cards');
  justForYouContainer.innerHTML = justForYouProducts.map((product, idx) => `
    <div class="just-for-you-card" data-index="${idx}">
      ${product.discount ? `<span class="discount">${product.discount}</span>` : ''}
      ${product.badge ? `<span class="new-badge">${product.badge}</span>` : ''}
      <span class="view-icon" style="position:absolute;top:14px;right:14px;color:#888;font-size:1.14rem;cursor:pointer;"><i class="fa fa-eye"></i></span>
      <img src="${product.img}" alt="${product.name}">
      <button class="add-to-cart-btn"><i class="fa fa-shopping-cart"></i> Add To Cart</button>
      <div class="product-name">${product.name}</div>
      <div>
        <span class="product-price">$${product.price}</span>
        ${product.oldPrice ? `<span class="old-price">$${product.oldPrice}</span>` : ''}
      </div>
      <div class="stars">
        ${'<i class="fa fa-star"></i>'.repeat(product.rating)}
        <span style="color:#888;font-size:0.96rem;font-weight:500;">(${product.reviews})</span>
      </div>
    </div>
  `).join('');

  setupJustForYouActions();
}

function setupJustForYouActions() {
  const justForYouContainer = document.querySelector('.just-for-you-cards');
  justForYouContainer.addEventListener('click', function(e) {
    // Add To Cart
    const addBtn = e.target.closest('.add-to-cart-btn');
    if (addBtn) {
      addToCart(1);
      return;
    }
    // Eye Icon
    const eyeBtn = e.target.closest('.view-icon');
    if (eyeBtn) {
      const card = eyeBtn.closest('.just-for-you-card');
      const idx = Number(card.getAttribute('data-index'));
      const prod = justForYouProducts[idx];
      alert(`${prod.name}\n\nPrice: $${prod.price}${prod.oldPrice ? ' (Old: $' + prod.oldPrice + ')' : ''}`);
      return;
    }
  });
  // See All
  const seeAllBtn = document.querySelector('.see-all-btn');
  if (seeAllBtn) {
    seeAllBtn.onclick = function() {
      window.location.href = '../products.html';
    };
  }
}

function updateWishlistCount() {
  const countSpan = document.querySelector('.wishlist-count');
  if (countSpan) {
    countSpan.textContent = wishlistProducts.length;
  }
  // Update Wishlist title
  const wishlistTitle = document.querySelector('.wishlist-title');
  if (wishlistTitle) {
    wishlistTitle.textContent = `Wishlist (${wishlistProducts.length})`;
  }
}

// حذف المنتج من الـ Wishlist
function setupWishlistDelete() {
  const wishlistContainer = document.querySelector('.wishlist-cards');
  wishlistContainer.addEventListener('click', function(e) {
    // حذف منتج مفرد
    const removeBtn = e.target.closest('.remove-icon');
    if (removeBtn) {
      const card = removeBtn.closest('.wishlist-card');
      const idx = Number(card.getAttribute('data-index'));
      wishlistProducts.splice(idx, 1);
      renderWishlist();
      updateWishlistCount();
      return;
    }
    // إضافة منتج واحد للعربة
    const addToCartBtn = e.target.closest('.add-to-cart-btn');
    if (addToCartBtn) {
      const card = addToCartBtn.closest('.wishlist-card');
      const idx = Number(card.getAttribute('data-index'));
      addToCart(1); // أضف منتج واحد
      wishlistProducts.splice(idx, 1);
      renderWishlist();
      updateWishlistCount();
      return;
    }
  });
}

// زر نقل الكل للعربة
function setupMoveAllToBag() {
  const moveAllBtn = document.querySelector('.move-all-btn');
  if (moveAllBtn) {
    moveAllBtn.addEventListener('click', function() {
      if (wishlistProducts.length > 0) {
        addToCart(wishlistProducts.length);
        wishlistProducts.length = 0;
        renderWishlist();
        updateWishlistCount();
      }
    });
  }
}

// عداد العربة
let cartCount = 0;
function addToCart(count) {
  cartCount += count;
  const cartCountSpan = document.querySelector('.cart-count');
  if (cartCountSpan) {
    cartCountSpan.textContent = cartCount;
    cartCountSpan.style.display = cartCount > 0 ? 'inline-block' : 'none';
  }
}


document.addEventListener('DOMContentLoaded', function() {
  renderWishlist();
  renderJustForYou();
  updateWishlistCount();
  setupWishlistDelete();
  setupMoveAllToBag();
  // أنشئ عداد العربة إذا لم يوجد
  let cartIcon = document.querySelector('.icon-link img[alt="Cart"]');
  if (cartIcon && !document.querySelector('.cart-count')) {
    const span = document.createElement('span');
    span.className = 'cart-count';
    span.style.position = 'absolute';
    span.style.top = '2px';
    span.style.right = '2px';
    span.style.background = '#DB4444';
    span.style.color = '#fff';
    span.style.fontSize = '12px';
    span.style.fontWeight = '700';
    span.style.padding = '1px 6px';
    span.style.borderRadius = '10px';
    span.style.minWidth = '18px';
    span.style.textAlign = 'center';
    span.style.display = 'none';
    cartIcon.parentNode.style.position = 'relative';
    cartIcon.parentNode.appendChild(span);
  }
});
