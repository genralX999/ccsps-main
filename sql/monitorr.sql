-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 22, 2025 at 12:07 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
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

--
-- Dumping data for table `actions`
--

INSERT INTO `actions` (`id`, `name`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'trrrr', 1, '2025-11-22 10:12:36', '2025-11-22 10:12:36'),
(2, 'rape', 1, '2025-11-22 10:32:21', '2025-11-22 10:32:21');

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
(17, 3, 'login', NULL, NULL, '{\"ip\":\"::1\"}', NULL, '2025-11-21 12:53:31', '2025-11-21 12:53:31'),
(18, 4, 'login', NULL, NULL, '{\"username\":\"rootadmin\"}', '::1', '2025-11-22 09:15:45', '2025-11-22 09:15:45'),
(19, 4, 'login', NULL, NULL, '{\"username\":\"rootadmin\"}', '::1', '2025-11-22 09:18:37', '2025-11-22 09:18:37'),
(20, 4, 'login', NULL, NULL, '{\"username\":\"rootadmin\"}', '::1', '2025-11-22 09:21:37', '2025-11-22 09:21:37'),
(21, 4, 'login', NULL, NULL, '{\"username\":\"rootadmin\"}', '::1', '2025-11-22 09:22:06', '2025-11-22 09:22:06'),
(22, 4, 'login', NULL, NULL, '{\"username\":\"rootadmin\"}', '::1', '2025-11-22 09:22:14', '2025-11-22 09:22:14'),
(23, 4, 'login', NULL, NULL, '{\"username\":\"rootadmin\"}', '::1', '2025-11-22 09:22:35', '2025-11-22 09:22:35'),
(24, 4, 'login', NULL, NULL, '{\"username\":\"rootadmin\"}', '::1', '2025-11-22 09:24:28', '2025-11-22 09:24:28'),
(25, 4, 'login', NULL, NULL, '{\"username\":\"rootadmin\"}', '::1', '2025-11-22 09:24:48', '2025-11-22 09:24:48'),
(26, 4, 'login', NULL, NULL, '{\"username\":\"rootadmin\"}', '::1', '2025-11-22 09:26:58', '2025-11-22 09:26:58'),
(27, 4, 'logout', NULL, NULL, '[]', '::1', '2025-11-22 09:47:08', '2025-11-22 09:47:08'),
(28, 4, 'login', NULL, NULL, '{\"username\":\"rootadmin\"}', '::1', '2025-11-22 09:48:30', '2025-11-22 09:48:30'),
(29, 4, 'create', 'regions', 1, '{\"name\":\"oromia\"}', '::1', '2025-11-22 09:53:50', '2025-11-22 09:53:50'),
(30, 4, 'create', 'users', 5, '{\"monitor_id_code\":\"CCSPM10000\",\"username\":\"user\"}', '::1', '2025-11-22 09:57:06', '2025-11-22 09:57:06'),
(31, 4, 'logout', NULL, NULL, '[]', '::1', '2025-11-22 09:57:14', '2025-11-22 09:57:14'),
(32, 4, 'login', NULL, NULL, '{\"username\":\"rootadmin\"}', '::1', '2025-11-22 09:58:43', '2025-11-22 09:58:43'),
(33, 4, 'logout', NULL, NULL, '[]', '::1', '2025-11-22 09:58:48', '2025-11-22 09:58:48'),
(34, 5, 'login', NULL, NULL, '{\"username\":\"user\"}', '::1', '2025-11-22 09:58:56', '2025-11-22 09:58:56'),
(35, 5, 'login', NULL, NULL, '{\"username\":\"user\"}', '::1', '2025-11-22 10:00:20', '2025-11-22 10:00:20'),
(36, 5, 'logout', NULL, NULL, '[]', '::1', '2025-11-22 10:10:21', '2025-11-22 10:10:21'),
(37, 4, 'login', NULL, NULL, '{\"username\":\"rootadmin\"}', '::1', '2025-11-22 10:10:26', '2025-11-22 10:10:26'),
(38, 4, 'create', 'event_types', 1, '{\"name\":\"jb\"}', '::1', '2025-11-22 10:12:20', '2025-11-22 10:12:20'),
(39, 4, 'create', 'sub_event_types', 1, '{\"name\":\"nn\",\"event_type_id\":1}', '::1', '2025-11-22 10:12:28', '2025-11-22 10:12:28'),
(40, 4, 'create', 'actions', 1, '{\"name\":\"trrrr\"}', '::1', '2025-11-22 10:12:36', '2025-11-22 10:12:36'),
(41, 4, 'logout', NULL, NULL, '[]', '::1', '2025-11-22 10:12:39', '2025-11-22 10:12:39'),
(42, 5, 'login', NULL, NULL, '{\"username\":\"user\"}', '::1', '2025-11-22 10:12:43', '2025-11-22 10:12:43'),
(43, 5, 'create', 'monitored_information', 1, '{\"location\":\" h jbj\"}', '::1', '2025-11-22 10:13:10', '2025-11-22 10:13:10'),
(44, 5, 'create', 'monitored_information', 2, '{\"location\":\"vdvd\"}', '::1', '2025-11-22 10:17:33', '2025-11-22 10:17:33'),
(45, 5, 'update', 'monitored_information', 2, '[]', '::1', '2025-11-22 10:25:52', '2025-11-22 10:25:52'),
(46, 5, 'delete', 'monitored_information', 1, '[]', '::1', '2025-11-22 10:26:05', '2025-11-22 10:26:05'),
(47, 5, 'logout', NULL, NULL, '[]', '::1', '2025-11-22 10:31:29', '2025-11-22 10:31:29'),
(48, 4, 'login', NULL, NULL, '{\"username\":\"rootadmin\"}', '::1', '2025-11-22 10:31:33', '2025-11-22 10:31:33'),
(49, 4, 'create', 'regions', 2, '{\"name\":\"amhara\"}', '::1', '2025-11-22 10:32:01', '2025-11-22 10:32:01'),
(50, 4, 'create', 'actions', 2, '{\"name\":\"rape\"}', '::1', '2025-11-22 10:32:21', '2025-11-22 10:32:21'),
(51, 4, 'create', 'event_types', 2, '{\"name\":\"rally\"}', '::1', '2025-11-22 10:32:35', '2025-11-22 10:32:35'),
(52, 4, 'create', 'sub_event_types', 2, '{\"name\":\"students rally\",\"event_type_id\":2}', '::1', '2025-11-22 10:32:48', '2025-11-22 10:32:48'),
(53, 4, 'create', 'sub_event_types', 3, '{\"name\":\"dre beats\",\"event_type_id\":1}', '::1', '2025-11-22 10:33:12', '2025-11-22 10:33:12'),
(54, 4, 'create', 'users', 6, '{\"monitor_id_code\":\"CCSPM10001\",\"username\":\"user2\"}', '::1', '2025-11-22 10:34:02', '2025-11-22 10:34:02'),
(55, 4, 'logout', NULL, NULL, '[]', '::1', '2025-11-22 10:34:05', '2025-11-22 10:34:05'),
(56, 6, 'login', NULL, NULL, '{\"username\":\"user2\"}', '::1', '2025-11-22 10:34:13', '2025-11-22 10:34:13'),
(57, 6, 'create', 'monitored_information', 3, '{\"location\":\"dw\"}', '::1', '2025-11-22 10:34:57', '2025-11-22 10:34:57'),
(58, 6, 'create', 'monitored_information', 4, '{\"location\":\"hvshvch\"}', '::1', '2025-11-22 10:35:29', '2025-11-22 10:35:29'),
(59, 6, 'logout', NULL, NULL, '[]', '::1', '2025-11-22 10:35:31', '2025-11-22 10:35:31'),
(60, 4, 'login', NULL, NULL, '{\"username\":\"rootadmin\"}', '::1', '2025-11-22 10:35:35', '2025-11-22 10:35:35'),
(61, 4, 'logout', NULL, NULL, '[]', '::1', '2025-11-22 10:37:17', '2025-11-22 10:37:17'),
(62, 5, 'login', NULL, NULL, '{\"username\":\"user\"}', '::1', '2025-11-22 10:37:21', '2025-11-22 10:37:21'),
(63, 5, 'create', 'monitored_information', 5, '{\"location\":\"gyg\"}', '::1', '2025-11-22 10:46:33', '2025-11-22 10:46:33'),
(64, 5, 'create', 'monitored_information', 6, '{\"location\":\"jjbj\"}', '::1', '2025-11-22 11:15:41', '2025-11-22 11:15:41'),
(65, 5, 'logout', NULL, NULL, '[]', '::1', '2025-11-22 11:24:52', '2025-11-22 11:24:52'),
(66, 4, 'login', NULL, NULL, '{\"username\":\"rootadmin\"}', '::1', '2025-11-22 11:24:55', '2025-11-22 11:24:55'),
(67, 4, 'logout', NULL, NULL, '[]', '::1', '2025-11-22 11:46:25', '2025-11-22 11:46:25'),
(68, 5, 'login', NULL, NULL, '{\"username\":\"user\"}', '::1', '2025-11-22 11:46:28', '2025-11-22 11:46:28'),
(69, 5, 'update', 'monitored_information', 6, '[]', '::1', '2025-11-22 11:47:03', '2025-11-22 11:47:03'),
(70, 5, 'logout', NULL, NULL, '[]', '::1', '2025-11-22 12:01:37', '2025-11-22 12:01:37'),
(71, 4, 'login', NULL, NULL, '{\"username\":\"rootadmin\"}', '::1', '2025-11-22 12:01:41', '2025-11-22 12:01:41'),
(72, 4, 'create', 'sub_event_types', 4, '{\"name\":\"city type rally\",\"event_type_id\":2}', '::1', '2025-11-22 12:02:04', '2025-11-22 12:02:04'),
(73, 4, 'logout', NULL, NULL, '[]', '::1', '2025-11-22 12:02:40', '2025-11-22 12:02:40'),
(74, 5, 'login', NULL, NULL, '{\"username\":\"user\"}', '::1', '2025-11-22 12:02:45', '2025-11-22 12:02:45'),
(75, 5, 'update', 'monitored_information', 2, '[]', '::1', '2025-11-22 12:03:05', '2025-11-22 12:03:05'),
(76, 5, 'update', 'monitored_information', 6, '[]', '::1', '2025-11-22 12:03:28', '2025-11-22 12:03:28'),
(77, 5, 'update', 'monitored_information', 2, '[]', '::1', '2025-11-22 12:03:55', '2025-11-22 12:03:55'),
(78, 5, 'logout', NULL, NULL, '[]', '::1', '2025-11-22 12:04:09', '2025-11-22 12:04:09'),
(79, 4, 'login', NULL, NULL, '{\"username\":\"rootadmin\"}', '::1', '2025-11-22 12:04:13', '2025-11-22 12:04:13'),
(80, 4, 'logout', NULL, NULL, '[]', '::1', '2025-11-22 12:12:25', '2025-11-22 12:12:25'),
(81, 5, 'login', NULL, NULL, '{\"username\":\"user\"}', '::1', '2025-11-22 12:12:29', '2025-11-22 12:12:29'),
(82, 5, 'update', 'monitored_information', 6, '[]', '::1', '2025-11-22 12:12:47', '2025-11-22 12:12:47'),
(83, 5, 'logout', NULL, NULL, '[]', '::1', '2025-11-22 12:22:03', '2025-11-22 12:22:03'),
(84, 5, 'login', NULL, NULL, '{\"username\":\"user\"}', '::1', '2025-11-22 12:24:47', '2025-11-22 12:24:47'),
(85, 5, 'logout', NULL, NULL, '[]', '::1', '2025-11-22 12:26:55', '2025-11-22 12:26:55'),
(86, 4, 'login', NULL, NULL, '{\"username\":\"rootadmin\"}', '::1', '2025-11-22 12:26:59', '2025-11-22 12:26:59'),
(87, 4, 'update', 'sub_event_types', 3, '{\"name\":\"dre beat\"}', '::1', '2025-11-22 12:32:39', '2025-11-22 12:32:39'),
(88, 4, 'export', 'monitored_information', NULL, '{\"format\":\"xlsx\",\"rows\":0}', '::1', '2025-11-22 13:07:49', '2025-11-22 13:07:49'),
(89, 4, 'export', 'monitored_information', NULL, '{\"format\":\"xlsx\",\"rows\":0}', '::1', '2025-11-22 13:16:26', '2025-11-22 13:16:26'),
(90, 4, 'export', 'monitored_information', NULL, '{\"format\":\"xlsx\",\"rows\":0}', '::1', '2025-11-22 13:19:47', '2025-11-22 13:19:47'),
(91, 4, 'export', 'monitored_information', NULL, '{\"format\":\"xlsx\",\"rows\":5}', '::1', '2025-11-22 13:22:31', '2025-11-22 13:22:31'),
(92, 4, 'export', 'monitored_information', NULL, '{\"format\":\"xlsx\",\"rows\":1}', '::1', '2025-11-22 13:38:42', '2025-11-22 13:38:42'),
(93, 4, 'logout', NULL, NULL, '[]', '::1', '2025-11-22 13:40:31', '2025-11-22 13:40:31'),
(94, 5, 'login', NULL, NULL, '{\"username\":\"user\"}', '::1', '2025-11-22 13:40:34', '2025-11-22 13:40:34'),
(95, 5, 'create', 'monitored_information', 7, '{\"location\":\"fvffe\"}', '::1', '2025-11-22 13:41:28', '2025-11-22 13:41:28'),
(96, 5, 'logout', NULL, NULL, '[]', '::1', '2025-11-22 13:43:28', '2025-11-22 13:43:28'),
(97, 4, 'login', NULL, NULL, '{\"username\":\"rootadmin\"}', '::1', '2025-11-22 13:43:31', '2025-11-22 13:43:31'),
(98, 4, 'logout', NULL, NULL, '[]', '::1', '2025-11-22 13:49:02', '2025-11-22 13:49:02'),
(99, 5, 'login', NULL, NULL, '{\"username\":\"user\"}', '::1', '2025-11-22 13:49:05', '2025-11-22 13:49:05'),
(100, 5, 'update', 'monitored_information', 7, '[]', '::1', '2025-11-22 14:01:55', '2025-11-22 14:01:55');

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

