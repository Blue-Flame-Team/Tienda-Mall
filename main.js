// Example product data; replace or extend as needed
const products = [
  {
    name: "HAVIT HV-G92 Gamepad",
    price: 120,
    oldPrice: 160,
    discount: 40,
    image: "images/voucher 1.PNG",
    rating: 5,
    reviews: 88,
    section: "flash-sales"
  },
  {
    name: "AK-900 Wired Keyboard",
    price: 960,
    oldPrice: 1160,
    discount: 35,
    image: "images/voucher 1.PNG",
    rating: 5,
    reviews: 75,
    section: "flash-sales"
  },
  {
    name: "IPS LCD Gaming Monitor",
    price: 370,
    oldPrice: 400,
    discount: 30,
    image: "images/voucher 1.PNG",
    rating: 5,
    reviews: 99,
    section: "flash-sales"
  },
  {
    name: "S-Series Comfort Chair",
    price: 375,
    oldPrice: 400,
    discount: 25,
    image: "images/voucher 1.PNG",
    rating: 5,
    reviews: 99,
    section: "flash-sales"
  }
  // Add more products and sections as needed
];

function getStars(rating) {
  return "★".repeat(rating) + "☆".repeat(5 - rating);
}

function renderProducts(section) {
  const container = document.querySelector(`.${section} .product-carousel`);
  if (!container) return;
  container.innerHTML = "";
  products.filter(p => p.section === section).forEach(product => {
    container.innerHTML += `
      <div class="product-card">
        <img src="${product.image}" alt="${product.name}">
        ${product.discount ? `<span class="discount">-${product.discount}%</span>` : ""}
        <h4>${product.name}</h4>
        <div class="price"><span>$${product.price}</span> ${product.oldPrice ? `<del>$${product.oldPrice}</del>` : ""}</div>
        <div class="stars">${getStars(product.rating)} <span>(${product.reviews})</span></div>
      </div>
    `;
  });
}

document.addEventListener("DOMContentLoaded", function() {
  // Hide loader after page is fully loaded
  setTimeout(function() {
    document.getElementById('page-loader').classList.add('hide');
  }, 2300); // Show loader for 2.3 seconds
  renderProducts("flash-sales");
  // Add more calls for other sections as you add them to the products array
});
