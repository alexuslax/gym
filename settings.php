<?php
session_start();
require_once 'config/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$success_message = '';
$error_message = '';

// Create upload directory if it doesn't exist
$upload_dir = 'img/profiles/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'update_profile') {
            $full_name = sanitizeInput($_POST['full_name']);
            $email = sanitizeInput($_POST['email']);
            
            try {
                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ? WHERE user_id = ?");
                $stmt->execute([$full_name, $email, $_SESSION['user_id']]);
                
                // Update session
                $_SESSION['full_name'] = $full_name;
                
                $success_message = 'Profile updated successfully!';
            } catch (PDOException $e) {
                $error_message = 'Error updating profile: ' . $e->getMessage();
            }
        } elseif ($_POST['action'] == 'update_picture') {
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['profile_picture']['tmp_name'];
                $file_name = $_FILES['profile_picture']['name'];
                $file_size = $_FILES['profile_picture']['size'];
                $file_type = mime_content_type($file_tmp);
                
                // Validate file type
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!in_array($file_type, $allowed_types)) {
                    $error_message = 'Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.';
                } elseif ($file_size > 5 * 1024 * 1024) { // 5MB limit
                    $error_message = 'File size must be less than 5MB.';
                } else {
                    try {
                        // Generate unique filename
                        $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
                        $new_filename = 'user_' . $_SESSION['user_id'] . '_' . time() . '.' . $file_extension;
                        $upload_path = $upload_dir . $new_filename;
                        
                        // Delete old profile picture if exists (check role-specific table)
                        $old_picture = null;
                        if ($_SESSION['role'] === 'member') {
                            $stmt = $pdo->prepare("SELECT profile_picture FROM members WHERE user_id = ?");
                            $stmt->execute([$_SESSION['user_id']]);
                            $result = $stmt->fetch();
                            if ($result && $result['profile_picture']) {
                                $old_picture = $result['profile_picture'];
                            }
                        } elseif ($_SESSION['role'] === 'trainer') {
                            $stmt = $pdo->prepare("SELECT profile_picture FROM trainers WHERE username = ?");
                            $stmt->execute([$_SESSION['username']]);
                            $result = $stmt->fetch();
                            if ($result && $result['profile_picture']) {
                                $old_picture = $result['profile_picture'];
                            }
                        }
                        
                        if ($old_picture && file_exists($old_picture)) {
                            unlink($old_picture);
                        }
                        
                        // Move uploaded file
                        if (move_uploaded_file($file_tmp, $upload_path)) {
                            // Update database in appropriate table based on role
                            if ($_SESSION['role'] === 'member') {
                                $stmt = $pdo->prepare("UPDATE members SET profile_picture = ? WHERE user_id = ?");
                                $stmt->execute([$upload_path, $_SESSION['user_id']]);
                            } elseif ($_SESSION['role'] === 'trainer') {
                                $stmt = $pdo->prepare("UPDATE trainers SET profile_picture = ? WHERE username = ?");
                                $stmt->execute([$upload_path, $_SESSION['username']]);
                            }
                            
                            // Update session
                            $_SESSION['profile_picture'] = $upload_path;
                            
                            $success_message = 'Profile picture updated successfully!';
                        } else {
                            $error_message = 'Failed to upload file. Please try again.';
                        }
                    } catch (PDOException $e) {
                        $error_message = 'Error updating profile picture: ' . $e->getMessage();
                    }
                }
            } else {
                $error_message = 'Please select a file to upload.';
            }
        } elseif ($_POST['action'] == 'change_password') {
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];
            
            if ($new_password !== $confirm_password) {
                $error_message = 'New passwords do not match.';
            } else {
                try {
                    // Verify current password
                    $stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $user = $stmt->fetch();
                    
                    if ($user && password_verify($current_password, $user['password'])) {
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                        $stmt->execute([$hashed_password, $_SESSION['user_id']]);
                        $success_message = 'Password changed successfully!';
                    } else {
                        $error_message = 'Current password is incorrect.';
                    }
                } catch (PDOException $e) {
                    $error_message = 'Error changing password: ' . $e->getMessage();
                }
            }
        }
    }
}

// Get user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Get profile picture from appropriate table based on role
if (!isset($user['profile_picture'])) {
    $user['profile_picture'] = null;
}

if ($_SESSION['role'] === 'member') {
    $stmt = $pdo->prepare("SELECT profile_picture FROM members WHERE username = ? OR user_id = ? LIMIT 1");
    $stmt->execute([$_SESSION['username'], $_SESSION['user_id']]);
    $result = $stmt->fetch();
    if ($result && $result['profile_picture']) {
        $user['profile_picture'] = $result['profile_picture'];
    }
} elseif ($_SESSION['role'] === 'trainer') {
    $stmt = $pdo->prepare("SELECT profile_picture FROM trainers WHERE username = ? LIMIT 1");
    $stmt->execute([$_SESSION['username']]);
    $result = $stmt->fetch();
    if ($result && $result['profile_picture']) {
        $user['profile_picture'] = $result['profile_picture'];
    }
}

