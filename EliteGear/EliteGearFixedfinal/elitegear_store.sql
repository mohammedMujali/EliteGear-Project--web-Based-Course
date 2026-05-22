-- EliteGear Database SQL / ملف قاعدة البيانات
-- Task 1 Database Design: Creates normalized tables for products, customers, admins, orders, order_items, and contact_messages.
-- Task 3 Display Products: products table is the source for product cards/details.
-- Task 7 Buy: orders and order_items store completed checkout data.
-- Task 8 Managers: admins table stores manager accounts.
-- Author / المنفذ: EliteGear Team.

-- Create database / إنشاء قاعدة بيانات المشروع إذا غير موجودة.
CREATE DATABASE IF NOT EXISTS elitegear_store
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE elitegear_store;

-- Task 1 + 3 / Products table: الحقول الأساسية للمنتج مثل id, name, picture, stock, price.
CREATE TABLE IF NOT EXISTS products (
  id VARCHAR(80) PRIMARY KEY,
  created_date DATETIME NOT NULL,
  name VARCHAR(180) NOT NULL,
  description TEXT,
  price DECIMAL(10,2) NOT NULL DEFAULT 0,
  stock INT NOT NULL DEFAULT 0,
  category VARCHAR(80) NOT NULL,
  brand VARCHAR(120),
  image_url TEXT,
  colors VARCHAR(255),
  sizes VARCHAR(255),
  discount_percent DECIMAL(5,2) NOT NULL DEFAULT 0,
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  rating DECIMAL(3,1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Task 12 / Customers table: يخزن العملاء وعدد الطلبات والمشتريات السابقة.
CREATE TABLE IF NOT EXISTS customers (
  id VARCHAR(80) PRIMARY KEY,
  full_name VARCHAR(160) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  phone VARCHAR(40),
  address TEXT,
  city VARCHAR(100),
  role VARCHAR(40) NOT NULL DEFAULT 'customer',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  total_orders INT NOT NULL DEFAULT 0,
  total_spent DECIMAL(10,2) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Task 8 / Admins table: يخزن بيانات دخول المديرين.
CREATE TABLE IF NOT EXISTS admins (
  id VARCHAR(80) PRIMARY KEY,
  user_name VARCHAR(120) NOT NULL,
  user_email VARCHAR(190) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  permissions VARCHAR(80),
  notes TEXT,
  is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Task 6 + 7 / Orders table: يخزن الطلب النهائي بعد checkout/buy.
CREATE TABLE IF NOT EXISTS orders (
  id VARCHAR(80) PRIMARY KEY,
  created_date DATETIME NOT NULL,
  user_email VARCHAR(190) NOT NULL,
  user_name VARCHAR(160),
  total_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  status ENUM('pending', 'confirmed', 'shipped', 'delivered', 'cancelled') NOT NULL DEFAULT 'confirmed',
  payment_status VARCHAR(60) NOT NULL DEFAULT 'paid',
  payment_method VARCHAR(60) NOT NULL DEFAULT 'card',
  payment_snapshot JSON,
  shipping_address TEXT,
  city VARCHAR(100),
  phone VARCHAR(40),
  notes TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Normalized relation / تفاصيل الطلب: كل طلب له عدة منتجات.
CREATE TABLE IF NOT EXISTS order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id VARCHAR(80) NOT NULL,
  product_id VARCHAR(80) NOT NULL,
  product_name VARCHAR(180) NOT NULL,
  selected_size VARCHAR(80),
  selected_color VARCHAR(80),
  quantity INT NOT NULL DEFAULT 1,
  price DECIMAL(10,2) NOT NULL DEFAULT 0,
  INDEX idx_order_items_order_id (order_id),
  CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Task 11 + 13 / Contact messages: رسائل صفحة Contact بعد validation.
CREATE TABLE IF NOT EXISTS contact_messages (
  id VARCHAR(80) PRIMARY KEY,
  created_date DATETIME NOT NULL,
  name VARCHAR(160) NOT NULL,
  email VARCHAR(190) NOT NULL,
  subject VARCHAR(190),
  message TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 0;
DELETE FROM order_items;
DELETE FROM orders;
DELETE FROM contact_messages;
DELETE FROM admins;
DELETE FROM customers;
DELETE FROM products;
SET FOREIGN_KEY_CHECKS = 1;

-- Seed products / تعبئة جدول المنتجات ببيانات تجريبية للموقع.
INSERT INTO products (id, created_date, name, description, price, stock, category, brand, image_url, colors, sizes, discount_percent, is_featured, rating) VALUES
  ('prod_velocity_boots', '2026-05-13 12:00:00', 'Velocity Strike Football Boots', 'Lightweight match boots built for rapid cuts, grip, and explosive acceleration on firm ground.', 349.99, 12, 'Football', 'AeroFlex', 'https://commons.wikimedia.org/wiki/Special:Redirect/file/Football_boots_%283293104040%29.jpg', 'Black, Hyper Green, White', '40, 41, 42, 43, 44', 10.00, 1, 5.0),
  ('prod_adidas_predator_boots', '2026-05-13 20:00:00', 'Adidas Predator League Football Boots', 'Football boots from Adidas with a comfortable fit and firm ground studs for regular training.', 379.00, 10, 'Football', 'Adidas', 'assets/images/products/adidas-predator-league-boots.jpg', 'Black, White, Red', '40, 41, 42, 43, 44', 0.00, 0, 4.0),
  ('prod_wilson_nba_basketball', '2026-05-13 19:00:00', 'Wilson NBA Authentic Basketball', 'Wilson basketball suitable for indoor and outdoor practice games.', 219.00, 14, 'Basketball', 'Wilson', 'assets/images/products/wilson-nba-authentic-basketball.jpg', 'Orange, Black', '7', 0.00, 0, 5.0),
  ('prod_babolat_pure_drive', '2026-05-13 18:00:00', 'Babolat Pure Drive Tennis Racket', 'Babolat tennis racket made for balanced power and control.', 699.00, 6, 'Tennis', 'Babolat', 'assets/images/products/babolat-pure-drive-racket.png', 'Blue, Black', 'Grip 2, Grip 3, Grip 4', 10.00, 1, 5.0),
  ('prod_speedo_biofuse_goggles', '2026-05-13 17:00:00', 'Speedo Biofuse Swim Goggles', 'Speedo swim goggles with soft seals and clear lenses for pool training.', 139.00, 16, 'Swimming', 'Speedo', 'assets/images/products/speedo-biofuse-goggles.png', 'Blue, Smoke, Clear', 'One Size', 0.00, 0, 4.0),
  ('prod_giro_register_helmet', '2026-05-13 16:00:00', 'Giro Register Cycling Helmet', 'Giro cycling helmet with adjustable sizing and good ventilation for daily rides.', 269.00, 8, 'Cycling', 'Giro', 'assets/images/products/giro-register-helmet.jpg', 'Black, White', 'Universal Adult', 0.00, 0, 4.0),
  ('prod_everlast_training_gloves', '2026-05-13 15:00:00', 'Everlast Pro Style Boxing Gloves', 'Everlast boxing gloves for beginner and intermediate bag training.', 249.00, 11, 'Boxing', 'Everlast', 'assets/images/products/everlast-prostyle-boxing-gloves.jpg', 'Black, Red', '10oz, 12oz, 14oz', 5.00, 0, 4.0),
  ('prod_under_armour_duffle', '2026-05-13 14:00:00', 'Under Armour Undeniable Duffle Bag', 'Under Armour training bag with roomy storage for gym clothes, shoes, and accessories.', 189.00, 13, 'Gym', 'Under Armour', 'assets/images/products/under-armour-undeniable-duffle.jpg', 'Black, Grey', 'Medium', 0.00, 0, 4.0),
  ('prod_nike_training_bottle', '2026-05-13 13:00:00', 'Nike Refuel Water Bottle', 'Nike sports water bottle for gym, running, and team practice.', 69.00, 25, 'Gym', 'Nike', 'assets/images/products/nike-refuel-water-bottle.png', 'Black, Blue', '24oz', 0.00, 0, 4.0),
  ('prod_court_elite_ball', '2026-05-12 11:00:00', 'Court Elite Basketball', 'Indoor and outdoor basketball with deep channels, balanced weight, and reliable bounce.', 189.50, 4, 'Basketball', 'PrimeHoop', 'https://commons.wikimedia.org/wiki/Special:Redirect/file/Basketball_ball385428_9836.jpg', 'Orange, Black', '7', 0.00, 1, 4.0),
  ('prod_spin_control_racket', '2026-05-11 10:00:00', 'Spin Control Tennis Racket', 'Carbon performance racket tuned for spin, control, and smooth baseline power.', 499.00, 7, 'Tennis', 'StringLab', 'https://commons.wikimedia.org/wiki/Special:Redirect/file/Tennis_racket.jpg', 'Navy, Green', 'Grip 2, Grip 3, Grip 4', 15.00, 0, 5.0),
  ('prod_pulse_runner', '2026-05-10 09:00:00', 'Asics Runner Pro Shoes', 'Asics running shoes with breathable mesh and comfortable cushioning for daily runs.', 449.99, 18, 'Running', 'Asics', 'assets/images/products/asics-runner-pro-shoes.avif', 'White, Blue', '39, 40, 41, 42, 43, 44, 45', 0.00, 1, 5.0),
  ('prod_aqua_blade_goggles', '2026-05-09 08:00:00', 'Aqua Blade Swim Goggles', 'Low-profile swim goggles with anti-fog lenses and a secure race-ready fit.', 119.00, 0, 'Swimming', 'AquaCore', 'https://commons.wikimedia.org/wiki/Special:Redirect/file/Swimming_goggles.JPG', 'Clear, Smoke, Blue', 'One Size', 0.00, 0, 4.0),
  ('prod_iron_grip_gloves', '2026-05-08 07:00:00', 'Iron Grip Training Gloves', 'Gym gloves with padded palms, wrist support, and breathable upper panels.', 89.99, 22, 'Gym', 'Nike', 'assets/images/products/iron-grip-training-gloves.jpg', 'Black, Red', 'S, M, L, XL', 5.00, 0, 4.0),
  ('prod_cadence_helmet', '2026-05-07 06:00:00', 'Cadence Aero Cycling Helmet', 'Ventilated cycling helmet with aerodynamic shell and adjustable retention system.', 299.00, 9, 'Cycling', 'VeloNova', 'https://commons.wikimedia.org/wiki/Special:Redirect/file/Cycling_helmet.JPG', 'Matte Black, White', 'M, L', 0.00, 0, 4.0),
  ('prod_impact_boxing_gloves', '2026-05-06 05:00:00', 'Impact Boxing Gloves', 'Durable boxing gloves with layered foam protection and firm wrist closure.', 239.75, 5, 'Boxing', 'RingForce', 'https://commons.wikimedia.org/wiki/Special:Redirect/file/Boxing_gloves.jpg', 'Red, Black', '10oz, 12oz, 14oz, 16oz', 20.00, 1, 5.0);

-- Seed customer / حساب عميل تجريبي لتجربة login و My Orders.
INSERT INTO customers (id, full_name, email, password, phone, address, city, role, is_active, total_orders, total_spent) VALUES
  ('cust_demo', 'Demo Customer', 'customer@elitegear.com', 'customer123', '+966 5X XXX XXXX', 'King Fahd Road, Riyadh', 'Riyadh', 'customer', 1, 1, 539.49);

-- Seed admin / حساب مدير تجريبي لتجربة Admin Dashboard.
INSERT INTO admins (id, user_name, user_email, password, permissions, notes, is_active) VALUES
  ('admin_demo', 'EliteGear Admin', 'admin@elitegear.com', 'admin123', 'super_admin', 'Localhost demo administrator', 1);

-- Seed order / طلب تجريبي يظهر في Admin Orders و My Orders.
INSERT INTO orders (id, created_date, user_email, user_name, total_amount, status, payment_status, payment_method, payment_snapshot, shipping_address, city, phone, notes) VALUES
  ('ord_demo_1001', '2026-05-13 13:30:00', 'customer@elitegear.com', 'Demo Customer', 539.49, 'shipped', 'paid', 'card', '{"cardholder":"Demo Customer","brand":"Visa","last4":"4242","expiry":"12/30"}', 'King Fahd Road', 'Riyadh', '+966 5X XXX XXXX', 'Leave with reception.');

-- Seed order items / المنتجات داخل الطلب التجريبي.
INSERT INTO order_items (order_id, product_id, product_name, selected_size, selected_color, quantity, price) VALUES
  ('ord_demo_1001', 'prod_velocity_boots', 'Velocity Strike Football Boots', '', '', 1, 314.99),
  ('ord_demo_1001', 'prod_court_elite_ball', 'Court Elite Basketball', '', '', 1, 189.50);
