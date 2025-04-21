// Example product data; replace or extend as needed

// Scroll-to-top button show/hide logic
window.addEventListener('scroll', function() {
  const scrollBtn = document.querySelector('.scroll-to-top');
  if (!scrollBtn) return;
  if (window.scrollY > 200) {
    scrollBtn.classList.add('show');
  } else {
    scrollBtn.classList.remove('show');
  }
});

const products = [
  {
    name: "HAVIT HV-G92 Gamepad",
    price: 120,
    oldPrice: 160,
    discount: 40,
    image: "assets/products/product1.jpg",
    rating: 5,
    reviews: 88,
    section: "flash-sales"
  },
  {
    name: "AK-900 Wired Keyboard",
    price: 960,
    oldPrice: 1160,
    discount: 35,
    image: "assets/products/product2.jpg",
    rating: 5,
    reviews: 75,
    section: "flash-sales"
  },
  {
    name: "IPS LCD Gaming Monitor",
    price: 370,
    oldPrice: 400,
    discount: 30,
    image: "assets/products/product3.jpg",
    rating: 5,
    reviews: 99,
    section: "flash-sales"
  },
  {
    name: "S-Series Comfort Chair",
    price: 375,
    oldPrice: 400,
    discount: 25,
    image: "assets/products/product4.jpg",
    rating: 5,
    reviews: 99,
    section: "flash-sales"
  },
  {
    name: "Logitech G203 Mouse",
    price: 45,
    oldPrice: 53,
    discount: 15,
    image: "assets/products/product5.jpg",
    rating: 4,
    reviews: 64,
    section: "flash-sales"
  },
  {
    name: "RGB liquid CPU Cooler",
    price: 160,
    oldPrice: 170,
    discount: 6,
    image: "assets/products/product6.jpg",
    rating: 5,
    reviews: 65,
    section: "flash-sales"
  },
  {
    name: "Gucci duffle bag",
    price: 960,
    oldPrice: 1160,
    discount: 17,
    image: "assets/products/product7.jpg",
    rating: 5,
    reviews: 65,
    section: "flash-sales"
  },
  {
    name: "Kids Electric Car",
    price: 960,
    oldPrice: 1200,
    discount: 20,
    image: "assets/products/product8.jpg",
    rating: 5,
    reviews: 65,
    section: "flash-sales"
  }
];

function getStars(rating) {
  return "★".repeat(rating) + "☆".repeat(5 - rating);
}

// توزيع الصور على جميع المنتاجات
const NUM_IMAGES = 16; 
const imgBasePath = 'assets/products/product';
const imgExt = '.jpg';

products.forEach((product, idx) => {
  if (product.image && product.image.trim() !== "") {
    product._imgSrc = product.image;
  } else {
    let imgNum = idx + 1;
    if (imgNum > NUM_IMAGES) imgNum = NUM_IMAGES;
    product._imgSrc = `${imgBasePath}${imgNum}${imgExt}`;
  }
  // fallback image if not found
  if (!product._imgSrc) {
    product._imgSrc = 'https://dummyimage.com/150x150/cccccc/fff&text=No+Image';
  }
});

function renderProducts(section) {
  const container = document.querySelector(`.${section} .product-carousel`);
  if (!container) return;
  container.innerHTML = "";
  const sectionProducts = products.filter(p => p.section === section);
  if (sectionProducts.length === 0) {
    container.innerHTML = '<div style="color:red">No products found for this section</div>';
    return;
  }
  sectionProducts.forEach((product) => {
    const imgSrc = product._imgSrc || 'https://dummyimage.com/150x150/cccccc/fff&text=No+Image';
    container.innerHTML += `
      <div class="product-card">
        <img src="${imgSrc}" alt="${product.name}" onerror="this.src='https://dummyimage.com/150x150/cccccc/fff&text=No+Image'">
        ${product.discount ? `<span class="discount">-${product.discount}%</span>` : ""}
        <h4>${product.name}</h4>
        <div class="price"><span>$${product.price}</span> ${product.oldPrice ? `<del>$${product.oldPrice}</del>` : ""}</div>
        <div class="stars">${getStars(product.rating)} <span>(${product.reviews})</span></div>
      </div>
    `;
  });
}

let globalProductIndex = 0;
document.addEventListener("DOMContentLoaded", function() {
  // Hide loader after page is fully loaded
  setTimeout(function() {
    document.getElementById('page-loader').classList.add('hide');
  }, 2300); // Show loader for 2.3 seconds
  renderProducts("flash-sales");
  renderProducts("more-products");

  // Hero Banner Slider Logic
  const slides = document.querySelectorAll('.hero-slide');
  const dots = document.querySelectorAll('.hero-dot');
  function showSlide(idx) {
    slides.forEach((slide, i) => {
      slide.classList.toggle('active', i === idx);
    });
    dots.forEach((dot, i) => {
      dot.classList.toggle('active', i === idx);
    });
  }
  dots.forEach((dot, idx) => {
    dot.addEventListener('click', () => showSlide(idx));
  });
  
  // Optionally: auto-slide
  // let current = 0;
  // setInterval(() => {
  //   current = (current + 1) % slides.length;
  //   showSlide(current);
  // }, 7000);

  // Flash Sales Timer Logic
  // Flash Sales Timer Logic
  let future = null;
  function setFlashTimerTarget() {
    future = new Date();
    future.setDate(future.getDate() + 3); // 3 days from now
    future.setHours(23, 59, 59, 999);
  }
  function updateFlashTimer() {
    if (!future) setFlashTimerTarget();
    const now = new Date();
    let diff = Math.max(0, future - now);
    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
    diff -= days * (1000 * 60 * 60 * 24);
    const hours = Math.floor(diff / (1000 * 60 * 60));
    diff -= hours * (1000 * 60 * 60);
    const minutes = Math.floor(diff / (1000 * 60));
    diff -= minutes * (1000 * 60);
    const seconds = Math.floor(diff / 1000);
    document.getElementById('timer-days').textContent = days.toString().padStart(2, '0');
    document.getElementById('timer-hours').textContent = hours.toString().padStart(2, '0');
    document.getElementById('timer-minutes').textContent = minutes.toString().padStart(2, '0');
    document.getElementById('timer-seconds').textContent = seconds.toString().padStart(2, '0');
  }
  if (document.getElementById('flash-timer')) {
    setFlashTimerTarget();
    updateFlashTimer();
    setInterval(updateFlashTimer, 1000);
  }
});
