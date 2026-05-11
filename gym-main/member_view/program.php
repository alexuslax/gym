<?php
require_once '../config/functions.php';

// Get member_id from session
$member_id = null;
if (isset($_SESSION['member_id'])) {
    $member_id = $_SESSION['member_id'];
} elseif (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare('SELECT member_id FROM members WHERE user_id = ? LIMIT 1');
    $stmt->execute([$_SESSION['user_id']]);
    $member = $stmt->fetch();
    $member_id = $member['member_id'] ?? null;
}

// Define program templates based on goals
$programs = [
        'weight_loss' => [
            'name' => 'Weight Loss & Fat Burning',
            'description' => 'High-intensity cardio combined with strength training to maximize calorie burn and boost metabolism.',
            'weekly_plan' => [
                'Monday' => [
                    ['exercise' => 'Treadmill Running', 'sets' => '1', 'reps' => '30 min', 'rest' => '60s', 'notes' => 'Moderate to high intensity intervals'],
                    ['exercise' => 'Dumbbell Thrusters', 'sets' => '3', 'reps' => '15', 'rest' => '60s', 'notes' => 'Full body compound movement'],
                    ['exercise' => 'Bench Step-ups', 'sets' => '3', 'reps' => '12 each leg', 'rest' => '60s', 'notes' => 'Hold dumbbells for added resistance'],
                    ['exercise' => 'Lat Machine Pulldowns', 'sets' => '3', 'reps' => '15', 'rest' => '45s', 'notes' => 'Quick tempo for fat burning'],
                ],
                'Tuesday' => [
                    ['exercise' => 'Stationary Bike', 'sets' => '1', 'reps' => '35 min', 'rest' => '-', 'notes' => 'HIIT intervals (30s sprint, 90s recovery)'],
                    ['exercise' => 'Vertical Leg Press', 'sets' => '4', 'reps' => '15', 'rest' => '60s', 'notes' => 'Light to moderate weight, high reps'],
                    ['exercise' => 'Dumbbell Squat to Press', 'sets' => '3', 'reps' => '12', 'rest' => '60s', 'notes' => 'Combines lower & upper body'],
                    ['exercise' => 'Handle Bar Bicep Curls', 'sets' => '3', 'reps' => '15', 'rest' => '45s', 'notes' => 'Controlled movement'],
                ],
                'Wednesday' => [
                    ['exercise' => 'Treadmill Incline Walking', 'sets' => '1', 'reps' => '40 min', 'rest' => '-', 'notes' => 'Steady state, high incline'],
                    ['exercise' => 'Bench Press (Barbell)', 'sets' => '3', 'reps' => '15', 'rest' => '60s', 'notes' => 'Moderate weight, higher reps'],
                    ['exercise' => 'Dumbbell Rows', 'sets' => '3', 'reps' => '12 each arm', 'rest' => '45s', 'notes' => 'Back engagement'],
                    ['exercise' => 'Dumbbell Russian Twists', 'sets' => '3', 'reps' => '20', 'rest' => '45s', 'notes' => 'Core rotation'],
                ],
                'Thursday' => [
                    ['exercise' => 'Stationary Bike', 'sets' => '1', 'reps' => '30 min', 'rest' => '-', 'notes' => 'Varying resistance levels'],
                    ['exercise' => 'Dumbbell Lunges', 'sets' => '3', 'reps' => '15 each leg', 'rest' => '60s', 'notes' => 'Alternating legs'],
                    ['exercise' => 'Lat Machine Rows', 'sets' => '3', 'reps' => '15', 'rest' => '45s', 'notes' => 'Seated cable rows'],
                    ['exercise' => 'Bench Decline Sit-ups', 'sets' => '3', 'reps' => '20', 'rest' => '45s', 'notes' => 'Hold dumbbell for resistance'],
                ],
                'Friday' => [
                    ['exercise' => 'Treadmill Sprint Intervals', 'sets' => '8', 'reps' => '1 min sprint', 'rest' => '90s', 'notes' => 'High intensity bursts'],
                    ['exercise' => 'Vertical Leg Press', 'sets' => '3', 'reps' => '20', 'rest' => '60s', 'notes' => 'Light weight, high volume'],
                    ['exercise' => 'Dumbbell Shoulder Press', 'sets' => '3', 'reps' => '12', 'rest' => '60s', 'notes' => 'Burn shoulder calories'],
                    ['exercise' => 'Lat Machine Pull-downs', 'sets' => '3', 'reps' => '15', 'rest' => '45s', 'notes' => 'Wide grip'],
                ],
                'Saturday' => [
                    ['exercise' => 'Stationary Bike', 'sets' => '1', 'reps' => '45 min', 'rest' => '-', 'notes' => 'Low intensity recovery ride'],
                    ['exercise' => 'Dumbbell Circuit', 'sets' => '2', 'reps' => '10 each exercise', 'rest' => '120s', 'notes' => 'Full body light weights'],
                ],
                'Sunday' => [
                    ['exercise' => 'Rest Day', 'sets' => '-', 'reps' => '-', 'rest' => '-', 'notes' => 'Recovery & meal prep'],
                ],
            ]
        ],
        'muscle_gain' => [
            'name' => 'Muscle Building & Strength',
            'description' => 'Progressive overload strength training focused on hypertrophy and muscle development.',
            'weekly_plan' => [
                'Monday' => [
                    ['exercise' => 'Barbell Bench Press', 'sets' => '4', 'reps' => '8-10', 'rest' => '120s', 'notes' => 'Heavy barbell plates, progressive overload'],
                    ['exercise' => 'Incline Dumbbell Press (Bench)', 'sets' => '4', 'reps' => '10-12', 'rest' => '90s', 'notes' => 'Adjust bench to incline, upper chest focus'],
                    ['exercise' => 'Dumbbell Flyes (Bench)', 'sets' => '3', 'reps' => '12', 'rest' => '60s', 'notes' => 'Chest stretch and squeeze'],
                    ['exercise' => 'Handle Bar Tricep Extensions', 'sets' => '3', 'reps' => '10-12', 'rest' => '90s', 'notes' => 'Overhead cable extension'],
                    ['exercise' => 'Dumbbell Kickbacks', 'sets' => '3', 'reps' => '12', 'rest' => '60s', 'notes' => 'Tricep isolation'],
                ],
                'Tuesday' => [
                    ['exercise' => 'Barbell Deadlift', 'sets' => '4', 'reps' => '6-8', 'rest' => '180s', 'notes' => 'Heavy barbell plates, compound lift'],
                    ['exercise' => 'Lat Machine Pull-downs (Wide Grip)', 'sets' => '4', 'reps' => '10-12', 'rest' => '90s', 'notes' => 'Back width development'],
                    ['exercise' => 'Lat Machine Rows (Seated)', 'sets' => '4', 'reps' => '10', 'rest' => '90s', 'notes' => 'Back thickness'],
                    ['exercise' => 'Handle Bar Bicep Curls', 'sets' => '4', 'reps' => '10-12', 'rest' => '60s', 'notes' => 'Cable curls for constant tension'],
                    ['exercise' => 'Dumbbell Hammer Curls', 'sets' => '3', 'reps' => '12', 'rest' => '60s', 'notes' => 'Bicep & forearm focus'],
                ],
                'Wednesday' => [
                    ['exercise' => 'Stationary Bike (Light)', 'sets' => '1', 'reps' => '20 min', 'rest' => '-', 'notes' => 'Active recovery, low resistance'],
                    ['exercise' => 'Stretching', 'sets' => '1', 'reps' => '15 min', 'rest' => '-', 'notes' => 'Flexibility work'],
                ],
                'Thursday' => [
                    ['exercise' => 'Barbell Squats', 'sets' => '4', 'reps' => '8-10', 'rest' => '180s', 'notes' => 'Heavy barbell plates, quad focus'],
                    ['exercise' => 'Vertical Leg Press', 'sets' => '4', 'reps' => '10-12', 'rest' => '90s', 'notes' => 'Heavy weight, quad development'],
                    ['exercise' => 'Dumbbell Romanian Deadlifts', 'sets' => '3', 'reps' => '10-12', 'rest' => '90s', 'notes' => 'Hamstring & glute focus'],
                    ['exercise' => 'Dumbbell Calf Raises', 'sets' => '4', 'reps' => '15-20', 'rest' => '60s', 'notes' => 'Full extension, hold dumbbells'],
                ],
                'Friday' => [
                    ['exercise' => 'Dumbbell Shoulder Press (Bench)', 'sets' => '4', 'reps' => '8-10', 'rest' => '120s', 'notes' => 'Seated on bench, shoulder mass builder'],
                    ['exercise' => 'Dumbbell Lateral Raises', 'sets' => '4', 'reps' => '12-15', 'rest' => '60s', 'notes' => 'Side deltoid isolation'],
                    ['exercise' => 'Dumbbell Front Raises', 'sets' => '3', 'reps' => '12', 'rest' => '60s', 'notes' => 'Front deltoid focus'],
                    ['exercise' => 'Lat Machine Face Pulls', 'sets' => '3', 'reps' => '15', 'rest' => '60s', 'notes' => 'Rear delts & upper back'],
                    ['exercise' => 'Dumbbell Shrugs', 'sets' => '3', 'reps' => '12', 'rest' => '60s', 'notes' => 'Trap development, heavy dumbbells'],
                ],
                'Saturday' => [
                    ['exercise' => 'Full Body Dumbbell Circuit', 'sets' => '3', 'reps' => '10 each', 'rest' => '120s', 'notes' => 'Moderate weight, muscle endurance'],
                    ['exercise' => 'Bench Decline Sit-ups', 'sets' => '3', 'reps' => '15-20', 'rest' => '60s', 'notes' => 'Core strength, hold dumbbell'],
                ],
                'Sunday' => [
                    ['exercise' => 'Rest Day', 'sets' => '-', 'reps' => '-', 'rest' => '-', 'notes' => 'Muscle recovery & growth'],
                ],
            ]
        ],
        'endurance' => [
            'name' => 'Endurance & Stamina Building',
            'description' => 'Cardiovascular conditioning and muscular endurance for improved performance and stamina.',
            'weekly_plan' => [
                'Monday' => [
                    ['exercise' => 'Treadmill Long Run', 'sets' => '1', 'reps' => '45 min', 'rest' => '-', 'notes' => 'Steady pace, build distance gradually'],
                    ['exercise' => 'Dumbbell Squats', 'sets' => '3', 'reps' => '25', 'rest' => '45s', 'notes' => 'Light weight, leg endurance'],
                    ['exercise' => 'Bench Plank Hold', 'sets' => '3', 'reps' => '90s', 'rest' => '60s', 'notes' => 'Hands on bench, core stability'],
                ],
                'Tuesday' => [
                    ['exercise' => 'Stationary Bike Intervals', 'sets' => '8', 'reps' => '2 min high / 2 min low', 'rest' => '60s', 'notes' => 'Interval training for stamina'],
                    ['exercise' => 'Bench Push-ups', 'sets' => '4', 'reps' => '20', 'rest' => '45s', 'notes' => 'Elevated or standard, upper body endurance'],
                    ['exercise' => 'Lat Machine Pull-downs', 'sets' => '3', 'reps' => 'Max reps', 'rest' => '90s', 'notes' => 'Light weight to failure'],
                ],
                'Wednesday' => [
                    ['exercise' => 'Stationary Bike', 'sets' => '1', 'reps' => '60 min', 'rest' => '-', 'notes' => 'Moderate to high intensity, steady state'],
                    ['exercise' => 'Dumbbell Walking Lunges', 'sets' => '3', 'reps' => '20 each leg', 'rest' => '60s', 'notes' => 'Light dumbbells for stamina'],
                ],
                'Thursday' => [
                    ['exercise' => 'Treadmill Hill Intervals', 'sets' => '10', 'reps' => '2 min incline / 1 min flat', 'rest' => '-', 'notes' => 'High intensity intervals'],
                    ['exercise' => 'Vertical Leg Press', 'sets' => '4', 'reps' => '20', 'rest' => '60s', 'notes' => 'Light weight, high reps for endurance'],
                    ['exercise' => 'Lat Machine Rows', 'sets' => '4', 'reps' => '15', 'rest' => '45s', 'notes' => 'Quick tempo'],
                ],
                'Friday' => [
                    ['exercise' => 'Treadmill Sprint Intervals', 'sets' => '10', 'reps' => '1 min sprint', 'rest' => '120s', 'notes' => 'Sprint intervals for power'],
                    ['exercise' => 'Bench Step-up Jumps', 'sets' => '3', 'reps' => '15', 'rest' => '90s', 'notes' => 'Explosive power, both legs'],
                ],
                'Saturday' => [
                    ['exercise' => 'Stationary Bike Long Ride', 'sets' => '1', 'reps' => '90 min', 'rest' => '-', 'notes' => 'Steady state endurance ride'],
                    ['exercise' => 'Dumbbell Core Circuit', 'sets' => '3', 'reps' => '20 each', 'rest' => '60s', 'notes' => 'Russian twists, wood chops, etc.'],
                ],
                'Sunday' => [
                    ['exercise' => 'Treadmill Active Recovery Walk', 'sets' => '1', 'reps' => '30 min', 'rest' => '-', 'notes' => 'Light pace, flexibility & recovery'],
                ],
            ]
        ],
        'general_fitness' => [
            'name' => 'General Fitness & Wellness',
            'description' => 'Balanced program combining strength, cardio, and flexibility for overall health and fitness.',
            'weekly_plan' => [
                'Monday' => [
                    ['exercise' => 'Treadmill Brisk Walking/Jogging', 'sets' => '1', 'reps' => '30 min', 'rest' => '-', 'notes' => 'Warm-up pace, moderate intensity'],
                    ['exercise' => 'Bench Push-ups', 'sets' => '3', 'reps' => '12-15', 'rest' => '60s', 'notes' => 'Incline or decline bench position'],
                    ['exercise' => 'Dumbbell Squats', 'sets' => '3', 'reps' => '15', 'rest' => '60s', 'notes' => 'Light to moderate dumbbells'],
                    ['exercise' => 'Bench Plank Hold', 'sets' => '3', 'reps' => '45s', 'rest' => '60s', 'notes' => 'Core strength, hands on bench'],
                ],
                'Tuesday' => [
                    ['exercise' => 'Stationary Bike', 'sets' => '1', 'reps' => '35 min', 'rest' => '-', 'notes' => 'Moderate intensity, steady state'],
                    ['exercise' => 'Dumbbell Rows', 'sets' => '3', 'reps' => '12', 'rest' => '60s', 'notes' => 'Light to moderate weight, each arm'],
                    ['exercise' => 'Dumbbell Shoulder Press', 'sets' => '3', 'reps' => '12', 'rest' => '60s', 'notes' => 'Seated on bench'],
                    ['exercise' => 'Handle Bar Bicep Curls', 'sets' => '3', 'reps' => '12', 'rest' => '45s', 'notes' => 'Controlled movement, cable curls'],
                ],
                'Wednesday' => [
                    ['exercise' => 'Stretching & Mobility', 'sets' => '1', 'reps' => '40 min', 'rest' => '-', 'notes' => 'Flexibility & balance exercises'],
                    ['exercise' => 'Treadmill Light Walk', 'sets' => '1', 'reps' => '15 min', 'rest' => '-', 'notes' => 'Active recovery'],
                ],
                'Thursday' => [
                    ['exercise' => 'Stationary Bike', 'sets' => '1', 'reps' => '30 min', 'rest' => '-', 'notes' => 'Continuous steady pace'],
                    ['exercise' => 'Dumbbell Lunges', 'sets' => '3', 'reps' => '12 each leg', 'rest' => '60s', 'notes' => 'Alternating legs, light dumbbells'],
                    ['exercise' => 'Vertical Leg Press', 'sets' => '3', 'reps' => '12', 'rest' => '60s', 'notes' => 'Moderate weight'],
                    ['exercise' => 'Dumbbell Calf Raises', 'sets' => '3', 'reps' => '15', 'rest' => '45s', 'notes' => 'Hold dumbbells at sides'],
                ],
                'Friday' => [
                    ['exercise' => 'Full Body Dumbbell Circuit', 'sets' => '3', 'reps' => '12 each', 'rest' => '90s', 'notes' => 'Squats, press, rows, curls'],
                    ['exercise' => 'Lat Machine Pull-downs', 'sets' => '3', 'reps' => '12', 'rest' => '60s', 'notes' => 'Back engagement'],
                    ['exercise' => 'Dumbbell Russian Twists', 'sets' => '3', 'reps' => '20', 'rest' => '45s', 'notes' => 'Core rotation with weight'],
                ],
                'Saturday' => [
                    ['exercise' => 'Treadmill + Bike Combo', 'sets' => '1', 'reps' => '30 min each', 'rest' => '-', 'notes' => 'Light cardio mix for active recovery'],
                ],
                'Sunday' => [
                    ['exercise' => 'Rest Day', 'sets' => '-', 'reps' => '-', 'rest' => '-', 'notes' => 'Recovery & relaxation'],
                ],
            ]
        ],
    ];

