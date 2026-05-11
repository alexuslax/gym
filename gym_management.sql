-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 11, 2026 at 09:49 AM
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
  `status` enum('Present','Absent','Currently Present') NOT NULL DEFAULT 'Absent',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`attendance_id`, `member_id`, `date`, `time_in`, `time_out`, `status`, `created_at`) VALUES
(19, 'STU0001', '2026-02-04', '22:58:53', NULL, 'Present', '2026-02-04 14:58:53'),
(20, 'STU0001', '2026-02-24', '09:10:01', '09:10:09', 'Present', '2026-02-24 01:10:01'),
(21, 'STU0001', '2026-02-24', '09:10:15', '09:10:18', 'Present', '2026-02-24 01:10:15');

-- --------------------------------------------------------

--
-- Table structure for table `billing`
--

CREATE TABLE `billing` (
  `billing_id` int(11) NOT NULL,
  `member_id` varchar(10) NOT NULL,
  `plan_id` int(11) NOT NULL,
  `payment_status` enum('Paid','Pending','Overdue') NOT NULL DEFAULT 'Pending',
  `due_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `description` text DEFAULT NULL,
  `created_by` varchar(10) DEFAULT NULL,
  `billing_amount` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `billing`
--

INSERT INTO `billing` (`billing_id`, `member_id`, `plan_id`, `payment_status`, `due_date`, `created_at`, `description`, `created_by`, `billing_amount`) VALUES
(10, 'STU0001', 1, 'Paid', '2026-02-03', '2026-02-02 21:50:04', 'Additional charges: [{\"name\":\"Locker Rental\",\"amount\":100}]', 'USR0001', 600.00),
(11, 'STU0001', 2, 'Pending', '2026-03-30', '2026-03-30 07:04:37', '', 'USR0001', 1500.00);

-- --------------------------------------------------------

--
-- Table structure for table `equipment`
--

CREATE TABLE `equipment` (
  `equipment_id` int(11) NOT NULL,
  `equipment_name` varchar(100) NOT NULL,
  `quantity_available` int(11) NOT NULL DEFAULT 0,
  `total_quantity` int(11) NOT NULL DEFAULT 0,
  `status` enum('Working','Under Maintenance','Needs Repair','Out of Order') NOT NULL DEFAULT 'Working',
  `is_machine` tinyint(1) DEFAULT 0,
  `is_weights` tinyint(1) DEFAULT 0,
  `weight_kg` decimal(8,2) DEFAULT NULL,
  `category` enum('Cardio','Strength','Accessories','Free Weights') NOT NULL,
  `purchase_date` date DEFAULT NULL,
  `purchase_price` decimal(10,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `equipment`
--

INSERT INTO `equipment` (`equipment_id`, `equipment_name`, `quantity_available`, `total_quantity`, `status`, `is_machine`, `is_weights`, `weight_kg`, `category`, `purchase_date`, `purchase_price`, `notes`, `created_at`, `updated_at`) VALUES
(8, 'Lat Machine', 11, 11, 'Working', 1, 0, NULL, '', '0000-00-00', 0.00, '', '2026-02-04 15:26:04', '2026-02-04 15:26:04'),
(10, 'Treadmill', 10, 10, 'Working', 1, 0, NULL, '', '0000-00-00', 0.00, '', '2026-02-04 16:56:10', '2026-02-04 16:56:10'),
(11, 'Stationary Bike', 10, 10, 'Working', 1, 0, NULL, '', '0000-00-00', 0.00, '', '2026-02-04 16:56:22', '2026-02-04 16:56:22'),
(12, 'Bench', 1, 1, 'Working', 1, 0, NULL, '', '0000-00-00', 0.00, '', '2026-02-04 16:56:32', '2026-02-04 16:56:32'),
(13, 'Leg Press Vertical', 1, 1, 'Working', 1, 0, NULL, '', '0000-00-00', 0.00, '', '2026-02-04 16:56:46', '2026-02-04 16:56:46'),
(14, 'Barbell Plate', 2, 2, 'Working', 0, 1, 20.00, '', '0000-00-00', 0.00, '', '2026-02-04 16:57:19', '2026-02-04 16:57:19'),
(15, 'Dumbells', 2, 2, 'Working', 0, 1, 2.00, '', '0000-00-00', 0.00, '', '2026-02-04 16:57:56', '2026-02-24 01:23:51'),
(16, 'Barbell Plates', 2, 2, 'Working', 0, 1, 1.00, '', '0000-00-00', 0.00, '', '2026-02-04 16:58:12', '2026-02-24 01:30:37'),
(17, 'Handle Bar', 1, 1, 'Working', 0, 1, 3.00, '', '0000-00-00', 0.00, '', '2026-02-04 16:58:29', '2026-02-04 17:09:33');

--
-- Triggers `equipment`
--
DELIMITER $$
CREATE TRIGGER `after_equipment_insert` AFTER INSERT ON `equipment` FOR EACH ROW BEGIN
  DECLARE i INT DEFAULT 1;
  WHILE i <= NEW.total_quantity DO
    INSERT INTO equipment_units (equipment_id, unit_number, status, purchase_date)
    VALUES (NEW.equipment_id, i, 'Available', NEW.purchase_date);
    SET i = i + 1;
  END WHILE;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `equipment_maintenance`
--

CREATE TABLE `equipment_maintenance` (
  `maintenance_id` int(11) NOT NULL,
  `equipment_id` int(11) NOT NULL,
  `unit_id` int(11) DEFAULT NULL,
  `maintenance_type` enum('Routine','Repair','Inspection','Cleaning') NOT NULL,
  `maintenance_date` date NOT NULL,
  `description` text NOT NULL,
  `cost` decimal(10,2) DEFAULT 0.00,
  `performed_by` varchar(100) DEFAULT NULL,
  `next_maintenance_date` date DEFAULT NULL,
  `status` enum('Scheduled','In Progress','Completed','Out of Order') NOT NULL,
  `completion_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `equipment_status_view`
-- (See below for the actual view)
--
CREATE TABLE `equipment_status_view` (
`category` enum('Cardio','Strength','Accessories','Free Weights')
,`total_equipment` bigint(21)
,`working` decimal(22,0)
,`maintenance` decimal(22,0)
,`repair` decimal(22,0)
,`out_of_order` decimal(22,0)
);

-- --------------------------------------------------------

--
-- Table structure for table `equipment_units`
--

CREATE TABLE `equipment_units` (
  `unit_id` int(11) NOT NULL,
  `equipment_id` int(11) NOT NULL,
  `unit_number` int(11) NOT NULL,
  `status` enum('Available','Under Maintenance','Out of Order') NOT NULL DEFAULT 'Available',
  `purchase_date` date DEFAULT NULL,
  `last_maintenance_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `equipment_units`
--

INSERT INTO `equipment_units` (`unit_id`, `equipment_id`, `unit_number`, `status`, `purchase_date`, `last_maintenance_date`, `notes`, `created_at`) VALUES
(24, 8, 1, 'Available', '0000-00-00', NULL, NULL, '2026-02-04 15:26:04'),
(25, 8, 2, 'Available', '0000-00-00', NULL, NULL, '2026-02-04 15:26:04'),
(26, 8, 3, 'Available', '0000-00-00', NULL, NULL, '2026-02-04 15:26:04'),
(27, 8, 4, 'Available', '0000-00-00', NULL, NULL, '2026-02-04 15:26:04'),
(28, 8, 5, 'Available', '0000-00-00', NULL, NULL, '2026-02-04 15:26:04'),
(29, 8, 6, 'Available', '0000-00-00', NULL, NULL, '2026-02-04 15:26:04'),
(30, 8, 7, 'Available', '0000-00-00', NULL, NULL, '2026-02-04 15:26:04'),
(31, 8, 8, 'Available', '0000-00-00', NULL, NULL, '2026-02-04 15:26:04'),
(32, 8, 9, 'Available', '0000-00-00', NULL, NULL, '2026-02-04 15:26:04'),
(33, 8, 10, 'Available', '0000-00-00', NULL, NULL, '2026-02-04 15:26:04'),
(34, 8, 11, 'Available', '0000-00-00', NULL, NULL, '2026-02-04 15:26:04'),
(36, 10, 1, 'Available', '0000-00-00', NULL, NULL, '2026-02-04 16:56:10'),
(37, 10, 2, 'Available', '0000-00-00', NULL, NULL, '2026-02-04 16:56:10'),
(38, 10, 3, 'Available', '0000-00-00', NULL, NULL, '2026-02-04 16:56:10'),
(39, 10, 4, 'Available', '0000-00-00', NULL, NULL, '2026-02-04 16:56:10'),
(40, 10, 5, 'Available', '0000-00-00', NULL, NULL, '2026-02-04 16:56:10'),
(41, 10, 6, 'Available', '0000-00-00', NULL, NULL, '2026-02-04 16:56:10'),
(42, 10, 7, 'Available', '0000-00-00', NULL, NULL, '2026-02-04 16:56:10'),
(43, 10, 8, 'Available', '0000-00-00', NULL, NULL, '2026-02-04 16:56:10'),
(44, 10, 9, 'Available', '0000-00-00', NULL, NULL, '2026-02-04 16:56:10'),
(45, 10, 10, 'Available', '0000-00-00', NULL, NULL, '2026-02-04 16:56:10'),
(46, 11, 1, 'Available', '0000-00-00', NULL, NULL, '2026-02-04 16:56:22'),
(47, 11, 2, 'Available', '0000-00-00', NULL, NULL, '2026-02-04 16:56:22'),
(48, 11, 3, 'Available', '0000-00-00', NULL, NULL, '2026-02-04 16:56:22'),
(49, 11, 4, 'Available', '0000-00-00', NULL, NULL, '2026-02-04 16:56:22'),
(50, 11, 5, 'Available', '0000-00-00', NULL, NULL, '2026-02-04 16:56:22'),
(51, 11, 6, 'Available', '0000-00-00', NULL, NULL, '2026-02-04 16:56:22'),
(52, 11, 7, 'Available', '0000-00-00', NULL, NULL, '2026-02-04 16:56:22'),
(53, 11, 8, 'Available', '0000-00-00', NULL, NULL, '2026-02-04 16:56:22'),
(54, 11, 9, 'Available', '0000-00-00', NULL, NULL, '2026-02-04 16:56:22'),
(55, 11, 10, 'Available', '0000-00-00', NULL, NULL, '2026-02-04 16:56:22'),
(56, 12, 1, 'Available', '0000-00-00', NULL, NULL, '2026-02-04 16:56:32'),
(57, 13, 1, 'Available', '0000-00-00', NULL, NULL, '2026-02-04 16:56:46'),
(58, 14, 1, 'Available', '0000-00-00', NULL, NULL, '2026-02-04 16:57:19'),
(59, 14, 2, 'Available', '0000-00-00', NULL, NULL, '2026-02-04 16:57:19'),
(60, 15, 1, 'Available', '0000-00-00', NULL, NULL, '2026-02-04 16:57:56'),
(61, 15, 2, 'Available', '0000-00-00', NULL, NULL, '2026-02-04 16:57:56'),
(62, 16, 1, 'Available', '0000-00-00', NULL, NULL, '2026-02-04 16:58:12'),
(63, 16, 2, 'Available', '0000-00-00', NULL, NULL, '2026-02-04 16:58:12'),
(64, 17, 1, 'Available', '0000-00-00', NULL, NULL, '2026-02-04 16:58:29');

-- --------------------------------------------------------

--
-- Table structure for table `exercise_completion`
--

CREATE TABLE `exercise_completion` (
  `completion_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `exercise_index` int(11) NOT NULL,
  `exercise_name` varchar(255) NOT NULL,
  `is_completed` tinyint(1) DEFAULT 0,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `exercise_completion`
--

INSERT INTO `exercise_completion` (`completion_id`, `session_id`, `exercise_index`, `exercise_name`, `is_completed`, `completed_at`, `created_at`, `updated_at`) VALUES
(1, 3, 0, 'Stationary Bike', 1, '2026-02-24 06:22:04', '2026-02-24 06:21:01', '2026-02-24 06:22:04'),
(2, 3, 2, 'Bench Decline Sit-ups', 1, '2026-02-24 06:21:09', '2026-02-24 06:21:09', '2026-02-24 06:21:09'),
(3, 3, 1, 'Vertical Leg Press', 1, '2026-02-24 06:21:17', '2026-02-24 06:21:17', '2026-02-24 06:21:17');

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
  `membership_status` enum('Active','Pending','Expired') NOT NULL DEFAULT 'Pending',
  `registration_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `email` varchar(100) DEFAULT NULL,
  `rfid_card_number` varchar(50) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `membership_start_date` date DEFAULT NULL,
  `membership_end_date` date DEFAULT NULL,
  `user_id` varchar(10) NOT NULL,
  `membership_plan` varchar(100) DEFAULT NULL,
  `student_number` varchar(64) DEFAULT NULL,
  `faculty_number` varchar(64) DEFAULT NULL,
  `member_type` enum('student','faculty') NOT NULL,
  `cor_document` varchar(255) DEFAULT NULL,
  `medical_certificate` varchar(255) DEFAULT NULL,
  `id_card` varchar(255) DEFAULT NULL,
  `credit_balance` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `members`
--

INSERT INTO `members` (`member_id`, `first_name`, `last_name`, `middle_name`, `username`, `gender`, `contact_number`, `address`, `date_of_birth`, `membership_status`, `registration_date`, `email`, `rfid_card_number`, `profile_picture`, `membership_start_date`, `membership_end_date`, `user_id`, `membership_plan`, `student_number`, `faculty_number`, `member_type`, `cor_document`, `medical_certificate`, `id_card`, `credit_balance`) VALUES
('STU0001', 'Stephanie Drew', 'Destura', 'Flores', 'stephwerd', 'Female', '09602145440', 'UEP', '2004-05-30', 'Active', '2026-02-02 16:00:00', 'stephdestu@gmail.com', '1323299067', 'img/profiles/profile_698110227d003.jpg', '2026-02-04', '2026-03-06', 'USR0006', '1', '225004', NULL, 'student', 'img/documents/sample.jpg', 'img/documents/sample.jpg', 'img/documents/sample.jpg', 100.00);

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
-- Table structure for table `member_fitness_goals`
--

CREATE TABLE `member_fitness_goals` (
  `goal_id` int(11) NOT NULL,
  `member_id` varchar(10) NOT NULL,
  `fitness_goal` enum('weight_loss','muscle_gain','endurance','general_fitness') NOT NULL,
  `program_data` text DEFAULT NULL,
  `selected_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `member_fitness_goals`
--

INSERT INTO `member_fitness_goals` (`goal_id`, `member_id`, `fitness_goal`, `program_data`, `selected_date`) VALUES
(1, 'STU0001', 'endurance', '{\"Monday\":[{\"exercise\":\"Treadmill Long Run\",\"sets\":\"1\",\"reps\":\"45 min\",\"rest\":\"-\",\"notes\":\"Steady pace, build distance gradually\"},{\"exercise\":\"Dumbbell Squats\",\"sets\":\"3\",\"reps\":\"25\",\"rest\":\"45s\",\"notes\":\"Light weight, leg endurance\"},{\"exercise\":\"Bench Plank Hold\",\"sets\":\"3\",\"reps\":\"90s\",\"rest\":\"60s\",\"notes\":\"Hands on bench, core stability\"}],\"Tuesday\":[{\"exercise\":\"Stationary Bike Intervals\",\"sets\":\"8\",\"reps\":\"2 min high \\/ 2 min low\",\"rest\":\"60s\",\"notes\":\"Interval training for stamina\"},{\"exercise\":\"Bench Push-ups\",\"sets\":\"4\",\"reps\":\"20\",\"rest\":\"45s\",\"notes\":\"Elevated or standard, upper body endurance\"},{\"exercise\":\"Lat Machine Pull-downs\",\"sets\":\"3\",\"reps\":\"Max reps\",\"rest\":\"90s\",\"notes\":\"Light weight to failure\"}],\"Wednesday\":[{\"exercise\":\"Stationary Bike\",\"sets\":\"1\",\"reps\":\"60 min\",\"rest\":\"-\",\"notes\":\"Moderate to high intensity, steady state\"},{\"exercise\":\"Dumbbell Walking Lunges\",\"sets\":\"3\",\"reps\":\"20 each leg\",\"rest\":\"60s\",\"notes\":\"Light dumbbells for stamina\"}],\"Thursday\":[{\"exercise\":\"Treadmill Hill Intervals\",\"sets\":\"10\",\"reps\":\"2 min incline \\/ 1 min flat\",\"rest\":\"-\",\"notes\":\"High intensity intervals\"},{\"exercise\":\"Vertical Leg Press\",\"sets\":\"4\",\"reps\":\"20\",\"rest\":\"60s\",\"notes\":\"Light weight, high reps for endurance\"},{\"exercise\":\"Lat Machine Rows\",\"sets\":\"4\",\"reps\":\"15\",\"rest\":\"45s\",\"notes\":\"Quick tempo\"}],\"Friday\":[{\"exercise\":\"Treadmill Sprint Intervals\",\"sets\":\"10\",\"reps\":\"1 min sprint\",\"rest\":\"120s\",\"notes\":\"Sprint intervals for power\"},{\"exercise\":\"Bench Step-up Jumps\",\"sets\":\"3\",\"reps\":\"15\",\"rest\":\"90s\",\"notes\":\"Explosive power, both legs\"}],\"Saturday\":[{\"exercise\":\"Stationary Bike Long Ride\",\"sets\":\"1\",\"reps\":\"90 min\",\"rest\":\"-\",\"notes\":\"Steady state endurance ride\"},{\"exercise\":\"Dumbbell Core Circuit\",\"sets\":\"3\",\"reps\":\"20 each\",\"rest\":\"60s\",\"notes\":\"Russian twists, wood chops, etc.\"}],\"Sunday\":[{\"exercise\":\"Treadmill Active Recovery Walk\",\"sets\":\"1\",\"reps\":\"30 min\",\"rest\":\"-\",\"notes\":\"Light pace, flexibility & recovery\"}]}', '2026-03-30 06:39:29');

-- --------------------------------------------------------

--
-- Table structure for table `member_program_daily_completion`
--

CREATE TABLE `member_program_daily_completion` (
  `id` int(11) NOT NULL,
  `program_history_id` int(11) NOT NULL,
  `day_name` varchar(20) NOT NULL,
  `completed` tinyint(1) DEFAULT 0,
  `completed_date` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `member_program_daily_completion`
--

INSERT INTO `member_program_daily_completion` (`id`, `program_history_id`, `day_name`, `completed`, `completed_date`) VALUES
(1, 1, 'Monday', 1, '2026-02-24 02:47:34'),
(2, 1, 'Tuesday', 1, '2026-02-24 02:47:37'),
(3, 1, 'Wednesday', 1, '2026-02-24 02:47:41'),
(4, 1, 'Thursday', 0, NULL),
(5, 1, 'Friday', 0, NULL),
(6, 1, 'Saturday', 0, NULL),
(7, 1, 'Sunday', 0, NULL),
(8, 2, 'Monday', 0, NULL),
(9, 2, 'Tuesday', 1, '2026-03-16 07:19:56'),
(10, 2, 'Wednesday', 0, NULL),
(11, 2, 'Thursday', 0, NULL),
(12, 2, 'Friday', 0, NULL),
(13, 2, 'Saturday', 0, NULL),
(14, 2, 'Sunday', 0, NULL),
(15, 3, 'Monday', 0, NULL),
(16, 3, 'Tuesday', 0, NULL),
(17, 3, 'Wednesday', 0, NULL),
(18, 3, 'Thursday', 0, NULL),
(19, 3, 'Friday', 0, NULL),
(20, 3, 'Saturday', 0, NULL),
(21, 3, 'Sunday', 0, NULL),
(22, 4, 'Monday', 0, NULL),
(23, 4, 'Tuesday', 0, NULL),
(24, 4, 'Wednesday', 0, NULL),
(25, 4, 'Thursday', 0, NULL),
(26, 4, 'Friday', 0, NULL),
(27, 4, 'Saturday', 0, NULL),
(28, 4, 'Sunday', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `member_program_history`
--

CREATE TABLE `member_program_history` (
  `id` int(11) NOT NULL,
  `member_id` varchar(10) NOT NULL,
  `fitness_goal` enum('weight_loss','muscle_gain','endurance','general_fitness') NOT NULL,
  `program_name` varchar(255) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `days_completed` int(11) DEFAULT 0,
  `total_days` int(11) DEFAULT 7,
  `generated_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `member_program_history`
--

INSERT INTO `member_program_history` (`id`, `member_id`, `fitness_goal`, `program_name`, `start_date`, `end_date`, `days_completed`, `total_days`, `generated_date`) VALUES
(1, 'STU0001', 'endurance', 'Endurance & Stamina Building', '2026-02-24', '2026-03-02', 3, 7, '2026-02-24 02:47:31'),
(2, 'STU0001', 'endurance', 'Endurance & Stamina Building', '2026-02-24', '2026-03-02', 1, 7, '2026-02-24 02:49:05'),
(3, 'STU0001', 'weight_loss', 'Weight Loss & Fat Burning', '2026-03-16', '2026-03-22', 0, 7, '2026-03-16 07:20:27'),
(4, 'STU0001', 'endurance', 'Endurance & Stamina Building', '2026-03-30', '2026-04-05', 0, 7, '2026-03-30 06:39:29');

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
-- Table structure for table `member_training_sessions`
--

CREATE TABLE `member_training_sessions` (
  `session_id` int(11) NOT NULL,
  `member_id` varchar(10) NOT NULL,
  `trainer_id` varchar(10) NOT NULL,
  `session_date` date NOT NULL,
  `session_time` time NOT NULL,
  `session_duration` int(11) DEFAULT 60,
  `status` enum('scheduled','ongoing','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `member_training_sessions`
--

INSERT INTO `member_training_sessions` (`session_id`, `member_id`, `trainer_id`, `session_date`, `session_time`, `session_duration`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'STU0001', 'TRN0002', '2026-02-05', '10:00:00', 60, 'cancelled', NULL, '2026-02-04 18:18:05', '2026-02-04 18:18:15'),
(2, 'STU0001', 'TRN0002', '2026-02-05', '14:00:00', 120, 'scheduled', NULL, '2026-02-04 18:22:38', '2026-02-04 18:22:38'),
(3, 'STU0001', 'TRN0002', '2026-02-24', '11:00:00', 60, 'completed', NULL, '2026-02-24 02:53:07', '2026-02-24 06:21:20'),
(4, 'STU0001', 'TRN0002', '2026-02-25', '10:00:00', 60, 'scheduled', NULL, '2026-02-24 02:53:19', '2026-02-24 02:53:19');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `billing_id` int(11) NOT NULL,
  `member_id` varchar(100) NOT NULL,
  `payment_amount` decimal(10,2) NOT NULL,
  `payment_date` datetime NOT NULL,
  `transaction_id` varchar(255) DEFAULT NULL,
  `payment_method` varchar(100) DEFAULT NULL,
  `payment_type` enum('advance','installment','full') NOT NULL DEFAULT 'full',
  `installment_no` int(11) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `billing_id`, `member_id`, `payment_amount`, `payment_date`, `transaction_id`, `payment_method`, `payment_type`, `installment_no`, `created_by`, `note`, `created_at`) VALUES
(6, 10, 'STU0001', 700.00, '2026-02-04 21:56:23', '123456789', '', 'advance', 0, 0, '', '2026-02-04 13:56:23');

-- --------------------------------------------------------

--
-- Table structure for table `progress`
--

CREATE TABLE `progress` (
  `progress_id` int(11) NOT NULL,
  `member_id` varchar(10) NOT NULL,
  `trainer_id` varchar(10) DEFAULT NULL,
  `progress_date` date NOT NULL,
  `exercise_name` varchar(100) NOT NULL,
  `sets` int(11) DEFAULT NULL,
  `reps` int(11) DEFAULT NULL,
  `weight` decimal(8,2) DEFAULT NULL,
  `duration_minutes` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `recorded_by` varchar(10) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `progress`
--

INSERT INTO `progress` (`progress_id`, `member_id`, `trainer_id`, `progress_date`, `exercise_name`, `sets`, `reps`, `weight`, `duration_minutes`, `notes`, `recorded_by`, `created_at`, `updated_at`) VALUES
(1, 'MEM0001', 'TRN0001', '2026-01-13', 'Bench Press', 2, 10, 20.00, NULL, 'good form', 'USR0003', '2026-01-13 22:06:38', '2026-01-19 14:57:30'),
(2, 'MEM0001', 'TRN0001', '2026-01-13', 'Squats', 3, 15, 10.00, NULL, 'nice', 'USR0003', '2026-01-13 22:07:14', '2026-01-19 14:59:36'),
(3, 'MEM0001', 'TRN0002', '2026-01-19', 'Treadmill', 3, 10, NULL, 120, 'very good', 'TRN0002', '2026-01-19 15:03:35', '2026-01-19 15:12:45'),
(4, 'MEM0001', 'TRN0002', '2026-01-19', 'Stationary Bike', 4, 6, NULL, 60, 'ok', 'TRN0002', '2026-01-19 15:12:29', '2026-01-19 15:12:29');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `staff_id` varchar(10) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `staff_number` varchar(50) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `hire_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_id` varchar(10) NOT NULL
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
(40, NULL, 'LOGIN', 'users', 'USR0002', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 13:08:15'),
(41, NULL, 'LOGIN', 'users', 'USR0002', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 13:17:51'),
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
(55, NULL, 'LOGIN', 'users', 'USR0003', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 16:29:29'),
(56, NULL, 'LOGOUT', 'users', 'USR0003', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 16:55:32'),
(57, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 16:55:37'),
(58, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 18:02:53'),
(59, NULL, 'LOGIN', 'users', 'USR0003', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 18:03:00'),
(60, NULL, 'LOGOUT', 'users', 'USR0003', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 18:20:57'),
(61, NULL, 'LOGIN', 'users', 'USR0002', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 18:21:05'),
(62, NULL, 'LOGOUT', 'users', 'USR0002', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 18:21:16'),
(63, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 18:21:24'),
(64, NULL, 'LOGIN', 'users', 'USR0003', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 19:28:01'),
(65, NULL, 'LOGOUT', 'users', 'USR0003', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 19:28:39'),
(66, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 19:28:47'),
(67, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 20:26:46'),
(68, NULL, 'LOGIN', 'users', 'USR0003', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 20:26:55'),
(69, NULL, 'LOGOUT', 'users', 'USR0003', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 20:27:32'),
(70, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 20:27:39'),
(71, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 20:39:46'),
(72, NULL, 'LOGIN', 'users', 'USR0003', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 20:39:52'),
(73, NULL, 'LOGOUT', 'users', 'USR0003', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 21:49:56'),
(74, NULL, 'LOGIN', 'users', 'USR0003', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 21:50:03'),
(75, NULL, 'LOGIN', 'users', 'USR0003', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 21:53:51'),
(76, NULL, 'LOGOUT', 'users', 'USR0003', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 22:47:11'),
(77, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-21 22:47:19'),
(78, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-22 03:13:27'),
(79, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-22 03:13:38'),
(80, NULL, 'LOGIN', 'users', 'USR0003', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-22 03:13:44'),
(81, NULL, 'LOGIN', 'users', 'USR0003', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-22 03:18:43'),
(82, NULL, 'LOGOUT', 'users', 'USR0003', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-22 03:21:16'),
(83, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-22 03:21:26'),
(84, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-22 03:22:04'),
(85, NULL, 'LOGIN', 'users', 'USR0003', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-22 03:23:21'),
(86, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-22 03:23:34'),
(87, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-22 03:23:40'),
(88, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-22 03:24:37'),
(89, NULL, 'LOGIN', 'users', 'USR0003', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-22 05:49:12'),
(90, NULL, 'LOGOUT', 'users', 'USR0003', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-22 05:55:12'),
(91, NULL, 'LOGIN', 'users', 'USR0003', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-22 05:57:53'),
(92, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-22 10:37:11'),
(93, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-22 11:02:35'),
(94, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 10:14:24'),
(95, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 10:22:59'),
(96, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 10:24:10'),
(97, NULL, 'LOGIN', 'users', 'USR0002', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 10:24:56'),
(98, NULL, 'LOGOUT', 'users', 'USR0002', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 10:25:23'),
(99, NULL, 'LOGIN', 'users', 'USR0003', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 10:26:16'),
(100, NULL, 'LOGOUT', 'users', 'USR0003', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 10:51:52'),
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
(111, NULL, 'LOGIN', 'users', 'USR0002', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 11:07:05'),
(112, NULL, 'LOGOUT', 'users', 'USR0002', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 11:11:47'),
(113, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 11:11:52'),
(114, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-08 14:23:08'),
(115, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 16:04:47'),
(116, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-13 18:18:13'),
(117, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-13 18:25:01'),
(118, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-13 18:28:12'),
(119, NULL, 'LOGIN', 'users', 'USR0002', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-13 18:28:19'),
(120, NULL, 'LOGOUT', 'users', 'USR0002', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-13 18:28:47'),
(121, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-13 18:29:05'),
(122, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-13 18:29:14'),
(123, NULL, 'LOGIN', 'users', 'USR0002', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-13 18:29:21'),
(124, NULL, 'LOGOUT', 'users', 'USR0002', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-13 19:48:08'),
(125, NULL, 'LOGIN', 'users', 'USR0003', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-13 19:48:18'),
(126, NULL, 'LOGOUT', 'users', 'USR0003', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-13 23:25:19'),
(127, NULL, 'LOGIN', 'users', 'USR0002', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-13 23:25:25'),
(128, NULL, 'LOGOUT', 'users', 'USR0002', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-13 23:57:39'),
(129, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-13 23:57:44'),
(130, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-14 00:00:41'),
(131, NULL, 'LOGIN', 'users', 'USR0002', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-14 00:00:49'),
(132, NULL, 'LOGOUT', 'users', 'USR0002', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-14 00:01:07'),
(133, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-14 00:01:12'),
(134, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-14 00:01:37'),
(135, NULL, 'LOGIN', 'users', 'USR0002', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-14 00:01:43'),
(136, NULL, 'LOGOUT', 'users', 'USR0002', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-14 00:12:54'),
(137, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-14 00:24:20'),
(138, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-14 00:27:45'),
(139, 'USR0004', 'LOGIN', 'users', 'USR0004', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-14 00:28:00'),
(140, 'USR0004', 'LOGOUT', 'users', 'USR0004', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-14 00:28:21'),
(141, NULL, 'LOGIN', 'users', 'USR0003', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-14 00:28:31'),
(142, NULL, 'LOGOUT', 'users', 'USR0003', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-14 00:28:51'),
(143, 'USR0004', 'LOGIN', 'users', 'USR0004', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-14 00:28:55'),
(144, 'USR0004', 'LOGOUT', 'users', 'USR0004', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-14 00:31:08'),
(145, NULL, 'LOGIN', 'users', 'USR0003', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-14 00:31:14'),
(146, NULL, 'LOGOUT', 'users', 'USR0003', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-14 00:45:32'),
(147, 'USR0004', 'LOGIN', 'users', 'USR0004', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-14 00:45:36'),
(148, 'USR0004', 'LOGOUT', 'users', 'USR0004', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-14 00:47:01'),
(149, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-14 00:47:10'),
(150, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 14:17:15'),
(151, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 14:35:27'),
(152, 'USR0004', 'LOGIN', 'users', 'USR0004', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 14:35:29'),
(153, 'USR0004', 'LOGOUT', 'users', 'USR0004', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 14:39:32'),
(154, NULL, 'LOGIN', 'users', 'USR0003', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 14:39:37'),
(155, NULL, 'LOGOUT', 'users', 'USR0003', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 14:40:11'),
(156, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 14:40:18'),
(157, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 14:42:25'),
(158, 'USR0004', 'LOGIN', 'users', 'USR0004', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 14:42:27'),
(159, 'USR0004', 'LOGOUT', 'users', 'USR0004', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 14:44:30'),
(160, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 14:44:35'),
(161, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 15:01:17'),
(162, 'USR0004', 'LOGIN', 'users', 'USR0004', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 15:01:20'),
(163, 'USR0004', 'LOGIN', 'users', 'USR0004', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 15:10:16'),
(164, 'USR0004', 'LOGOUT', 'users', 'USR0004', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 15:18:23'),
(165, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 15:18:28'),
(166, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 15:19:38'),
(167, 'USR0004', 'LOGIN', 'users', 'USR0004', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 15:19:41'),
(168, 'USR0004', 'LOGOUT', 'users', 'USR0004', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 15:54:21'),
(169, NULL, 'LOGIN', 'users', 'USR0002', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 15:54:51'),
(170, NULL, 'LOGOUT', 'users', 'USR0002', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 15:59:56'),
(171, 'USR0004', 'LOGIN', 'users', 'USR0004', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 15:59:58'),
(172, 'USR0004', 'LOGOUT', 'users', 'USR0004', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 16:00:22'),
(173, 'USR0004', 'LOGIN', 'users', 'USR0004', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 16:00:24'),
(174, 'USR0004', 'LOGOUT', 'users', 'USR0004', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 16:00:28'),
(175, NULL, 'LOGIN', 'users', 'USR0002', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 16:00:35'),
(176, NULL, 'LOGOUT', 'users', 'USR0002', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 16:13:16'),
(177, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 16:13:22'),
(178, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 16:15:29'),
(179, NULL, 'LOGIN', 'users', 'USR0002', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 16:15:39'),
(180, NULL, 'LOGOUT', 'users', 'USR0002', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 17:02:08'),
(181, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 17:02:13'),
(182, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 21:40:59'),
(183, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 21:52:32'),
(184, NULL, 'LOGIN', 'users', 'USR0002', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 21:52:53'),
(185, NULL, 'LOGOUT', 'users', 'USR0002', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 21:55:10'),
(186, 'USR0004', 'LOGIN', 'users', 'USR0004', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 21:55:12'),
(187, 'USR0004', 'LOGOUT', 'users', 'USR0004', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 21:59:22'),
(188, 'USR0004', 'LOGIN', 'users', 'USR0004', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 21:59:24'),
(189, 'USR0004', 'LOGOUT', 'users', 'USR0004', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 21:59:27'),
(190, NULL, 'LOGIN', 'users', 'USR0003', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 21:59:33'),
(191, NULL, 'LOGOUT', 'users', 'USR0003', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 21:59:36'),
(192, 'USR0004', 'LOGIN', 'users', 'USR0004', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 21:59:42'),
(193, 'USR0004', 'LOGOUT', 'users', 'USR0004', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 22:02:57'),
(194, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 22:12:41'),
(195, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 22:15:23'),
(196, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 22:31:01'),
(197, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 23:54:50'),
(198, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 23:56:53'),
(199, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 23:57:31'),
(200, NULL, 'LOGIN', 'users', 'USR0005', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-19 23:57:43'),
(201, NULL, 'LOGOUT', 'users', 'USR0005', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 00:02:29'),
(202, NULL, 'LOGIN', 'users', 'USR0002', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 00:02:52'),
(203, NULL, 'LOGOUT', 'users', 'USR0002', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 00:05:57'),
(204, NULL, 'LOGIN', 'users', 'USR0005', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 00:06:08'),
(205, NULL, 'LOGOUT', 'users', 'USR0005', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 00:06:54'),
(206, 'USR0004', 'LOGIN', 'users', 'USR0004', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 00:07:37'),
(207, 'USR0004', 'LOGOUT', 'users', 'USR0004', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 00:08:00'),
(208, NULL, 'LOGIN', 'users', 'USR0005', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 00:08:10'),
(210, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 00:13:53'),
(211, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 00:14:58'),
(212, 'USR0005', 'LOGIN', 'users', 'USR0005', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 00:15:03'),
(213, 'USR0005', 'LOGOUT', 'users', 'USR0005', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 00:26:13'),
(214, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 00:28:12'),
(215, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 00:36:37'),
(216, NULL, 'LOGIN', 'users', 'USR0006', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 00:36:44'),
(217, NULL, 'LOGOUT', 'users', 'USR0006', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 00:37:02'),
(218, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 00:37:07'),
(219, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 01:20:43'),
(220, 'USR0005', 'LOGIN', 'users', 'USR0005', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 01:20:54'),
(221, 'USR0005', 'LOGOUT', 'users', 'USR0005', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 01:21:17'),
(222, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 02:37:40'),
(223, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 02:47:54'),
(224, NULL, 'LOGIN', 'users', 'USR0007', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 02:48:05'),
(225, NULL, 'LOGOUT', 'users', 'USR0007', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 02:48:10'),
(226, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 02:48:19'),
(227, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 03:07:52'),
(228, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 03:09:34'),
(229, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 03:09:52'),
(230, NULL, 'LOGIN', 'users', 'USR0007', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 03:09:58'),
(232, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 03:23:10'),
(233, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 03:38:37'),
(234, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 03:38:56'),
(235, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 04:00:27'),
(236, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 04:02:35'),
(237, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 05:34:37'),
(238, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 05:42:01'),
(239, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 05:49:42'),
(240, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 05:58:15'),
(241, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 05:58:36'),
(242, NULL, 'LOGIN', 'users', 'USR0008', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 05:58:44'),
(243, NULL, 'LOGOUT', 'users', 'USR0008', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 06:00:28'),
(244, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 06:00:33'),
(245, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 06:07:12'),
(246, 'USR0004', 'LOGIN', 'users', 'USR0004', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 06:07:39'),
(247, 'USR0004', 'LOGOUT', 'users', 'USR0004', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 06:12:25'),
(248, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-20 06:12:37'),
(249, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-31 20:23:40'),
(250, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-31 20:29:08'),
(251, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-31 20:29:13');
INSERT INTO `system_logs` (`log_id`, `user_id`, `action`, `table_name`, `record_id`, `old_values`, `new_values`, `ip_address`, `user_agent`, `created_at`) VALUES
(252, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-31 20:37:41'),
(253, 'USR0005', 'LOGIN', 'users', 'USR0005', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-31 20:37:46'),
(254, 'USR0005', 'LOGOUT', 'users', 'USR0005', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-31 20:38:08'),
(255, NULL, 'LOGIN', 'users', 'USR0002', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-31 20:38:14'),
(256, NULL, 'LOGOUT', 'users', 'USR0002', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-31 20:39:44'),
(257, 'USR0005', 'LOGIN', 'users', 'USR0005', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-31 20:39:50'),
(258, 'USR0005', 'LOGOUT', 'users', 'USR0005', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-31 21:00:59'),
(259, 'USR0005', 'LOGIN', 'users', 'USR0005', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-31 21:01:12'),
(260, 'USR0005', 'LOGIN', 'users', 'USR0005', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-31 21:08:13'),
(261, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 16:38:55'),
(262, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 18:07:30'),
(263, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 19:07:26'),
(264, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 19:40:03'),
(265, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 19:46:40'),
(266, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 20:14:06'),
(267, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 20:16:42'),
(268, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 20:29:55'),
(269, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 20:35:58'),
(270, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 20:55:16'),
(271, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 20:59:23'),
(272, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 12:10:17'),
(273, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 17:10:05'),
(274, 'USR0006', 'LOGIN', 'users', 'USR0006', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 17:10:17'),
(275, 'USR0006', 'LOGOUT', 'users', 'USR0006', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 17:36:16'),
(276, 'USR0006', 'LOGIN', 'users', 'USR0006', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 17:36:31'),
(277, 'USR0006', 'LOGIN', 'users', 'USR0006', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 18:42:15'),
(278, 'USR0006', 'LOGOUT', 'users', 'USR0006', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 18:44:11'),
(279, 'USR0004', 'LOGIN', 'users', 'USR0004', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 18:44:19'),
(280, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 18:44:40'),
(281, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 18:46:09'),
(282, 'USR0004', 'LOGIN', 'users', 'USR0004', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-04 18:46:30'),
(283, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 06:55:35'),
(284, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 07:01:42'),
(285, 'USR0006', 'LOGIN', 'users', 'USR0006', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 07:02:43'),
(286, 'USR0006', 'LOGOUT', 'users', 'USR0006', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 07:05:07'),
(287, 'USR0004', 'LOGIN', 'users', 'USR0004', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-05 07:05:19'),
(288, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-24 00:55:06'),
(289, 'USR0001', 'UPDATE', 'equipment', '15', '{\"equipment_name\":\"Dumbell\",\"total_quantity\":2,\"category\":\"\",\"purchase_date\":\"0000-00-00\",\"purchase_price\":\"0.00\",\"notes\":\"\"}', '{\"equipment_name\":\"Dumbell\",\"total_quantity\":3,\"category\":\"\",\"purchase_date\":\"0000-00-00\",\"purchase_price\":\"0.00\",\"notes\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-24 01:23:14'),
(290, 'USR0001', 'UPDATE', 'equipment', '15', '{\"equipment_name\":\"Dumbell\",\"total_quantity\":3,\"category\":\"\",\"purchase_date\":\"0000-00-00\",\"purchase_price\":\"0.00\",\"notes\":\"\"}', '{\"equipment_name\":\"Dumbell\",\"total_quantity\":2,\"category\":\"\",\"purchase_date\":\"0000-00-00\",\"purchase_price\":\"0.00\",\"notes\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-24 01:23:28'),
(291, 'USR0001', 'UPDATE', 'equipment', '15', '{\"equipment_name\":\"Dumbell\",\"total_quantity\":2,\"category\":\"\",\"purchase_date\":\"0000-00-00\",\"purchase_price\":\"0.00\",\"notes\":\"\"}', '{\"equipment_name\":\"Dumbells\",\"total_quantity\":2,\"category\":\"\",\"purchase_date\":\"0000-00-00\",\"purchase_price\":\"0.00\",\"notes\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-24 01:23:51'),
(292, 'USR0001', 'UPDATE', 'equipment', '16', '{\"equipment_name\":\"Barbell Plate\",\"total_quantity\":2,\"category\":\"\",\"purchase_date\":\"0000-00-00\",\"purchase_price\":\"0.00\",\"notes\":\"\"}', '{\"equipment_name\":\"Barbell Plate\",\"total_quantity\":12,\"category\":\"\",\"purchase_date\":\"0000-00-00\",\"purchase_price\":\"0.00\",\"notes\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-24 01:30:13'),
(293, 'USR0001', 'UPDATE', 'equipment', '16', '{\"equipment_name\":\"Barbell Plate\",\"total_quantity\":12,\"category\":\"\",\"purchase_date\":\"0000-00-00\",\"purchase_price\":\"0.00\",\"notes\":\"\"}', '{\"equipment_name\":\"Barbell Plates\",\"total_quantity\":2,\"category\":\"\",\"purchase_date\":\"0000-00-00\",\"purchase_price\":\"0.00\",\"notes\":\"\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-24 01:30:37'),
(294, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-24 01:30:56'),
(295, 'USR0006', 'LOGIN', 'users', 'USR0006', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-24 01:31:04'),
(296, 'USR0006', 'LOGOUT', 'users', 'USR0006', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-24 02:37:24'),
(297, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-24 02:38:01'),
(298, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-24 02:38:22'),
(299, 'USR0006', 'LOGIN', 'users', 'USR0006', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-24 02:38:33'),
(300, 'USR0006', 'LOGOUT', 'users', 'USR0006', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-24 02:51:15'),
(301, 'USR0006', 'LOGOUT', 'users', 'USR0006', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-24 02:51:15'),
(302, 'USR0004', 'LOGIN', 'users', 'USR0004', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-24 02:51:44'),
(303, 'USR0004', 'LOGOUT', 'users', 'USR0004', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-24 02:52:42'),
(304, 'USR0006', 'LOGIN', 'users', 'USR0006', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-24 02:52:49'),
(305, 'USR0006', 'LOGOUT', 'users', 'USR0006', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-24 02:53:23'),
(306, 'USR0004', 'LOGIN', 'users', 'USR0004', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-24 02:53:34'),
(307, 'USR0006', 'LOGIN', 'users', 'USR0006', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-24 03:19:21'),
(308, 'USR0006', 'LOGOUT', 'users', 'USR0006', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-24 03:24:33'),
(309, 'USR0004', 'LOGIN', 'users', 'USR0004', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-24 03:24:49'),
(310, 'USR0004', 'LOGOUT', 'users', 'USR0004', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-24 03:26:39'),
(311, 'USR0006', 'LOGIN', 'users', 'USR0006', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-24 03:26:48'),
(312, 'USR0006', 'LOGOUT', 'users', 'USR0006', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-24 03:37:39'),
(313, 'USR0004', 'LOGIN', 'users', 'USR0004', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-24 03:37:46'),
(314, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-24 08:21:58'),
(315, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-24 08:28:10'),
(316, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-24 08:28:54'),
(317, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-03 02:59:26'),
(318, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-03 03:04:39'),
(319, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-03 03:12:51'),
(320, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-03 03:13:36'),
(321, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-03 03:13:47'),
(322, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-03 03:17:22'),
(323, 'USR0006', 'LOGIN', 'users', 'USR0006', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-16 07:19:36'),
(324, 'USR0006', 'LOGIN', 'users', 'USR0006', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-16 07:23:53'),
(325, 'USR0005', 'LOGIN', 'users', 'USR0005', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-17 08:54:29'),
(326, 'USR0005', 'LOGOUT', 'users', 'USR0005', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-17 08:59:22'),
(327, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-17 08:59:34'),
(328, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-17 09:08:52'),
(329, 'USR0006', 'LOGIN', 'users', 'USR0006', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-17 09:08:59'),
(330, 'USR0006', 'LOGOUT', 'users', 'USR0006', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-17 09:16:50'),
(331, 'USR0004', 'LOGIN', 'users', 'USR0004', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-17 09:17:09'),
(332, 'USR0006', 'LOGIN', 'users', 'USR0006', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-30 06:38:59'),
(333, 'USR0006', 'LOGOUT', 'users', 'USR0006', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-30 06:44:20'),
(334, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-30 06:44:29'),
(335, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-30 07:05:52'),
(336, 'USR0006', 'LOGIN', 'users', 'USR0006', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-09 06:50:25'),
(337, 'USR0006', 'LOGOUT', 'users', 'USR0006', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-09 06:50:32'),
(338, 'USR0004', 'LOGIN', 'users', 'USR0004', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-09 06:51:27'),
(339, 'USR0004', 'LOGOUT', 'users', 'USR0004', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-09 06:51:34'),
(340, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-09 06:51:43'),
(341, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-09 06:53:50'),
(342, 'USR0006', 'LOGIN', 'users', 'USR0006', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-09 06:53:57'),
(343, 'USR0006', 'LOGOUT', 'users', 'USR0006', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-09 07:03:09'),
(344, 'USR0001', 'LOGIN', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-09 07:03:44'),
(345, 'USR0001', 'LOGOUT', 'users', 'USR0001', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-09 07:14:24'),
(346, 'USR0006', 'LOGIN', 'users', 'USR0006', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-09 07:14:45'),
(347, 'USR0006', 'LOGOUT', 'users', 'USR0006', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-09 07:14:57');

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
  `profile_picture` varchar(255) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `hire_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `username` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `trainers`
--

INSERT INTO `trainers` (`trainer_id`, `first_name`, `middle_name`, `last_name`, `gender`, `address`, `date_of_birth`, `specialization`, `profile_picture`, `contact_number`, `status`, `hire_date`, `username`, `email`) VALUES
('TRN0002', 'Rhoe', '', 'Rebadulla', 'Male', 'UEP Zone 2, Catarman, Northern Samar', '1974-07-10', 'Cardio', 'profile_6966e228cb04a.jpg', '09914571354', 'Active', '2026-01-13 16:00:00', 'rhoe_rebadulla', 'rhoerebadulla@gmail.com');

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
(2, 'TRN0001', 'MEM0001', 'Group Class', '2025-12-26', '00:57:00', '05:57:00', 'completed', 'k', '2025-12-21 16:57:25', '2026-01-13 20:13:47'),
(3, 'TRN0002', 'MEM0001', 'Personal Training', '2026-01-19', '10:00:00', '11:00:00', 'completed', '', '2026-01-19 15:01:04', '2026-01-19 15:02:46');

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
  `role` enum('staff','admin','trainer','member') NOT NULL DEFAULT 'member',
  `is_active` tinyint(1) DEFAULT 1,
  `pending_data` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `rfid_number` varchar(64) GENERATED ALWAYS AS (json_unquote(json_extract(`pending_data`,'$.rfid_number'))) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `email`, `full_name`, `role`, `is_active`, `pending_data`, `created_at`, `updated_at`) VALUES
('USR0001', 'staff', '$2y$10$JEAhhgjBOa4URJRW1iVazex63bh4Cgi.5kje6H/TNi1KZRHG9Pbfe', 'staff@uepgym.com', 'Gym Staff', 'staff', 1, NULL, '2025-09-18 17:10:24', '2026-01-31 20:37:29'),
('USR0004', 'rhoe_rebadulla', '$2y$10$3EsZWsKqPVnVQZ.dk12Lpu8CzNq9TgOUiKE5ZT1BStimgw5tdZFGy', 'rhoerebadulla@gmail.com', 'Rhoe Rebadulla', 'trainer', 1, NULL, '2026-01-14 00:24:08', '2026-01-14 00:27:06'),
('USR0005', 'admin', '$2y$10$NIJBY9NyWIaUqqi5zcks/uisgwUSmHqfZJcj9ymI03wGbKEZbvhyK', 'admin@gmail.com', 'System Administrator', 'admin', 1, NULL, '2026-01-20 00:13:43', '2026-01-31 20:37:34'),
('USR0006', 'stephwerd', '$2y$10$x8QTkHAeCEMLluFfPKheiezXBrSIzpRHARLScIPypdWU/IbySXwZ6', 'stephdestu@gmail.com', 'Stephanie Drew Flor Destura', 'member', 1, NULL, '2026-02-02 20:59:14', '2026-02-04 17:22:52');

-- --------------------------------------------------------

--
-- Table structure for table `vital_signs`
--

CREATE TABLE `vital_signs` (
  `record_id` varchar(10) NOT NULL,
  `member_id` varchar(10) NOT NULL,
  `date_of_recording` date NOT NULL,
  `height_cm` decimal(5,2) DEFAULT NULL,
  `heart_rate` int(11) DEFAULT NULL,
  `blood_pressure_systolic` int(11) DEFAULT NULL,
  `blood_pressure_diastolic` int(11) DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `bmi` decimal(4,2) DEFAULT NULL,
  `body_fat_percentage` decimal(5,2) DEFAULT NULL,
  `waist_circumference` decimal(5,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `trainer_id` varchar(10) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vital_signs`
--

INSERT INTO `vital_signs` (`record_id`, `member_id`, `date_of_recording`, `height_cm`, `heart_rate`, `blood_pressure_systolic`, `blood_pressure_diastolic`, `weight`, `bmi`, `body_fat_percentage`, `waist_circumference`, `notes`, `trainer_id`, `created_at`) VALUES
('VS_69835bd', 'STU0001', '2026-02-04', 157.00, 60, 120, 80, 69.00, 27.99, NULL, NULL, NULL, 'USR0001', '2026-02-04 14:46:53'),
('VS_699cfa5', 'STU0001', '2026-02-24', 160.00, 70, 120, 80, 69.00, 26.95, NULL, NULL, NULL, 'USR0001', '2026-02-24 01:09:47'),
('VS_69b918f', 'STU0001', '2026-03-17', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'USR0001', '2026-03-17 09:03:45');

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
  ADD PRIMARY KEY (`maintenance_id`),
  ADD KEY `fk_maintenance_equipment` (`equipment_id`),
  ADD KEY `fk_maintenance_unit` (`unit_id`);

--
-- Indexes for table `equipment_units`
--
ALTER TABLE `equipment_units`
  ADD PRIMARY KEY (`unit_id`),
  ADD UNIQUE KEY `unique_equipment_unit` (`equipment_id`,`unit_number`);

--
-- Indexes for table `exercise_completion`
--
ALTER TABLE `exercise_completion`
  ADD PRIMARY KEY (`completion_id`),
  ADD UNIQUE KEY `unique_session_exercise` (`session_id`,`exercise_index`);

--
-- Indexes for table `members`
--
ALTER TABLE `members`
  ADD PRIMARY KEY (`member_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `rfid_card_number` (`rfid_card_number`),
  ADD KEY `idx_rfid` (`rfid_card_number`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_member_type` (`member_type`);

--
-- Indexes for table `membership_plans`
--
ALTER TABLE `membership_plans`
  ADD PRIMARY KEY (`plan_id`);

--
-- Indexes for table `member_fitness_goals`
--
ALTER TABLE `member_fitness_goals`
  ADD PRIMARY KEY (`goal_id`),
  ADD UNIQUE KEY `unique_member_goal` (`member_id`);

--
-- Indexes for table `member_program_daily_completion`
--
ALTER TABLE `member_program_daily_completion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `program_history_id` (`program_history_id`);

--
-- Indexes for table `member_program_history`
--
ALTER TABLE `member_program_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `member_id` (`member_id`);

--
-- Indexes for table `member_progress`
--
ALTER TABLE `member_progress`
  ADD PRIMARY KEY (`progress_id`),
  ADD KEY `member_id` (`member_id`);

--
-- Indexes for table `member_training_sessions`
--
ALTER TABLE `member_training_sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `idx_member_date` (`member_id`,`session_date`),
  ADD KEY `idx_trainer_date` (`trainer_id`,`session_date`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `idx_billing` (`billing_id`),
  ADD KEY `idx_member` (`member_id`);

--
-- Indexes for table `progress`
--
ALTER TABLE `progress`
  ADD PRIMARY KEY (`progress_id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`staff_id`);

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
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_rfid` (`rfid_number`);

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
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `billing`
--
ALTER TABLE `billing`
  MODIFY `billing_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `equipment`
--
ALTER TABLE `equipment`
  MODIFY `equipment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `equipment_maintenance`
--
ALTER TABLE `equipment_maintenance`
  MODIFY `maintenance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `equipment_units`
--
ALTER TABLE `equipment_units`
  MODIFY `unit_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT for table `exercise_completion`
--
ALTER TABLE `exercise_completion`
  MODIFY `completion_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `membership_plans`
--
ALTER TABLE `membership_plans`
  MODIFY `plan_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `member_fitness_goals`
--
ALTER TABLE `member_fitness_goals`
  MODIFY `goal_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `member_program_daily_completion`
--
ALTER TABLE `member_program_daily_completion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `member_program_history`
--
ALTER TABLE `member_program_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `member_progress`
--
ALTER TABLE `member_progress`
  MODIFY `progress_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `member_training_sessions`
--
ALTER TABLE `member_training_sessions`
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `progress`
--
ALTER TABLE `progress`
  MODIFY `progress_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `system_logs`
--
ALTER TABLE `system_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=348;

--
-- AUTO_INCREMENT for table `trainer_assignments`
--
ALTER TABLE `trainer_assignments`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
-- Constraints for table `equipment_maintenance`
--
ALTER TABLE `equipment_maintenance`
  ADD CONSTRAINT `fk_maintenance_equipment` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`equipment_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_maintenance_unit` FOREIGN KEY (`unit_id`) REFERENCES `equipment_units` (`unit_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `equipment_units`
--
ALTER TABLE `equipment_units`
  ADD CONSTRAINT `fk_unit_equipment` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`equipment_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `exercise_completion`
--
ALTER TABLE `exercise_completion`
  ADD CONSTRAINT `exercise_completion_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `member_training_sessions` (`session_id`) ON DELETE CASCADE;

--
-- Constraints for table `members`
--
ALTER TABLE `members`
  ADD CONSTRAINT `fk_members_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_members_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `member_fitness_goals`
--
ALTER TABLE `member_fitness_goals`
  ADD CONSTRAINT `member_fitness_goals_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`member_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `member_program_daily_completion`
--
ALTER TABLE `member_program_daily_completion`
  ADD CONSTRAINT `member_program_daily_completion_ibfk_1` FOREIGN KEY (`program_history_id`) REFERENCES `member_program_history` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `member_program_history`
--
ALTER TABLE `member_program_history`
  ADD CONSTRAINT `member_program_history_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`member_id`) ON DELETE CASCADE;

--
-- Constraints for table `member_progress`
--
ALTER TABLE `member_progress`
  ADD CONSTRAINT `member_progress_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`member_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payments_billing` FOREIGN KEY (`billing_id`) REFERENCES `billing` (`billing_id`) ON DELETE CASCADE;

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
  ADD CONSTRAINT `fk_vitals_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`member_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `vital_signs_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`member_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
