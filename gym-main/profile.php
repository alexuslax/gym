<?php
session_start();
require_once 'config/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Get user details from database
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Get profile picture from appropriate table based on role
$profile_picture = null;
if ($_SESSION['role'] === 'member') {
    $stmt = $pdo->prepare("SELECT profile_picture FROM members WHERE username = ? OR user_id = ? LIMIT 1");
    $stmt->execute([$_SESSION['username'], $_SESSION['user_id']]);
    $result = $stmt->fetch();
    if ($result && $result['profile_picture']) {
        $profile_picture = $result['profile_picture'];
    }
} elseif ($_SESSION['role'] === 'trainer') {
    $stmt = $pdo->prepare("SELECT profile_picture FROM trainers WHERE user_id = ? LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch();
    if ($result && $result['profile_picture']) {
        $profile_picture = $result['profile_picture'];
    }
} else {
    // Admin role - use profile picture from users table
    if ($user['profile_picture']) {
        $profile_picture = $user['profile_picture'];
    }
}

// Get member details if user is a member (based on role or member_id)
$member = null;
if (isset($_SESSION['member_id']) || $_SESSION['role'] === 'member') {
    // Try to find member by user_id or username and get plan from billing table
    $stmt = $pdo->prepare("
        SELECT m.*, mp.plan_name
        FROM members m
        LEFT JOIN (
            SELECT member_id, plan_id, MAX(created_at) as latest
            FROM billing
            GROUP BY member_id
        ) latest_billing ON m.member_id = latest_billing.member_id
        LEFT JOIN billing b ON latest_billing.member_id = b.member_id AND latest_billing.latest = b.created_at
        LEFT JOIN membership_plans mp ON b.plan_id = mp.plan_id
        WHERE m.username = ? OR m.user_id = ?
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['username'], $_SESSION['user_id']]);
    $member = $stmt->fetch();
}

$page_title = 'Profile - UEP Fitness Gym';
include 'header.php';
?>

