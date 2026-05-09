-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 11, 2026 at 06:20 PM
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
-- Database: `gym_management`
--

-- --------------------------------------------------------

--
-- Stand-in structure for view `active_members_view`
-- (See below for the actual view)
--
CREATE TABLE `active_members_view` (
`member_id` varchar(10)
,`first_name` varchar(100)
,`last_name` varchar(100)
,`membership_status` enum('Active','Pending','Expired')
);

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL,
  `member_id` varchar(10) NOT NULL,
  `date` date NOT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `duration` varchar(20) DEFAULT NULL,
  `status` enum('Present','Absent','Currently Present') NOT NULL DEFAULT 'Absent',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`attendance_id`, `member_id`, `date`, `time_in`, `time_out`, `duration`, `status`, `created_at`) VALUES
(7, 'MEM0001', '2025-12-21', '21:22:26', '21:22:37', NULL, 'Present', '2025-12-21 20:22:26'),
(8, 'MEM0001', '2025-12-21', '21:22:59', '21:23:26', NULL, 'Present', '2025-12-21 20:22:59'),
(9, 'MEM0001', '2026-01-08', '15:16:27', '15:35:21', NULL, 'Present', '2026-01-08 14:16:27');

-- --------------------------------------------------------

--
-- Table structure for table `billing`
--

