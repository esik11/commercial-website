-- DOi PC database (fresh install).
-- WARNING: this script DROPS and recreates the tables. Only run it on a new or sample-data database.
CREATE DATABASE IF NOT EXISTS doi_pc CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE doi_pc;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS quote_requests, bundle_components, bundles, hardware_items, hardware_categories, login_attempts, admin_users, custom_build_items, custom_builds;
SET FOREIGN_KEY_CHECKS = 1;

-- No default admin account. Create the first one at /admin/setup.php, then delete that file.
CREATE TABLE admin_users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  must_change_password TINYINT(1) NOT NULL DEFAULT 0,
  last_login DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE login_attempts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ip VARCHAR(45) NOT NULL,
  attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_ip_time (ip, attempted_at)
);

CREATE TABLE hardware_categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL,
  display_order INT DEFAULT 0
);
INSERT INTO hardware_categories (name, display_order) VALUES
('CPU',1),('Motherboard',2),('RAM',3),('GPU',4),('Storage',5),('Power Supply',6),('Case',7),('Cooling',8),('Monitor',9),('Peripherals',10);

CREATE TABLE hardware_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category_id INT NOT NULL,
  brand VARCHAR(100) NOT NULL,
  model VARCHAR(200) NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  stock_quantity INT NOT NULL DEFAULT 0,
  specifications TEXT,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES hardware_categories(id),
  INDEX idx_category (category_id),
  INDEX idx_active_stock (is_active, stock_quantity)
);

-- SAMPLE inventory. Replace with the shop's real stock from /admin/inventory.php
INSERT INTO hardware_items (category_id, brand, model, price, stock_quantity, specifications) VALUES
(1,'Intel','Core i3-12100',6500,10,'4 cores, 8 threads'),
(1,'Intel','Core i5-12400',9500,8,'6 cores, 12 threads'),
(1,'Intel','Core i7-12700K',18500,5,'12 cores, 20 threads'),
(1,'AMD','Ryzen 5 5600',8500,12,'6 cores, 12 threads'),
(1,'AMD','Ryzen 7 5800X',16500,6,'8 cores, 16 threads'),
(4,'NVIDIA','GTX 1650 4GB',12000,15,'4GB GDDR6'),
(4,'NVIDIA','RTX 3060 12GB',22000,8,'12GB GDDR6'),
(4,'NVIDIA','RTX 4060 Ti 8GB',35000,4,'8GB GDDR6'),
(4,'AMD','RX 6600 8GB',18000,10,'8GB GDDR6'),
(4,'AMD','RX 7700 XT 12GB',32000,5,'12GB GDDR6'),
(3,'Kingston','Fury 8GB DDR4 3200MHz',1800,25,'8GB DDR4'),
(3,'Corsair','Vengeance 16GB DDR4 3600MHz',3500,20,'2x8GB DDR4'),
(3,'G.Skill','Trident Z 32GB DDR4 3600MHz',7000,10,'2x16GB DDR4'),
(5,'Samsung','970 EVO 256GB NVMe SSD',2500,30,'256GB NVMe'),
(5,'WD','Blue 512GB SATA SSD',2800,25,'512GB SATA'),
(5,'Samsung','980 PRO 1TB NVMe SSD',6500,15,'1TB NVMe Gen4');

CREATE TABLE bundles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category ENUM('Office','Gaming','Workstation') NOT NULL,
  name VARCHAR(100) NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  description TEXT,
  extra_specs TEXT,             -- one line per spec, for things that are not tracked parts (OS, warranty...)
  image_url VARCHAR(255) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,   -- manual hide switch
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_category_active (category, is_active)
);

CREATE TABLE bundle_components (
  id INT AUTO_INCREMENT PRIMARY KEY,
  bundle_id INT NOT NULL,
  hardware_item_id INT NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  FOREIGN KEY (bundle_id) REFERENCES bundles(id) ON DELETE CASCADE,
  FOREIGN KEY (hardware_item_id) REFERENCES hardware_items(id),
  UNIQUE KEY uq_bundle_item (bundle_id, hardware_item_id)
);

-- SAMPLE bundles
INSERT INTO bundles (category, name, price, description, extra_specs, sort_order) VALUES
('Office','Basic Office',15000,'Everyday office tasks','Integrated graphics\nWindows 11 Home',1),
('Office','Standard Office',22000,'Smooth multitasking','Integrated graphics\nWindows 11 Pro',2),
('Gaming','Entry Gaming',30000,'Great for 1080p gaming','RGB cooling',1),
('Gaming','Mid-Range Gaming',45000,'High settings at 1080p and 1440p','RGB case and fans',2),
('Workstation','Content Creation',60000,'Video editing and rendering','1 color-accurate monitor',1);

INSERT INTO bundle_components (bundle_id, hardware_item_id, quantity)
SELECT b.id, i.id, 1 FROM bundles b JOIN hardware_items i ON
  (b.name = 'Basic Office' AND i.model = 'Core i3-12100') OR
  (b.name = 'Basic Office' AND i.model = 'Fury 8GB DDR4 3200MHz') OR
  (b.name = 'Basic Office' AND i.model = '970 EVO 256GB NVMe SSD') OR
  (b.name = 'Standard Office' AND i.model = 'Core i5-12400') OR
  (b.name = 'Standard Office' AND i.model = 'Vengeance 16GB DDR4 3600MHz') OR
  (b.name = 'Standard Office' AND i.model = 'Blue 512GB SATA SSD') OR
  (b.name = 'Entry Gaming' AND i.model = 'Ryzen 5 5600') OR
  (b.name = 'Entry Gaming' AND i.model = 'Vengeance 16GB DDR4 3600MHz') OR
  (b.name = 'Entry Gaming' AND i.model = 'GTX 1650 4GB') OR
  (b.name = 'Entry Gaming' AND i.model = 'Blue 512GB SATA SSD') OR
  (b.name = 'Mid-Range Gaming' AND i.model = 'Core i5-12400') OR
  (b.name = 'Mid-Range Gaming' AND i.model = 'Vengeance 16GB DDR4 3600MHz') OR
  (b.name = 'Mid-Range Gaming' AND i.model = 'RTX 3060 12GB') OR
  (b.name = 'Mid-Range Gaming' AND i.model = '980 PRO 1TB NVMe SSD') OR
  (b.name = 'Content Creation' AND i.model = 'Ryzen 7 5800X') OR
  (b.name = 'Content Creation' AND i.model = 'Trident Z 32GB DDR4 3600MHz') OR
  (b.name = 'Content Creation' AND i.model = 'RX 6600 8GB') OR
  (b.name = 'Content Creation' AND i.model = '980 PRO 1TB NVMe SSD');

CREATE TABLE quote_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(120),
  phone VARCHAR(30) NOT NULL,
  build_type VARCHAR(50),
  budget VARCHAR(50),
  message TEXT,
  bundle_id INT NULL,
  bundle_name VARCHAR(100) NULL,
  status ENUM('New','Contacted','Quoted','Converted','Closed') NOT NULL DEFAULT 'New',
  stock_deducted TINYINT(1) NOT NULL DEFAULT 0,
  ip VARCHAR(45),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (bundle_id) REFERENCES bundles(id) ON DELETE SET NULL,
  INDEX idx_status_date (status, created_at),
  INDEX idx_ip_date (ip, created_at)
);
