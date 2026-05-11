<?php
require_once '../config/database.php';
session_start();

// Check if user is logged in and is staff
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if (!isset($_GET['equipment_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Equipment ID required']);
    exit();
}

$equipment_id = (int)$_GET['equipment_id'];

try {
    // Get units currently under maintenance for this equipment
    $stmt = $pdo->prepare("SELECT unit_id, unit_number FROM equipment_units WHERE equipment_id = ? AND status = 'Under Maintenance' ORDER BY unit_number");
    $stmt->execute([$equipment_id]);
    $units = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($units);
} catch (Exception $e) {
    error_log('get_units_under_maintenance.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error', 'message' => $e->getMessage()]);
}
?>