--
-- Dumping data for table `event_types`
--

INSERT INTO `event_types` (`id`, `name`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'jb', 1, '2025-11-22 10:12:20', '2025-11-22 10:12:20'),
(2, 'rally', 1, '2025-11-22 10:32:35', '2025-11-22 10:32:35');

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

--
-- Dumping data for table `monitored_information`
--

INSERT INTO `monitored_information` (`id`, `user_id`, `event_date`, `region_id`, `location`, `event_type_id`, `sub_event_type_id`, `action_id`, `source_url`, `notes`, `fatalities`, `rating`, `is_deleted`, `created_at`, `updated_at`) VALUES
(1, 5, '2025-11-20', 1, ' h jbj', 1, 1, 1, NULL, 'jbjbj\r\n', 0, NULL, 1, '2025-11-22 10:13:10', '2025-11-22 10:26:05'),
(2, 5, '2025-11-14', 1, 'jimma', 1, 1, 1, '', 'veve', 0, NULL, 0, '2025-11-22 10:17:33', '2025-11-22 12:03:55'),
(3, 6, '2025-11-13', 2, 'dw', 2, 2, 2, NULL, 'jdwudbubd', 0, NULL, 0, '2025-11-22 10:34:57', '2025-11-22 10:34:57'),
(4, 6, '2025-11-12', 1, 'hvshvch', 1, 1, 1, NULL, 'vhvh\r\n', 0, NULL, 0, '2025-11-22 10:35:29', '2025-11-22 10:35:29'),
(5, 5, '2025-10-29', 2, 'gyg', 1, 3, 2, NULL, 'guu', 0, NULL, 0, '2025-11-22 10:46:33', '2025-11-22 10:46:33'),
(6, 5, '2025-06-11', 2, 'jjbj', 1, 3, 2, 'jbjb.com', 'nenj', 54, 10, 0, '2025-11-22 11:15:41', '2025-11-22 12:12:47'),
(7, 5, '2025-11-08', 1, 'fvffe', 2, 4, 2, 'https://ehrc.org/%E1%89%A0%E1%8D%96%E1%88%88%E1%89%B2%E1%8A%AB-%E1%8D%93%E1%88%AD%E1%89%B2%E1%8B%8E%E1%89%BD-%E1%8B%A8%E1%88%98%E1%88%B0%E1%89%A5%E1%88%B0%E1%89%A5-%E1%88%98%E1%89%A5%E1%89%B5-%E1%88%8B%E1%8B%AD/', 'A group of seven political parties in Ethiopia\'s Benishangul Gumuz regional state have requested the National Election Board of Ethiopia to hold elections in areas where the 6th national election did not take place in June 2021. Elections were not conducted in four constituencies in the region, and out of the total 99 seats in the regional council, only 28 were contested and won by the ruling party. The opposition parties have requested the board to make necessary preparations for elections by June 7, arguing that the current ruling council\'s terms have expired and it is legally inappropriate to govern the populace without an elected government. The security situation in the region has improved considerably following peace agreements signed by the regional government with armed groups.', 3, 7, 0, '2025-11-22 13:41:28', '2025-11-22 14:01:54');

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

