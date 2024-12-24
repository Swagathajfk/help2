-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 24, 2024 at 05:53 AM
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
-- Database: `freelancing_site`
--

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `freelancer_id` int(11) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `industry` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `profile_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clients`
--

INSERT INTO `clients` (`id`, `first_name`, `last_name`, `email`, `password`, `company_name`, `phone_number`, `industry`, `description`, `location`, `created_at`, `updated_at`, `profile_image`) VALUES
(2, '', '', 'sraj11121@gmail.com', '', NULL, NULL, NULL, NULL, NULL, '2024-12-21 17:57:25', '2024-12-21 17:57:25', NULL),
(3, 'S', 'Raj', 'sraj@gmail.com', '', 'Raj', '123456789', 'f', '', 'dsds', '2024-12-21 17:57:25', '2024-12-24 01:19:53', 'uploads/profile_images/3_1734970378.jpg'),
(8, 'Swagath', 'sdfdfdf', 'swagath@gmail.com', 'password@', 'awdfgdfg', '123456789', 'asfafafasdf', 'sdfgbnhvdcvsdvg', 'dfg', '2024-12-19 05:13:32', '2024-12-19 18:51:52', 'uploads/profile_images/8_1734603325.jpg'),
(9, 'Swara', 'Swagath', 'sraj1@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$SE52WlB6Uzk0aC9QbURDTA$tqFrJRo4o9g51khZacJWzlCWL4shq34tAjk8RzlHDFw', 'Swaaraj', '123456789', 'not ye but oine day', 'I hope I get it one day', 'not ours', '2024-12-19 19:09:33', '2024-12-20 05:56:28', 'uploads/profile_images/9_1734635388.jpg'),
(10, '', '', 'sraj2@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$RWdmSUh5ZEs2QlU3RmZBaQ$TCagqq4VFv8p4Cl4zghlUc5PfJdAAd+yBZynENNjX2A', NULL, NULL, NULL, NULL, NULL, '2024-12-20 08:08:00', '2024-12-20 17:58:05', 'uploads/profile_images/10_1734717485.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `client_job_applications`
--

CREATE TABLE `client_job_applications` (
  `id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `status` enum('pending','accepted','rejected') DEFAULT 'pending',
  `applied_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `client_notifications`
--

CREATE TABLE `client_notifications` (
  `id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `freelancers`
--

CREATE TABLE `freelancers` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `profession` varchar(100) DEFAULT NULL,
  `skills` text DEFAULT NULL,
  `hourly_rate` decimal(10,2) DEFAULT NULL,
  `experience_level` enum('beginner','intermediate','expert') DEFAULT NULL,
  `portfolio_url` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `profile_image` varchar(255) DEFAULT NULL,
  `total_jobs_completed` int(11) DEFAULT 0,
  `total_earnings` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` int(11) NOT NULL,
  `freelancer_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `job_type` varchar(50) NOT NULL,
  `reward` decimal(10,2) NOT NULL,
  `deadline` date NOT NULL,
  `files` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`files`)),
  `status` enum('open','assigned','active','draft','closed') DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `job_image` varchar(255) DEFAULT NULL,
  `tags` text DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `payment_status` enum('unpaid','processing','paid') DEFAULT 'unpaid',
  `payment_date` datetime DEFAULT NULL,
  `transaction_id` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jobs`
--

INSERT INTO `jobs` (`id`, `freelancer_id`, `client_id`, `title`, `description`, `job_type`, `reward`, `deadline`, `files`, `status`, `created_at`, `updated_at`, `job_image`, `tags`, `start_date`, `end_date`, `assigned_to`, `reviewed_at`, `payment_status`, `payment_date`, `transaction_id`) VALUES
(5, 1, 0, 'test', 'I am not sure.', 'freelance', 122.00, '0000-00-00', NULL, 'closed', '2024-12-02 13:11:52', '2024-12-24 00:45:02', 'uploads/job_images/674db218cdb2f.jpg', '[\"web-development\"]', NULL, NULL, NULL, NULL, 'unpaid', NULL, NULL),
(6, 1, 0, 'refdefdf', 'fdfdf', 'Contract', 100.00, '0000-00-00', '[]', 'open', '2024-12-02 13:52:08', '2024-12-20 19:33:47', 'uploads/job_images/674dbb88714bc.jpg', '[\"graphic-design\"]', '2004-01-01', '2025-04-05', NULL, NULL, 'unpaid', NULL, NULL),
(7, 1, 0, 'assssssss', 'ddddddhhh', 'On-site', 111.00, '0000-00-00', '[]', 'open', '2024-12-03 13:02:16', '2024-12-19 11:57:05', 'uploads/job_images/674f015842a7d.jpg', '[\"graphic-design\"]', '2024-12-20', '2025-01-02', NULL, NULL, 'unpaid', NULL, NULL),
(8, 1, 0, 'fafafa', 'fafaf', 'Remote', 2000.00, '0000-00-00', '[]', 'assigned', '2024-12-18 16:40:38', '2024-12-24 04:37:49', 'uploads/job_images/6762fb0630f18.jpg', '[\"digital-marketing\"]', '2024-02-02', '2025-02-05', 8, NULL, 'paid', '2024-12-24 10:07:49', 'TXN17350150688419'),
(9, 1, 0, 'rgfdgdgdg', 'sfdgbnhmjsdfgh', '0', 200.00, '0000-00-00', NULL, 'open', '2024-12-19 05:17:47', '2024-12-19 11:57:05', 'uploads/job_images/6763ac7b03251.jpg', '[\"digital-marketing\"]', '2024-01-02', '2025-04-01', NULL, NULL, 'unpaid', NULL, NULL),
(15, 1, 0, 'aswfa', 'afwaefe', '0', 5000.00, '0000-00-00', NULL, 'open', '2024-12-21 17:44:46', '2024-12-21 17:44:46', 'uploads/job_images/6766fe8e37204.jpg', '[\"consulting\"]', '2005-01-02', '2025-01-02', NULL, NULL, 'unpaid', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `job_applications`
--

CREATE TABLE `job_applications` (
  `application_id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `why_hire` text DEFAULT NULL,
  `contact_info` text DEFAULT NULL,
  `meeting_preference` varchar(255) DEFAULT NULL,
  `status` enum('pending','reviewing','accepted','rejected') DEFAULT 'pending',
  `applied_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `freelancer_response` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `job_applications`
--

INSERT INTO `job_applications` (`application_id`, `job_id`, `client_id`, `why_hire`, `contact_info`, `meeting_preference`, `status`, `applied_date`, `freelancer_response`) VALUES
(2, 9, 8, 'dsdsdsdsd', 'dsdsds', 'online', 'accepted', '2024-12-19 17:24:57', 'gsdgsgfggsgs'),
(3, 9, 3, 'fgdfgdfbg', 'sdfbdfgbdfbdfb', 'in-person', 'rejected', '2024-12-19 18:53:56', 'ewfrwefwefwefw'),
(4, 7, 3, 'dawdada', 'daddadad', 'in-person', 'accepted', '2024-12-19 18:57:36', 'sdsdsd'),
(5, 9, 9, 'dont no', 'phone number', 'in-person', 'accepted', '2024-12-19 19:11:31', 'gdfgdfgvsvfvfvdfvdfv'),
(6, 7, 9, 'tyrthyhyhyhyh', 'efwcwfr', 'online', 'rejected', '2024-12-20 06:01:44', 'rtgetgetgtg'),
(7, 6, 9, 'lkjmhngjhmnjukm', 'mumikm', 'phone', 'accepted', '2024-12-20 06:34:20', 'sfasdfsdfsf'),
(8, 5, 9, 'gdjhkfh', 'dfutuy', 'phone', 'accepted', '2024-12-20 06:45:55', 'sdfghjk'),
(9, 9, 10, 'wafaf', 'afafaf', 'phone', 'accepted', '2024-12-20 08:08:14', 'srgtetghetgrtgrtg'),
(14, 5, 3, 'efryh', 'trtrt', 'in-person', 'rejected', '2024-12-21 17:43:50', 'wert5yu'),
(15, 15, 3, 'zczzsdfsc', 'szvfzvzd', 'online', 'accepted', '2024-12-21 17:45:07', 'q25rty67u');

-- --------------------------------------------------------

--
-- Table structure for table `job_edits`
--

CREATE TABLE `job_edits` (
  `id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `updated_description` text NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_files`
--

CREATE TABLE `job_files` (
  `id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `job_id` int(11) DEFAULT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `receiver_id` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `job_id`, `sender_id`, `receiver_id`, `message`, `attachment_path`, `is_read`, `created_at`) VALUES
(1, 7, 1, 3, 'dgdg', NULL, 1, '2024-12-23 00:42:27'),
(2, 7, 3, 1, 'dgdg', NULL, 1, '2024-12-23 00:42:29'),
(3, 7, 1, 3, 'sfadf', NULL, 1, '2024-12-23 00:45:31'),
(4, 15, 1, 3, 'hey ia mstuck', NULL, 1, '2024-12-23 16:13:48'),
(5, 15, 3, 1, 'why', NULL, 1, '2024-12-23 16:14:03'),
(6, 15, 1, 3, 'idk', NULL, 1, '2024-12-23 16:14:19'),
(7, 15, 3, 1, 'gghdh', NULL, 1, '2024-12-23 18:21:24'),
(8, 15, 1, 3, 'sadfghjklkjhgfdsadfghjkl;kjhgfdsasdfghjk', NULL, 1, '2024-12-23 18:35:48'),
(9, 15, 3, 1, 'hey, why arnt you saying anything', NULL, 1, '2024-12-24 00:40:48');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_type` enum('client','freelancer') DEFAULT 'client'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `message`, `is_read`, `created_at`, `user_type`) VALUES
(1, 1, 'You have a new job application.', 1, '2024-10-24 06:41:21', 'client'),
(2, 1, 'New application received for job: rgfdgdgdg', 1, '2024-12-19 17:15:03', 'freelancer'),
(3, 1, 'New application received for job: rgfdgdgdg', 1, '2024-12-19 17:24:57', 'freelancer'),
(4, 8, 'Your application has been reviewing for job: rgfdgdgdg', 0, '2024-12-19 18:47:04', 'client'),
(5, 1, 'New application received for job: rgfdgdgdg', 1, '2024-12-19 18:53:56', 'freelancer'),
(6, 3, 'Your application has been rejected for job: rgfdgdgdg', 1, '2024-12-19 18:54:14', 'client'),
(7, 1, 'New application received for job: assssssss', 1, '2024-12-19 18:57:36', 'freelancer'),
(8, 1, 'New application received for job: rgfdgdgdg', 1, '2024-12-19 19:11:31', 'freelancer'),
(9, 3, 'Your application has been accepted for job: assssssss', 1, '2024-12-19 19:50:28', 'client'),
(10, 9, 'Your application has been accepted for job: rgfdgdgdg', 0, '2024-12-20 05:57:15', 'client'),
(11, 1, 'New application received for job: assssssss', 1, '2024-12-20 06:01:44', 'freelancer'),
(12, 9, 'Your application has been rejected for job: assssssss', 0, '2024-12-20 06:02:02', 'client'),
(13, 8, 'Your application has been accepted for job: rgfdgdgdg', 0, '2024-12-20 06:33:47', 'client'),
(14, 1, 'New application received for job: refdefdf', 1, '2024-12-20 06:34:20', 'freelancer'),
(15, 9, 'Your application has been accepted for job: refdefdf', 0, '2024-12-20 06:44:15', 'client'),
(16, 1, 'New application received for job: test', 1, '2024-12-20 06:45:55', 'freelancer'),
(17, 9, 'Your application has been accepted for job: test', 1, '2024-12-20 06:46:13', 'client'),
(18, 1, 'New application received for job: rgfdgdgdg', 1, '2024-12-20 08:08:14', 'freelancer'),
(19, 10, 'Your application has been reviewing for job: rgfdgdgdg', 0, '2024-12-20 08:08:49', 'client'),
(20, 10, 'Your application has been accepted for job: rgfdgdgdg', 0, '2024-12-20 08:09:19', 'client'),
(21, 1, 'New application received for job: assssssss', 1, '2024-12-20 09:00:23', 'freelancer'),
(22, 1, 'New application received for job: assssssss', 1, '2024-12-20 09:01:10', 'freelancer'),
(23, 1, 'New application received for job: assssssss', 1, '2024-12-20 09:02:07', 'freelancer'),
(24, 1, 'New application received for job: refdefdf', 0, '2024-12-21 17:42:48', 'freelancer'),
(25, 1, 'New application received for job: test', 0, '2024-12-21 17:43:51', 'freelancer'),
(26, 1, 'New application received for job: aswfa', 0, '2024-12-21 17:45:07', 'freelancer'),
(27, 3, 'Your application has been reviewing for job: aswfa', 1, '2024-12-21 18:08:07', 'client'),
(28, 3, 'Your application has been rejected for job: test', 0, '2024-12-21 18:08:16', 'client'),
(29, 3, 'Your application has been accepted for job: aswfa', 0, '2024-12-21 18:08:46', 'client'),
(30, 3, 'New message received for job: assssssss', 0, '2024-12-23 00:42:27', 'client'),
(31, 1, 'New message received for job: assssssss', 0, '2024-12-23 00:42:29', 'freelancer'),
(32, 3, 'New message received for job: assssssss', 0, '2024-12-23 00:45:31', 'client'),
(33, 3, 'New message received for job: aswfa', 1, '2024-12-23 16:13:48', 'client'),
(34, 1, 'New message received for job: aswfa', 0, '2024-12-23 16:14:03', 'freelancer'),
(35, 3, 'New message received for job: aswfa', 0, '2024-12-23 16:14:19', 'client'),
(36, 1, 'New message received for job: aswfa', 0, '2024-12-23 18:21:24', 'freelancer'),
(37, 3, 'New message received for job: aswfa', 1, '2024-12-23 18:35:48', 'client'),
(38, 1, 'New message received for job: aswfa', 0, '2024-12-24 00:40:48', 'freelancer'),
(39, 1, 'Payment received for job #8', 0, '2024-12-24 04:37:49', 'freelancer');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `freelancer_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','completed','failed') DEFAULT 'pending',
  `transaction_id` varchar(50) DEFAULT NULL,
  `payment_date` datetime DEFAULT current_timestamp(),
  `payment_method` varchar(50) DEFAULT NULL,
  `billing_address` text DEFAULT NULL,
  `card_last_four` varchar(4) DEFAULT NULL,
  `receipt_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `job_id`, `client_id`, `freelancer_id`, `amount`, `status`, `transaction_id`, `payment_date`, `payment_method`, `billing_address`, `card_last_four`, `receipt_url`) VALUES
(1, 8, 1, 1, 2000.00, 'completed', 'TXN17350150688419', '2024-12-24 10:07:48', 'card', 'qwerty', '5678', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `payment_status_logs`
--

CREATE TABLE `payment_status_logs` (
  `id` int(11) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `status_message` text NOT NULL,
  `status` varchar(50) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `first_name` varchar(255) DEFAULT NULL,
  `surname` varchar(255) DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `address1` varchar(255) DEFAULT NULL,
  `address2` varchar(255) DEFAULT NULL,
  `postcode` varchar(20) DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
  `area` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `education` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','freelancer','client') NOT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `profile_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `username`, `first_name`, `surname`, `mobile`, `address1`, `address2`, `postcode`, `state`, `area`, `country`, `education`, `password`, `role`, `status`, `created_at`, `updated_at`, `profile_image`) VALUES
(1, 'sraj11120@gmail.com', '', 'Swaraj', '.', '123456789', '', '', '123456', 'Mars', '', '', 'ertyui', '$2y$10$yC/yuMR.eOHz.cWJ/vkkEOPPrViRmFSNYKvIaY4vjMY4PQZTgOHry', 'freelancer', 'active', '2024-10-18 17:26:56', '2024-12-24 00:00:45', NULL),
(2, 'sraj11121@gmail.com', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '$2y$10$E/XSdS3NIxpkxej3C2q6EukwLaY03kjfbRi8FK9DHQ8oHIiIXfUUe', 'client', 'active', '2024-10-18 17:27:24', '2024-10-18 17:27:24', NULL),
(3, 'sraj@gmail.com', '', 'S', 'Raj', '123456789', 'aDsfgvhj', 'dsfxgvhmnj', '147895', 'asdfgh', 'dsfghjkl', 'dsfgvhbnjmk,', 'sdfghvjk', '$2y$10$5vxlkwAt377rn10dRMFsOOASmJ8Li2/4JGeW5s/EDI9WkVha7R0r2', 'client', 'active', '2024-10-19 13:17:59', '2024-10-19 13:17:59', NULL),
(8, 'swagath@gmail.com', 'swagath', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'password@', 'client', 'active', '2024-12-19 05:13:32', '2024-12-19 18:52:19', NULL),
(9, 'sraj1@gmail.com', 'sraj1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '$argon2id$v=19$m=65536,t=4,p=1$SE52WlB6Uzk0aC9QbURDTA$tqFrJRo4o9g51khZacJWzlCWL4shq34tAjk8RzlHDFw', 'client', 'active', '2024-12-19 19:09:33', '2024-12-19 19:09:33', NULL),
(10, 'sraj2@gmail.com', 'sraj2', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '$argon2id$v=19$m=65536,t=4,p=1$RWdmSUh5ZEs2QlU3RmZBaQ$TCagqq4VFv8p4Cl4zghlUc5PfJdAAd+yBZynENNjX2A', 'client', 'active', '2024-12-20 08:08:00', '2024-12-20 18:33:03', NULL),
(17, 'admin@gmail.com', 'admin', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '$argon2id$v=19$m=65536,t=4,p=1$ckxKTFM5VjJwYXlCdXhZeg$R0ZCVDmvBxgMh+vzlKJX5Jp7DnJsSWfY4M2exVK9Qys', 'admin', 'active', '2024-12-20 13:14:28', '2024-12-20 13:14:28', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `job_id` (`job_id`),
  ADD KEY `freelancer_id` (`freelancer_id`);

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `client_job_applications`
--
ALTER TABLE `client_job_applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `job_id` (`job_id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `client_notifications`
--
ALTER TABLE `client_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `freelancers`
--
ALTER TABLE `freelancers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `freelancer_id` (`freelancer_id`),
  ADD KEY `fk_jobs_assigned_to` (`assigned_to`);

--
-- Indexes for table `job_applications`
--
ALTER TABLE `job_applications`
  ADD PRIMARY KEY (`application_id`),
  ADD UNIQUE KEY `unique_application` (`job_id`,`client_id`),
  ADD KEY `client_id` (`client_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `job_id` (`job_id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `job_id` (`job_id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `freelancer_id` (`freelancer_id`);

--
-- Indexes for table `payment_status_logs`
--
ALTER TABLE `payment_status_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payment_id` (`payment_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `client_job_applications`
--
ALTER TABLE `client_job_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `client_notifications`
--
ALTER TABLE `client_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `job_applications`
--
ALTER TABLE `job_applications`
  MODIFY `application_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payment_status_logs`
--
ALTER TABLE `payment_status_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `applications`
--
ALTER TABLE `applications`
  ADD CONSTRAINT `applications_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `applications_ibfk_2` FOREIGN KEY (`freelancer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `clients`
--
ALTER TABLE `clients`
  ADD CONSTRAINT `clients_ibfk_1` FOREIGN KEY (`id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `client_job_applications`
--
ALTER TABLE `client_job_applications`
  ADD CONSTRAINT `client_job_applications_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`),
  ADD CONSTRAINT `client_job_applications_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `client_notifications`
--
ALTER TABLE `client_notifications`
  ADD CONSTRAINT `client_notifications_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `freelancers`
--
ALTER TABLE `freelancers`
  ADD CONSTRAINT `freelancers_ibfk_1` FOREIGN KEY (`id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `jobs`
--
ALTER TABLE `jobs`
  ADD CONSTRAINT `fk_jobs_assigned_to` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `jobs_ibfk_1` FOREIGN KEY (`freelancer_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `jobs_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`);

--
-- Constraints for table `job_applications`
--
ALTER TABLE `job_applications`
  ADD CONSTRAINT `job_applications_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`),
  ADD CONSTRAINT `job_applications_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`),
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `messages_ibfk_3` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payment_status_logs`
--
ALTER TABLE `payment_status_logs`
  ADD CONSTRAINT `payment_status_logs_ibfk_1` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
