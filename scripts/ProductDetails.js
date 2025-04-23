<<<<<<< HEAD
const mainImg = document.querySelector('.main-img');
const mainImgFloat = document.querySelector('.main-img-float');
const floatingImg = document.querySelector('.floating-img');
const overlay = document.querySelector('.overlay');
const closeFloat = document.querySelector('.close-icon');
const thumbs = document.querySelectorAll('.thumb-img');
const floatThumbs = document.querySelectorAll('.thumb-img-float');
const addCartBtn = document.querySelector('.add-cart-btn');
const leftArrow = document.querySelector('.btn-swipe-left');
const rightArrow = document.querySelector('.btn-swipe-right');
const minusBtn = document.querySelector('.minus');
const plusBtn = document.querySelector('.plus');
const cartNumber = document.querySelector('.cart-number');
const navCart = document.querySelector('.nav-cart');
const cartBox = document.querySelector('.cart');
const emptyCart = document.querySelector('.empty-cart');
const filledCart = document.querySelector('.cart-bottom');
const numberCart = document.querySelector('.count-items');
const currentPrice = document.querySelector('.current-price');
const deleteIcon = document.querySelector('.delete');
const navCartBadge = document.querySelector('.nav-cart-after');
const btnLeft = document.querySelector('.btn-left');
const btnRight = document.querySelector('.btn-right');
const cartCheckout = document.querySelector('.cart-checkout');
// جلب بيانات المنتج من productsData.js بناءً على id في الرابط
// جلب اسم المنتج (slug) من الرابط
function getProductNameSlugFromUrl() {
    const params = new URLSearchParams(window.location.search);
    return params.get('name');
}

// تحويل النص من slug إلى اسم عادي
function unslugify(slug) {
    if (!slug) return '';
    return slug.replace(/-/g, ' ').replace(/\s+/g, ' ').trim().toLowerCase();
}

