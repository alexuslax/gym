<?php
session_start();
require_once 'config/functions.php';

$error_message = '';

// Retrieve error messages from session (set by POST redirects)
if (isset($_SESSION['login_error'])) {
    $error_message = $_SESSION['login_error'];
    unset($_SESSION['login_error']);
} elseif (isset($_SESSION['signup_error'])) {
    $error_message = $_SESSION['signup_error'];
    unset($_SESSION['signup_error']);
}

// Handle login or signup form submission (POST requests)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // If login form submitted
    if (isset($_POST['username']) && isset($_POST['password'])) {
        $username = sanitizeInput($_POST['username']);
        $password = $_POST['password'];
        if (!empty($username) && !empty($password)) {
            try {
                $stmt = $pdo->prepare("SELECT user_id, username, password, full_name, role, is_active FROM users WHERE username = ? AND is_active = 1");
                $stmt->execute([$username]);
                $user = $stmt->fetch();
                if ($user && password_verify($password, $user['password'])) {
                    // Login successful
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['login_time'] = time();
                    // Log the login
                    $stmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, table_name, record_id, ip_address, user_agent) VALUES (?, 'LOGIN', 'users', ?, ?, ?)");
                    $stmt->execute([$user['user_id'], $user['user_id'], $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);
                    // Redirect based on role
                    if (in_array($user['role'], ['admin','staff'], true)) {
                        // Legacy: treat admin as staff during migration
                        header('Location: staff_view/dashboard.php');
                    } elseif ($user['role'] === 'trainer') {
                        header('Location: trainer_view/dashboard.php');
                    } else {
                        header('Location: member_view/dashboard.php');
                    }
                    exit();
                } else {
                    // Store error in session and redirect (Post-Redirect-Get pattern)
                    $_SESSION['login_error'] = 'Invalid username or password.';
                    header('Location: login.php');
                    exit();
                }
            } catch (PDOException $e) {
                $_SESSION['login_error'] = 'Database error. Please try again.';
                header('Location: login.php');
                exit();
            }
        } else {
            $_SESSION['login_error'] = 'Please enter both username and password.';
            header('Location: login.php');
            exit();
        }
    }
    // If signup form submitted
    elseif (isset($_POST['signup_username'])) {
        $signup_type = isset($_POST['signup_type']) ? sanitizeInput($_POST['signup_type']) : 'member';
        
        // Collect and sanitize common fields
        $signup_username = sanitizeInput($_POST['signup_username']);
        $signup_email = sanitizeInput($_POST['signup_email']);
        $signup_password = $_POST['signup_password'];
        $signup_confirm_password = $_POST['signup_confirm_password'];
        $signup_first_name = sanitizeInput($_POST['signup_first_name']);
        $signup_last_name = sanitizeInput($_POST['signup_last_name']);
        $signup_middle_name = isset($_POST['signup_middle_name']) ? sanitizeInput($_POST['signup_middle_name']) : '';
        $signup_gender = sanitizeInput($_POST['signup_gender']);
        $signup_dob = sanitizeInput($_POST['signup_dob']);
        $signup_contact = sanitizeInput($_POST['signup_contact']);
        $signup_address = sanitizeInput($_POST['signup_address']);
        $profile_picture = null;

        // Validate common required fields
        if (empty($signup_username) || empty($signup_email) || empty($signup_password) || empty($signup_confirm_password) || empty($signup_first_name) || empty($signup_last_name) || empty($signup_gender) || empty($signup_dob) || empty($signup_contact) || empty($signup_address)) {
            $_SESSION['signup_error'] = 'Please fill in all required fields.';
            header('Location: login.php?form=signup&type=' . $signup_type);
            exit();
        } elseif ($signup_password !== $signup_confirm_password) {
            $_SESSION['signup_error'] = 'Passwords do not match.';
            header('Location: login.php?form=signup&type=' . $signup_type);
            exit();
        } else {
            // Check for duplicate username/email
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$signup_username, $signup_email]);
            $user_exists = $stmt->fetchColumn();
            
            if ($user_exists > 0) {
                $_SESSION['signup_error'] = 'Username or email already exists.';
                header('Location: login.php?form=signup&type=' . $signup_type);
                exit();
            } else {
                // Handle profile picture upload
                if (isset($_FILES['signup_picture']) && $_FILES['signup_picture']['error'] === UPLOAD_ERR_OK) {
                    $ext = pathinfo($_FILES['signup_picture']['name'], PATHINFO_EXTENSION);
                    $new_filename = uniqid('profile_') . '.' . $ext;
                    $upload_dir = 'img/';
                    $upload_path = $upload_dir . $new_filename;
                    if (move_uploaded_file($_FILES['signup_picture']['tmp_name'], $upload_path)) {
                        $profile_picture = $upload_path;
                    }
                }
                
                $full_name = trim($signup_first_name . ' ' . ($signup_middle_name ? $signup_middle_name . ' ' : '') . $signup_last_name);
                $hashed_password = password_hash($signup_password, PASSWORD_DEFAULT);
                
                try {
                    $pdo->beginTransaction();
                    
                    // Generate user ID
                    $user_id = generateID('USR', 'users', 'user_id');
                    
                    // Insert into users table - set is_active to 0 (pending approval)
                    $role = ($signup_type === 'trainer') ? 'trainer' : 'member';
                    $stmt = $pdo->prepare("INSERT INTO users (user_id, username, password, email, full_name, role, profile_picture, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, 0)");
                    $stmt->execute([$user_id, $signup_username, $hashed_password, $signup_email, $full_name, $role, $profile_picture]);
                    
                    if ($signup_type === 'trainer') {
                        // Trainer signup - set status to 'Inactive' (pending approval)
                        $signup_specialization = sanitizeInput($_POST['signup_specialization']);
                        $signup_hire_date = sanitizeInput($_POST['signup_hire_date']);
                        
                        if (empty($signup_specialization) || empty($signup_hire_date)) {
                            throw new Exception('Please fill in all trainer required fields.');
                        }
                        
                        $trainer_id = generateID('TRN', 'trainers', 'trainer_id');
                        
                        // Insert into trainers table - status set to 'Inactive' for pending approval
                        // Note: profile_picture column may not exist, so we'll try without it first
                        try {
                            $stmt = $pdo->prepare("INSERT INTO trainers (trainer_id, username, first_name, last_name, middle_name, gender, contact_number, email, address, date_of_birth, specialization, status, hire_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Inactive', ?)");
                            $stmt->execute([$trainer_id, $signup_username, $signup_first_name, $signup_last_name, $signup_middle_name, $signup_gender, $signup_contact, $signup_email, $signup_address, $signup_dob, $signup_specialization, $signup_hire_date]);
                        } catch (PDOException $e) {
                            // If profile_picture column exists, try with it
                            if (strpos($e->getMessage(), 'profile_picture') !== false) {
                                $stmt = $pdo->prepare("INSERT INTO trainers (trainer_id, username, first_name, last_name, middle_name, gender, contact_number, email, address, date_of_birth, specialization, status, hire_date, profile_picture) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Inactive', ?, ?)");
                                $stmt->execute([$trainer_id, $signup_username, $signup_first_name, $signup_last_name, $signup_middle_name, $signup_gender, $signup_contact, $signup_email, $signup_address, $signup_dob, $signup_specialization, $signup_hire_date, $profile_picture]);
                            } else {
                                throw $e;
                            }
                        }
                    } else {
                        // Member signup - set status to 'Pending' (pending approval)
                        $signup_rfid = sanitizeInput($_POST['signup_rfid']);
                        $signup_plan = sanitizeInput($_POST['signup_plan']);
                        
                        if (empty($signup_rfid) || empty($signup_plan)) {
                            throw new Exception('Please fill in all member required fields.');
                        }
                        
                        // Check for duplicate RFID
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM members WHERE rfid_card_number = ?");
                        $stmt->execute([$signup_rfid]);
                        $rfid_exists = $stmt->fetchColumn();
                        if ($rfid_exists > 0) {
                            throw new Exception('RFID card number already exists.');
                        }
                        
                        $member_id = generateID('MEM', 'members', 'member_id');
                    
                    // Calculate registration and membership dates
                    $registration_date = date('Y-m-d');
                    $renewal_date = null;
                    $membership_start_date = date('Y-m-d');
                    // Set membership_end_date based on plan
                    $duration_days = 30; // Default Monthly
                    if ($signup_plan === 'Quarterly') {
                        $duration_days = 90;
                    } elseif ($signup_plan === 'Annual') {
                        $duration_days = 365;
                    }
                    $membership_end_date = date('Y-m-d', strtotime("+$duration_days days"));
                    
                        // Insert into members table - membership_status set to 'Pending' for approval
                    $stmt = $pdo->prepare("
    INSERT INTO members (
        member_id, first_name, last_name, middle_name, username,
        gender, contact_number, address, date_of_birth,
        membership_plan, membership_status,
        registration_date, renewal_date,
        email, rfid_card_number, profile_picture,
        membership_start_date, membership_end_date,
        user_id
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', ?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->execute([
    $member_id,
    $signup_first_name,
    $signup_last_name,
    $signup_middle_name,
    $signup_username,
    $signup_gender,
    $signup_contact,
    $signup_address,
    $signup_dob,
    $signup_plan,
    $registration_date,
    $renewal_date,
    $signup_email,
    $signup_rfid,
    $profile_picture,
    $membership_start_date,
    $membership_end_date,
    $user_id
]);
                    }
                    
                    $pdo->commit();
                    
                    // Redirect with success message
                    header('Location: login.php?success=registration');
                    exit();
                } catch (Exception $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    $_SESSION['signup_error'] = 'Registration failed: ' . $e->getMessage();
                    error_log('Registration error: ' . $e->getMessage());
                    header('Location: login.php?form=signup&type=' . $signup_type);
                    exit();
                } catch (PDOException $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    $_SESSION['signup_error'] = 'Registration failed: ' . $e->getMessage();
                    error_log('Registration error: ' . $e->getMessage());
                    header('Location: login.php?form=signup&type=' . $signup_type);
                    exit();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - UEP Fitness Gym</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Work+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="login-container"
            style="background-image: url('img/cover.png');">

        <div id="form-container" class="login-form-container md:grid md:grid-cols-2 px-2 sm:px-4">

        <!-- Left panel: branding -->
        <!-- Branding for desktop -->
        <div class="login-branding">
            <div class="flex items-center gap-4">
                <img src="img/logo.jpg" alt="UEP Logo" class="w-24 h-24 rounded-full border-2 border-white/20 shadow object-cover aspect-square" />
                <div>
                    <h2 class="text-3xl font-extrabold">UEP Fitness Gym</h2>
                    <p class="text-sm text-white/80">Strength. Health. Community.</p>
                </div>
            </div>
            <div class="mt-4">
                <h3 class="text-2xl font-bold leading-tight">Access Your Fitness Dashboard</h3>
                <p class="mt-2 text-white/80 text-sm">Manage your workouts, attendance, and progress in one place.</p>
            </div>
            <ul class="mt-6 space-y-3 text-sm text-white/85">
                <li> Easy membership management</li>
                <li> Track vitals and progress</li>
                <li> Secure login with RFID support</li>
            </ul>
            <p>
                <p>
            <div class="mt-auto text-xs text-white/60">© 2025 UEP Fitness Gym</div>
        </div>
        <!-- Branding for mobile/tablet -->
        <div class="login-branding-mobile">
            <img src="img/logo.jpg" alt="UEP Logo" class="w-16 h-16 rounded-full border-2 border-white/20 shadow object-cover aspect-square mb-2" />
            <h2 class="text-4xl font-extrabold">UEP Fitness Gym</h2>
            <p class="text-xs text-white/80 -mt-1 mb-1">Strength. Health. Community.</p>

        </div>

        <!-- Right panel: forms -->
        <div class="login-form-panel">
            <!-- Error Message -->
            <?php if (!empty($error_message)): ?>
                <div id="error-message" class="login-alert login-alert-error">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <!-- Success Message -->
            <?php if (isset($_GET['success']) && $_GET['success'] === 'registration'): ?>
                <div class="login-alert login-alert-success">
                    <div class="flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5 text-blue-600" style="flex-shrink: 0;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                        </svg>
                        <div>
                            <strong>Registration successful!</strong> Your account is pending staff approval. You will be able to log in once approved.
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form id="login-form" method="POST" class="space-y-6">
                <div>
                    <h1 id="form-title" class="text-2xl text-slate-800 font-extrabold">Welcome Back,</h1>
                    <p class="text-sm text-slate-500">Ready to be fit? Log in to continue.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Username</label>
                    <input name="username" type="text" required
                           class="login-input placeholder-slate-400 shadow-sm"
                           placeholder="Username" value="" autocomplete="username" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                    <div class="relative">
                        <input name="password" id="login-password" type="password" required
                               class="login-input placeholder-slate-400 shadow-sm pr-12"
                               placeholder="Password" autocomplete="current-password" />
                        <button type="button" id="toggle-login-password" class="absolute inset-y-0 right-0 flex items-center justify-center px-3 text-slate-500 hover:text-indigo-600 focus:outline-none transition-colors duration-200" aria-label="Toggle password visibility">
                            <img src="img/show.png" class="w-5 h-5 object-contain" alt="Show Password" />
                        </button>
                    </div>
                </div>

                <button type="submit" class="login-btn">
                    Sign In
                </button>

                <div class="text-center">
                    <button type="button" id="show-signup" class="login-link">Don't have an account? Sign Up</button>
                </div>
            </form>

            <!-- Role Selection (hidden by default) -->
            <div id="role-selection" class="hidden">
                <h2 class="text-2xl text-slate-800 font-bold mb-4">Choose Your Account Type</h2>
                <p class="text-sm text-slate-500 mb-6">Select whether you want to sign up as a member or trainer.</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                    <button type="button" id="select-member" class="login-role-card">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6 text-indigo-600">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.5v-1.125A3.375 3.375 0 0 0 11.625 15h-3.75A3.375 3.375 0 0 0 4.5 18.375V19.5M12 10.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0M18 8.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-slate-800">Member</h3>
                        </div>
                        <p class="text-sm text-slate-600">Sign up as a gym member to access facilities, track workouts, and manage your membership.</p>
                    </button>
                    <button type="button" id="select-trainer" class="login-role-card login-role-card-green">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-6 h-6 text-green-600">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.905 59.905 0 0 1 12 4.813a59.902 59.902 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443a55.381 55.381 0 0 1 5.25 2.882V15"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-slate-800">Trainer</h3>
                        </div>
                        <p class="text-sm text-slate-600">Sign up as a trainer to manage clients, schedule sessions, and track training programs.</p>
                    </button>
                </div>
                <div class="text-center">
                    <button type="button" id="back-to-login-from-role" class="login-link">Back to Login</button>
                </div>
            </div>

            <!-- Sign Up Form - Member (hidden by default) -->
            <form id="signup-form-member" method="POST" enctype="multipart/form-data" class="space-y-4 hidden mt-3">
                <input type="hidden" name="signup_type" value="member">
                <div class="flex items-center justify-between mb-2">
                    <h2 class="text-2xl text-slate-800 font-bold">Create Member Account</h2>
                    <button type="button" id="back-to-role-member" class="text-sm text-indigo-600 hover:underline">Change Type</button>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm text-slate-600">Username</label>
                        <input name="signup_username" type="text" required class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50" />
                    </div>
                    <div>
                        <label class="block text-sm text-slate-600">Email</label>
                        <input name="signup_email" type="email" required class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50" />
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm text-slate-600">Password</label>
                        <div class="relative">
                            <input name="signup_password" id="signup-password" type="password" required class="login-input pr-12" />
                            <button type="button" id="toggle-signup-password" class="absolute inset-y-0 right-0 flex items-center justify-center px-3 text-slate-500 hover:text-indigo-600 focus:outline-none transition-colors duration-200" aria-label="Toggle password visibility">
                                <img src="img/show.png" class="w-5 h-5 object-contain" alt="Show Password" />
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm text-slate-600">Confirm Password</label>
                        <div class="relative">
                            <input name="signup_confirm_password" id="signup-confirm-password" type="password" required class="login-input pr-12" />
                            <button type="button" id="toggle-signup-confirm-password" class="absolute inset-y-0 right-0 flex items-center justify-center px-3 text-slate-500 hover:text-indigo-600 focus:outline-none transition-colors duration-200" aria-label="Toggle password visibility">
                                <img src="img/show.png" class="w-5 h-5 object-contain" alt="Show Password" />
                            </button>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm text-slate-600">First Name</label>
                        <input name="signup_first_name" type="text" required class="login-input" />
                    </div>
                    <div>
                        <label class="block text-sm text-slate-600">Last Name</label>
                        <input name="signup_last_name" type="text" required class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50" />
                    </div>
                </div>

                <div>
                    <label class="block text-sm text-slate-600">Middle Name <span class="text-xs text-slate-400">(Optional)</span></label>
                    <input name="signup_middle_name" type="text" class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50" />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm text-slate-600">Gender</label>
                        <select name="signup_gender" required class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50">
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-slate-600">Date of Birth</label>
                        <input name="signup_dob" type="date" required class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50" />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm text-slate-600">Contact Number</label>
                        <input name="signup_contact" type="tel" required class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50" />
                    </div>
                    <div>
                        <label class="block text-sm text-slate-600">RFID Card Number</label>
                        <input name="signup_rfid" type="text" required class="login-input" />
                    </div>
                </div>

                <div>
                    <label class="block text-sm text-slate-600">Address</label>
                    <textarea name="signup_address" required class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50"></textarea>
                </div>

                <!-- Profile Picture Upload -->
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-600">Profile picture <span class="text-xs text-slate-400">(Optional)</span></label>
                    <div class="flex items-center gap-4">
                        <div class="flex flex-col items-center gap-1">
    <div id="preview-container"
         class="w-20 h-20 bg-slate-100 rounded-xl flex items-center justify-center overflow-hidden shadow-sm">
        <img id="preview-image"
             src="img/image.png"
             alt="Preview"
             class="w-full h-full object-cover">
    </div>

    <span id="no-file-text" class="text-red-600 text-xs">
        No image chosen
    </span>
</div>

                        <label class="cursor-pointer bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium flex items-center gap-2">
                            <span>Choose File</span>
                            <input type="file" name="signup_picture" id="signup_picture" accept="image/*" class="hidden">
                        </label>
                    </div>
                </div>

                    <div>
                        <label class="block text-sm text-slate-600">Membership Plan</label>
                        <select name="signup_plan" required class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50">
                            <option value="Monthly">Monthly</option>
                            <option value="Quarterly">Quarterly</option>
                            <option value="Annual">Annual</option>
                        </select>
                    <p class="text-xs text-slate-500 mt-1">Your account will be pending staff approval.</p>
                </div>

                <button type="submit" name="signup" class="w-full bg-green-600 text-white py-3 rounded-xl font-semibold hover:opacity-95 transition">Create Member Account</button>

                <div class="text-center">
                    <button type="button" id="back-to-login-member" class="text-indigo-600 hover:underline font-semibold">Already have an account? Log In</button>
                </div>
            </form>

            <!-- Sign Up Form - Trainer (hidden by default) -->
            <form id="signup-form-trainer" method="POST" enctype="multipart/form-data" class="space-y-4 hidden mt-3">
                <input type="hidden" name="signup_type" value="trainer">
                <div class="flex items-center justify-between mb-2">
                    <h2 class="text-2xl text-slate-800 font-bold">Create Trainer Account</h2>
                    <button type="button" id="back-to-role-trainer" class="text-sm text-indigo-600 hover:underline">Change Type</button>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm text-slate-600">Username</label>
                        <input name="signup_username" type="text" required class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50" />
                    </div>
                    <div>
                        <label class="block text-sm text-slate-600">Email</label>
                        <input name="signup_email" type="email" required class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50" />
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm text-slate-600">Password</label>
                        <div class="relative">
                            <input name="signup_password" id="signup-password-trainer" type="password" required class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 pr-12" />
                            <button type="button" id="toggle-signup-password-trainer" class="absolute inset-y-0 right-0 flex items-center justify-center px-3 text-slate-500 hover:text-indigo-600 focus:outline-none transition-colors duration-200" aria-label="Toggle password visibility">
                                <img src="img/show.png" class="w-5 h-5 object-contain" alt="Show Password" />
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm text-slate-600">Confirm Password</label>
                        <div class="relative">
                            <input name="signup_confirm_password" id="signup-confirm-password-trainer" type="password" required class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50 pr-12" />
                            <button type="button" id="toggle-signup-confirm-password-trainer" class="absolute inset-y-0 right-0 flex items-center justify-center px-3 text-slate-500 hover:text-indigo-600 focus:outline-none transition-colors duration-200" aria-label="Toggle password visibility">
                                <img src="img/show.png" class="w-5 h-5 object-contain" alt="Show Password" />
                            </button>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm text-slate-600">First Name</label>
                        <input name="signup_first_name" type="text" required class="login-input" />
                    </div>
                    <div>
                        <label class="block text-sm text-slate-600">Last Name</label>
                        <input name="signup_last_name" type="text" required class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50" />
                    </div>
                </div>

                <div>
                    <label class="block text-sm text-slate-600">Middle Name <span class="text-xs text-slate-400">(Optional)</span></label>
                    <input name="signup_middle_name" type="text" class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50" />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm text-slate-600">Gender</label>
                        <select name="signup_gender" required class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50">
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-slate-600">Date of Birth</label>
                        <input name="signup_dob" type="date" required class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50" />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm text-slate-600">Contact Number</label>
                        <input name="signup_contact" type="tel" required class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50" />
                    </div>
                    <div>
                        <label class="block text-sm text-slate-600">Specialization</label>
                        <input name="signup_specialization" type="text" required class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50" placeholder="e.g., Weight Training, Cardio, Yoga" />
                    </div>
                </div>

                <div>
                    <label class="block text-sm text-slate-600">Address</label>
                    <textarea name="signup_address" required class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50"></textarea>
                </div>

                <!-- Profile Picture Upload -->
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-slate-600">Profile picture <span class="text-xs text-slate-400">(Optional)</span></label>
                    <div class="flex items-center gap-4">
                        <div class="flex flex-col items-center gap-1">
                            <div id="preview-container-trainer"
                                 class="w-20 h-20 bg-slate-100 rounded-xl flex items-center justify-center overflow-hidden shadow-sm">
                                <img id="preview-image-trainer"
                                     src="img/image.png"
                                     alt="Preview"
                                     class="w-full h-full object-cover">
                            </div>
                            <span id="no-file-text-trainer" class="text-red-600 text-xs">
                                No image chosen
                            </span>
                        </div>
                        <label class="cursor-pointer bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg font-medium flex items-center gap-2">
                            <span>Choose File</span>
                            <input type="file" name="signup_picture" id="signup_picture_trainer" accept="image/*" class="hidden">
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-sm text-slate-600">Hire Date</label>
                    <input name="signup_hire_date" type="date" required class="w-full px-3 py-2 rounded-lg border border-slate-200 bg-slate-50" />
                    <p class="text-xs text-slate-500 mt-1">Your account will be pending staff approval.</p>
                </div>

                <button type="submit" name="signup" class="w-full bg-green-600 text-white py-3 rounded-xl font-semibold hover:opacity-95 transition">Create Trainer Account</button>

                <div class="text-center">
                    <button type="button" id="back-to-login-trainer" class="text-indigo-600 hover:underline font-semibold">Already have an account? Log In</button>
                </div>
            </form>
        </div>

    <script src="js/auth.js"></script>
</body>
</html>
