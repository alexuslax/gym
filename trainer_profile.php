<?php
session_start();
require_once 'config/functions.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_trainer') {
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
    
    header('Location: trainer_profile.php?id=' . $trainer_id . '&return=' . urlencode($_GET['return'] ?? 'staff_view/trainers.php') . '&success=Trainer updated successfully');
    exit();
}

// Get trainer ID from URL
$trainer_id = isset($_GET['id']) ? sanitizeInput($_GET['id']) : '';

// Get return URL or determine based on user role
$return_url = isset($_GET['return']) ? sanitizeInput($_GET['return']) : '';
if (empty($return_url)) {
    // Determine return URL based on user role
    if (isset($_SESSION['role'])) {
        switch ($_SESSION['role']) {
            case 'admin':
            case 'staff':
                $return_url = 'staff_view/trainers.php';
                break;
            default:
                $return_url = 'trainers.php';
        }
    } else {
        $return_url = 'trainers.php';
    }
}

if (empty($trainer_id)) {
    header('Location: ' . $return_url);
    exit();
}

// Get trainer details with profile picture from trainers table
$stmt = $pdo->prepare("SELECT t.* FROM trainers t WHERE t.trainer_id = ?");
$stmt->execute([$trainer_id]);
$trainer = $stmt->fetch();

if (!$trainer) {
    header('Location: ' . $return_url . '?error=Trainer not found');
    exit();
}

