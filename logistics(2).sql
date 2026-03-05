-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Mar 05, 2026 at 11:46 AM
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
-- Database: `logistics`
--

-- --------------------------------------------------------

--
-- Table structure for table `assets`
--

CREATE TABLE `assets` (
  `id` int(11) NOT NULL,
  `asset_name` varchar(255) NOT NULL,
  `asset_type` enum('vehicle','equipment','warehouse','other') NOT NULL,
  `status` enum('good','warning','bad') NOT NULL DEFAULT 'good',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `asset_condition` int(11) NOT NULL DEFAULT 100
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assets`
--

INSERT INTO `assets` (`id`, `asset_name`, `asset_type`, `status`, `created_at`, `updated_at`, `asset_condition`) VALUES
(25, 'Car', 'vehicle', 'good', '2026-03-01 14:00:24', '2026-03-01 14:00:24', 70),
(26, 'car2', 'vehicle', 'warning', '2026-03-02 08:48:36', '2026-03-02 08:48:36', 50);

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `color_class` varchar(50) DEFAULT NULL,
  `item_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `category_name`, `description`, `color_class`, `item_count`, `created_at`) VALUES
(1, 'Electronics', NULL, 'blue', 0, '2026-03-03 10:47:45'),
(2, 'Furniture', NULL, 'emerald', 0, '2026-03-03 10:47:45'),
(3, 'Clothing', NULL, 'purple', 4, '2026-03-03 10:47:45'),
(4, 'Food & Beverages', NULL, 'amber', 0, '2026-03-03 10:47:45'),
(5, 'Office Supplies', NULL, 'rose', 0, '2026-03-03 10:47:45'),
(6, 'Sports', NULL, 'amber', 1, '2026-03-03 11:49:42');

-- --------------------------------------------------------

--
-- Stand-in structure for view `dashboard_stats`
-- (See below for the actual view)
--
CREATE TABLE `dashboard_stats` (
`total_items` bigint(21)
,`total_categories` bigint(21)
,`low_stock_items` bigint(21)
,`out_of_stock_items` bigint(21)
,`total_inventory_value` decimal(42,2)
,`active_suppliers` bigint(21)
);

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `document_type` varchar(100) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `asset_id` int(11) DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `documents`
--

INSERT INTO `documents` (`id`, `title`, `document_type`, `file_name`, `file_path`, `file_size`, `description`, `asset_id`, `uploaded_by`, `expiry_date`, `uploaded_at`) VALUES
(1, 'Vehicle registration', 'registration', 'Untitled document.docx', 'uploads/documents/1772470674_69a5c192d8bad.docx', 10034, 'afasf', 26, 2, '2026-03-11', '2026-03-02 16:57:54');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_items`
--

CREATE TABLE `inventory_items` (
  `id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `sku` varchar(50) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `price` decimal(10,2) NOT NULL,
  `reorder_level` int(11) DEFAULT 10,
  `description` text DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `status` enum('in_stock','low_stock','out_of_stock') GENERATED ALWAYS AS (case when `quantity` <= 0 then 'out_of_stock' when `quantity` <= `reorder_level` then 'low_stock' else 'in_stock' end) STORED,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_items`
--

INSERT INTO `inventory_items` (`id`, `item_name`, `sku`, `category_id`, `quantity`, `price`, `reorder_level`, `description`, `supplier_id`, `last_updated`, `created_at`) VALUES
(2, 'vapeshark', 'TS-BLk-S', 3, 0, 100.00, 10, 'good', 6, '2026-03-03 12:42:36', '2026-03-03 11:20:08'),
(3, 'JOKES CLOTHING', 'JC-WHI-XXL', 3, 5, 100.00, 10, 'hehe', 4, '2026-03-03 12:42:16', '2026-03-03 11:50:44'),
(4, 'RealJOKes', 'RJ-WHI-M', 3, 45, 100.00, 10, 'hehe', 1, '2026-03-03 12:41:58', '2026-03-03 12:37:40'),
(5, 'Molten', 'BALL-LG-BLK', 6, 50, 100.00, 10, 'good', 1, '2026-03-04 06:43:33', '2026-03-04 06:43:33'),
(6, 'jjk', 'JJK-BLK-XXL', 3, 100, 100.00, 10, 'gwge', 6, '2026-03-05 10:18:21', '2026-03-05 10:18:21');

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_alerts`
--