function renderProductDetails() {
    if (!window.productsData) return;
    const slug = getProductNameSlugFromUrl();
    if (!slug) {
        const titleElem = document.querySelector('.product-title-detail');
        if (titleElem) titleElem.textContent = 'Product Not Found';
        const mainImg = document.querySelector('.main-img');
        if (mainImg) mainImg.src = getProductImgPath('product1.png');
        return;
    }
    // ابحث عن المنتج بالاسم بعد فك السلاج
    const product = window.productsData.find(p => p.title && unslugify(p.title) === unslugify(slug));
    if (!product) {
        const titleElem = document.querySelector('.product-title-detail');
        if (titleElem) titleElem.textContent = 'Product Not Found';
        const mainImg = document.querySelector('.main-img');
        if (mainImg) mainImg.src = getProductImgPath('product1.jpg');
        return;
    }
    // تحديث العنوان
    const titleElem = document.querySelector('.product-title-detail');
    if (titleElem) titleElem.textContent = product.title || 'Product';

    // صور المنتج
    const mainImg = document.querySelector('.main-img');
    const mainImgFloat = document.querySelector('.main-img-float');
    let imagesArr = (product.images && product.images.length) ? product.images : [];
    // إذا لم توجد صور للمنتج في البيانات، استخدم صور من مجلد assets/products
    if (imagesArr.length === 0) {
        imagesArr = [];
        for (let i = 1; i <= 16; i++) {
            imagesArr.push(`product${i}.jpg`);
        }
    }
    if (mainImg) mainImg.src = getProductImgPath(imagesArr[0]);
    if (mainImgFloat) mainImgFloat.src = getProductImgPath(imagesArr[0]);

    // Populate thumbnails
    const thumbsContainer = document.querySelector('.thumbnails');
    if (thumbsContainer) {
        thumbsContainer.innerHTML = '';
        imagesArr.forEach((img, idx) => {
            const thumbDiv = document.createElement('div');
            thumbDiv.className = 'thumb-img' + (idx === 0 ? ' active-thumb' : '');
            const thumbImg = document.createElement('img');
            thumbImg.src = getProductImgPath(img);
            thumbImg.alt = `product thumbnail ${idx+1}`;
            thumbImg.addEventListener('click', function() {
                // Update main image
                if (mainImg) mainImg.src = getProductImgPath(img);
                // تحديث حالة الـ active-thumb
                document.querySelectorAll('.thumb-img').forEach(el => el.classList.remove('active-thumb'));
                thumbDiv.classList.add('active-thumb');
            });
            thumbDiv.appendChild(thumbImg);
            thumbsContainer.appendChild(thumbDiv);
        });
    }
    // دعم thumbnails عائمة (zoom/floating)
    const floatThumbsContainer = document.querySelector('.thumbnails-float');
    if (floatThumbsContainer && mainImgFloat) {
        floatThumbsContainer.innerHTML = '';
        imagesArr.forEach((img, idx) => {
            const thumbDiv = document.createElement('div');
            thumbDiv.className = 'thumb-img-float' + (idx === 0 ? ' active-thumb' : '');
            const thumbImg = document.createElement('img');
            thumbImg.src = getProductImgPath(img);
            thumbImg.alt = `product float thumbnail ${idx+1}`;
            thumbImg.addEventListener('click', () => {
                mainImgFloat.src = getProductImgPath(img);
                // Update main image as well
                if (mainImg) mainImg.src = getProductImgPath(img);
                // Highlight active float thumb
                floatThumbsContainer.querySelectorAll('.thumb-img-float').forEach(el => el.classList.remove('active-thumb'));
                thumbDiv.classList.add('active-thumb');
                // Highlight in main thumbs
                const mainThumbs = document.querySelectorAll('.thumb-img');
                if (mainThumbs && mainThumbs[idx]) {
                    mainThumbs.forEach(el => el.classList.remove('active-thumb'));
                    mainThumbs[idx].classList.add('active-thumb');
                }
            });
            thumbDiv.appendChild(thumbImg);
            floatThumbsContainer.appendChild(thumbDiv);
        });
    }

    // تحديث الوصف
    const descElem = document.getElementById('product-desc');
    if (descElem) descElem.textContent = product.desc || 'No description available.';
    // تحديث السعر
    const priceElem = document.getElementById('product-price');
    if (priceElem) priceElem.textContent = product.price ? `$${product.price}` : 'Contact us';

    // التقييم والريفيوهات
    const ratingStarsElem = document.getElementById('product-rating-stars');
    const reviewsElem = document.getElementById('product-reviews');
    if (ratingStarsElem) {
        const rating = product.rating || 4.5;
        let starsHtml = '';
        let fullStars = Math.floor(rating);
        let halfStar = rating % 1 >= 0.5;
        for (let i = 0; i < fullStars; i++) starsHtml += '<ion-icon name="star" style="color:#FFD600"></ion-icon>';
        if (halfStar) starsHtml += '<ion-icon name="star-half" style="color:#FFD600"></ion-icon>';
        for (let i = fullStars + (halfStar ? 1 : 0); i < 5; i++) starsHtml += '<ion-icon name="star-outline" style="color:#FFD600"></ion-icon>';
        ratingStarsElem.innerHTML = starsHtml;
    }
    if (reviewsElem) {
        reviewsElem.textContent = `(${product.reviews || 100} reviews)`;
    }
    // حالة التوفر
    const stockElem = document.getElementById('product-in-stock');
    if (stockElem) {
        stockElem.textContent = (product.inStock === false) ? 'Out of Stock' : 'In Stock';
        stockElem.style.color = (product.inStock === false) ? '#DB4444' : '#249c2b';
    }
    // الألوان
    const colorsElem = document.getElementById('product-colors');
    if (colorsElem) {
        colorsElem.innerHTML = '';
        (product.colors || ['#fff', '#000', '#b47cff']).forEach((color, idx) => {
            const colorSpan = document.createElement('span');
            colorSpan.className = 'color-circle' + (idx === 0 ? ' selected' : '');
            colorSpan.style.background = color;
            colorSpan.title = color;
            colorSpan.addEventListener('click', function() {
                document.querySelectorAll('.color-circle').forEach(el => el.classList.remove('selected'));
                colorSpan.classList.add('selected');
            });
            colorsElem.appendChild(colorSpan);
        });
    }
    // المقاسات
    const sizesElem = document.getElementById('product-sizes');
    if (sizesElem) {
        sizesElem.innerHTML = '';
        (product.sizes || ['XS','S','M','L','XL']).forEach((size, idx) => {
            const btn = document.createElement('button');
            btn.className = 'size-btn' + (size === 'M' ? ' selected' : '');
            btn.textContent = size;
            btn.addEventListener('click', function() {
                document.querySelectorAll('.size-btn').forEach(el => el.classList.remove('selected'));
                btn.classList.add('selected');
            });
            sizesElem.appendChild(btn);
        });
    }
    // الكمية
    let qty = 1;
    const qtyMinus = document.getElementById('qty-minus');
    const qtyPlus = document.getElementById('qty-plus');
    const qtyNumber = document.getElementById('qty-number');
    if (qtyMinus && qtyPlus && qtyNumber) {
        qtyNumber.textContent = qty;
        qtyMinus.onclick = function() {
            if (qty > 1) qty--;
            qtyNumber.textContent = qty;
        };
        qtyPlus.onclick = function() {
            if (qty < 10) qty++;
            qtyNumber.textContent = qty;
        };
    }
    // المفضلة
    const wishlistBtn = document.getElementById('wishlist-btn');
    if (wishlistBtn) {
        wishlistBtn.onclick = function() {
            wishlistBtn.classList.toggle('selected');
            const icon = wishlistBtn.querySelector('ion-icon');
            if (wishlistBtn.classList.contains('selected')) {
                icon.name = 'heart';
            } else {
                icon.name = 'heart-outline';
            }
        };
    }
    // زر Buy Now
    const buyNowBtn = document.getElementById('buy-now-btn');
    if (buyNowBtn) {
        buyNowBtn.onclick = function() {
            alert('تم شراء المنتج بنجاح!');
        };
    }
}

