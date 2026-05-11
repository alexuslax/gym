<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Get equipment_id from query string
if (!isset($_GET['equipment_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Equipment ID required']);
    exit();
}

$equipment_id = $_GET['equipment_id'];

try {
    // Fetch all maintenance records for this equipment
    $stmt = $pdo->prepare("
        SELECT 
            em.maintenance_id,
            em.maintenance_type,
            DATE_FORMAT(em.maintenance_date, '%M %d, %Y') as maintenance_date,
            em.status,
            em.cost,
            em.performed_by,
            em.description,
            DATE_FORMAT(em.completion_date, '%M %d, %Y') as completion_date,
            GROUP_CONCAT(
                CONCAT('Unit #', eu.unit_number) 
                ORDER BY eu.unit_number 
                SEPARATOR ', '
            ) as units
        FROM equipment_maintenance em
        LEFT JOIN equipment_units eu ON em.unit_id = eu.unit_id
        WHERE em.equipment_id = ?
        GROUP BY em.maintenance_id
        ORDER BY em.maintenance_date DESC, em.maintenance_id DESC
    ");
    
    $stmt->execute([$equipment_id]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return the history as JSON (empty array if no results)
    echo json_encode($history);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
