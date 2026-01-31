<?php
require_once 'config/database.php';
require_once 'config/functions.php';

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Authentication check (session)
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'trainer') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$assignment_id = isset($_POST['assignment_id']) ? intval($_POST['assignment_id']) : 0;
$action = $_POST['action'] ?? '';

if (!$assignment_id || !in_array($action, ['start', 'finish'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit();
}

// Fetch session
$stmt = $pdo->prepare('SELECT * FROM trainer_assignments WHERE assignment_id = ?');
$stmt->execute([$assignment_id]);
$session = $stmt->fetch();
if (!$session) {
    http_response_code(404);
    echo json_encode(['error' => 'Session not found']);
    exit();
}

// Update status
if ($action === 'start' && $session['status'] === 'Scheduled') {
    $new_status = 'Ongoing';
} elseif ($action === 'finish' && ($session['status'] === 'Ongoing' || $session['status'] === 'Scheduled')) {
    $new_status = 'Completed';
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid status transition']);
    exit();
}

$stmt = $pdo->prepare('UPDATE trainer_assignments SET status = ? WHERE assignment_id = ?');
$stmt->execute([$new_status, $assignment_id]);

echo json_encode(['success' => true, 'new_status' => $new_status]);
