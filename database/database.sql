CREATE TABLE Users ( 
user_id number(4) primary key, 
profile_image varchar(255), 
email_verified VARCHAR2(3), 
verification_token number(4), 
password_hash char(4) not null, 
reset_token number(8), 
reset_token_expiry Date default sysdate,
email VARCHAR2(100) unique,
first_name VARCHAR2(80)  not null,
last_name VARCHAR2(80)  not null,
phone varchar(13) not null unique,
date_of_birth date,
is_active varchar(3)
);

create table ship_to (
user_id number(8) ,
address_id number(8),
foreign key (user_id) references users(user_id),
foreign key (address_id) references shipping_address(address_id)
);

describe users

CREATE TABLE Payment_method (
payment_method_id varchar(5) primary key,
expiry_date date ,
account_number_last4 number(4), 
provider Varchar2(100),
payment_type VARCHAR2(255),
is_default varchar(3),
user_id number(4) 
);
alter table Payment_method modify ( payment_method_id number )

Alter table  payment_method
add constraint fk62
foreign key (user_id) references users(user_id);

describe Payment_method

CREATE TABLE shipping_address(
address_id number(4) primary key,
city VARCHAR2(80),
address_line1 VARCHAR2(255) not null ,
address_line2 VARCHAR2(255) not null , 
phone number(13) not null unique ,
is_defaultt varchar(3),
country VARCHAR2(255) not null, 
postal_co VARCHAR2(255) ,
state VARCHAR2(255) not null  
);

describe shipping_address

CREATE TABLE review(
    review_id NUMBER(4) PRIMARY KEY,
    rating VARCHAR2(2) NOT NULL,
    coment VARCHAR2(255),
    title VARCHAR2(150),
    is_approved VARCHAR2(3),
    user_id NUMBER(4),
    product_id NUMBER(4)
);

Alter table  review
add constraint fk50
foreign key (user_id) references users(user_id);

Alter table review
add constraint fk51
foreign key (product_id) references product(product_id);

describe review

CREATE TABLE ordor (
order_id number(4) primary key ,
status VARCHAR2(80),
notes VARCHAR2(90),
tracking_number number(20), 
billing_address_id number(20),
shipping_fee number(5) not null,
user_id number(8),
payment_method_id number(10) ,
address_id number(10),
coupon_id number(10)
);

Alter table ordor
add constraint fk52
foreign key (user_id) references users(user_id) on delete set null;

Alter table ordor
add constraint fk53
foreign key (payment_method_id) references payment_method(payment_method_id);

Alter table ordor
add constraint fk54
foreign key (address_id) references shipping_address(address_id);

Alter table ordor
add constraint fk55
foreign key (coupon_id) references coupons(coupon_id);

describe ordor

CREATE TABLE admin_logs (
log_id number(4) primary key,
entity_id number(10),
entity_type VARCHAR2(255),
action VARCHAR2(120), 
action_details VARCHAR2(100),
user_agent VARCHAR2(90),
ip_address number(15) unique,
admin_id number(4)
); 

Alter table admin_logs
add constraint fk56
foreign key (admin_id) references admin(admin_id);

describe admin_logs

CREATE TABLE coupons (
coupon_id number(10) primary key,
is_active varchar(3),
max_uses number(10),
end_date date,
start_date date,
minimum_order_value number(5),
discount_value number(10),
discount_type VARCHAR2(255),
description VARCHAR2(255),
code number(10) unique,
admin_id number(10)
);

Alter table coupons
add constraint fk57
foreign key (admin_id) references admin(admin_id);

describe coupons 
create table admin (
admin_id  number(10) primary key,
profile_image varchar(255),
email VARCHAR2(100),
password_hash varchar(10),
first_name VARCHAR2(100) not null,
last_name VARCHAR2(100) not null,
role VARCHAR2(255) not null,
is_active varchar(3)
)

describe admin 

CREATE TABLE order_item (
    order_item_id      NUMBER PRIMARY KEY,
    unit_price         NUMBER(10, 2),
    quantity           NUMBER ,
    belongs_to_order_id  NUMBER,
    contains_item_order_id NUMBER    
);

ALTER TABLE order_item
ADD CONSTRAINT fk
FOREIGN KEY (belongs_to_order_id)
REFERENCES ordor(order_id);


ALTER TABLE order_item
ADD CONSTRAINT fk1
FOREIGN KEY (contains_item_order_id)
REFERENCES ordor(order_id);

describe order_item