// Handle goal submission
$program_generated = false;
$selected_goal = null;
$weekly_program = [];
$saved_goal = null;
$current_program_id = null;
$latest_program_history = null;

// Get previously saved goal
if ($member_id) {
    $stmt = $pdo->prepare('SELECT fitness_goal FROM member_fitness_goals WHERE member_id = ? LIMIT 1');
    $stmt->execute([$member_id]);
    $goal_result = $stmt->fetch();
    if ($goal_result) {
        $saved_goal = $goal_result['fitness_goal'];
    }
    
    // Get the latest program history
    $stmt = $pdo->prepare('
        SELECT id, program_name, start_date, end_date, days_completed, total_days, generated_date, fitness_goal
        FROM member_program_history 
        WHERE member_id = ? 
        ORDER BY generated_date DESC 
        LIMIT 1
    ');
    $stmt->execute([$member_id]);
    $latest_program_history = $stmt->fetch();
    
    if ($latest_program_history) {
        $current_program_id = $latest_program_history['id'];
        
        // Get completion status for each day
        $stmt = $pdo->prepare('
            SELECT day_name, completed
            FROM member_program_daily_completion 
            WHERE program_history_id = ?
        ');
        $stmt->execute([$current_program_id]);
        $daily_completions = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
}

// Check if form was submitted or if there's a saved goal
if (($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fitness_goal'])) || ($saved_goal && !isset($_GET['change']))) {
    // Determine which goal to use
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fitness_goal'])) {
        $selected_goal = $_POST['fitness_goal'];
        
        // Save the selected goal to database
        if ($member_id) {
            // Get the weekly program data
            $program_data_json = json_encode($programs[$selected_goal]['weekly_plan']);
            
            $stmt = $pdo->prepare('
                INSERT INTO member_fitness_goals (member_id, fitness_goal, program_data) 
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    fitness_goal = VALUES(fitness_goal), 
                    program_data = VALUES(program_data),
                    selected_date = CURRENT_TIMESTAMP
            ');
            $stmt->execute([$member_id, $selected_goal, $program_data_json]);
            
            // Create program history record
            $program_name = $programs[$selected_goal]['name'];
            $chosen_date = new DateTimeImmutable('today');
            $days_from_monday = (int) $chosen_date->format('N') - 1;
            $week_start = $chosen_date->modify('-' . $days_from_monday . ' days');
            $week_end = $week_start->modify('+6 days');
            $start_date = $week_start->format('Y-m-d');
            $end_date = $week_end->format('Y-m-d');
            
            $stmt = $pdo->prepare('
                INSERT INTO member_program_history 
                (member_id, fitness_goal, program_name, start_date, end_date, total_days) 
                VALUES (?, ?, ?, ?, ?, 7)
            ');
            $stmt->execute([$member_id, $selected_goal, $program_name, $start_date, $end_date]);
            
            $program_history_id = $pdo->lastInsertId();
            
            // Create daily completion records for each day
            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            $stmt = $pdo->prepare('
                INSERT INTO member_program_daily_completion 
                (program_history_id, day_name) VALUES (?, ?)
            ');
            foreach ($days as $day) {
                $stmt->execute([$program_history_id, $day]);
            }
        }
    } else {
        // Use the saved goal
        $selected_goal = $saved_goal;
    }
    
    if (isset($programs[$selected_goal])) {
        $weekly_program = $programs[$selected_goal];
        $program_generated = true;
    }
}

$page_title = 'Fitness Program - UEP Fitness Gym';
include '../header.php';
?>
<style>
* {
  box-sizing: border-box;
}

body {
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
  min-height: 100vh;
}

.program-container {
  max-width: 1280px;
  margin: 0 auto;
  padding: 2rem 1rem;
}

.program-header {
  margin-bottom: 3rem;
  animation: slideIn 0.6s ease;
}

.program-title {
  font-size: 2.5rem;
  font-weight: 800;
  margin-bottom: 0.5rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  letter-spacing: -0.5px;
}

.program-subtitle {
  color: #64748b;
  font-size: 1.125rem;
  font-weight: 500;
}

.goal-selection-card {
  background: white;
  border-radius: 1.25rem;
  padding: 2.5rem;
  box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.8);
  margin-bottom: 3rem;
  animation: slideIn 0.6s ease 0.1s both;
}

.goal-section-title {
  font-size: 1.5rem;
  font-weight: 800;
  color: #0f172a;
  margin-bottom: 1.5rem;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.goal-section-title svg {
  width: 2rem;
  height: 2rem;
  color: #667eea;
}

.goal-description {
  color: #64748b;
  font-size: 1rem;
  margin-bottom: 2rem;
  line-height: 1.6;
}

.goal-options {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.goal-option {
  position: relative;
  cursor: pointer;
  transition: all 0.3s ease;
}

.goal-option input[type="radio"] {
  position: absolute;
  opacity: 0;
  cursor: pointer;
}

.goal-option-label {
  display: block;
  padding: 1.5rem;
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  border: 2px solid #e2e8f0;
  border-radius: 1rem;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.goal-option:hover .goal-option-label {
  background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
  border-color: #3b82f6;
  transform: translateY(-4px);
  box-shadow: 0 12px 24px -6px rgba(59, 130, 246, 0.15);
}

.goal-option input[type="radio"]:checked + .goal-option-label {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-color: #667eea;
  box-shadow: 0 12px 24px -6px rgba(102, 126, 234, 0.3);
}

.goal-option input[type="radio"]:checked + .goal-option-label .goal-option-icon {
  color: white;
}

.goal-option input[type="radio"]:checked + .goal-option-label .goal-option-title,
.goal-option input[type="radio"]:checked + .goal-option-label .goal-option-desc {
  color: white;
}

.goal-option-icon {
  width: 3rem;
  height: 3rem;
  margin: 0 auto 1rem;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #667eea;
  transition: all 0.3s ease;
}

.goal-option-icon svg {
  width: 100%;
  height: 100%;
  stroke-width: 1.5;
}

.goal-option-title {
  font-size: 1.125rem;
  font-weight: 700;
  color: #0f172a;
  margin-bottom: 0.5rem;
  text-align: center;
  transition: all 0.3s ease;
}

.goal-option-desc {
  font-size: 0.875rem;
  color: #64748b;
  text-align: center;
  line-height: 1.4;
  transition: all 0.3s ease;
}

.generate-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem 2.5rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 0.75rem;
  font-size: 1.125rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 10px 20px -5px rgba(102, 126, 234, 0.3);
}

.generate-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 15px 30px -8px rgba(102, 126, 234, 0.4);
}

.generate-btn:active {
  transform: translateY(0);
}

.generate-btn svg {
  width: 1.5rem;
  height: 1.5rem;
}

.program-overview {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 1.25rem;
  padding: 2.5rem;
  color: white;
  margin-bottom: 2rem;
  box-shadow: 0 20px 40px -10px rgba(102, 126, 234, 0.3);
  animation: slideIn 0.6s ease 0.2s both;
}

.program-overview h2 {
  font-size: 2rem;
  font-weight: 800;
  margin-bottom: 0.75rem;
}

.program-overview p {
  font-size: 1.125rem;
  opacity: 0.95;
  line-height: 1.6;
}

.weekly-schedule {
  display: grid;
  gap: 1.5rem;
}

.day-card {
  background: white;
  border-radius: 1.25rem;
  overflow: hidden;
  box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.8);
  transition: all 0.3s ease;
  animation: slideIn 0.6s ease both;
}

.day-card:nth-child(1) { animation-delay: 0.3s; }
.day-card:nth-child(2) { animation-delay: 0.35s; }
.day-card:nth-child(3) { animation-delay: 0.4s; }
.day-card:nth-child(4) { animation-delay: 0.45s; }
.day-card:nth-child(5) { animation-delay: 0.5s; }
.day-card:nth-child(6) { animation-delay: 0.55s; }
.day-card:nth-child(7) { animation-delay: 0.6s; }

.day-card:hover {
  box-shadow: 0 30px 60px -15px rgba(0, 0, 0, 0.12);
  transform: translateY(-4px);
}

.day-header {
  padding: 1.5rem 2rem;
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  border-bottom: 2px solid #e2e8f0;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.day-icon {
  width: 2.5rem;
  height: 2.5rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 0.75rem;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 800;
  font-size: 1.125rem;
  box-shadow: 0 6px 12px -3px rgba(102, 126, 234, 0.3);
}

.day-name {
  font-size: 1.25rem;
  font-weight: 800;
  color: #0f172a;
}

.day-done-btn {
  margin-left: auto;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  background: white;
  border: 2px solid #e2e8f0;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  font-weight: 600;
  color: #64748b;
  cursor: pointer;
  transition: all 0.2s ease;
}

.day-done-btn:hover {
  border-color: #667eea;
  color: #667eea;
  background: #f8f9ff;
}

.day-done-btn svg {
  width: 1.125rem;
  height: 1.125rem;
}

.day-done-btn.completed {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  border-color: #10b981;
  color: white;
}

.day-done-btn.completed:hover {
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  border-color: #059669;
}

.exercise-list {
  padding: 0;
}

.exercise-item {
  padding: 1.5rem 2rem;
  border-bottom: 1px solid #f1f5f9;
  transition: background-color 0.2s ease;
}

.exercise-item:last-child {
  border-bottom: none;
}

.exercise-item:hover {
  background-color: #f8fafc;
}

.exercise-name {
  font-size: 1.125rem;
  font-weight: 700;
  color: #0f172a;
  margin-bottom: 0.75rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.exercise-name svg {
  width: 1.25rem;
  height: 1.25rem;
  color: #667eea;
}

.exercise-details {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
  gap: 1rem;
  margin-bottom: 0.75rem;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.detail-label {
  font-size: 0.75rem;
  font-weight: 700;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.detail-value {
  font-size: 0.95rem;
  font-weight: 700;
  color: #334155;
}

.exercise-notes {
  font-size: 0.875rem;
  color: #64748b;
  font-style: italic;
  padding: 0.75rem 1rem;
  background: #f8fafc;
  border-left: 3px solid #667eea;
  border-radius: 0.5rem;
}

.info-card {
  background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
  border: 1px solid #bfdbfe;
  border-radius: 1rem;
  padding: 1.5rem;
  margin-top: 2rem;
}

.info-card-title {
  font-size: 1.125rem;
  font-weight: 800;
  color: #1e40af;
  margin-bottom: 0.75rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.info-card-title svg {
  width: 1.5rem;
  height: 1.5rem;
}

.info-card ul {
  margin: 0;
  padding-left: 1.5rem;
  color: #1e3a8a;
  line-height: 1.8;
}

.info-card li {
  margin-bottom: 0.5rem;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@media (max-width: 768px) {
  .program-title {
    font-size: 2rem;
  }
  
  .goal-options {
    grid-template-columns: 1fr;
  }
  
  .exercise-details {
    grid-template-columns: 1fr 1fr;
  }
  
  .generate-btn {
    width: 100%;
    justify-content: center;
  }
}
</style>

<div class="program-container">
  <div class="program-header">
    <h2 class="program-title">Fitness Program Generator</h2>
    <p class="program-subtitle">Get a personalized weekly workout plan tailored to your fitness goals</p>
  </div>

  <?php if (!$program_generated): ?>
    <!-- Goal Selection Form -->
    <div class="goal-selection-card">
      <h3 class="goal-section-title">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.563.563 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.563.563 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z"/>
        </svg>
        What's Your Fitness Goal?
      </h3>
      <p class="goal-description">
        Select your primary fitness objective, and we'll create a customized 7-day workout program designed specifically to help you achieve your goal.
      </p>
      
      <?php if ($saved_goal): ?>
      <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 20px; border-radius: 10px; margin-bottom: 20px; text-align: center; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);">
        <strong>✓ Current Goal:</strong> <?php 
          $goal_names = ['weight_loss' => 'Weight Loss', 'muscle_gain' => 'Muscle Gain', 'endurance' => 'Endurance', 'general_fitness' => 'General Fitness'];
          echo $goal_names[$saved_goal] ?? 'Not Set';
        ?>
      </div>
      <?php endif; ?>

      <form method="POST" action="">
        <div class="goal-options">
          <div class="goal-option">
            <input type="radio" name="fitness_goal" value="weight_loss" id="goal_weight_loss" <?php echo $saved_goal === 'weight_loss' ? 'checked' : ''; ?> required>
            <label for="goal_weight_loss" class="goal-option-label">
              <div class="goal-option-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0 1 12 21 8.25 8.25 0 0 1 6.038 7.047 8.287 8.287 0 0 0 9 9.601a8.983 8.983 0 0 1 3.361-6.867 8.21 8.21 0 0 0 3 2.48Z"/>
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 18a3.75 3.75 0 0 0 .495-7.468 5.99 5.99 0 0 0-1.925 3.547 5.975 5.975 0 0 1-2.133-1.001A3.75 3.75 0 0 0 12 18Z"/>
                </svg>
              </div>
              <div class="goal-option-title">Weight Loss</div>
              <div class="goal-option-desc">Burn fat and lose weight with high-intensity cardio and strength training</div>
            </label>
          </div>

          <div class="goal-option">
            <input type="radio" name="fitness_goal" value="muscle_gain" id="goal_muscle_gain" <?php echo $saved_goal === 'muscle_gain' ? 'checked' : ''; ?> required>
            <label for="goal_muscle_gain" class="goal-option-label">
              <div class="goal-option-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/>
                </svg>
              </div>
              <div class="goal-option-title">Muscle Gain</div>
              <div class="goal-option-desc">Build muscle mass and strength with progressive overload training</div>
            </label>
          </div>

          <div class="goal-option">
            <input type="radio" name="fitness_goal" value="endurance" id="goal_endurance" <?php echo $saved_goal === 'endurance' ? 'checked' : ''; ?> required>
            <label for="goal_endurance" class="goal-option-label">
              <div class="goal-option-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/>
                </svg>
              </div>
              <div class="goal-option-title">Endurance</div>
              <div class="goal-option-desc">Improve stamina and cardiovascular fitness for better performance</div>
            </label>
          </div>

          <div class="goal-option">
            <input type="radio" name="fitness_goal" value="general_fitness" id="goal_general" <?php echo $saved_goal === 'general_fitness' ? 'checked' : ''; ?> required>
            <label for="goal_general" class="goal-option-label">
              <div class="goal-option-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z"/>
                </svg>
              </div>
              <div class="goal-option-title">General Fitness</div>
              <div class="goal-option-desc">Balanced program for overall health, wellness, and fitness</div>
            </label>
          </div>
        </div>

        <button type="submit" class="generate-btn">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z"/>
          </svg>
          Generate My Program
        </button>
      </form>
    </div>
  <?php else: ?>
    <!-- Display Program History (Latest) -->
    <?php if ($latest_program_history): ?>
    <div style="background: white; border-radius: 1rem; padding: 1.5rem; margin-bottom: 2rem; box-shadow: 0 4px 15px rgba(0,0,0,0.08);">
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
        <h3 style="font-size: 1.25rem; font-weight: 700; color: #0f172a; margin: 0;">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="width: 1.5rem; height: 1.5rem; display: inline-block; vertical-align: middle; margin-right: 0.5rem;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
          </svg>
          Current Program History
        </h3>
        <?php
        $end_date = strtotime($latest_program_history['end_date']);
        $today = strtotime(date('Y-m-d'));
        $status = $today > $end_date ? 'Completed' : 'In Progress';
        $status_color = $status === 'Completed' ? '#10b981' : '#3b82f6';
        ?>
        <span style="padding: 0.375rem 0.875rem; background: <?php echo $status_color; ?>; color: white; border-radius: 9999px; font-size: 0.813rem; font-weight: 600;">
          <?php echo $status; ?>
        </span>
      </div>
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
        <div>
          <div style="font-size: 0.75rem; color: #64748b; text-transform: uppercase; font-weight: 600; margin-bottom: 0.25rem;">Program</div>
          <div style="font-size: 1rem; color: #0f172a; font-weight: 600;"><?php echo htmlspecialchars($latest_program_history['program_name']); ?></div>
        </div>
        <div>
          <div style="font-size: 0.75rem; color: #64748b; text-transform: uppercase; font-weight: 600; margin-bottom: 0.25rem;">Period</div>
          <div style="font-size: 1rem; color: #0f172a; font-weight: 600;">
            <?php echo date('M d', strtotime($latest_program_history['start_date'])); ?> - <?php echo date('M d, Y', strtotime($latest_program_history['end_date'])); ?>
          </div>
        </div>
        <div>
          <div style="font-size: 0.75rem; color: #64748b; text-transform: uppercase; font-weight: 600; margin-bottom: 0.25rem;">Progress</div>
          <div style="font-size: 1rem; color: #0f172a; font-weight: 600;">
            <?php echo $latest_program_history['days_completed']; ?>/<?php echo $latest_program_history['total_days']; ?> days completed
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>
    
    <!-- Display Generated Program -->
    <div class="program-overview">
      <h2><?php echo htmlspecialchars($weekly_program['name']); ?></h2>
      <p><?php echo htmlspecialchars($weekly_program['description']); ?></p>
    </div>

    <div class="weekly-schedule">
      <?php foreach ($weekly_program['weekly_plan'] as $day => $exercises): 
        $is_completed = isset($daily_completions[$day]) && $daily_completions[$day] == 1;
      ?>
        <div class="day-card">
          <div class="day-header">
            <div class="day-icon"><?php echo substr($day, 0, 1); ?></div>
            <h3 class="day-name"><?php echo $day; ?></h3>
            <button type="button" 
                    class="day-done-btn <?php echo $is_completed ? 'completed' : ''; ?>" 
                    data-day="<?php echo htmlspecialchars($day); ?>"
                    onclick="toggleDayDone(this)" 
                    title="Mark this day as done">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
              </svg>
              <span><?php echo $is_completed ? 'Done!' : 'Done'; ?></span>
            </button>
          </div>
          <div class="exercise-list">
            <?php foreach ($exercises as $exercise): ?>
              <div class="exercise-item">
                <div class="exercise-name">
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                  </svg>
                  <?php echo htmlspecialchars($exercise['exercise']); ?>
                </div>
                <div class="exercise-details">
                  <div class="detail-item">
                    <span class="detail-label">Sets</span>
                    <span class="detail-value"><?php echo htmlspecialchars($exercise['sets']); ?></span>
                  </div>
                  <div class="detail-item">
                    <span class="detail-label">Reps/Duration</span>
                    <span class="detail-value"><?php echo htmlspecialchars($exercise['reps']); ?></span>
                  </div>
                  <div class="detail-item">
                    <span class="detail-label">Rest</span>
                    <span class="detail-value"><?php echo htmlspecialchars($exercise['rest']); ?></span>
                  </div>
                </div>
                <?php if (!empty($exercise['notes'])): ?>
                  <div class="exercise-notes">
                    💡 <?php echo htmlspecialchars($exercise['notes']); ?>
                  </div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Important Tips -->
    <div class="info-card">
      <div class="info-card-title">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"/>
        </svg>
        Important Tips
      </div>
      <ul>
        <li><strong>Progressive Overload:</strong> Gradually increase weight, reps, or intensity each week for continuous improvement</li>
        <li><strong>Proper Form:</strong> Always prioritize correct form over lifting heavier weights to prevent injuries</li>
        <li><strong>Nutrition:</strong> Support your training with adequate protein intake and balanced meals</li>
        <li><strong>Rest & Recovery:</strong> Allow muscles time to recover - rest days are just as important as workout days</li>
        <li><strong>Hydration:</strong> Drink plenty of water before, during, and after workouts</li>
        <li><strong>Warm-up & Cool-down:</strong> Always warm up before and stretch after your workouts</li>
        <li><strong>Track Progress:</strong> Keep a workout log to monitor your improvements and stay motivated</li>
        <li><strong>Consult Trainer:</strong> If you're unsure about any exercise, ask a trainer for guidance</li>
      </ul>
    </div>

    <div style="margin-top: 2rem; text-align: center;">
      <form method="POST" action="">
        <button type="button" onclick="window.location.href='program.php?change=true'" class="generate-btn" style="background: linear-gradient(135deg, #64748b 0%, #475569 100%);">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/>
          </svg>
          Generate Different Program
        </button>
      </form>
    </div>
  <?php endif; ?>
</div>

<script>
async function toggleDayDone(button) {
  const dayName = button.getAttribute('data-day');
  const isCompleted = button.classList.contains('completed');
  const newStatus = !isCompleted;
  
  // Optimistic UI update
  button.classList.toggle('completed');
  const span = button.querySelector('span');
  span.textContent = newStatus ? 'Done!' : 'Done';
  
  // Save to database
  try {
    const response = await fetch('update_program_completion.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        day_name: dayName,
        completed: newStatus
      })
    });
    
    const result = await response.json();
    
    if (!result.success) {
      // Revert UI if save failed
      button.classList.toggle('completed');
      span.textContent = isCompleted ? 'Done!' : 'Done';
      console.error('Failed to save completion status:', result.error);
    }
  } catch (error) {
    // Revert UI if request failed
    button.classList.toggle('completed');
    span.textContent = isCompleted ? 'Done!' : 'Done';
    console.error('Error saving completion status:', error);
  }
}
</script>

<?php include '../footer.php'; ?>