// Import cart.js for cart API

document.addEventListener('DOMContentLoaded', renderProductDetails);

// Add to cart integration for details page
// === Add To Cart for Related Items ===
document.addEventListener('DOMContentLoaded', function() {
  // Add to cart for related items
  const relatedAddBtns = document.querySelectorAll('.related-card-add');
  relatedAddBtns.forEach(btn => {
    btn.addEventListener('click', function() {
      // زيادة عداد العربة في localStorage
      let cartCount = parseInt(localStorage.getItem('cartCount')) || 0;
      cartCount++;
      localStorage.setItem('cartCount', cartCount);
      // تحديث البادج في الهيدر إذا موجود
      const badge = document.querySelector('.nav-cart-after');
      if (badge) {
        badge.textContent = cartCount;
        badge.classList.add('show');
      }
      alert('تمت إضافة المنتج إلى العربة!');
    });
  });

  // Buy Now for related items
  const relatedBuyBtns = document.querySelectorAll('.related-card-buy');
  relatedBuyBtns.forEach(btn => {
    btn.addEventListener('click', function() {
      window.location.href = '/pages/cart.html';
    });
  });
});

// Add to cart integration for details page
document.addEventListener('DOMContentLoaded', function() {
  const addCartBtn = document.querySelector('.add-cart-btn') || document.getElementById('buy-now-btn');
  if (addCartBtn) {
    addCartBtn.addEventListener('click', function(e) {
      if (!window.productsData) return;
      const slug = getProductNameSlugFromUrl();
      const product = window.productsData.find(p => p.title && unslugify(p.title) === unslugify(slug));
      if (!product) return;
      // Get selected options
      let selectedColor = document.querySelector('.color-circle.selected');
      let selectedSize = document.querySelector('.size-btn.selected');
      let qty = parseInt(document.getElementById('qty-number')?.textContent || '1', 10);
      window.cartAPI.addToCart({
        id: product.id,
        name: product.title,
        price: product.price,
        image: product.images[0],
        color: selectedColor ? selectedColor.style.background : '',
        size: selectedSize ? selectedSize.textContent : '',
      }, qty);
      // Update cart badge
      const badge = document.querySelector('.nav-cart-after');
      if (badge) {
        badge.textContent = window.cartAPI.getCartCount();
        badge.classList.add('show');
      }
      alert('تمت إضافة المنتج إلى العربة!');
    });
  }
});


