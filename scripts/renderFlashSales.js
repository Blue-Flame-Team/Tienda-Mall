// توليد كروت قسم Flash Sales تلقائياً من بيانات productsData.js
window.addEventListener('DOMContentLoaded', function() {
  if (!window.productsData) return;
  var container = document.querySelector('.flash-sales .product-carousel');
  if (!container) return;
  container.innerHTML = '';
  window.productsData.forEach(function(product) {
    var a = document.createElement('a');
    a.className = 'product-card flash';
    a.href = 'pages/ProductDetails.html?id=' + product.id;

    var img = document.createElement('img');
    img.src = product.images[0];
    img.alt = product.title;
    a.appendChild(img);

    var title = document.createElement('div');
    title.className = 'product-title';
    title.textContent = product.title;
    a.appendChild(title);

    var priceRow = document.createElement('div');
    priceRow.className = 'product-price-row';
    priceRow.innerHTML = '<span class="product-price">$' + product.price + '</span>' +
      '<span class="product-old-price">$' + product.oldPrice + '</span>';
    a.appendChild(priceRow);

    container.appendChild(a);
  });
});
