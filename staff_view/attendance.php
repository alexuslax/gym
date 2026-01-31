<?php
session_start();
require_once '../config/functions.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'manual_check_out':
                $member_id = sanitizeInput($_POST['member_id']);
                $date = sanitizeInput($_POST['date']);
                $time_out = sanitizeInput($_POST['time_out']);
                // Update the latest attendance record for this member on this date that has no time_out
                $stmt = $pdo->prepare("UPDATE attendance SET time_out = ? WHERE member_id = ? AND date = ? AND time_out IS NULL");
                $stmt->execute([$time_out, $member_id, $date]);
                header('Location: attendance.php?success=Manual check-out recorded successfully');
                exit();
                break;
            case 'auto_check':
                $rfid_card = sanitizeInput($_POST['rfid_card']);
                $date = date('Y-m-d');
                $current_time = date('H:i:s');
                
                // Lookup member_id by RFID
                $stmt = $pdo->prepare("SELECT member_id FROM members WHERE rfid_card_number = ? LIMIT 1");
                $stmt->execute([$rfid_card]);
                $member = $stmt->fetch();
                if (!$member) {
                    header('Location: attendance.php?error=' . urlencode('RFID card not found.'));
                    exit();
                }
                $member_id = $member['member_id'];
                
                try {
                    // Check if member has an active check-in today (time_in exists but time_out is NULL)
                    $stmt = $pdo->prepare("SELECT attendance_id, time_in FROM attendance WHERE member_id = ? AND date = ? AND time_out IS NULL ORDER BY time_in DESC LIMIT 1");
                    $stmt->execute([$member_id, $date]);
                    $active_checkin = $stmt->fetch();
                    
                    if ($active_checkin) {
                        // Member is checked in, perform check-out
                        $stmt = $pdo->prepare("UPDATE attendance SET time_out = ? WHERE attendance_id = ?");
                        $stmt->execute([$current_time, $active_checkin['attendance_id']]);
                        header('Location: attendance.php?success=' . urlencode('Check-out successful.'));
                    } else {
                        // Member is not checked in, perform check-in
                        $status = 'Present';
                        $stmt = $pdo->prepare("INSERT INTO attendance (member_id, date, time_in, status) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$member_id, $date, $current_time, $status]);
                        header('Location: attendance.php?success=' . urlencode('Check-in successful.'));
                    }
                    exit();
                } catch (PDOException $e) {
                    header('Location: attendance.php?error=' . urlencode($e->getMessage()));
                    exit();
                }
                break;
                
            case 'check_in':
                $rfid_card = sanitizeInput($_POST['rfid_card']);
                $date = date('Y-m-d');
                $time_in = date('H:i:s');
                $status = 'Present';
                // Lookup member_id by RFID
                $stmt = $pdo->prepare("SELECT member_id FROM members WHERE rfid_card_number = ? LIMIT 1");
                $stmt->execute([$rfid_card]);
                $member = $stmt->fetch();
                if (!$member) {
                    header('Location: attendance.php?error=' . urlencode('RFID card not found.'));
                    exit();
                }
                $member_id = $member['member_id'];
                try {
                    $stmt = $pdo->prepare("INSERT INTO attendance (member_id, date, time_in, status) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$member_id, $date, $time_in, $status]);
                    header('Location: attendance.php?success=' . urlencode('Check-in successful.'));
                    exit();
                } catch (PDOException $e) {
                    header('Location: attendance.php?error=' . urlencode($e->getMessage()));
                    exit();
                }
                break;
                
            case 'check_out':
                $rfid_card = sanitizeInput($_POST['rfid_card']);
                // Lookup member_id by RFID
                $stmt = $pdo->prepare("SELECT member_id FROM members WHERE rfid_card_number = ? LIMIT 1");
                $stmt->execute([$rfid_card]);
                $member = $stmt->fetch();
                if (!$member) {
                    header('Location: attendance.php?error=' . urlencode('RFID card not found.'));
                    exit();
                }
                $member_id = $member['member_id'];
                try {
                    $stmt = $pdo->prepare("UPDATE attendance SET time_out = ? WHERE member_id = ? AND date = ? AND time_out IS NULL");
                    $stmt->execute([date('H:i:s'), $member_id, date('Y-m-d')]);
                    header('Location: attendance.php?success=' . urlencode('Check-out successful.'));
                    exit();
                } catch (PDOException $e) {
                    header('Location: attendance.php?error=' . urlencode($e->getMessage()));
                    exit();
                }
                break;
                
            case 'manual_check_in':
                $member_id = sanitizeInput($_POST['member_id']);
                $date = sanitizeInput($_POST['date']);
                $time_in = sanitizeInput($_POST['time_in']);
                $status = 'Present';
                $stmt = $pdo->prepare("INSERT INTO attendance (member_id, date, time_in, status) VALUES (?, ?, ?, ?)");
                $stmt->execute([$member_id, $date, $time_in, $status]);
                header('Location: attendance.php?success=Manual check-in recorded successfully');
                exit();
                break;
        }
    }
}