// ========== يمكنك إضافة منطق السلايدر أو الكارت لاحقاً حسب الحاجة ==========
// CART FUNCTIONALITY
if (navCart && cartBox) {
  navCart.addEventListener("click", () => {
    cartBox.classList.toggle("show");
  });
}
if (document && cartBox) {
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") cartBox.classList.remove("show");
  });
}
if (btnRight && cartNumber && emptyCart && filledCart && numberCart && navCartBadge && currentPrice) {
  btnRight.addEventListener("click", () => {
    if (cartCount > 0 && totalItems + cartCount <= 20) {
      totalItems += cartCount;
      emptyCart.classList.remove("show");
      filledCart.classList.add("show");
      currentPrice.textContent = `$${totalItems * 125}.00`;
      numberCart.textContent = totalItems;
      navCartBadge.classList.add("show");
      navCartBadge.textContent = totalItems === 20 ? "full" : totalItems;
    }
  });
}
if (deleteIcon && emptyCart && filledCart && navCartBadge) {
  deleteIcon.addEventListener("click", () => {
    totalItems = 0;
    emptyCart.classList.add("show");
    filledCart.classList.remove("show");
    navCartBadge.classList.remove("show");
  });
}
if (minusBtn && cartNumber) {
  minusBtn.addEventListener("click", () => {
    if (cartCount > 0) {
      cartCount--;
      localStorage.setItem('cartCount', cartCount);
      cartNumber.textContent = cartCount;
      updateCartUI();
    }
  });
}
if (plusBtn && cartNumber) {
  plusBtn.addEventListener("click", () => {
    if (cartCount < 10) {
      cartCount++;
      localStorage.setItem('cartCount', cartCount);
      cartNumber.textContent = cartCount;
      updateCartUI();
    }
  });
}
// CLICK OUTSIDE TO CLOSE CART

// تعريف العناصر بأمان
// تعريف جميع العناصر مرة واحدة فقط في الأعلى


// دالة مساعدة لتصحيح مسار الصورة دائماً من فولدر المنتجات
function getProductImgPath(img) {
  if (!img) return 'https://dummyimage.com/400x400/cccccc/fff&text=No+Image';
  // إذا كان المسار يبدأ بـ assets/products/ أضف ../ في البداية ليعمل من صفحة الـ pages
  if (img.startsWith('assets/products/')) return '../' + img;
  // لو الصورة اسم فقط بدون مسار
  if (!img.startsWith('http')) return '../assets/products/' + img.replace(/^.*[\\\/]/, '');
  return img;
}


// تعريف المتغيرات العامة مرة واحدة فقط
let totalItems = 0;
// Initialize cartCount from localStorage or 0
let cartCount = parseInt(localStorage.getItem('cartCount')) || 0;
if (typeof window.currentIndex === 'undefined') window.currentIndex = 0;

// ========== FLOAT IMAGE LOGIC ==========
if(mainImg && floatingImg && overlay) {
  mainImg.addEventListener("click", () => {
    floatingImg.classList.add("activate");
    overlay.classList.add("activate");
    if (mainImgFloat) mainImgFloat.src = mainImg.src;
    if (floatThumbs && thumbs) {
      floatThumbs.forEach((thumb, i) => {
        thumb.classList.remove("active-thumb");
        if (thumbs[i] && thumbs[i].classList.contains("active-thumb")) {
          thumb.classList.add("active-thumb");
          window.currentIndex = i;
        }
      });
    }
  });
}
if (closeFloat) closeFloat.addEventListener("click", () => {
  if (floatingImg) floatingImg.classList.remove("activate");
  if (overlay) overlay.classList.remove("activate");
});
if (overlay) overlay.addEventListener("click", () => {
  if (floatingImg) floatingImg.classList.remove("activate");
  overlay.classList.remove("activate");
});
document.addEventListener("keydown", (e) => {
  if (e.key === "Escape") {
    if (floatingImg) floatingImg.classList.remove("activate");
    if (overlay) overlay.classList.remove("activate");
    if (cartBox) cartBox.classList.remove("show");
  }
});

// ========== SWIPE FLOAT IMAGE ==========
function updateFloatImage(direction) {
  if (!(floatThumbs && floatThumbs.length && mainImgFloat)) return;
  const max = floatThumbs.length - 1;
  if ((direction === "left" && window.currentIndex > 0) || (direction === "right" && window.currentIndex < max)) {
    floatThumbs[window.currentIndex].classList.remove("active-thumb");
    window.currentIndex += direction === "right" ? 1 : -1;
    floatThumbs[window.currentIndex].classList.add("active-thumb");
    mainImgFloat.src = floatThumbs[window.currentIndex].querySelector('img').src;
  }
}
// --- END updateFloatImage ---
if (leftArrow) leftArrow.addEventListener("click", () => updateFloatImage("left"));
if (rightArrow) rightArrow.addEventListener("click", () => updateFloatImage("right"));