CREATE TABLE product_image (
    image_id     NUMBER(4) PRIMARY KEY,
    image_url    VARCHAR2(255),
    sort_order   NUMBER(2),
    is_primary   varCHAR(3),  
    product_id   NUMBER
);


ALTER TABLE product_image
ADD CONSTRAINT fk2
FOREIGN KEY (product_id)
REFERENCES product(product_id);

describe product_image

 CONSTRAINT fk_product FOREIGN KEY (product_id) 
        REFERENCES products(product_id)

CREATE TABLE product (
    product_id        NUMBER(4) PRIMARY KEY,
    created_at        DATE default sysdate,
    meta_description  VARCHAR2(120),
    meta_title        VARCHAR2(70),
    title             VARCHAR2(70) not null,
    sku               VARCHAR2(100) not null unique,
    description       varchar(40),
    price             NUMBER(10, 2) not null,
    cost_price        NUMBER(10, 2),
    is_active         CHAR(1),
    weight            NUMBER(10, 2),
    width             NUMBER(10, 2),
    height            NUMBER(10, 2)
);


describe product 

CREATE TABLE included_in(
product_id int,
order_item_id int ,
foreign key (product_id) references product(product_id),
foreign key (order_item_id) references order_item(order_item_id)
)

CREATE TABLE product_meta_keywords(
meta_keywords varchar2(22),
product_id int ,
foreign key (product_id) references product(product_id)
)

CREATE TABLE categorized_in(
category_id int,
product_id int ,
foreign key (category_id) references category(category_id),
foreign key (product_id) references product(product_id)
)


create table inventory (
inventory_id number(8) primary key,
low_stock_threshold number(8),
quantity number(4),
warehous_location VARCHAR2(255),
product_id number(8)
)


ALTER TABLE inventory 
ADD CONSTRAINT fk3
FOREIGN KEY (product_id)
REFERENCES product(product_id);
describe inventory 

CREATE TABLE category(
category_id number primary key,
image_url varchar(150),
is_active varchar(3) ,
name varchar(70),
description varchar(110)
)

CREATE TABLE has_subcategory(
category_id1 int,
subcategory_id2 int ,
foreign key (category_id1) references category(category_id),
foreign key (subcategory_id2) references category(category_id)
)

describe category

INSERT ALL
  INTO users (user_id, profile_image, email_verified, verification_token, password_hash, reset_token, email, first_name, last_name, phone, date_of_birth, is_active)
  VALUES (1001, 'profile1.jpg', 'YES', 1234, 'abcd', 56789012, 'user1@example.com', 'John', 'Doe', '0123456789', TO_DATE('1990-01-01', 'YYYY-MM-DD'), 'YES')

  INTO users (user_id, profile_image, email_verified, verification_token, password_hash, reset_token, email, first_name, last_name, phone, date_of_birth, is_active)
  VALUES (1002, 'profile2.jpg', 'NO', 5678, 'efgh', 12345678, 'user2@example.com', 'Jane', 'Smith', '0123456780', TO_DATE('1992-02-02', 'YYYY-MM-DD'), 'NO')

  INTO users (user_id, profile_image, email_verified, verification_token, password_hash, reset_token, email, first_name, last_name, phone, date_of_birth, is_active)
  VALUES (1003, 'profile3.jpg', 'YES', 9101, 'ijkl', 23456789, 'user3@example.com', 'Alice', 'Johnson', '0123456781', TO_DATE('1988-03-03', 'YYYY-MM-DD'), 'YES')
SELECT * FROM dual;


INSERT ALL
  INTO Payment_method (payment_method_id, expiry_date, account_number_last4, provider, payment_type, is_default, user_id)
  VALUES (1, TO_DATE('2026-12-31', 'YYYY-MM-DD'), 1234, 'Visa', 'Credit Card', 'Y', 1001)

  INTO Payment_method (payment_method_id, expiry_date, account_number_last4, provider, payment_type, is_default, user_id)
  VALUES (2, TO_DATE('2025-11-30', 'YYYY-MM-DD'), 5678, 'MasterCard', 'Debit Card', 'N', 1002)

  INTO Payment_method (payment_method_id, expiry_date, account_number_last4, provider, payment_type, is_default, user_id)
  VALUES (3, TO_DATE('2027-10-15', 'YYYY-MM-DD'), 9101, 'PayPal', 'Online', 'N', 1003)
SELECT * FROM dual;


