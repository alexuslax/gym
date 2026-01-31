<?php
session_start();
require_once 'config/functions.php';

$error_message = '';
$form_data = [];
$password_mismatch = false;

// Retrieve error messages from session (set by POST redirects)
if (isset($_SESSION['login_error'])) {
    $error_message = $_SESSION['login_error'];
    unset($_SESSION['login_error']);
} elseif (isset($_SESSION['signup_error'])) {
    $error_message = $_SESSION['signup_error'];
    if ($error_message === 'Passwords do not match.') {
        $password_mismatch = true;
    }
    unset($_SESSION['signup_error']);
}

// Retrieve saved form data if validation failed
if (isset($_SESSION['signup_form_data'])) {
    $form_data = $_SESSION['signup_form_data'];
    unset($_SESSION['signup_form_data']);
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
                    if (in_array($user['role'], ['staff', 'admin'], true)) {
                        header('Location: staff_view/dashboard.php');
                    } elseif ($user['role'] === 'faculty') {
                        header('Location: faculty_view/dashboard.php');
                    } else {
                        header('Location: student_view/dashboard.php');
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
        $signup_type = isset($_POST['signup_type']) ? sanitizeInput($_POST['signup_type']) : 'student';
        
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
            $_SESSION['signup_form_data'] = $_POST; // Store form data
            header('Location: login.php?form=signup&type=' . $signup_type);
            exit();
        } elseif ($signup_password !== $signup_confirm_password) {
            $_SESSION['signup_error'] = 'Passwords do not match.';
            $_SESSION['signup_form_data'] = $_POST; // Store form data
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
                    $upload_dir = 'img/profiles/';
                    $upload_path = $upload_dir . $new_filename;
                    if (move_uploaded_file($_FILES['signup_picture']['tmp_name'], $upload_path)) {
                        $profile_picture = $upload_path;
                    }
                }
                
                // Handle student-specific document uploads
                $cor_path = null;
                $medical_cert_path = null;
                $id_card_path = null;
                $signup_rfid = null;
                $signup_student_number = null;
                
                if ($signup_type === 'student') {
                    // Get student-specific fields
                    $signup_rfid = isset($_POST['signup_rfid']) ? sanitizeInput($_POST['signup_rfid']) : null;
                    $signup_student_number = sanitizeInput($_POST['signup_student_number']);
                    
                    if (empty($signup_student_number)) {
                        throw new Exception('Student number is required.');
                    }
                    
                    // Handle Certificate of Registration upload
                    if (isset($_FILES['signup_cor']) && $_FILES['signup_cor']['error'] === UPLOAD_ERR_OK) {
                        $ext = pathinfo($_FILES['signup_cor']['name'], PATHINFO_EXTENSION);
                        $new_filename = uniqid('cor_') . '.' . $ext;
                        $upload_dir = 'img/documents/';
                        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                        $upload_path = $upload_dir . $new_filename;
                        if (move_uploaded_file($_FILES['signup_cor']['tmp_name'], $upload_path)) {
                            $cor_path = $upload_path;
                        }
                    }
                    
                    // Handle Medical Certificate upload
                    if (isset($_FILES['signup_medical_cert']) && $_FILES['signup_medical_cert']['error'] === UPLOAD_ERR_OK) {
                        $ext = pathinfo($_FILES['signup_medical_cert']['name'], PATHINFO_EXTENSION);
                        $new_filename = uniqid('medical_') . '.' . $ext;
                        $upload_dir = 'img/documents/';
                        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                        $upload_path = $upload_dir . $new_filename;
                        if (move_uploaded_file($_FILES['signup_medical_cert']['tmp_name'], $upload_path)) {
                            $medical_cert_path = $upload_path;
                        }
                    }
                    
                    // Handle ID Card upload
                    if (isset($_FILES['signup_id_card']) && $_FILES['signup_id_card']['error'] === UPLOAD_ERR_OK) {
                        $ext = pathinfo($_FILES['signup_id_card']['name'], PATHINFO_EXTENSION);
                        $new_filename = uniqid('id_') . '.' . $ext;
                        $upload_dir = 'img/documents/';
                        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                        $upload_path = $upload_dir . $new_filename;
                        if (move_uploaded_file($_FILES['signup_id_card']['tmp_name'], $upload_path)) {
                            $id_card_path = $upload_path;
                        }
                    }
                }
                
                $full_name = trim($signup_first_name . ' ' . ($signup_middle_name ? $signup_middle_name . ' ' : '') . $signup_last_name);
                $hashed_password = password_hash($signup_password, PASSWORD_DEFAULT);
                
                try {
                    $pdo->beginTransaction();
                    
                    // Generate user ID
                    $user_id = generateID('USR', 'users', 'user_id');
                    
                    // Store pending registration data as JSON for later processing on approval
                    $pending_data = json_encode([
                        'first_name' => $signup_first_name,
                        'last_name' => $signup_last_name,
                        'middle_name' => $signup_middle_name,
                        'gender' => $signup_gender,
                        'contact_number' => $signup_contact,
                        'address' => $signup_address,
                        'date_of_birth' => $signup_dob,
                        'profile_picture' => $profile_picture,
                        'registration_date' => date('Y-m-d'),
                        'rfid_number' => $signup_rfid,
                        'student_number' => $signup_student_number,
                        'cor_document' => $cor_path,
                        'medical_certificate' => $medical_cert_path,
                        'id_card' => $id_card_path
                    ]);
                    
                    // For faculty, add faculty-specific fields and document uploads
                    if ($signup_type === 'faculty') {
                        $signup_hire_date = sanitizeInput($_POST['signup_hire_date']);
                        $signup_rfid = isset($_POST['signup_rfid']) ? sanitizeInput($_POST['signup_rfid']) : null;
                        $signup_faculty_number = isset($_POST['signup_faculty_number']) ? sanitizeInput($_POST['signup_faculty_number']) : '';

                        if (empty($signup_hire_date) || empty($signup_faculty_number)) {
                            throw new Exception('Please fill in all faculty required fields.');
                        }

                        // Prepare upload paths
                        $cor_path_f = null;
                        $medical_cert_path_f = null;
                        $id_card_path_f = null;

                        // Certificate of Registration
                        if (isset($_FILES['signup_cor']) && $_FILES['signup_cor']['error'] === UPLOAD_ERR_OK) {
                            $ext = pathinfo($_FILES['signup_cor']['name'], PATHINFO_EXTENSION);
                            $new_filename = uniqid('cor_') . '.' . $ext;
                            $upload_dir = 'img/documents/';
                            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                            $upload_path = $upload_dir . $new_filename;
                            if (move_uploaded_file($_FILES['signup_cor']['tmp_name'], $upload_path)) {
                                $cor_path_f = $upload_path;
                            }
                        }

                        // Medical Certificate
                        if (isset($_FILES['signup_medical_cert']) && $_FILES['signup_medical_cert']['error'] === UPLOAD_ERR_OK) {
                            $ext = pathinfo($_FILES['signup_medical_cert']['name'], PATHINFO_EXTENSION);
                            $new_filename = uniqid('medical_') . '.' . $ext;
                            $upload_dir = 'img/documents/';
                            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                            $upload_path = $upload_dir . $new_filename;
                            if (move_uploaded_file($_FILES['signup_medical_cert']['tmp_name'], $upload_path)) {
                                $medical_cert_path_f = $upload_path;
                            }
                        }

                        // ID Card
                        if (isset($_FILES['signup_id_card']) && $_FILES['signup_id_card']['error'] === UPLOAD_ERR_OK) {
                            $ext = pathinfo($_FILES['signup_id_card']['name'], PATHINFO_EXTENSION);
                            $new_filename = uniqid('id_') . '.' . $ext;
                            $upload_dir = 'img/documents/';
                            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                            $upload_path = $upload_dir . $new_filename;
                            if (move_uploaded_file($_FILES['signup_id_card']['tmp_name'], $upload_path)) {
                                $id_card_path_f = $upload_path;
                            }
                        }

                        $pending_data = json_encode([
                            'first_name' => $signup_first_name,
                            'last_name' => $signup_last_name,
                            'middle_name' => $signup_middle_name,
                            'gender' => $signup_gender,
                            'contact_number' => $signup_contact,
                            'address' => $signup_address,
                            'date_of_birth' => $signup_dob,
                            'profile_picture' => $profile_picture,
                            'hire_date' => $signup_hire_date,
                            'rfid_number' => $signup_rfid,
                            'faculty_number' => $signup_faculty_number,
                            'cor_document' => $cor_path_f,
                            'medical_certificate' => $medical_cert_path_f,
                            'id_card' => $id_card_path_f,
                            'registration_date' => date('Y-m-d')
                        ]);
                    }
                    
                    // Insert into users table only - set is_active to 0 (pending approval)
                    // Student/Faculty ID will be generated when admin approves
                    $role = ($signup_type === 'faculty') ? 'faculty' : 'student';
                    $stmt = $pdo->prepare("INSERT INTO users (user_id, username, password, email, full_name, role, is_active, pending_data) VALUES (?, ?, ?, ?, ?, ?, 0, ?)");
                    $stmt->execute([$user_id, $signup_username, $hashed_password, $signup_email, $full_name, $role, $pending_data]);
                    
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url('img/cover.png');
            background-size: cover;
            background-position: center;
            opacity: 0.15;
            z-index: 0;
        }

        .login-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 1200px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
            background: white;
            border-radius: 2rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            animation: slideUp 0.6s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .branding-panel {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 3rem;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }

        .branding-panel::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: float 20s infinite ease-in-out;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            50% { transform: translate(-20px, 20px) rotate(180deg); }
        }

        .branding-content {
            position: relative;
            z-index: 1;
        }

        .logo-wrapper {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .logo-img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 3px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            object-fit: cover;
        }

        .brand-title {
            font-size: 2rem;
            font-weight: 800;
            line-height: 1.2;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .brand-tagline {
            font-size: 0.875rem;
            opacity: 0.9;
            margin-top: 0.25rem;
        }

        .brand-heading {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 1rem;
            line-height: 1.3;
        }

        .brand-description {
            opacity: 0.9;
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .features-list {
            list-style: none;
            margin-bottom: 2rem;
        }

        .features-list li {
            padding-left: 1.75rem;
            margin-bottom: 0.75rem;
            position: relative;
            opacity: 0.95;
        }

        .features-list li::before {
            content: '✓';
            position: absolute;
            left: 0;
            font-weight: 700;
            color: #86efac;
        }

        .brand-footer {
            opacity: 0.7;
            font-size: 0.75rem;
        }

        .form-panel {
            padding: 3rem;
            background: white;
            overflow-y: auto;
            max-height: 90vh;
        }

        .alert {
            padding: 1rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            animation: slideDown 0.4s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-error {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            border: 1px solid #fecaca;
            color: #991b1b;
        }

        .alert-success {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border: 1px solid #bbf7d0;
            color: #166534;
        }

        .alert svg {
            width: 1.25rem;
            height: 1.25rem;
            flex-shrink: 0;
            margin-top: 0.125rem;
        }

        .form-title {
            font-size: 1.875rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 0.5rem;
        }

        .form-subtitle {
            color: #64748b;
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 0.5rem;
        }

        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            font-size: 0.9375rem;
            transition: all 0.2s ease;
            background: #f8fafc;
            font-family: inherit;
        }

        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-input.error, .form-select.error {
            border-color: #ef4444;
            background: #fef2f2;
        }

        .form-input.error:focus, .form-select.error:focus {
            border-color: #ef4444;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
        }

        .error-text {
            display: block;
            color: #ef4444;
            font-size: 0.8125rem;
            font-weight: 500;
            margin-top: 0.375rem;
        }

        .form-textarea {
            resize: vertical;
            min-height: 80px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon-btn {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            transition: color 0.2s;
        }

        .input-icon-btn:hover {
            color: #667eea;
        }

        .input-icon-btn img {
            width: 1.25rem;
            height: 1.25rem;
            object-fit: contain;
        }

        .input-with-icon {
            padding-right: 3rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .btn {
            width: 100%;
            padding: 0.875rem 1.5rem;
            border: none;
            border-radius: 0.75rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: inherit;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.5);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.5);
        }

        .btn-upload {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.625rem 1.25rem;
            border-radius: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .btn-upload:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .text-link {
            background: none;
            border: none;
            color: #667eea;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.9375rem;
            transition: color 0.2s;
        }

        .text-link:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        .text-center {
            text-align: center;
        }

        .role-card {
            padding: 1.5rem;
            border: 2px solid #e2e8f0;
            border-radius: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }

        .role-card:hover {
            border-color: #667eea;
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(102, 126, 234, 0.15);
        }

        .role-card-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
        }

        .role-icon {
            width: 3rem;
            height: 3rem;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .role-icon-blue {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
        }

        .role-icon-green {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
        }

        .role-icon svg {
            width: 1.5rem;
            height: 1.5rem;
        }

        .role-icon-blue svg {
            color: #2563eb;
            stroke: #2563eb;
        }

        .role-icon-green svg {
            color: #059669;
            stroke: #059669;
        }

        .role-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: #0f172a;
        }

        .role-description {
            font-size: 0.875rem;
            color: #64748b;
            line-height: 1.5;
        }

        .preview-container {
            width: 80px;
            height: 80px;
            border-radius: 1rem;
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .preview-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .upload-wrapper {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .upload-info {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.25rem;
        }

        .no-file-text {
            font-size: 0.75rem;
            color: #ef4444;
        }

        .optional-text {
            font-size: 0.75rem;
            color: #94a3b8;
        }

        .helper-text {
            font-size: 0.75rem;
            color: #64748b;
            margin-top: 0.375rem;
        }

        .hidden {
            display: none !important;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .login-container {
                grid-template-columns: 1fr;
                max-width: 500px;
            }

            .branding-panel {
                padding: 2rem;
                min-height: auto;
            }

            .logo-wrapper {
                margin-bottom: 1rem;
            }

            .logo-img {
                width: 60px;
                height: 60px;
            }

            .brand-title {
                font-size: 1.5rem;
            }

            .brand-heading {
                font-size: 1.25rem;
            }

            .form-panel {
                padding: 2rem 1.5rem;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-title {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 0.5rem;
            }

            .login-container {
                border-radius: 1rem;
            }

            .form-panel {
                padding: 1.5rem 1rem;
            }
        }

        /* Scrollbar Styling */
        .form-panel::-webkit-scrollbar {
            width: 8px;
        }

        .form-panel::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        .form-panel::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        .form-panel::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Left Panel: Branding -->
        <div class="branding-panel">
            <div class="branding-content">
                <div class="logo-wrapper">
                    <img src="img/logo.jpg" alt="UEP Logo" class="logo-img" />
                    <div>
                        <h2 class="brand-title">UEP Fitness Gym</h2>
                        <p class="brand-tagline">Strength. Health. Community.</p>
                    </div>
                </div>
                <div>
                    <h3 class="brand-heading">Access Your Fitness Dashboard</h3>
                    <p class="brand-description">Manage your workouts, attendance, and progress in one place.</p>
                </div>
                <ul class="features-list">
                    <li>Easy student enrollment management</li>
                    <li>Track vitals and progress</li>
                    <li>Secure login with RFID support</li>
                </ul>
            </div>
            <div class="brand-footer">© 2025 UEP Fitness Gym</div>
        </div>

        <!-- Right Panel: Forms -->
        <div class="form-panel">
            <!-- Error Message -->
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-error">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                    </svg>
                    <div><?php echo htmlspecialchars($error_message); ?></div>
                </div>
            <?php endif; ?>
            
            <!-- Success Message -->
            <?php if (isset($_GET['success']) && $_GET['success'] === 'registration'): ?>
                <div class="alert alert-success">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                    </svg>
                    <div>
                        <strong>Registration successful!</strong> Your account is pending staff approval. You will be able to log in once approved.
                    </div>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form id="login-form" method="POST">
                <div style="margin-bottom: 2rem;">
                    <h1 class="form-title">Welcome Back</h1>
                    <p class="form-subtitle">Ready to be fit? Log in to continue.</p>
                </div>

                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input name="username" type="text" required class="form-input" placeholder="Enter your username" autocomplete="username" />
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div class="input-wrapper">
                        <input name="password" id="login-password" type="password" required class="form-input input-with-icon" placeholder="Enter your password" autocomplete="current-password" />
                        <button type="button" id="toggle-login-password" class="input-icon-btn" aria-label="Toggle password visibility">
                            <img src="img/show.png" alt="Show Password" />
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Sign In</button>
                </div>

                <div class="text-center">
                    <button type="button" id="show-signup" class="text-link">Don't have an account? Sign Up</button>
                </div>
            </form>

            <!-- Role Selection (hidden by default) -->
            <div id="role-selection" class="hidden">
                <h2 class="form-title">Choose Your Account Type</h2>
                <p class="form-subtitle" style="margin-bottom: 1.5rem;">Select whether you want to sign up as a student or faculty.</p>
                <div class="form-grid" style="margin-bottom: 1.5rem;">
                    <button type="button" id="select-student" class="role-card">
                        <div class="role-card-header">
                            <div class="role-icon role-icon-blue">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.5v-1.125A3.375 3.375 0 0 0 11.625 15h-3.75A3.375 3.375 0 0 0 4.5 18.375V19.5M12 10.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0M18 8.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0"/>
                                </svg>
                            </div>
                            <h3 class="role-title">Student</h3>
                        </div>
                        <p class="role-description">Sign up as a student to access facilities, track workouts, and manage your enrollment.</p>
                    </button>
                    <button type="button" id="select-faculty" class="role-card">
                        <div class="role-card-header">
                            <div class="role-icon role-icon-green">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.905 59.905 0 0 1 12 4.813a59.902 59.902 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443a55.381 55.381 0 0 1 5.25 2.882V15"/>
                                </svg>
                            </div>
                            <h3 class="role-title">Faculty</h3>
                        </div>
                        <p class="role-description">Sign up as a faculty to manage students, schedule sessions, and track training programs.</p>
                    </button>
                    <button type="button" id="select-staff" class="role-card">
                        <div class="role-card-header">
                            <div class="role-icon role-icon-yellow">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zM6 20v-1a4 4 0 014-4h4a4 4 0 014 4v1"/>
                                </svg>
                            </div>
                            <h3 class="role-title">Staff</h3>
                        </div>
                        <p class="role-description">Sign up as staff to manage operations and access staff tools.</p>
                    </button>
                </div>
                <div class="text-center">
                    <button type="button" id="back-to-login-from-role" class="text-link">Back to Login</button>
                </div>
            </div>

            <!-- Sign Up Form - Student (hidden by default) -->
            <form id="signup-form-student" method="POST" enctype="multipart/form-data" class="hidden">
                <input type="hidden" name="signup_type" value="student">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
                    <h2 class="form-title" style="margin-bottom: 0;">Create Student Account</h2>
                    <button type="button" id="back-to-role-student" class="text-link" style="font-size: 0.875rem;">Change Type</button>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <input name="signup_username" type="text" required class="form-input" value="<?php echo htmlspecialchars($form_data['signup_username'] ?? ''); ?>" />
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input name="signup_email" type="email" required class="form-input" value="<?php echo htmlspecialchars($form_data['signup_email'] ?? ''); ?>" />
                    </div>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <div class="input-wrapper">
                            <input name="signup_password" id="signup-password" type="password" required class="form-input input-with-icon" value="<?php echo htmlspecialchars($form_data['signup_password'] ?? ''); ?>" />
                            <button type="button" id="toggle-signup-password" class="input-icon-btn" aria-label="Toggle password visibility">
                                <img src="img/show.png" alt="Show Password" />
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm Password</label>
                        <div class="input-wrapper">
                            <input name="signup_confirm_password" id="signup-confirm-password" type="password" required class="form-input input-with-icon <?php echo ($password_mismatch && isset($form_data['signup_type']) && $form_data['signup_type'] === 'student') ? 'error' : ''; ?>" value="<?php echo htmlspecialchars($form_data['signup_confirm_password'] ?? ''); ?>" />
                            <button type="button" id="toggle-signup-confirm-password" class="input-icon-btn" aria-label="Toggle password visibility">
                                <img src="img/show.png" alt="Show Password" />
                            </button>
                        </div>
                        <?php if ($password_mismatch && isset($form_data['signup_type']) && $form_data['signup_type'] === 'student'): ?>
                            <span class="error-text">Passwords do not match</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">First Name</label>
                        <input name="signup_first_name" type="text" required class="form-input" value="<?php echo htmlspecialchars($form_data['signup_first_name'] ?? ''); ?>" />
                    </div>
                    <div class="form-group">
                        <label class="form-label">Last Name</label>
                        <input name="signup_last_name" type="text" required class="form-input" value="<?php echo htmlspecialchars($form_data['signup_last_name'] ?? ''); ?>" />
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Middle Name <span class="optional-text">(Optional)</span></label>
                    <input name="signup_middle_name" type="text" class="form-input" value="<?php echo htmlspecialchars($form_data['signup_middle_name'] ?? ''); ?>" />
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Gender</label>
                        <select name="signup_gender" required class="form-select">
                            <option value="">Select Gender</option>
                            <option value="Male" <?php echo (isset($form_data['signup_gender']) && $form_data['signup_gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo (isset($form_data['signup_gender']) && $form_data['signup_gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo (isset($form_data['signup_gender']) && $form_data['signup_gender'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date of Birth</label>
                        <input name="signup_dob" type="date" required class="form-input" value="<?php echo htmlspecialchars($form_data['signup_dob'] ?? ''); ?>" />
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Contact Number</label>
                    <input name="signup_contact" type="tel" required class="form-input" value="<?php echo htmlspecialchars($form_data['signup_contact'] ?? ''); ?>" />
                </div>

                <div class="form-group">
                    <label class="form-label">Address</label>
                    <textarea name="signup_address" required class="form-textarea"><?php echo htmlspecialchars($form_data['signup_address'] ?? ''); ?></textarea>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">RFID Number <span class="optional-text">(Optional)</span></label>
                        <input name="signup_rfid" type="text" class="form-input" placeholder="Enter RFID number" value="<?php echo htmlspecialchars($form_data['signup_rfid'] ?? ''); ?>" />
                    </div>
                    <div class="form-group">
                        <label class="form-label">Student Number</label>
                        <input name="signup_student_number" type="text" required class="form-input" placeholder="Enter student number" value="<?php echo htmlspecialchars($form_data['signup_student_number'] ?? ''); ?>" />
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Certificate of Registration</label>
                    <input type="file" name="signup_cor" accept="image/*,.pdf" required class="form-input" style="padding: 0.5rem;" />
                    <p class="helper-text">Upload image or PDF file</p>
                </div>

                <div class="form-group">
                    <label class="form-label">Medical Certificate</label>
                    <input type="file" name="signup_medical_cert" accept="image/*,.pdf" required class="form-input" style="padding: 0.5rem;" />
                    <p class="helper-text">Upload image or PDF file</p>
                </div>

                <div class="form-group">
                    <label class="form-label">Identification Card (School ID)</label>
                    <input type="file" name="signup_id_card" accept="image/*,.pdf" required class="form-input" style="padding: 0.5rem;" />
                    <p class="helper-text">Upload image or PDF file</p>
                </div>

                <div class="form-group">
                    <label class="form-label">Profile Picture <span class="optional-text">(Optional)</span></label>
                    <div class="upload-wrapper">
                        <div class="upload-info">
                            <div class="preview-container" id="preview-container">
                                <img id="preview-image" src="img/image.png" alt="Preview" class="preview-image">
                            </div>
                            <span id="no-file-text" class="no-file-text">No image chosen</span>
                        </div>
                        <label class="btn-upload">
                            <span>Choose File</span>
                            <input type="file" name="signup_picture" id="signup_picture" accept="image/*" class="hidden">
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <button type="submit" name="signup" class="btn btn-success">Create Student Account</button>
                    <p class="helper-text" style="margin-top: 0.5rem; text-align: center;">Your account will be pending staff approval. RFID and enrollment plan will be assigned by staff.</p>
                </div>

                <div class="text-center">
                    <button type="button" id="back-to-login-student" class="text-link">Already have an account? Log In</button>
                </div>
            </form>

            <!-- Sign Up Form - Faculty (hidden by default) -->
            <form id="signup-form-faculty" method="POST" enctype="multipart/form-data" class="hidden">
                <input type="hidden" name="signup_type" value="faculty">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
                    <h2 class="form-title" style="margin-bottom: 0;">Create Faculty Account</h2>
                    <button type="button" id="back-to-role-faculty" class="text-link" style="font-size: 0.875rem;">Change Type</button>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <input name="signup_username" type="text" required class="form-input" value="<?php echo htmlspecialchars($form_data['signup_username'] ?? ''); ?>" />
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input name="signup_email" type="email" required class="form-input" value="<?php echo htmlspecialchars($form_data['signup_email'] ?? ''); ?>" />
                    </div>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <div class="input-wrapper">
                            <input name="signup_password" id="signup-password-faculty" type="password" required class="form-input input-with-icon" value="<?php echo htmlspecialchars($form_data['signup_password'] ?? ''); ?>" />
                            <button type="button" id="toggle-signup-password-faculty" class="input-icon-btn" aria-label="Toggle password visibility">
                                <img src="img/show.png" alt="Show Password" />
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm Password</label>
                        <div class="input-wrapper">
                            <input name="signup_confirm_password" id="signup-confirm-password-faculty" type="password" required class="form-input input-with-icon <?php echo ($password_mismatch && isset($form_data['signup_type']) && $form_data['signup_type'] === 'faculty') ? 'error' : ''; ?>" value="<?php echo htmlspecialchars($form_data['signup_confirm_password'] ?? ''); ?>" />
                            <button type="button" id="toggle-signup-confirm-password-faculty" class="input-icon-btn" aria-label="Toggle password visibility">
                                <img src="img/show.png" alt="Show Password" />
                            </button>
                        </div>
                        <?php if ($password_mismatch && isset($form_data['signup_type']) && $form_data['signup_type'] === 'faculty'): ?>
                            <span class="error-text">Passwords do not match</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">First Name</label>
                        <input name="signup_first_name" type="text" required class="form-input" value="<?php echo htmlspecialchars($form_data['signup_first_name'] ?? ''); ?>" />
                    </div>
                    <div class="form-group">
                        <label class="form-label">Last Name</label>
                        <input name="signup_last_name" type="text" required class="form-input" value="<?php echo htmlspecialchars($form_data['signup_last_name'] ?? ''); ?>" />
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Middle Name <span class="optional-text">(Optional)</span></label>
                    <input name="signup_middle_name" type="text" class="form-input" value="<?php echo htmlspecialchars($form_data['signup_middle_name'] ?? ''); ?>" />
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Gender</label>
                        <select name="signup_gender" required class="form-select">
                            <option value="">Select Gender</option>
                            <option value="Male" <?php echo (isset($form_data['signup_gender']) && $form_data['signup_gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo (isset($form_data['signup_gender']) && $form_data['signup_gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo (isset($form_data['signup_gender']) && $form_data['signup_gender'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date of Birth</label>
                        <input name="signup_dob" type="date" required class="form-input" value="<?php echo htmlspecialchars($form_data['signup_dob'] ?? ''); ?>" />
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Contact Number</label>
                        <input name="signup_contact" type="tel" required class="form-input" value="<?php echo htmlspecialchars($form_data['signup_contact'] ?? ''); ?>" />
                    </div>
                        
                </div>
                <div class="form-group">
                    <label class="form-label">Address</label>
                    <textarea name="signup_address" required class="form-textarea"><?php echo htmlspecialchars($form_data['signup_address'] ?? ''); ?></textarea>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">RFID Number <span class="optional-text">(Optional)</span></label>
                        <input name="signup_rfid" type="text" class="form-input" placeholder="Enter RFID number" value="<?php echo htmlspecialchars($form_data['signup_rfid'] ?? ''); ?>" />
                    </div>
                    <div class="form-group">
                        <label class="form-label">Faculty Number</label>
                        <input name="signup_faculty_number" type="text" required class="form-input" placeholder="Enter faculty number" value="<?php echo htmlspecialchars($form_data['signup_faculty_number'] ?? ''); ?>" />
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Certificate of Registration</label>
                    <input type="file" name="signup_cor" accept="image/*,.pdf" required class="form-input" style="padding: 0.5rem;" />
                    <p class="helper-text">Upload image or PDF file</p>
                </div>

                <div class="form-group">
                    <label class="form-label">Medical Certificate</label>
                    <input type="file" name="signup_medical_cert" accept="image/*,.pdf" required class="form-input" style="padding: 0.5rem;" />
                    <p class="helper-text">Upload image or PDF file</p>
                </div>

                <div class="form-group">
                    <label class="form-label">Identification Card (Faculty ID)</label>
                    <input type="file" name="signup_id_card" accept="image/*,.pdf" required class="form-input" style="padding: 0.5rem;" />
                    <p class="helper-text">Upload image or PDF file</p>
                </div>

                <div class="form-group">
                    <label class="form-label">Profile Picture <span class="optional-text">(Optional)</span></label>
                    <div class="upload-wrapper">
                        <div class="upload-info">
                            <div class="preview-container" id="preview-container-faculty">
                                <img id="preview-image-faculty" src="img/image.png" alt="Preview" class="preview-image">
                            </div>
                            <span id="no-file-text-faculty" class="no-file-text">No image chosen</span>
                        </div>
                        <label class="btn-upload">
                            <span>Choose File</span>
                            <input type="file" name="signup_picture" id="signup_picture_faculty" accept="image/*" class="hidden">
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Hire Date</label>
                    <input name="signup_hire_date" type="date" required class="form-input" value="<?php echo htmlspecialchars($form_data['signup_hire_date'] ?? ''); ?>" />
                    <p class="helper-text">Your account will be pending staff approval.</p>
                </div>

                <div class="form-group">
                    <button type="submit" name="signup" class="btn btn-success">Create Faculty Account</button>
                </div>

                <div class="text-center">
                    <button type="button" id="back-to-login-faculty" class="text-link">Already have an account? Log In</button>
                </div>
            </form>

            <!-- Sign Up Form - Staff (hidden by default) -->
            <form id="signup-form-staff" method="POST" enctype="multipart/form-data" class="hidden">
                <input type="hidden" name="signup_type" value="staff">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
                    <h2 class="form-title" style="margin-bottom: 0;">Create Staff Account</h2>
                    <button type="button" id="back-to-role-staff" class="text-link" style="font-size: 0.875rem;">Change Type</button>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <input name="signup_username" type="text" required class="form-input" value="<?php echo htmlspecialchars($form_data['signup_username'] ?? ''); ?>" />
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input name="signup_email" type="email" required class="form-input" value="<?php echo htmlspecialchars($form_data['signup_email'] ?? ''); ?>" />
                    </div>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <div class="input-wrapper">
                            <input name="signup_password" id="signup-password-staff" type="password" required class="form-input input-with-icon" value="<?php echo htmlspecialchars($form_data['signup_password'] ?? ''); ?>" />
                            <button type="button" id="toggle-signup-password-staff" class="input-icon-btn" aria-label="Toggle password visibility">
                                <img src="img/show.png" alt="Show Password" />
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm Password</label>
                        <div class="input-wrapper">
                            <input name="signup_confirm_password" id="signup-confirm-password-staff" type="password" required class="form-input input-with-icon <?php echo ($password_mismatch && isset($form_data['signup_type']) && $form_data['signup_type'] === 'staff') ? 'error' : ''; ?>" value="<?php echo htmlspecialchars($form_data['signup_confirm_password'] ?? ''); ?>" />
                            <button type="button" id="toggle-signup-confirm-password-staff" class="input-icon-btn" aria-label="Toggle password visibility">
                                <img src="img/show.png" alt="Show Password" />
                            </button>
                        </div>
                        <?php if ($password_mismatch && isset($form_data['signup_type']) && $form_data['signup_type'] === 'staff'): ?>
                            <span class="error-text">Passwords do not match</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">First Name</label>
                        <input name="signup_first_name" type="text" required class="form-input" value="<?php echo htmlspecialchars($form_data['signup_first_name'] ?? ''); ?>" />
                    </div>
                    <div class="form-group">
                        <label class="form-label">Last Name</label>
                        <input name="signup_last_name" type="text" required class="form-input" value="<?php echo htmlspecialchars($form_data['signup_last_name'] ?? ''); ?>" />
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Middle Name <span class="optional-text">(Optional)</span></label>
                    <input name="signup_middle_name" type="text" class="form-input" value="<?php echo htmlspecialchars($form_data['signup_middle_name'] ?? ''); ?>" />
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Gender</label>
                        <select name="signup_gender" required class="form-select">
                            <option value="">Select Gender</option>
                            <option value="Male" <?php echo (isset($form_data['signup_gender']) && $form_data['signup_gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo (isset($form_data['signup_gender']) && $form_data['signup_gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo (isset($form_data['signup_gender']) && $form_data['signup_gender'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date of Birth</label>
                        <input name="signup_dob" type="date" required class="form-input" value="<?php echo htmlspecialchars($form_data['signup_dob'] ?? ''); ?>" />
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Contact Number</label>
                    <input name="signup_contact" type="tel" required class="form-input" value="<?php echo htmlspecialchars($form_data['signup_contact'] ?? ''); ?>" />
                </div>

                <div class="form-group">
                    <label class="form-label">Address</label>
                    <textarea name="signup_address" required class="form-textarea"><?php echo htmlspecialchars($form_data['signup_address'] ?? ''); ?></textarea>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">RFID Number <span class="optional-text">(Optional)</span></label>
                        <input name="signup_rfid" type="text" class="form-input" placeholder="Enter RFID number" value="<?php echo htmlspecialchars($form_data['signup_rfid'] ?? ''); ?>" />
                    </div>
                    <div class="form-group">
                        <label class="form-label">Staff Number</label>
                        <input name="signup_staff_number" type="text" required class="form-input" placeholder="Enter staff number" value="<?php echo htmlspecialchars($form_data['signup_staff_number'] ?? ''); ?>" />
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Certificate of Registration</label>
                    <input type="file" name="signup_cor" accept="image/*,.pdf" required class="form-input" style="padding: 0.5rem;" />
                    <p class="helper-text">Upload image or PDF file</p>
                </div>

                <div class="form-group">
                    <label class="form-label">Medical Certificate</label>
                    <input type="file" name="signup_medical_cert" accept="image/*,.pdf" required class="form-input" style="padding: 0.5rem;" />
                    <p class="helper-text">Upload image or PDF file</p>
                </div>

                <div class="form-group">
                    <label class="form-label">Identification Card (ID)</label>
                    <input type="file" name="signup_id_card" accept="image/*,.pdf" required class="form-input" style="padding: 0.5rem;" />
                    <p class="helper-text">Upload image or PDF file</p>
                </div>

                <div class="form-group">
                    <label class="form-label">Hire Date</label>
                    <input name="signup_hire_date" type="date" required class="form-input" value="<?php echo htmlspecialchars($form_data['signup_hire_date'] ?? ''); ?>" />
                    <p class="helper-text">Your account will be pending staff approval.</p>
                </div>

                <div class="form-group">
                    <label class="form-label">Profile Picture <span class="optional-text">(Optional)</span></label>
                    <div class="upload-wrapper">
                        <div class="upload-info">
                            <div class="preview-container" id="preview-container-staff">
                                <img id="preview-image-staff" src="img/image.png" alt="Preview" class="preview-image">
                            </div>
                            <span id="no-file-text-staff" class="no-file-text">No image chosen</span>
                        </div>
                        <label class="btn-upload">
                            <span>Choose File</span>
                            <input type="file" name="signup_picture" id="signup_picture_staff" accept="image/*" class="hidden">
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <button type="submit" name="signup" class="btn btn-success">Create Staff Account</button>
                </div>

                <div class="text-center">
                    <button type="button" id="back-to-login-staff" class="text-link">Already have an account? Log In</button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/auth.js"></script>
</body>
</html>