$page_title = 'Settings - UEP Fitness Gym';
include 'header.php';
?>

<style>
    .settings-container {
        max-width: 900px;
        margin: 0 auto;
        padding: 2rem 1rem;
    }

    .page-header {
        margin-bottom: 2rem;
    }

    .page-title {
        font-size: 2.25rem;
        font-weight: 800;
        margin-bottom: 0.5rem;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .page-subtitle {
        font-size: 1.125rem;
        color: #64748b;
        font-weight: 500;
    }

    .alert {
        padding: 1rem;
        border-radius: 0.75rem;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        animation: slideDown 0.3s ease;
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

    .alert-success {
        background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
        border: 1px solid #bbf7d0;
        color: #166534;
    }

    .alert-error {
        background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
        border: 1px solid #fecaca;
        color: #991b1b;
    }

    .alert svg {
        width: 1.25rem;
        height: 1.25rem;
        flex-shrink: 0;
        margin-top: 0.125rem;
    }

    .card {
        background: white;
        border-radius: 1rem;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08);
        border: 1px solid rgba(0, 0, 0, 0.05);
        overflow: hidden;
        margin-bottom: 1.5rem;
    }

    .card-header {
        padding: 1.5rem;
        border-bottom: 1px solid #e2e8f0;
        background: linear-gradient(to right, white, #f8fafc);
    }

    .card-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 0.25rem;
    }

    .card-subtitle {
        font-size: 0.875rem;
        color: #64748b;
        margin-top: 0.25rem;
    }

    .card-body {
        padding: 1.5rem;
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-group:last-child {
        margin-bottom: 0;
    }

    .form-label {
        display: block;
        font-size: 0.875rem;
        font-weight: 600;
        color: #334155;
        margin-bottom: 0.5rem;
    }

    .form-input, .form-textarea {
        width: 100%;
        padding: 0.75rem 1rem;
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
        font-size: 0.9375rem;
        transition: all 0.2s ease;
        background: #f8fafc;
        font-family: inherit;
    }

    .form-input:focus, .form-textarea:focus {
        outline: none;
        border-color: #667eea;
        background: white;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .form-input:disabled {
        background: #f1f5f9;
        color: #94a3b8;
        cursor: not-allowed;
    }

    .form-help {
        font-size: 0.75rem;
        color: #64748b;
        margin-top: 0.375rem;
    }

    .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 1rem;
        margin-top: 1.5rem;
        padding-top: 1.5rem;
        border-top: 1px solid #e2e8f0;
    }

    .btn {
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 0.75rem;
        font-size: 0.9375rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        font-family: inherit;
        display: inline-flex;
        align-items: center;
        justify-content: center;
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

    .btn-danger {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
    }

    .btn-danger:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(239, 68, 68, 0.5);
    }

    .btn-danger:active {
        transform: translateY(0);
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
    }

    .info-item {
        padding: 1rem;
        background: #f8fafc;
        border-radius: 0.75rem;
        border: 1px solid #e2e8f0;
    }

    .info-label {
        font-size: 0.75rem;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.5rem;
    }

    .info-value {
        font-size: 1rem;
        font-weight: 700;
        color: #0f172a;
    }

    .badge {
        display: inline-block;
        padding: 0.375rem 0.75rem;
        background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
        color: #1e40af;
        border-radius: 0.5rem;
        font-size: 0.875rem;
        font-weight: 600;
        border: 1px solid #93c5fd;
    }

    .profile-picture-section {
        display: flex;
        align-items: center;
        gap: 2rem;
        padding-bottom: 1.5rem;
        border-bottom: 1px solid #e2e8f0;
        margin-bottom: 1.5rem;
    }

    .profile-picture-preview {
        position: relative;
        flex-shrink: 0;
    }

    .profile-picture-img {
        width: 120px;
        height: 120px;
        border-radius: 1rem;
        object-fit: cover;
        border: 3px solid #e2e8f0;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .profile-picture-placeholder {
        width: 120px;
        height: 120px;
        border-radius: 1rem;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        border: 3px solid #e2e8f0;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        font-size: 3rem;
        color: white;
    }

    .picture-upload-input {
        display: none;
    }

    .picture-upload-label {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.75rem 1.5rem;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 0.75rem;
        font-weight: 600;
        font-size: 0.9375rem;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        border: none;
    }

    .picture-upload-label:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.5);
    }

    .picture-upload-label:active {
        transform: translateY(0);
    }

    .picture-upload-info {
        flex: 1;
    }

    .picture-upload-help {
        font-size: 0.75rem;
        color: #64748b;
        margin-top: 0.5rem;
        line-height: 1.5;
    }

    @media (max-width: 768px) {
        .settings-container {
            padding: 1rem 0.5rem;
        }

        .page-title {
            font-size: 1.875rem;
        }

        .page-subtitle {
            font-size: 1rem;
        }

        .card-header {
            padding: 1rem;
        }

        .card-body {
            padding: 1rem;
        }

        .form-actions {
            flex-direction: column;
        }

        .btn {
            width: 100%;
        }

        .info-grid {
            grid-template-columns: 1fr;
        }

        .profile-picture-section {
            flex-direction: column;
            align-items: flex-start;
            gap: 1.5rem;
        }
    }
