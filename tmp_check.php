<?php
require_once __DIR__ . '/config/functions.php';
header('Content-Type: application/json');
try {
    $out = [];
    $stmt = $pdo->query("SELECT COUNT(*) AS c FROM users WHERE is_active = 0");
    $out['inactive_users'] = (int)$stmt->fetchColumn();
    $stmt = $pdo->query("SELECT COUNT(*) AS c FROM users WHERE pending_data IS NOT NULL");
    $out['users_with_pending_data'] = (int)$stmt->fetchColumn();
    $stmt = $pdo->query("SELECT role, COUNT(*) as c FROM users WHERE is_active = 0 OR pending_data IS NOT NULL GROUP BY role");
    $out['by_role'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt = $pdo->query("SELECT user_id, username, role, is_active, pending_data, created_at FROM users WHERE is_active = 0 OR pending_data IS NOT NULL ORDER BY created_at DESC LIMIT 10");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$r) {
        if (!empty($r['pending_data'])) {
            $r['pending_parsed'] = json_decode($r['pending_data'], true);
        } else {
            $r['pending_parsed'] = null;
        }
        unset($r['pending_data']);
    }
    $out['samples'] = $rows;
    echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
