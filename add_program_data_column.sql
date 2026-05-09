-- Add program_data column to member_fitness_goals table
ALTER TABLE member_fitness_goals 
ADD COLUMN program_data TEXT DEFAULT NULL
AFTER fitness_goal;
