-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 21, 2025 at 01:57 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@OLD_COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `monitoring`
--

-- --------------------------------------------------------

--
-- Table structure for table `actions`
--

CREATE TABLE `actions` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(200) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `action_type` enum('create','update','delete','login','logout','approve_user','decline_user','reset_password','export') NOT NULL,
  `target_table` varchar(128) DEFAULT NULL,
  `target_id` bigint(20) UNSIGNED DEFAULT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `action_type`, `target_table`, `target_id`, `details`, `ip_address`, `created_at`, `updated_at`) VALUES
(1, 2, 'login', NULL, NULL, '{\"ip\":\"::1\"}', NULL, '2025-11-21 11:46:23', '2025-11-21 11:46:23'),
(2, 2, 'login', NULL, NULL, '{\"ip\":\"::1\"}', NULL, '2025-11-21 11:46:48', '2025-11-21 11:46:48'),
(3, 2, 'login', NULL, NULL, '{\"ip\":\"::1\"}', NULL, '2025-11-21 11:47:00', '2025-11-21 11:47:00'),
(4, 3, 'login', NULL, NULL, '{\"ip\":\"::1\"}', NULL, '2025-11-21 11:52:31', '2025-11-21 11:52:31'),
(5, 3, 'login', NULL, NULL, '{\"ip\":\"::1\"}', NULL, '2025-11-21 11:55:03', '2025-11-21 11:55:03'),
(6, 3, 'login', NULL, NULL, '{\"ip\":\"::1\"}', NULL, '2025-11-21 11:56:29', '2025-11-21 11:56:29'),
(7, 3, 'login', NULL, NULL, '{\"ip\":\"::1\"}', NULL, '2025-11-21 11:56:31', '2025-11-21 11:56:31'),
(8, 3, 'login', NULL, NULL, '{\"ip\":\"::1\"}', NULL, '2025-11-21 11:57:31', '2025-11-21 11:57:31'),
(9, 3, 'login', NULL, NULL, '{\"ip\":\"::1\"}', NULL, '2025-11-21 11:58:50', '2025-11-21 11:58:50'),
(10, 3, 'login', NULL, NULL, '{\"ip\":\"::1\"}', NULL, '2025-11-21 11:58:52', '2025-11-21 11:58:52'),
(11, 3, 'login', NULL, NULL, '{\"ip\":\"::1\"}', NULL, '2025-11-21 12:13:08', '2025-11-21 12:13:08'),
(12, 3, 'login', NULL, NULL, '{\"ip\":\"::1\"}', NULL, '2025-11-21 12:15:22', '2025-11-21 12:15:22'),
(13, 3, 'login', NULL, NULL, '{\"ip\":\"::1\"}', NULL, '2025-11-21 12:16:40', '2025-11-21 12:16:40'),
(14, 3, 'login', NULL, NULL, '{\"ip\":\"::1\"}', NULL, '2025-11-21 12:30:21', '2025-11-21 12:30:21'),
(15, 3, 'login', NULL, NULL, '{\"ip\":\"::1\"}', NULL, '2025-11-21 12:30:35', '2025-11-21 12:30:35'),
(16, 3, 'login', NULL, NULL, '{\"ip\":\"::1\"}', NULL, '2025-11-21 12:30:55', '2025-11-21 12:30:55'),
(17, 3, 'login', NULL, NULL, '{\"ip\":\"::1\"}', NULL, '2025-11-21 12:53:31', '2025-11-21 12:53:31');

-- --------------------------------------------------------

--
-- Table structure for table `event_types`
--

CREATE TABLE `event_types` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(200) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `monitored_information`
--

CREATE TABLE `monitored_information` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `event_date` date NOT NULL,
  `region_id` int(10) UNSIGNED NOT NULL,
  `location` varchar(500) DEFAULT NULL,
  `event_type_id` int(10) UNSIGNED NOT NULL,
  `sub_event_type_id` int(10) UNSIGNED NOT NULL,
  `action_id` int(10) UNSIGNED NOT NULL,
  `source_url` varchar(1000) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `fatalities` int(10) UNSIGNED DEFAULT 0,
  `rating` tinyint(3) UNSIGNED DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `regions`
--

CREATE TABLE `regions` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(200) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sub_event_types`
--

CREATE TABLE `sub_event_types` (
  `id` int(10) UNSIGNED NOT NULL,
  `event_type_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(200) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `monitor_number` bigint(20) UNSIGNED DEFAULT NULL,
  `monitor_id_code` varchar(16) DEFAULT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('superadmin','admin','user') DEFAULT 'user',
  `status` enum('pending','approved','declined') DEFAULT 'approved',
  `profile_picture` varchar(512) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `monitor_number`, `monitor_id_code`, `username`, `email`, `password_hash`, `role`, `status`, `profile_picture`, `is_active`, `created_at`, `updated_at`) VALUES
(3, 3, 'CCSPM03', 'superadmin', 'admin@example.com', '$2b$12$mju0YWgkUhq2TVpc0aGc6.nNvqw8WL.d5SW4zHaoHW9.cdhJM3XIO', 'superadmin', 'approved', NULL, 1, '2025-11-21 11:52:06', '2025-11-21 11:52:06');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `actions`
--
ALTER TABLE `actions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `event_types`
--
ALTER TABLE `event_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `monitored_information`
--
ALTER TABLE `monitored_information`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `region_id` (`region_id`),
  ADD KEY `event_type_id` (`event_type_id`),
  ADD KEY `sub_event_type_id` (`sub_event_type_id`),
  ADD KEY `action_id` (`action_id`);

--
-- Indexes for table `regions`
--
ALTER TABLE `regions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sub_event_types`
--
ALTER TABLE `sub_event_types`
  ADD PRIMARY KEY (`id`),
  ADD KEY `event_type_id` (`event_type_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--
ALTER TABLE `actions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `activity_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;
ALTER TABLE `event_types`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `monitored_information`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `regions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `sub_event_types`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints
--
ALTER TABLE `monitored_information`
  ADD CONSTRAINT `monitored_information_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `monitored_information_ibfk_2` FOREIGN KEY (`region_id`) REFERENCES `regions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `monitored_information_ibfk_3` FOREIGN KEY (`event_type_id`) REFERENCES `event_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `monitored_information_ibfk_4` FOREIGN KEY (`sub_event_type_id`) REFERENCES `sub_event_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `monitored_information_ibfk_5` FOREIGN KEY (`action_id`) REFERENCES `actions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE `sub_event_types`
  ADD CONSTRAINT `sub_event_types_ibfk_1` FOREIGN KEY (`event_type_id`) REFERENCES `event_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;
