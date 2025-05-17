/**
 * Script para corregir problemas con el carrito
 * Este archivo reemplaza referencias a funciones no definidas
 */

// Aseguramos que updateCartBadge siempre esté definido y use updateCartCounter
if (typeof updateCartBadge === 'undefined') {
  function updateCartBadge() {
    if (window.cartAPI && window.cartAPI.updateCartCounter) {
      window.cartAPI.updateCartCounter();
    }
  }
}

// Definimos una función para corregir rutas de imágenes
function fixImagePaths() {
  // Seleccionar todas las imágenes que tienen rutas duplicadas
  const images = document.querySelectorAll('img[src^="/Tienda/"]');
  images.forEach(img => {
    // Eliminar el prefijo /Tienda/ del src
    let src = img.getAttribute('src');
    if (src.startsWith('/Tienda/')) {
      src = src.replace('/Tienda/', '/');
      img.setAttribute('src', src);
    }
  });
  
  // Corregir rutas que empiezan con http://localhost/Tienda/http://
  const badImages = document.querySelectorAll('img[src*="http://localhost/Tienda/http://"]');
  badImages.forEach(img => {
    let src = img.getAttribute('src');
    if (src.includes('http://localhost/Tienda/http://')) {
      src = src.replace('http://localhost/Tienda/http://', 'http://');
      img.setAttribute('src', src);
    }
  });
}

// Ejecutar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
  // Corregir rutas de imágenes
  fixImagePaths();
  
  // Intentar corregir problemas del carrito
  if (window.cartAPI && window.cartAPI.updateCartCounter) {
    window.cartAPI.updateCartCounter();
  }
});
