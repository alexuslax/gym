-- Create table for member program history
CREATE TABLE IF NOT EXISTS member_program_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id VARCHAR(10) NOT NULL,
    fitness_goal ENUM('weight_loss', 'muscle_gain', 'endurance', 'general_fitness') NOT NULL,
    program_name VARCHAR(255) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    days_completed INT DEFAULT 0,
    total_days INT DEFAULT 7,
    generated_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create table for tracking daily completion
CREATE TABLE IF NOT EXISTS member_program_daily_completion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_history_id INT NOT NULL,
    day_name VARCHAR(20) NOT NULL,
    completed BOOLEAN DEFAULT FALSE,
    completed_date TIMESTAMP NULL,
    FOREIGN KEY (program_history_id) REFERENCES member_program_history(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