INSERT ALL
  INTO shipping_address (address_id, city, address_line1, address_line2, phone, is_defaultt, country, postal_co, state)
  VALUES (2001, 'Alexandria', '123 Main St', 'Apt 4B', 1234567890, 'Y', 'Egypt', '12345', 'Alexandria')

  INTO shipping_address (address_id, city, address_line1, address_line2, phone, is_defaultt, country, postal_co, state)
  VALUES (2002, 'Cairo', '456 Elm St', 'Apt 2A', 2345678901, 'N', 'Egypt', '67890', 'Cairo')

  INTO shipping_address (address_id, city, address_line1, address_line2, phone, is_defaultt, country, postal_co, state)
  VALUES (2003, 'Giza', '789 Oak St', 'Apt 3C', 3456789012, 'N', 'Egypt', '11223', 'Giza')
SELECT * FROM dual;

INSERT ALL
  INTO review (review_id, rating, coment, title, is_approved, user_id, product_id)
  VALUES (3001, '5', 'Excellent product!', 'Great Purchase', 'Y', 1001, 4001)

  INTO review (review_id, rating, coment, title, is_approved, user_id, product_id)
  VALUES (3002, '4', 'Good value for money.', 'Satisfied', 'Y', 1002, 4002)

  INTO review (review_id, rating, coment, title, is_approved, user_id, product_id)
  VALUES (3003, '3', 'Average quality.', 'Okay', 'N', 1003, 4003)
SELECT * FROM dual;

INSERT ALL
  INTO ordor (order_id, status, notes, tracking_number, billing_address_id, shipping_fee, user_id, payment_method_id, address_id, coupon_id)
  VALUES (4001, 'Shipped', 'On time delivery', 12345678901234567890, 2001, 50, 1001, 1, 2001, 5001)

  INTO ordor (order_id, status, notes, tracking_number, billing_address_id, shipping_fee, user_id, payment_method_id, address_id, coupon_id)
  VALUES (4002, 'Processing', 'Awaiting payment', 23456789012345678901, 2002, 60, 1002, 2, 2002, 5002)

  INTO ordor (order_id, status, notes, tracking_number, billing_address_id, shipping_fee, user_id, payment_method_id, address_id, coupon_id)
  VALUES (4003, 'Delivered', 'Delivered successfully', 34567890123456789012, 2003, 70, 1003, 3, 2003, 5003)
SELECT * FROM dual;

INSERT ALL
  INTO admin_logs (log_id, entity_id, entity_type, action, action_details, user_agent, ip_address, admin_id)
  VALUES (5001, 1001, 'User', 'Update', 'Updated profile image', 'Mozilla/5.0', '123456789012345', 6001)

  INTO admin_logs (log_id, entity_id, entity_type, action, action_details, user_agent, ip_address, admin_id)
  VALUES (5002, 1002, 'Order', 'Cancel', 'Cancelled order #4002', 'Chrome/91.0', '234567890123456', 6002)

  INTO admin_logs (log_id, entity_id, entity_type, action, action_details, user_agent, ip_address, admin_id)
  VALUES (5003, 1003, 'Product', 'Add', 'Added new product', 'Safari/14.0', '345678901234567', 6003)
SELECT * FROM dual;


INSERT ALL
  INTO coupons (coupon_id, is_active, max_uses, end_date, start_date, minimum_order_value, discount_value, discount_type, description, code, admin_id)
  VALUES (5001, 'Y', 100, TO_DATE('2025-12-31', 'YYYY-MM-DD'), TO_DATE('2025-01-01', 'YYYY-MM-DD'), 100, 20, 'Percentage', '20% off on orders above $100', '1234567890', 6001)

  INTO coupons (coupon_id, is_active, max_uses, end_date, start_date, minimum_order_value, discount_value, discount_type, description, code, admin_id)
  VALUES (5002, 'N', 50, TO_DATE('2025-06-30', 'YYYY-MM-DD'), TO_DATE('2025-03-01', 'YYYY-MM-DD'), 50, 10, 'Fixed', '$10 off on orders above $50', '2345678901', 6002)

  INTO coupons (coupon_id, is_active, max_uses, end_date, start_date, minimum_order_value, discount_value, discount_type, description, code, admin_id)
  VALUES (5003, 'Y', 200, TO_DATE('2025-11-30', 'YYYY-MM-DD'), TO_DATE('2025-04-01', 'YYYY-MM-DD'), 150, 30, 'Percentage', '30% off on orders above $150', '3456789012', 6003)
SELECT * FROM dual;


