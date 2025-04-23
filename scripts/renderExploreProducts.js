// توليد كروت قسم Explore Our Products تلقائياً من بيانات productsData.js
window.addEventListener('DOMContentLoaded', function() {
  if (!window.productsData) return;
  var grid = document.querySelector('.products-grid');
  if (!grid) return;
  grid.innerHTML = '';
  window.productsData.forEach(function(product) {
    var card = document.createElement('a');
    card.className = 'product-card';
    card.href = 'pages/ProductDetails.html?id=' + product.id;

    // شارة جديد إذا أردت تمييز منتج
    if (product.id === '1') {
      var badge = document.createElement('span');
      badge.className = 'product-badge new';
      badge.textContent = 'NEW';
      card.appendChild(badge);
    }

    var iconsRow = document.createElement('div');
    iconsRow.className = 'product-card-icons-row';
    iconsRow.innerHTML = '<button class="icon-btn wishlist-icon" title="Add to Wishlist"><i class="fa fa-heart"></i></button>' +
      '<button class="icon-btn view-icon" title="Quick View"><i class="fa fa-eye"></i></button>';
    card.appendChild(iconsRow);

    var img = document.createElement('img');
    img.src = product.images[0];
    img.alt = product.title;
    card.appendChild(img);

    var details = document.createElement('div');
    details.className = 'product-details';

    var title = document.createElement('div');
    title.className = 'product-title';
    title.textContent = product.title;
    details.appendChild(title);

    var priceRating = document.createElement('div');
    priceRating.className = 'product-price-rating';
    priceRating.innerHTML = '<span class="price">$' + product.price + '</span>';
    details.appendChild(priceRating);

    card.appendChild(details);
    grid.appendChild(card);
  });
});
