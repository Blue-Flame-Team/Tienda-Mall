/**
 * Tienda Mall API Client
 * Handles all API interactions between frontend and backend
 */

const API_URL = '/api';  // Points to the API directory for all AJAX requests

// API Helper Functions
const api = {
    // Authentication methods
    auth: {
        /**
         * Log in a user
         * @param {string} email - User email
         * @param {string} password - User password
         * @returns {Promise} - Response from API
         */
        login: async (email, password) => {
            return await api.post('/auth', { 
                action: 'login',
                email, 
                password 
            });
        },

        /**
         * Register a new user
         * @param {Object} userData - User registration data
         * @returns {Promise} - Response from API
         */
        register: async (userData) => {
            return await api.post('/auth', { 
                action: 'register',
                ...userData 
            });
        },

        /**
         * Log out the current user
         * @returns {Promise} - Response from API
         */
        logout: async () => {
            return await api.post('/auth', { action: 'logout' });
        },

        /**
         * Check if user is logged in
         * @returns {Promise} - Response from API with user info
         */
        checkStatus: async () => {
            return await api.get('/auth?action=check');
        }
    },

    // Product methods
    products: {
        /**
         * Get all products with optional filters
         * @param {Object} filters - Optional filters
         * @param {number} page - Page number
         * @param {number} limit - Items per page
         * @returns {Promise} - Response from API
         */
        getAll: async (filters = {}, page = 1, limit = 12) => {
            let queryParams = new URLSearchParams();
            queryParams.append('page', page);
            queryParams.append('limit', limit);
            
            // Add filters to query parameters
            Object.keys(filters).forEach(key => {
                if (filters[key] !== null && filters[key] !== undefined) {
                    queryParams.append(key, filters[key]);
                }
            });
            
            return await api.get(`/products?${queryParams.toString()}`);
        },

        /**
         * Get product by ID
         * @param {number} id - Product ID
         * @returns {Promise} - Response from API
         */
        getById: async (id) => {
            return await api.get(`/products/${id}`);
        },

        /**
         * Get product reviews
         * @param {number} id - Product ID
         * @returns {Promise} - Response from API
         */
        getReviews: async (id) => {
            return await api.get(`/products/${id}/reviews`);
        }
    },

    // Cart methods
    cart: {
        /**
         * Get cart contents
         * @returns {Promise} - Response from API
         */
        get: async () => {
            return await api.get('/cart');
        },

        /**
         * Add item to cart
         * @param {number} productId - Product ID to add
         * @param {number} quantity - Quantity to add
         * @returns {Promise} - Response from API
         */
        addItem: async (productId, quantity = 1) => {
            return await api.post('/cart', {
                action: 'add',
                product_id: productId,
                quantity
            });
        },

        /**
         * Update cart item quantity
         * @param {number} itemId - Cart item ID
         * @param {number} quantity - New quantity
         * @returns {Promise} - Response from API
         */
        updateItem: async (itemId, quantity) => {
            return await api.put('/cart', {
                action: 'update',
                item_id: itemId,
                quantity
            });
        },

        /**
         * Remove item from cart
         * @param {number} itemId - Cart item ID to remove
         * @returns {Promise} - Response from API
         */
        removeItem: async (itemId) => {
            return await api.delete(`/cart/${itemId}`);
        },

        /**
         * Clear cart
         * @returns {Promise} - Response from API
         */
        clear: async () => {
            return await api.post('/cart', { action: 'clear' });
        }
    },

    // Order methods
    orders: {
        /**
         * Get user orders
         * @returns {Promise} - Response from API
         */
        getAll: async () => {
            return await api.get('/orders');
        },

        /**
         * Get order by ID
         * @param {number} id - Order ID
         * @returns {Promise} - Response from API
         */
        getById: async (id) => {
            return await api.get(`/orders/${id}`);
        },

        /**
         * Create a new order
         * @param {Object} orderData - Order data
         * @returns {Promise} - Response from API
         */
        create: async (orderData) => {
            return await api.post('/orders', orderData);
        }
    },

    // Base HTTP methods
    get: async (endpoint) => {
        try {
            const response = await fetch(`${API_URL}${endpoint}`, {
                method: 'GET',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            return await response.json();
        } catch (error) {
            console.error('API Error:', error);
            return {
                status: 'error',
                message: 'Network error',
                data: null
            };
        }
    },

    post: async (endpoint, data) => {
        try {
            const response = await fetch(`${API_URL}${endpoint}`, {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });
            return await response.json();
        } catch (error) {
            console.error('API Error:', error);
            return {
                status: 'error',
                message: 'Network error',
                data: null
            };
        }
    },

    put: async (endpoint, data) => {
        try {
            const response = await fetch(`${API_URL}${endpoint}`, {
                method: 'PUT',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });
            return await response.json();
        } catch (error) {
            console.error('API Error:', error);
            return {
                status: 'error',
                message: 'Network error',
                data: null
            };
        }
    },

    delete: async (endpoint) => {
        try {
            const response = await fetch(`${API_URL}${endpoint}`, {
                method: 'DELETE',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            return await response.json();
        } catch (error) {
            console.error('API Error:', error);
            return {
                status: 'error',
                message: 'Network error',
                data: null
            };
        }
    }
};

// Export API client
window.api = api;