INSERT ALL
  INTO admin (admin_id, profile_image, email, password_hash, first_name, last_name, role, is_active)
  VALUES (6001, 'admin1.jpg', 'admin1@example.com', 'admin123', 'Alice', 'Williams', 'Manager', 'Y')

  INTO admin (admin_id, profile_image, email, password_hash, first_name, last_name, role, is_active)
  VALUES (6002, 'admin2.jpg', 'admin2@example.com', 'admin456', 'Bob', 'Johnson', 'Supervisor', 'Y')

  INTO admin (admin_id, profile_image, email, password_hash, first_name, last_name, role, is_active)
  VALUES (6003, 'admin3.jpg', 'admin3@example.com', 'admin789', 'Charlie', 'Brown', 'Clerk', 'N')
SELECT * FROM dual;


INSERT ALL
  INTO product (product_id, created_at, meta_description, meta_title, title, sku, description, price, cost_price, is_active, weight, width, height)
  VALUES (4001, SYSDATE, 'High quality leather wallet', 'Leather Wallet', 'Leather Wallet', 'SKU001', 'Genuine leather wallet', 49.99, 25.00, 'Y', 0.5, 10.0, 7.5)

  INTO product (product_id, created_at, meta_description, meta_title, title, sku, description, price, cost_price, is_active, weight, width, height)
  VALUES (4002, SYSDATE, 'Stylish wristwatch for men', 'Men''s Watch', 'Men''s Watch', 'SKU002', 'Classic wristwatch with leather band', 99.99, 55.00, 'Y', 0.2, 5.0, 5.0)

  INTO product (product_id, created_at, meta_description, meta_title, title, sku, description, price, cost_price, is_active, weight, width, height)
  VALUES (4003, SYSDATE, 'Wireless Bluetooth speaker', 'Bluetooth Speaker', 'Bluetooth Speaker', 'SKU003', 'Portable Bluetooth speaker', 59.99, 30.00, 'Y', 1.0, 15.0, 15.0)
SELECT * FROM dual;

INSERT INTO product_image (image_id, image_url, sort_order, is_primary, product_id)
VALUES
(1, 'images/sku001_main.jpg', 1, 'YES', 4001),
(2, 'images/sku002_main.jpg', 1, 'YES', 4002),
(3, 'images/sku003_main.jpg', 1, 'YES', 4003);

INSERT ALL
  INTO product_image (image_id, image_url, sort_order, is_primary, product_id)
  VALUES (1, 'images/sku001_main.jpg', 1, 'YES', 4001)
  INTO product_image (image_id, image_url, sort_order, is_primary, product_id)
  VALUES (2, 'images/sku002_main.jpg', 1, 'YES', 4002)
  INTO product_image (image_id, image_url, sort_order, is_primary, product_id)
  VALUES (3, 'images/sku003_main.jpg', 1, 'YES', 4003)
SELECT * FROM dual;

update product_image set sort_order = 2 where image_id = 2
update product_image set sort_order = 3 where image_id = 3
update product_image set is_primary = 'No' where image_id = 2
update product_image set is_primary = 'No' where image_id = 3



INSERT ALL
  INTO included_in (product_id, order_item_id)
  VALUES (4001, 1)
  INTO included_in (product_id, order_item_id)
  VALUES (4002, 2)
  INTO included_in (product_id, order_item_id)
  VALUES (4003, null)
SELECT * FROM dual;

INSERT ALL
  INTO product_meta_keywords (meta_keywords, product_id)
  VALUES ('accessories', 4001)

  INTO product_meta_keywords (meta_keywords, product_id)
  VALUES ('fashion', 4002)

  INTO product_meta_keywords (meta_keywords, product_id)
  VALUES ('portable', 4003)

SELECT * FROM dual;

INSERT ALL
  INTO category (category_id, image_url, is_active, name, description)
  VALUES (1001, 'https://example.com/images/wallet_category.jpg', 'Y', 'Wallets', 'A variety of high-quality wallets for men.')

  INTO category (category_id, image_url, is_active, name, description)
  VALUES (1002, 'https://example.com/images/watch_category.jpg', 'Y', 'Watches', 'Stylish and elegant wristwatches for men.')

  INTO category (category_id, image_url, is_active, name, description)
  VALUES (1003, 'https://example.com/images/speaker_category.jpg', 'Y', 'Speakers', 'Portable Bluetooth speakers with superior sound quality.')

SELECT * FROM dual;


INSERT ALL
  INTO categorized_in (category_id, product_id)
  VALUES (1001, 4001)

  INTO categorized_in (category_id, product_id)
  VALUES (1002, 4002)

  INTO categorized_in (category_id, product_id)
  VALUES (1003, 4003)

SELECT * FROM dual;

