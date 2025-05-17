-- MySQL version of the database schema for Tienda Mall

CREATE DATABASE IF NOT EXISTS tienda_mall;
USE tienda_mall;

-- Core Tables

-- Users table for customer accounts
CREATE TABLE users ( 
  user_id INT PRIMARY KEY AUTO_INCREMENT, 
  profile_image VARCHAR(255), 
  email_verified VARCHAR(3) DEFAULT 'NO', 
  verification_token VARCHAR(64), 
  password_hash CHAR(60) NOT NULL, 
  reset_token VARCHAR(64), 
  reset_token_expiry DATETIME DEFAULT CURRENT_TIMESTAMP,
  email VARCHAR(100) UNIQUE NOT NULL,
  first_name VARCHAR(80) NOT NULL,
  last_name VARCHAR(80) NOT NULL,
  phone VARCHAR(20) UNIQUE,
  date_of_birth DATE,
  is_active VARCHAR(3) DEFAULT 'YES',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  last_login DATETIME
);

-- Shipping addresses table for user addresses
CREATE TABLE shipping_addresses(
  address_id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  full_name VARCHAR(255) NOT NULL,
  city VARCHAR(80) NOT NULL,
  address_line1 VARCHAR(255) NOT NULL,
  address_line2 VARCHAR(255), 
  phone VARCHAR(20) NOT NULL,
  is_default TINYINT(1) DEFAULT 0,
  country VARCHAR(100) NOT NULL, 
  postal_code VARCHAR(20) NOT NULL,
  state VARCHAR(100) NOT NULL,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Categories table with hierarchical structure
CREATE TABLE category (
  category_id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  description TEXT,
  parent_id INT,
  image_url VARCHAR(255),
  is_active VARCHAR(3) DEFAULT 'YES',
  is_featured VARCHAR(3) DEFAULT 'NO',
  sort_order INT DEFAULT 0,
  seo_title VARCHAR(100),
  seo_description VARCHAR(255),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (parent_id) REFERENCES category(category_id) ON DELETE SET NULL
);

-- Products table for main product information
CREATE TABLE product (
  product_id INT PRIMARY KEY AUTO_INCREMENT,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  price DECIMAL(10,2) NOT NULL,
  cost_price DECIMAL(10,2),
  old_price DECIMAL(10,2),
  sku VARCHAR(50) UNIQUE,
  quantity INT NOT NULL DEFAULT 0,
  is_featured CHAR(3) DEFAULT 'YES',
  is_on_sale CHAR(3) DEFAULT 'NO',
  is_new CHAR(3) DEFAULT 'NO',
  is_best_seller CHAR(3) DEFAULT 'NO',
  is_active CHAR(1) DEFAULT 'Y',
  image_url VARCHAR(255),
  weight DECIMAL(8,2),
  dimensions VARCHAR(50),
  rating DECIMAL(3,2) DEFAULT 0,
  review_count INT DEFAULT 0,
  seo_title VARCHAR(100),
  seo_description VARCHAR(255),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Junction table for product-category many-to-many relationship
CREATE TABLE product_category (
  product_id INT NOT NULL,
  category_id INT NOT NULL,
  PRIMARY KEY (product_id, category_id),
  FOREIGN KEY (product_id) REFERENCES product(product_id) ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES category(category_id) ON DELETE CASCADE
);

-- Product images table for multiple images per product
CREATE TABLE product_image (
  image_id INT PRIMARY KEY AUTO_INCREMENT,
  product_id INT NOT NULL,
  image_url VARCHAR(255) NOT NULL,
  is_primary CHAR(3) DEFAULT 'YES',
  sort_order INT DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES product(product_id) ON DELETE CASCADE
);

-- Orders table for order processing
CREATE TABLE orders (
  order_id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  order_date DATETIME DEFAULT CURRENT_TIMESTAMP,
  status VARCHAR(20) NOT NULL DEFAULT 'Pending',
  subtotal DECIMAL(10,2) NOT NULL,
  shipping_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  tax_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  total_amount DECIMAL(10,2) NOT NULL,
  payment_method VARCHAR(50),
  payment_status VARCHAR(20) DEFAULT 'Pending',
  notes TEXT,
  tracking_number VARCHAR(100),
  shipping_provider VARCHAR(100),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE RESTRICT
);

-- Order items table for items in each order
CREATE TABLE order_items (
  item_id INT PRIMARY KEY AUTO_INCREMENT,
  order_id INT NOT NULL,
  product_id INT NOT NULL,
  quantity INT NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  discount DECIMAL(10,2) DEFAULT 0,
  variation TEXT,
  FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES product(product_id) ON DELETE RESTRICT
);

-- Order shipping table for shipping information
CREATE TABLE order_shipping (
  shipping_id INT PRIMARY KEY AUTO_INCREMENT,
  order_id INT NOT NULL UNIQUE,
  full_name VARCHAR(255) NOT NULL,
  address_line1 VARCHAR(255) NOT NULL,
  address_line2 VARCHAR(255),
  city VARCHAR(100) NOT NULL,
  state VARCHAR(100) NOT NULL,
  postal_code VARCHAR(20) NOT NULL,
  country VARCHAR(100) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  shipping_method VARCHAR(50),
  FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE
);

-- Order status history
CREATE TABLE order_status (
  status_id INT PRIMARY KEY AUTO_INCREMENT,
  order_id INT NOT NULL,
  status VARCHAR(20) NOT NULL,
  notes TEXT,
  status_date DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE
);

-- Reviews table for product ratings and reviews
CREATE TABLE review (
  review_id INT PRIMARY KEY AUTO_INCREMENT,
  product_id INT NOT NULL,
  user_id INT NOT NULL,
  rating INT NOT NULL,
  title VARCHAR(255),
  review_text TEXT,
  is_approved VARCHAR(3) DEFAULT 'NO',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES product(product_id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Admin users table
CREATE TABLE admins (
  admin_id INT PRIMARY KEY AUTO_INCREMENT,
  email VARCHAR(100) UNIQUE NOT NULL,
  first_name VARCHAR(80) NOT NULL,
  last_name VARCHAR(80) NOT NULL,
  password CHAR(60) NOT NULL,
  role VARCHAR(20) DEFAULT 'editor',
  status VARCHAR(20) DEFAULT 'active',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  last_login DATETIME
);

-- Admin logs for activity tracking
CREATE TABLE admin_logs (
  log_id INT PRIMARY KEY AUTO_INCREMENT,
  admin_id INT NOT NULL,
  action VARCHAR(255) NOT NULL,
  entity VARCHAR(50),
  entity_id INT,
  details TEXT,
  ip_address VARCHAR(45),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (admin_id) REFERENCES admins(admin_id) ON DELETE CASCADE
);

-- Coupons for promotional codes
CREATE TABLE coupons (
  coupon_id INT PRIMARY KEY AUTO_INCREMENT,
  code VARCHAR(50) UNIQUE NOT NULL,
  discount_type ENUM('percentage', 'fixed') NOT NULL,
  discount_amount DECIMAL(10,2) NOT NULL,
  minimum_order DECIMAL(10,2) DEFAULT 0,
  maximum_discount DECIMAL(10,2),
  start_date DATETIME,
  end_date DATETIME,
  usage_limit INT,
  usage_count INT DEFAULT 0,
  is_active TINYINT(1) DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Wishlist for users to save products
CREATE TABLE wishlist (
  wishlist_id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  product_id INT NOT NULL,
  added_date DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY (user_id, product_id),
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES product(product_id) ON DELETE CASCADE
);

-- Payment methods stored for users
CREATE TABLE payment_methods (
  payment_id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  payment_type VARCHAR(50) NOT NULL,
  provider VARCHAR(100) NOT NULL,
  account_number VARCHAR(255),
  expiry_date VARCHAR(10),
  is_default TINYINT(1) DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Insert an admin user for accessing the admin panel
INSERT INTO admins (email, first_name, last_name, password, role, status) 
VALUES ('admin@tiendamall.com', 'Admin', 'User', '$2y$10$9Gg8n6R5ej5UY5vGMpWnveGJ9m.gtLLpBH.UlyOI6lYuxpvVkYtXi', 'super_admin', 'active');
-- Default password is 'admin123'
