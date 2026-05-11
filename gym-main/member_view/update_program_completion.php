<?php
session_start();
require_once '../config/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

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

if (!$member_id) {
    echo json_encode(['success' => false, 'error' => 'Member not found']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$day_name = $data['day_name'] ?? '';
$completed = $data['completed'] ?? false;

if (empty($day_name)) {
    echo json_encode(['success' => false, 'error' => 'Day name required']);
    exit();
}

try {
    // Get the current active program
    $stmt = $pdo->prepare('
        SELECT id 
        FROM member_program_history 
        WHERE member_id = ? 
        ORDER BY generated_date DESC 
        LIMIT 1
    ');
    $stmt->execute([$member_id]);
    $program = $stmt->fetch();
    
    if (!$program) {
        echo json_encode(['success' => false, 'error' => 'No active program found']);
        exit();
    }
    
    $program_id = $program['id'];
    
    // Update the daily completion status
    $completed_date = $completed ? date('Y-m-d H:i:s') : null;
    $stmt = $pdo->prepare('
        UPDATE member_program_daily_completion 
        SET completed = ?, completed_date = ?
        WHERE program_history_id = ? AND day_name = ?
    ');
    $stmt->execute([$completed, $completed_date, $program_id, $day_name]);
    
    // Update the days_completed count in the history
    $stmt = $pdo->prepare('
        UPDATE member_program_history 
        SET days_completed = (
            SELECT COUNT(*) 
            FROM member_program_daily_completion 
            WHERE program_history_id = ? AND completed = 1
        )
        WHERE id = ?
    ');
    $stmt->execute([$program_id, $program_id]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