// Get search parameters
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$date_filter = isset($_GET['date']) ? sanitizeInput($_GET['date']) : date('Y-m-d');
$status_filter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';

// Build query
$where_conditions = ["a.date = ?"];
$params = [$date_filter];

if (!empty($search)) {
    $where_conditions[] = "(CONCAT(m.first_name, ' ', m.middle_name, ' ', m.last_name) LIKE ? OR m.member_id LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($status_filter)) {
    $where_conditions[] = "a.status = ?";
    $params[] = $status_filter;
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

// Get attendance records
$sql = "SELECT a.*, m.first_name, m.middle_name, m.last_name, m.membership_status, m.rfid_card_number 
    FROM attendance a 
    JOIN members m ON a.member_id COLLATE utf8mb4_unicode_ci = m.member_id COLLATE utf8mb4_unicode_ci 
    $where_clause 
    ORDER BY a.time_in DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$attendance_records = $stmt->fetchAll();

// Get today's statistics
$today_stats = [
    'total_checkins' => 0,
    'active_members' => 0,
    'checked_out' => 0
];

$stmt = $pdo->prepare("SELECT 
    COUNT(*) as total_checkins,
    SUM(CASE WHEN time_out IS NULL THEN 1 ELSE 0 END) as active_members,
    SUM(CASE WHEN time_out IS NOT NULL THEN 1 ELSE 0 END) as checked_out
    FROM attendance 
    WHERE date = ?");
$stmt->execute([date('Y-m-d')]);
$result = $stmt->fetch();

if ($result) {
    $today_stats['total_checkins'] = $result['total_checkins'] ?? 0;
    $today_stats['active_members'] = $result['active_members'] ?? 0;
    $today_stats['checked_out'] = $result['checked_out'] ?? 0;
}

// Get all members for dropdown
$stmt = $pdo->query("SELECT member_id, first_name, middle_name, last_name, membership_status FROM members WHERE membership_status = 'Active' ORDER BY first_name, last_name");
$active_members = $stmt->fetchAll();
?>
<?php $page_title = 'Attendance Management - UEP Fitness Gym'; include '../header.php'; ?>

<style>
  .page-header {
    margin-bottom: 2.5rem;
    animation: slideInDown 0.5s ease;
  }

  .page-title {
    font-size: 2.25rem;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 0.5rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    transition: all 0.3s ease;
    cursor: default;
  }

  .page-title:hover {
    background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    transform: scale(1.02);
  }

  .page-subtitle {
    font-size: 1rem;
    color: #64748b;
    font-weight: 500;
  }

  @keyframes slideInDown {
    from {
      opacity: 0;
      transform: translateY(-20px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }
</style>

        <!-- Success/Error Messages -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="alert-icon">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                </svg>
                <span class="alert-message"><?php echo htmlspecialchars($_GET['success']); ?></span>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="alert-icon">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                </svg>
                <span class="alert-message"><?php echo htmlspecialchars($_GET['error']); ?></span>
            </div>
        <?php endif; ?>

        <!-- Page Header -->
        <div class="page-header">
            <h2 class="page-title">Attendance Management</h2>
            <p class="page-subtitle">Track member check-ins and check-outs using RFID system.</p>
        </div>

        <div style="margin-bottom: 2rem;">
            <div style="display: flex; gap: var(--spacing-3);">
                <button onclick="openManualModal()" class="btn" style="background: linear-gradient(to right, var(--green-600), var(--green-700)); color: white;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1.25rem; height: 1.25rem;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                    </svg>
                    Manual Check-in
                </button>
                <button onclick="openManualOutModal()" class="btn" style="background: linear-gradient(to right, var(--red-600), var(--red-700)); color: white;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1.25rem; height: 1.25rem;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Manual Check-out
                </button>
            </div>
        </div>

        <!-- Today's Statistics -->
        <div class="stats-grid" style="grid-template-columns: repeat(3, minmax(0, 1fr)); gap: var(--spacing-3); margin-bottom: var(--spacing-8);">
            <div class="card-stats card-stats-blue">
                <div class="stats-decoration stats-decoration-blue" style="position: absolute;"></div>
                <div style="position: relative;">
                    <div class="stats-icon-container stats-icon-container-blue">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" style="color: #ffffff; width: 1.5rem; height: 1.5rem;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"/>
                        </svg>
                    </div>
                    <h3 class="stats-label">Total Check-ins Today</h3>
                    <p class="stats-value"><?php echo $today_stats['total_checkins']; ?></p>
                </div>
            </div>
            <div class="card-stats card-stats-green">
                <div class="stats-decoration stats-decoration-green" style="position: absolute;"></div>
                <div style="position: relative;">
                    <div class="stats-icon-container stats-icon-container-green">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" style="color: #ffffff; width: 1.5rem; height: 1.5rem;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75"/>
                        </svg>
                    </div>
                    <h3 class="stats-label">Currently in Gym</h3>
                    <p class="stats-value"><?php echo $today_stats['active_members']; ?></p>
                </div>
            </div>
            <div class="card-stats card-stats-yellow">
                <div class="stats-decoration stats-decoration-amber" style="position: absolute;"></div>
                <div style="position: relative;">
                    <div class="stats-icon-container stats-icon-container-amber">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" style="color: #ffffff; width: 1.5rem; height: 1.5rem;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z"/>
                        </svg>
                    </div>
                    <h3 class="stats-label">Checked Out</h3>
                    <p class="stats-value"><?php echo $today_stats['checked_out']; ?></p>
                </div>
            </div>
        </div>

    <!-- RFID Auto Check-in/Check-out -->
        <div class="card" style="margin-bottom: var(--spacing-8);">
            <h3 style="font-size: 1.125rem; font-weight: 600; color: var(--slate-900); margin-bottom: var(--spacing-6); display: flex; align-items: center; gap: 0.5rem;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1.25rem; height: 1.25rem; color: var(--blue-600);">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z"/>
                </svg>
                RFID Scanner - Auto Check-in/Check-out
            </h3>
            <div style="max-width: 28rem; margin: 0 auto;">
                <div style="padding: var(--spacing-8); border-radius: 1rem; border: 2px dashed var(--blue-300); text-align: center; background: linear-gradient(to bottom right, var(--blue-50), var(--indigo-50));">
                    <div style="display: inline-flex; align-items: center; justify-content: center; width: 5rem; height: 5rem; background-color: var(--blue-500); border-radius: 50%; margin-bottom: var(--spacing-4); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 2.5rem; height: 2.5rem; color: white;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z"/>
                        </svg>
                    </div>
                    <p style="color: var(--gray-600); margin-bottom: var(--spacing-6); font-weight: 500;">Scan RFID card to automatically check in or check out</p>
                    <form method="POST" id="rfidForm" style="display: flex; flex-direction: column; gap: var(--spacing-4);">
                        <input type="hidden" name="action" value="auto_check">
                        <div>
                            <label style="display: block; font-size: 0.875rem; font-weight: 600; color: var(--gray-700); margin-bottom: 0.5rem; text-align: left;">RFID Card Number</label>
                            <input type="text" name="rfid_card" id="rfid_input" required autofocus class="form-input" style="width: 100%; padding: var(--spacing-4); border-radius: 0.75rem; border: 2px solid var(--blue-300); font-family: monospace; text-align: center; font-size: 1.125rem; letter-spacing: 0.05em;" placeholder="Tap or scan RFID card">
                        </div>
                        <button type="submit" class="btn btn-primary" style="width: 100%; padding: var(--spacing-4); border-radius: 0.75rem; background: linear-gradient(to right, var(--blue-600), var(--indigo-600)); color: green; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); font-weight: 600; font-size: 1.125rem; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1.25rem; height: 1.25rem;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z"/>
                            </svg>
                            Process RFID
                        </button>
                    </form>
                    <p style="font-size: 0.75rem; color: var(--gray-500); margin-top: var(--spacing-4);">The system will automatically determine whether to check in or check out based on your last attendance record.</p>
                </div>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="card" style="margin-bottom: var(--spacing-8);">
            <h3 style="font-size: 1.125rem; font-weight: 600; color: var(--slate-900); margin-bottom: var(--spacing-4); display: flex; align-items: center; gap: 0.5rem;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1.25rem; height: 1.25rem; color: var(--blue-600);">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607z"/>
                </svg>
                Search & Filter Attendance
            </h3>
            <form method="GET" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: var(--spacing-4);">
                <div style="position: relative;">
                    <input type="text" name="search" placeholder="Search by name or ID" value="<?php echo htmlspecialchars($search); ?>" class="form-input" style="width: 100%; padding-left: 2.5rem; padding-right: var(--spacing-4); padding-top: 0.625rem; padding-bottom: 0.625rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1.25rem; height: 1.25rem; color: var(--gray-400); position: absolute; left: 0.75rem; top: 0.875rem;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607z"/>
                    </svg>
                </div>
                <input type="date" name="date" value="<?php echo htmlspecialchars($date_filter); ?>" class="form-input" style="width: 100%;">
                <select name="status" class="form-select" style="width: 100%;">
                    <option value="">All Status</option>
                    <option value="Present" <?php echo $status_filter == 'Present' ? 'selected' : ''; ?>>Present</option>
                    <option value="Absent" <?php echo $status_filter == 'Absent' ? 'selected' : ''; ?>>Absent</option>
                    <option value="Late" <?php echo $status_filter == 'Late' ? 'selected' : ''; ?>>Late</option>
                </select>
                <button type="submit" class="btn btn-primary" style="padding: 0.625rem var(--spacing-4);">
                    Search
                </button>
            </form>
        </div>

        <!-- Attendance Records -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-header-title" style="display: flex; align-items: center; gap: 0.5rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1.25rem; height: 1.25rem; color: var(--blue-600);">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5A2.25 2.25 0 0 1 5.25 5.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25M3 18.75A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75M3 18.75v-8.25A2.25 2.25 0 0 1 5.25 8.25h13.5A2.25 2.25 0 0 1 21 10.5v8.25"/>
                    </svg>
                    Attendance Records - <?php echo formatDate($date_filter); ?>
                </h3>
            </div>
            <div style="overflow-x: auto;">
                <table style="width: 100%;">
                    <thead style="background: linear-gradient(to right, var(--gray-50), var(--gray-100));">
                        <tr>
                            <th class="table-header">Member</th>
                            <th class="table-header">Check-in Time</th>
                            <th class="table-header">Check-out Time</th>
                            <th class="table-header">Duration</th>
                            <th class="table-header">Status</th>
                            <th class="table-header">RFID Card</th>
                        </tr>
                    </thead>
                    <tbody style="background-color: white;">
                        <?php foreach ($attendance_records as $record): ?>
                        <tr class="table-row">
                            <td class="table-cell">
                                <div>
                                    <div style="font-size: 0.875rem; font-weight: 600; color: var(--slate-900);"><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['middle_name'] . ' ' . $record['last_name']); ?></div>
                                    <div style="font-size: 0.875rem; color: var(--gray-500);"><?php echo htmlspecialchars($record['member_id']); ?></div>
                                </div>
                            </td>
                            <td class="table-cell">
                                <span style="font-size: 0.875rem; font-weight: 500; color: var(--gray-900);">
                                    <?php echo $record['time_in'] ? formatTime($record['time_in']) : '<span style="color: var(--gray-400); font-style: italic;">-</span>'; ?>
                                </span>
                            </td>
                            <td class="table-cell">
                                <span style="font-size: 0.875rem; font-weight: 500; color: var(--gray-900);">
                                    <?php echo $record['time_out'] ? formatTime($record['time_out']) : '<span style="color: var(--gray-400); font-style: italic;">-</span>'; ?>
                                </span>
                            </td>
                            <td class="table-cell">
                                <span style="font-size: 0.875rem; font-weight: 500; color: var(--gray-700);">
                                    <?php 
                                    if ($record['time_in'] && $record['time_out']) {
                                        echo calculateDuration($record['time_in'], $record['time_out']);
                                    } elseif ($record['time_in']) {
                                        echo '<span style="color: var(--blue-600);">' . calculateDuration($record['time_in'], date('H:i:s')) . '</span>';
                                    } else {
                                        echo '<span style="color: var(--gray-400); font-style: italic;">-</span>';
                                    }
                                    ?>
                                </span>
                            </td>
                            <td class="table-cell">
                                <?php
                                $status_colors = [
                                    'Present' => 'background-color: var(--green-100); color: var(--green-800); border: 1px solid var(--green-200);',
                                    'Absent' => 'background-color: var(--red-100); color: var(--red-800); border: 1px solid var(--red-200);',
                                    'Late' => 'background-color: var(--yellow-100); color: var(--yellow-800); border: 1px solid var(--yellow-200);'
                                ];
                                $color_style = $status_colors[$record['status']] ?? 'background-color: var(--gray-100); color: var(--gray-800); border: 1px solid var(--gray-200);';
                                ?>
                                <span class="badge" style="<?php echo $color_style; ?>">
                                    <?php echo htmlspecialchars($record['status']); ?>
                                </span>
                            </td>
                            <td class="table-cell">
                                <span style="font-size: 0.875rem; color: var(--gray-600); font-family: monospace;">
                                    <?php echo htmlspecialchars($record['rfid_card_number'] ?? 'Not assigned'); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Manual Check-in Modal -->
    <div id="manualModal" class="modal-overlay" style="display: none;">
        <div class="modal-content" style="max-width: 28rem;" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h3 class="card-header-title">Manual Check-in</h3>
                <button type="button" onclick="closeManualModal()" class="modal-close-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1.5rem; height: 1.5rem;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form method="POST" style="padding: var(--spacing-6);">
                <input type="hidden" name="action" value="manual_check_in">
                
                <div style="display: flex; flex-direction: column; gap: var(--spacing-5);">
                    <div>
                        <label class="form-label">Member</label>
                        <select name="member_id" required class="form-select" style="width: 100%;">
                            <option value="">Select Member</option>
                            <?php foreach ($active_members as $member): ?>
                                <option value="<?php echo $member['member_id']; ?>">
                                    <?php echo htmlspecialchars($member['first_name'] . ' ' . $member['middle_name'] . ' ' . $member['last_name'] . ' (' . $member['member_id'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Date</label>
                        <input type="date" name="date" value="<?php echo date('Y-m-d'); ?>" required class="form-input" style="width: 100%;">
                    </div>
                    <div>
                        <label class="form-label">Check-in Time</label>
                        <input type="time" name="time_in" value="<?php echo date('H:i'); ?>" required class="form-input" style="width: 100%;">
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" onclick="closeManualModal()" class="btn btn-secondary">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-green">
                        Record Check-in
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Manual Check-out -->
     <div id="manualOutModal" class="modal-overlay" style="display: none;">
        <div class="modal-content" style="max-width: 28rem;" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h3 class="card-header-title">Manual Check-out</h3>
                <button type="button" onclick="closeManualOutModal()" class="modal-close-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1.5rem; height: 1.5rem;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form method="POST" style="padding: var(--spacing-6); display: flex; flex-direction: column; gap: var(--spacing-5);">
                <input type="hidden" name="action" value="manual_check_out">

                <div>
                    <label class="form-label">Member</label>
                    <select name="member_id" required class="form-select" style="width: 100%;">
                        <option value="">Select Member</option>
                        <?php foreach ($active_members as $member): ?>
                            <option value="<?php echo $member['member_id']; ?>">
                                <?php echo htmlspecialchars(trim($member['first_name'] . ' ' . $member['middle_name'] . ' ' . $member['last_name']) . ' (' . $member['member_id'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="form-label">Date</label>
                    <input type="date" name="date" value="<?php echo date('Y-m-d'); ?>" required class="form-input" style="width: 100%;">
                </div>

                <div>
                    <label class="form-label">Time Out</label>
                    <input type="time" name="time_out" value="<?php echo date('H:i'); ?>" required class="form-input" style="width: 100%;">
                </div>

                <div class="modal-footer">
                    <button type="button" onclick="closeManualOutModal()" class="btn btn-secondary">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-red">
                        Record Check-out
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal functions
        function openManualModal() {
            document.getElementById('manualModal').classList.add('show');
        }

        function closeManualModal() {
            document.getElementById('manualModal').classList.remove('show');
        }

        function openManualOutModal() {
            document.getElementById('manualOutModal').classList.add('show');
        }

        function closeManualOutModal() {
            document.getElementById('manualOutModal').classList.remove('show');
        }

        // Close modals when clicking outside
        const manualModal = document.getElementById('manualModal');
        const manualOutModal = document.getElementById('manualOutModal');
        
        if (manualModal) {
            manualModal.addEventListener('click', function(e) {
                if (e.target === manualModal) {
                    closeManualModal();
                }
            });
        }
        
        if (manualOutModal) {
            manualOutModal.addEventListener('click', function(e) {
                if (e.target === manualOutModal) {
                    closeManualOutModal();
                }
            });
        }

        // Close modals on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                if (manualModal.classList.contains('show')) closeManualModal();
                if (manualOutModal.classList.contains('show')) closeManualOutModal();
            }
        });

        // Mobile menu toggle
        const menuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');
        const userMenuBtn = document.getElementById('user-menu-btn');
        const userMenu = document.getElementById('user-menu');

        menuBtn.addEventListener('click', () => {
            const isHidden = mobileMenu.classList.toggle('hidden');
            mobileMenu.classList.toggle('flex', !isHidden);
            mobileMenu.classList.toggle('flex-col', !isHidden);
            menuBtn.setAttribute('aria-expanded', String(!isHidden));
        });

        // User menu toggle
        if (userMenuBtn && userMenu) {
            userMenuBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                const hidden = userMenu.classList.toggle('hidden');
                userMenuBtn.setAttribute('aria-expanded', String(!hidden));
            });

            document.addEventListener('click', (e) => {
                if (!userMenu.classList.contains('hidden')) {
                    const target = e.target;
                    const within = userMenu.contains(target) || userMenuBtn.contains(target);
                    if (!within) {
                        userMenu.classList.add('hidden');
                        userMenuBtn.setAttribute('aria-expanded', 'false');
                    }
                }
            });

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && !userMenu.classList.contains('hidden')) {
                    userMenu.classList.add('hidden');
                    userMenuBtn.setAttribute('aria-expanded', 'false');
                }
            });
        }

        // Auto-submit RFID form when card is scanned
        const rfidInput = document.getElementById('rfid_input');
        const rfidForm = document.getElementById('rfidForm');
        
        if (rfidInput && rfidForm) {
            // Auto-submit when Enter key is pressed or when input loses focus (common RFID scanner behavior)
            rfidInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter' && this.value.trim() !== '') {
                    e.preventDefault();
                    rfidForm.submit();
                }
            });

            // Auto-submit when input loses focus (RFID scanners often trigger this)
            rfidInput.addEventListener('blur', function() {
                if (this.value.trim() !== '') {
                    setTimeout(() => {
                        rfidForm.submit();
                    }, 100);
                }
            });

            // Focus the input field on page load
            rfidInput.focus();
        }
    </script>

 <?php include '../footer.php'; ?>
