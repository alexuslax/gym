-- Add member_training_sessions table to track member-trainer session bookings
CREATE TABLE IF NOT EXISTS `member_training_sessions` (
  `session_id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` varchar(10) NOT NULL,
  `trainer_id` varchar(10) NOT NULL,
  `session_date` date NOT NULL,
  `session_time` time NOT NULL,
  `session_duration` int(11) DEFAULT 60,
  `status` enum('scheduled','ongoing','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`session_id`),
  KEY `idx_member_date` (`member_id`, `session_date`),
  KEY `idx_trainer_date` (`trainer_id`, `session_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
