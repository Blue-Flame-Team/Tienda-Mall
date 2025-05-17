/**
 * Home page specific JavaScript for Tienda Mall
 * Loads featured products, new arrivals, and flash sale items
 */

document.addEventListener('DOMContentLoaded', () => {
    // Load products for each section
    loadFeaturedProducts();
    loadNewArrivals();
    loadFlashSales();
    
    // Load categories for footer
    loadCategories('footer-categories');
});

/**
 * Load featured products
 */
async function loadFeaturedProducts() {
    const container = document.getElementById('featured-products');
    if (!container) return;
    
    try {
        const response = await api.products.getAll({ featured: 'true' }, 1, 8);
        
        if (response.status === 'success' && response.data && response.data.products) {
            const products = response.data.products;
            
            if (products.length === 0) {
                container.innerHTML = '<p class="no-products">No featured products available</p>';
                return;
            }
            
            let html = '';
            products.forEach(product => {
                html += createProductCard(product);
            });
            
            container.innerHTML = html;
        } else {
            container.innerHTML = '<p class="error">Failed to load featured products</p>';
        }
    } catch (error) {
        console.error('Error loading featured products:', error);
        container.innerHTML = '<p class="error">Error loading products</p>';
    }
}

/**
 * Load new arrivals
 */
async function loadNewArrivals() {
    const container = document.getElementById('new-products');
    if (!container) return;
    
    try {
        // Sort by created_at desc to get newest products
        const response = await api.products.getAll({ sort: 'created_at:desc' }, 1, 8);
        
        if (response.status === 'success' && response.data && response.data.products) {
            const products = response.data.products;
            
            if (products.length === 0) {
                container.innerHTML = '<p class="no-products">No new products available</p>';
                return;
            }
            
            let html = '';
            products.forEach(product => {
                html += createProductCard(product);
            });
            
            container.innerHTML = html;
        } else {
            container.innerHTML = '<p class="error">Failed to load new products</p>';
        }
    } catch (error) {
        console.error('Error loading new products:', error);
        container.innerHTML = '<p class="error">Error loading products</p>';
    }
}

/**
 * Load flash sale items
 */
async function loadFlashSales() {
    const container = document.getElementById('sale-products');
    if (!container) return;
    
    try {
        // Get products with discounts
        const response = await api.products.getAll({ 
            discount: 'true', 
            sort: 'discount_percent:desc' 
        }, 1, 8);
        
        if (response.status === 'success' && response.data && response.data.products) {
            const products = response.data.products;
            
            if (products.length === 0) {
                container.innerHTML = '<p class="no-products">No sale products available</p>';
                return;
            }
            
            let html = '';
            products.forEach(product => {
                html += createProductCard(product);
            });
            
            container.innerHTML = html;
        } else {
            container.innerHTML = '<p class="error">Failed to load sale products</p>';
        }
    } catch (error) {
        console.error('Error loading sale products:', error);
        container.innerHTML = '<p class="error">Error loading products</p>';
    }
}
