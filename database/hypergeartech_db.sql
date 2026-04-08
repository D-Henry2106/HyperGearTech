-- ============================================
-- HyperGear Tech - Database Schema (UPGRADED v2)
-- Database: hypergeartech_db
-- Compatible with phpMyAdmin / XAMPP MySQL
-- ============================================
-- Includes: product_variations with image support,
-- product_images gallery, order_item_options
-- ============================================

CREATE DATABASE IF NOT EXISTS `hypergeartech_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `hypergeartech_db`;

-- ============================================
-- 1. Users Table
-- ============================================
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `first_name` VARCHAR(50) NOT NULL,
    `last_name` VARCHAR(50) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `address` TEXT DEFAULT NULL,
    `city` VARCHAR(50) DEFAULT NULL,
    `role` ENUM('customer', 'admin') DEFAULT 'customer',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- 2. Categories Table
-- ============================================
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `icon` VARCHAR(50) DEFAULT 'fa-tag',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- 3. Products Table
-- ============================================
CREATE TABLE IF NOT EXISTS `products` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT NOT NULL,
    `name` VARCHAR(200) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `price` DECIMAL(10,2) NOT NULL,
    `old_price` DECIMAL(10,2) DEFAULT NULL,
    `stock` INT NOT NULL DEFAULT 0,
    `image` VARCHAR(255) DEFAULT 'default.png',
    `featured` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- 4. Product Variations Table (UPGRADED v2)
-- Now with per-variant image, price, stock
-- ============================================
CREATE TABLE IF NOT EXISTS `product_variations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `product_id` INT NOT NULL,
    `variant_type` ENUM('color', 'storage', 'version') NOT NULL,
    `variant_value` VARCHAR(100) NOT NULL,
    `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `price_modifier` DECIMAL(10,2) DEFAULT 0.00,
    `stock` INT NOT NULL DEFAULT 0,
    `image` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- 5. Product Images Table (NEW - Gallery)
-- ============================================
CREATE TABLE IF NOT EXISTS `product_images` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `product_id` INT NOT NULL,
    `image_path` VARCHAR(255) NOT NULL,
    `sort_order` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- 6. Orders Table
-- ============================================
CREATE TABLE IF NOT EXISTS `orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `total_amount` DECIMAL(10,2) NOT NULL,
    `shipping_address` TEXT NOT NULL,
    `shipping_city` VARCHAR(50) NOT NULL,
    `phone` VARCHAR(20) NOT NULL,
    `status` ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    `payment_method` VARCHAR(50) DEFAULT 'cod',
    `notes` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- 7. Order Details Table
-- ============================================
CREATE TABLE IF NOT EXISTS `order_details` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `quantity` INT NOT NULL,
    `price` DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- 8. Order Item Options Table
-- ============================================
CREATE TABLE IF NOT EXISTS `order_item_options` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_detail_id` INT NOT NULL,
    `variant_type` VARCHAR(50) NOT NULL,
    `variant_value` VARCHAR(100) NOT NULL,
    `variant_image` VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (`order_detail_id`) REFERENCES `order_details`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- 9. Cart Table (with variation_id)
-- ============================================
CREATE TABLE IF NOT EXISTS `cart` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `variation_id` INT DEFAULT NULL,
    `quantity` INT NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`variation_id`) REFERENCES `product_variations`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================
-- UPGRADE SCRIPT (for existing installations)
-- ============================================
-- ALTER TABLE `product_variations` ADD COLUMN `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `variant_value`;
-- ALTER TABLE `product_variations` ADD COLUMN `image` VARCHAR(255) DEFAULT NULL AFTER `stock`;
-- ALTER TABLE `order_item_options` ADD COLUMN `variant_image` VARCHAR(255) DEFAULT NULL AFTER `variant_value`;

-- ============================================
-- SAMPLE DATA
-- ============================================

-- Admin user (password: admin123)
INSERT INTO `users` (`first_name`, `last_name`, `email`, `password`, `phone`, `role`) VALUES
('Admin', 'User', 'admin@hypergear.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0123456789', 'admin');

