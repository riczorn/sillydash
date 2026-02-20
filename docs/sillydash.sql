-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Feb 18, 2026 at 10:28 PM
-- Server version: 12.1.2-MariaDB
-- PHP Version: 8.5.3

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sillydash2`
--

-- --------------------------------------------------------

--
-- Table structure for table `silly_accounts`
--

CREATE TABLE `silly_accounts` (
  `id` int(11) UNSIGNED NOT NULL,
  `parent_id` int(11) UNSIGNED DEFAULT NULL,
  `domain` varchar(255) NOT NULL,
  `username` varchar(100) NOT NULL,
  `home_directory` varchar(255) NOT NULL,
  `db_names` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `silly_files`
--

CREATE TABLE `silly_files` (
  `id` int(11) UNSIGNED NOT NULL,
  `filename` varchar(255) NOT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'sizes',
  `file_date` datetime NOT NULL,
  `processed` tinyint(1) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `silly_records`
--

CREATE TABLE `silly_records` (
  `id` int(11) UNSIGNED NOT NULL,
  `file_id` int(10) UNSIGNED NOT NULL,
  `account_id` int(9) UNSIGNED DEFAULT NULL,
  `size_bytes` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `kind` varchar(50) NOT NULL DEFAULT 'disk',
  `path` varchar(512) NOT NULL,
  `time` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `silly_users`
--

CREATE TABLE `silly_users` (
  `id` int(11) UNSIGNED NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `allowed_accounts` text DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `silly_accounts`
--
ALTER TABLE `silly_accounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `domain` (`domain`),
  ADD KEY `username` (`username`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indexes for table `silly_files`
--
ALTER TABLE `silly_files`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `file_name` (`filename`),
  ADD UNIQUE KEY `idx_filename` (`filename`);

--
-- Indexes for table `silly_records`
--
ALTER TABLE `silly_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `file_id` (`file_id`),
  ADD KEY `mapping_id` (`account_id`);

--
-- Indexes for table `silly_users`
--
ALTER TABLE `silly_users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `silly_accounts`
--
ALTER TABLE `silly_accounts`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `silly_files`
--
ALTER TABLE `silly_files`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `silly_records`
--
ALTER TABLE `silly_records`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `silly_users`
--
ALTER TABLE `silly_users`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `silly_records`
--
ALTER TABLE `silly_records`
  ADD CONSTRAINT `silly_records_file_id_foreign` FOREIGN KEY (`file_id`) REFERENCES `silly_files` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `silly_records_mapping_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `silly_accounts` (`id`) ON DELETE CASCADE ON UPDATE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