CREATE TABLE `maintenance_alerts` (
  `id` int(11) NOT NULL,
  `asset_name` varchar(255) NOT NULL,
  `issue` varchar(255) NOT NULL,
  `priority` enum('low','medium','high') NOT NULL,
  `due_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_date` date DEFAULT NULL,
  `status` enum('pending','done') NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `maintenance_alerts`
--

INSERT INTO `maintenance_alerts` (`id`, `asset_name`, `issue`, `priority`, `due_date`, `created_at`, `completed_date`, `status`) VALUES
(1, 'car', 'oil change', 'medium', '2026-03-03', '2026-03-02 09:46:35', '2026-03-02', 'done'),
(4, 'car3', 'cahnge belt', 'high', '2026-03-04', '2026-03-02 13:13:10', '2026-03-03', 'done'),
(5, 'car3', 'cahnge belt', 'high', '2026-03-04', '2026-03-02 13:13:18', '2026-03-03', 'done');

-- --------------------------------------------------------

--
-- Table structure for table `price_history`
--

CREATE TABLE `price_history` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `old_price` decimal(10,2) NOT NULL,
  `new_price` decimal(10,2) NOT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

CREATE TABLE `purchase_orders` (
  `id` int(11) NOT NULL,
  `po_number` varchar(50) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `order_date` date NOT NULL,
  `expected_delivery` date DEFAULT NULL,
  `actual_delivery` date DEFAULT NULL,
  `status` enum('draft','pending','approved','rejected','completed','cancelled') DEFAULT 'draft',
  `priority` enum('low','normal','high','urgent') DEFAULT 'normal',
  `subtotal` decimal(15,2) NOT NULL,
  `tax_amount` decimal(15,2) DEFAULT 0.00,
  `shipping_cost` decimal(15,2) DEFAULT 0.00,
  `total_amount` decimal(15,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_orders`
--

INSERT INTO `purchase_orders` (`id`, `po_number`, `supplier_id`, `order_date`, `expected_delivery`, `actual_delivery`, `status`, `priority`, `subtotal`, `tax_amount`, `shipping_cost`, `total_amount`, `notes`, `approved_by`, `approved_at`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'PO-2026-03-0001', 1, '2026-03-05', '2026-03-13', NULL, 'pending', 'high', 100.00, 10.00, 0.00, 110.00, 'wegweg\nwegwege', NULL, NULL, 2, '2026-03-05 10:07:09', '2026-03-05 10:07:09');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_items`
--

CREATE TABLE `purchase_order_items` (
  `id` bigint(20) NOT NULL,
  `po_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `total_price` decimal(15,2) NOT NULL,
  `received_quantity` int(11) DEFAULT 0,
  `status` enum('pending','partial','received','cancelled') DEFAULT 'pending',
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase_order_items`
--

INSERT INTO `purchase_order_items` (`id`, `po_id`, `item_id`, `quantity`, `unit_price`, `total_price`, `received_quantity`, `status`, `notes`) VALUES
(1, 1, 3, 1, 100.00, 100.00, 0, 'pending', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `receiving_history`
--

CREATE TABLE `receiving_history` (
  `id` bigint(20) NOT NULL,
  `po_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity_received` int(11) NOT NULL,
  `received_by` int(11) NOT NULL,
  `received_at` datetime DEFAULT current_timestamp(),
  `stock_movement_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shipments`
--

CREATE TABLE `shipments` (
  `shipment_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `vehicle_id` int(11) DEFAULT NULL,
  `driver_id` int(11) DEFAULT NULL,
  `shipment_status` enum('pending','in_transit','delivered','delayed') DEFAULT 'pending',
  `departure_time` datetime DEFAULT NULL,
  `estimated_arrival` datetime DEFAULT NULL,
  `actual_arrival` datetime DEFAULT NULL,
  `current_location` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shipment_tracking`
--

CREATE TABLE `shipment_tracking` (
  `tracking_id` int(11) NOT NULL,
  `shipment_id` int(11) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `status_update` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_movements`
--

CREATE TABLE `stock_movements` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `movement_type` enum('in','out','adjustment') NOT NULL,
  `quantity_change` int(11) NOT NULL,
  `previous_quantity` int(11) NOT NULL,
  `new_quantity` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_movements`
--

INSERT INTO `stock_movements` (`id`, `item_id`, `movement_type`, `quantity_change`, `previous_quantity`, `new_quantity`, `notes`, `user_id`, `created_at`) VALUES
(2, 2, 'in', 10, 0, 10, 'Initial stock', NULL, '2026-03-03 11:20:08'),
(3, 3, 'in', 12, 0, 12, 'Initial stock', NULL, '2026-03-03 11:50:44'),
(4, 4, 'in', 100, 0, 100, 'Initial stock', NULL, '2026-03-03 12:37:40'),
(5, 4, 'out', 55, 100, 45, 'Stock adjustment', NULL, '2026-03-03 12:41:58'),
(6, 3, 'out', 7, 12, 5, 'Stock adjustment', NULL, '2026-03-03 12:42:16'),
(7, 2, 'out', 10, 10, 0, 'Stock adjustment', NULL, '2026-03-03 12:42:36'),
(8, 5, 'in', 50, 0, 50, 'Initial stock', NULL, '2026-03-04 06:43:33'),
(9, 6, 'in', 100, 0, 100, 'Initial stock', NULL, '2026-03-05 10:18:21');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `supplier_name` varchar(255) NOT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `supplier_name`, `contact_person`, `email`, `phone`, `address`, `created_at`) VALUES
(1, 'ABC Supplies Inc.', 'John Smith', 'john@abcsupplies.com', '+1-555-0101', '123 Business Ave, New York, NY 10001', '2026-03-03 10:49:06'),
(2, 'Global Traders Ltd.', 'Sarah Johnson', 'sarah@globaltraders.com', '+1-555-0102', '456 Commerce St, Los Angeles, CA 90001', '2026-03-03 10:49:06'),
(3, 'Direct Source Co.', 'Mike Wilson', 'mike@directsource.com', '+1-555-0103', '789 Industrial Rd, Chicago, IL 60601', '2026-03-03 10:49:06'),
(4, 'Quality Distributors', 'Emily Brown', 'emily@qualitydist.com', '+1-555-0104', '321 Warehouse Blvd, Houston, TX 77001', '2026-03-03 10:49:06'),
(5, 'Premier Goods Ltd.', 'David Lee', 'david@premiergoods.com', '+1-555-0105', '654 Market St, San Francisco, CA 94101', '2026-03-03 10:49:06'),
(6, 'Allied Supply Chain', 'Lisa Anderson', 'lisa@alliedsupply.com', '+1-555-0106', '987 Logistics Pkwy, Miami, FL 33101', '2026-03-03 10:49:06'),
(7, 'Metro Wholesale', 'Robert Taylor', 'robert@metrowholesale.com', '+1-555-0107', '147 Distribution Dr, Seattle, WA 98101', '2026-03-03 10:49:06'),
(8, 'Summit Traders', 'Jennifer Garcia', 'jennifer@summittraders.com', '+1-555-0108', '258 Import Ave, Boston, MA 02101', '2026-03-03 10:49:06'),
(9, 'Pioneer Supplies', 'Thomas Martinez', 'thomas@pioneersupplies.com', '+1-555-0109', '369 Export Ln, Denver, CO 80201', '2026-03-03 10:49:06'),
(10, 'Apex International', 'Maria Rodriguez', 'maria@apexintl.com', '+1-555-0110', '741 Global Circle, Atlanta, GA 30301', '2026-03-03 10:49:06');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `employee_id` varchar(20) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(50) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `avatar_url` varchar(255) DEFAULT NULL,
  `role` enum('admin','dispatcher','driver','employee') NOT NULL DEFAULT 'employee',
  `status` enum('active','inactive','on-leave') NOT NULL DEFAULT 'active',
  `department` varchar(100) DEFAULT NULL,
  `join_date` date DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `employee_id`, `username`, `email`, `password`, `full_name`, `phone`, `avatar_url`, `role`, `status`, `department`, `join_date`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'EMP5675', 'asierra389@gmail.com', 'asierra389@gmail.com', '$2y$10$zJBe22LYnUvaLoapWU1hi.1Slr.BWaFbjBBzxKL1vP3L/KxKyj3zC', 'Joshua sierra', NULL, NULL, 'employee', 'active', NULL, NULL, NULL, '2026-03-01 04:34:35', '2026-03-01 04:34:35'),
(2, 'EMP4112', 'joshua', 'asierr389@gmail.com', '$2y$10$KBnZdY6N5bExhyI.Y3JS1eFPRsIhp5TyW6FPy.cIy1XpsCcKNfTnW', 'Joshua arncel sierra', NULL, NULL, 'admin', 'active', NULL, NULL, NULL, '2026-03-01 04:38:42', '2026-03-01 04:39:48');

-- --------------------------------------------------------

--
-- Table structure for table `user_activity_logs`
--

CREATE TABLE `user_activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `document_id` int(11) NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `timestamp` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_activity_logs`
--

INSERT INTO `user_activity_logs` (`id`, `user_id`, `document_id`, `action_type`, `timestamp`) VALUES
(1, 1, 1, 'download', '2026-03-03 09:45:32');

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `login_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `logout_time` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure for view `dashboard_stats`
--
DROP TABLE IF EXISTS `dashboard_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `dashboard_stats`  AS SELECT (select count(0) from `inventory_items`) AS `total_items`, (select count(0) from `categories`) AS `total_categories`, (select count(0) from `inventory_items` where `inventory_items`.`quantity` <= `inventory_items`.`reorder_level` and `inventory_items`.`quantity` > 0) AS `low_stock_items`, (select count(0) from `inventory_items` where `inventory_items`.`quantity` <= 0) AS `out_of_stock_items`, (select sum(`inventory_items`.`quantity` * `inventory_items`.`price`) from `inventory_items`) AS `total_inventory_value`, (select count(distinct `inventory_items`.`supplier_id`) from `inventory_items`) AS `active_suppliers` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `assets`
--
ALTER TABLE `assets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_asset_type` (`asset_type`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `category_name` (`category_name`),
  ADD KEY `idx_category_name` (`category_name`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `asset_id` (`asset_id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `idx_sku` (`sku`),
  ADD KEY `idx_category` (`category_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_supplier` (`supplier_id`);

--
-- Indexes for table `maintenance_alerts`
--
ALTER TABLE `maintenance_alerts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `price_history`
--
ALTER TABLE `price_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `po_number` (`po_number`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_po_number` (`po_number`),
  ADD KEY `idx_supplier` (`supplier_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_po` (`po_id`),
  ADD KEY `idx_item` (`item_id`);

--
-- Indexes for table `receiving_history`
--
ALTER TABLE `receiving_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `po_id` (`po_id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `received_by` (`received_by`),
  ADD KEY `stock_movement_id` (`stock_movement_id`);

--
-- Indexes for table `shipments`
--
ALTER TABLE `shipments`
  ADD PRIMARY KEY (`shipment_id`);

--
-- Indexes for table `shipment_tracking`
--
ALTER TABLE `shipment_tracking`
  ADD PRIMARY KEY (`tracking_id`),
  ADD KEY `shipment_id` (`shipment_id`);

--
-- Indexes for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_item_id` (`item_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_supplier_name` (`supplier_name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_employee_id` (`employee_id`);

--
-- Indexes for table `user_activity_logs`
--
ALTER TABLE `user_activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `document_id` (`document_id`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_token` (`session_token`),
  ADD KEY `idx_token` (`session_token`),
  ADD KEY `idx_user_active` (`user_id`,`is_active`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `assets`
--
ALTER TABLE `assets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `inventory_items`
--
ALTER TABLE `inventory_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `maintenance_alerts`
--
ALTER TABLE `maintenance_alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `price_history`
--
ALTER TABLE `price_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `receiving_history`
--
ALTER TABLE `receiving_history`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `shipments`
--
ALTER TABLE `shipments`
  MODIFY `shipment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `shipment_tracking`
--
ALTER TABLE `shipment_tracking`
  MODIFY `tracking_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_movements`
--
ALTER TABLE `stock_movements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `user_activity_logs`
--
ALTER TABLE `user_activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `documents_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `price_history`
--
ALTER TABLE `price_history`
  ADD CONSTRAINT `price_history_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `inventory_items` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD CONSTRAINT `purchase_orders_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  ADD CONSTRAINT `purchase_orders_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `purchase_orders_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD CONSTRAINT `purchase_order_items_ibfk_1` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_order_items_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `inventory_items` (`id`);

--
-- Constraints for table `receiving_history`
--
ALTER TABLE `receiving_history`
  ADD CONSTRAINT `receiving_history_ibfk_1` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`),
  ADD CONSTRAINT `receiving_history_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `inventory_items` (`id`),
  ADD CONSTRAINT `receiving_history_ibfk_3` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `receiving_history_ibfk_4` FOREIGN KEY (`stock_movement_id`) REFERENCES `stock_movements` (`id`);

--
-- Constraints for table `shipment_tracking`
--
ALTER TABLE `shipment_tracking`
  ADD CONSTRAINT `shipment_tracking_ibfk_1` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`shipment_id`);

--
-- Constraints for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD CONSTRAINT `stock_movements_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `inventory_items` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_activity_logs`
--
ALTER TABLE `user_activity_logs`
  ADD CONSTRAINT `user_activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `user_activity_logs_ibfk_2` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`);

--
-- Constraints for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
