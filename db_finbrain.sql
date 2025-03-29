-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Mar 29, 2025 at 11:06 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_finbrain`
--

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL COMMENT 'Category name, must be unique',
  `is_expense` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Indicates if the category is for expenses',
  `image` varchar(255) DEFAULT NULL COMMENT 'Optional image path for the category',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `is_expense`, `image`, `created_at`, `updated_at`) VALUES
(1, 'March Salary', 0, 'images-categories/01JQBVYW474T30ZSV1RQW390F8.png', '2025-03-27 05:59:53', '2025-03-27 06:16:42'),
(2, 'Vehicle', 1, 'images-categories/01JQBYF4AAJSMJYHYF4TA1D005.png', '2025-03-27 07:00:32', '2025-03-27 07:00:32');

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2025_03_27_102906_create_categories_table', 1),
(5, '2025_03_27_140350_create_transactions_table', 2),
(7, '2025_03_29_031819_create_permission_tables', 3);

-- --------------------------------------------------------

--
-- Table structure for table `model_has_permissions`
--

CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `model_has_roles`
--

CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `model_has_roles`
--

INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES
(1, 'App\\Models\\User', 2),
(3, 'App\\Models\\User', 1);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'view_category', 'web', '2025-03-28 20:23:37', '2025-03-28 20:23:37'),
(2, 'view_any_category', 'web', '2025-03-28 20:23:37', '2025-03-28 20:23:37'),
(3, 'create_category', 'web', '2025-03-28 20:23:37', '2025-03-28 20:23:37'),
(4, 'update_category', 'web', '2025-03-28 20:23:37', '2025-03-28 20:23:37'),
(5, 'restore_category', 'web', '2025-03-28 20:23:37', '2025-03-28 20:23:37'),
(6, 'restore_any_category', 'web', '2025-03-28 20:23:37', '2025-03-28 20:23:37'),
(7, 'replicate_category', 'web', '2025-03-28 20:23:37', '2025-03-28 20:23:37'),
(8, 'reorder_category', 'web', '2025-03-28 20:23:37', '2025-03-28 20:23:37'),
(9, 'delete_category', 'web', '2025-03-28 20:23:37', '2025-03-28 20:23:37'),
(10, 'delete_any_category', 'web', '2025-03-28 20:23:37', '2025-03-28 20:23:37'),
(11, 'force_delete_category', 'web', '2025-03-28 20:23:37', '2025-03-28 20:23:37'),
(12, 'force_delete_any_category', 'web', '2025-03-28 20:23:37', '2025-03-28 20:23:37'),
(13, 'view_transaction', 'web', '2025-03-28 20:23:37', '2025-03-28 20:23:37'),
(14, 'view_any_transaction', 'web', '2025-03-28 20:23:37', '2025-03-28 20:23:37'),
(15, 'create_transaction', 'web', '2025-03-28 20:23:37', '2025-03-28 20:23:37'),
(16, 'update_transaction', 'web', '2025-03-28 20:23:37', '2025-03-28 20:23:37'),
(17, 'restore_transaction', 'web', '2025-03-28 20:23:37', '2025-03-28 20:23:37'),
(18, 'restore_any_transaction', 'web', '2025-03-28 20:23:37', '2025-03-28 20:23:37'),
(19, 'replicate_transaction', 'web', '2025-03-28 20:23:37', '2025-03-28 20:23:37'),
(20, 'reorder_transaction', 'web', '2025-03-28 20:23:37', '2025-03-28 20:23:37'),
(21, 'delete_transaction', 'web', '2025-03-28 20:23:37', '2025-03-28 20:23:37'),
(22, 'delete_any_transaction', 'web', '2025-03-28 20:23:37', '2025-03-28 20:23:37'),
(23, 'force_delete_transaction', 'web', '2025-03-28 20:23:37', '2025-03-28 20:23:37'),
(24, 'force_delete_any_transaction', 'web', '2025-03-28 20:23:37', '2025-03-28 20:23:37'),
(25, 'page_FilterDate', 'web', '2025-03-28 20:23:37', '2025-03-28 20:23:37'),
(26, 'widget_AnalysisCard', 'web', '2025-03-28 20:23:37', '2025-03-28 20:23:37'),
(27, 'widget_WidgetIncomeChart', 'web', '2025-03-28 20:23:37', '2025-03-28 20:23:37'),
(28, 'widget_WidgetExpenseChart', 'web', '2025-03-28 20:23:37', '2025-03-28 20:23:37'),
(29, 'view_role', 'web', '2025-03-28 20:24:59', '2025-03-28 20:24:59'),
(30, 'view_any_role', 'web', '2025-03-28 20:24:59', '2025-03-28 20:24:59'),
(31, 'create_role', 'web', '2025-03-28 20:24:59', '2025-03-28 20:24:59'),
(32, 'update_role', 'web', '2025-03-28 20:24:59', '2025-03-28 20:24:59'),
(33, 'delete_role', 'web', '2025-03-28 20:24:59', '2025-03-28 20:24:59'),
(34, 'delete_any_role', 'web', '2025-03-28 20:24:59', '2025-03-28 20:24:59'),
(35, 'view_user', 'web', '2025-03-28 20:31:14', '2025-03-28 20:31:14'),
(36, 'view_any_user', 'web', '2025-03-28 20:31:14', '2025-03-28 20:31:14'),
(37, 'create_user', 'web', '2025-03-28 20:31:14', '2025-03-28 20:31:14'),
(38, 'update_user', 'web', '2025-03-28 20:31:14', '2025-03-28 20:31:14'),
(39, 'restore_user', 'web', '2025-03-28 20:31:14', '2025-03-28 20:31:14'),
(40, 'restore_any_user', 'web', '2025-03-28 20:31:14', '2025-03-28 20:31:14'),
(41, 'replicate_user', 'web', '2025-03-28 20:31:14', '2025-03-28 20:31:14'),
(42, 'reorder_user', 'web', '2025-03-28 20:31:14', '2025-03-28 20:31:14'),
(43, 'delete_user', 'web', '2025-03-28 20:31:14', '2025-03-28 20:31:14'),
(44, 'delete_any_user', 'web', '2025-03-28 20:31:14', '2025-03-28 20:31:14'),
(45, 'force_delete_user', 'web', '2025-03-28 20:31:14', '2025-03-28 20:31:14'),
(46, 'force_delete_any_user', 'web', '2025-03-28 20:31:14', '2025-03-28 20:31:14');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'User', 'web', '2025-03-28 20:23:37', '2025-03-28 20:23:37'),
(2, 'admin', 'web', '2025-03-28 20:24:59', '2025-03-28 20:24:59'),
(3, 'super_admin', 'web', '2025-03-28 20:31:12', '2025-03-28 20:31:12');

-- --------------------------------------------------------

--
-- Table structure for table `role_has_permissions`
--

CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_has_permissions`
--

INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES
(1, 1),
(1, 2),
(1, 3),
(2, 1),
(2, 2),
(2, 3),
(3, 1),
(3, 2),
(3, 3),
(4, 1),
(4, 2),
(4, 3),
(5, 1),
(5, 2),
(5, 3),
(6, 1),
(6, 2),
(6, 3),
(7, 1),
(7, 2),
(7, 3),
(8, 1),
(8, 2),
(8, 3),
(9, 1),
(9, 2),
(9, 3),
(10, 1),
(10, 2),
(10, 3),
(11, 1),
(11, 2),
(11, 3),
(12, 1),
(12, 2),
(12, 3),
(13, 1),
(13, 2),
(13, 3),
(14, 1),
(14, 2),
(14, 3),
(15, 1),
(15, 2),
(15, 3),
(16, 1),
(16, 2),
(16, 3),
(17, 1),
(17, 2),
(17, 3),
(18, 1),
(18, 2),
(18, 3),
(19, 1),
(19, 2),
(19, 3),
(20, 1),
(20, 2),
(20, 3),
(21, 1),
(21, 2),
(21, 3),
(22, 1),
(22, 2),
(22, 3),
(23, 1),
(23, 2),
(23, 3),
(24, 1),
(24, 2),
(24, 3),
(25, 1),
(25, 3),
(26, 1),
(27, 1),
(27, 3),
(28, 1),
(28, 3),
(29, 2),
(29, 3),
(30, 2),
(30, 3),
(31, 2),
(31, 3),
(32, 2),
(32, 3),
(33, 2),
(33, 3),
(34, 2),
(34, 3),
(35, 2),
(35, 3),
(36, 2),
(36, 3),
(37, 2),
(37, 3),
(38, 2),
(38, 3),
(39, 2),
(39, 3),
(40, 2),
(40, 3),
(41, 2),
(41, 3),
(42, 2),
(42, 3),
(43, 2),
(43, 3),
(44, 2),
(44, 3),
(45, 2),
(45, 3),
(46, 2),
(46, 3);

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('XzyUa7890HI25vss6Z1jW5Thee2KezkMUUkxoLoh', 2, '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:137.0) Gecko/20100101 Firefox/137.0', 'YTo4OntzOjM6InVybCI7YTowOnt9czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MzA6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9maW5icmFpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NjoiX3Rva2VuIjtzOjQwOiJ1S0ZqZUJKNDBQeXdpb2dqTWpTRTZvNWZMS2EzWThkQmNkc2c2QUhZIjtzOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToyO3M6MTc6InBhc3N3b3JkX2hhc2hfd2ViIjtzOjYwOiIkMnkkMTIkVnl4Si9BMWVpc1ViSGRSaEdBai9JZWUzNHJwNHVuNGlENkJRRUNWQ2pORXVYZzB1eEVaUTIiO3M6NDA6ImJhNDIzNTlhNDhiNjc3MWRiZjRkMTc0NmJhMzVkZjUxX2ZpbHRlcnMiO2E6Mjp7czo5OiJzdGFydERhdGUiO3M6MTA6IjIwMjUtMDMtMDEiO3M6NzoiZW5kRGF0ZSI7czowOiIiO31zOjg6ImZpbGFtZW50IjthOjA6e319', 1743242705);

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(50) NOT NULL COMMENT 'Unique transaction code',
  `name` varchar(255) NOT NULL COMMENT 'Transaction name',
  `category_id` bigint(20) UNSIGNED NOT NULL,
  `date_transaction` date NOT NULL COMMENT 'Transaction date',
  `payment_method` enum('cash','credit_card','bank_transfer','digital_wallet') NOT NULL COMMENT 'Payment method used for the transaction',
  `amount` bigint(20) UNSIGNED NOT NULL COMMENT 'Transaction amount, must be positive',
  `note` varchar(500) DEFAULT NULL COMMENT 'Optional transaction note',
  `image` varchar(255) DEFAULT NULL COMMENT 'Optional image path for the transaction',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT 'Soft delete column'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `code`, `name`, `category_id`, `date_transaction`, `payment_method`, `amount`, `note`, `image`, `created_at`, `updated_at`, `deleted_at`) VALUES
