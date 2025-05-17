// Example product data; replace or extend as needed

document.addEventListener('DOMContentLoaded', function() {
  // Enable add-to-cart for Explore section
  document.querySelectorAll('.products-grid .add-to-cart-btn').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
      e.stopPropagation();
      var card = btn.closest('.product-card');
      if (!card) return;
      var nameEl = card.querySelector('.product-title');
      if (!nameEl) return;
      var productName = nameEl.textContent.trim();
      // Find product in products array
      var prod = (window.products || products).find(function(p) {
        return (p.name || p.title) === productName;
      });
      if (prod && window.cartAPI && window.cartAPI.addToCart) {
        window.cartAPI.addToCart({
          id: prod.id,
          name: prod.name || prod.title,
          price: prod.price,
          image: prod.image || prod._imgSrc || '',
        }, 1);
        // Update cart badge
        var badge = document.querySelector('.nav-cart-after');
        if (badge && window.cartAPI.getCartCount) {
          var count = window.cartAPI.getCartCount();
          badge.textContent = count;
          badge.style.display = count > 0 ? 'inline-block' : 'none';
        }
        alert('تم إضافة المنتج إلى العربة!');
      }
    });
  });
});

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
  // Flash Sales
  {
    id: "1",
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
    id: "2",
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
    id: "3",
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
    id: "4",
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
    id: "5",
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
    id: "6",
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
    id: "7",
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
    id: "8",
    name: "Kids Electric Car",
    price: 960,
    oldPrice: 1200,
    discount: 20,
    image: "assets/products/product8.jpg",
    rating: 5,
    reviews: 65,
    section: "flash-sales"
  },
  // Explore Our Products
  {
    id: "101",
    name: "Breed Dry Dog Food",
    price: 100,
    oldPrice: 120,
    discount: 17,
    image: "assets/images/dog-food.png",
    rating: 3,
    reviews: 35,
    section: "explore-products"
  },
  {
    id: "102",
    name: "CANON EOS DSLR Camera",
    price: 360,
    oldPrice: 400,
    discount: 10,
    image: "assets/images/camera.png",
    rating: 5,
    reviews: 95,
    section: "explore-products"
  },
  {
    id: "103",
    name: "ASUS FHD Gaming Laptop",
    price: 700,
    oldPrice: 800,
    discount: 12,
    image: "assets/images/laptop.png",
    rating: 5,
    reviews: 325,
    section: "explore-products"
  },
  {
    id: "104",
    name: "Curology Product Set",
    price: 500,
    oldPrice: 550,
    discount: 9,
    image: "assets/images/curology.png",
    rating: 4,
    reviews: 145,
    section: "explore-products"
  },
  {
    id: "105",
    name: "Kids Electric Car",
    price: 960,
    oldPrice: 1200,
    discount: 20,
    image: "assets/images/kids-car.png",
    rating: 5,
    reviews: 65,
    section: "explore-products"
  },
  {
    id: "106",
    name: "Jr. Zoom Soccer Cleats",
    price: 1160,
    oldPrice: 1300,
    discount: 11,
    image: "assets/images/cleats.png",
    rating: 5,
    reviews: 35,
    section: "explore-products"
  },
  {
    id: "107",
    name: "GP11 Shooter USB Gamepad",
    price: 660,
    oldPrice: 800,
    discount: 17,
    image: "assets/images/gamepad.png",
    rating: 5,
    reviews: 55,
    section: "explore-products"
  },
  {
    id: "108",
    name: "Quilted Satin Jacket",
    price: 660,
    oldPrice: 800,
    discount: 17,
    image: "assets/images/jacket.png",
    rating: 5,
    reviews: 55,
    section: "explore-products"
  }
];

function getStars(rating) {
  return "★".repeat(rating) + "☆".repeat(5 - rating);
}

// توزيع الصور على جميع المنتاجات
const NUM_IMAGES = 16; 
const imgBasePath = 'assets/products/product';
const imgExt = '.jpg';

// No longer assign _imgSrc or fallback images. All products must have a valid image in assets/products/.
products.forEach((product) => {
  // No-op: just keep product.image as-is
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
    const imgSrc = product.image;
    container.innerHTML += `
      <div class="product-card" data-id="${product.id}" style="cursor:pointer;">
        <img src="${imgSrc}" alt="${product.name}">
        ${product.discount ? `<span class="discount">-${product.discount}%</span>` : ""}
        <h4>${product.name}</h4>
        <div class="price"><span>$${product.price}</span> ${product.oldPrice ? `<del>$${product.oldPrice}</del>` : ""}</div>
        <div class="stars">${getStars(product.rating)} <span>(${product.reviews})</span></div>
        <button class="add-to-cart-btn styled-cart-btn" data-id="${product.id}"><i class="fa fa-shopping-cart"></i> Add to Cart</button>
      </div>
    `;
  });
  // Add event listeners for Add to Cart buttons
  // Event: فتح صفحة المنتج عند الضغط على الكارت وليس زر العربة
  container.querySelectorAll('.product-card').forEach(card => {
    card.addEventListener('click', function(e) {
      // إذا ضغط على زر العربة لا تفتح صفحة المنتج
      if (e.target.closest('.add-to-cart-btn')) return;
      const id = card.getAttribute('data-id');
      if (id) {
        window.location.href = `pages/ProductDetails.html?id=${id}`;
      }
    });
  });
  container.querySelectorAll('.add-to-cart-btn').forEach(btn => {
    btn.addEventListener('click', function() {
      const id = this.getAttribute('data-id');
      const prod = products.find(p => p.id == id);
      if (prod) {
        window.cartAPI.addToCart({
          id: prod.id,
          name: prod.name || prod.title,
          price: prod.price,
          image: prod._imgSrc || prod.images?.[0] || '',
        }, 1);
        // Update cart badge
        const badge = document.querySelector('.nav-cart-after');
        if (badge) {
          const count = window.cartAPI.getCartCount();
          badge.textContent = count;
          badge.style.display = count > 0 ? 'inline-block' : 'none';
        }
        alert('تم إضافة المنتج إلى العربة!');
      }
    });
  });
}

// Import cart.js for cart API

let globalProductIndex = 0;
document.addEventListener("DOMContentLoaded", function() {
  // Hide loader after page is fully loaded
  setTimeout(function() {
    var loader = document.getElementById('page-loader');
    if (loader) loader.classList.add('hide');
  }, 2300); // Show loader for 2.3 seconds
  renderProducts("flash-sales");
  renderProducts("explore-products");
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
