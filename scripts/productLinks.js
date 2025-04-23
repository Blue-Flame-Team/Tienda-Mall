// This script enables clicking on any product card (manual or dynamic) to open the ProductDetails.html
// It extracts product name (from h4 or .product-title), finds the product in the products array (from main.js),
// and navigates to the details page with the product name as a URL parameter

// Helper: slugify product name for URL
function slugify(text) {
  return text.toString().toLowerCase().replace(/\s+/g, '-')
    .replace(/[^\w\-]+/g, '')
    .replace(/\-\-+/g, '-')
    .replace(/^-+/, '').replace(/-+$/, '');
}

// Attach click handlers to all product cards
function attachProductCardLinks() {
  // Manual cards: .product-card inside .products-grid or .best-selling-carousel
  document.querySelectorAll('.product-card').forEach(card => {
    // Find product name
    let nameEl = card.querySelector('h4') || card.querySelector('.product-title');
    if (!nameEl) return;
    let productName = nameEl.textContent.trim();
    card.style.cursor = 'pointer';
    card.addEventListener('click', function(e) {
      // Prevent click if it's on a button or icon inside the card
      if (e.target.closest('button') || e.target.tagName === 'BUTTON' || e.target.tagName === 'I') return;
      // Go to details page with slug as param
      window.location.href = `pages/ProductDetails.html?name=${encodeURIComponent(slugify(productName))}`;
    });
  });
}

document.addEventListener('DOMContentLoaded', attachProductCardLinks);

// For dynamically rendered cards (e.g. Flash Sales), re-attach after render
if (window.renderProducts) {
  const origRenderProducts = window.renderProducts;
  window.renderProducts = function(...args) {
    origRenderProducts.apply(this, args);
    setTimeout(attachProductCardLinks, 0);
  };
}
