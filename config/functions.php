<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'database.php';

// Function to sanitize input data
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to generate unique IDs
function generateID($prefix, $table, $id_column) {
    global $pdo;
    // Get the highest existing numeric part of the ID
    $stmt = $pdo->prepare("SELECT MAX(CAST(SUBSTRING($id_column, LENGTH(?) + 1) AS UNSIGNED)) FROM $table WHERE $id_column LIKE CONCAT(?, '%')");
    $stmt->execute([$prefix, $prefix]);
    $max_number = $stmt->fetchColumn();
    $next_number = $max_number ? $max_number + 1 : 1;
    $new_id = $prefix . str_pad($next_number, 4, '0', STR_PAD_LEFT);
    return $new_id;
}

// Function to format date for display
function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

// Function to format time for display
function formatTime($time) {
    return date('g:i A', strtotime($time));
}

// Function to calculate duration between two times
function calculateDuration($time_in, $time_out) {
    if (!$time_in || !$time_out) return null;
    
    $start = new DateTime($time_in);
    $end = new DateTime($time_out);
    $diff = $start->diff($end);
    
    if ($diff->h > 0) {
        return $diff->h . 'h ' . $diff->i . 'm';
    } else {
        return $diff->i . 'm';
    }
}

// Function to get dashboard statistics
function getDashboardStats() {
    global $pdo;
    
    $stats = [];
    
    // Total members
    $stmt = $pdo->query("SELECT COUNT(*) FROM members");
    $stats['total_members'] = $stmt->fetchColumn();
    
    // Active members
    $stmt = $pdo->query("SELECT COUNT(*) FROM members WHERE membership_status = 'Active'");
    $stats['active_members'] = $stmt->fetchColumn();
    
    // Today's attendance
    $stmt = $pdo->query("SELECT COUNT(*) FROM attendance WHERE date = CURDATE() AND status = 'Present'");
    $stats['today_attendance'] = $stmt->fetchColumn();
    
    // Pending payments
    $stmt = $pdo->query("SELECT COUNT(*) FROM billing WHERE payment_status = 'Pending'");
    $stats['pending_payments'] = $stmt->fetchColumn();
    
    // Equipment status
    $stmt = $pdo->query("SELECT 
        SUM(CASE WHEN status = 'Working' THEN quantity_available ELSE 0 END) as working,
        SUM(CASE WHEN status = 'Needs Repair' THEN quantity_available ELSE 0 END) as repair,
        SUM(CASE WHEN status = 'Under Maintenance' THEN quantity_available ELSE 0 END) as maintenance
        FROM equipment");
    $equipment = $stmt->fetch();
    $stats['equipment'] = $equipment;
    
    return $stats;
}

// Function to get recent activity
function getRecentActivity() {
    global $pdo;
    
    $activities = [];
    
    // Recent system logs (LOGIN/LOGOUT events)
    $stmt = $pdo->query("SELECT sl.action, sl.created_at, u.full_name, u.username 
                        FROM system_logs sl 
                        LEFT JOIN users u ON sl.user_id = u.user_id 
                        WHERE sl.action IN ('LOGIN', 'LOGOUT')
                        ORDER BY sl.created_at DESC LIMIT 3");
    $logs = $stmt->fetchAll();
    foreach ($logs as $log) {
        $user_name = $log['full_name'] ?? $log['username'] ?? 'Unknown user';
        $action = strtolower($log['action']);
        $activities[] = [
            'type' => 'system',
            'message' => ucfirst($action) . ": " . $user_name,
            'date' => $log['created_at'],
            'timestamp' => timeAgo($log['created_at'])
        ];
    }
    
    // Recent member registrations
    $stmt = $pdo->query("SELECT first_name, middle_name, last_name, registration_date FROM members ORDER BY registration_date DESC LIMIT 2");
    $members = $stmt->fetchAll();
    foreach ($members as $member) {
        $full_name = trim($member['first_name'] . ' ' . ($member['middle_name'] ?? '') . ' ' . $member['last_name']);
        $activities[] = [
            'type' => 'registration',
            'message' => "New member registered: " . $full_name,
            'date' => $member['registration_date'],
            'timestamp' => timeAgo($member['registration_date'])
        ];
    }
    
    // Recent check-ins
    $stmt = $pdo->query("SELECT m.first_name, m.middle_name, m.last_name, a.time_in, a.date FROM attendance a 
                        JOIN members m ON a.member_id COLLATE utf8mb4_unicode_ci = m.member_id COLLATE utf8mb4_unicode_ci 
                        WHERE a.time_in IS NOT NULL 
                        ORDER BY a.date DESC, a.time_in DESC LIMIT 2");
    $checkins = $stmt->fetchAll();
    foreach ($checkins as $checkin) {
        $full_name = trim($checkin['first_name'] . ' ' . ($checkin['middle_name'] ?? '') . ' ' . $checkin['last_name']);
        $check_datetime = $checkin['date'] . ' ' . $checkin['time_in'];
        $activities[] = [
            'type' => 'checkin',
            'message' => "RFID check-in: " . $full_name,
            'date' => $check_datetime,
            'timestamp' => timeAgo($check_datetime)
        ];
    }
    
    // Sort by date
    usort($activities, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
    
    return array_slice($activities, 0, 8);
}

// Function to format time ago
function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $difference = time() - $timestamp;
    
    if ($difference < 60) {
        return 'Just now';
    } elseif ($difference < 3600) {
        $mins = floor($difference / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($difference < 86400) {
        $hours = floor($difference / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($difference < 604800) {
        $days = floor($difference / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, Y g:i A', $timestamp);
    }
}

// Function to check if user is logged in (for future authentication)
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: index.html');
        exit();
    }
}
?>