--
-- Dumping data for table `regions`
--

INSERT INTO `regions` (`id`, `name`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'oromia', 1, '2025-11-22 09:53:50', '2025-11-22 09:53:50'),
(2, 'amhara', 1, '2025-11-22 10:32:01', '2025-11-22 10:32:01');

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

--
-- Dumping data for table `sub_event_types`
--

INSERT INTO `sub_event_types` (`id`, `event_type_id`, `name`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'nn', 1, '2025-11-22 10:12:28', '2025-11-22 10:12:28'),
(2, 2, 'students rally', 1, '2025-11-22 10:32:48', '2025-11-22 10:32:48'),
(3, 1, 'dre beat', 1, '2025-11-22 10:33:12', '2025-11-22 12:32:39'),
(4, 2, 'city type rally', 1, '2025-11-22 12:02:04', '2025-11-22 12:02:04');

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
(3, 3, 'CCSPM03', 'superadmin', 'admin@example.com', '$2y$12$V/kjxRn3e1CI6JPl/W56I.VX95bpyYeGKScR/efQQGdIIyQcU9LeG', 'superadmin', 'approved', NULL, 1, '2025-11-21 11:52:06', '2025-11-21 11:52:06'),
(4, 9999, 'CCSPM9999', 'rootadmin', 'rootadmin@example.com', '$2y$12$eeRkaabBXQJLkhLvMJXAIeQNvlwTJy80GeFNhJ.ma.xVNAQVILWUu', 'superadmin', 'approved', NULL, 1, '2025-11-22 09:15:16', '2025-11-22 09:15:16'),
(5, 10000, 'CCSPM10000', 'user', 'yyfy@gmail.comw', '$2y$12$FFAIUKo625Lljv7L53mNpekwHhhmErvHr/SfLJ2w50.6fNZiCPG1e', 'user', 'approved', NULL, 1, '2025-11-22 09:57:06', '2025-11-22 09:57:06'),
(6, 10001, 'CCSPM10001', 'user2', 'firaol927@gmail.com', '$2y$12$/1IutoKQavzvbNj.2NbeIe/pRN9yYOFSbyYqslwRTxu32H.CWGdD.', 'user', 'approved', NULL, 1, '2025-11-22 10:34:02', '2025-11-22 10:34:02');

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

--
-- AUTO_INCREMENT for table `actions`
--
ALTER TABLE `actions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;

--
-- AUTO_INCREMENT for table `event_types`
--
ALTER TABLE `event_types`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `monitored_information`
--
ALTER TABLE `monitored_information`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `regions`
--
ALTER TABLE `regions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `sub_event_types`
--
ALTER TABLE `sub_event_types`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `monitored_information`
--
ALTER TABLE `monitored_information`
  ADD CONSTRAINT `monitored_information_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `monitored_information_ibfk_2` FOREIGN KEY (`region_id`) REFERENCES `regions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `monitored_information_ibfk_3` FOREIGN KEY (`event_type_id`) REFERENCES `event_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `monitored_information_ibfk_4` FOREIGN KEY (`sub_event_type_id`) REFERENCES `sub_event_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `monitored_information_ibfk_5` FOREIGN KEY (`action_id`) REFERENCES `actions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `sub_event_types`
--
ALTER TABLE `sub_event_types`
  ADD CONSTRAINT `sub_event_types_ibfk_1` FOREIGN KEY (`event_type_id`) REFERENCES `event_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