-- Sample customers (password: password)
INSERT INTO `users` (`first_name`, `last_name`, `email`, `password`, `phone`, `address`, `city`) VALUES
('John', 'Doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0111111111', '123 Main St', 'New York'),
('Jane', 'Smith', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0122222222', '456 Oak Ave', 'Los Angeles');

-- Categories
INSERT INTO `categories` (`name`, `description`, `icon`) VALUES
('Keyboards', 'Mechanical and membrane keyboards for gaming and office', 'fa-keyboard'),
('Mice', 'Gaming and productivity mice with precision sensors', 'fa-computer-mouse'),
('Monitors', 'High-resolution displays for work and play', 'fa-desktop'),
('Headsets', 'Audio headsets for gaming, music, and communication', 'fa-headphones'),
('Accessories', 'Mousepads, webcams, cables, and other peripherals', 'fa-plug');

-- Products
INSERT INTO `products` (`category_id`, `name`, `description`, `price`, `old_price`, `stock`, `image`, `featured`) VALUES
(1, 'HyperStrike Mechanical Keyboard', 'RGB mechanical keyboard with Cherry MX Blue switches, aluminum frame, and detachable USB-C cable. Perfect for gaming and typing.', 89.99, 119.99, 50, 'keyboard1.png', 1),
(1, 'SilentType Pro Keyboard', 'Ultra-quiet membrane keyboard with ergonomic design, spill-resistant, and low-profile keys for comfortable all-day typing.', 39.99, NULL, 80, 'keyboard2.png', 0),
(1, 'CompactX 60% Keyboard', 'Compact 60% layout mechanical keyboard with hot-swappable switches and Bluetooth 5.0 connectivity.', 69.99, 89.99, 35, 'keyboard3.png', 1),
(2, 'PrecisionX Gaming Mouse', 'Lightweight gaming mouse with 25,600 DPI optical sensor, 6 programmable buttons, and RGB lighting.', 59.99, 79.99, 60, 'mouse1.png', 1),
(2, 'ErgoGlide Wireless Mouse', 'Ergonomic wireless mouse with silent clicks, 2.4GHz connection, and 12-month battery life.', 29.99, NULL, 100, 'mouse2.png', 0),
(2, 'SwiftAim FPS Mouse', 'Ultra-light 58g FPS gaming mouse with paracord cable and PTFE feet for maximum speed.', 49.99, 64.99, 45, 'mouse3.png', 0),
(3, 'UltraView 27" 4K Monitor', '27-inch IPS 4K UHD monitor with 144Hz refresh rate, HDR600, and 1ms response time.', 449.99, 549.99, 20, 'monitor1.png', 1),
(3, 'CurveMax 34" Ultrawide', '34-inch curved ultrawide WQHD monitor, 3440x1440, 165Hz, ideal for multitasking and immersive gaming.', 599.99, 699.99, 15, 'monitor2.png', 1),
(3, 'BudgetView 24" FHD Monitor', '24-inch Full HD IPS monitor with 75Hz refresh rate, VESA mount compatible.', 149.99, NULL, 40, 'monitor3.png', 0),
(4, 'ThunderBass 7.1 Headset', 'Virtual 7.1 surround sound gaming headset with noise-cancelling mic and memory foam cushions.', 79.99, 99.99, 55, 'headset1.png', 1),
(4, 'ClearVoice Wireless Headset', 'Bluetooth 5.2 wireless headset with 40-hour battery, ANC, and multipoint connection.', 129.99, 159.99, 30, 'headset2.png', 0),
(4, 'BasicBuds Stereo Headset', 'Affordable stereo headset with inline microphone, lightweight design for everyday use.', 19.99, NULL, 120, 'headset3.png', 0),
(5, 'GlideX XXL Mousepad', 'Extended gaming mousepad 900x400mm with stitched edges and non-slip rubber base.', 24.99, 34.99, 75, 'mousepad1.png', 0),
(5, 'StreamCam Pro Webcam', '1080p 60fps webcam with autofocus, built-in ring light, and privacy shutter.', 69.99, NULL, 40, 'webcam1.png', 1),
(5, 'USB-C Hub 7-in-1', 'Multiport USB-C hub with HDMI 4K, USB 3.0, SD card reader, and PD charging.', 34.99, 44.99, 90, 'usbhub1.png', 0);

-- Sample product variations (with per-variant price)
INSERT INTO `product_variations` (`product_id`, `variant_type`, `variant_value`, `price`, `price_modifier`, `stock`, `image`) VALUES
-- Keyboard colors
(1, 'color', 'Black', 89.99, 0.00, 20, NULL),
(1, 'color', 'White', 89.99, 0.00, 15, NULL),
(1, 'color', 'Blue', 94.99, 5.00, 15, NULL),
-- CompactX colors
(3, 'color', 'Black', 69.99, 0.00, 15, NULL),
(3, 'color', 'Pink', 74.99, 5.00, 10, NULL),
(3, 'color', 'Green', 74.99, 5.00, 10, NULL),
-- Mouse colors
(4, 'color', 'Black', 59.99, 0.00, 25, NULL),
(4, 'color', 'White', 59.99, 0.00, 20, NULL),
(4, 'color', 'Red', 59.99, 0.00, 15, NULL),
-- Wireless mouse colors
(5, 'color', 'Gray', 29.99, 0.00, 50, NULL),
(5, 'color', 'Black', 29.99, 0.00, 50, NULL),
-- Monitor versions
(7, 'version', 'Standard', 449.99, 0.00, 10, NULL),
(7, 'version', 'Pro (HDR1000)', 499.99, 50.00, 10, NULL),
-- Ultrawide versions
(8, 'version', 'Flat Stand', 599.99, 0.00, 8, NULL),
(8, 'version', 'Arm Mount Bundle', 679.98, 79.99, 7, NULL),
-- Headset colors
(10, 'color', 'Black', 79.99, 0.00, 25, NULL),
(10, 'color', 'Red', 79.99, 0.00, 15, NULL),
(10, 'color', 'White', 79.99, 0.00, 15, NULL),
-- Wireless headset colors
(11, 'color', 'Black', 129.99, 0.00, 15, NULL),
(11, 'color', 'Silver', 139.99, 10.00, 15, NULL);

-- Sample orders
INSERT INTO `orders` (`user_id`, `total_amount`, `shipping_address`, `shipping_city`, `phone`, `status`, `payment_method`) VALUES
(2, 219.97, '123 Main St', 'New York', '0111111111', 'delivered', 'cod'),
(2, 79.99, '123 Main St', 'New York', '0111111111', 'processing', 'cod'),
(3, 449.99, '456 Oak Ave', 'Los Angeles', '0122222222', 'shipped', 'cod');

-- Sample order details
INSERT INTO `order_details` (`order_id`, `product_id`, `quantity`, `price`) VALUES
(1, 1, 1, 89.99),
(1, 4, 1, 59.99),
(1, 14, 1, 69.99),
(2, 10, 1, 79.99),
(3, 7, 1, 449.99);

-- Sample order item options
INSERT INTO `order_item_options` (`order_detail_id`, `variant_type`, `variant_value`) VALUES
(1, 'color', 'Black'),
(2, 'color', 'White'),
(5, 'version', 'Standard');
