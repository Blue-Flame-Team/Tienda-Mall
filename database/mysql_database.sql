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
CREATE TABLE payment_method (
  payment_method_id INT PRIMARY KEY,
  expiry_date DATE,
  account_number_last4 INT, 
  provider VARCHAR(100),
  payment_type VARCHAR(255),
  is_default VARCHAR(3),
  user_id INT,
  FOREIGN KEY (user_id) REFERENCES users(user_id)
);

CREATE TABLE category(
  category_id INT PRIMARY KEY,
  image_url VARCHAR(150),
  is_active VARCHAR(3),
  name VARCHAR(70),
  description VARCHAR(110)
);

CREATE TABLE has_subcategory(
  category_id1 INT,
  subcategory_id2 INT,
  PRIMARY KEY (category_id1, subcategory_id2),
  FOREIGN KEY (category_id1) REFERENCES category(category_id),
  FOREIGN KEY (subcategory_id2) REFERENCES category(category_id)
);

CREATE TABLE product (
  product_id INT PRIMARY KEY,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  meta_description VARCHAR(120),
  meta_title VARCHAR(70),
  title VARCHAR(70) NOT NULL,
  sku VARCHAR(100) NOT NULL UNIQUE,
  description VARCHAR(500),
  price DECIMAL(10, 2) NOT NULL,
  cost_price DECIMAL(10, 2),
  is_active CHAR(1),
  weight DECIMAL(10, 2),
  width DECIMAL(10, 2),
  height DECIMAL(10, 2)
);

CREATE TABLE product_meta_keywords(
  meta_keywords VARCHAR(22),
  product_id INT,
  FOREIGN KEY (product_id) REFERENCES product(product_id)
);

CREATE TABLE categorized_in(
  category_id INT,
  product_id INT,
  PRIMARY KEY (category_id, product_id),
  FOREIGN KEY (category_id) REFERENCES category(category_id),
  FOREIGN KEY (product_id) REFERENCES product(product_id)
);

CREATE TABLE inventory (
  inventory_id INT PRIMARY KEY,
  low_stock_threshold INT,
  quantity INT,
  warehous_location VARCHAR(255),
  product_id INT,
  FOREIGN KEY (product_id) REFERENCES product(product_id)
);

CREATE TABLE product_image (
  image_id INT PRIMARY KEY,
  image_url VARCHAR(255),
  sort_order INT,
  is_primary VARCHAR(3),  
  product_id INT,
  FOREIGN KEY (product_id) REFERENCES product(product_id)
);

CREATE TABLE admin (
  admin_id INT PRIMARY KEY,
  profile_image VARCHAR(255),
  email VARCHAR(100),
  password_hash VARCHAR(60),
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  role VARCHAR(255) NOT NULL,
  is_active VARCHAR(3)
);

CREATE TABLE coupons (
  coupon_id INT PRIMARY KEY,
  is_active VARCHAR(3),
  max_uses INT,
  end_date DATE,
  start_date DATE,
  minimum_order_value INT,
  discount_value INT,
  discount_type VARCHAR(255),
  description VARCHAR(255),
  code VARCHAR(20) UNIQUE,
  admin_id INT,
  FOREIGN KEY (admin_id) REFERENCES admin(admin_id)
);

CREATE TABLE review(
  review_id INT PRIMARY KEY,
  rating VARCHAR(2) NOT NULL,
  coment VARCHAR(255),
  title VARCHAR(150),
  is_approved VARCHAR(3),
  user_id INT,
  product_id INT,
  FOREIGN KEY (user_id) REFERENCES users(user_id),
  FOREIGN KEY (product_id) REFERENCES product(product_id)
);

CREATE TABLE ordor (
  order_id INT PRIMARY KEY,
  status VARCHAR(80),
  notes VARCHAR(90),
  tracking_number VARCHAR(30), 
  billing_address_id INT,
  shipping_fee DECIMAL(10, 2) NOT NULL,
  user_id INT,
  payment_method_id INT,
  address_id INT,
  coupon_id INT,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
  FOREIGN KEY (payment_method_id) REFERENCES payment_method(payment_method_id),
  FOREIGN KEY (address_id) REFERENCES shipping_address(address_id),
  FOREIGN KEY (coupon_id) REFERENCES coupons(coupon_id)
);

CREATE TABLE order_item (
  order_item_id INT PRIMARY KEY,
  unit_price DECIMAL(10, 2),
  quantity INT,
  belongs_to_order_id INT,
  contains_item_order_id INT,
  FOREIGN KEY (belongs_to_order_id) REFERENCES ordor(order_id),
  FOREIGN KEY (contains_item_order_id) REFERENCES ordor(order_id)
);

CREATE TABLE included_in(
  product_id INT,
  order_item_id INT,
  PRIMARY KEY (product_id, order_item_id),
  FOREIGN KEY (product_id) REFERENCES product(product_id),
  FOREIGN KEY (order_item_id) REFERENCES order_item(order_item_id)
);

CREATE TABLE admin_logs (
  log_id INT PRIMARY KEY,
  entity_id INT,
  entity_type VARCHAR(255),
  action VARCHAR(120), 
  action_details VARCHAR(100),
  user_agent VARCHAR(90),
  ip_address VARCHAR(15),
  admin_id INT,
  FOREIGN KEY (admin_id) REFERENCES admin(admin_id)
);

-- Sample data for testing