// Get trainer's assigned members
$stmt = $pdo->prepare("SELECT ta.*, 
    CONCAT(m.first_name, ' ', COALESCE(m.middle_name, ''), ' ', m.last_name) as member_name,
    m.member_id as member_id,
    m.membership_status
    FROM trainer_assignments ta
    JOIN members m ON ta.member_id COLLATE utf8mb4_unicode_ci = m.member_id COLLATE utf8mb4_unicode_ci
    WHERE ta.trainer_id = ? 
    ORDER BY ta.session_date DESC, ta.start_time DESC
    LIMIT 20");
$stmt->execute([$trainer_id]);
$assigned_members = $stmt->fetchAll();

// Get upcoming sessions
$stmt = $pdo->prepare("SELECT ta.*, 
    CONCAT(m.first_name, ' ', COALESCE(m.middle_name, ''), ' ', m.last_name) as member_name,
    m.member_id as member_id
    FROM trainer_assignments ta
    JOIN members m ON ta.member_id COLLATE utf8mb4_unicode_ci = m.member_id COLLATE utf8mb4_unicode_ci
    WHERE ta.trainer_id = ? AND ta.session_date >= CURDATE()
    ORDER BY ta.session_date ASC, ta.start_time ASC
    LIMIT 10");
$stmt->execute([$trainer_id]);
$upcoming_sessions = $stmt->fetchAll();

// Calculate statistics
$stats = [
    'total_sessions' => 0,
    'completed_sessions' => 0,
    'upcoming_sessions' => 0,
    'assigned_members' => 0
];

// Total sessions
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM trainer_assignments WHERE trainer_id = ?");
$stmt->execute([$trainer_id]);
$total_result = $stmt->fetch();
$stats['total_sessions'] = $total_result['total'] ?? 0;

// Completed sessions
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM trainer_assignments WHERE trainer_id = ? AND status = 'Completed'");
$stmt->execute([$trainer_id]);
$completed_result = $stmt->fetch();
$stats['completed_sessions'] = $completed_result['total'] ?? 0;

// Upcoming sessions
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM trainer_assignments WHERE trainer_id = ? AND session_date >= CURDATE()");
$stmt->execute([$trainer_id]);
$upcoming_result = $stmt->fetch();
$stats['upcoming_sessions'] = $upcoming_result['total'] ?? 0;

// Assigned members count
$stmt = $pdo->prepare("SELECT COUNT(DISTINCT member_id) as total FROM trainer_assignments WHERE trainer_id = ?");
$stmt->execute([$trainer_id]);
$members_result = $stmt->fetch();
$stats['assigned_members'] = $members_result['total'] ?? 0;

// Calculate experience
$experience_text = 'N/A';
if (!empty($trainer['hire_date'])) {
    $hire_date = new DateTime($trainer['hire_date']);
    $today = new DateTime();
    $experience = $today->diff($hire_date);
    if ($experience->y > 0) {
        $experience_text = $experience->y . ' year' . ($experience->y > 1 ? 's' : '');
    } elseif ($experience->m > 0) {
        $experience_text = $experience->m . ' month' . ($experience->m > 1 ? 's' : '');
    } else {
        $experience_text = 'Less than a month';
    }
}

$trainer_full_name = trim($trainer['first_name'] . ' ' . ($trainer['middle_name'] ? $trainer['middle_name'] . ' ' : '') . $trainer['last_name']);

$page_title = htmlspecialchars($trainer_full_name) . ' - Trainer Profile - UEP Fitness Gym';
include 'header.php';
?>

<link rel="stylesheet" href="css/trainer_profile.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="trainer-profile-container">

        <!-- Success Message -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="alert-icon">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                </svg>
                <span class="alert-message"><?php echo htmlspecialchars($_GET['success']); ?></span>
            </div>
        <?php endif; ?>

        <!-- Error Message -->
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="alert-icon">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                </svg>
                <span class="alert-message"><?php echo htmlspecialchars($_GET['error']); ?></span>
            </div>
        <?php endif; ?>

        <!-- Back Button -->
        <a href="<?php echo htmlspecialchars($return_url); ?>" class="back-btn">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Back to Trainers
        </a>

        <!-- Trainer Header -->
        <div class="profile-header">
            <div class="profile-header-inner">
                <div class="profile-header-content">
                    <div class="profile-left-section">
                        <div class="profile-avatar-wrapper">
                            <?php 
                            $profile_pic = isset($trainer['profile_picture']) ? trim($trainer['profile_picture']) : '';
                            $initial = strtoupper(substr($trainer['first_name'], 0, 1));
                            
                            $pic_path = '';
                            if (!empty($profile_pic)) {
                                if (strpos($profile_pic, 'profiles/') === 0) {
                                    $pic_path = 'img/' . $profile_pic;
                                } elseif (strpos($profile_pic, 'img/') === 0) {
                                    $pic_path = $profile_pic;
                                } else {
                                    $pic_path = 'img/profiles/' . $profile_pic;
                                }
                            }
                            
                            if (!empty($pic_path) && file_exists($pic_path)): 
                            ?>
                                <img src="<?php echo htmlspecialchars($pic_path); ?>" alt="Profile" class="profile-avatar-img">
                            <?php else: ?>
                                <img src="img/user.png" alt="Profile" class="profile-avatar-img">
                            <?php endif; ?>
                        </div>
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'staff'): ?>
                        <button type="button" id="editTrainerBtn" class="btn-edit-member" onclick="openEditTrainerModal()">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            Edit Trainer
                        </button>
                        <?php endif; ?>
                    </div>
                    <div class="profile-right-section">
                        <h1 class="profile-name-large"><?php echo htmlspecialchars($trainer_full_name); ?></h1>
                        <p class="profile-id-large"><?php echo htmlspecialchars($trainer['trainer_id']); ?></p>
                        <div class="profile-status-badges">
                            <?php
                            $status_badges = [
                                'Active' => 'badge-green',
                                'Inactive' => 'badge-gray',
                                'On Leave' => 'badge-yellow'
                            ];
                            $badge_class = $status_badges[$trainer['status']] ?? 'badge-gray';
                            ?>
                            <span class="badge <?php echo $badge_class; ?>">
                                <?php echo htmlspecialchars($trainer['status']); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Trainer Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon stat-icon-blue">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5A2.25 2.25 0 0 1 5.25 5.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25M3 18.75A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75M3 18.75v-8.25A2.25 2.25 0 0 1 5.25 8.25h13.5A2.25 2.25 0 0 1 21 10.5v8.25"/>
                    </svg>
                </div>
                <h3 class="stat-label">Total Sessions</h3>
                <p class="stat-value"><?php echo $stats['total_sessions']; ?></p>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon-green">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                    </svg>
                </div>
                <h3 class="stat-label">Completed</h3>
                <p class="stat-value"><?php echo $stats['completed_sessions']; ?></p>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon-purple">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                    </svg>
                </div>
                <h3 class="stat-label">Upcoming</h3>
                <p class="stat-value"><?php echo $stats['upcoming_sessions']; ?></p>
            </div>
            <div class="stat-card">
                <div class="stat-icon stat-icon-orange">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.5v-1.125A3.375 3.375 0 0 0 11.625 15h-3.75A3.375 3.375 0 0 0 4.5 18.375V19.5M12 10.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0M18 8.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0M20.25 19.5v-1.125A3.375 3.375 0 0 0 17.25 15h-1.125"/>
                    </svg>
                </div>
                <h3 class="stat-label">Assigned Members</h3>
                <p class="stat-value"><?php echo $stats['assigned_members']; ?></p>
            </div>
        </div>

        <!-- Trainer Details -->
        <div class="details-grid">
            <!-- Personal Information -->
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0zM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                        </svg>
                        Personal Information
                    </h3>
                </div>
                <div class="detail-card-content">
                    <div class="info-row">
                        <div class="info-item">
                            <p class="info-label">Gender</p>
                            <p class="info-value"><?php echo htmlspecialchars($trainer['gender']); ?></p>
                        </div>
                        <div class="info-item">
                            <p class="info-label">Date of Birth</p>
                            <p class="info-value"><?php echo formatDate($trainer['date_of_birth']); ?></p>
                        </div>
                    </div>
                    <div class="info-item">
                        <p class="info-label">Contact Number</p>
                        <p class="info-value"><?php echo htmlspecialchars($trainer['contact_number']); ?></p>
                    </div>
                    <div class="info-item">
                        <p class="info-label">Email</p>
                        <p class="info-value"><?php echo htmlspecialchars($trainer['email']); ?></p>
                    </div>
                    <div class="info-item">
                        <p class="info-label">Address</p>
                        <p class="info-value"><?php echo htmlspecialchars($trainer['address']); ?></p>
                    </div>
                </div>
            </div>

            <!-- Professional Information -->
            <div class="detail-card">
                <div class="detail-card-header">
                    <h3 class="detail-card-title">Professional Information</h3>
                </div>
                <div class="detail-card-content">
                    <div class="info-item">
                        <p class="info-label">Specialization</p>
                        <p class="info-value"><?php echo htmlspecialchars($trainer['specialization'] ?? 'N/A'); ?></p>
                    </div>
                    <?php if (!empty($trainer['certification'])): ?>
                    <div class="info-item">
                        <p class="info-label">Certification</p>
                        <p class="info-value"><?php echo htmlspecialchars($trainer['certification']); ?></p>
                    </div>
                    <?php endif; ?>
                    <div class="info-item">
                        <p class="info-label">Experience</p>
                        <p class="info-value"><?php echo htmlspecialchars($experience_text); ?></p>
                    </div>
                    <div class="info-item">
                        <p class="info-label">Hire Date</p>
                        <p class="info-value"><?php echo formatDate($trainer['hire_date']); ?></p>
                    </div>
                    <?php if (!empty($trainer['hourly_rate'])): ?>
                    <div class="info-item">
                        <p class="info-label">Hourly Rate</p>
                        <p class="info-value">$<?php echo number_format($trainer['hourly_rate'], 2); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Upcoming Sessions -->
        <?php if (!empty($upcoming_sessions)): ?>
        <div class="table-container">
            <div class="table-header">
                <h3 class="table-title">Upcoming Sessions</h3>
            </div>
            <div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Member</th>
                            <th>Session Type</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($upcoming_sessions as $session): ?>
                        <tr>
                            <td>
                                <a href="member_profile.php?id=<?php echo $session['member_id']; ?>&return=trainer_profile.php?id=<?php echo $trainer_id; ?>">
                                    <?php echo htmlspecialchars($session['member_name']); ?>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars($session['session_type']); ?></td>
                            <td><?php echo formatDate($session['session_date']); ?></td>
                            <td>
                                <?php echo date('g:i A', strtotime($session['start_time'])); ?> - <?php echo date('g:i A', strtotime($session['end_time'])); ?>
                            </td>
                            <td>
                                <?php 
                                $status_class = 'status-scheduled';
                                if ($session['status'] === 'Completed') $status_class = 'status-completed';
                                if ($session['status'] === 'Cancelled') $status_class = 'status-cancelled';
                                ?>
                                <span class="status-badge <?php echo $status_class; ?>">
                                    <?php echo htmlspecialchars($session['status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Assigned Members -->
        <?php if (!empty($assigned_members)): ?>
        <div class="table-container" style="margin-top: 2rem;">
            <div class="table-header">
                <h3 class="table-title">Assigned Members</h3>
            </div>
            <div style="padding: 1.5rem;">
                <div class="members-grid">
                    <?php 
                    $unique_members = [];
                    foreach ($assigned_members as $assignment) {
                        if (!isset($unique_members[$assignment['member_id']])) {
                            $unique_members[$assignment['member_id']] = $assignment;
                        }
                    }
                    foreach ($unique_members as $member): 
                    ?>
                    <a href="member_profile.php?id=<?php echo $member['member_id']; ?>&return=trainer_profile.php?id=<?php echo $trainer_id; ?>" class="member-card">
                        <div class="member-name"><?php echo htmlspecialchars($member['member_name']); ?></div>
                        <div class="member-id"><?php echo htmlspecialchars($member['member_id']); ?></div>
                        <div style="margin-top: 0.5rem;">
                            <?php 
                            $status_class = $member['membership_status'] === 'Active' ? 'status-active' : 'status-inactive';
                            ?>
                            <span class="status-badge <?php echo $status_class; ?>">
                                <?php echo htmlspecialchars($member['membership_status']); ?>
                            </span>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
</div>

    <script>
        // Edit Trainer Modal functionality
        function openEditTrainerModal() {
            const trainer = <?php echo json_encode($trainer, JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
            document.getElementById('editTrainerId').value = trainer.trainer_id;
            document.getElementById('modal_username').value = trainer.username || '';
            document.getElementById('modal_first_name').value = trainer.first_name || '';
            document.getElementById('modal_last_name').value = trainer.last_name || '';
            document.getElementById('modal_middle_name').value = trainer.middle_name || '';
            document.getElementById('modal_gender').value = trainer.gender || '';
            document.getElementById('modal_contact_number').value = trainer.contact_number || '';
            document.getElementById('modal_email').value = trainer.email || '';
            document.getElementById('modal_date_of_birth').value = trainer.date_of_birth || '';
            document.getElementById('modal_specialization').value = trainer.specialization || '';
            document.getElementById('modal_status').value = trainer.status || 'Active';
            document.getElementById('modal_hire_date').value = trainer.hire_date || '';
            document.getElementById('modal_address').value = trainer.address || '';
            document.getElementById('trainerModal').classList.remove('hidden');
        }

        function closeTrainerModal() {
            document.getElementById('trainerModal').classList.add('hidden');
        }

        // Close modal when clicking outside
        const trainerModal = document.getElementById('trainerModal');
        if (trainerModal) {
            trainerModal.addEventListener('click', function(e) {
                if (e.target === trainerModal) {
                    closeTrainerModal();
                }
            });
        }

        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && trainerModal && !trainerModal.classList.contains('hidden')) {
                closeTrainerModal();
            }
        });
    </script>

    <!-- Edit Trainer Modal -->
    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'staff'): ?>
    <div id="trainerModal" class="modal-overlay hidden">
        <div class="modal-content" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">Edit Trainer</h3>
                <button type="button" onclick="closeTrainerModal()" class="modal-close-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form method="POST" class="modal-form">
                <input type="hidden" name="action" value="update_trainer">
                <input type="hidden" name="trainer_id" id="editTrainerId" value="<?php echo $trainer['trainer_id']; ?>">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" id="modal_username" required class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">First Name</label>
                        <input type="text" name="first_name" id="modal_first_name" required class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Last Name</label>
                        <input type="text" name="last_name" id="modal_last_name" required class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Middle Name <span class="form-label-optional">(Optional)</span></label>
                        <input type="text" name="middle_name" id="modal_middle_name" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Gender</label>
                        <select name="gender" id="modal_gender" required class="form-select">
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Contact Number</label>
                        <input type="tel" name="contact_number" id="modal_contact_number" required class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" id="modal_email" required class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date of Birth</label>
                        <input type="date" name="date_of_birth" id="modal_date_of_birth" required class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Specialization</label>
                        <input type="text" name="specialization" id="modal_specialization" class="form-input" placeholder="e.g., Weight Training, Cardio, Yoga">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" id="modal_status" required class="form-select">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                            <option value="On Leave">On Leave</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Hire Date</label>
                        <input type="date" name="hire_date" id="modal_hire_date" required class="form-input">
                    </div>
                    <div class="form-group form-group-full">
                        <label class="form-label">Address</label>
                        <textarea name="address" id="modal_address" required rows="3" class="form-textarea"></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" onclick="closeTrainerModal()" class="btn-cancel">
                        Cancel
                    </button>
                    <button type="submit" class="btn-submit">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

<?php include 'footer.php'; ?>