<div class="max-w-6xl mx-auto">
  <!-- Page Header -->
  <div class="mb-8">
    <h2 class="text-3xl md:text-4xl font-bold tracking-tight mb-2 bg-gradient-to-r from-slate-900 via-blue-800 to-slate-900 bg-clip-text text-transparent">My Profile</h2>
    <p class="text-gray-600 text-lg">View and manage your account information</p>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Profile Card -->
    <div class="lg:col-span-1">
      <div class="bg-white rounded-2xl shadow-lg ring-1 ring-gray-200/50 overflow-hidden">
        <div class="bg-gradient-to-br from-blue-600 to-blue-700 px-6 py-8 text-center">
          <div class="inline-block relative">
            <div class="w-32 h-32 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center mx-auto mb-4 ring-4 ring-white/30">
              <?php if ($profile_picture): ?>
                <?php
                  // Ensure proper path format
                  $pic_path = $profile_picture;
                  if (strpos($pic_path, 'profiles/') === 0) {
                    $pic_path = 'img/' . $pic_path;
                  } elseif (strpos($pic_path, 'img/') !== 0) {
                    $pic_path = 'img/profiles/' . $pic_path;
                  }
                  
                  // Check if file exists
                  if (file_exists($pic_path)) {
                    echo '<img src="' . htmlspecialchars($pic_path) . '" alt="Profile" class="w-full h-full rounded-full object-cover">';
                  } else {
                    echo '<img src="img/user.png" alt="Profile" class="w-20 h-20 rounded-full object-cover opacity-80">';
                  }
                ?>
              <?php else: ?>
                <img src="img/user.png" 
                     alt="Profile" 
                     class="w-20 h-20 rounded-full object-cover opacity-80">
              <?php endif; ?>
            </div>
          </div>
          <h3 class="text-2xl font-bold text-white mb-1"><?php echo htmlspecialchars($user['full_name']); ?></h3>
          <p class="text-blue-100 text-sm mb-3">@<?php echo htmlspecialchars($user['username']); ?></p>
          <span class="inline-block px-4 py-1.5 bg-white/20 backdrop-blur-sm text-white text-sm font-medium rounded-full border border-white/30">
            <?php echo ucfirst($user['role']); ?>
          </span>
        </div>

        <div class="p-6 space-y-4">
          <div class="flex items-center gap-3 text-gray-700">
            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5 text-blue-600">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/>
              </svg>
            </div>
            <div class="min-w-0 flex-1">
              <p class="text-xs text-gray-500 mb-0.5">Email</p>
              <p class="text-sm font-medium truncate"><?php echo htmlspecialchars($user['email']); ?></p>
            </div>
          </div>

          <div class="flex items-center gap-3 text-gray-700">
            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5 text-green-600">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
              </svg>
            </div>
            <div class="min-w-0 flex-1">
              <p class="text-xs text-gray-500 mb-0.5">Account Status</p>
              <p class="text-sm font-medium"><?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?></p>
            </div>
          </div>

          <div class="flex items-center gap-3 text-gray-700">
            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center flex-shrink-0">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5 text-purple-600">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/>
              </svg>
            </div>
            <div class="min-w-0 flex-1">
              <p class="text-xs text-gray-500 mb-0.5">Member Since</p>
              <p class="text-sm font-medium"><?php echo date('F j, Y', strtotime($user['created_at'])); ?></p>
            </div>
          </div>
        </div>

        <div class="px-6 pb-6">
          <a href="settings.php" class="block w-full text-center px-4 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl hover:from-blue-700 hover:to-blue-800 shadow-md hover:shadow-lg font-medium transition-all duration-200">
            Edit Profile
          </a>
        </div>
      </div>
    </div>

    <!-- Details Section -->
    <div class="lg:col-span-2 space-y-6">
      <!-- Account Information -->
      <div class="bg-white rounded-2xl shadow-lg ring-1 ring-gray-200/50 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-200 bg-gradient-to-r from-white to-gray-50">
          <h3 class="text-xl font-bold text-gray-900">Account Information</h3>
        </div>
        <div class="p-6">
          <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <dt class="text-sm font-medium text-gray-500 mb-1">User ID</dt>
              <dd class="text-base font-semibold text-gray-900"><?php echo htmlspecialchars($user['user_id']); ?></dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-gray-500 mb-1">Username</dt>
              <dd class="text-base font-semibold text-gray-900"><?php echo htmlspecialchars($user['username']); ?></dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-gray-500 mb-1">Full Name</dt>
              <dd class="text-base font-semibold text-gray-900"><?php echo htmlspecialchars($user['full_name']); ?></dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-gray-500 mb-1">Email Address</dt>
              <dd class="text-base font-semibold text-gray-900"><?php echo htmlspecialchars($user['email']); ?></dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-gray-500 mb-1">Role</dt>
              <dd class="text-base font-semibold text-gray-900">
                <span class="inline-block px-3 py-1 bg-blue-100 text-blue-800 rounded-lg text-sm font-medium">
                  <?php echo ucfirst($user['role']); ?>
                </span>
              </dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-gray-500 mb-1">Account Created</dt>
              <dd class="text-base font-semibold text-gray-900"><?php echo date('F j, Y', strtotime($user['created_at'])); ?></dd>
            </div>
          </dl>
        </div>
      </div>

      <!-- Membership Information (if member) -->
      <?php if ($member): ?>
      <div class="bg-white rounded-2xl shadow-lg ring-1 ring-gray-200/50 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-200 bg-gradient-to-r from-white to-gray-50">
          <h3 class="text-xl font-bold text-gray-900">Membership Information</h3>
        </div>
        <div class="p-6">
          <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <dt class="text-sm font-medium text-gray-500 mb-1">Member ID</dt>
              <dd class="text-base font-semibold text-gray-900"><?php echo htmlspecialchars($member['member_id']); ?></dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-gray-500 mb-1">Membership Plan</dt>
              <dd class="text-base font-semibold text-gray-900">
                <span class="inline-block px-3 py-1 bg-green-100 text-green-800 rounded-lg text-sm font-medium">
                  <?php echo htmlspecialchars($member['plan_name'] ?? 'Not Assigned'); ?>
                </span>
              </dd>
            </div>
            <div>
              <dt class="text-sm font-medium text-gray-500 mb-1">Membership Status</dt>
              <dd class="text-base font-semibold text-gray-900">
                <?php
                $statusColors = [
                  'Active' => 'bg-green-100 text-green-800',
                  'Pending' => 'bg-yellow-100 text-yellow-800',
                  'Expired' => 'bg-red-100 text-red-800',
                  'Suspended' => 'bg-gray-100 text-gray-800'
                ];
                $statusColor = $statusColors[$member['membership_status']] ?? 'bg-gray-100 text-gray-800';
                ?>
                <span class="inline-block px-3 py-1 <?php echo $statusColor; ?> rounded-lg text-sm font-medium">
                  <?php echo htmlspecialchars($member['membership_status']); ?>
                </span>
              </dd>
            </div>
            <?php if ($member['membership_start_date']): ?>
            <div>
              <dt class="text-sm font-medium text-gray-500 mb-1">Start Date</dt>
              <dd class="text-base font-semibold text-gray-900"><?php echo date('F j, Y', strtotime($member['membership_start_date'])); ?></dd>
            </div>
            <?php endif; ?>
            <?php if ($member['membership_end_date']): ?>
            <div>
              <dt class="text-sm font-medium text-gray-500 mb-1">End Date</dt>
              <dd class="text-base font-semibold text-gray-900"><?php echo date('F j, Y', strtotime($member['membership_end_date'])); ?></dd>
            </div>
            <?php endif; ?>
            <?php if ($member['registration_date']): ?>
            <div>
              <dt class="text-sm font-medium text-gray-500 mb-1">Registration Date</dt>
              <dd class="text-base font-semibold text-gray-900"><?php echo date('F j, Y', strtotime($member['registration_date'])); ?></dd>
            </div>
            <?php endif; ?>
            <?php if ($member['rfid_card_number']): ?>
            <div>
              <dt class="text-sm font-medium text-gray-500 mb-1">RFID Card Number</dt>
              <dd class="text-base font-semibold text-gray-900 font-mono"><?php echo htmlspecialchars($member['rfid_card_number']); ?></dd>
            </div>
            <?php endif; ?>
          </dl>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>

