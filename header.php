<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Prevent warnings for undefined 'role'
$role = $_SESSION['role'] ?? null;

// Detect if we're in a subdirectory (staff_view, student_view, faculty_view) or root
$script_path = $_SERVER['SCRIPT_NAME'] ?? '';
$is_subdirectory = (strpos($script_path, '/staff_view/') !== false || 
                   strpos($script_path, '/student_view/') !== false || 
                   strpos($script_path, '/faculty_view/') !== false);

// Set path prefixes based on context
$img_prefix = $is_subdirectory ? '../img/' : 'img/';
$base_path = $is_subdirectory ? '..' : '.';
$profile_link = $is_subdirectory ? '../profile.php' : 'profile.php';
$settings_link = $is_subdirectory ? '../settings.php' : 'settings.php';
$help_link = $is_subdirectory ? '../help.php' : 'help.php';
$logout_link = $is_subdirectory ? '../logout.php' : 'logout.php';

// Get user's profile picture
$user_profile_pic = $img_prefix . 'user.png';
if (isset($_SESSION['user_id'])) {
    require_once $base_path . '/config/functions.php';
    
    try {
        // Fetch profile picture based on role
        $pic_path = null;
        
        if ($role === 'student') {
            // For students, get profile_picture from members table
            $stmt = $pdo->prepare('SELECT profile_picture FROM members WHERE user_id = ? LIMIT 1');
            $stmt->execute([$_SESSION['user_id']]);
            $member_data = $stmt->fetch();
            $pic_path = $member_data['profile_picture'] ?? null;
        } elseif ($role === 'faculty') {
            // For faculty, get profile_picture from trainers table by joining with users table
            $stmt = $pdo->prepare('SELECT t.profile_picture 
                                   FROM users u 
                                   INNER JOIN trainers t ON u.username COLLATE utf8mb4_unicode_ci = t.username COLLATE utf8mb4_unicode_ci 
                                   WHERE u.user_id = ? LIMIT 1');
            $stmt->execute([$_SESSION['user_id']]);
            $trainer_data = $stmt->fetch();
            $pic_path = $trainer_data['profile_picture'] ?? null;
        } else {
            // For admin/staff, get from users table
            $stmt = $pdo->prepare('SELECT profile_picture FROM users WHERE user_id = ? LIMIT 1');
            $stmt->execute([$_SESSION['user_id']]);
            $user_data = $stmt->fetch();
            $pic_path = $user_data['profile_picture'] ?? null;
        }
        
        if (!empty($pic_path)) {
            // If path is just a filename (no directory), assume it's in img/profiles/
            if (strpos($pic_path, '/') === false && strpos($pic_path, '\\') === false) {
                $pic_path = 'img/profiles/' . $pic_path;
            }
            // If path starts with img/ but not img/profiles/, fix it
            elseif (strpos($pic_path, 'img/') === 0 && strpos($pic_path, 'img/profiles/') !== 0) {
                // Extract just the filename
                $filename = basename($pic_path);
                $pic_path = 'img/profiles/' . $filename;
            }
            
            // Check if file actually exists
            $file_to_check = $is_subdirectory ? '../' . $pic_path : $pic_path;
            if (file_exists($file_to_check)) {
                if (strpos($pic_path, 'img/') === 0) {
                    // Already has img/ prefix, adjust based on current directory
                    if ($is_subdirectory) {
                        $user_profile_pic = '../' . $pic_path;
                    } else {
                        $user_profile_pic = $pic_path;
                    }
                } else {
                    // Filename only, add the appropriate prefix
                    $user_profile_pic = $img_prefix . $pic_path;
                }
            }
            // If file doesn't exist, keep default user.png
        }
    } catch (PDOException $e) {
        // If profile_picture column doesn't exist, just use default user.png
        $user_profile_pic = $img_prefix . 'user.png';
    }
}

// Determine the view directory based on role (for navigation from root)
$view_dir = '';
if (!$is_subdirectory && $role) {
    switch ($role) {
        case 'staff':
            $view_dir = 'staff_view/';
            break;
        case 'student':
            $view_dir = 'student_view/';
            break;
        case 'faculty':
            $view_dir = 'faculty_view/';
            break;
    }
}
// If we're already in a subdirectory, determine nav_prefix based on role
// Staff should always navigate to staff_view, even when viewing trainer_view pages
if ($is_subdirectory) {
    if ($role === 'staff' && strpos($script_path, '/faculty_view/') !== false) {
        // Staff viewing faculty_view pages - navigate to staff_view
        $nav_prefix = '../staff_view/';
    } elseif ($role === 'staff' && strpos($script_path, '/staff_view/') !== false) {
        // Staff in staff_view - use current directory
        $nav_prefix = '';
    } elseif ($role === 'faculty' && strpos($script_path, '/faculty_view/') !== false) {
        // Faculty in faculty_view - use current directory
        $nav_prefix = '';
    } elseif ($role === 'student' && strpos($script_path, '/student_view/') !== false) {
        // Student in student_view - use current directory
        $nav_prefix = '';
    } else {
        // Default: use current directory
        $nav_prefix = '';
    }
} else {
    // Not in a subdirectory, use view_dir
    $nav_prefix = $view_dir;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo isset($page_title) ? $page_title : 'UEP Fitness Gym'; ?></title>
  <link rel="stylesheet" href="<?php echo $is_subdirectory ? '../css/styles.css' : 'css/styles.css'; ?>">
  <?php if ($role === 'faculty'): ?>
  <link rel="stylesheet" href="<?php echo $is_subdirectory ? '../css/faculty.css' : 'css/faculty.css'; ?>">
  <?php endif; ?>
</head>

<body>

  <!-- Navbar -->
  <nav class="navbar">

    <!-- Logo + Title -->
<div class="navbar-logo-container">
    <div class="navbar-logo">
        <img src="<?php echo $img_prefix; ?>logo.jpg"
             alt="UEP Logo"
             class="h-full w-full object-cover">
    </div>
    <h1 class="navbar-title">
        UEP Fitness Gym
    </h1>
</div>


    <!-- Desktop Navigation -->
    <ul class="navbar-nav">
      <li><a href="<?php echo $nav_prefix; ?>dashboard.php">Home</a></li>

      <?php if ($role === 'staff'): ?>
        <li><a href="<?php echo $nav_prefix; ?>members.php">Members</a></li>
        <li><a href="<?php echo $nav_prefix; ?>attendance.php">Attendance</a></li>
        <li><a href="<?php echo $nav_prefix; ?>equipment.php">Equipment</a></li>
        <li><a href="<?php echo $nav_prefix; ?>billing.php">Billing</a></li>
        <li><a href="<?php echo $nav_prefix; ?>trainers.php">Trainers</a></li>
        <li><a href="<?php 
            if (strpos($script_path, '/staff_view/') !== false) {
                echo 'progress.php';
            } elseif (strpos($script_path, '/faculty_view/') !== false) {
                echo './progress.php';
            } else {
                echo 'staff_view/progress.php';
            }
        ?>">Progress</a></li>
        <li><a href="<?php 
            if (strpos($script_path, '/staff_view/') !== false) {
                echo 'vitals.php';
            } elseif (strpos($script_path, '/faculty_view/') !== false) {
                echo './vitals.php';
            } else {
                echo 'staff_view/vitals.php';
            }
        ?>">Vital Signs</a></li>

      <?php elseif ($role === 'student'): ?>
        <li><a href="<?php echo $nav_prefix; ?>attendance.php">Attendance</a></li>
        <li><a href="<?php echo $nav_prefix; ?>billing.php">Billing</a></li>
        <li><a href="<?php echo $nav_prefix; ?>vitals.php">Vital Signs</a></li>
        <li><a href="<?php echo $nav_prefix; ?>progress.php">Progress</a></li>

      <?php elseif ($role === 'faculty'): ?>
        <li><a href="<?php echo $nav_prefix; ?>attendance.php">Attendance</a></li>
        <li><a href="<?php echo $nav_prefix; ?>members.php">Students</a></li>
        <li><a href="<?php echo $nav_prefix; ?>progress.php">Progress</a></li>
        <li><a href="<?php echo $nav_prefix; ?>vitals.php">Vital Signs</a></li>
        <li><a href="<?php echo $nav_prefix; ?>schedule.php">Schedule</a></li>
      <?php endif; ?>
    </ul>

    <!-- User Menu (Desktop) -->
    <div class="navbar-user-menu">
      <button id="user-menu-btn" class="navbar-user-btn">
        <img src="<?php echo $user_profile_pic; ?>" alt="User" class="navbar-user-img">
      </button>
      <div id="user-menu" class="navbar-user-dropdown">
        <a href="<?php echo $profile_link; ?>">Profile</a>
        <a href="<?php echo $settings_link; ?>">Settings</a>
        <a href="<?php echo $help_link; ?>">Help</a>
        <a href="<?php echo $logout_link; ?>" class="logout">Log out</a>
      </div>
    </div>

    <!-- Mobile Menu Button -->
    <button id="mobile-menu-btn" class="navbar-mobile-btn">☰</button>
  </nav>

  <!-- Mobile Sidebar Overlay -->
  <div id="mobile-menu-overlay" class="navbar-sidebar-overlay"></div>

  <!-- Mobile Sidebar Menu -->
  <ul id="mobile-menu" class="navbar-mobile-menu">
    <li><button id="mobile-menu-close" class="navbar-sidebar-close">✕</button></li>

      <li><a href="<?php echo $nav_prefix; ?>dashboard.php">Home</a></li>

      <?php if ($role === 'staff'): ?>
      <li><a href="<?php echo $nav_prefix; ?>members.php">Members</a></li>
      <li><a href="<?php echo $nav_prefix; ?>attendance.php">Attendance</a></li>
      <li><a href="<?php echo $nav_prefix; ?>equipment.php">Equipment</a></li>
      <li><a href="<?php echo $nav_prefix; ?>billing.php">Billing</a></li>
      <li><a href="<?php echo $nav_prefix; ?>trainers.php">Trainers</a></li>
      <li><a href="<?php 
            if (strpos($script_path, '/staff_view/') !== false) {
                echo 'vitals.php';
            } elseif (strpos($script_path, '/faculty_view/') !== false) {
                echo './vitals.php';
            } else {
                echo 'staff_view/vitals.php';
            }
        ?>">Vital Signs</a></li>
      <li><a href="<?php 
            if (strpos($script_path, '/staff_view/') !== false) {
                echo 'progress.php';
            } elseif (strpos($script_path, '/faculty_view/') !== false) {
                echo './progress.php';
            } else {
                echo 'staff_view/progress.php';
            }
        ?>">Progress</a></li>

    <?php elseif ($role === 'student'): ?>
      <li><a href="<?php echo $nav_prefix; ?>attendance.php">Attendance</a></li>
      <li><a href="<?php echo $nav_prefix; ?>billing.php">Billing</a></li>
      <li><a href="<?php echo $nav_prefix; ?>vitals.php">Vital Signs</a></li>
      <li><a href="<?php echo $nav_prefix; ?>progress.php">Progress</a></li>

    <?php elseif ($role === 'faculty'): ?>
      <li><a href="<?php echo $nav_prefix; ?>attendance.php">Attendance</a></li>
      <li><a href="<?php echo $nav_prefix; ?>members.php">Students</a></li>
      <li><a href="<?php echo $nav_prefix; ?>progress.php">Progress</a></li>
      <li><a href="<?php echo $nav_prefix; ?>schedule.php">Schedule</a></li>
    <?php endif; ?>

    <li><a href="<?php echo $profile_link; ?>">Profile</a></li>
    <li><a href="<?php echo $settings_link; ?>">Settings</a></li>
    <li><a href="<?php echo $help_link; ?>">Help</a></li>
    <li><a href="<?php echo $logout_link; ?>" class="logout">Logout</a></li>

  </ul>

  <!-- Page Wrapper -->
  <div class="container page-wrapper">
