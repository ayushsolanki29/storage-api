-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 21, 2025 at 11:23 AM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `storage_api`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_settings`
--

CREATE TABLE `admin_settings` (
  `id` int NOT NULL,
  `setting_key` varchar(255) NOT NULL,
  `setting_value` text,
  `setting_type` enum('string','integer','boolean','json') DEFAULT 'string',
  `description` text,
  `updated_by` int DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `admin_settings`
--

INSERT INTO `admin_settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `description`, `updated_by`, `updated_at`) VALUES
(1, 'system_name', 'App', 'string', 'The name of the system', 4, '2025-10-21 10:27:55'),
(2, 'max_file_size_default', '5242880', 'integer', 'Default maximum file size in bytes', NULL, '2025-10-21 08:17:57'),
(3, 'allowed_file_types', '[\"image/jpeg\", \"image/png\", \"image/gif\", \"image/webp\", \"application/pdf\"]', 'json', 'Allowed file types for upload', NULL, '2025-10-21 08:17:57'),
(4, 'user_registration', 'enabled', 'string', 'Whether user registration is enabled', 4, '2025-10-21 10:27:55'),
(5, 'default_user_plan', '1', 'integer', 'Default plan for new users', 4, '2025-10-21 10:27:55'),
(6, 'session_timeout', '30', 'integer', 'User session timeout in minutes', 4, '2025-10-21 10:27:55'),
(7, 'min_password_length', '8', 'integer', 'Minimum password length requirement', 4, '2025-10-21 10:27:55'),
(8, 'api_rate_limit', '60', 'integer', 'API rate limit per minute per key', 4, '2025-10-21 10:27:55'),
(9, 'max_login_attempts', '5', 'integer', 'Maximum failed login attempts before lockout', 4, '2025-10-21 10:27:55'),
(10, 'enable_compression', '1', 'boolean', 'Enable automatic image compression', NULL, '2025-10-21 10:27:24'),
(11, 'enable_thumbnails', '1', 'boolean', 'Enable thumbnail generation', NULL, '2025-10-21 10:27:24'),
(12, 'keep_original', '1', 'boolean', 'Keep original files after processing', NULL, '2025-10-21 10:27:24'),
(13, 'api_base_url', 'http://localhost/storage_app/api/', 'string', 'Base URL for API endpoints', NULL, '2025-10-21 10:27:24'),
(14, 'api_version', 'v1', 'string', 'Current API version', NULL, '2025-10-21 10:27:24'),
(15, 'cors_enabled', '1', 'boolean', 'Enable CORS for API', NULL, '2025-10-21 10:27:24'),
(16, 'allowed_origins', '', 'string', 'Allowed CORS origins (one per line)', NULL, '2025-10-21 10:27:24'),
(17, 'smtp_host', 'smtp.gmail.com', 'string', 'SMTP server hostname', NULL, '2025-10-21 10:27:24'),
(18, 'smtp_port', '587', 'integer', 'SMTP server port', NULL, '2025-10-21 10:27:24'),
(19, 'smtp_username', '', 'string', 'SMTP username', NULL, '2025-10-21 10:27:24'),
(20, 'smtp_password', '', 'string', 'SMTP password', NULL, '2025-10-21 10:27:24'),
(21, 'from_email', 'noreply@example.com', 'string', 'Default from email address', NULL, '2025-10-21 10:27:24'),
(22, 'from_name', 'Mini Cloudinary', 'string', 'Default from name', NULL, '2025-10-21 10:27:24');

-- --------------------------------------------------------

--
-- Table structure for table `api_keys`
--

CREATE TABLE `api_keys` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `api_key` varchar(64) NOT NULL,
  `label` varchar(100) DEFAULT 'Primary Key',
  `is_revoked` tinyint(1) DEFAULT '0',
  `last_used` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `plan_id` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('paid','pending','failed') DEFAULT 'pending',
  `gateway_txn` varchar(255) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `plans`
