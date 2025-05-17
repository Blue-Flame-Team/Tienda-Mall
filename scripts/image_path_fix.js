/**
 * image_path_fix.js - Universal image path handling
 * This file ensures that all image paths across the site are consistent
 */

// Function to normalize image paths throughout the site
function normalizeImagePaths() {
  // Fix image elements
  document.querySelectorAll('img[src]').forEach(img => {
    let src = img.getAttribute('src');
    
    // Fix duplicate /Tienda/ prefix
    if (src && src.startsWith('/Tienda/')) {
      src = src.replace('/Tienda/', '/');
      img.setAttribute('src', src);
    }
    
    // Fix paths with http://localhost/Tienda/http://
    if (src && src.includes('http://localhost/Tienda/http://')) {
      src = src.replace('http://localhost/Tienda/http://', 'http://');
      img.setAttribute('src', src);
    }
    
    // Handle relative paths for /pages/ directory
    if (window.location.pathname.includes('/pages/') && src) {
      if (src.startsWith('assets/')) {
        src = '../' + src;
        img.setAttribute('src', src);
      } else if (src.startsWith('/assets/')) {
        src = '..' + src;
        img.setAttribute('src', src);
      }
    }
    
    // Remove any double slashes (except after protocol)
    if (src) {
      src = src.replace(/:\/\//, '---PROTOCOL---')
              .replace(/\/\//g, '/')
              .replace(/---PROTOCOL---/, '://');
      img.setAttribute('src', src);
    }
  });
  
  // Fix any background images in style attributes
  document.querySelectorAll('[style*="background-image"]').forEach(el => {
    let style = el.getAttribute('style');
    if (style && style.includes('url(')) {
      // Fix duplicate /Tienda/ in background URLs
      if (style.includes('/Tienda/')) {
        style = style.replace(/url\(['"]?\/Tienda\//g, 'url(\'/');
        el.setAttribute('style', style);
      }
    }
  });
}

// Run when the DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
  normalizeImagePaths();
  
  // Also run after AJAX content might be loaded
  setTimeout(normalizeImagePaths, 1000);
});

// Expose the function globally
window.normalizeImagePaths = normalizeImagePaths;
