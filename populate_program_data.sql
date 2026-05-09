-- Update existing member_fitness_goals with program_data
-- This populates the program_data for members who already selected a fitness goal

-- Note: You may need to run this through a PHP script instead since we need the program templates
-- For now, this creates NULL entries that will be populated when members regenerate their programs
UPDATE member_fitness_goals 
SET program_data = NULL 
WHERE program_data IS NULL;
