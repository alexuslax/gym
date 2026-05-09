-- Add member_fitness_goals table to store selected fitness goals
CREATE TABLE IF NOT EXISTS `member_fitness_goals` (
  `goal_id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` varchar(10) NOT NULL,
  `fitness_goal` enum('weight_loss','muscle_gain','endurance','general_fitness') NOT NULL,
  `selected_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`goal_id`),
  FOREIGN KEY (`member_id`) REFERENCES `members` (`member_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  UNIQUE KEY `unique_member_goal` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
