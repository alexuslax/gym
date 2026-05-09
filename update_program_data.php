<?php
require_once 'config/functions.php';

// Define program templates (same as in program.php)
$programs = [
    'weight_loss' => [
        'name' => 'Weight Loss & Fat Burning',
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
        'weekly_plan' => [
            'Monday' => [
                ['exercise' => 'Treadmill Running', 'sets' => '1', 'reps' => '45 min', 'rest' => '-', 'notes' => 'Steady pace, build stamina'],
                ['exercise' => 'Dumbbell Lunges', 'sets' => '3', 'reps' => '20 each leg', 'rest' => '60s', 'notes' => 'Leg endurance'],
                ['exercise' => 'Lat Machine Pull-downs', 'sets' => '3', 'reps' => '20', 'rest' => '45s', 'notes' => 'Light weight, high reps'],
            ],
            'Tuesday' => [
                ['exercise' => 'Stationary Bike', 'sets' => '1', 'reps' => '60 min', 'rest' => '-', 'notes' => 'Moderate intensity, steady cadence'],
                ['exercise' => 'Vertical Leg Press', 'sets' => '3', 'reps' => '25', 'rest' => '60s', 'notes' => 'Light weight, focus on endurance'],
                ['exercise' => 'Bench Decline Sit-ups', 'sets' => '3', 'reps' => '25', 'rest' => '45s', 'notes' => 'Core endurance'],
            ],
            'Wednesday' => [
                ['exercise' => 'Treadmill Intervals', 'sets' => '10', 'reps' => '2 min run', 'rest' => '60s walk', 'notes' => 'Build cardiovascular endurance'],
                ['exercise' => 'Dumbbell Squats', 'sets' => '3', 'reps' => '20', 'rest' => '60s', 'notes' => 'Light to moderate weight'],
                ['exercise' => 'Handle Bar Rows', 'sets' => '3', 'reps' => '20', 'rest' => '45s', 'notes' => 'Back endurance'],
            ],
            'Thursday' => [
                ['exercise' => 'Stationary Bike', 'sets' => '1', 'reps' => '50 min', 'rest' => '-', 'notes' => 'Hill simulation program'],
                ['exercise' => 'Dumbbell Step-ups', 'sets' => '3', 'reps' => '20 each leg', 'rest' => '60s', 'notes' => 'Leg endurance focus'],
            ],
            'Friday' => [
                ['exercise' => 'Treadmill Long Distance', 'sets' => '1', 'reps' => '60 min', 'rest' => '-', 'notes' => 'Steady pace, distance goal'],
                ['exercise' => 'Dumbbell Circuit', 'sets' => '3', 'reps' => '15 each', 'rest' => '90s', 'notes' => 'Full body endurance'],
            ],
            'Saturday' => [
                ['exercise' => 'Stationary Bike', 'sets' => '1', 'reps' => '40 min', 'rest' => '-', 'notes' => 'Active recovery ride'],
                ['exercise' => 'Light Stretching', 'sets' => '1', 'reps' => '20 min', 'rest' => '-', 'notes' => 'Flexibility & recovery'],
            ],
            'Sunday' => [
                ['exercise' => 'Rest Day', 'sets' => '-', 'reps' => '-', 'rest' => '-', 'notes' => 'Complete rest for recovery'],
            ],
        ]
    ],
    'general_fitness' => [
        'name' => 'General Fitness & Wellness',
        'weekly_plan' => [
            'Monday' => [
                ['exercise' => 'Treadmill Walking/Jogging', 'sets' => '1', 'reps' => '30 min', 'rest' => '-', 'notes' => 'Comfortable pace'],
                ['exercise' => 'Dumbbell Squats', 'sets' => '3', 'reps' => '12', 'rest' => '60s', 'notes' => 'Basic leg strength'],
                ['exercise' => 'Bench Press (Light)', 'sets' => '3', 'reps' => '12', 'rest' => '60s', 'notes' => 'Upper body strength'],
                ['exercise' => 'Lat Machine Pull-downs', 'sets' => '3', 'reps' => '12', 'rest' => '60s', 'notes' => 'Back development'],
            ],
            'Tuesday' => [
                ['exercise' => 'Stationary Bike', 'sets' => '1', 'reps' => '30 min', 'rest' => '-', 'notes' => 'Moderate intensity'],
                ['exercise' => 'Dumbbell Shoulder Press', 'sets' => '3', 'reps' => '12', 'rest' => '60s', 'notes' => 'Shoulder strength'],
                ['exercise' => 'Handle Bar Bicep Curls', 'sets' => '3', 'reps' => '12', 'rest' => '60s', 'notes' => 'Arm development'],
                ['exercise' => 'Bench Decline Sit-ups', 'sets' => '3', 'reps' => '15', 'rest' => '60s', 'notes' => 'Core strength'],
            ],
            'Wednesday' => [
                ['exercise' => 'Treadmill Walking', 'sets' => '1', 'reps' => '30 min', 'rest' => '-', 'notes' => 'Active recovery'],
                ['exercise' => 'Stretching Routine', 'sets' => '1', 'reps' => '15 min', 'rest' => '-', 'notes' => 'Full body flexibility'],
            ],
            'Thursday' => [
                ['exercise' => 'Vertical Leg Press', 'sets' => '3', 'reps' => '15', 'rest' => '60s', 'notes' => 'Leg strength'],
                ['exercise' => 'Dumbbell Rows', 'sets' => '3', 'reps' => '12', 'rest' => '60s', 'notes' => 'Back muscles'],
                ['exercise' => 'Dumbbell Lunges', 'sets' => '3', 'reps' => '12 each', 'rest' => '60s', 'notes' => 'Leg balance & strength'],
            ],
            'Friday' => [
                ['exercise' => 'Stationary Bike', 'sets' => '1', 'reps' => '30 min', 'rest' => '-', 'notes' => 'Cardio fitness'],
                ['exercise' => 'Dumbbell Circuit', 'sets' => '2', 'reps' => '10 each', 'rest' => '90s', 'notes' => 'Full body workout'],
                ['exercise' => 'Dumbbell Russian Twists', 'sets' => '3', 'reps' => '15', 'rest' => '60s', 'notes' => 'Core rotation'],
            ],
            'Saturday' => [
                ['exercise' => 'Treadmill Walking', 'sets' => '1', 'reps' => '25 min', 'rest' => '-', 'notes' => 'Light cardio'],
                ['exercise' => 'Light Dumbbell Exercises', 'sets' => '2', 'reps' => '12 each', 'rest' => '60s', 'notes' => 'Maintenance workout'],
            ],
            'Sunday' => [
                ['exercise' => 'Rest Day', 'sets' => '-', 'reps' => '-', 'rest' => '-', 'notes' => 'Rest & recovery'],
            ],
        ]
    ]
];

echo "Updating member fitness goals with program data...\n\n";

// Get all members with fitness goals but no program data
$stmt = $pdo->query("SELECT member_id, fitness_goal FROM member_fitness_goals WHERE program_data IS NULL");
$members = $stmt->fetchAll();

$updated = 0;
$skipped = 0;

foreach ($members as $member) {
    $fitness_goal = $member['fitness_goal'];
    
    if (isset($programs[$fitness_goal])) {
        $program_data_json = json_encode($programs[$fitness_goal]['weekly_plan']);
        
        $updateStmt = $pdo->prepare("UPDATE member_fitness_goals SET program_data = ? WHERE member_id = ?");
        $updateStmt->execute([$program_data_json, $member['member_id']]);
        
        echo "Updated member ID {$member['member_id']} with {$fitness_goal} program\n";
        $updated++;
    } else {
        echo "Skipped member ID {$member['member_id']} - unknown fitness goal: {$fitness_goal}\n";
        $skipped++;
    }
}

echo "\n=================================\n";
echo "Update complete!\n";
echo "Updated: $updated members\n";
echo "Skipped: $skipped members\n";
echo "=================================\n";
