<?php
session_start();
require_once '../config/functions.php';

if (!isset($_GET['equipment_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Equipment ID required']);
    exit();
}

$equipment_id = sanitizeInput($_GET['equipment_id']);

try {
    // Fetch ONLY the latest equipment edit from system_logs
    $stmt = $pdo->prepare("
        SELECT 
            sl.log_id,
            sl.action,
            sl.old_values,
            sl.new_values,
            sl.created_at,
            u.username,
            u.full_name
        FROM system_logs sl
        LEFT JOIN users u ON sl.user_id = u.user_id
        WHERE sl.table_name = 'equipment' 
        AND sl.record_id = ?
        AND sl.action IN ('UPDATE', 'INSERT')
        ORDER BY sl.created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$equipment_id]);
    $record = $stmt->fetch();
    
    if (!$record) {
        echo json_encode([]);
        exit();
    }
    
    $oldValues = json_decode($record['old_values'], true) ?: [];
    $newValues = json_decode($record['new_values'], true) ?: [];
    
    // Extract quantity changes
    $oldQty = $oldValues['total_quantity'] ?? null;
    $newQty = $newValues['total_quantity'] ?? null;
    $qtyChange = null;
    $changeType = null;
    
    if ($oldQty !== null && $newQty !== null) {
        $qtyChange = $newQty - $oldQty;
        $changeType = $qtyChange > 0 ? 'Added' : ($qtyChange < 0 ? 'Reduced' : 'No Change');
    } elseif ($record['action'] === 'INSERT') {
        $qtyChange = $newQty;
        $changeType = 'Initial Add';
    }
    
    // Check for equipment name changes
    $oldName = $oldValues['equipment_name'] ?? null;
    $newName = $newValues['equipment_name'] ?? null;
    $nameChanged = ($oldName !== null && $newName !== null && $oldName !== $newName);
    
    $history = [
        [
            'log_id' => $record['log_id'],
            'action' => $record['action'],
            'created_at' => $record['created_at'],
            'username' => $record['username'] ?? 'System',
            'full_name' => $record['full_name'] ?? 'System User',
            'quantity_change' => $qtyChange !== null ? abs($qtyChange) : 0,
            'change_type' => $changeType,
            'old_quantity' => $oldQty,
            'new_quantity' => $newQty,
            'name_changed' => $nameChanged,
            'old_name' => $oldName,
            'new_name' => $newName
        ]
    ];
    
    echo json_encode($history);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

