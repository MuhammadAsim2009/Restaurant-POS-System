-- ============================================================
-- Khan Fast Food POS System — Database Schema + Seed Data
-- Import via: phpMyAdmin → Import → Select this file
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+05:00";

-- Create and use database
CREATE DATABASE IF NOT EXISTS `khan_pos` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `khan_pos`;

-- ============================================================
-- Table: users
-- ============================================================
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL UNIQUE,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('cashier','manager') NOT NULL DEFAULT 'cashier',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Table: categories
-- ============================================================
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `icon` varchar(10) NOT NULL DEFAULT '🍔',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Table: menu_items
-- ============================================================
CREATE TABLE `menu_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_item_category` (`category_id`),
  CONSTRAINT `fk_item_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Table: restaurant_tables
-- ============================================================
CREATE TABLE `restaurant_tables` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `table_number` varchar(10) NOT NULL UNIQUE,
  `capacity` int(11) NOT NULL DEFAULT 4,
  `status` enum('available','occupied','reserved') NOT NULL DEFAULT 'available',
  `current_order_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Table: orders
-- ============================================================
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_number` varchar(20) NOT NULL UNIQUE,
  `table_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `order_type` enum('dine-in','takeaway','delivery') NOT NULL DEFAULT 'dine-in',
  `status` enum('pending','preparing','ready','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_method` enum('cash','card','easypaisa','jazzcash') DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_order_table` (`table_id`),
  KEY `fk_order_user` (`user_id`),
  CONSTRAINT `fk_order_table` FOREIGN KEY (`table_id`) REFERENCES `restaurant_tables` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_order_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Table: order_items
-- ============================================================
CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `menu_item_id` int(11) NOT NULL,
  `item_name` varchar(150) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_oi_order` (`order_id`),
  KEY `fk_oi_item` (`menu_item_id`),
  CONSTRAINT `fk_oi_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_oi_item` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Table: payments
-- ============================================================
CREATE TABLE `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL UNIQUE,
  `method` enum('cash','card','easypaisa','jazzcash') NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL,
  `change_given` decimal(10,2) NOT NULL DEFAULT 0.00,
  `paid_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_pay_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SEED DATA
-- ============================================================

-- Users (password for all = "khan123" | manager password = "manager123")
INSERT INTO `users` (`name`, `username`, `password_hash`, `role`) VALUES
('Ahmad Khan', 'manager', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager'),
('Ali Cashier', 'cashier1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cashier'),
('Bilal Cashier', 'cashier2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cashier');

-- NOTE: The above hash = "password" (bcrypt). Update via: password_hash('yourpassword', PASSWORD_BCRYPT)
-- For demo: username=manager password=password | username=cashier1 password=password

-- Categories
INSERT INTO `categories` (`name`, `icon`, `sort_order`) VALUES
('Burgers', '🍔', 1),
('Deals', '🎁', 2),
('Sides', '🍟', 3),
('Drinks', '🥤', 4),
('Desserts', '🍦', 5),
('Broast', '🍗', 6);

-- Menu Items — Burgers
INSERT INTO `menu_items` (`category_id`, `name`, `description`, `price`) VALUES
(1, 'Classic Burger', 'Juicy beef patty with fresh veggies & special sauce', 350.00),
(1, 'Zinger Burger', 'Crispy fried chicken fillet with coleslaw & mayo', 420.00),
(1, 'Double Smash Burger', 'Double smashed beef patty, cheese, pickles', 580.00),
(1, 'Spicy Chicken Burger', 'Spicy fried chicken with jalapeños & sriracha', 450.00),
(1, 'Tower Burger', 'Triple patty loaded burger — the ultimate challenge', 750.00),
(1, 'Beef Cheese Burger', 'Beef patty with cheddar cheese & caramelized onions', 480.00);

-- Menu Items — Deals
INSERT INTO `menu_items` (`category_id`, `name`, `description`, `price`) VALUES
(2, 'Family Deal (4 pax)', '4 Zinger Burgers + 4 Drinks + Large Fries', 1600.00),
(2, 'Couple Deal', '2 Classic Burgers + 2 Drinks + 2 Sides', 950.00),
(2, 'Student Deal', '1 Burger + 1 Drink + 1 Side', 499.00),
(2, 'Broast Deal (6 pcs)', '6 pcs Broast + Fries + 2 Drinks + Sauce', 1200.00),
(2, 'Party Pack (8 pax)', '8 Zinger Burgers + 8 Drinks + 2 Large Fries', 3200.00);

-- Menu Items — Sides
INSERT INTO `menu_items` (`category_id`, `name`, `description`, `price`) VALUES
(3, 'Regular Fries', 'Golden crispy salted fries', 180.00),
(3, 'Large Fries', 'Extra large serving of crispy fries', 280.00),
(3, 'Loaded Fries', 'Fries topped with cheese sauce & jalapeños', 350.00),
(3, 'Coleslaw', 'Creamy homemade coleslaw', 120.00),
(3, 'Onion Rings', 'Crispy battered onion rings', 220.00),
(3, 'Garlic Bread', '4 slices of toasted garlic bread', 150.00);

-- Menu Items — Drinks
INSERT INTO `menu_items` (`category_id`, `name`, `description`, `price`) VALUES
(4, 'Coca Cola (Regular)', '330ml chilled Coca Cola', 80.00),
(4, 'Coca Cola (Large)', '500ml chilled Coca Cola', 120.00),
(4, 'Fanta Orange', '330ml Fanta', 80.00),
(4, 'Sprite', '330ml Sprite', 80.00),
(4, 'Mango Shake', 'Fresh mango blended milkshake', 250.00),
(4, 'Strawberry Shake', 'Fresh strawberry milkshake', 250.00),
(4, 'Water Bottle', '500ml mineral water', 60.00);

-- Menu Items — Desserts
INSERT INTO `menu_items` (`category_id`, `name`, `description`, `price`) VALUES
(5, 'Soft Serve Ice Cream', 'Vanilla soft serve cone', 120.00),
(5, 'Chocolate Sundae', 'Vanilla ice cream with chocolate sauce', 180.00),
(5, 'Apple Pie', 'Warm apple pie slice with cream', 150.00);

-- Menu Items — Broast
INSERT INTO `menu_items` (`category_id`, `name`, `description`, `price`) VALUES
(6, 'Broast 2 pcs', '2 pieces crispy broasted chicken', 380.00),
(6, 'Broast 4 pcs', '4 pieces crispy broasted chicken', 720.00),
(6, 'Broast 6 pcs', '6 pieces crispy broasted chicken', 1050.00),
(6, 'Chicken Strips (4 pcs)', 'Crispy chicken tenderloins with dipping sauce', 350.00);

-- Restaurant Tables
INSERT INTO `restaurant_tables` (`table_number`, `capacity`) VALUES
('T01', 2), ('T02', 4), ('T03', 4), ('T04', 6),
('T05', 2), ('T06', 4), ('T07', 4), ('T08', 8),
('T09', 4), ('T10', 6), ('VIP1', 8), ('VIP2', 10);

COMMIT;
