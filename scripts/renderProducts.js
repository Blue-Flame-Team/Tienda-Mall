// هذا السكريبت يقوم بتوليد كروت المنتجات تلقائياً في الصفحة الرئيسية من ملف productsData.js
window.addEventListener('DOMContentLoaded', function() {
  if (!window.productsData) return;
  // حدد مكان عرض الكروت (مثلاً قسم best-selling)
  var container = document.querySelector('.best-selling-carousel');
  if (!container) return;
  container.innerHTML = '';
  window.productsData.forEach(function(product) {
    var a = document.createElement('a');
    a.className = 'product-card best';
    a.href = 'pages/ProductDetails.html?id=' + product.id;

    // زرار Add To Cart خارج a
    var addCartBtn = document.createElement('button');
    addCartBtn.className = 'add-cart-btn';
    addCartBtn.innerHTML = '<i class="fa fa-cart-plus"></i>';
    addCartBtn.title = 'Add to Cart';
    addCartBtn.addEventListener('click', function(e) {
      e.stopPropagation();
      e.preventDefault();
      let cartCount = parseInt(localStorage.getItem('cartCount')) || 0;
      cartCount++;
      localStorage.setItem('cartCount', cartCount);
      const badge = document.querySelector('.nav-cart-after');
      if (badge) {
        badge.textContent = cartCount;
        badge.style.display = 'inline-block';
      }
      alert('تمت إضافة المنتج إلى العربة!');
    });

    var iconsRow = document.createElement('div');
    iconsRow.className = 'product-card-icons-row';
    iconsRow.innerHTML = '<button class="icon-btn wishlist-icon" title="Add to Wishlist"><i class="fa fa-heart"></i></button>' +
      '<button class="icon-btn view-icon" title="View Product"><i class="fa fa-eye"></i></button>';
    a.appendChild(iconsRow);

    var img = document.createElement('img');
    img.src = product.images[0];
    img.alt = product.title;
    a.appendChild(img);

    var title = document.createElement('div');
    title.className = 'product-title';
    title.textContent = product.title;
    a.appendChild(title);

    var desc = document.createElement('div');
    desc.className = 'product-desc';
    desc.textContent = product.desc;
    a.appendChild(desc);

    var priceRow = document.createElement('div');
    priceRow.className = 'product-price-row';
    priceRow.innerHTML = '<span class="product-price">$' + product.price + '</span>' +
      '<span class="product-old-price">$' + product.oldPrice + '</span>';
    a.appendChild(priceRow);

    container.appendChild(a);
  });
});