(3, 'TRX - QLXG7UWC', 'Salary', 1, '2025-03-28', 'credit_card', 9000000, '<p><strong>Monthly Salary + Overtime as Full Stack Developers&nbsp;</strong></p>', 'transaction-images/01JQCEFXMDF48NZAN8B2T70019.jpg', '2025-03-27 11:40:35', '2025-03-28 22:38:50', NULL),
(4, 'TRX - 7JTTQZSN', 'Shell Car Gasoline', 2, '2025-03-28', 'credit_card', 4300000, '<p><strong><em>Gasoline that will be filled every 1 month</em></strong></p>', 'transaction-images/01JQCEHM365DEPZE1W0BPCXV72.jpg', '2025-03-27 11:41:31', '2025-03-28 22:39:35', NULL),
(5, 'TRX - XAOBF5H7', 'Vehicle Gasoline', 2, '2025-03-29', 'bank_transfer', 5000000, NULL, NULL, '2025-03-28 20:15:01', '2025-03-28 22:39:19', NULL),
(6, 'TRX - OMBOUVAG', 'March Salary', 1, '2025-03-29', 'bank_transfer', 10000000, NULL, NULL, '2025-03-28 21:48:53', '2025-03-28 22:56:04', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Founder - Creative Trees', 'admin@finbrain.com', NULL, '$2y$12$2z8wx99C3yZ41btHV6j2wuTJ0mMPhmQeglAENY4mxV70p4F8blg7y', 'Rju1vrv3jxApEs4VufVenaRJ69LO9Fzr0k417YA9ClkHg61MYJwOSbXHNCZa', '2025-03-27 04:52:27', '2025-03-28 21:30:05');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `categories_name_unique` (`name`),
  ADD KEY `idx_categories_name` (`name`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  ADD KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  ADD KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`role_id`),
  ADD KEY `role_has_permissions_role_id_foreign` (`role_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transactions_code_unique` (`code`),
  ADD KEY `transactions_category_id_foreign` (`category_id`),
  ADD KEY `transactions_date_transaction_index` (`date_transaction`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