// MAIN IMAGE THUMBNAILS
if (thumbs && thumbs.length && mainImg) {
  thumbs.forEach((thumb, i) => {
    thumb.addEventListener("click", () => {
      thumbs.forEach((el) => el.classList.remove("active-thumb"));
      thumb.classList.add("active-thumb");
      // لأن thumb نفسه هو <img>
      mainImg.classList.add('animate-img');
      setTimeout(() => {
        mainImg.src = thumb.src;
        setTimeout(() => {
          mainImg.classList.remove('animate-img');
        }, 50);
      }, 200);
      // sync float thumbs (لو فيه)
      if (floatThumbs && floatThumbs.length) {
        floatThumbs.forEach((ft, fi) => {
          ft.classList.toggle("active-thumb", fi === i);
        });
      }
      window.currentIndex = i;
    });
  });
  // === Slider Arrows for Main Images ===
  if (btnLeft && btnRight) {
    btnLeft.addEventListener('click', function() {
      window.currentIndex = (window.currentIndex - 1 + thumbs.length) % thumbs.length;
      thumbs[window.currentIndex].click();
    });
    btnRight.addEventListener('click', function() {
      window.currentIndex = (window.currentIndex + 1) % thumbs.length;
      thumbs[window.currentIndex].click();
    });
  }
}


// FLOAT IMAGE THUMBNAILS
if (floatThumbs && floatThumbs.length && mainImgFloat) {
  floatThumbs.forEach((thumb, i) => {
    thumb.addEventListener("click", () => {
      floatThumbs.forEach((el) => el.classList.remove("active-thumb"));
      thumb.classList.add("active-thumb");
      window.currentIndex = i;
      const imgTag = thumb.querySelector("img");
      if (imgTag) mainImgFloat.src = getProductImgPath(imgTag.src);
      // sync main thumbs
      if (thumbs && thumbs.length) {
        thumbs.forEach((mt, mi) => {
          mt.classList.toggle("active-thumb", mi === i);
        });
      }
    });
  });
}

// Swipe logic
if(leftArrow && rightArrow && floatThumbs.length) {
  leftArrow.addEventListener('click', () => {
    currentIndex = (currentIndex - 1 + floatThumbs.length) % floatThumbs.length;
    floatThumbs[currentIndex].click();
  });
  rightArrow.addEventListener('click', () => {
    currentIndex = (currentIndex + 1) % floatThumbs.length;
    floatThumbs[currentIndex].click();
  });
}

// Quantity & Cart Logic
let quantity = 1;
const minQty = 1;
const maxQty = 10;
if(minusBtn && plusBtn && cartNumber) {
  minusBtn.addEventListener('click', () => {
    if(quantity > minQty) quantity--;
    cartNumber.textContent = quantity;
  });
  plusBtn.addEventListener('click', () => {
    if(quantity < maxQty) quantity++;
    cartNumber.textContent = quantity;
  });
}
// On page load, update cart number and badge from localStorage
if (cartNumber) cartNumber.textContent = quantity;
const updateCartUI = () => {
  // Update badge in navbar
  if (navCartBadge) {
    navCartBadge.textContent = cartCount > 0 ? cartCount : '';
    navCartBadge.classList.toggle('show', cartCount > 0);
  }
  // Update cart number near quantity selector
  if (cartNumber) cartNumber.textContent = quantity;
};
updateCartUI();

// Add to Cart
if(addCartBtn && cartAfter && cartBox && emptyCart) {
  addCartBtn.addEventListener('click', () => {
    cartCount += quantity;
    localStorage.setItem('cartCount', cartCount);
    if (cartAfter) cartAfter.textContent = cartCount;
    emptyCart.classList.remove('show');
    // Show cart and update content (for demo, just update count)
    cartBox.classList.add('show');
    updateCartUI();
    // Optionally, show a visual feedback (e.g. animation)
  });
}
// Checkout button logic (for demo)
if(cartCheckout) {
  cartCheckout.addEventListener('click', () => {
    alert('Checkout process!');
  });
}

