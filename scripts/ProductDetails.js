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