-- Insert sample users
INSERT INTO users (user_id, profile_image, email_verified, verification_token, password_hash, reset_token, email, first_name, last_name, phone, date_of_birth, is_active)
VALUES 
(1001, 'profile1.jpg', 'YES', 1234, '$2y$10$abcdefghijklmnopqrstuv', 56789012, 'user1@example.com', 'John', 'Doe', '0123456789', '1990-01-01', 'YES'),
(1002, 'profile2.jpg', 'NO', 5678, '$2y$10$abcdefghijklmnopqrstuv', 12345678, 'user2@example.com', 'Jane', 'Smith', '0123456780', '1992-02-02', 'NO'),
(1003, 'profile3.jpg', 'YES', 9101, '$2y$10$abcdefghijklmnopqrstuv', 23456789, 'user3@example.com', 'Alice', 'Johnson', '0123456781', '1988-03-03', 'YES');

-- Insert sample addresses
INSERT INTO shipping_address (address_id, city, address_line1, address_line2, phone, is_defaultt, country, postal_co, state)
VALUES 
(2001, 'Alexandria', '123 Main St', 'Apt 4B', '1234567890', 'Y', 'Egypt', '12345', 'Alexandria'),
(2002, 'Cairo', '456 Elm St', 'Apt 2A', '2345678901', 'N', 'Egypt', '67890', 'Cairo'),
(2003, 'Giza', '789 Oak St', 'Apt 3C', '3456789012', 'N', 'Egypt', '11223', 'Giza');

-- Insert sample admin users
INSERT INTO admin (admin_id, profile_image, email, password_hash, first_name, last_name, role, is_active)
VALUES 
(6001, 'admin1.jpg', 'admin1@example.com', '$2y$10$abcdefghijklmnopqrstuv', 'Alice', 'Williams', 'Manager', 'Y'),
(6002, 'admin2.jpg', 'admin2@example.com', '$2y$10$abcdefghijklmnopqrstuv', 'Bob', 'Johnson', 'Supervisor', 'Y'),
(6003, 'admin3.jpg', 'admin3@example.com', '$2y$10$abcdefghijklmnopqrstuv', 'Charlie', 'Brown', 'Clerk', 'N');

-- Insert sample coupons
INSERT INTO coupons (coupon_id, is_active, max_uses, end_date, start_date, minimum_order_value, discount_value, discount_type, description, code, admin_id)
VALUES 
(5001, 'Y', 100, '2025-12-31', '2025-01-01', 100, 20, 'Percentage', '20% off on orders above $100', '1234567890', 6001),
(5002, 'N', 50, '2025-06-30', '2025-03-01', 50, 10, 'Fixed', '$10 off on orders above $50', '2345678901', 6002),
(5003, 'Y', 200, '2025-11-30', '2025-04-01', 150, 30, 'Percentage', '30% off on orders above $150', '3456789012', 6003);

-- Insert sample payment methods
INSERT INTO payment_method (payment_method_id, expiry_date, account_number_last4, provider, payment_type, is_default, user_id)
VALUES 
(1, '2026-12-31', 1234, 'Visa', 'Credit Card', 'Y', 1001),
(2, '2025-11-30', 5678, 'MasterCard', 'Debit Card', 'N', 1002),
(3, '2027-10-15', 9101, 'PayPal', 'Online', 'N', 1003);

-- Insert sample categories
INSERT INTO category (category_id, image_url, is_active, name, description)
VALUES 
(1, 'categories/electronics.jpg', 'YES', 'Electronics', 'All electronic devices and accessories'),
(2, 'categories/clothing.jpg', 'YES', 'Clothing', 'Men and women clothing items'),
(3, 'categories/home.jpg', 'YES', 'Home & Kitchen', 'Home and kitchen appliances and decor');

-- Insert sample products
INSERT INTO product (product_id, created_at, meta_description, meta_title, title, sku, description, price, cost_price, is_active, weight, width, height)
VALUES 
(4001, NOW(), 'High quality leather wallet', 'Leather Wallet', 'Leather Wallet', 'SKU001', 'Genuine leather wallet', 49.99, 25.00, 'Y', 0.5, 10.0, 7.5),
(4002, NOW(), 'Stylish wristwatch for men', 'Men''s Watch', 'Men''s Watch', 'SKU002', 'Classic wristwatch with leather band', 99.99, 55.00, 'Y', 0.2, 5.0, 5.0),
(4003, NOW(), 'Wireless Bluetooth speaker', 'Bluetooth Speaker', 'Bluetooth Speaker', 'SKU003', 'Portable Bluetooth speaker', 59.99, 30.00, 'Y', 1.0, 15.0, 15.0);

-- Insert sample product images
INSERT INTO product_image (image_id, image_url, sort_order, is_primary, product_id)
VALUES 
(1, 'images/products/sku001_main.jpg', 1, 'YES', 4001),
(2, 'images/products/sku002_main.jpg', 1, 'YES', 4002),
(3, 'images/products/sku003_main.jpg', 1, 'YES', 4003),
(4, 'images/products/sku001_alt1.jpg', 2, 'NO', 4001),
(5, 'images/products/sku002_alt1.jpg', 2, 'NO', 4002),
(6, 'images/products/sku003_alt1.jpg', 2, 'NO', 4003);

-- Connect products to categories
INSERT INTO categorized_in (category_id, product_id)
VALUES 
(2, 4001), -- Wallet in Clothing
(1, 4002), -- Watch in Electronics
(1, 4003); -- Speaker in Electronics

-- Insert inventory
INSERT INTO inventory (inventory_id, low_stock_threshold, quantity, warehous_location, product_id)
VALUES 
(1, 5, 25, 'Shelf A1', 4001),
(2, 3, 10, 'Shelf B2', 4002),
(3, 5, 15, 'Shelf C3', 4003);