CREATE TABLE `billing` (
  `billing_id` int(11) NOT NULL,
  `member_id` varchar(10) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `payment_status` enum('Paid','Pending','Overdue') NOT NULL DEFAULT 'Pending',
  `payment_date` date DEFAULT NULL,
  `due_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `transaction_id` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_by` varchar(10) DEFAULT NULL,
  `billing_amount` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `billing`
--

INSERT INTO `billing` (`billing_id`, `member_id`, `plan_id`, `payment_status`, `payment_date`, `due_date`, `created_at`, `transaction_id`, `description`, `created_by`, `billing_amount`) VALUES
(1, 'MEM0001', 1, 'Paid', '2026-01-08', '2026-01-16', '2026-01-07 20:42:52', '0002', 'good', 'USR0001', 100.00),
(3, 'MEM0001', 3, 'Paid', '2026-01-08', '2026-07-07', '2026-01-08 11:54:04', '001', 'eww', 'USR0001', 3000.00);

-- --------------------------------------------------------

--
-- Table structure for table `equipment`
--

CREATE TABLE `equipment` (
  `equipment_id` int(11) NOT NULL,
  `equipment_name` varchar(100) NOT NULL,
  `quantity_available` int(11) NOT NULL,
  `total_quantity` int(11) NOT NULL DEFAULT 1,
  `location` varchar(150) DEFAULT NULL,
  `warranty_expiry` date DEFAULT NULL,
  `status` enum('Working','Under Maintenance','Needs Repair') NOT NULL DEFAULT 'Working',
  `last_maintenance_date` date DEFAULT NULL,
  `next_maintenance_date` date DEFAULT NULL,
  `category` enum('Cardio', 'Strength', 'Accessories', 'Free Weights' ) NOT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `purchase_price` decimal(10,2) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `equipment`
--

INSERT INTO `equipment` (`equipment_id`, `equipment_name`, `quantity_available`, `total_quantity`, `location`, `warranty_expiry`, `status`, `last_maintenance_date`, `next_maintenance_date`, `category`, `brand`, `model`, `serial_number`, `purchase_date`, `purchase_price`, `notes`) VALUES
(1, 'Treadmill Pro X500', 2, 10, 'acad', '2026-04-05', 'Working', NULL, NULL, 'Cardio', 'Life Fitness', 'X500 Commercial Series', 'LF-TM-2023-0456', '2023-04-25', 4000.00, 'ok good'),
(2, 'adas', 5, 10, 'uep', '2026-01-15', 'Working', NULL, NULL, 'Strength', 'asdad', 'dasdad', '356', '2026-01-05', 1000.00, 'ghjk');

-- --------------------------------------------------------

--
-- Table structure for table `equipment_maintenance`
--

CREATE TABLE `equipment_maintenance` (
  `maintenance_id` int(11) NOT NULL,
  `equipment_id` varchar(10) NOT NULL,
  `maintenance_type` enum('Routine','Repair','Inspection','Cleaning') NOT NULL,
  `maintenance_date` date NOT NULL,
  `description` text NOT NULL,
  `cost` decimal(10,2) DEFAULT 0.00,
  `performed_by` varchar(100) DEFAULT NULL,
  `next_maintenance_date` date DEFAULT NULL,
  `status` enum('Completed','In Progress','Scheduled') DEFAULT 'Scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `equipment_maintenance`
--

INSERT INTO `equipment_maintenance` (`maintenance_id`, `equipment_id`, `maintenance_type`, `maintenance_date`, `description`, `cost`, `performed_by`, `next_maintenance_date`, `status`, `created_at`, `updated_at`) VALUES
(1, 'EQP0001', 'Inspection', '2025-12-22', 'jguiyu', 150.00, 'ytfyjukgiyl', '2025-02-25', 'Completed', '2025-12-21 23:25:04', '2025-12-21 23:25:04'),
(2, 'EQP0002', 'Repair', '2026-01-12', 'sfdgfhg', 2345.00, 'ytfyjukgiyl', '2026-01-31', 'In Progress', '2026-01-11 16:06:10', '2026-01-11 16:06:10');

-- --------------------------------------------------------

--
-- Stand-in structure for view `equipment_status_view`
-- (See below for the actual view)
--
CREATE TABLE `equipment_status_view` (
`category` varchar(50)
,`total_equipment` bigint(21)
,`working` decimal(22,0)
,`maintenance` decimal(22,0)
,`repair` decimal(22,0)
,`out_of_order` decimal(22,0)
);

-- --------------------------------------------------------

--
-- Table structure for table `members`
--

CREATE TABLE `members` (
  `member_id` varchar(10) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `date_of_birth` date NOT NULL,
  `membership_plan` enum('Monthly','Quarterly','Annual') NOT NULL,
  `membership_status` enum('Active','Pending','Expired') NOT NULL DEFAULT 'Pending',
  `registration_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `renewal_date` date DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `rfid_card_number` varchar(50) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `membership_start_date` date DEFAULT NULL,
  `membership_end_date` date DEFAULT NULL,
  `user_id` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `members`
--

INSERT INTO `members` (`member_id`, `first_name`, `last_name`, `middle_name`, `username`, `gender`, `contact_number`, `address`, `date_of_birth`, `membership_plan`, `membership_status`, `registration_date`, `renewal_date`, `email`, `rfid_card_number`, `profile_picture`, `membership_start_date`, `membership_end_date`, `user_id`) VALUES
('MEM0001', 'Stephanie Drew', 'Destura', 'Flores', 'stephwerd', 'Female', '09602145440', 'UEP Zone 1, Catarman', '2004-05-30', 'Monthly', 'Active', '2025-12-19 16:00:00', NULL, 'stephdestu@gmail.com', '1323299067', 'img/profile_69469fb727448.jpg', '2025-12-20', '2026-01-19', 'USR0002');

-- --------------------------------------------------------

--
-- Table structure for table `membership_plans`
--

CREATE TABLE `membership_plans` (
  `plan_id` int(11) NOT NULL,
  `plan_name` varchar(50) NOT NULL,
  `plan_type` enum('Monthly','Quarterly','Semi-Annually','Annually') NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `duration_days` int(11) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `membership_plans`
--

INSERT INTO `membership_plans` (`plan_id`, `plan_name`, `plan_type`, `price`, `duration_days`, `is_active`, `created_at`, `updated_at`) VALUES
(1, '1 Month', 'Monthly', 500.00, 30, 1, '2025-09-18 17:10:25', '2026-01-08 11:37:48'),
(2, '3 Months', 'Quarterly', 1500.00, 90, 1, '2025-09-18 17:10:25', '2026-01-08 11:38:03'),
(3, '6 Months', 'Semi-Annually', 3000.00, 180, 1, '2025-09-18 17:10:25', '2026-01-08 11:38:10'),
(4, '12 Months', 'Annually', 6000.00, 365, 1, '2025-09-18 17:10:25', '2026-01-08 11:38:17');

-- --------------------------------------------------------

--
-- Table structure for table `member_progress`
--

CREATE TABLE `member_progress` (
  `progress_id` int(11) NOT NULL,
  `member_id` varchar(10) NOT NULL,
  `attendance_summary` text DEFAULT NULL,
  `sessions_completed` int(11) DEFAULT 0,
  `trainer_feedback` text DEFAULT NULL,
  `progress_status` enum('Improving','Needs Attention','At Risk') NOT NULL DEFAULT 'Needs Attention',
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `progress`
--

CREATE TABLE `progress` (
  `progress_id` int(11) NOT NULL,
  `member_id` varchar(10) NOT NULL,
  `progress_date` date NOT NULL,
  `exercise_name` varchar(100) NOT NULL,
  `sets` int(11) DEFAULT NULL,
  `reps` int(11) DEFAULT NULL,
  `weight` decimal(8,2) DEFAULT NULL,
  `duration_minutes` int(11) DEFAULT NULL,
  `distance_km` decimal(8,2) DEFAULT NULL,
  `calories_burned` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `recorded_by` varchar(10) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_logs`
--

CREATE TABLE `system_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` varchar(10) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` varchar(50) DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_logs`
--

INSERT INTO `system_logs` (`log_id`, `user_id`, `action`, `table_name`, `record_id`, `old_values`, `new_values`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 14:58:15'),
(2, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 15:24:50'),
(3, NULL, 'LOGIN', 'users', 'USR0002', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 15:41:53'),
(4, NULL, 'LOGIN', 'users', 'UEP0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 15:55:46'),
(5, NULL, 'LOGIN', 'users', 'UEP0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 18:35:11'),
(6, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-27 18:35:25'),
(7, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-29 14:26:33'),
(8, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-29 14:35:51'),
(9, NULL, 'LOGIN', 'users', 'UEP0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-29 14:55:24'),
(10, NULL, 'LOGIN', 'users', 'UEP0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-29 15:40:54'),
(11, NULL, 'LOGIN', 'users', 'UEP0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-29 15:46:06'),
(12, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-03 11:59:25'),
(13, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-07 11:14:30'),
(14, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-10-07 11:16:02'),
(15, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 07:19:55'),
(16, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-05 10:49:02'),
(17, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-05 11:01:43'),
(18, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-05 11:08:32'),
(19, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-05 11:21:21'),
(20, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-05 11:21:25'),
(21, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-05 11:25:53'),
(22, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-05 11:25:57'),
(23, NULL, 'LOGIN', 'users', 'UEP0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-05 12:56:44'),
(24, NULL, 'LOGOUT', 'users', 'UEP0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-05 12:56:59'),
(25, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-05 12:57:11'),
(26, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-05 12:57:16'),
(27, NULL, 'LOGIN', 'users', 'UEP0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-05 12:57:25'),
(28, NULL, 'LOGIN', 'users', 'UEP0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-05 12:59:31'),
(29, NULL, 'LOGIN', 'users', 'UEP0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-05 12:59:41'),
(30, NULL, 'LOGIN', 'users', 'UEP0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-05 13:04:58'),
(31, NULL, 'LOGOUT', 'users', 'UEP0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-05 13:05:07'),
(32, NULL, 'LOGIN', 'users', 'UEP0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-05 13:05:13'),
(33, NULL, 'LOGIN', 'users', 'UEP0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-05 13:05:28'),
(35, NULL, 'LOGIN', 'users', 'USR0002', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-05 16:01:17'),
(36, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 09:26:16'),
(37, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 12:39:43'),
(38, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 12:39:50'),
(39, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 12:45:11'),
(40, 'USR0002', 'LOGIN', 'users', 'USR0002', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 13:08:15'),
(41, 'USR0002', 'LOGIN', 'users', 'USR0002', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 13:17:51'),
(42, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 14:48:56'),
(43, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 14:58:54'),
(44, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 15:00:32'),
(45, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 15:38:05'),
(46, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 15:38:28'),
(47, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 15:41:47'),
(48, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 15:51:48'),
(49, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 15:52:22'),
(50, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 16:22:04'),
(51, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 16:23:25'),
(52, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 16:23:58'),
(53, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 16:28:16'),
(54, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 16:29:20'),
(55, 'USR0003', 'LOGIN', 'users', 'USR0003', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 16:29:29'),
(56, 'USR0003', 'LOGOUT', 'users', 'USR0003', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 16:55:32'),
(57, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 16:55:37'),
(58, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 18:02:53'),
(59, 'USR0003', 'LOGIN', 'users', 'USR0003', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 18:03:00'),
(60, 'USR0003', 'LOGOUT', 'users', 'USR0003', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 18:20:57'),
(61, 'USR0002', 'LOGIN', 'users', 'USR0002', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 18:21:05'),
(62, 'USR0002', 'LOGOUT', 'users', 'USR0002', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 18:21:16'),
(63, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 18:21:24'),
(64, 'USR0003', 'LOGIN', 'users', 'USR0003', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 19:28:01'),
(65, 'USR0003', 'LOGOUT', 'users', 'USR0003', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 19:28:39'),
(66, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 19:28:47'),
(67, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 20:26:46'),
(68, 'USR0003', 'LOGIN', 'users', 'USR0003', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 20:26:55'),
(69, 'USR0003', 'LOGOUT', 'users', 'USR0003', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 20:27:32'),
(70, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 20:27:39'),
(71, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 20:39:46'),
(72, 'USR0003', 'LOGIN', 'users', 'USR0003', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 20:39:52'),
(73, 'USR0003', 'LOGOUT', 'users', 'USR0003', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 21:49:56'),
(74, 'USR0003', 'LOGIN', 'users', 'USR0003', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 21:50:03'),
(75, 'USR0003', 'LOGIN', 'users', 'USR0003', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 21:53:51'),
(76, 'USR0003', 'LOGOUT', 'users', 'USR0003', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 22:47:11'),
(77, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 22:47:19'),
(78, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-22 03:13:27'),
(79, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-22 03:13:38'),
(80, 'USR0003', 'LOGIN', 'users', 'USR0003', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-22 03:13:44'),
(81, 'USR0003', 'LOGIN', 'users', 'USR0003', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-22 03:18:43'),
(82, 'USR0003', 'LOGOUT', 'users', 'USR0003', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-22 03:21:16'),
(83, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-22 03:21:26'),
(84, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-22 03:22:04'),
(85, 'USR0003', 'LOGIN', 'users', 'USR0003', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-22 03:23:21'),
(86, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-22 03:23:34'),
(87, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-22 03:23:40'),
(88, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-22 03:24:37'),
(89, 'USR0003', 'LOGIN', 'users', 'USR0003', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-22 05:49:12'),
(90, 'USR0003', 'LOGOUT', 'users', 'USR0003', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-22 05:55:12'),
(91, 'USR0003', 'LOGIN', 'users', 'USR0003', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-22 05:57:53'),
(92, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-22 10:37:11'),
(93, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-22 11:02:35'),
(94, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 10:14:24'),
(95, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 10:22:59'),
(96, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 10:24:10'),
(97, 'USR0002', 'LOGIN', 'users', 'USR0002', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 10:24:56'),
(98, 'USR0002', 'LOGOUT', 'users', 'USR0002', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 10:25:23'),
(99, 'USR0003', 'LOGIN', 'users', 'USR0003', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 10:26:16'),
(100, 'USR0003', 'LOGOUT', 'users', 'USR0003', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 10:51:52'),
(101, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 10:51:58'),
(102, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-07 13:42:21'),
(103, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-07 13:53:03'),
(104, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-07 14:58:43'),
(105, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-07 14:59:15'),
(106, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-07 15:01:28'),
(107, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-07 18:31:45'),
(108, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '192.168.1.15', 'Mozilla/5.0 (iPhone; CPU iPhone OS 26_1_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/143.0.7499.151 Mobile/15E148 Safari/604.1', '2026-01-07 21:21:33'),
(109, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 10:56:32'),
(110, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 11:06:59'),
(111, 'USR0002', 'LOGIN', 'users', 'USR0002', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 11:07:05'),
(112, 'USR0002', 'LOGOUT', 'users', 'USR0002', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 11:11:47'),
(113, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 11:11:52'),
(114, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 14:23:08'),
(115, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 16:04:47');

-- --------------------------------------------------------

--
-- Table structure for table `trainers`
--

CREATE TABLE `trainers` (
  `trainer_id` varchar(10) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `specialization` varchar(100) NOT NULL,
  `availability_schedule` text NOT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `hire_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `username` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `trainers`
--

INSERT INTO `trainers` (`trainer_id`, `first_name`, `middle_name`, `last_name`, `gender`, `address`, `date_of_birth`, `specialization`, `availability_schedule`, `contact_number`, `status`, `hire_date`, `username`, `email`) VALUES
('TRN0001', 'Khimverly', '', 'Estido', 'Female', 'UEP', '2004-04-05', 'Weight', '', '09123456789', 'Active', '2025-12-21 16:00:00', 'khim', 'khim@gmail.com');

-- --------------------------------------------------------

--
-- Table structure for table `trainer_assignments`
--

CREATE TABLE `trainer_assignments` (
  `assignment_id` int(11) NOT NULL,
  `trainer_id` varchar(10) NOT NULL,
  `member_id` varchar(10) NOT NULL,
  `session_type` enum('Personal Training','Group Class','Consultation') NOT NULL,
  `session_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `status` enum('scheduled','ongoing','completed') NOT NULL DEFAULT 'scheduled',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `trainer_assignments`
--

INSERT INTO `trainer_assignments` (`assignment_id`, `trainer_id`, `member_id`, `session_type`, `session_date`, `start_time`, `end_time`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'TRN0001', 'MEM0001', 'Personal Training', '2025-12-21', '00:58:00', '00:58:00', 'completed', 'okay', '2025-12-21 16:56:45', '2025-12-21 22:02:55'),
(2, 'TRN0001', 'MEM0001', 'Group Class', '2025-12-26', '00:57:00', '05:57:00', 'ongoing', 'k', '2025-12-21 16:57:25', '2025-12-21 22:02:11');

-- --------------------------------------------------------

--
-- Table structure for table `trainer_sessions`
--

CREATE TABLE `trainer_sessions` (
  `id` int(10) UNSIGNED NOT NULL,
  `trainer_id` varchar(10) NOT NULL,
  `session_name` varchar(100) NOT NULL,
  `session_date` date NOT NULL,
  `scheduled_start` time NOT NULL,
  `scheduled_end` time NOT NULL,
  `actual_start` datetime DEFAULT NULL,
  `actual_end` datetime DEFAULT NULL,
  `status` enum('scheduled','ongoing','completed') NOT NULL DEFAULT 'scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` varchar(10) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('admin','staff','member','trainer') NOT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `email`, `full_name`, `role`, `profile_picture`, `is_active`, `created_at`, `updated_at`) VALUES
('USR0001', 'admin', '$2y$10$JEAhhgjBOa4URJRW1iVazex63bh4Cgi.5kje6H/TNi1KZRHG9Pbfe', 'admin@uepgym.com', 'System Administrator', 'admin', NULL, 1, '2025-09-18 17:10:24', '2025-09-27 15:24:23'),
('USR0002', 'stephwerd', '$2y$10$.QAtq7Oy1CXYe.rbIt0bMOY5SWueVR0YUsXiDSPFEN2eAXtNcjuy6', 'stephdestu@gmail.com', 'Stephanie Drew Flores Destura', 'member', 'img/profile_69469fb727448.jpg', 1, '2025-12-20 13:08:07', '2025-12-20 13:08:07'),
('USR0003', 'khim', '$2y$10$dKDFQbtaiJEBd28uqfNZ1Owl8w7jFBlkULHBi.TldvR0TO.s/wrtK', 'khim@gmail.com', 'Khimverly Estido', 'trainer', 'img/profile_69469fb727448.jpg', 1, '2025-12-21 16:28:09', '2025-12-21 19:08:56');

-- --------------------------------------------------------

--
-- Table structure for table `vital_signs`
--

CREATE TABLE `vital_signs` (
  `record_id` varchar(10) NOT NULL,
  `member_id` varchar(10) NOT NULL,
  `date_of_recording` date NOT NULL,
  `heart_rate` int(11) DEFAULT NULL,
  `blood_pressure_systolic` int(11) DEFAULT NULL,
  `blood_pressure_diastolic` int(11) DEFAULT NULL,
  `oxygen_level` int(11) DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `bmi` decimal(4,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `trainer_id` varchar(10) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure for view `active_members_view`
--
DROP TABLE IF EXISTS `active_members_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `active_members_view`  AS SELECT `members`.`member_id` AS `member_id`, `members`.`first_name` AS `first_name`, `members`.`last_name` AS `last_name`, `members`.`membership_status` AS `membership_status` FROM `members` WHERE `members`.`membership_status` = 'Active' ;

-- --------------------------------------------------------

--
-- Structure for view `equipment_status_view`
--
DROP TABLE IF EXISTS `equipment_status_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `equipment_status_view`  AS SELECT `equipment`.`category` AS `category`, count(0) AS `total_equipment`, sum(case when `equipment`.`status` = 'Working' then 1 else 0 end) AS `working`, sum(case when `equipment`.`status` = 'Under Maintenance' then 1 else 0 end) AS `maintenance`, sum(case when `equipment`.`status` = 'Needs Repair' then 1 else 0 end) AS `repair`, sum(case when `equipment`.`status` = 'Out of Order' then 1 else 0 end) AS `out_of_order` FROM `equipment` GROUP BY `equipment`.`category` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD KEY `member_id` (`member_id`),
  ADD KEY `idx_attendance_member` (`member_id`),
  ADD KEY `idx_attendance_status` (`status`);

--
-- Indexes for table `billing`
--
ALTER TABLE `billing`
  ADD PRIMARY KEY (`billing_id`),
  ADD KEY `member_id` (`member_id`),
  ADD KEY `idx_billing_status` (`payment_status`),
  ADD KEY `idx_billing_due_date` (`due_date`),
  ADD KEY `idx_billing_plan` (`plan_id`);

--
-- Indexes for table `equipment`
--
ALTER TABLE `equipment`
  ADD PRIMARY KEY (`equipment_id`),
  ADD KEY `idx_equipment_category` (`category`);

--
-- Indexes for table `equipment_maintenance`
--
ALTER TABLE `equipment_maintenance`
  ADD PRIMARY KEY (`maintenance_id`);

--
-- Indexes for table `members`
--
ALTER TABLE `members`
  ADD PRIMARY KEY (`member_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `rfid_card_number` (`rfid_card_number`),
  ADD KEY `idx_members_plan` (`membership_plan`),
  ADD KEY `fk_members_users` (`user_id`);

--
-- Indexes for table `membership_plans`
--
ALTER TABLE `membership_plans`
  ADD PRIMARY KEY (`plan_id`);

--
-- Indexes for table `member_progress`
--
ALTER TABLE `member_progress`
  ADD PRIMARY KEY (`progress_id`),
  ADD KEY `member_id` (`member_id`);

--
-- Indexes for table `progress`
--
ALTER TABLE `progress`
  ADD PRIMARY KEY (`progress_id`);

--
-- Indexes for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `trainers`
--
ALTER TABLE `trainers`
  ADD PRIMARY KEY (`trainer_id`);

--
-- Indexes for table `trainer_assignments`
--
ALTER TABLE `trainer_assignments`
  ADD PRIMARY KEY (`assignment_id`);

--
-- Indexes for table `trainer_sessions`
--
ALTER TABLE `trainer_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_trainer_sessions_trainer` (`trainer_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `vital_signs`
--
ALTER TABLE `vital_signs`
  ADD PRIMARY KEY (`record_id`),
  ADD KEY `member_id` (`member_id`),
  ADD KEY `trainer_id` (`trainer_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `billing`
--
ALTER TABLE `billing`
  MODIFY `billing_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `equipment`
--
ALTER TABLE `equipment`
  MODIFY `equipment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `equipment_maintenance`
--
ALTER TABLE `equipment_maintenance`
  MODIFY `maintenance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `membership_plans`
--
ALTER TABLE `membership_plans`
  MODIFY `plan_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `member_progress`
--
ALTER TABLE `member_progress`
  MODIFY `progress_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `progress`
--
ALTER TABLE `progress`
  MODIFY `progress_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_logs`
--
ALTER TABLE `system_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=116;

--
-- AUTO_INCREMENT for table `trainer_assignments`
--
ALTER TABLE `trainer_assignments`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `trainer_sessions`
--
ALTER TABLE `trainer_sessions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`member_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `billing`
--
ALTER TABLE `billing`
  ADD CONSTRAINT `billing_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`member_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_billing_plan` FOREIGN KEY (`plan_id`) REFERENCES `membership_plans` (`plan_id`) ON UPDATE CASCADE;

--
-- Constraints for table `members`
--
ALTER TABLE `members`
  ADD CONSTRAINT `fk_members_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `member_progress`
--
ALTER TABLE `member_progress`
  ADD CONSTRAINT `member_progress_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`member_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD CONSTRAINT `system_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `trainer_sessions`
--
ALTER TABLE `trainer_sessions`
  ADD CONSTRAINT `fk_trainer_sessions_trainer` FOREIGN KEY (`trainer_id`) REFERENCES `trainers` (`trainer_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `vital_signs`
--
ALTER TABLE `vital_signs`
  ADD CONSTRAINT `vital_signs_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`member_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
