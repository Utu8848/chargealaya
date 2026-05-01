-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 28, 2026 at 09:40 AM
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
-- Database: `25123857`
--

-- --------------------------------------------------------

CREATE DATABASE IF NOT EXISTS `25123857`;
USE `25123857`;

--
-- Table structure for table `chargers`
--

CREATE TABLE `chargers` (
  `charger_id` int(11) NOT NULL,
  `station_id` int(11) NOT NULL,
  `charger_type` enum('fast','normal') NOT NULL,
  `max_power_kw` decimal(6,2) NOT NULL,
  `connector_type` varchar(50) DEFAULT NULL,
  `status` enum('available','in_use','maintenance') DEFAULT 'available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chargers`
--

INSERT INTO `chargers` (`charger_id`, `station_id`, `charger_type`, `max_power_kw`, `connector_type`, `status`) VALUES
(1, 1, 'fast', 50.00, 'CCS2', 'available'),
(2, 1, 'fast', 50.00, 'CHAdeMO', 'available'),
(3, 1, 'normal', 22.00, 'Type2', 'available'),
(4, 2, 'fast', 150.00, 'CCS2', 'available'),
(5, 2, 'normal', 22.00, 'Type2', 'available'),
(6, 3, 'fast', 50.00, 'CCS2', 'available'),
(7, 3, 'normal', 7.40, 'Type2', 'available');

-- --------------------------------------------------------

--
-- Table structure for table `charging_sessions`
--

CREATE TABLE `charging_sessions` (
  `session_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `vehicle_id` int(11) NOT NULL,
  `charger_id` int(11) NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime DEFAULT NULL,
  `energy_used_kwh` decimal(10,2) DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  `session_status` char(10) NOT NULL DEFAULT 'completed'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `charging_sessions`
--

INSERT INTO `charging_sessions` (`session_id`, `user_id`, `vehicle_id`, `charger_id`, `start_time`, `end_time`, `energy_used_kwh`, `cost`, `session_status`) VALUES
(1, 3, 1, 1, '2026-01-17 22:19:37', '2026-01-18 00:19:37', 50.00, 800.00, 'completed'),
(2, 3, 1, 3, '2026-01-19 22:19:37', '2026-01-20 00:19:37', 25.00, 425.00, 'completed'),
(3, 3, 3, 2, '2026-01-12 22:19:37', '2026-01-13 01:19:37', 55.00, 880.00, 'completed'),
(4, 4, 2, 4, '2026-01-15 22:19:37', '2026-01-15 23:19:37', 35.00, 705.00, 'completed'),
(5, 4, 2, 6, '2026-01-20 22:19:37', '2026-01-21 00:19:37', 40.00, 700.00, 'completed'),
(6, 4, 4, 5, '2026-01-21 22:19:37', '2026-01-22 00:19:37', 48.00, 924.00, 'completed');

-- --------------------------------------------------------

--
-- Table structure for table `charging_stations`
--

CREATE TABLE `charging_stations` (
  `station_id` int(11) NOT NULL,
  `station_name` varchar(200) NOT NULL,
  `user_id` int(11) NOT NULL,
  `address` text NOT NULL,
  `city` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `operating_hours` varchar(100) DEFAULT NULL,
  `status` enum('online','offline','under_maintenance') DEFAULT 'online'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `charging_stations`
--

INSERT INTO `charging_stations` (`station_id`, `station_name`, `user_id`, `address`, `city`, `province`, `latitude`, `longitude`, `operating_hours`, `status`) VALUES
(1, 'TU Campus Charging Hub', 2, 'Tribhuvan University, Kirtipur', 'Kirtipur', 'Bagmati', 27.67880000, 85.29170000, '24/7', 'online'),
(2, 'Thamel EV Station', 2, 'Thamel Marg, Kathmandu', 'Kathmandu', 'Bagmati', 27.71530000, 85.31260000, '06:00-22:00', 'online'),
(3, 'Pokhara Lakeside Charging', 2, 'Lakeside, Pokhara', 'Pokhara', 'Gandaki', 28.20960000, 83.95960000, '24/7', 'online');

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `feedback_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `station_id` int(11) NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `comment` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`feedback_id`, `user_id`, `station_id`, `rating`, `comment`, `created_at`) VALUES
(1, 3, 1, 5, 'Excellent charging station! Fast and reliable service.', '2026-01-18 22:19:37'),
(2, 4, 2, 4, 'Good location but could use more chargers during peak hours.', '2026-01-20 22:19:37'),
(3, 3, 3, 5, 'Great experience! Will definitely come back.', '2026-01-21 22:19:37'),
(4, 4, 1, 3, 'Decent service, but waiting time was a bit long.', '2026-01-22 22:19:37');

-- --------------------------------------------------------

--
-- Table structure for table `maintenance`
--

CREATE TABLE `maintenance` (
  `maintenance_id` int(11) NOT NULL,
  `charger_id` int(11) NOT NULL,
  `reported_by` int(11) NOT NULL,
  `issue_description` text DEFAULT NULL,
  `reported_date` datetime DEFAULT current_timestamp(),
  `fixed_date` datetime DEFAULT NULL,
  `status` enum('open','in_progress','resolved') DEFAULT 'open'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `maintenance`
--

INSERT INTO `maintenance` (`maintenance_id`, `charger_id`, `reported_by`, `issue_description`, `reported_date`, `fixed_date`, `status`) VALUES
(1, 7, 3, 'Connector not properly locking', '2026-01-19 22:19:37', NULL, 'in_progress'),
(2, 5, 3, 'Display screen flickering', '2026-01-15 22:19:37', '2026-01-17 22:19:37', 'resolved'),
(3, 2, 4, 'Charging speed slower than expected', '2026-01-12 22:19:37', '2026-01-14 22:19:37', 'resolved');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('esewa','khalti','card','cash','pending') DEFAULT 'pending',
  `transaction_time` datetime DEFAULT current_timestamp(),
  `payment_status` enum('pending','paid') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `session_id`, `user_id`, `amount`, `payment_method`, `transaction_time`, `payment_status`) VALUES
(1, 1, 3, 800.00, 'esewa', '2026-01-18 00:19:37', 'paid'),
(2, 2, 3, 425.00, 'khalti', '2026-01-20 00:19:37', 'paid'),
(3, 3, 3, 880.00, 'cash', '2026-01-13 01:19:37', 'pending'),
(4, 4, 4, 705.00, 'card', '2026-01-15 23:19:37', 'paid'),
(5, 5, 4, 700.00, 'esewa', '2026-01-21 00:19:37', 'paid'),
(6, 6, 4, 924.00, 'khalti', '2026-01-22 00:19:37', 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `reservation_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `charger_id` int(11) NOT NULL,
  `vehicle_id` int(11) DEFAULT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `status` enum('confirmed','cancelled','completed') DEFAULT 'confirmed'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`reservation_id`, `user_id`, `charger_id`, `vehicle_id`, `start_time`, `end_time`, `status`) VALUES
(1, 3, 1, 1, '2026-01-23 22:19:37', '2026-01-24 00:19:37', 'confirmed'),
(2, 4, 4, 2, '2026-01-24 22:19:37', '2026-01-24 23:19:37', 'confirmed'),
(3, 3, 3, 3, '2026-01-25 22:19:37', '2026-01-26 01:19:37', 'confirmed'),
(4, 3, 2, 1, '2026-01-17 22:19:37', '2026-01-18 00:19:37', 'confirmed'),
(5, 4, 5, 4, '2026-01-19 22:19:37', '2026-01-20 00:19:37', 'confirmed'),
(6, 3, 6, 1, '2026-01-27 22:19:37', '2026-01-28 00:19:37', 'cancelled');

-- --------------------------------------------------------

--
-- Table structure for table `tariffs`
--

CREATE TABLE `tariffs` (
  `tariff_id` int(11) NOT NULL,
  `station_id` int(11) NOT NULL,
  `service_fee` decimal(10,2) DEFAULT NULL,
  `price_per_kwh` decimal(10,2) NOT NULL,
  `peak_start_time` time DEFAULT NULL,
  `peak_end_time` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tariffs`
--

INSERT INTO `tariffs` (`tariff_id`, `station_id`, `service_fee`, `price_per_kwh`, `peak_start_time`, `peak_end_time`) VALUES
(1, 1, 50.00, 15.00, '18:00:00', '22:00:00'),
(2, 2, 75.00, 18.00, '17:00:00', '21:00:00'),
(3, 3, 60.00, 16.00, '18:00:00', '22:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `phone` varchar(10) NOT NULL,
  `role` enum('admin','station_owner','ev_owner') NOT NULL DEFAULT 'ev_owner',
  `status` enum('active','blocked') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `first_name`, `last_name`, `email`, `password_hash`, `phone`, `role`, `status`, `created_at`) VALUES
(1, 'Admin', 'User', 'admin@chargealaya.com', '$2y$10$C8RVFCJlyvx.0iilD4eTyewm7C8fqJC9CQSvLVfPG6nE2G63vUr8m', '9848765432', 'admin', 'active', '2026-01-22 16:33:01'),
(2, 'Harry', 'Walker', 'harrywalker@gmail.com', '$2y$10$iRk4Dl4CUkps9jXeIfDixevuG.LqS9pMGkb2r1boKodyxs78/tjh6', '9841234567', 'station_owner', 'active', '2026-01-22 16:33:01'),
(3, 'John', 'Cena', 'johncena@gmail.com', '$2y$10$/Pu6ZxhBh.7tNp0RU6PqwuThUL27PrtmKJSwk0Vqm9Yo5Vbowb5EK', '9851234567', 'ev_owner', 'active', '2026-01-22 16:33:01'),
(4, 'Roman', 'Reigns', 'romanreigns@gmail.com', '$2y$10$knSuXJ8A/UEXp2GhKT9SYuqm4WBeeZ.3WAsGbScJ0icpgnyrA8YbG', '9861234567', 'ev_owner', 'active', '2026-01-22 16:33:01');

-- --------------------------------------------------------

--
-- Table structure for table `vehicles`
--

CREATE TABLE `vehicles` (
  `vehicle_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `brand` varchar(100) NOT NULL,
  `model` varchar(100) NOT NULL,
  `battery_capacity_kwh` decimal(6,2) DEFAULT NULL,
  `connector_type` varchar(50) DEFAULT NULL,
  `license_plate` varchar(50) DEFAULT NULL,
  `manufacturing_year` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vehicles`
--

INSERT INTO `vehicles` (`vehicle_id`, `user_id`, `brand`, `model`, `battery_capacity_kwh`, `connector_type`, `license_plate`, `manufacturing_year`) VALUES
(1, 3, 'Tesla', 'Model 3', 75.00, 'CCS2', 'BA-01-PA-1234', 2023),
(2, 4, 'Nissan', 'Leaf', 40.00, 'CHAdeMO', 'BA-02-PA-5678', 2022),
(3, 3, 'BYD', 'Atto 3', 60.48, 'CCS2', 'BA-01-PA-9999', 2024),
(4, 4, 'MG', 'ZS EV', 50.30, 'CCS2', 'BA-02-PA-8888', 2023),
(5, 4, 'Hyundai', 'Kona Electric', 64.00, 'CCS2', 'BA-02-PA-7777', 2022);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `chargers`
--
ALTER TABLE `chargers`
  ADD PRIMARY KEY (`charger_id`),
  ADD KEY `idx_station_status` (`station_id`,`status`);

--
-- Indexes for table `charging_sessions`
--
ALTER TABLE `charging_sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `vehicle_id` (`vehicle_id`),
  ADD KEY `charger_id` (`charger_id`),
  ADD KEY `idx_user_session` (`user_id`,`session_status`),
  ADD KEY `idx_start_time` (`start_time`);

--
-- Indexes for table `charging_stations`
--
ALTER TABLE `charging_stations`
  ADD PRIMARY KEY (`station_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_city` (`city`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`feedback_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_station_rating` (`station_id`,`rating`);

--
-- Indexes for table `maintenance`
--
ALTER TABLE `maintenance`
  ADD PRIMARY KEY (`maintenance_id`),
  ADD KEY `reported_by` (`reported_by`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_charger` (`charger_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `session_id` (`session_id`),
  ADD KEY `idx_payment_status` (`payment_status`),
  ADD KEY `idx_user_payment` (`user_id`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`reservation_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `charger_id` (`charger_id`),
  ADD KEY `vehicle_id` (`vehicle_id`),
  ADD KEY `idx_reservation_time` (`start_time`,`end_time`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `tariffs`
--
ALTER TABLE `tariffs`
  ADD PRIMARY KEY (`tariff_id`),
  ADD KEY `idx_station_id` (`station_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`);

--
-- Indexes for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD PRIMARY KEY (`vehicle_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `chargers`
--
ALTER TABLE `chargers`
  MODIFY `charger_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `charging_sessions`
--
ALTER TABLE `charging_sessions`
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `charging_stations`
--
ALTER TABLE `charging_stations`
  MODIFY `station_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `feedback_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `maintenance`
--
ALTER TABLE `maintenance`
  MODIFY `maintenance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `reservation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `tariffs`
--
ALTER TABLE `tariffs`
  MODIFY `tariff_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `vehicle_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `chargers`
--
ALTER TABLE `chargers`
  ADD CONSTRAINT `chargers_ibfk_1` FOREIGN KEY (`station_id`) REFERENCES `charging_stations` (`station_id`) ON DELETE CASCADE;

--
-- Constraints for table `charging_sessions`
--
ALTER TABLE `charging_sessions`
  ADD CONSTRAINT `charging_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `charging_sessions_ibfk_2` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`vehicle_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `charging_sessions_ibfk_3` FOREIGN KEY (`charger_id`) REFERENCES `chargers` (`charger_id`) ON DELETE CASCADE;

--
-- Constraints for table `charging_stations`
--
ALTER TABLE `charging_stations`
  ADD CONSTRAINT `charging_stations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `feedback_ibfk_2` FOREIGN KEY (`station_id`) REFERENCES `charging_stations` (`station_id`) ON DELETE CASCADE;

--
-- Constraints for table `maintenance`
--
ALTER TABLE `maintenance`
  ADD CONSTRAINT `maintenance_ibfk_1` FOREIGN KEY (`charger_id`) REFERENCES `chargers` (`charger_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `maintenance_ibfk_2` FOREIGN KEY (`reported_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `charging_sessions` (`session_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`charger_id`) REFERENCES `chargers` (`charger_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservations_ibfk_3` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`vehicle_id`) ON DELETE SET NULL;

--
-- Constraints for table `tariffs`
--
ALTER TABLE `tariffs`
  ADD CONSTRAINT `tariffs_ibfk_1` FOREIGN KEY (`station_id`) REFERENCES `charging_stations` (`station_id`) ON DELETE CASCADE;

--
-- Constraints for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD CONSTRAINT `vehicles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
