<?php
require_once dirname(__DIR__) . '/config/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$action = $_POST['action'] ?? null;
$session_id = intval($_POST['session_id'] ?? 0);
$exercise_index = intval($_POST['exercise_index'] ?? 0);
$is_completed = intval($_POST['is_completed'] ?? 0);
$exercise_name = $_POST['exercise_name'] ?? '';

if (!$action || !$session_id) {
    echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
    exit;
}

try {
    if ($action === 'toggle') {
        // Insert or update exercise completion status
        $stmt = $pdo->prepare('
            INSERT INTO exercise_completion (session_id, exercise_index, exercise_name, is_completed, completed_at)
            VALUES (?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
                is_completed = VALUES(is_completed),
                completed_at = CASE WHEN VALUES(is_completed) = 1 THEN NOW() ELSE NULL END,
                updated_at = NOW()
        ');
        
        $result = $stmt->execute([$session_id, $exercise_index, $exercise_name, $is_completed]);
        
        echo json_encode([
            'success' => $result,
            'message' => $is_completed ? 'Exercise marked as done' : 'Exercise marked as not done'
        ]);
    } elseif ($action === 'get') {
        // Get exercise completion status for a session
        $stmt = $pdo->prepare('
            SELECT exercise_index, is_completed 
            FROM exercise_completion 
            WHERE session_id = ?
            ORDER BY exercise_index
        ');
        $stmt->execute([$session_id]);
        $completions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $completion_map = [];
        foreach ($completions as $comp) {
            $completion_map[$comp['exercise_index']] = (bool)$comp['is_completed'];
        }
        
        echo json_encode([
            'success' => true,
            'completions' => $completion_map
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
