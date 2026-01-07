-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jan 07, 2026 at 11:21 AM
-- Server version: 8.2.0
-- PHP Version: 8.1.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `wallet_service`
--

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
CREATE TABLE IF NOT EXISTS `cache` (
  `key` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
CREATE TABLE IF NOT EXISTS `cache_locks` (
  `key` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE IF NOT EXISTS `failed_jobs` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `idempotency_logs`
--

DROP TABLE IF EXISTS `idempotency_logs`;
CREATE TABLE IF NOT EXISTS `idempotency_logs` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `idempotency_key` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `request_hash` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `response_data` json NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idempotency_logs_idempotency_key_unique` (`idempotency_key`),
  KEY `idempotency_logs_idempotency_key_index` (`idempotency_key`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `idempotency_logs`
--

INSERT INTO `idempotency_logs` (`id`, `idempotency_key`, `request_hash`, `response_data`, `created_at`) VALUES
(1, 'deposit-1767781112', '9a4c9a3bb922315441c596934da0cff4297090994efb1d0cd2f8afa03c9bc7f7', '{\"type\": \"deposit\", \"amount\": 10000, \"wallet_id\": 1, \"new_balance\": 10000, \"transaction_id\": 1}', '2026-01-07 10:18:32'),
(2, 'transfer-1767781128', '73832ab71ac371bcb6d894085af0dae411d89d5e36ca6a8ca2446df03dada1b4', '{\"amount\": 5000, \"transfer_id\": 2, \"source_wallet_id\": 1, \"target_wallet_id\": 2, \"source_new_balance\": 5000, \"target_new_balance\": 5000, \"debit_transaction_id\": 2, \"credit_transaction_id\": 3}', '2026-01-07 10:18:48'),
(3, 'test-transfer-idempotency-456', 'a36528cb3f791b8001006cdf56aaf243427d403ea3ab1107b47fd2bbacae9d30', '{\"amount\": 2000, \"transfer_id\": 4, \"source_wallet_id\": 1, \"target_wallet_id\": 2, \"source_new_balance\": 3000, \"target_new_balance\": 7000, \"debit_transaction_id\": 4, \"credit_transaction_id\": 5}', '2026-01-07 10:24:56'),
(4, 'test-idempotency-key-123', '9a4c9a3bb922315441c596934da0cff4297090994efb1d0cd2f8afa03c9bc7f7', '{\"type\": \"deposit\", \"amount\": 10000, \"wallet_id\": 1, \"new_balance\": 13000, \"transaction_id\": 6}', '2026-01-07 10:25:59'),
(5, 'deposit-1767783899', 'd94c1a56ad86bd44661146893279e1d170b9fa480cdd8449f5e85fc034f3e1bd', '{\"type\": \"deposit\", \"amount\": 10000, \"wallet_id\": 3, \"new_balance\": 10000, \"transaction_id\": 7}', '2026-01-07 11:04:59'),
(6, 'deposit-1767783908', '5c2d7e67a95e2c399bc05cd6b37eb4e0d397509475eeb626d84fb9ae788fb260', '{\"type\": \"deposit\", \"amount\": 20000, \"wallet_id\": 4, \"new_balance\": 20000, \"transaction_id\": 8}', '2026-01-07 11:05:08');

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
CREATE TABLE IF NOT EXISTS `jobs` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `queue` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint UNSIGNED NOT NULL,
  `reserved_at` int UNSIGNED DEFAULT NULL,
  `available_at` int UNSIGNED NOT NULL,
  `created_at` int UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
CREATE TABLE IF NOT EXISTS `job_batches` (
  `id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
CREATE TABLE IF NOT EXISTS `migrations` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `migration` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2026_01_07_092647_create_wallets_table', 1),
(5, '2026_01_07_092813_create_transactions_table', 1),
(6, '2026_01_07_092904_create_idempotency_logs_table', 1);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
CREATE TABLE IF NOT EXISTS `sessions` (
  `id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('sSYfbxze5d81LfmrSbcSE5XVrNQkDI1uulqo3YoI', NULL, '127.0.0.1', 'PostmanRuntime/7.51.0', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiSWsxZ2NSYTdCdEptRkdyQkNsOUdkNEFMV2N2NGVwc0tnSElMNDhrMCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMCI7czo1OiJyb3V0ZSI7Tjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1767781645);

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

DROP TABLE IF EXISTS `transactions`;
CREATE TABLE IF NOT EXISTS `transactions` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `wallet_id` bigint UNSIGNED NOT NULL,
  `type` enum('deposit','withdrawal','transfer_debit','transfer_credit') COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` bigint UNSIGNED NOT NULL,
  `related_wallet_id` bigint UNSIGNED DEFAULT NULL,
  `idempotency_key` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `transactions_idempotency_key_unique` (`idempotency_key`),
  KEY `transactions_related_wallet_id_foreign` (`related_wallet_id`),
  KEY `transactions_wallet_id_index` (`wallet_id`),
  KEY `transactions_type_index` (`type`),
  KEY `transactions_created_at_index` (`created_at`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `wallet_id`, `type`, `amount`, `related_wallet_id`, `idempotency_key`, `metadata`, `created_at`) VALUES
(1, 1, 'deposit', 10000, NULL, 'deposit-1767781112', NULL, '2026-01-07 10:18:32'),
(2, 1, 'transfer_debit', 5000, 2, 'transfer-1767781128', '{\"transfer_to\": \"Nour Eddin\", \"transfer_to_wallet_id\": 2}', '2026-01-07 10:18:48'),
(3, 2, 'transfer_credit', 5000, 1, NULL, '{\"transfer_from\": \"Abdul\", \"transfer_from_wallet_id\": 1}', '2026-01-07 10:18:48'),
(4, 1, 'transfer_debit', 2000, 2, 'test-transfer-idempotency-456', '{\"transfer_to\": \"Nour Eddin\", \"transfer_to_wallet_id\": 2}', '2026-01-07 10:24:56'),
(5, 2, 'transfer_credit', 2000, 1, NULL, '{\"transfer_from\": \"Abdul\", \"transfer_from_wallet_id\": 1}', '2026-01-07 10:24:56'),
(6, 1, 'deposit', 10000, NULL, 'test-idempotency-key-123', NULL, '2026-01-07 10:25:59'),
(7, 3, 'deposit', 10000, NULL, 'deposit-1767783899', NULL, '2026-01-07 11:04:59'),
(8, 4, 'deposit', 20000, NULL, 'deposit-1767783908', NULL, '2026-01-07 11:05:08');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `wallets`
--

DROP TABLE IF EXISTS `wallets`;
CREATE TABLE IF NOT EXISTS `wallets` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `owner_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `currency` char(3) COLLATE utf8mb4_unicode_ci NOT NULL,
  `balance` bigint UNSIGNED NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wallets_owner_name_index` (`owner_name`),
  KEY `wallets_currency_index` (`currency`),
  KEY `wallets_owner_name_currency_index` (`owner_name`,`currency`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `wallets`
--

INSERT INTO `wallets` (`id`, `owner_name`, `currency`, `balance`, `created_at`, `updated_at`) VALUES
(1, 'Abdul', 'USD', 13000, '2026-01-07 02:17:15', '2026-01-07 02:25:59'),
(2, 'Nour Eddin', 'USD', 7000, '2026-01-07 02:18:11', '2026-01-07 02:24:56'),
(3, 'Omar', 'USD', 10000, '2026-01-07 02:34:34', '2026-01-07 03:04:59'),
(4, 'Ahmad', 'USD', 20000, '2026-01-07 02:34:41', '2026-01-07 03:05:08');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
