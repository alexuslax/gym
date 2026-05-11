<?php
session_start();
require_once 'config/functions.php';

// Resolve a safe, displayable profile image URL from database value
function resolveProfileImagePath($value) {
    $v = trim($value ?? '');
    if ($v === '') return null;
    // Absolute URL
    if (preg_match('#^https?://#', $v)) {
        return $v;
    }
    $baseDir = __DIR__;
    // If value starts with known folders relative to project root
    if (preg_match('#^(img|uploads|assets)/#', $v)) {
        $fsPath = $baseDir . '/' . $v;
        if (pathinfo($v, PATHINFO_EXTENSION) === '') {
            foreach (["jpg","jpeg","png"] as $ext) {
                $candidate = $fsPath . '.' . $ext;
                if (file_exists($candidate)) {
                    return $v . '.' . $ext;
                }
            }
        }
        if (file_exists($fsPath)) {
            return $v;
        }
        return null;
    }
    // Bare filename: assume under img/profiles/
    $dirFs = $baseDir . '/img/profiles/';
    $dirWeb = 'img/profiles/';
    $ext = pathinfo($v, PATHINFO_EXTENSION);
    if ($ext) {
        if (file_exists($dirFs . $v)) {
            return $dirWeb . $v;
        }
    } else {
        foreach (["jpg","jpeg","png"] as $ext) {
            $fn = $v . '.' . $ext;
            if (file_exists($dirFs . $fn)) {
                return $dirWeb . $fn;
            }
        }
    }
    return null;
}

// Handle member update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_member') {
    $member_id = sanitizeInput($_POST['member_id']);
    $username = sanitizeInput($_POST['username']);
    $first_name = sanitizeInput($_POST['first_name']);
    $middle_name = !empty($_POST['middle_name']) ? sanitizeInput($_POST['middle_name']) : null;
    $last_name = sanitizeInput($_POST['last_name']);
    $gender = sanitizeInput($_POST['gender']);
    $contact_number = sanitizeInput($_POST['contact_number']);
    $address = sanitizeInput($_POST['address']);
    $date_of_birth = sanitizeInput($_POST['date_of_birth']);
    $email = isset($_POST['email']) ? sanitizeInput($_POST['email']) : '';
    $rfid_card_number = sanitizeInput($_POST['rfid_card_number']);

    $stmt = $pdo->prepare("UPDATE members 
        SET username=?, first_name=?, middle_name=?, last_name=?, gender=?, contact_number=?, address=?, date_of_birth=?, email=?, rfid_card_number=? 
        WHERE member_id=?");

    $stmt->execute([
        $username, $first_name, $middle_name, $last_name, $gender,
        $contact_number, $address, $date_of_birth, $email,
        $rfid_card_number, $member_id
    ]);

    header('Location: member_profile.php?id=' . $member_id . '&success=Member updated successfully');
    exit();
}

// Get member ID from URL
$member_id = isset($_GET['id']) ? sanitizeInput($_GET['id']) : '';

// Get return URL or determine based on user role
$return_url = isset($_GET['return']) ? sanitizeInput($_GET['return']) : '';
if (empty($return_url)) {
    // Determine return URL based on user role
    if (isset($_SESSION['role'])) {
        switch ($_SESSION['role']) {
            case 'trainer':
                $return_url = 'trainer_view/members.php';
                break;
            case 'admin':
            case 'staff':
                $return_url = 'staff_view/members.php';
                break;
            default:
                $return_url = 'members.php';
        }
    } else {
        $return_url = 'members.php';
    }
}

if (empty($member_id)) {
    header('Location: ' . $return_url);
    exit();
}

