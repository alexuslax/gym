<?php
session_start();
require_once '../config/functions.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'approve_trainer':
                $user_id = sanitizeInput($_POST['user_id']);
                try {
                    $pdo->beginTransaction();
                    
                    // Get user information and pending data
                    $stmt = $pdo->prepare("SELECT user_id, username, email, role, pending_data FROM users WHERE user_id = ? AND is_active = 0");
                    $stmt->execute([$user_id]);
                    $user = $stmt->fetch();
                    
                    if ($user && !empty($user['pending_data']) && $user['role'] === 'trainer') {
                        $pending_data = json_decode($user['pending_data'], true);
                        
                        // Generate trainer ID
                        $trainer_id = generateID('TRN', 'trainers', 'trainer_id');
                        
                        // Create trainer record
                        $stmt = $pdo->prepare("
                            INSERT INTO trainers (
                                trainer_id, username, first_name, last_name, middle_name,
                                gender, contact_number, email, address, date_of_birth,
                                specialization, status, hire_date, profile_picture
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active', ?, ?)
                        ");
                        $stmt->execute([
                            $trainer_id,
                            $user['username'],
                            $pending_data['first_name'],
                            $pending_data['last_name'],
                            $pending_data['middle_name'] ?? '',
                            $pending_data['gender'],
                            $pending_data['contact_number'],
                            $user['email'],
                            $pending_data['address'],
                            $pending_data['date_of_birth'],
                            $pending_data['specialization'] ?? '',
                            $pending_data['hire_date'] ?? date('Y-m-d'),
                            $pending_data['profile_picture'] ?? null
                        ]);
                        
                        // Activate user account and clear pending data
                        $stmt = $pdo->prepare("UPDATE users SET is_active = 1, pending_data = NULL WHERE user_id = ?");
                        $stmt->execute([$user['user_id']]);
                        
                        $pdo->commit();
                        header('Location: trainers.php?success=Trainer approved successfully');
                    } else {
                        $pdo->rollBack();
                        header('Location: trainers.php?error=Pending trainer not found or invalid data');
                    }
                    exit();
                } catch (PDOException $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    error_log('Trainer approval error: ' . $e->getMessage());
                    header('Location: trainers.php?error=Database error occurred: ' . $e->getMessage());
                    exit();
                }
                break;
                
            case 'reject_trainer':
                $user_id = sanitizeInput($_POST['user_id']);
                try {
                    // Delete pending user account
                    $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ? AND is_active = 0");
                    $stmt->execute([$user_id]);
                    
                    header('Location: trainers.php?success=Trainer registration rejected');
                    exit();
                } catch (PDOException $e) {
                    error_log('Trainer rejection error: ' . $e->getMessage());
                    header('Location: trainers.php?error=Failed to reject trainer');
                    exit();
                }
                break;
                
            case 'add_trainer':
                $trainer_id = generateID('TRN', 'trainers', 'trainer_id');
                $username = sanitizeInput($_POST['username']);
                $first_name = sanitizeInput($_POST['first_name']);
                $last_name = sanitizeInput($_POST['last_name']);
                $middle_name = isset($_POST['middle_name']) ? sanitizeInput($_POST['middle_name']) : '';
                $gender = sanitizeInput($_POST['gender']);
                $contact_number = sanitizeInput($_POST['contact_number']);
                $email = sanitizeInput($_POST['email']);
                $address = sanitizeInput($_POST['address']);
                $date_of_birth = sanitizeInput($_POST['date_of_birth']);
                $specialization = sanitizeInput($_POST['specialization']);
                $status = sanitizeInput($_POST['status']);
                $hire_date = sanitizeInput($_POST['hire_date']);
                
                $stmt = $pdo->prepare("INSERT INTO trainers (trainer_id, username, first_name, last_name, middle_name, gender, contact_number, email, address, date_of_birth, specialization, status, hire_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$trainer_id, $username, $first_name, $last_name, $middle_name, $gender, $contact_number, $email, $address, $date_of_birth, $specialization, $status, $hire_date]);
                
                header('Location: trainers.php?success=Trainer added successfully');
                exit();
                break;
                
            case 'update_trainer':
                $trainer_id = sanitizeInput($_POST['trainer_id']);
                $username = sanitizeInput($_POST['username']);
                $first_name = sanitizeInput($_POST['first_name']);
                $last_name = sanitizeInput($_POST['last_name']);
                $middle_name = isset($_POST['middle_name']) ? sanitizeInput($_POST['middle_name']) : '';
                $gender = sanitizeInput($_POST['gender']);
                $contact_number = sanitizeInput($_POST['contact_number']);
                $email = sanitizeInput($_POST['email']);
                $address = sanitizeInput($_POST['address']);
                $date_of_birth = sanitizeInput($_POST['date_of_birth']);
                $specialization = sanitizeInput($_POST['specialization']);
                $status = sanitizeInput($_POST['status']);
                $hire_date = sanitizeInput($_POST['hire_date']);
                
                $stmt = $pdo->prepare("UPDATE trainers SET username=?, first_name=?, last_name=?, middle_name=?, gender=?, contact_number=?, email=?, address=?, date_of_birth=?, specialization=?, status=?, hire_date=? WHERE trainer_id=?");
                $stmt->execute([$username, $first_name, $last_name, $middle_name, $gender, $contact_number, $email, $address, $date_of_birth, $specialization, $status, $hire_date, $trainer_id]);
                
                header('Location: trainers.php?success=Trainer updated successfully');
                exit();
                break;
                
            case 'delete_trainer':
                $trainer_id = sanitizeInput($_POST['trainer_id']);
                $stmt = $pdo->prepare("DELETE FROM trainers WHERE trainer_id = ?");
                $stmt->execute([$trainer_id]);
                
                header('Location: trainers.php?success=Trainer deleted successfully');
                exit();
                break;
                
            case 'add_assignment':
                $trainer_id = sanitizeInput($_POST['trainer_id']);
                $member_id = sanitizeInput($_POST['member_id']);
                $session_type = sanitizeInput($_POST['session_type']);
                $session_date = sanitizeInput($_POST['session_date']);
                $start_time = sanitizeInput($_POST['start_time']);
                $end_time = sanitizeInput($_POST['end_time']);
                $notes = sanitizeInput($_POST['notes']);
                
                $stmt = $pdo->prepare("INSERT INTO trainer_assignments (trainer_id, member_id, session_type, session_date, start_time, end_time, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$trainer_id, $member_id, $session_type, $session_date, $start_time, $end_time, $notes]);
                
                header('Location: trainers.php?success=Training session scheduled successfully');
                exit();
                break;
        }
    }
}

// Get search parameters
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$specialization_filter = isset($_GET['specialization']) ? sanitizeInput($_GET['specialization']) : '';

// Build where conditions for active trainers (exclude Inactive status)
$active_where_conditions = [];
$active_params = [];

if (!empty($search)) {
    $active_where_conditions[] = "((t.first_name LIKE ? OR t.last_name LIKE ? OR t.middle_name LIKE ?) OR t.trainer_id LIKE ? OR t.username LIKE ?)";
    $search_param = "%$search%";
    $active_params[] = $search_param;
    $active_params[] = $search_param;
    $active_params[] = $search_param;
    $active_params[] = $search_param;
    $active_params[] = $search_param;
}

// For active trainers tab, only show Active, On Leave status (exclude Inactive)
// If status filter is set and it's not 'Inactive', apply it
if (!empty($status_filter) && $status_filter != 'Inactive') {
    $active_where_conditions[] = "t.status = ?";
    $active_params[] = $status_filter;
} else {
    // If no status filter or filter is 'Inactive', show Active and On Leave trainers
    $active_where_conditions[] = "(t.status = 'Active' OR t.status = 'On Leave')";
}

if (!empty($specialization_filter)) {
    $active_where_conditions[] = "t.specialization LIKE ?";
    $active_params[] = "%$specialization_filter%";
}

$active_where = !empty($active_where_conditions) ? "WHERE " . implode(" AND ", $active_where_conditions) : "WHERE (t.status = 'Active' OR t.status = 'On Leave')";

// Get active trainers (exclude inactive/pending)
$sql = "SELECT t.* FROM trainers t $active_where ORDER BY t.last_name, t.first_name, t.middle_name";
$stmt = $pdo->prepare($sql);
$stmt->execute($active_params);
$trainers = $stmt->fetchAll();

// Get pending trainers separately - query users table for pending approvals
// These are users who registered as trainers but haven't been approved yet (no trainer_id assigned)
$pending_sql = "SELECT u.user_id, u.username, u.email, u.full_name, u.pending_data, u.created_at
                FROM users u
                WHERE u.is_active = 0 AND u.role = 'trainer'
                ORDER BY u.created_at DESC";
$stmt = $pdo->prepare($pending_sql);
$stmt->execute();
$pending_users = $stmt->fetchAll();

// Parse pending data for display
$pending_trainers = [];
foreach ($pending_users as $user) {
    $pending_data = json_decode($user['pending_data'], true);
    if ($pending_data) {
        $pending_trainers[] = [
            'user_id' => $user['user_id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'first_name' => $pending_data['first_name'] ?? '',
            'last_name' => $pending_data['last_name'] ?? '',
            'middle_name' => $pending_data['middle_name'] ?? '',
            'gender' => $pending_data['gender'] ?? '',
            'contact_number' => $pending_data['contact_number'] ?? '',
            'address' => $pending_data['address'] ?? '',
            'date_of_birth' => $pending_data['date_of_birth'] ?? '',
            'specialization' => $pending_data['specialization'] ?? '',
            'hire_date' => $pending_data['hire_date'] ?? '',
            'profile_picture' => $pending_data['profile_picture'] ?? null,
            'status' => 'Pending'
        ];
    }
}

// Get trainer statistics
$stats = [
    'total_trainers' => 0,
    'active_trainers' => 0,
    'total_sessions' => 0,
    'upcoming_sessions' => 0
];

$stmt = $pdo->query("SELECT 
    COUNT(*) as total_trainers,
    SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as active_trainers
    FROM trainers");
$trainer_stats = $stmt->fetch();

$stmt = $pdo->query("SELECT 
    COUNT(*) as total_sessions,
    COALESCE(SUM(CASE WHEN session_date >= CURDATE() THEN 1 ELSE 0 END), 0) as upcoming_sessions
    FROM trainer_assignments");
$session_stats = $stmt->fetch();

$stats = array_merge($trainer_stats, $session_stats);

// Fetch all sessions with details
$stmt = $pdo->query("SELECT ta.*, 
    CONCAT(m.first_name, ' ', COALESCE(m.middle_name, ''), ' ', m.last_name) as member_name,
    m.member_id as member_id,
    CONCAT(t.first_name, ' ', COALESCE(t.middle_name, ''), ' ', t.last_name) as trainer_name,
    t.trainer_id as trainer_id
    FROM trainer_assignments ta
    JOIN members m ON ta.member_id COLLATE utf8mb4_unicode_ci = m.member_id COLLATE utf8mb4_unicode_ci
    JOIN trainers t ON ta.trainer_id COLLATE utf8mb4_unicode_ci = t.trainer_id COLLATE utf8mb4_unicode_ci
    ORDER BY ta.session_date DESC, ta.start_time DESC
    LIMIT 100");
$all_sessions = $stmt->fetchAll();

// Fetch upcoming sessions (future sessions)
$stmt = $pdo->query("SELECT ta.*, 
    CONCAT(m.first_name, ' ', COALESCE(m.middle_name, ''), ' ', m.last_name) as member_name,
    m.member_id as member_id,
    CONCAT(t.first_name, ' ', COALESCE(t.middle_name, ''), ' ', t.last_name) as trainer_name,
    t.trainer_id as trainer_id
    FROM trainer_assignments ta
    JOIN members m ON ta.member_id COLLATE utf8mb4_unicode_ci = m.member_id COLLATE utf8mb4_unicode_ci
    JOIN trainers t ON ta.trainer_id COLLATE utf8mb4_unicode_ci = t.trainer_id COLLATE utf8mb4_unicode_ci
    WHERE ta.session_date >= CURDATE()
    ORDER BY ta.session_date ASC, ta.start_time ASC
    LIMIT 50");
$upcoming_sessions_list = $stmt->fetchAll();

// Get all members for assignment dropdown
$stmt = $pdo->query("SELECT member_id, first_name, middle_name, last_name FROM members WHERE membership_status = 'Active' ORDER BY first_name, last_name");
$active_members = $stmt->fetchAll();

// Get trainer for editing
$edit_trainer = null;
if (isset($_GET['edit'])) {
    $edit_id = sanitizeInput($_GET['edit']);
    $stmt = $pdo->prepare("SELECT t.* FROM trainers t WHERE t.trainer_id = ?");
    $stmt->execute([$edit_id]);
    $edit_trainer = $stmt->fetch();
}

// Get trainer assignments
$trainer_assignments = [];
if (isset($_GET['assignments'])) {
    $trainer_id = sanitizeInput($_GET['assignments']);
    $stmt = $pdo->prepare("SELECT ta.*, CONCAT(m.first_name, ' ', m.middle_name, ' ', m.last_name) as member_name, CONCAT(t.first_name, ' ', t.middle_name, ' ', t.last_name) as trainer_name 
                          FROM trainer_assignments ta 
                          JOIN members m ON ta.member_id COLLATE utf8mb4_unicode_ci = m.member_id COLLATE utf8mb4_unicode_ci 
                          JOIN trainers t ON ta.trainer_id COLLATE utf8mb4_unicode_ci = t.trainer_id COLLATE utf8mb4_unicode_ci 
                          WHERE ta.trainer_id = ? 
                          ORDER BY ta.session_date DESC, ta.start_time DESC");
    $stmt->execute([$trainer_id]);
    $trainer_assignments = $stmt->fetchAll();
}
?>
  <?php $page_title = 'Trainers Management - UEP Fitness Gym'; include '../header.php'; ?>

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

        <!-- Success Message -->
        <?php if (isset($_GET['success'])): ?>
            <div class="mb-6 bg-gradient-to-r from-green-50 to-emerald-50 border-l-4 border-green-500 text-green-800 px-6 py-4 rounded-lg shadow-md flex items-center gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6 text-green-600 flex-shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                </svg>
                <span class="font-medium"><?php echo htmlspecialchars($_GET['success']); ?></span>
            </div>
        <?php endif; ?>

        <!-- Error Message -->
        <?php if (isset($_GET['error'])): ?>
            <div class="mb-6 bg-gradient-to-r from-red-50 to-red-100 border-l-4 border-red-500 text-red-800 px-6 py-4 rounded-lg shadow-md flex items-center gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6 text-red-600 flex-shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                </svg>
                <span class="font-medium"><?php echo htmlspecialchars($_GET['error']); ?></span>
            </div>
        <?php endif; ?>

        <!-- Page Header -->
        <div class="page-header">
            <h2 class="page-title">Trainers Management</h2>
            <p class="page-subtitle">Manage trainer profiles, schedules, and member assignments.</p>
        </div>

        <div style="margin-bottom: 2rem;">
            <div class="flex gap-3">
                <button onclick="openAssignmentModal()" class="btn btn-green">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5A2.25 2.25 0 0 1 5.25 5.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25M3 18.75A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75M3 18.75v-8.25A2.25 2.25 0 0 1 5.25 8.25h13.5A2.25 2.25 0 0 1 21 10.5v8.25"/>
                    </svg>
                    Schedule Session
                </button>
            </div>
        </div>

        <!-- Trainer Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="card-stats card-stats-blue">
                <div class="absolute top-0 right-0 w-20 h-20 bg-blue-200/30 rounded-full -mr-10 -mt-10 blur-2xl"></div>
                <div class="relative">
                    <div class="inline-flex items-center justify-center w-12 h-12 bg-blue-500 rounded-xl mb-3 shadow-md">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6 text-white">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.5v-1.125A3.375 3.375 0 0 0 11.625 15h-3.75A3.375 3.375 0 0 0 4.5 18.375V19.5M12 10.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0M18 8.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0M20.25 19.5v-1.125A3.375 3.375 0 0 0 17.25 15h-1.125"/>
                        </svg>
                    </div>
                    <h3 class="text-gray-600 text-sm font-medium mb-1">Total Trainers</h3>
                    <p class="text-3xl font-bold text-slate-900"><?php echo $stats['total_trainers']; ?></p>
                </div>
            </div>
            <div class="card-stats card-stats-green">
                <div class="absolute top-0 right-0 w-20 h-20 bg-green-200/30 rounded-full -mr-10 -mt-10 blur-2xl"></div>
                <div class="relative">
                    <div class="inline-flex items-center justify-center w-12 h-12 bg-green-500 rounded-xl mb-3 shadow-md">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6 text-white">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-gray-600 text-sm font-medium mb-1">Active Trainers</h3>
                    <p class="text-3xl font-bold text-slate-900"><?php echo $stats['active_trainers']; ?></p>
                </div>
            </div>
            <div class="card-stats card-stats-purple">
                <div class="absolute top-0 right-0 w-20 h-20 bg-purple-200/30 rounded-full -mr-10 -mt-10 blur-2xl"></div>
                <div class="relative">
                    <div class="inline-flex items-center justify-center w-12 h-12 bg-purple-500 rounded-xl mb-3 shadow-md">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6 text-white">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5A2.25 2.25 0 0 1 5.25 5.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25M3 18.75A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75M3 18.75v-8.25A2.25 2.25 0 0 1 5.25 8.25h13.5A2.25 2.25 0 0 1 21 10.5v8.25"/>
                        </svg>
                    </div>
                    <h3 class="text-gray-600 text-sm font-medium mb-1">Total Sessions</h3>
                    <p class="text-3xl font-bold text-slate-900"><?php echo $stats['total_sessions']; ?></p>
                </div>
            </div>
            <div class="card-stats" style="background: linear-gradient(to bottom right, var(--orange-100), var(--amber-100)); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05), 0 0 0 1px rgba(251, 146, 60, 0.5);">
                <div class="absolute top-0 right-0 w-20 h-20 bg-orange-200/30 rounded-full -mr-10 -mt-10 blur-2xl"></div>
                <div class="relative">
                    <div class="inline-flex items-center justify-center w-12 h-12 bg-yellow-500 rounded-xl mb-3 shadow-md">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6 text-white">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5A2.25 2.25 0 0 1 5.25 5.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25M3 18.75A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75M3 18.75v-8.25A2.25 2.25 0 0 1 5.25 8.25h13.5A2.25 2.25 0 0 1 21 10.5v8.25"/>
                        </svg>
                    </div>
                    <h3 class="text-gray-600 text-sm font-medium mb-1">Upcoming Sessions</h3>
                    <p class="text-3xl font-bold text-slate-900"><?php echo $stats['upcoming_sessions']; ?></p>
                </div>
            </div>
        </div>

        <!-- Sessions List -->
        <div class="mb-8">
            <!-- Tabs for Sessions -->
            <div class="mb-6 border-b border-gray-200">
                <nav class="tab-nav" aria-label="Sessions Tabs">
                    <button onclick="switchSessionTab('upcoming')" id="tab-session-upcoming" class="tab-button tab-button-active">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5 inline mr-2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5A2.25 2.25 0 0 1 5.25 5.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25M3 18.75A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75M3 18.75v-8.25A2.25 2.25 0 0 1 5.25 8.25h13.5A2.25 2.25 0 0 1 21 10.5v8.25"/>
                        </svg>
                        Upcoming Sessions
                        <span class="ml-2 bg-orange-100 text-orange-600 py-0.5 px-2.5 rounded-full text-xs font-medium"><?php echo count($upcoming_sessions_list); ?></span>
                    </button>
                    <button onclick="switchSessionTab('all')" id="tab-session-all" class="tab-button">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5 inline mr-2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                        </svg>
                        All Sessions
                        <span class="ml-2 bg-purple-100 text-purple-600 py-0.5 px-2.5 rounded-full text-xs font-medium"><?php echo count($all_sessions); ?></span>
                    </button>
                </nav>
            </div>

            <!-- Upcoming Sessions Tab -->
            <div id="session-tab-upcoming" class="tab-content active">
                <div class="card overflow-hidden">
                    <div class="card-header">
                        <h3 class="text-lg font-semibold text-slate-900 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5 text-orange-600">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5A2.25 2.25 0 0 1 5.25 5.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25M3 18.75A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75M3 18.75v-8.25A2.25 2.25 0 0 1 5.25 8.25h13.5A2.25 2.25 0 0 1 21 10.5v8.25"/>
                            </svg>
                            Upcoming Training Sessions
                        </h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Trainer</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Member</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Session Type</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Time</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                <?php if (!empty($upcoming_sessions_list)): ?>
                                    <?php foreach ($upcoming_sessions_list as $session): ?>
                                        <tr class="hover:bg-orange-50/50 transition-colors duration-150 group">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars(trim($session['trainer_name'])); ?></div>
                                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($session['trainer_id']); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars(trim($session['member_name'])); ?></div>
                                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($session['member_id']); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-blue-100 text-blue-800">
                                                    <?php echo htmlspecialchars($session['session_type']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php echo formatDate($session['session_date']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                <?php echo date('h:i A', strtotime($session['start_time'])); ?> - <?php echo date('h:i A', strtotime($session['end_time'])); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php
                                                $status_colors = [
                                                    'Scheduled' => 'bg-blue-100 text-blue-800 ring-1 ring-blue-200',
                                                    'Completed' => 'bg-green-100 text-green-800 ring-1 ring-green-200',
                                                    'Cancelled' => 'bg-red-100 text-red-800 ring-1 ring-red-200',
                                                    'No Show' => 'bg-yellow-100 text-yellow-800 ring-1 ring-yellow-200'
                                                ];
                                                $status = $session['status'] ?? 'Scheduled';
                                                $color_class = $status_colors[$status] ?? 'bg-gray-100 text-gray-800 ring-1 ring-gray-200';
                                                ?>
                                                <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full <?php echo $color_class; ?>">
                                                    <?php echo htmlspecialchars($status); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="px-6 py-12 text-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-12 h-12 text-gray-400 mx-auto mb-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5A2.25 2.25 0 0 1 5.25 5.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25M3 18.75A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75M3 18.75v-8.25A2.25 2.25 0 0 1 5.25 8.25h13.5A2.25 2.25 0 0 1 21 10.5v8.25"/>
                                            </svg>
                                            <p class="text-gray-500 font-medium">No upcoming sessions</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- All Sessions Tab -->
            <div id="session-tab-all" class="tab-content hidden">
                <div class="bg-white rounded-2xl shadow-lg ring-1 ring-gray-200/50 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white">
                        <h3 class="text-lg font-semibold text-slate-900 flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5 text-purple-600">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                            </svg>
                            All Training Sessions
                        </h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Trainer</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Member</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Session Type</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Time</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                <?php if (!empty($all_sessions)): ?>
                                    <?php foreach ($all_sessions as $session): ?>
                                        <tr class="hover:bg-purple-50/50 transition-colors duration-150 group">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars(trim($session['trainer_name'])); ?></div>
                                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($session['trainer_id']); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars(trim($session['member_name'])); ?></div>
                                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($session['member_id']); ?></div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-medium bg-blue-100 text-blue-800">
                                                    <?php echo htmlspecialchars($session['session_type']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php echo formatDate($session['session_date']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                <?php echo date('h:i A', strtotime($session['start_time'])); ?> - <?php echo date('h:i A', strtotime($session['end_time'])); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php
                                                $status_colors = [
                                                    'Scheduled' => 'bg-blue-100 text-blue-800 ring-1 ring-blue-200',
                                                    'Completed' => 'bg-green-100 text-green-800 ring-1 ring-green-200',
                                                    'Cancelled' => 'bg-red-100 text-red-800 ring-1 ring-red-200',
                                                    'No Show' => 'bg-yellow-100 text-yellow-800 ring-1 ring-yellow-200'
                                                ];
                                                $status = $session['status'] ?? 'Scheduled';
                                                $color_class = $status_colors[$status] ?? 'bg-gray-100 text-gray-800 ring-1 ring-gray-200';
                                                ?>
                                                <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full <?php echo $color_class; ?>">
                                                    <?php echo htmlspecialchars($status); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="px-6 py-12 text-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-12 h-12 text-gray-400 mx-auto mb-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                                            </svg>
                                            <p class="text-gray-500 font-medium">No sessions found</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="bg-white p-6 rounded-2xl shadow-lg ring-1 ring-gray-200/50 mb-8">
            <h3 class="text-lg font-semibold text-slate-900 mb-4 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5 text-blue-600">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607z"/>
                </svg>
                Search & Filter Trainers
            </h3>
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="relative">
                    <input type="text" name="search" placeholder="Search trainers" value="<?php echo htmlspecialchars($search); ?>" class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5 text-gray-400 absolute left-3 top-3.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607z"/>
                    </svg>
                </div>
                <select name="status" class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 bg-white">
                    <option value="">All Status</option>
                    <option value="Active" <?php echo $status_filter == 'Active' ? 'selected' : ''; ?>>Active</option>
                    <option value="Inactive" <?php echo $status_filter == 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                    <option value="On Leave" <?php echo $status_filter == 'On Leave' ? 'selected' : ''; ?>>On Leave</option>
                </select>
                <input type="text" name="specialization" placeholder="Specialization" value="<?php echo htmlspecialchars($specialization_filter); ?>" class="w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200">
                <button type="submit" class="rounded-xl bg-gradient-to-r from-blue-600 to-blue-700 text-white px-4 py-2.5 hover:from-blue-700 hover:to-blue-800 shadow-md hover:shadow-lg transition-all duration-200 font-medium">
                    Search
                </button>
            </form>
        </div>

        <!-- Tabs -->
        <div class="mb-6 border-b border-gray-200">
            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                <button onclick="switchTrainerTab('active')" id="tab-trainer-active" class="tab-button border-b-2 border-blue-500 py-4 px-1 text-sm font-medium text-blue-600">
                    Active Trainers
                    <span class="ml-2 bg-blue-100 text-blue-600 py-0.5 px-2.5 rounded-full text-xs font-medium"><?php echo count($trainers); ?></span>
                </button>
                <button onclick="switchTrainerTab('pending')" id="tab-trainer-pending" class="tab-button border-b-2 border-transparent py-4 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    Pending Approvals
                    <span class="ml-2 bg-yellow-100 text-yellow-600 py-0.5 px-2.5 rounded-full text-xs font-medium"><?php echo count($pending_trainers); ?></span>
                </button>
            </nav>
        </div>

        <!-- Active Trainers Cards -->
        <div id="active-trainers-tab" class="tab-content active">
            <?php if (empty($trainers)): ?>
                <div class="bg-white rounded-2xl shadow-lg ring-1 ring-gray-200/50 p-12 text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-16 h-16 text-gray-400 mx-auto mb-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.5v-1.125A3.375 3.375 0 0 0 11.625 15h-3.75A3.375 3.375 0 0 0 4.5 18.375V19.5M12 10.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0M18 8.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0M20.25 19.5v-1.125A3.375 3.375 0 0 0 17.25 15h-1.125"/>
                    </svg>
                    <p class="text-gray-500 font-medium text-lg mb-2">No trainers found</p>
                    <p class="text-sm text-gray-400">Try adjusting your search or filter criteria.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($trainers as $trainer): ?>
                        <div class="card-member">
                            <div class="p-6">
                                <div class="flex items-start gap-4 mb-4">
                                    <div class="flex-shrink-0">
                                        <div class="h-16 w-16 rounded-2xl bg-gradient-to-br from-indigo-400 to-indigo-600 flex items-center justify-center shadow-md ring-2 ring-white overflow-hidden">
                                            <?php 
                                            $profile_pic = isset($trainer['profile_picture']) ? trim($trainer['profile_picture']) : '';
                                            $initial = strtoupper(substr($trainer['first_name'], 0, 1));
                                            
                                            // Ensure proper path format
                                            $pic_path = '';
                                            if (!empty($profile_pic)) {
                                                if (strpos($profile_pic, 'profiles/') === 0) {
                                                    $pic_path = '../img/' . $profile_pic;
                                                } elseif (strpos($profile_pic, 'img/') === 0) {
                                                    $pic_path = '../' . $profile_pic;
                                                } else {
                                                    $pic_path = '../img/profiles/' . $profile_pic;
                                                }
                                            }
                                            
                                            if (!empty($pic_path) && file_exists($pic_path)): 
                                            ?>
                                                <img src="<?php echo htmlspecialchars($pic_path); ?>" class="h-full w-full object-cover rounded-2xl" alt="">
                                            <?php else: ?>
                                                <span class="text-white text-2xl font-bold">
                                                    <?php echo $initial; ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-lg font-bold text-gray-900 mb-1 truncate">
                                            <?php echo htmlspecialchars(trim($trainer['first_name'] . ' ' . ($trainer['middle_name'] ? $trainer['middle_name'] . ' ' : '') . $trainer['last_name'])); ?>
                                        </h3>
                                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($trainer['trainer_id']); ?></p>
                                        <p class="text-xs text-gray-400 mt-1"><?php echo htmlspecialchars($trainer['contact_number']); ?></p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-3 mb-4">
                                    <div class="bg-indigo-50 rounded-xl p-3">
                                        <p class="text-xs text-indigo-600 font-medium mb-1">Specialization</p>
                                        <p class="text-sm font-bold text-indigo-900 truncate"><?php echo htmlspecialchars($trainer['specialization'] ?: 'N/A'); ?></p>
                                    </div>
                                    <div class="bg-purple-50 rounded-xl p-3">
                                        <p class="text-xs text-purple-600 font-medium mb-1">Experience</p>
                                        <p class="text-sm font-bold text-purple-900">
                                            <?php
                                            if (!empty($trainer['hire_date'])) {
                                                $hire_date = new DateTime($trainer['hire_date']);
                                                $today = new DateTime();
                                                $experience = $today->diff($hire_date);
                                                if ($experience->y > 0) {
                                                    echo $experience->y . ' year' . ($experience->y > 1 ? 's' : '');
                                                } elseif ($experience->m > 0) {
                                                    echo $experience->m . ' month' . ($experience->m > 1 ? 's' : '');
                                                } else {
                                                    echo '<1 month';
                                                }
                                            } else {
                                                echo 'N/A';
                                            }
                                            ?>
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                                    <?php
                                    $status_badges = [
                                        'Active' => 'badge-green',
                                        'Inactive' => 'badge-red',
                                        'On Leave' => 'badge-yellow'
                                    ];
                                    $badge_class = $status_badges[$trainer['status']] ?? 'badge-gray';
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <?php echo htmlspecialchars($trainer['status']); ?>
                                    </span>
                                    <a href="../trainer_profile.php?id=<?php echo $trainer['trainer_id']; ?>&return=staff_view/trainers.php" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold text-white rounded-xl shadow-md hover:shadow-lg transition-all duration-200" style="background: linear-gradient(to right, #6366f1, #4f46e5);" 
                                       onmouseover="this.style.background='linear-gradient(to right, #4f46e5, #4338ca)'; this.style.transform='scale(1.02)'"
                                       onmouseout="this.style.background='linear-gradient(to right, #6366f1, #4f46e5)'; this.style.transform='scale(1)'">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0zM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                                        </svg>
                                        View Profile
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pending Trainers Cards -->
        <div id="pending-trainers-tab" class="tab-content hidden">
            <?php if (empty($pending_trainers)): ?>
                <div class="bg-white rounded-2xl shadow-lg ring-1 ring-gray-200/50 p-12 text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-16 h-16 text-gray-400 mx-auto mb-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                    </svg>
                    <p class="text-gray-500 font-medium text-lg mb-2">No pending trainer approvals</p>
                    <p class="text-sm text-gray-400">All trainer registrations have been processed.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($pending_trainers as $trainer): ?>
                        <div class="bg-white rounded-2xl shadow-lg ring-1 ring-yellow-200/50 overflow-hidden hover:shadow-xl transition-all duration-300 hover:-translate-y-1 border-l-4 border-yellow-500">
                            <div class="p-6">
                                <div class="flex items-start gap-4 mb-4">
                                    <div class="flex-shrink-0">
                                        <div class="h-16 w-16 rounded-2xl bg-gradient-to-br from-yellow-400 to-yellow-600 flex items-center justify-center shadow-md ring-2 ring-white overflow-hidden">
                                            <?php 
                                            $profile_pic = isset($trainer['profile_picture']) ? trim($trainer['profile_picture']) : '';
                                            $initial = strtoupper(substr($trainer['first_name'], 0, 1));
                                            
                                            // Ensure proper path format
                                            $pic_path = '';
                                            if (!empty($profile_pic)) {
                                                if (strpos($profile_pic, 'profiles/') === 0) {
                                                    $pic_path = '../img/' . $profile_pic;
                                                } elseif (strpos($profile_pic, 'img/') === 0) {
                                                    $pic_path = '../' . $profile_pic;
                                                } else {
                                                    $pic_path = '../img/profiles/' . $profile_pic;
                                                }
                                            }
                                            
                                            if (!empty($pic_path) && file_exists($pic_path)): 
                                            ?>
                                                <img src="<?php echo htmlspecialchars($pic_path); ?>" class="h-full w-full object-cover rounded-2xl" alt="">
                                            <?php else: ?>
                                                <span class="text-white text-2xl font-bold">
                                                    <?php echo $initial; ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-lg font-bold text-gray-900 mb-1 truncate">
                                            <?php echo htmlspecialchars(trim($trainer['first_name'] . ' ' . ($trainer['middle_name'] ? $trainer['middle_name'] . ' ' : '') . $trainer['last_name'])); ?>
                                        </h3>
                                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($trainer['username']); ?></p>
                                        <p class="text-xs text-gray-400 mt-1"><?php echo htmlspecialchars($trainer['contact_number']); ?></p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-3 mb-4">
                                    <div class="bg-indigo-50 rounded-xl p-3">
                                        <p class="text-xs text-indigo-600 font-medium mb-1">Specialization</p>
                                        <p class="text-sm font-bold text-indigo-900 truncate"><?php echo htmlspecialchars($trainer['specialization']); ?></p>
                                    </div>
                                    <div class="bg-purple-50 rounded-xl p-3">
                                        <p class="text-xs text-purple-600 font-medium mb-1">Email</p>
                                        <p class="text-xs font-semibold text-purple-900 truncate"><?php echo htmlspecialchars($trainer['email']); ?></p>
                                    </div>
                                </div>

                                <div class="mb-4 text-xs text-gray-600">
                                    <div class="flex items-center justify-between">
                                        <span class="text-gray-500">Registration Date:</span>
                                        <span class="font-medium"><?php echo !empty($trainer['hire_date']) ? date('M d, Y', strtotime($trainer['hire_date'])) : 'N/A'; ?></span>
                                    </div>
                                </div>

                                <div class="flex flex-col gap-3 pt-4 border-t border-gray-200">
                                    <div class="flex items-center justify-between">
                                        <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800 ring-1 ring-yellow-200">
                                            Pending Approval
                                        </span>
                                        <div class="flex gap-2">
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="approve_trainer">
                                                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($trainer['user_id']); ?>">
                                                <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white rounded-lg transition-all duration-200" style="background: linear-gradient(to right, #10b981, #059669);" onmouseover="this.style.transform='scale(1.05); box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4)'" onmouseout="this.style.transform='scale(1); box-shadow: none'">
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-3.5 h-3.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                                                    </svg>
                                                    Approve
                                                </button>
                                            </form>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to reject this trainer?');">
                                                <input type="hidden" name="action" value="reject_trainer">
                                                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($trainer['user_id']); ?>">
                                                <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white rounded-lg transition-all duration-200" style="background: linear-gradient(to right, #ef4444, #dc2626);" onmouseover="this.style.transform='scale(1.05); box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4)'" onmouseout="this.style.transform='scale(1); box-shadow: none'">
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-3.5 h-3.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                    Reject
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Trainer Assignments Section -->
        <?php if (!empty($trainer_assignments)): ?>
        <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold">Training Sessions</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Member</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Session Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($trainer_assignments as $assignment): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($assignment['member_name']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($assignment['session_type']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo formatDate($assignment['session_date']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo formatTime($assignment['start_time']) . ' - ' . formatTime($assignment['end_time']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                $status_colors = [
                                    'Scheduled' => 'bg-blue-100 text-blue-800',
                                    'Completed' => 'bg-green-100 text-green-800',
                                    'Cancelled' => 'bg-red-100 text-red-800',
                                    'No Show' => 'bg-yellow-100 text-yellow-800'
                                ];
                                $color_class = $status_colors[$assignment['status']] ?? 'bg-gray-100 text-gray-800';
                                ?>
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $color_class; ?>">
                                    <?php echo htmlspecialchars($assignment['status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- Add/Edit Trainer Modal -->
    <div id="trainerModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto border border-gray-200" onclick="event.stopPropagation()">
            <div class="px-6 py-5 border-b border-gray-200 bg-gradient-to-r from-white to-gray-50 flex items-center justify-between sticky top-0 bg-white z-10">
                <h3 class="text-xl font-bold text-slate-900" id="modalTitle">Add Trainer</h3>
                <button type="button" onclick="closeModal()" class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg p-1.5 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-gray-300">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
                </div>
                <form method="POST" class="p-6 space-y-5">
                    <input type="hidden" name="action" id="formAction" value="add_trainer">
                    <input type="hidden" name="trainer_id" id="editTrainerId">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Username</label>
                            <input type="text" name="username" id="username" required class="block w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">First Name</label>
                            <input type="text" name="first_name" id="first_name" required class="block w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Last Name</label>
                            <input type="text" name="last_name" id="last_name" required class="block w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Middle Name <span class="text-xs font-normal text-gray-500">(Optional)</span></label>
                            <input type="text" name="middle_name" id="middle_name" class="block w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Gender</label>
                            <select name="gender" id="gender" required class="block w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 bg-white">
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Contact Number</label>
                            <input type="tel" name="contact_number" id="contact_number" required class="block w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Email</label>
                            <input type="email" name="email" id="email" required class="block w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Date of Birth</label>
                            <input type="date" name="date_of_birth" id="date_of_birth" required class="block w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Specialization</label>
                            <input type="text" name="specialization" id="specialization" class="block w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200" placeholder="e.g., Weight Training, Cardio, Yoga">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Status</label>
                            <select name="status" id="status" required class="block w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 bg-white">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                                <option value="On Leave">On Leave</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Hire Date</label>
                            <input type="date" name="hire_date" id="hire_date" required class="block w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Address</label>
                        <textarea name="address" id="address" required rows="3" class="block w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 resize-none"></textarea>
                    </div>
                    
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                        <button type="button" onclick="closeModal()" class="px-6 py-2.5 text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200 font-medium transition-colors duration-200">
                            Cancel
                        </button>
                        <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl hover:from-blue-700 hover:to-blue-800 shadow-md hover:shadow-lg font-medium transition-all duration-200">
                            Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Assignment Modal -->
    <div id="assignmentModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full max-h-[90vh] overflow-y-auto border border-gray-200" onclick="event.stopPropagation()">
            <div class="px-6 py-5 border-b border-gray-200 bg-gradient-to-r from-white to-gray-50 flex items-center justify-between">
                <h3 class="text-xl font-bold text-slate-900">Schedule Training Session</h3>
                <button type="button" onclick="closeAssignmentModal()" class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg p-1.5 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-gray-300">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
                </div>
                <form method="POST" class="p-6 space-y-5">
                    <input type="hidden" name="action" value="add_assignment">
                    
                        <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Trainer</label>
                        <select name="trainer_id" required class="block w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 bg-white">
                                <option value="">Select Trainer</option>
                                <?php foreach ($trainers as $trainer): ?>
                                    <?php if ($trainer['status'] == 'Active'): ?>
                                        <option value="<?php echo $trainer['trainer_id']; ?>">
                                            <?php echo htmlspecialchars(trim($trainer['first_name'] . ' ' . ($trainer['middle_name'] ? $trainer['middle_name'] . ' ' : '') . $trainer['last_name']) . ' (' . $trainer['specialization'] . ')'); ?>
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Member</label>
                        <select name="member_id" required class="block w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 bg-white">
                                <option value="">Select Member</option>
                                <?php foreach ($active_members as $member): ?>
                                    <option value="<?php echo $member['member_id']; ?>">
                                        <?php echo htmlspecialchars(trim($member['first_name'] . ' ' . $member['middle_name'] . ' ' . $member['last_name']) . ' (' . $member['member_id'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Session Type</label>
                        <select name="session_type" required class="block w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 bg-white">
                                <option value="">Select Type</option>
                                <option value="Personal Training">Personal Training</option>
                                <option value="Group Class">Group Class</option>
                                <option value="Consultation">Consultation</option>
                            </select>
                        </div>
                        <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Session Date</label>
                        <input type="date" name="session_date" value="<?php echo date('Y-m-d'); ?>" required class="block w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Start Time</label>
                            <input type="time" name="start_time" required class="block w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200">
                            </div>
                            <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">End Time</label>
                            <input type="time" name="end_time" required class="block w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200">
                            </div>
                        </div>
                        <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Notes <span class="text-xs font-normal text-gray-500">(Optional)</span></label>
                        <textarea name="notes" rows="3" class="block w-full px-4 py-2.5 rounded-xl border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 resize-none"></textarea>
                    </div>
                    
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                        <button type="button" onclick="closeAssignmentModal()" class="px-6 py-2.5 text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200 font-medium transition-colors duration-200">
                            Cancel
                        </button>
                        <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-xl hover:from-green-700 hover:to-green-800 shadow-md hover:shadow-lg font-medium transition-all duration-200">
                            Schedule Session
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Tab switching function
        function switchTrainerTab(tab) {
            // Hide all tab contents
            const activeTab = document.getElementById('active-trainers-tab');
            const pendingTab = document.getElementById('pending-trainers-tab');
            
            activeTab.classList.add('hidden');
            activeTab.classList.remove('active');
            pendingTab.classList.add('hidden');
            pendingTab.classList.remove('active');
            
            // Remove active styles from all tabs
            document.getElementById('tab-trainer-active').classList.remove('border-blue-500', 'text-blue-600');
            document.getElementById('tab-trainer-active').classList.add('border-transparent', 'text-gray-500');
            document.getElementById('tab-trainer-pending').classList.remove('border-blue-500', 'text-blue-600');
            document.getElementById('tab-trainer-pending').classList.add('border-transparent', 'text-gray-500');
            
            // Show selected tab content and add active styles
            if (tab === 'active') {
                activeTab.classList.remove('hidden');
                activeTab.classList.add('active');
                document.getElementById('tab-trainer-active').classList.remove('border-transparent', 'text-gray-500');
                document.getElementById('tab-trainer-active').classList.add('border-blue-500', 'text-blue-600');
            } else if (tab === 'pending') {
                pendingTab.classList.remove('hidden');
                pendingTab.classList.add('active');
                document.getElementById('tab-trainer-pending').classList.remove('border-transparent', 'text-gray-500');
                document.getElementById('tab-trainer-pending').classList.add('border-blue-500', 'text-blue-600');
            }
        }

        function switchSessionTab(tab) {
            // Hide all session tab contents
            const upcomingContent = document.getElementById('session-tab-upcoming');
            const allContent = document.getElementById('session-tab-all');
            
            upcomingContent.classList.add('hidden');
            upcomingContent.classList.remove('active');
            allContent.classList.add('hidden');
            allContent.classList.remove('active');
            
            // Remove active styles from all session tabs
            const upcomingTab = document.getElementById('tab-session-upcoming');
            const allTab = document.getElementById('tab-session-all');
            
            upcomingTab.classList.remove('border-orange-500', 'text-orange-600');
            upcomingTab.classList.add('border-transparent', 'text-gray-500');
            allTab.classList.remove('border-purple-500', 'text-purple-600');
            allTab.classList.add('border-transparent', 'text-gray-500');
            
            // Show selected tab content and add active styles
            if (tab === 'upcoming') {
                upcomingContent.classList.remove('hidden');
                upcomingContent.classList.add('active');
                upcomingTab.classList.remove('border-transparent', 'text-gray-500');
                upcomingTab.classList.add('border-orange-500', 'text-orange-600');
            } else if (tab === 'all') {
                allContent.classList.remove('hidden');
                allContent.classList.add('active');
                allTab.classList.remove('border-transparent', 'text-gray-500');
                allTab.classList.add('border-purple-500', 'text-purple-600');
            }
        }

        // Modal functions
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add Trainer';
            document.getElementById('formAction').value = 'add_trainer';
            document.getElementById('editTrainerId').value = '';
            document.querySelector('#trainerModal form').reset();
            document.getElementById('trainerModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('trainerModal').classList.add('hidden');
        }

        function openAssignmentModal() {
            document.getElementById('assignmentModal').classList.remove('hidden');
        }

        function closeAssignmentModal() {
            document.getElementById('assignmentModal').classList.add('hidden');
        }

        // Close modals when clicking outside
        const trainerModal = document.getElementById('trainerModal');
        const assignmentModal = document.getElementById('assignmentModal');
        
        if (trainerModal) {
            trainerModal.addEventListener('click', function(e) {
                if (e.target === trainerModal) closeModal();
            });
        }
        
        if (assignmentModal) {
            assignmentModal.addEventListener('click', function(e) {
                if (e.target === assignmentModal) closeAssignmentModal();
            });
        }

        // Close modals on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                if (!trainerModal.classList.contains('hidden')) closeModal();
                if (!assignmentModal.classList.contains('hidden')) closeAssignmentModal();
            }
        });

        function deleteTrainer(trainerId) {
            if (confirm('Are you sure you want to delete this trainer?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_trainer">
                    <input type="hidden" name="trainer_id" value="${trainerId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Edit trainer functionality
        <?php if ($edit_trainer): ?>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('modalTitle').textContent = 'Edit Trainer';
            document.getElementById('formAction').value = 'update_trainer';
            document.getElementById('editTrainerId').value = '<?php echo $edit_trainer['trainer_id']; ?>';
            document.getElementById('username').value = '<?php echo htmlspecialchars($edit_trainer['username']); ?>';
            document.getElementById('first_name').value = '<?php echo htmlspecialchars($edit_trainer['first_name']); ?>';
            document.getElementById('last_name').value = '<?php echo htmlspecialchars($edit_trainer['last_name']); ?>';
            document.getElementById('middle_name').value = '<?php echo htmlspecialchars($edit_trainer['middle_name']); ?>';
            document.getElementById('gender').value = '<?php echo htmlspecialchars($edit_trainer['gender']); ?>';
            document.getElementById('contact_number').value = '<?php echo htmlspecialchars($edit_trainer['contact_number']); ?>';
            document.getElementById('email').value = '<?php echo htmlspecialchars($edit_trainer['email']); ?>';
            document.getElementById('date_of_birth').value = '<?php echo $edit_trainer['date_of_birth']; ?>';
            document.getElementById('specialization').value = '<?php echo htmlspecialchars($edit_trainer['specialization']); ?>';
            // Removed certification, experience_years, hourly_rate JS
            document.getElementById('status').value = '<?php echo htmlspecialchars($edit_trainer['status']); ?>';
            document.getElementById('hire_date').value = '<?php echo $edit_trainer['hire_date']; ?>';
            document.getElementById('address').value = '<?php echo htmlspecialchars($edit_trainer['address']); ?>';
            document.getElementById('trainerModal').classList.remove('hidden');
        });
        <?php endif; ?>

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
    </script>

 <?php include '../footer.php'; ?>