</style>

<div class="settings-container">
    <!-- Page Header -->
    <div class="page-header">
        <h2 class="page-title">Settings</h2>
        <p class="page-subtitle">Manage your account settings and preferences</p>
    </div>

    <!-- Success/Error Messages -->
    <?php if ($success_message): ?>
        <div class="alert alert-success">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
            </svg>
            <span><?php echo htmlspecialchars($success_message); ?></span>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-error">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0zm-9 3.75h.008v.008H12v-.008z"/>
            </svg>
            <span><?php echo htmlspecialchars($error_message); ?></span>
        </div>
    <?php endif; ?>

    <!-- Profile Picture Settings -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Profile Picture</h3>
            <p class="card-subtitle">Upload and manage your profile photo</p>
        </div>
        <div class="card-body">
            <div class="profile-picture-section">
                <div class="profile-picture-preview">
                    <?php if ($user['profile_picture'] && file_exists($user['profile_picture'])): ?>
                        <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile Picture" class="profile-picture-img" id="profilePreview">
                    <?php else: ?>
                        <div class="profile-picture-placeholder" id="profilePreview">👤</div>
                    <?php endif; ?>
                </div>
                <div class="picture-upload-info">
                    <form method="POST" enctype="multipart/form-data" id="pictureForm">
                        <input type="hidden" name="action" value="update_picture">
                        <input type="file" id="profilePictureInput" name="profile_picture" class="picture-upload-input" accept="image/jpeg,image/png,image/gif,image/webp">
                        <label for="profilePictureInput" class="picture-upload-label">Choose Photo</label>
                        <p class="picture-upload-help">
                            Supported formats: JPG, PNG, GIF, WebP<br>
                            Maximum file size: 5MB<br>
                            Recommended: Square image (500x500px or larger)
                        </p>
                    </form>
                </div>
            </div>
            <script>
                document.getElementById('profilePictureInput').addEventListener('change', function() {
                    // Preview image before upload
                    const file = this.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const preview = document.getElementById('profilePreview');
                            if (preview.tagName === 'IMG') {
                                preview.src = e.target.result;
                            } else {
                                const img = document.createElement('img');
                                img.src = e.target.result;
                                img.alt = 'Profile Picture';
                                img.className = 'profile-picture-img';
                                img.id = 'profilePreview';
                                preview.parentNode.replaceChild(img, preview);
                            }
                        };
                        reader.readAsDataURL(file);
                        
                        // Auto-submit the form
                        document.getElementById('pictureForm').submit();
                    }
                });
            </script>
        </div>
    </div>

    <!-- Profile Information -->
        <div class="card-header">
            <h3 class="card-title">Profile Information</h3>
            <p class="card-subtitle">Update your personal information</p>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="update_profile">
                
                <div class="form-group">
                    <label for="full_name" class="form-label">Full Name</label>
                    <input type="text" 
                           id="full_name" 
                           name="full_name" 
                           value="<?php echo htmlspecialchars($user['full_name']); ?>" 
                           required
                           class="form-input" />
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           value="<?php echo htmlspecialchars($user['email']); ?>" 
                           required
                           class="form-input" />
                </div>

                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input type="text" 
                           value="<?php echo htmlspecialchars($user['username']); ?>" 
                           disabled
                           class="form-input" />
                    <p class="form-help">Username cannot be changed</p>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Password Settings -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Change Password</h3>
            <p class="card-subtitle">Update your password to keep your account secure</p>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="change_password">
                
                <div class="form-group">
                    <label for="current_password" class="form-label">Current Password</label>
                    <input type="password" 
                           id="current_password" 
                           name="current_password" 
                           required
                           class="form-input" />
                </div>

                <div class="form-group">
                    <label for="new_password" class="form-label">New Password</label>
                    <input type="password" 
                           id="new_password" 
                           name="new_password" 
                           required
                           minlength="6"
                           class="form-input" />
                    <p class="form-help">Password must be at least 6 characters long</p>
                </div>

                <div class="form-group">
                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                    <input type="password" 
                           id="confirm_password" 
                           name="confirm_password" 
                           required
                           minlength="6"
                           class="form-input" />
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-danger">
                        Change Password
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Account Information -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Account Information</h3>
            <p class="card-subtitle">View your account details</p>
        </div>
        <div class="card-body">
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">User ID</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['user_id']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Role</div>
                    <div class="info-value">
                        <span class="badge">
                            <?php echo ucfirst($user['role']); ?>
                        </span>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Account Created</div>
                    <div class="info-value"><?php echo date('F j, Y', strtotime($user['created_at'])); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Last Updated</div>
                    <div class="info-value"><?php echo date('F j, Y', strtotime($user['updated_at'])); ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
