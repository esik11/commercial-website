-- DOi PC Database Schema
-- Run this in phpMyAdmin or MySQL command line

CREATE DATABASE IF NOT EXISTS doi_pc;
USE doi_pc;

-- Admin Users Table
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin (password: changeme123)
INSERT INTO admin_users (username, password) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Hardware Categories Table
CREATE TABLE IF NOT EXISTS hardware_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO hardware_categories (name, display_order) VALUES
('CPU', 1),
('Motherboard', 2),
('RAM', 3),
('GPU', 4),
('Storage', 5),
('Power Supply', 6),
('Case', 7),
('Cooling', 8),
('Monitor', 9),
('Peripherals', 10);

-- Hardware Items (Inventory)
CREATE TABLE IF NOT EXISTS hardware_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    brand VARCHAR(100) NOT NULL,
    model VARCHAR(200) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    stock_quantity INT DEFAULT 0,
    specifications TEXT,
    image_url VARCHAR(255),
    is_featured BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES hardware_categories(id),
    INDEX idx_category (category_id),
    INDEX idx_active_stock (is_active, stock_quantity)
);

-- Sample Hardware Data
INSERT INTO hardware_items (category_id, brand, model, price, stock_quantity, specifications) VALUES
-- CPUs
(1, 'Intel', 'Core i3-12100', 6500.00, 10, '4 Cores, 8 Threads, 3.3GHz Base'),
(1, 'Intel', 'Core i5-12400', 9500.00, 8, '6 Cores, 12 Threads, 2.5GHz Base'),
(1, 'Intel', 'Core i7-12700K', 18500.00, 5, '12 Cores, 20 Threads, 3.6GHz Base'),
(1, 'AMD', 'Ryzen 5 5600', 8500.00, 12, '6 Cores, 12 Threads, 3.5GHz Base'),
(1, 'AMD', 'Ryzen 7 5800X', 16500.00, 6, '8 Cores, 16 Threads, 3.8GHz Base'),

-- GPUs
(4, 'NVIDIA', 'GTX 1650 4GB', 12000.00, 15, '896 CUDA Cores, 4GB GDDR6'),
(4, 'NVIDIA', 'RTX 3060 12GB', 22000.00, 8, '3584 CUDA Cores, 12GB GDDR6'),
(4, 'NVIDIA', 'RTX 4060 Ti 8GB', 35000.00, 4, '4352 CUDA Cores, 8GB GDDR6'),
(4, 'AMD', 'RX 6600 8GB', 18000.00, 10, '1792 Stream Processors, 8GB GDDR6'),
(4, 'AMD', 'RX 7700 XT 12GB', 32000.00, 5, '3456 Stream Processors, 12GB GDDR6'),

-- RAM
(3, 'Kingston', 'Fury 8GB DDR4 3200MHz', 1800.00, 25, '8GB, DDR4, 3200MHz, CL16'),
(3, 'Corsair', 'Vengeance 16GB DDR4 3600MHz', 3500.00, 20, '16GB (2x8GB), DDR4, 3600MHz'),
(3, 'G.Skill', 'Trident Z 32GB DDR4 3600MHz', 7000.00, 10, '32GB (2x16GB), DDR4, 3600MHz'),

-- Storage
(5, 'Samsung', '970 EVO 256GB NVMe SSD', 2500.00, 30, '256GB, NVMe, 3500MB/s Read'),
(5, 'WD', 'Blue 512GB SATA SSD', 2800.00, 25, '512GB, SATA, 560MB/s Read'),
(5, 'Samsung', '980 PRO 1TB NVMe SSD', 6500.00, 15, '1TB, NVMe Gen4, 7000MB/s Read');

-- PC Build Bundles Table
CREATE TABLE IF NOT EXISTS bundles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category ENUM('Office', 'Gaming', 'Workstation') NOT NULL,
    name VARCHAR(100) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    stock_status ENUM('In Stock', 'Low Stock', 'Out of Stock') DEFAULT 'In Stock',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category_active (category, is_active)
);

-- Bundle Components (Junction Table)
CREATE TABLE IF NOT EXISTS bundle_components (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bundle_id INT NOT NULL,
    hardware_item_id INT,
    component_description VARCHAR(255) NOT NULL,
    FOREIGN KEY (bundle_id) REFERENCES bundles(id) ON DELETE CASCADE,
    FOREIGN KEY (hardware_item_id) REFERENCES hardware_items(id) ON DELETE SET NULL
);

-- Sample Bundles
INSERT INTO bundles (category, name, price, description, stock_status) VALUES
('Office', 'Basic Office', 15000.00, 'Perfect for everyday office tasks', 'In Stock'),
('Office', 'Standard Office', 22000.00, 'Enhanced performance for multitasking', 'In Stock'),
('Gaming', 'Entry Gaming', 30000.00, 'Great for 1080p gaming', 'In Stock'),
('Gaming', 'Mid-Range Gaming', 45000.00, 'High settings at 1440p', 'Low Stock'),
('Workstation', 'Content Creation', 60000.00, 'For video editing and rendering', 'In Stock');

-- Custom PC Builds (Customer Orders)
CREATE TABLE IF NOT EXISTS custom_builds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(100) NOT NULL,
    customer_email VARCHAR(100),
    customer_phone VARCHAR(20) NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    status ENUM('Pending', 'Confirmed', 'Building', 'Completed', 'Cancelled') DEFAULT 'Pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_created (created_at)
);

-- Custom Build Components
CREATE TABLE IF NOT EXISTS custom_build_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    build_id INT NOT NULL,
    hardware_item_id INT NOT NULL,
    quantity INT DEFAULT 1,
    price_at_time DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (build_id) REFERENCES custom_builds(id) ON DELETE CASCADE,
    FOREIGN KEY (hardware_item_id) REFERENCES hardware_items(id)
);

-- Quote Requests Table
CREATE TABLE IF NOT EXISTS quote_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20) NOT NULL,
    build_type VARCHAR(50),
    budget VARCHAR(50),
    message TEXT,
    status ENUM('New', 'Contacted', 'Quoted', 'Converted', 'Closed') DEFAULT 'New',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status_date (status, created_at)
);