// الكود النظيف بدون تكرار
// ========== MAIN IMAGE THUMBNAILS ==========
if (thumbs && thumbs.length && mainImg) {
  thumbs.forEach((thumb, i) => {
    thumb.addEventListener("click", () => {
      thumbs.forEach((el) => el.classList.remove("active-thumb"));
      thumb.classList.add("active-thumb");
      const imgTag = thumb.querySelector("img");
      if (imgTag) mainImg.src = getProductImgPath(imgTag.src);
      // sync float thumbs
      if (floatThumbs && floatThumbs.length) {
        floatThumbs.forEach((ft, fi) => {
          ft.classList.toggle("active-thumb", fi === i);
        });
      }
      window.currentIndex = i;
    });
  });
}
// ========== FLOAT IMAGE THUMBNAILS ==========
if (floatThumbs && floatThumbs.length && mainImgFloat) {
  floatThumbs.forEach((thumb, i) => {
    thumb.addEventListener("click", () => {
      floatThumbs.forEach((el) => el.classList.remove("active-thumb"));
      thumb.classList.add("active-thumb");
      window.currentIndex = i;
      const imgTag = thumb.querySelector("img");
      if (imgTag) mainImgFloat.src = getProductImgPath(imgTag.src);
      // sync main thumbs
      if (thumbs && thumbs.length) {
        thumbs.forEach((mt, mi) => {
          mt.classList.toggle("active-thumb", mi === i);
        });
      }
    });
  });
}
// ========== CART FUNCTIONALITY & ITEM COUNT ==========
if (minusBtn && cartNumber) {
  minusBtn.addEventListener("click", () => {
    if (cartCount > 0) {
      cartCount--;
      localStorage.setItem('cartCount', cartCount);
      cartNumber.textContent = cartCount;
      updateCartUI();
    }
  });
}
if (plusBtn && cartNumber) {
  plusBtn.addEventListener("click", () => {
    if (cartCount < 10) {
      cartCount++;
      localStorage.setItem('cartCount', cartCount);
      cartNumber.textContent = cartCount;
      updateCartUI();
    }
  });
}
if (navCart && cartBox) {
  navCart.addEventListener("click", () => {
    cartBox.classList.toggle("show");
  });
}
document.addEventListener("keydown", (e) => {
  if (e.key === "Escape" && cartBox) cartBox.classList.remove("show");
});
if (btnRight && cartNumber && emptyCart && filledCart && numberCart && navCartBadge && currentPrice) {
  btnRight.addEventListener("click", () => {
    if (cartCount > 0 && totalItems + cartCount <= 20) {
      totalItems += cartCount;
      emptyCart.classList.remove("show");
      filledCart.classList.add("show");
      currentPrice.textContent = `$${totalItems * 125}.00`;
      numberCart.textContent = totalItems;
      navCartBadge.classList.add("show");
      navCartBadge.textContent = totalItems === 20 ? "full" : totalItems;
    }
  });
}
if (deleteIcon && emptyCart && filledCart && navCartBadge) {
  deleteIcon.addEventListener("click", () => {
    totalItems = 0;
    emptyCart.classList.add("show");
    filledCart.classList.remove("show");
    navCartBadge.classList.remove("show");
  });
}
// ========== CLICK OUTSIDE TO CLOSE CART ==========
if (cartBox) {
  document.body.addEventListener("click", (e) => {
    if (!cartBox.classList.contains("show")) return;
    const clickable = [btnRight, btnLeft, navCart, cartBox].filter(Boolean);
    if (!clickable.includes(e.target) && !cartBox.contains(e.target)) {
      cartBox.classList.remove("show");
    }
  });
}
=======
// DOM Elements
const mainImg = document.querySelector(".main-img");
const floatImgContainer = document.querySelector(".floating-img");
const floatMainImg = document.querySelector(".main-img-float");
const overlay = document.querySelector(".overlay");
const closeBtn = document.querySelector(".close-icon");

const thumbs = document.querySelectorAll(".thumb-img");
const floatThumbs = document.querySelectorAll(".thumb-img-float");

const leftArrow = document.querySelector(".btn-swipe-left");
const rightArrow = document.querySelector(".btn-swipe-right");

const minusBtn = document.querySelector(".minus");
const plusBtn = document.querySelector(".plus");
const cartNumber = document.querySelector(".cart-number");

const navCart = document.querySelector(".nav-cart");
const cartBox = document.querySelector(".cart");
const emptyCart = document.querySelector(".empty-cart");
const filledCart = document.querySelector(".cart-bottom");
const numberCart = document.querySelector(".count-items");
const currentPrice = document.querySelector(".current-price");
const deleteIcon = document.querySelector(".delete");
const navCartBadge = document.querySelector(".nav-cart-after");