// Get member details with plan information from billing
$stmt = $pdo->prepare("SELECT m.*, mp.plan_name
                       FROM members m
                       LEFT JOIN (
                           SELECT member_id, plan_id, MAX(created_at) as latest
                           FROM billing
                           GROUP BY member_id
                       ) latest_billing ON m.member_id = latest_billing.member_id
                       LEFT JOIN billing b ON latest_billing.member_id = b.member_id AND latest_billing.latest = b.created_at
                       LEFT JOIN membership_plans mp ON b.plan_id = mp.plan_id
                       WHERE m.member_id = ?");
$stmt->execute([$member_id]);
$member = $stmt->fetch();

if (!$member) {
    header('Location: ' . $return_url . '?error=Member not found');
    exit();
}

// Get member's attendance records (last 30 days)
$stmt = $pdo->prepare("SELECT * FROM attendance WHERE member_id = ? AND date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) ORDER BY date DESC");
$stmt->execute([$member_id]);
$attendance_records = $stmt->fetchAll();

// Get member's billing records with plan_type from membership_plans
$stmt = $pdo->prepare("SELECT b.*, mp.plan_type FROM billing b LEFT JOIN membership_plans mp ON b.plan_id = mp.plan_id WHERE b.member_id = ? ORDER BY b.created_at DESC LIMIT 10");
$stmt->execute([$member_id]);
$billing_records = $stmt->fetchAll();

// Get member's vital signs (latest 5)
$stmt = $pdo->prepare("SELECT * FROM vital_signs WHERE member_id = ? ORDER BY date_of_recording DESC LIMIT 5");
$stmt->execute([$member_id]);
$vital_records = $stmt->fetchAll();

// Get member's progress records (latest 10)
$stmt = $pdo->prepare("SELECT * FROM progress WHERE member_id = ? ORDER BY progress_date DESC LIMIT 10");
$stmt->execute([$member_id]);
$progress_records = $stmt->fetchAll();

// Get member's trainer assignments
$stmt = $pdo->prepare("SELECT ta.*, TRIM(CONCAT(COALESCE(t.first_name, ''), ' ', COALESCE(t.middle_name, ''), ' ', COALESCE(t.last_name, ''))) as trainer_name FROM trainer_assignments ta 
                      JOIN trainers t ON ta.trainer_id COLLATE utf8mb4_unicode_ci = t.trainer_id COLLATE utf8mb4_unicode_ci 
                      WHERE ta.member_id = ? ORDER BY ta.session_date DESC LIMIT 5");
$stmt->execute([$member_id]);
$trainer_assignments = $stmt->fetchAll();

// Calculate statistics
$stats = [
    'total_visits' => 0,
    'avg_duration' => 0,
    'last_visit' => null,
    'total_billings' => 0,
    'pending_payments' => 0,
    'total_progress_records' => 0
];

// Attendance stats
$stmt = $pdo->prepare("SELECT 
    COUNT(*) as total_visits,
    MAX(date) as last_visit
    FROM attendance 
    WHERE member_id = ? AND time_out IS NOT NULL");
$stmt->execute([$member_id]);
$attendance_stats = $stmt->fetch();

// Calculate average duration manually
$stmt = $pdo->prepare("SELECT time_in, time_out FROM attendance WHERE member_id = ? AND time_out IS NOT NULL");
$stmt->execute([$member_id]);
$duration_records = $stmt->fetchAll();
$total_duration = 0;
$count_with_duration = 0;
foreach ($duration_records as $record) {
    $time_in = strtotime($record['time_in']);
    $time_out = strtotime($record['time_out']);
    if ($time_in && $time_out) {
        $duration = ($time_out - $time_in) / 60; // Convert to minutes
        $total_duration += $duration;
        $count_with_duration++;
    }
}
$attendance_stats['avg_duration'] = $count_with_duration > 0 ? round($total_duration / $count_with_duration) : 0;

$stats = array_merge($stats, $attendance_stats);

// Billing stats
$stmt = $pdo->prepare("SELECT 
    COUNT(*) as total_billings,
    SUM(CASE WHEN payment_status = 'Pending' THEN 1 ELSE 0 END) as pending_payments
    FROM billing 
    WHERE member_id = ?");
$stmt->execute([$member_id]);
$billing_stats = $stmt->fetch();
$stats['total_billings'] = $billing_stats['total_billings'];
$stats['pending_payments'] = $billing_stats['pending_payments'];

// Progress stats
$stmt = $pdo->prepare("SELECT 
    COUNT(*) as total_progress_records
    FROM progress 
    WHERE member_id = ?");
$stmt->execute([$member_id]);
$progress_stats = $stmt->fetch();
$stats['total_progress_records'] = $progress_stats['total_progress_records'];

$page_title = htmlspecialchars(trim($member['first_name'] . ' ' . ($member['middle_name'] ? $member['middle_name'] . ' ' : '') . $member['last_name'])) . ' - Member Profile - UEP Fitness Gym';
include 'header.php';
?>

<link rel="stylesheet" href="css/member_profile.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="member-profile-container">

        <!-- Back Button -->
        <a href="<?php echo htmlspecialchars($return_url); ?>" class="back-btn">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Back to Members
        </a>

        <!-- Member Header -->
        <div class="profile-header">
            <div class="profile-header-inner">
                <div class="profile-header-content">
                    <div class="profile-left-section">
                        <div class="profile-avatar-wrapper">
                            <?php $img = resolveProfileImagePath($member['profile_picture']); ?>
                            <img src="<?php echo htmlspecialchars($img ?: 'img/user.png'); ?>" alt="Profile" class="profile-avatar-img">
                        </div>
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'staff'): ?>
                        <button type="button" id="editMemberBtn" class="btn-edit-member" data-member='<?php echo json_encode($member, JSON_HEX_APOS | JSON_HEX_QUOT); ?>'>
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            Edit Member
                        </button>
                        <?php endif; ?>
                    </div>
                    <div class="profile-right-section">
                        <h1 class="profile-name-large"><?php echo htmlspecialchars(trim($member['first_name'] . ' ' . ($member['middle_name'] ? $member['middle_name'] . ' ' : '') . $member['last_name'])); ?></h1>
                        <p class="profile-id-large"><?php echo htmlspecialchars($member['member_id']); ?></p>
                        <div class="profile-status-badges">
                            <?php
                            $status_badges = [
                                'Active' => 'badge-green',
                                'Pending' => 'badge-yellow',
                                'Expired' => 'badge-red',
                                'Suspended' => 'badge-gray'
                            ];
                            $badge_class = $status_badges[$member['membership_status']] ?? 'badge-gray';
                            ?>
                            <span class="badge <?php echo $badge_class; ?>">
                                <?php echo htmlspecialchars($member['membership_status']); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Member Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3 class="stat-label">Total Visits</h3>
                <p class="stat-value stat-value-blue"><?php echo $stats['total_visits']; ?></p>
            </div>
            <div class="stat-card">
                <h3 class="stat-label">Avg Duration</h3>
                <p class="stat-value stat-value-green"><?php echo number_format($stats['avg_duration'], 0); ?> <span class="stat-unit">min</span></p>
            </div>
            <div class="stat-card">
                <h3 class="stat-label">Progress Records</h3>
                <p class="stat-value stat-value-purple"><?php echo $stats['total_progress_records']; ?></p>
            </div>
        </div>

        <!-- Member Details -->
        <div class="details-grid">
            <!-- Personal Information -->
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Personal Information</h3>
                </div>
                <div class="detail-card-content">
                    <div class="info-row">
                        <div class="info-item">
                            <p class="info-label">Gender</p>
                            <p class="info-value"><?php echo htmlspecialchars($member['gender']); ?></p>
                        </div>
                        <div class="info-item">
                            <p class="info-label">Date of Birth</p>
                            <p class="info-value"><?php echo formatDate($member['date_of_birth']); ?></p>
                        </div>
                    </div>
                    <div class="info-item">
                        <p class="info-label">Contact Number</p>
                        <p class="info-value"><?php echo htmlspecialchars($member['contact_number']); ?></p>
                    </div>
                    <?php if ($member['email']): ?>
                    <div class="info-item">
                        <p class="info-label">Email</p>
                        <p class="info-value"><?php echo htmlspecialchars($member['email']); ?></p>
                    </div>
                    <?php endif; ?>
                    <div class="info-item">
                        <p class="info-label">Address</p>
                        <p class="info-value"><?php echo htmlspecialchars($member['address']); ?></p>
                    </div>
                    <?php if ($member['rfid_card_number']): ?>
                    <div class="info-item">
                        <p class="info-label">RFID Card</p>
                        <p class="info-value"><?php echo htmlspecialchars($member['rfid_card_number']); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Membership Information -->
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Membership Information</h3>
                </div>
                <div class="detail-card-content">
                    <div class="info-row">
                        <div class="info-item">
                            <p class="info-label">Membership Plan</p>
                            <p class="info-value"><?php echo !empty($member['plan_name']) ? htmlspecialchars($member['plan_name']) : 'Not Assigned'; ?></p>
                        </div>
                        <div class="info-item">
                            <p class="info-label">Status</p>
                            <p class="info-value"><?php echo htmlspecialchars($member['membership_status']); ?></p>
                        </div>
                    </div>
                    <div class="info-item">
                        <p class="info-label">Registration Date</p>
                        <p class="info-value"><?php echo formatDate($member['registration_date']); ?></p>
                    </div>
                    <?php if ($member['membership_start_date']): ?>
                    <div class="info-item">
                        <p class="info-label">Membership Start</p>
                        <p class="info-value"><?php echo formatDate($member['membership_start_date']); ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if ($member['membership_end_date']): ?>
                    <div class="info-item">
                        <p class="info-label">Membership End</p>
                        <p class="info-value"><?php echo formatDate($member['membership_end_date']); ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if (isset($member['emergency_contact']) && !empty($member['emergency_contact'])): ?>
                    <div class="info-item">
                        <p class="info-label">Emergency Contact</p>
                        <p class="info-value"><?php echo htmlspecialchars($member['emergency_contact']); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Activity Tabs -->
        <div class="tabs-container">
            <nav class="tabs-nav" aria-label="Tabs">
                    <button onclick="showTab('attendance')" id="attendance-tab" class="tab-button active py-4 px-1 border-b-2 border-blue-500 font-medium text-sm text-blue-600">
                        Attendance
                    </button>
                    <?php if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'trainer'): ?>
                    <button onclick="showTab('billing')" id="billing-tab" class="tab-button py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        Billing
                    </button>
                    <?php endif; ?>
                    <button onclick="showTab('vitals')" id="vitals-tab" class="tab-button py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        Vital Signs
                    </button>
                    <button onclick="showTab('progress')" id="progress-tab" class="tab-button py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        Progress
                    </button>
                    <button onclick="showTab('trainers')" id="trainers-tab" class="tab-button py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        Training Sessions
                    </button>
                </nav>
            </div>

            <!-- Attendance Tab -->
            <div id="attendance-content" class="tab-content">
                <h3 class="tab-title">Recent Attendance (Last 30 Days)</h3>
                <div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Check-in</th>
                                <th>Check-out</th>
                                <th>Duration</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attendance_records as $record): ?>
                            <tr>
                                <td><?php echo isset($record['date']) ? formatDate($record['date']) : '-'; ?></td>
                                <td><?php echo isset($record['time_in']) && $record['time_in'] ? formatTime($record['time_in']) : '-'; ?></td>
                                <td><?php echo isset($record['time_out']) && $record['time_out'] ? formatTime($record['time_out']) : '-'; ?></td>
                                <td>
                                    <?php 
                                    if (isset($record['time_in']) && isset($record['time_out']) && $record['time_in'] && $record['time_out']) {
                                        echo calculateDuration($record['time_in'], $record['time_out']);
                                    } elseif (isset($record['time_in']) && $record['time_in']) {
                                        echo calculateDuration($record['time_in'], date('H:i:s'));
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $status = isset($record['status']) ? $record['status'] : 'Unknown';
                                    $status_class = 'status-present';
                                    if ($status === 'Absent') $status_class = 'status-absent';
                                    if ($status === 'Late') $status_class = 'status-late';
                                    ?>
                                    <span class="status-badge <?php echo $status_class; ?>">
                                        <?php echo htmlspecialchars($status); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Billing Tab -->
            <?php if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'trainer'): ?>
            <div id="billing-content" class="tab-content hidden">
                <h3 class="tab-title">Recent Billing Records</h3>
                <div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Billing ID</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Due Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($billing_records as $record): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($record['billing_id'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars(!empty($record['plan_type']) ? $record['plan_type'] : (!empty($record['plan_id']) ? $record['plan_id'] : 'N/A')); ?></td>
                                <td>₱<?php echo isset($record['amount']) ? number_format((float)$record['amount'], 2) : '0.00'; ?></td>
                                <td><?php echo !empty($record['due_date']) ? formatDate($record['due_date']) : '-'; ?></td>
                                <td>
                                    <?php
                                    $status = $record['payment_status'] ?? 'Unknown';
                                    $status_class = 'status-pending';
                                    if ($status === 'Paid') $status_class = 'status-paid';
                                    if ($status === 'Overdue') $status_class = 'status-overdue';
                                    if ($status === 'Cancelled') $status_class = 'status-cancelled';
                                    ?>
                                    <span class="status-badge <?php echo $status_class; ?>">
                                        <?php echo htmlspecialchars($status); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Vital Signs Tab -->
            <div id="vitals-content" class="tab-content hidden">
                <h3 class="tab-title">Recent Vital Signs</h3>
                <div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Weight</th>
                                <th>Height</th>
                                <th>Body Fat %</th>
                                <th>Blood Pressure</th>
                                <th>Heart Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vital_records as $record): ?>
                            <tr>
                                <td><?php echo formatDate($record['date_of_recording']); ?></td>
                                <td><?php echo $record['weight'] ? number_format($record['weight'], 1) . ' kg' : '-'; ?></td>
                                <td><?php echo $record['height_cm'] ? number_format($record['height_cm'], 1) . ' cm' : '-'; ?></td>
                                <td><?php echo $record['body_fat_percentage'] ? number_format($record['body_fat_percentage'], 1) . '%' : '-'; ?></td>
                                <td>
                                    <?php 
                                    if ($record['blood_pressure_systolic'] && $record['blood_pressure_diastolic']) {
                                        echo $record['blood_pressure_systolic'] . '/' . $record['blood_pressure_diastolic'] . ' mmHg';
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td><?php echo $record['heart_rate'] ? $record['heart_rate'] . ' bpm' : '-'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Progress Tab -->
            <div id="progress-content" class="tab-content hidden">
                <h3 class="tab-title">Recent Progress Records</h3>
                <div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Exercise</th>
                                <th>Sets/Reps</th>
                                <th>Weight</th>
                                <th>Duration</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($progress_records as $record): ?>
                            <tr>
                                <td><?php echo formatDate($record['progress_date']); ?></td>
                                <td><?php echo htmlspecialchars($record['exercise_name']); ?></td>
                                <td>
                                    <?php 
                                    if ($record['sets'] && $record['reps']) {
                                        echo $record['sets'] . 'x' . $record['reps'];
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td><?php echo $record['weight'] ? number_format($record['weight'], 1) . ' kg' : '-'; ?></td>
                                <td><?php echo $record['duration_minutes'] ? $record['duration_minutes'] . ' min' : '-'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Training Sessions Tab -->
            <div id="trainers-content" class="tab-content hidden">
                <h3 class="tab-title">Training Sessions</h3>
                <div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Trainer</th>
                                <th>Type</th>
                                <th>Time</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($trainer_assignments as $assignment): ?>
                            <tr>
                                <td><?php echo formatDate($assignment['session_date']); ?></td>
                                <td><?php echo htmlspecialchars($assignment['trainer_name']); ?></td>
                                <td><?php echo htmlspecialchars($assignment['session_type']); ?></td>
                                <td>
                                    <?php echo formatTime($assignment['start_time']) . ' - ' . formatTime($assignment['end_time']); ?>
                                </td>
                                <td>
                                    <?php
                                    $status = $assignment['status'];
                                    $status_class = 'status-scheduled';
                                    if ($status === 'Completed') $status_class = 'status-completed';
                                    if ($status === 'Cancelled') $status_class = 'status-cancelled';
                                    if ($status === 'No Show') $status_class = 'status-noshow';
                                    ?>
                                    <span class="status-badge <?php echo $status_class; ?>">
                                        <?php echo htmlspecialchars($status); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <script>
        // Tab functionality
        function showTab(tabName) {
            // Hide all tab contents
            const contents = document.querySelectorAll('.tab-content');
            contents.forEach(content => content.classList.add('hidden'));
            
            // Remove active class from all tabs
            const tabs = document.querySelectorAll('.tab-button');
            tabs.forEach(tab => {
                tab.classList.remove('active', 'border-blue-500', 'text-blue-600');
                tab.classList.add('border-transparent', 'text-gray-500');
            });
            
            // Show selected tab content
            document.getElementById(tabName + '-content').classList.remove('hidden');
            
            // Add active class to selected tab
            const activeTab = document.getElementById(tabName + '-tab');
            activeTab.classList.add('active', 'border-blue-500', 'text-blue-600');
            activeTab.classList.remove('border-transparent', 'text-gray-500');
        }

        // Edit Member Modal functionality
        const editMemberBtn = document.getElementById('editMemberBtn');
        if (editMemberBtn) {
            editMemberBtn.addEventListener('click', function() {
                const memberData = JSON.parse(editMemberBtn.getAttribute('data-member'));
                openEditModal(memberData);
            });
        }

        function openEditModal(data) {
            document.getElementById('editMemberId').value = data.member_id || '';
            document.getElementById('editUsername').value = data.username || '';
            document.getElementById('editFirstName').value = data.first_name || '';
            document.getElementById('editLastName').value = data.last_name || '';
            document.getElementById('editMiddleName').value = data.middle_name || '';
            document.getElementById('editGender').value = data.gender || '';
            document.getElementById('editDateOfBirth').value = data.date_of_birth || '';
            document.getElementById('editEmail').value = data.email || '';
            document.getElementById('editContactNumber').value = data.contact_number || '';
            document.getElementById('editAddress').value = data.address || '';
            document.getElementById('editRfidCardNumber').value = data.rfid_card_number || '';
            document.getElementById('editMemberModal').classList.add('show');
        }

        function closeEditModal() {
            document.getElementById('editMemberModal').classList.remove('show');
        }

        const closeEditBtn = document.getElementById('closeEditBtn');
        if (closeEditBtn) {
            closeEditBtn.addEventListener('click', closeEditModal);
        }
    </script>

    <!-- Edit Member Modal -->
    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'staff'): ?>
    <div id="editMemberModal" class="modal-overlay" onclick="if(event.target === this) closeEditModal()">
        <div class="modal-content" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h3 class="modal-title">Edit Member</h3>
                <button type="button" id="closeEditBtn" class="modal-close-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form method="POST" class="modal-form">
                <input type="hidden" name="action" value="update_member">
                <input type="hidden" name="member_id" id="editMemberId">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" id="editUsername" required class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" id="editEmail" required class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">First Name</label>
                        <input type="text" name="first_name" id="editFirstName" required class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Last Name</label>
                        <input type="text" name="last_name" id="editLastName" required class="form-input">
                    </div>
                    <div class="form-group form-group-full">
                        <label class="form-label">Middle Name <span class="form-label-optional">(Optional)</span></label>
                        <input type="text" name="middle_name" id="editMiddleName" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Gender</label>
                        <select name="gender" id="editGender" required class="form-select">
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date of Birth</label>
                        <input type="date" name="date_of_birth" id="editDateOfBirth" required class="form-input">
                    </div>
                    <div class="form-group form-group-full">
                        <label class="form-label">Contact Number</label>
                        <input type="tel" name="contact_number" id="editContactNumber" required class="form-input">
                    </div>
                    <div class="form-group form-group-full">
                        <label class="form-label">Address</label>
                        <textarea name="address" id="editAddress" required rows="3" class="form-textarea"></textarea>
                    </div>
                    <div class="form-group form-group-full">
                        <label class="form-label">RFID Card Number</label>
                        <input type="text" name="rfid_card_number" id="editRfidCardNumber" required class="form-input" style="font-family: monospace;">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn-submit">
                        Save Member
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

<?php include 'footer.php'; ?>
