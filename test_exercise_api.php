<?php
require_once 'config/functions.php';

echo "Testing exercise completion API...\n\n";

// Test with session_id 3
$session_id = 3;
$exercise_index = 0;
$exercise_name = 'Test Exercise';

// Test 1: Insert/update
echo "Test 1: Inserting exercise completion...\n";
$stmt = $pdo->prepare('
    INSERT INTO exercise_completion (session_id, exercise_index, exercise_name, is_completed, completed_at)
    VALUES (?, ?, ?, ?, NOW())
    ON DUPLICATE KEY UPDATE 
        is_completed = VALUES(is_completed),
        completed_at = CASE WHEN VALUES(is_completed) = 1 THEN NOW() ELSE NULL END,
        updated_at = NOW()
');

$result = $stmt->execute([$session_id, $exercise_index, $exercise_name, 1]);
echo "Insert result: " . ($result ? "SUCCESS" : "FAILED") . "\n\n";

// Test 2: Retrieve
echo "Test 2: Retrieving exercise completion...\n";
$stmt = $pdo->prepare('
    SELECT exercise_index, is_completed 
    FROM exercise_completion 
    WHERE session_id = ?
    ORDER BY exercise_index
');
$stmt->execute([$session_id]);
$completions = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($completions) . " completions:\n";
foreach ($completions as $comp) {
    echo "  - Exercise " . $comp['exercise_index'] . ": " . ($comp['is_completed'] ? "DONE" : "NOT DONE") . "\n";
}
?>
