-- Create table for tracking exercise completion status
CREATE TABLE IF NOT EXISTS exercise_completion (
    completion_id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    exercise_index INT NOT NULL,
    exercise_name VARCHAR(255) NOT NULL,
    is_completed BOOLEAN DEFAULT FALSE,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES member_training_sessions(session_id) ON DELETE CASCADE,
    UNIQUE KEY unique_session_exercise (session_id, exercise_index)
);