--

CREATE TABLE `plans` (
  `id` int NOT NULL,
  `name` varchar(100) NOT NULL,
  `price` decimal(10,2) DEFAULT '0.00',
  `storage_limit` bigint DEFAULT '1073741824',
  `monthly_request_limit` int DEFAULT '1000',
  `bandwidth_limit` bigint DEFAULT '5368709120',
  `max_file_size` int DEFAULT '5242880',
  `allow_compression` tinyint(1) DEFAULT '1',
  `allow_thumbnails` tinyint(1) DEFAULT '1',
  `thumbnail_sizes` json DEFAULT NULL,
  `features` json DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `plans`
--

INSERT INTO `plans` (`id`, `name`, `price`, `storage_limit`, `monthly_request_limit`, `bandwidth_limit`, `max_file_size`, `allow_compression`, `allow_thumbnails`, `thumbnail_sizes`, `features`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Free', '0.00', 1073741824, 1000, 5368709120, 5242880, 1, 1, NULL, '[\"Basic Compression\", \"Thumbnail Generation\", \"API Access\"]', 1, '2025-10-20 18:56:54', '2025-10-21 09:18:15'),
(2, 'Pro', '9.99', 5368709120, 10000, 21474836480, 20971520, 1, 1, NULL, '[\"Advanced Compression\", \"Multiple Thumbnail Sizes\", \"Priority Support\", \"No Watermark\"]', 1, '2025-10-20 18:56:54', '2025-10-21 09:18:21'),
(3, 'Business', '29.99', 21474836480, 50000, 107374182400, 52428800, 1, 1, NULL, '[\"Maximum Compression\", \"Custom Thumbnails\", \"Priority Processing\", \"Advanced Analytics\"]', 1, '2025-10-20 18:56:54', '2025-10-20 18:56:54');

-- --------------------------------------------------------

--
-- Table structure for table `remember_tokens`
--

CREATE TABLE `remember_tokens` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `uploads`
--

CREATE TABLE `uploads` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_url` varchar(500) NOT NULL,
  `compressed_path` varchar(500) DEFAULT NULL,
  `compressed_url` varchar(500) DEFAULT NULL,
  `thumbnail_path` varchar(500) DEFAULT NULL,
  `thumbnail_url` varchar(500) DEFAULT NULL,
  `size` bigint NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `width` int DEFAULT NULL,
  `height` int DEFAULT NULL,
  `is_compressed` varchar(1) DEFAULT '0',
  `compression_ratio` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `uploads`
--

INSERT INTO `uploads` (`id`, `user_id`, `file_name`, `file_path`, `file_url`, `compressed_path`, `compressed_url`, `thumbnail_path`, `thumbnail_url`, `size`, `mime_type`, `width`, `height`, `is_compressed`, `compression_ratio`, `created_at`) VALUES
(2, 2, 'Gtu Mca Nft Blockchain Paper.docx', 'F:/Software/laragon/www/storage_app/uploads/2/2025/10/Gtu Mca Nft Blockchain Paper_68f6984def9fa.docx', 'https://localhost/storage_app/uploads/2/2025/10/Gtu Mca Nft Blockchain Paper_68f6984def9fa.docx', NULL, NULL, NULL, NULL, 12352, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', NULL, NULL, '0', NULL, '2025-10-20 20:15:09'),
(3, 2, '3.jpg', 'F:/Software/laragon/www/storage_app/uploads/2/2025/10/3_68f698c939b53.jpg', 'https://localhost/storage_app/uploads/2/2025/10/3_68f698c939b53.jpg', 'F:/Software/laragon/www/storage_app/uploads/2/2025/10/compressed_3_68f698c939b53.jpg', 'https://localhost/storage_app/uploads/2/2025/10/compressed_3_68f698c939b53.jpg', 'F:/Software/laragon/www/storage_app/uploads/2/2025/10/thumb_3_68f698c939b53.jpg', 'https://localhost/storage_app/uploads/2/2025/10/thumb_3_68f698c939b53.jpg', 554105, 'image/jpeg', 1120, 1120, '1', '81.32', '2025-10-20 20:17:13'),
(4, 3, 'Blockchain Technology and NFT-based Digital Ownership System.pdf', 'F:/Software/laragon/www/storage_app/uploads/3/2025/10/Blockchain Technology and NFT-based Digital Ownership System_68f73e9223582.pdf', 'https://localhost/storage_app/uploads/3/2025/10/Blockchain Technology and NFT-based Digital Ownership System_68f73e9223582.pdf', NULL, NULL, NULL, NULL, 392896, 'application/pdf', NULL, NULL, '0', NULL, '2025-10-21 08:04:34'),
(5, 3, '3.jpg', 'F:/Software/laragon/www/storage_app/uploads/3/2025/10/3_68f73ea256c39.jpg', 'https://localhost/storage_app/uploads/3/2025/10/3_68f73ea256c39.jpg', 'F:/Software/laragon/www/storage_app/uploads/3/2025/10/compressed_3_68f73ea256c39.jpg', 'https://localhost/storage_app/uploads/3/2025/10/compressed_3_68f73ea256c39.jpg', 'F:/Software/laragon/www/storage_app/uploads/3/2025/10/thumb_3_68f73ea256c39.jpg', 'https://localhost/storage_app/uploads/3/2025/10/thumb_3_68f73ea256c39.jpg', 554105, 'image/jpeg', 1120, 1120, '1', '81.32', '2025-10-21 08:04:50'),
(6, 3, '3.jpg', 'F:/Software/laragon/www/storage_app/uploads/3/2025/10/3_68f73f5467f18.jpg', 'http://localhost/storage_app/uploads/3/2025/10/3_68f73f5467f18.jpg', 'F:/Software/laragon/www/storage_app/uploads/3/2025/10/compressed_3_68f73f5467f18.jpg', 'http://localhost/storage_app/uploads/3/2025/10/compressed_3_68f73f5467f18.jpg', 'F:/Software/laragon/www/storage_app/uploads/3/2025/10/thumb_3_68f73f5467f18.jpg', 'http://localhost/storage_app/uploads/3/2025/10/thumb_3_68f73f5467f18.jpg', 554105, 'image/jpeg', 1120, 1120, '1', '81.32', '2025-10-21 08:07:48'),
(7, 3, '1 v1.jpg', 'F:/Software/laragon/www/storage_app/uploads/3/2025/10/1 v1_68f740067f0d7.jpg', 'http://localhost/storage_app/uploads/3/2025/10/1 v1_68f740067f0d7.jpg', 'F:/Software/laragon/www/storage_app/uploads/3/2025/10/compressed_1 v1_68f740067f0d7.jpg', 'http://localhost/storage_app/uploads/3/2025/10/compressed_1 v1_68f740067f0d7.jpg', 'F:/Software/laragon/www/storage_app/uploads/3/2025/10/thumb_1 v1_68f740067f0d7.jpg', 'http://localhost/storage_app/uploads/3/2025/10/thumb_1 v1_68f740067f0d7.jpg', 941271, 'image/jpeg', 2406, 1171, '1', '74.84', '2025-10-21 08:10:46'),
(8, 3, '2.pdf', 'F:/Software/laragon/www/storage_app/uploads/3/2025/10/2_68f740b68e42c.pdf', 'http://localhost/storage_app/uploads/3/2025/10/2_68f740b68e42c.pdf', NULL, NULL, NULL, NULL, 2870187, 'application/pdf', NULL, NULL, '0', NULL, '2025-10-21 08:13:42'),
(9, 3, '2.pdf', 'F:/Software/laragon/www/storage_app/uploads/3/2025/10/2_68f740bba3d73.pdf', 'http://localhost/storage_app/uploads/3/2025/10/2_68f740bba3d73.pdf', NULL, NULL, NULL, NULL, 2870187, 'application/pdf', NULL, NULL, '0', NULL, '2025-10-21 08:13:47'),
(10, 3, '1 v1.jpg', 'F:/Software/laragon/www/storage_app/uploads/3/2025/10/1 v1_68f742e95285f.jpg', 'http://localhost/storage_app/uploads/3/2025/10/1 v1_68f742e95285f.jpg', 'F:/Software/laragon/www/storage_app/uploads/3/2025/10/compressed_1 v1_68f742e95285f.jpg', 'http://localhost/storage_app/uploads/3/2025/10/compressed_1 v1_68f742e95285f.jpg', 'F:/Software/laragon/www/storage_app/uploads/3/2025/10/thumb_1 v1_68f742e95285f.jpg', 'http://localhost/storage_app/uploads/3/2025/10/thumb_1 v1_68f742e95285f.jpg', 941271, 'image/jpeg', 2406, 1171, '1', '74.84', '2025-10-21 08:23:05'),
(11, 3, '2.jpg', 'F:/Software/laragon/www/storage_app/uploads/3/2025/10/2_68f742ed6aa58.jpg', 'http://localhost/storage_app/uploads/3/2025/10/2_68f742ed6aa58.jpg', 'F:/Software/laragon/www/storage_app/uploads/3/2025/10/compressed_2_68f742ed6aa58.jpg', 'http://localhost/storage_app/uploads/3/2025/10/compressed_2_68f742ed6aa58.jpg', 'F:/Software/laragon/www/storage_app/uploads/3/2025/10/thumb_2_68f742ed6aa58.jpg', 'http://localhost/storage_app/uploads/3/2025/10/thumb_2_68f742ed6aa58.jpg', 718641, 'image/jpeg', 1024, 1024, '1', '77.48', '2025-10-21 08:23:09'),
(12, 3, '2.jpg', 'F:/Software/laragon/www/storage_app/uploads/3/2025/10/2_68f742f2525fb.jpg', 'http://localhost/storage_app/uploads/3/2025/10/2_68f742f2525fb.jpg', 'F:/Software/laragon/www/storage_app/uploads/3/2025/10/compressed_2_68f742f2525fb.jpg', 'http://localhost/storage_app/uploads/3/2025/10/compressed_2_68f742f2525fb.jpg', 'F:/Software/laragon/www/storage_app/uploads/3/2025/10/thumb_2_68f742f2525fb.jpg', 'http://localhost/storage_app/uploads/3/2025/10/thumb_2_68f742f2525fb.jpg', 718641, 'image/jpeg', 1024, 1024, '1', '77.48', '2025-10-21 08:23:14'),
(13, 3, '2.pdf', 'F:/Software/laragon/www/storage_app/uploads/3/2025/10/2_68f74342b3b11.pdf', 'http://localhost/storage_app/uploads/3/2025/10/2_68f74342b3b11.pdf', NULL, NULL, NULL, NULL, 2870187, 'application/pdf', NULL, NULL, '0', NULL, '2025-10-21 08:24:34'),
(14, 3, 'પખાલીને ડામ.docx', 'F:/Software/laragon/www/storage_app/uploads/3/2025/10/પખાલીને ડામ_68f743531ad30.docx', 'http://localhost/storage_app/uploads/3/2025/10/પખાલીને ડામ_68f743531ad30.docx', NULL, NULL, NULL, NULL, 24714, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', NULL, NULL, '0', NULL, '2025-10-21 08:24:51'),
(15, 3, '3 (1).jpg', 'F:/Software/laragon/www/storage_app/uploads/3/2025/10/3 (1)_68f767611887c.jpg', 'http://localhost/storage_app/uploads/3/2025/10/3 (1)_68f767611887c.jpg', 'F:/Software/laragon/www/storage_app/uploads/3/2025/10/compressed_3 (1)_68f767611887c.jpg', 'http://localhost/storage_app/uploads/3/2025/10/compressed_3 (1)_68f767611887c.jpg', 'F:/Software/laragon/www/storage_app/uploads/3/2025/10/thumb_3 (1)_68f767611887c.jpg', 'http://localhost/storage_app/uploads/3/2025/10/thumb_3 (1)_68f767611887c.jpg', 26621725, 'image/jpeg', 14620, 15000, '1', '74.34', '2025-10-21 10:58:48');

-- --------------------------------------------------------

--
-- Table structure for table `usage_logs`
--

CREATE TABLE `usage_logs` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `upload_id` int DEFAULT NULL,
  `type` enum('upload','download','api_call','auth_fail','billing') NOT NULL,
  `bytes` bigint DEFAULT '0',
  `endpoint` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `usage_logs`
--

INSERT INTO `usage_logs` (`id`, `user_id`, `upload_id`, `type`, `bytes`, `endpoint`, `ip_address`, `user_agent`, `created_at`) VALUES
(2, 2, 2, 'upload', 12352, 'upload.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-20 20:15:09'),
(3, 2, 3, 'upload', 554105, 'upload.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-20 20:17:13'),
(4, 3, 4, 'upload', 392896, 'upload.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-21 08:04:34'),
(5, 3, 5, 'upload', 554105, 'upload.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-21 08:04:50'),
(6, 3, 6, 'upload', 554105, 'upload.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-21 08:07:48'),
(7, 3, 7, 'upload', 941271, 'upload.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-21 08:10:46'),
(8, 3, 8, 'upload', 2870187, 'upload.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-21 08:13:42'),
(10, 3, 10, 'upload', 941271, 'upload.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-21 08:23:05'),
(11, 3, 11, 'upload', 718641, 'upload.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-21 08:23:09'),
(12, 3, 12, 'upload', 718641, 'upload.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-21 08:23:14'),
(13, 3, 13, 'upload', 2870187, 'upload.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-21 08:24:34'),
(14, 3, 14, 'upload', 24714, 'upload.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-21 08:24:51'),
(15, 3, NULL, 'api_call', 0, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-21 08:29:37'),
(16, 4, NULL, 'api_call', 0, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-21 08:30:39'),
(17, 4, NULL, 'api_call', 0, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-21 08:37:49'),
(18, 4, NULL, 'api_call', 0, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-21 08:47:26'),
(19, 4, NULL, 'api_call', 0, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-21 08:54:15'),
(20, 4, NULL, 'api_call', 0, 'admin_login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-21 09:04:38'),
(21, 4, NULL, 'api_call', 0, 'admin_login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-21 09:04:38'),
(22, 4, 4, 'api_call', 0, 'create_plan', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-21 09:17:56'),
(25, 4, 2, 'api_call', 0, 'deactivate_plan', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-21 09:18:17'),
(26, 4, 2, 'api_call', 0, 'activate_plan', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-21 09:18:21'),
(27, 4, 5, 'api_call', 0, 'create_plan', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-21 09:23:33'),
(28, 4, 4, 'api_call', 0, 'reset_api_key', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-21 09:46:20'),
(29, 4, NULL, 'api_call', 0, 'Updated user #3 plan to #3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-21 10:42:17'),
(30, 4, 3, 'api_call', 0, 'update_user_status_suspended', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-21 10:42:52'),
(31, 4, 3, 'api_call', 0, 'update_user_status_active', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-21 10:42:55'),
(32, 4, 3, 'api_call', 0, 'update_user_status_suspended', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-21 10:44:30'),
(33, 4, 3, 'api_call', 0, 'update_user_status_active', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-21 10:44:32'),
(34, 4, 4, 'api_call', 0, 'delete_plan', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-21 10:45:09'),
(35, 4, 5, 'api_call', 0, 'delete_plan', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-21 10:45:12'),
(36, 3, NULL, 'api_call', 0, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-21 10:52:10'),
(37, 3, 15, 'upload', 26621725, 'upload.php', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-21 10:58:48'),
(38, 3, NULL, 'billing', 0, 'plan_change:3->2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-21 11:11:18'),
(39, 3, NULL, 'billing', 0, 'plan_change:2->2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-21 11:11:24'),
(40, 3, NULL, 'billing', 0, 'plan_change:2->3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-21 11:11:32');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `api_key` varchar(64) NOT NULL,
  `plan_id` int DEFAULT '1',
  `used_space` bigint DEFAULT '0',
  `monthly_requests` int DEFAULT '0',
  `monthly_bandwidth` bigint DEFAULT '0',
  `status` enum('active','suspended','pending') DEFAULT 'active',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `role` enum('user','admin') DEFAULT 'user',
  `admin_permissions` json DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `api_key`, `plan_id`, `used_space`, `monthly_requests`, `monthly_bandwidth`, `status`, `last_login`, `created_at`, `updated_at`, `role`, `admin_permissions`) VALUES
(2, 'Web Mobile Contacts Icons', 'sododik8274@touchend.com', '$2y$10$7A7GSFp5C3Y5hVePOfqpCuPhJgKGKeSBuU1ORPenwYnFS9shKqJw.', 'ece4fa489f6e4d4e0fb7a154611ebb84b769f8661b42ce081db0a979f3b8f0f9', 1, 566457, 0, 566457, 'active', '2025-10-20 19:58:05', '2025-10-20 19:58:05', '2025-10-20 20:17:13', 'user', NULL),
(3, 'ayushsolanki.exe', 'cikan72556@memeazon.com', '$2y$10$47NUaVrwcy5u50jhVfHyMeKpacl5MmLRoBp/nRB8u7606iEh28bga', 'd173af00b6edd5517e0c6e102ef4d0d3806053bd4d46c70cad5b726c60affaa5', 3, 40077930, 2, 40077930, 'active', '2025-10-21 10:52:10', '2025-10-21 08:04:15', '2025-10-21 11:11:32', 'user', NULL),
(4, 'Administrator', 'admin@mini-cloudinary.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'e26cf1736897eb94d8364628dfd5f2cfef60f30652e908b3514e67792266d8a9', 1, 0, 17, 0, 'active', '2025-10-21 09:04:38', '2025-10-21 08:18:40', '2025-10-21 10:45:12', 'admin', '{\"can_view_logs\": true, \"can_manage_plans\": true, \"can_manage_users\": true, \"can_suspend_users\": true, \"can_reset_api_keys\": true, \"can_view_analytics\": true, \"can_manage_settings\": true}');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_settings`
--
ALTER TABLE `admin_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `api_keys`
--
ALTER TABLE `api_keys`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `api_key` (`api_key`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `plan_id` (`plan_id`);

--
-- Indexes for table `plans`
--
ALTER TABLE `plans`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Indexes for table `uploads`
--
ALTER TABLE `uploads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `usage_logs`
--
ALTER TABLE `usage_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `upload_id` (`upload_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `api_key` (`api_key`),
  ADD KEY `plan_id` (`plan_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_settings`
--
ALTER TABLE `admin_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `api_keys`
--
ALTER TABLE `api_keys`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `plans`
--
ALTER TABLE `plans`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `uploads`
--
ALTER TABLE `uploads`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `usage_logs`
--
ALTER TABLE `usage_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_settings`
--
ALTER TABLE `admin_settings`
  ADD CONSTRAINT `admin_settings_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `api_keys`
--
ALTER TABLE `api_keys`
  ADD CONSTRAINT `api_keys_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`);

--
-- Constraints for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD CONSTRAINT `remember_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `uploads`
--
ALTER TABLE `uploads`
  ADD CONSTRAINT `uploads_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `usage_logs`
--
ALTER TABLE `usage_logs`
  ADD CONSTRAINT `usage_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `usage_logs_ibfk_2` FOREIGN KEY (`upload_id`) REFERENCES `uploads` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
