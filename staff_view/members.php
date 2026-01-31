  <?php
  session_start();
  require_once '../config/functions.php';


  // Handle form submissions
  if ($_SERVER['REQUEST_METHOD'] == 'POST') {
      if (isset($_POST['action'])) {
          switch ($_POST['action']) {
        case 'approve_member':
            $user_id = sanitizeInput($_POST['user_id']);
            try {
                $pdo->beginTransaction();
                
                // Get user information and pending data
                $stmt = $pdo->prepare("SELECT user_id, username, email, role, pending_data FROM users WHERE user_id = ? AND is_active = 0");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();
                
                if ($user && !empty($user['pending_data'])) {
                    $pending_data = json_decode($user['pending_data'], true);
                    
                    if ($user['role'] === 'student') {
                        // Generate student ID
                        $student_id = generateID('STU', 'members', 'member_id');
                        
                        // Create member record
                        $stmt = $pdo->prepare("
                            INSERT INTO members (
                                member_id, first_name, last_name, middle_name, username,
                                gender, contact_number, address, date_of_birth,
                                membership_status, registration_date, email, 
                                profile_picture, user_id
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active', ?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $student_id,
                            $pending_data['first_name'],
                            $pending_data['last_name'],
                            $pending_data['middle_name'] ?? '',
                            $user['username'],
                            $pending_data['gender'],
                            $pending_data['contact_number'],
                            $pending_data['address'],
                            $pending_data['date_of_birth'],
                            $pending_data['registration_date'],
                            $user['email'],
                            $pending_data['profile_picture'] ?? null,
                            $user['user_id']
                        ]);
                    } elseif ($user['role'] === 'faculty') {
                        // Generate faculty ID
                        $faculty_id = generateID('FAC', 'trainers', 'trainer_id');
                        
                        // Create trainer record
                        $stmt = $pdo->prepare("
                            INSERT INTO trainers (
                                trainer_id, username, first_name, last_name, middle_name,
                                gender, contact_number, email, address, date_of_birth,
                                specialization, status, hire_date, profile_picture
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active', ?, ?)
                        ");
                        $stmt->execute([
                            $faculty_id,
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
                      } elseif ($user['role'] === 'staff') {
                        // Generate staff ID
                        $staff_id = generateID('STF', 'staff', 'staff_id');

                        // Insert into staff table
                        $stmt = $pdo->prepare("INSERT INTO staff (
                          staff_id, username, first_name, last_name, middle_name,
                          gender, contact_number, email, address, date_of_birth,
                          staff_number, profile_picture, status, hire_date, user_id
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active', ?, ?)");

                        $stmt->execute([
                          $staff_id,
                          $user['username'],
                          $pending_data['first_name'],
                          $pending_data['last_name'],
                          $pending_data['middle_name'] ?? '',
                          $pending_data['gender'],
                          $pending_data['contact_number'],
                          $user['email'],
                          $pending_data['address'],
                          $pending_data['date_of_birth'],
                          $pending_data['staff_number'] ?? '',
                          $pending_data['profile_picture'] ?? null,
                          $pending_data['registration_date'] ?? date('Y-m-d'),
                          $user['user_id']
                        ]);
                    }
                    
                    // Activate user account and clear pending data
                    $stmt = $pdo->prepare("UPDATE users SET is_active = 1, pending_data = NULL WHERE user_id = ?");
                    $stmt->execute([$user['user_id']]);
                    
                    $pdo->commit();
                    header('Location: members.php?success=User approved successfully');
                } else {
                    $pdo->rollBack();
                    header('Location: members.php?error=Pending user not found or invalid data');
                }
                exit();
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('Member approval error: ' . $e->getMessage());
                header('Location: members.php?error=Database error occurred: ' . $e->getMessage());
                exit();
            }
            break;
            
        case 'reject_member':
            $user_id = sanitizeInput($_POST['user_id']);
            try {
                // Delete pending user account
                $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ? AND is_active = 0");
                $stmt->execute([$user_id]);
                
                header('Location: members.php?success=Member registration rejected');
                exit();
            } catch (PDOException $e) {
                error_log('Member rejection error: ' . $e->getMessage());
                header('Location: members.php?error=Failed to reject member');
                exit();
            }
            break;
            
        case 'add_member':
        $rfid_card_number = sanitizeInput($_POST['rfid_card_number']);
        error_log('RFID ADD: ' . $rfid_card_number);
        $member_id = generateID('MEM', 'members', 'member_id');
        $user_id = generateID('USR', 'users', 'user_id');
      $username = sanitizeInput($_POST['username']);
      $email = sanitizeInput($_POST['email']);
      $first_name = sanitizeInput($_POST['first_name']);
      $last_name = sanitizeInput($_POST['last_name']);
      $middle_name = !empty($_POST['middle_name']) ? sanitizeInput($_POST['middle_name']) : null;
      $gender = sanitizeInput($_POST['gender']);
      $contact_number = sanitizeInput($_POST['contact_number']);
      $address = sanitizeInput($_POST['address']);
      $date_of_birth = sanitizeInput($_POST['date_of_birth']);
      $rfid_card_number = sanitizeInput($_POST['rfid_card_number']);

      // Handle profile picture upload
      $profile_picture = null;
      if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
          $upload_dir = __DIR__ . '/../img/profiles/';
          if (!file_exists($upload_dir)) {
              mkdir($upload_dir, 0777, true);
          }
          
          $file_extension = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
          $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
          
          if (in_array($file_extension, $allowed_extensions)) {
              $new_filename = $member_id . '_' . time() . '.' . $file_extension;
              $upload_path = $upload_dir . $new_filename;
              
              if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                  $profile_picture = 'img/profiles/' . $new_filename;
                  error_log('Profile picture uploaded: ' . $profile_picture);
              } else {
                  error_log('Failed to move uploaded file');
              }
          } else {
              error_log('Invalid file extension: ' . $file_extension);
          }
      } else {
          if (isset($_FILES['profile_picture'])) {
              error_log('File upload error code: ' . $_FILES['profile_picture']['error']);
          } else {
              error_log('No profile_picture file in request');
          }
      }

      // First create user record
      $user_stmt = $pdo->prepare("INSERT INTO users (user_id, username, email, password, role, is_active) VALUES (?, ?, ?, ?, 'student', 1)");
      $default_password = password_hash('password123', PASSWORD_DEFAULT);
      $user_stmt->execute([$user_id, $username, $email, $default_password]);

      // Then create member record
      error_log('Saving profile_picture to DB: ' . ($profile_picture ?? 'NULL'));
      $stmt = $pdo->prepare("INSERT INTO members 
          (member_id, user_id, username, email, first_name, middle_name, last_name, gender, contact_number, address, date_of_birth, rfid_card_number, profile_picture, membership_status) 
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active')");

      $stmt->execute([
          $member_id, $user_id, $username, $email, $first_name, $middle_name, $last_name,
          $gender, $contact_number, $address, $date_of_birth,
          $rfid_card_number, $profile_picture
      ]);

      header('Location: members.php?success=Member added successfully');
      exit();
      break;

                  
              case 'update_member':
    $rfid_card_number = sanitizeInput($_POST['rfid_card_number']);
    error_log('RFID UPDATE: ' . $rfid_card_number);
      $member_id = sanitizeInput($_POST['member_id']);
      $username = sanitizeInput($_POST['username']);
      $first_name = sanitizeInput($_POST['first_name']);
      $middle_name = !empty($_POST['middle_name']) ? sanitizeInput($_POST['middle_name']) : null;
      $last_name = sanitizeInput($_POST['last_name']);
      $gender = sanitizeInput($_POST['gender']);
      $contact_number = sanitizeInput($_POST['contact_number']);
      $address = sanitizeInput($_POST['address']);
      $date_of_birth = sanitizeInput($_POST['date_of_birth']);
      $membership_plan = sanitizeInput($_POST['membership_plan']);
      $membership_status = sanitizeInput($_POST['membership_status']);
      $email = isset($_POST['email']) ? sanitizeInput($_POST['email']) : '';

      $stmt = $pdo->prepare("UPDATE members 
      SET username=?, first_name=?, middle_name=?, last_name=?, gender=?, contact_number=?, address=?, date_of_birth=?, membership_plan=?, membership_status=?, email=?, rfid_card_number=? 
      WHERE member_id=?");

  $stmt->execute([
      $username, $first_name, $middle_name, $last_name, $gender,
      $contact_number, $address, $date_of_birth,
      $membership_plan, $membership_status, $email,
      $rfid_card_number, $member_id
  ]);

      // Check if there's a return URL, otherwise redirect to members.php
      $return_to = isset($_POST['return_to']) ? $_POST['return_to'] : 'members.php';
      header('Location: ' . $return_to . (strpos($return_to, '?') !== false ? '&' : '?') . 'success=Member updated successfully');
      exit();
      break;

          }
      }
  }

  // Get search parameters
  $search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
  $status_filter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
  $plan_filter = isset($_GET['plan']) ? sanitizeInput($_GET['plan']) : '';

  // Build query
  $where_conditions = [];
  $params = [];

  if (!empty($search)) {
      $where_conditions[] = "(CONCAT(first_name, ' ', middle_name, ' ', last_name) LIKE ? OR member_id LIKE ? OR username LIKE ?)";
      $search_param = "%$search%";
      $params[] = $search_param;
      $params[] = $search_param;
      $params[] = $search_param;
  }

  if (!empty($status_filter)) {
      $where_conditions[] = "membership_status = ?";
      $params[] = $status_filter;
  }

  if (!empty($plan_filter)) {
      $where_conditions[] = "b.plan_id = ?";
      $params[] = $plan_filter;
  }

  $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

  // Get active members (exclude pending) - join with billing to get plan info
  $active_where = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) . " AND m.membership_status != 'Pending'" : "WHERE m.membership_status != 'Pending'";
  $sql = "SELECT m.*, mp.plan_name, mp.price, b.plan_id
          FROM members m
          LEFT JOIN (
              SELECT member_id, plan_id, MAX(created_at) as latest
              FROM billing
              GROUP BY member_id
          ) latest_billing ON m.member_id = latest_billing.member_id
          LEFT JOIN billing b ON latest_billing.member_id = b.member_id AND latest_billing.latest = b.created_at
          LEFT JOIN membership_plans mp ON b.plan_id = mp.plan_id
          $active_where
          ORDER BY m.registration_date DESC";
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $members = $stmt->fetchAll();

  // Get pending members separately - query users table for pending approvals
  // These are users who registered but haven't been approved yet (no member_id assigned)
  // Include legacy and new roles that may be pending approval
  $pending_where_conditions = ["is_active = ?", "role IN ('member','student','faculty','trainer','staff')"];
  $pending_params = [0];
  
  // Apply search filter to pending members if provided
  if (!empty($search)) {
    $pending_where_conditions[] = "(full_name LIKE ? OR user_id LIKE ? OR username LIKE ? OR email LIKE ?)";
    $search_param = "%$search%";
    $pending_params[] = $search_param;
    $pending_params[] = $search_param;
    $pending_params[] = $search_param;
    $pending_params[] = $search_param;
  }
  
  $pending_where_clause = "WHERE " . implode(" AND ", $pending_where_conditions);
  $pending_sql = "SELECT u.user_id, u.username, u.email, u.full_name, u.pending_data, u.created_at as registration_date
                  FROM users u
                  $pending_where_clause
                  ORDER BY u.created_at DESC";
  $stmt = $pdo->prepare($pending_sql);
  $stmt->execute($pending_params);
  $pending_users = $stmt->fetchAll();
  
  // Parse pending data for display
  $pending_members = [];
  foreach ($pending_users as $user) {
    $pending_data = json_decode($user['pending_data'], true);
    if ($pending_data) {
      $pending_members[] = [
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
          'profile_picture' => $pending_data['profile_picture'] ?? null,
        'registration_date' => $user['registration_date'],
        'membership_status' => 'Pending'
        ];
        // Attach any uploaded documents for reviewer convenience
        if (!empty($pending_data['cor_document'])) {
          $pending_members[count($pending_members)-1]['cor_document'] = $pending_data['cor_document'];
        }
        if (!empty($pending_data['medical_certificate'])) {
          $pending_members[count($pending_members)-1]['medical_certificate'] = $pending_data['medical_certificate'];
        }
        if (!empty($pending_data['id_card'])) {
          $pending_members[count($pending_members)-1]['id_card'] = $pending_data['id_card'];
        }
        if (!empty($pending_data['rfid_number'])) {
          $pending_members[count($pending_members)-1]['rfid_number'] = $pending_data['rfid_number'];
        }
        if (!empty($pending_data['student_number'])) {
          $pending_members[count($pending_members)-1]['student_number'] = $pending_data['student_number'];
        }
        if (!empty($pending_data['faculty_number'])) {
          $pending_members[count($pending_members)-1]['faculty_number'] = $pending_data['faculty_number'];
        }
        if (!empty($pending_data['staff_number'])) {
          $pending_members[count($pending_members)-1]['staff_number'] = $pending_data['staff_number'];
        }
    }
  }


  // Resolve a safe, displayable profile image URL from database value
  function resolveProfileImagePath($value) {
    $v = trim($value ?? '');
    if ($v === '') return null;
    // Absolute URL
    if (preg_match('#^https?://#', $v)) {
      return $v;
    }
    $baseDir = __DIR__;
    // Paths relative to gym root (e.g., img/profiles/..., uploads/..., assets/...)
    if (preg_match('#^(img|uploads|assets)/#', $v)) {
      $fsPath = $baseDir . '/../' . $v;
      if (pathinfo($v, PATHINFO_EXTENSION) === '') {
        foreach (["jpg","jpeg","png"] as $ext) {
          $candidate = $fsPath . '.' . $ext;
          if (file_exists($candidate)) {
            return '../' . $v . '.' . $ext;
          }
        }
      }
      if (file_exists($fsPath)) {
        return '../' . $v;
      }
      // If file not found, don't return a broken URL
      return null;
    }
    // Bare filename: look under img/profiles/
    $dirFs = $baseDir . '/../img/profiles/';
    $dirWeb = '../img/profiles/';
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

  // Get member for editing
  $edit_member = null;
  if (isset($_GET['edit'])) {
      $edit_id = sanitizeInput($_GET['edit']);
      $stmt = $pdo->prepare("SELECT * FROM members WHERE member_id = ?");
      $stmt->execute([$edit_id]);
      $edit_member = $stmt->fetch();
  }
  ?>
  <?php $page_title = 'Members Management - UEP Fitness Gym'; include '../header.php'; ?>

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

      <!-- Page Header -->
      <div class="page-header">
        <h2 class="page-title">Members Management</h2>
        <p class="page-subtitle">Manage gym members, their profiles, and membership status.</p>
      </div>

      <div style="margin-bottom: 2rem;">
        <button type="button" id="addMemberBtn" onclick="openMemberAddModal(); return false;" class="btn btn-primary" style="cursor: pointer;">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1.25rem; height: 1.25rem;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
          </svg>
          Add New Member
        </button>
      </div>

      <!-- Search and Filters -->
      <div class="card mb-8">
        <h3 class="text-lg font-semibold text-slate-900 mb-4 flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5 text-blue-600">
            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607z"/>
          </svg>
          Search & Filter Members
        </h3>
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div class="relative">
            <input type="text" name="search" placeholder="Search by name or ID" value="<?php echo htmlspecialchars($search); ?>" class="form-input pl-10">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5 text-gray-400 absolute left-3 top-3.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607z"/>
            </svg>
          </div>
          <select name="status" class="form-select">
            <option value="">All Status</option>
            <option value="Active" <?php echo $status_filter == 'Active' ? 'selected' : ''; ?>>Active</option>
            <option value="Pending" <?php echo $status_filter == 'Pending' ? 'selected' : ''; ?>>Pending</option>
            <option value="Expired" <?php echo $status_filter == 'Expired' ? 'selected' : ''; ?>>Expired</option>
          </select>
          <select name="plan" class="form-select">
            <option value="">All Plans</option>
            <option value="Monthly" <?php echo $plan_filter == 'Monthly' ? 'selected' : ''; ?>>Monthly</option>
            <option value="Quarterly" <?php echo $plan_filter == 'Quarterly' ? 'selected' : ''; ?>>Quarterly</option>
            <option value="Annual" <?php echo $plan_filter == 'Annual' ? 'selected' : ''; ?>>Annual</option>
          </select>
          <button type="submit" class="btn btn-primary">
            Search
          </button>
        </form>
      </div>

      <!-- Tabs -->
      <div class="tabs">
        <nav class="tab-nav" aria-label="Tabs">
          <button onclick="switchTab('active')" id="tab-active" class="tab-button active">
            Active Members
            <span class="badge badge-blue ml-2"><?php echo count($members); ?></span>
          </button>
          <button onclick="switchTab('pending')" id="tab-pending" class="tab-button">
            Pending Approvals
            <span class="badge badge-yellow ml-2"><?php echo count($pending_members); ?></span>
          </button>
        </nav>
      </div>

    <!-- Active Members Cards -->
    <div id="active-members-tab" class="tab-content active">
      <?php if (empty($members)): ?>
        <div class="empty-state">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="empty-state-icon">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.5v-1.125A3.375 3.375 0 0 0 11.625 15h-3.75A3.375 3.375 0 0 0 4.5 18.375V19.5M12 10.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0M18 8.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0M20.25 19.5v-1.125A3.375 3.375 0 0 0 17.25 15h-1.125"/>
          </svg>
          <p class="empty-state-title">No members found</p>
          <p class="empty-state-subtitle">Try adjusting your search or filter criteria.</p>
        </div>
      <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <?php foreach ($members as $member): ?>
            <div class="card-member">
              <div class="p-6">
                <div class="flex items-start gap-4 mb-4">
                  <div class="flex-shrink-0">
                    <div class="h-16 w-16 rounded-2xl bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center shadow-md ring-2 ring-white">
                      <?php $img = resolveProfileImagePath($member['profile_picture']); ?>
                      <?php if ($img): ?>
                        <img src="<?php echo htmlspecialchars($img); ?>" class="h-full w-full object-cover rounded-2xl" alt="">
                      <?php else: ?>
                        <span class="text-white text-2xl font-bold">
                          <?php echo strtoupper(substr($member['first_name'], 0, 1)); ?>
                        </span>
                      <?php endif; ?>
                    </div>
                  </div>
                  <div class="flex-1 min-w-0">
                    <h3 class="text-lg font-bold text-gray-900 mb-1 truncate">
                      <?php echo htmlspecialchars(trim($member['first_name'] . ' ' . ($member['middle_name'] ? $member['middle_name'] . ' ' : '') . $member['last_name'])); ?>
                    </h3>
                    <p class="text-sm text-gray-500"><?php echo htmlspecialchars($member['member_id']); ?></p>
                    <p class="text-xs text-gray-400 mt-1"><?php echo htmlspecialchars($member['contact_number']); ?></p>
                  </div>
                </div>

                <div class="grid grid-cols-2 gap-3 mb-4">
                  <div class="bg-blue-50 rounded-xl p-3">
                    <p class="text-xs text-blue-600 font-medium mb-1">Plan</p>
                    <p class="text-sm font-bold text-blue-900"><?php echo !empty($member['plan_name']) ? htmlspecialchars($member['plan_name']) : 'Not Assigned'; ?></p>
                  </div>
                  <div class="bg-purple-50 rounded-xl p-3">
                    <p class="text-xs text-purple-600 font-medium mb-1">RFID</p>
                    <p class="text-xs font-semibold text-purple-900 truncate">
                      <?php echo !empty($member['rfid_card_number']) ? htmlspecialchars($member['rfid_card_number']) : 'N/A'; ?>
                    </p>
                  </div>
                </div>

                <div class="space-y-2 mb-4 text-xs text-gray-600">
                  <div class="flex items-center justify-between">
                    <span class="text-gray-500">Date of Birth:</span>
                    <span class="font-medium"><?php echo formatDate($member['date_of_birth']); ?></span>
                  </div>
                  <div class="flex items-center justify-between">
                    <span class="text-gray-500">Email:</span>
                    <span class="font-medium"><?php echo htmlspecialchars($member['email'] ?? 'N/A'); ?></span>
                  </div>
                </div>

                <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                  <?php
                  $status_colors = [
                      'Active' => 'bg-green-100 text-green-800 ring-1 ring-green-200',
                      'Pending' => 'bg-yellow-100 text-yellow-800 ring-1 ring-yellow-200',
                      'Expired' => 'bg-red-100 text-red-800 ring-1 ring-red-200'
                  ];
                  $color_class = $status_colors[$member['membership_status']] ?? 'bg-gray-100 text-gray-800 ring-1 ring-gray-200';
                  ?>
                  <?php
                  $status_badges = [
                      'Active' => 'badge-green',
                      'Pending' => 'badge-yellow',
                      'Expired' => 'badge-red'
                  ];
                  $badge_class = $status_badges[$member['membership_status']] ?? 'badge-gray';
                  ?>
                  <span class="badge <?php echo $badge_class; ?>">
                    <?php echo htmlspecialchars($member['membership_status']); ?>
                  </span>
                  <a href="../member_profile.php?id=<?php echo $member['member_id']; ?>&return=staff_view/members.php" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold text-white rounded-xl shadow-md hover:shadow-lg transition-all duration-200" style="background: linear-gradient(to right, #6366f1, #4f46e5);" 
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

    <!-- Pending Members Cards -->
    <div id="pending-members-tab" class="tab-content" style="display: none;">
      <?php if (empty($pending_members)): ?>
        <div class="empty-state">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="empty-state-icon">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
          </svg>
          <p class="empty-state-title">No pending member approvals</p>
          <p class="empty-state-subtitle">All member registrations have been processed.</p>
        </div>
      <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <?php foreach ($pending_members as $member): ?>
            <div class="card-member border-l-4 border-yellow-500">
              <div class="p-6">
                <div class="flex items-start gap-4 mb-4">
                  <div class="flex-shrink-0">
                    <div class="h-16 w-16 rounded-2xl bg-gradient-to-br from-yellow-400 to-yellow-600 flex items-center justify-center shadow-md ring-2 ring-white">
                      <?php $img = resolveProfileImagePath($member['profile_picture']); ?>
                      <?php if ($img): ?>
                        <img src="<?php echo htmlspecialchars($img); ?>" class="h-full w-full object-cover rounded-2xl" alt="">
                      <?php else: ?>
                        <span class="text-white text-2xl font-bold">
                          <?php echo strtoupper(substr($member['first_name'], 0, 1)); ?>
                        </span>
                      <?php endif; ?>
                    </div>
                  </div>
                  <div class="flex-1 min-w-0">
                    <h3 class="text-lg font-bold text-gray-900 mb-1 truncate">
                      <?php echo htmlspecialchars(trim($member['first_name'] . ' ' . ($member['middle_name'] ? $member['middle_name'] . ' ' : '') . $member['last_name'])); ?>
                    </h3>
                    <p class="text-sm text-gray-500"><?php echo htmlspecialchars($member['username']); ?></p>
                    <p class="text-xs text-gray-400 mt-1"><?php echo htmlspecialchars($member['contact_number']); ?></p>
                  </div>
                </div>

                <div class="grid grid-cols-2 gap-3 mb-4">
                  <div class="bg-blue-50 rounded-xl p-3">
                    <p class="text-xs text-blue-600 font-medium mb-1">Email</p>
                    <p class="text-xs font-bold text-blue-900 truncate"><?php echo htmlspecialchars($member['email']); ?></p>
                  </div>
                  <div class="bg-purple-50 rounded-xl p-3">
                    <p class="text-xs text-purple-600 font-medium mb-1">Status</p>
                    <p class="text-xs font-semibold text-purple-900">
                      Pending Approval
                    </p>
                  </div>
                </div>

                <div class="mb-4 text-xs text-gray-600">
                  <div class="flex items-center justify-between">
                    <span class="text-gray-500">Registration Date:</span>
                    <span class="font-medium"><?php echo formatDate($member['registration_date']); ?></span>
                  </div>
                </div>

                <?php if (!empty($member['cor_document']) || !empty($member['medical_certificate']) || !empty($member['id_card']) || !empty($member['rfid_number'])): ?>
                  <div class="mb-4 text-xs text-gray-600">
                    <div style="display:flex;flex-direction:column;gap:6px;">
                      <?php if (!empty($member['cor_document'])): ?>
                        <a href="<?php echo htmlspecialchars($member['cor_document']); ?>" target="_blank" class="text-blue-600">View Certificate of Registration</a>
                      <?php endif; ?>
                      <?php if (!empty($member['medical_certificate'])): ?>
                        <a href="<?php echo htmlspecialchars($member['medical_certificate']); ?>" target="_blank" class="text-blue-600">View Medical Certificate</a>
                      <?php endif; ?>
                      <?php if (!empty($member['id_card'])): ?>
                        <a href="<?php echo htmlspecialchars($member['id_card']); ?>" target="_blank" class="text-blue-600">View ID Card</a>
                      <?php endif; ?>
                      <?php if (!empty($member['rfid_number'])): ?>
                        <div class="text-xs text-gray-500">RFID: <span class="font-medium"><?php echo htmlspecialchars($member['rfid_number']); ?></span></div>
                      <?php endif; ?>
                    </div>
                  </div>
                <?php endif; ?>

                <div class="flex items-center gap-2 pt-4 border-t border-gray-200">
                  <form method="POST" style="flex: 1;" onsubmit="return confirm('Are you sure you want to approve this member?');">
                    <input type="hidden" name="action" value="approve_member">
                    <input type="hidden" name="user_id" value="<?php echo $member['user_id']; ?>">
                    <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 px-4 py-2 text-sm font-semibold text-white rounded-xl shadow-md hover:shadow-lg transition-all duration-200" style="background: linear-gradient(to right, #10b981, #059669);" 
                       onmouseover="this.style.background='linear-gradient(to right, #059669, #047857)'; this.style.transform='scale(1.02)'"
                       onmouseout="this.style.background='linear-gradient(to right, #10b981, #059669)'; this.style.transform='scale(1)'">
                      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                      </svg>
                      Approve
                    </button>
                  </form>
                  <form method="POST" style="flex: 1;" onsubmit="return confirm('Are you sure you want to reject this member?');">
                    <input type="hidden" name="action" value="reject_member">
                    <input type="hidden" name="user_id" value="<?php echo $member['user_id']; ?>">
                    <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 px-4 py-2 text-sm font-semibold text-white rounded-xl shadow-md hover:shadow-lg transition-all duration-200" style="background: linear-gradient(to right, #ef4444, #dc2626);" 
                       onmouseover="this.style.background='linear-gradient(to right, #dc2626, #b91c1c)'; this.style.transform='scale(1.02)'"
                       onmouseout="this.style.background='linear-gradient(to right, #ef4444, #dc2626)'; this.style.transform='scale(1)'">
                      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                      </svg>
                      Reject
                    </button>
                  </form>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    </div>

    <!-- Add/Edit Member Modal -->
    <div id="memberModal" class="modal-overlay" style="display: none;" onclick="if(event.target === this) closeMemberModal()">
    <div class="modal-content" style="max-width: 48rem;" onclick="event.stopPropagation()">
      <div class="modal-header">
        <h3 class="card-header-title" id="modalTitle">Add New Member</h3>
        <button type="button" id="closeMemberBtn" class="modal-close-btn">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1.5rem; height: 1.5rem;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>
      <form method="POST" enctype="multipart/form-data" class="modal-form-spacing">
        <input type="hidden" name="action" id="formAction" value="add_member">
        <input type="hidden" name="member_id" id="editMemberId">
        <input type="hidden" name="return_to" id="returnTo">
        <div id="registrationDateDiv" style="display:none; padding: var(--spacing-4); background-color: var(--blue-50); border-radius: 0.75rem; border: 1px solid var(--blue-200);"></div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: var(--spacing-6); padding-top: var(--spacing-4); padding-left: var(--spacing-4); padding-right: var(--spacing-4);">
          <div>
            <label class="form-label">Username</label>
            <input type="text" name="username" id="username" required class="form-input">
          </div>
          
          <div>
            <label class="form-label">Email</label>
            <input type="email" name="email" id="email" required class="form-input">
          </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: var(--spacing-6); padding-left: var(--spacing-4); padding-right: var(--spacing-4);">
          <div class="form-group">
            <label class="form-label">First Name</label>
            <input type="text" name="first_name" id="first_name" required class="form-input">
          </div>
          <div class="form-group">
            <label class="form-label">Last Name</label>
            <input type="text" name="last_name" id="last_name" required class="form-input">
          </div>
        </div>
        
        <div style="padding-left: var(--spacing-4); padding-right: var(--spacing-4);">
        <div class="form-group">
          <label class="form-label">Middle Name <span style="font-size: 0.75rem; font-weight: normal; color: var(--gray-500);">(Optional)</span></label>
          <input type="text" name="middle_name" id="middle_name" class="form-input">
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: var(--spacing-6); padding-top: var(--spacing-4);">
          <div class="form-group">
            <label class="form-label">Gender</label>
            <select name="gender" id="gender" required class="form-select">
              <option value="">Select Gender</option>
              <option value="Male">Male</option>
              <option value="Female">Female</option>
              <option value="Other">Other</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Date of Birth</label>
            <input type="date" name="date_of_birth" id="date_of_birth" required class="form-input">
          </div>
        </div>
          
        <div style="padding-top: var(--spacing-4);">
        <div class="form-group">
          <label class="form-label">Contact Number</label>
          <input type="tel" name="contact_number" id="contact_number" required class="form-input">
        </div>
        </div>

        <div style="padding-top: var(--spacing-4);">
        <div class="form-group">
          <label class="form-label">Address</label>
          <textarea name="address" id="address" required rows="3" class="form-textarea"></textarea>
        </div>
        </div>

        <div style="padding-top: var(--spacing-4);">
        <div class="form-group">
          <label class="form-label">RFID Card Number</label>
          <div style="display: flex; gap: var(--spacing-3);">
            <input type="text" name="rfid_card_number" id="rfid_card_number" required class="form-input" style="flex: 1; font-family: monospace;" placeholder="Tap card or click Scan">
            <button type="button" id="scanRfidBtn" class="btn btn-primary" style="white-space: nowrap; background: linear-gradient(to right, var(--green-600), var(--green-700));">
              Scan Card
            </button>
          </div>
          <p style="font-size: 0.75rem; color: var(--gray-500); margin-top: var(--spacing-2); display: flex; align-items: center; gap: 0.25rem;">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 0.875rem; height: 0.875rem;">
              <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"/>
            </svg>
            Tap the RFID card on the reader or click Scan and then tap.
          </p>
        </div>
        </div>

        <div style="padding-top: var(--spacing-4);">
        <div class="form-group">
          <label class="form-label">Profile Picture <span style="font-size: 0.75rem; font-weight: normal; color: var(--gray-500);">(Optional)</span></label>
          <input type="file" name="profile_picture" id="profile_picture" accept="image/*" class="form-input">
        </div>
        </div>

        <div class="modal-footer">
          <button type="submit" class="btn btn-primary" style="background: linear-gradient(to right, var(--green-600), var(--green-700));">
            Save Member
          </button>
        </div>
      </form>
    </div>
    </div>
            <script>
  // ===========================
  // RFID Scan logic for Add/Edit Member
  // ===========================
  const rfidInput = document.getElementById('rfid_card_number');
  const scanBtn = document.getElementById('scanRfidBtn');
  let scanning = false;
  let scanBuffer = '';
  let scanTimeout = null;

  // Ensure RFID value is valid before form submit
  const memberForm = document.querySelector('#memberModal form');
  if (memberForm && rfidInput) {
    memberForm.addEventListener('submit', function(e) {
      if (!rfidInput.value || rfidInput.value.trim().length < 3) {
        alert('Please scan or enter a valid RFID card number.');
        rfidInput.focus();
        e.preventDefault();
        return false;
      }
    });
  }

  // Prevent Enter key from submitting form while scanning
  if (rfidInput) {
    rfidInput.addEventListener('keydown', function(e) {
      if (e.key === 'Enter') e.preventDefault();
    });
  }

  // RFID scan button logic
  if (scanBtn && rfidInput) {
    scanBtn.addEventListener('click', function() {
      scanning = true;
      scanBuffer = '';
      rfidInput.value = '';
      scanBtn.textContent = 'Waiting...';
      window.addEventListener('keydown', handleScanKey, true);
    });

    function handleScanKey(e) {
      if (!scanning) return;
      if (e.key.length === 1) {
        scanBuffer += e.key;
        if (scanTimeout) clearTimeout(scanTimeout);
        scanTimeout = setTimeout(() => {
          if (scanBuffer.length >= 6) { // adjust RFID length if needed
            rfidInput.value = scanBuffer;
            scanning = false;
            scanBtn.textContent = 'Scan Card';
            window.removeEventListener('keydown', handleScanKey, true);
          }
          scanBuffer = '';
        }, 100);
      }
    }

    rfidInput.addEventListener('input', function() {
      if (scanning && rfidInput.value.length >= 6) {
        scanning = false;
        scanBtn.textContent = 'Scan Card';
        window.removeEventListener('keydown', handleScanKey, true);
      }
    });
  }

  // ===========================
  // Tab Switching
  // ===========================
  function switchTab(tab) {
    console.log('Switching to tab:', tab);
    
    // Get tab elements
    const activeTab = document.getElementById('active-members-tab');
    const pendingTab = document.getElementById('pending-members-tab');
    const activeBtn = document.getElementById('tab-active');
    const pendingBtn = document.getElementById('tab-pending');
    
    // Hide all tab contents
    if (activeTab) {
      activeTab.classList.remove('active');
      activeTab.style.display = 'none';
    }
    if (pendingTab) {
      pendingTab.classList.remove('active');
      pendingTab.style.display = 'none';
    }
    
    // Remove active styles from all tab buttons
    if (activeBtn) activeBtn.classList.remove('active');
    if (pendingBtn) pendingBtn.classList.remove('active');
    
    // Show selected tab content and add active styles
    if (tab === 'active') {
      if (activeTab) {
        activeTab.classList.add('active');
        activeTab.style.display = 'block';
        console.log('Active tab displayed');
      }
      if (activeBtn) activeBtn.classList.add('active');
    } else if (tab === 'pending') {
      if (pendingTab) {
        pendingTab.classList.add('active');
        pendingTab.style.display = 'block';
        console.log('Pending tab displayed');
      }
      if (pendingBtn) pendingBtn.classList.add('active');
    }
  }

  // ===========================
  // Add/Edit/View Member Modals
  // ===========================
  function openMemberAddModal() {
    console.log('openMemberAddModal called');
    const modal = document.getElementById('memberModal');
    if (!modal) {
      console.error('Member modal not found!');
      alert('Error: Member modal not found. Please refresh the page.');
      return;
    }
    
    console.log('Modal found, showing...');
    
    resetMemberModal();
    const modalTitle = document.getElementById('modalTitle');
    const formAction = document.getElementById('formAction');
    
    if (modalTitle) modalTitle.textContent = 'Add New Member';
    if (formAction) formAction.value = 'add_member';
    
    // Show modal - force with inline styles to override any CSS conflicts
    modal.classList.add('show');
    modal.style.display = 'flex';
    modal.style.visibility = 'visible';
    modal.style.opacity = '1';
    modal.style.position = 'fixed';
    modal.style.inset = '0';
    modal.style.zIndex = '9999';
    modal.style.alignItems = 'center';
    modal.style.justifyContent = 'center';
    modal.style.backgroundColor = 'rgba(0, 0, 0, 0.6)';
    modal.style.backdropFilter = 'blur(4px)';
    modal.style.padding = 'var(--spacing-4)';
    
    console.log('Modal show class added');
    console.log('Modal display style:', window.getComputedStyle(modal).display);
  }
  
  // Set as window property to ensure it overrides footer.php
  window.openMemberAddModal = openMemberAddModal;

  // Alias for compatibility
  function openAddModal() {
    openMemberAddModal();
  }
  
  // Ensure function is available after DOM loads (in case footer.php overrides it)
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
      window.openMemberAddModal = openMemberAddModal;
      window.closeMemberModal = closeMemberModal;
      console.log('openMemberAddModal function registered on DOMContentLoaded');
    });
  } else {
    // DOM already loaded
    window.openMemberAddModal = openMemberAddModal;
    window.closeMemberModal = closeMemberModal;
    console.log('openMemberAddModal function registered (DOM already loaded)');
  }
  
  // Also override after footer.php loads (if it runs)
  setTimeout(function() {
    window.openMemberAddModal = openMemberAddModal;
    window.closeMemberModal = closeMemberModal;
    console.log('openMemberAddModal function re-registered after timeout');
  }, 100);
  
  // Add event listener to button as backup
  document.addEventListener('DOMContentLoaded', function() {
    const addBtn = document.getElementById('addMemberBtn');
    if (addBtn) {
      addBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        console.log('Button clicked via event listener');
        openMemberAddModal();
      });
      console.log('Event listener added to Add New Member button');
    } else {
      console.error('Add New Member button not found!');
    }
  });

  function resetMemberModal() {
    document.querySelectorAll('#memberModal input, #memberModal select, #memberModal textarea').forEach(f => {
      f.value = '';
      f.removeAttribute('disabled');
    });
  }

  // Cancel and close buttons close modal
  function closeMemberModal() {
    const modal = document.getElementById('memberModal');
    if (modal) {
      modal.classList.remove('show');
      modal.style.removeProperty('display');
      modal.style.removeProperty('opacity');
      modal.style.removeProperty('visibility');
      modal.style.removeProperty('z-index');
      modal.style.removeProperty('position');
      modal.style.removeProperty('inset');
      modal.style.removeProperty('align-items');
      modal.style.removeProperty('justify-content');
      modal.style.removeProperty('background-color');
      modal.style.removeProperty('backdrop-filter');
      modal.style.removeProperty('padding');
    }
    resetMemberModal();
  }
  
  // Set as window property
  window.closeMemberModal = closeMemberModal;
  
  // Close modal when clicking outside
  const memberModal = document.getElementById('memberModal');
  if (memberModal) {
    memberModal.addEventListener('click', function(e) {
      if (e.target === memberModal) {
        closeMemberModal();
      }
    });
  }
  
  document.getElementById('closeMemberBtn').addEventListener('click', closeMemberModal);

  // Edit/View button logic - use event delegation on document since we're using cards now
  document.addEventListener('click', function(e) {
    // Check if clicked element or its parent has the class
    const editBtn = e.target.closest('.edit-member-btn');
    const viewBtn = e.target.closest('.view-member-btn');
    
    if (editBtn) {
      e.preventDefault();
      const data = JSON.parse(editBtn.getAttribute('data-member'));
      fillMemberModal(data, 'Edit Member', 'update_member', false, '');
    } else if (viewBtn) {
      e.preventDefault();
      const data = JSON.parse(viewBtn.getAttribute('data-member'));
      fillMemberModal(data, 'View Member', '', true, '');
    }
  });

  function fillMemberModal(data, title, action, readOnly, returnTo) {
    document.getElementById('modalTitle').textContent = title;
    document.getElementById('formAction').value = action;
    document.getElementById('editMemberId').value = data.member_id || '';
    document.getElementById('returnTo').value = returnTo || '';
    document.getElementById('username').value = data.username || '';
    document.getElementById('first_name').value = data.first_name || '';
    document.getElementById('last_name').value = data.last_name || '';
    document.getElementById('middle_name').value = data.middle_name || '';
    document.getElementById('gender').value = data.gender || '';
    document.getElementById('date_of_birth').value = data.date_of_birth || '';
    document.getElementById('email').value = data.email || '';
    document.getElementById('contact_number').value = data.contact_number || '';
    document.getElementById('address').value = data.address || '';
    document.getElementById('rfid_card_number').value = data.rfid_card_number || '';
    // Show registration date in view mode, hide action buttons
    const regDateDiv = document.getElementById('registrationDateDiv');
    const actionsDiv = document.getElementById('memberModalActions');
    if (readOnly) {
      regDateDiv.style.display = 'block';
      regDateDiv.innerHTML = `<label class='block text-sm font-semibold text-gray-700 mb-2'>Registration Date</label><div class='text-base font-medium text-gray-900'>${data.registration_date ? new Date(data.registration_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : 'N/A'}</div>`;
      if (actionsDiv) actionsDiv.style.display = 'none';
    } else {
      regDateDiv.style.display = 'none';
      if (actionsDiv) actionsDiv.style.display = 'flex';
    }
    document.getElementById('memberModal').classList.add('show');
    // Enable/disable fields for view mode
    document.querySelectorAll('#memberModal input, #memberModal select, #memberModal textarea').forEach(f => {
      if (readOnly) {
        f.setAttribute('disabled', 'disabled');
      } else {
        f.removeAttribute('disabled');
      }
    });
  }

  // Auto-open modal if edit parameter is present
  <?php if ($edit_member): ?>
  document.addEventListener('DOMContentLoaded', function() {
    const editData = <?php echo json_encode($edit_member, JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    const urlParams = new URLSearchParams(window.location.search);
    const returnTo = urlParams.get('return_to') || '';
    fillMemberModal(editData, 'Edit Member', 'update_member', false, returnTo);
  });
  <?php endif; ?>
</script>

  
        </div>
      </div>
    </div>

  <?php include '../footer.php'; ?>