const btnLeft = document.querySelector(".btn-left");
const btnRight = document.querySelector(".btn-right");

let currentIndex = 0;
let cartCount = 0;
let totalItems = 0;

// ========== FLOAT IMAGE LOGIC ========== //
mainImg.addEventListener("click", () => {
  floatImgContainer.classList.add("activate");
  overlay.classList.add("activate");

  floatMainImg.src = mainImg.src;

  floatThumbs.forEach((thumb, i) => {
    thumb.classList.remove("active-thumb");
    if (thumbs[i].classList.contains("active-thumb")) {
      thumb.classList.add("active-thumb");
      currentIndex = i;
    }
  });
});

const closeFloatImg = () => {
  floatImgContainer.classList.remove("activate");
  overlay.classList.remove("activate");
};

closeBtn.addEventListener("click", closeFloatImg);
overlay.addEventListener("click", closeFloatImg);
document.addEventListener("keydown", (e) => {
  if (e.key === "Escape") closeFloatImg();
});

// ========== MAIN IMAGE THUMBNAILS ========== //
thumbs.forEach((thumb, i) => {
  thumb.addEventListener("click", () => {
    thumbs.forEach((el) => el.classList.remove("active-thumb"));
    thumb.classList.add("active-thumb");

    const src = thumb.querySelector("img").getAttribute("src");
    const imgIndex = src.match(/image-product-(\d)/)[1];
    mainImg.src = `images/image-product-${imgIndex}.jpg`;
  });
});

// ========== FLOAT IMAGE THUMBNAILS ========== //
floatThumbs.forEach((thumb, i) => {
  thumb.addEventListener("click", () => {
    floatThumbs.forEach((el) => el.classList.remove("active-thumb"));
    thumb.classList.add("active-thumb");
    currentIndex = i;

    const src = thumb.querySelector("img").getAttribute("src");
    const imgIndex = src.match(/image-product-(\d)/)[1];
    floatMainImg.src = `images/image-product-${imgIndex}.jpg`;
  });
});

// ========== SWIPE FLOAT IMAGE ========== //
const updateFloatImage = (direction) => {
  const max = floatThumbs.length - 1;
  if ((direction === "left" && currentIndex > 0) || (direction === "right" && currentIndex < max)) {
    floatThumbs[currentIndex].classList.remove("active-thumb");
    currentIndex += direction === "right" ? 1 : -1;
    floatThumbs[currentIndex].classList.add("active-thumb");
    floatMainImg.src = `images/image-product-${currentIndex + 1}.jpg`;
  }
};

leftArrow.addEventListener("click", () => updateFloatImage("left"));
rightArrow.addEventListener("click", () => updateFloatImage("right"));

// ========== CART ITEM COUNT ========== //
minusBtn.addEventListener("click", () => {
  if (cartCount > 0) {
    cartCount--;
    cartNumber.textContent = cartCount;
  }
});

plusBtn.addEventListener("click", () => {
  if (cartCount < 10) {
    cartCount++;
    cartNumber.textContent = cartCount;
  }
});

// ========== CART FUNCTIONALITY ========== //
navCart.addEventListener("click", () => {
  cartBox.classList.toggle("show");
});

document.addEventListener("keydown", (e) => {
  if (e.key === "Escape") cartBox.classList.remove("show");
});

btnRight.addEventListener("click", () => {
  if (cartCount > 0 && totalItems + cartCount <= 20) {
    totalItems += cartCount;
    emptyCart.classList.remove("show");
    filledCart.classList.add("show");

    currentPrice.textContent = `$${totalItems * 125}.00`;
    numberCart.textContent = totalItems;
    navCartBadge.classList.add("show");
    navCartBadge.textContent = totalItems === 20 ? "full" : totalItems;
  }
});

deleteIcon.addEventListener("click", () => {
  totalItems = 0;
  emptyCart.classList.add("show");
  filledCart.classList.remove("show");
  navCartBadge.classList.remove("show");
});

// ========== CLICK OUTSIDE TO CLOSE CART ========== //
document.body.addEventListener("click", (e) => {
  if (!cartBox.classList.contains("show")) return;

  if (![btnRight, btnLeft, navCart, cartBox].includes(e.target) && !cartBox.contains(e.target)) {
    cartBox.classList.remove("show");
  }
});
>>>>>>> b518cc1015a9d72cbb3066bc69a8cd0496751b80