INSERT ALL
  INTO order_item (order_item_id, unit_price, quantity, belongs_to_order_id, contains_item_order_id)
  VALUES (1, 49.99, 2, 4001, Null)

  INTO order_item (order_item_id, unit_price, quantity, belongs_to_order_id, contains_item_order_id)
  VALUES (2, 99.99, 1, 4002, 4002)

  INTO order_item (order_item_id, unit_price, quantity, belongs_to_order_id, contains_item_order_id)
  VALUES (3, 59.99, 3, 4003, 4003)

SELECT * FROM dual;

INSERT ALL
  INTO inventory (inventory_id, low_stock_threshold, quantity, warehous_location, product_id)
  VALUES (7001, 5, 50, 'Warehouse A', 4001)

  INTO inventory (inventory_id, low_stock_threshold, quantity, warehous_location, product_id)
  VALUES (7002, 5, 30, 'Warehouse B', 4002)

  INTO inventory (inventory_id, low_stock_threshold, quantity, warehous_location, product_id)
  VALUES (7003, 10, 100, 'Warehouse C', 4003)

SELECT * FROM dual;

INSERT ALL
  INTO has_subcategory (category_id1, subcategory_id2)
  VALUES (1001, 1002) 

  INTO has_subcategory (category_id1, subcategory_id2)
  VALUES (1001, 1003)  

  INTO has_subcategory (category_id1, subcategory_id2)
  VALUES (1002, 1003) 
SELECT * FROM dual;

INSERT INTO ship_to (user_id, address_id) VALUES (1001, 2001);
INSERT INTO ship_to (user_id, address_id) VALUES (1002, 2002);
INSERT INTO ship_to (user_id, address_id) VALUES (1003, 2003);

SELECT product.title, categorized_in.category_id
FROM product
INNER JOIN categorized_in ON (product.product_id = categorized_in.product_id);


SELECT product.title, review.product_id , review.rating , review.title
FROM product
INNER JOIN review ON (product.product_id = review.product_id);

delete from admin_logs where log_id = 5003

select * from admin_logs
describe product
describe categorized_in

update users set email = 'blueflame@gmail.com ' where user_id = 1003

select max(product.price) as maxPrice , title from product
group by (title)
order by maxPrice

select max(product.price) as maxPrice , title from product
group by (title)
order by maxPrice DESC

select upper(first_name) from users

update users set profile_image = ( select image_url from category where category_id = 1003 ) where user_id = 1001 

update users set email = 'mohamedmagdy@gmail.com' where user_id = 1001

update users set first_name = 'Abdelrahman' where user_id = 1002

update users set last_name = 'Abdelbadea' where user_id = 1003

update admin set password_hash = '012121' where admin_id = 6002
update admin set role = 'Owner' where admin_id = 6003

select * from users
select * from ordor
select * from coupons
select * from admin
select * from admin_logs
select * from payment_method
select * from shipping_address
select * from ship_to
select * from included_in
select * from order_item
select * from review
select * from product_image
select * from product
select * from product_meta_keywords
select * from categorized_in
select * from category
select * from inventory
select * from has_subcategory

select order_item.unit_price , order_item.CONTAINS_ITEM_ORDER_ID , ordor.status , ordor.shipping_fee
from ordor
inner join order_item on ( order_item.CONTAINS_ITEM_ORDER_ID = ordor.order_id);
 
SELECT * FROM users
WHERE first_name LIKE 'A%';
select * from ordor
WHERE notes LIKE '%el%';

select * from coupons where discount_value < 20
SELECT * FROM admin WHERE first_name <> 'Bob';
SELECT * FROM admin_logs WHERE action LIKE '__n%';
select * from shipping_address where is_defaultt = 'Y'
select * from ship_to
select * from included_in
select * from order_item
select * from review where lower(title) = 'okay'
select * from product_image where is_primary Like '%S'
select min(cost_price) from product 
select max(cost_price) from product 
select sum(cost_price) from product 
select round(avg(cost_price)) from product 
select floor(avg(cost_price)) from product 


select * from category where name != 'Wallets'
SELECT DISTINCT  low_stock_threshold FROM inventory;


select product.product_id , product.title , categorized_in.category_id , category.name
from product 
full outer join categorized_in on product.product_id = categorized_in.product_id
join category on categorized_in.category_id = category.category_id

select coupons.code , coupons.start_date , coupons.end_date , coupons.discount_value , CONCAT(CONCAT(admin.first_name, ' '), admin.last_name) AS "Full Name" , admin.role
from coupons
join admin on coupons.admin_id = admin.admin_id
