-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 20, 2025 at 06:58 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `coffee`
--

-- --------------------------------------------------------

--
-- Table structure for table `locations`
--

CREATE TABLE `locations` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `locations`
--

INSERT INTO `locations` (`id`, `code`, `name`, `description`, `created_at`) VALUES
(1, 'WH-001', 'Main Storage', NULL, '2025-09-19 13:10:56'),
(2, 'WH-002', 'Front Counter', NULL, '2025-09-19 13:10:56');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `sku` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `unit` varchar(30) DEFAULT 'pcs',
  `expiration_date` date DEFAULT NULL,
  `min_qty` int(11) DEFAULT 0,
  `max_qty` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `warehouse_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `sku`, `name`, `description`, `category`, `unit`, `expiration_date`, `min_qty`, `max_qty`, `created_at`, `updated_at`, `warehouse_id`) VALUES
(1, 'CB001', 'Arabica Coffee Beans', 'High quality Arabica beans, medium roast.', 'Coffee Beans', 'kg', '2025-12-31', 5, 50, '2025-09-19 13:10:56', NULL, 1),
(2, 'CB002', 'Robusta Coffee Beans', 'Strong flavor, used for espresso blends.', 'Coffee Beans', 'kg', '2025-12-31', 3, 30, '2025-09-19 13:10:56', NULL, 1),
(3, 'ML001', 'Whole Milk', 'Fresh dairy milk, used for lattes and cappuccinos.', 'Dairy', 'liters', '2025-10-01', 10, 100, '2025-09-19 13:10:56', NULL, 1),
(4, 'ML002', 'Almond Milk', 'Non-dairy alternative, unsweetened.', 'Dairy Alternative', 'liters', '2025-11-01', 5, 50, '2025-09-19 13:10:56', NULL, 1),
(5, 'ML003', 'Oat Milk', 'Creamy plant-based milk.', 'Dairy Alternative', 'liters', '2025-11-15', 5, 50, '2025-09-19 13:10:56', NULL, 1),
(6, 'SY001', 'Vanilla Syrup', 'Used for flavored lattes.', 'Syrups', 'bottle', '2026-01-01', 5, 30, '2025-09-19 13:10:56', NULL, 1),
(7, 'SY002', 'Caramel Syrup', 'Used in caramel macchiatos and frappes.', 'Syrups', 'bottle', '2026-01-01', 5, 30, '2025-09-19 13:10:56', NULL, 1),
(8, 'SY003', 'Hazelnut Syrup', 'Classic syrup for nutty flavors.', 'Syrups', 'bottle', '2026-01-01', 5, 30, '2025-09-19 13:10:56', NULL, 1),
(9, 'CP001', '12oz Paper Cups', 'Standard serving cups for hot beverages.', 'Packaging', 'pcs', NULL, 100, 1000, '2025-09-19 13:10:56', NULL, 2),
(10, 'CP002', '16oz Paper Cups', 'Larger size for iced drinks.', 'Packaging', 'pcs', NULL, 100, 1000, '2025-09-19 13:10:56', NULL, 2),
(11, 'CP003', 'Cup Lids', 'Lids compatible with 12oz and 16oz cups.', 'Packaging', 'pcs', NULL, 200, 2000, '2025-09-19 13:10:56', NULL, 2),
(12, 'SW001', 'White Sugar', 'Used in all beverages.', 'Sweeteners', 'kg', '2026-03-01', 2, 20, '2025-09-19 13:10:56', NULL, 1),
(13, 'SW002', 'Brown Sugar', 'Preferred for iced drinks.', 'Sweeteners', 'kg', '2026-03-01', 1, 10, '2025-09-19 13:10:56', NULL, 1),
(14, 'SW003', 'Stevia Packets', 'Low-calorie sweetener.', 'Sweeteners', 'box', '2026-05-01', 1, 5, '2025-09-19 13:10:56', NULL, 1),
(15, 'OT001', 'Ice Cubes', 'Packaged ice for cold drinks.', 'Other', 'kg', '2025-10-01', 10, 100, '2025-09-19 13:10:56', NULL, 1),
(16, 'OT002', 'Whipped Cream', 'Used as topping for frappes.', 'Dairy', 'can', '2025-11-01', 5, 20, '2025-09-19 13:10:56', NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `product_locations`
--

CREATE TABLE `product_locations` (
  `product_id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_locations`
--

INSERT INTO `product_locations` (`product_id`, `location_id`, `quantity`) VALUES
(1, 1, 20),
(2, 1, 15),
(3, 1, 50),
(4, 1, 30),
(5, 1, 30),
(6, 1, 10),
(7, 1, 10),
(8, 1, 10),
(9, 2, 500),
(10, 2, 500),
(11, 2, 1000),
(12, 1, 10),
(13, 1, 5),
(14, 1, 2),
(15, 1, 100),
(16, 1, 10);

-- --------------------------------------------------------

--
-- Table structure for table `stock_transactions`
--

CREATE TABLE `stock_transactions` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `location_from` int(11) DEFAULT NULL,
  `location_to` int(11) DEFAULT NULL,
  `qty` int(11) NOT NULL,
  `type` enum('stock-in','stock-out','transfer') NOT NULL,
  `reference` varchar(255) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `trans_date` datetime DEFAULT current_timestamp(),
  `user_name` varchar(100) DEFAULT 'system',
  `expiration_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `fk_products_warehouse` (`warehouse_id`);

--
-- Indexes for table `product_locations`
--
ALTER TABLE `product_locations`
  ADD PRIMARY KEY (`product_id`,`location_id`),
  ADD UNIQUE KEY `uq_product` (`product_id`),
  ADD KEY `location_id` (`location_id`);

--
-- Indexes for table `stock_transactions`
--
ALTER TABLE `stock_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `location_from` (`location_from`),
  ADD KEY `location_to` (`location_to`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `stock_transactions`
--
ALTER TABLE `stock_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_warehouse` FOREIGN KEY (`warehouse_id`) REFERENCES `locations` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `product_locations`
--
ALTER TABLE `product_locations`
  ADD CONSTRAINT `product_locations_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_locations_ibfk_2` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_transactions`
--
ALTER TABLE `stock_transactions`
  ADD CONSTRAINT `stock_transactions_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_transactions_ibfk_2` FOREIGN KEY (`location_from`) REFERENCES `locations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `stock_transactions_ibfk_3` FOREIGN KEY (`location_to`) REFERENCES `locations` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
