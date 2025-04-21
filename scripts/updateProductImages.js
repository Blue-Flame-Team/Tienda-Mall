// This script replaces all product images in .product-card with images from assets/products/
(function() {
  // Number of images you have in the assets/products folder
  const NUM_IMAGES = 8;
  const imgBasePath = 'assets/products/product';
  const imgExt = '.jpg';

  // Get all product card images
  const productImgs = document.querySelectorAll('.product-card img');
  // Shuffle image indices for variety
  let indices = Array.from({length: NUM_IMAGES}, (_, i) => i+1);
  for (let i = indices.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [indices[i], indices[j]] = [indices[j], indices[i]];
  }
  // Assign images
  productImgs.forEach((img, idx) => {
    const imgNum = indices[idx % NUM_IMAGES];
    img.src = imgBasePath + imgNum + imgExt;
    img.alt = 'Product Image ' + imgNum;
  });
})();
