  <?php
  session_start();
  require_once '../config/functions.php';

  // Provide a local fallback for resolving profile image paths if not defined elsewhere
  if (!function_exists('resolveProfileImagePath')) {
    function resolveProfileImagePath($value) {
      $v = trim($value ?? '');
      if ($v === '') return null;
      if (preg_match('#^https?://#', $v)) return $v;
      $baseDir = dirname(__DIR__);
      // compute web-root prefix (e.g. /gym)
      $webRoot = '/' . trim(basename($baseDir), '/') . '/';
      if (preg_match('#^(img|uploads|assets)/#', $v)) {
        $fsPath = $baseDir . '/' . $v;
        if (pathinfo($v, PATHINFO_EXTENSION) === '') {
          foreach (['jpg','jpeg','png'] as $ext) {
            $candidate = $fsPath . '.' . $ext;
            if (file_exists($candidate)) return $webRoot . $v . '.' . $ext;
          }
        }
        if (file_exists($fsPath)) return $webRoot . $v;
        return null;
      }
      $dirFs = $baseDir . '/img/profiles/';
      $dirWeb = $webRoot . 'img/profiles/';
      $ext = pathinfo($v, PATHINFO_EXTENSION);
      if ($ext) {
        if (file_exists($dirFs . $v)) return $dirWeb . $v;
      } else {
        foreach (['jpg','jpeg','png'] as $ext) {
          $fn = $v . '.' . $ext;
          if (file_exists($dirFs . $fn)) return $dirWeb . $fn;
        }
      }
      return null;
    }
  }
  // Resolve a document/file URL to a web-root absolute path when possible
  if (!function_exists('resolveFileUrl')) {
    function resolveFileUrl($value) {
      $v = trim($value ?? '');
      if ($v === '') return null;
      if (preg_match('#^https?://#', $v)) return $v;
      $baseDir = dirname(__DIR__);
      $webRoot = '/' . trim(basename($baseDir), '/') . '/';
      // If already starts with known folder
      if (preg_match('#^(img|uploads|assets)/#', $v)) {
        return $webRoot . $v;
      }
      // If bare filename, assume img/documents/
      $fsCandidate = $baseDir . '/img/documents/' . $v;
      if (file_exists($fsCandidate)) return $webRoot . 'img/documents/' . $v;
      // Try with common extensions
      foreach (['pdf','jpg','jpeg','png'] as $ext) {
        if (file_exists($fsCandidate . '.' . $ext)) return $webRoot . 'img/documents/' . $v . '.' . $ext;
      }
      return $v; // fallback as given
    }
  }

  // Live search suggestions endpoint
  if (isset($_GET['action']) && $_GET['action'] === 'search_suggestions') {
    header('Content-Type: application/json');
    $q = isset($_GET['q']) ? trim($_GET['q']) : '';
    $results = [];
    if (strlen($q) >= 1) {
      try {
        $like = '%' . $q . '%';
        $stmt = $pdo->prepare("
          SELECT member_id, first_name, last_name, email, membership_status, rfid_card_number
          FROM members
          WHERE first_name LIKE ? OR last_name LIKE ?
             OR CONCAT(first_name, ' ', last_name) LIKE ?
             OR email LIKE ? OR member_id LIKE ? OR rfid_card_number LIKE ?
          ORDER BY last_name, first_name
          LIMIT 8
        ");
        $stmt->execute([$like, $like, $like, $like, $like, $like]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
          $results[] = [
            'id'     => $row['member_id'],
            'name'   => trim($row['first_name'] . ' ' . $row['last_name']),
            'email'  => $row['email'] ?? '',
            'status' => $row['membership_status'] ?? '',
            'value'  => trim($row['first_name'] . ' ' . $row['last_name']),
          ];
        }
      } catch (PDOException $e) {
        error_log('search_suggestions error: ' . $e->getMessage());
      }
    }
    echo json_encode(['results' => $results]);
    exit;
  }

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
                    // Determine requested member type from pending_data (fallback to stored role)
                    $account_type = isset($pending_data['member_type']) ? $pending_data['member_type'] : $user['role'];
                    $final_role = in_array($account_type, ['student','faculty','staff']) ? $account_type : $user['role'];

                    if ($account_type === 'student') {
                    // Generate student ID
                    $student_id = generateID('STU', 'members', 'member_id');

                    // Create member record (include RFID and student number when provided)
                    $stmt = $pdo->prepare(
                        "INSERT INTO members (
                          member_id, first_name, last_name, middle_name, username,
                          gender, contact_number, address, date_of_birth, email,
                          rfid_card_number, student_number, profile_picture, membership_plan, cor_document, medical_certificate, id_card, membership_status, registration_date, user_id
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active', ?, ?)"
                      );
                      $stmt->execute([
                        $student_id,
                        $pending_data['first_name'] ?? '',
                        $pending_data['last_name'] ?? '',
                        $pending_data['middle_name'] ?? '',
                        $user['username'],
                        $pending_data['gender'] ?? '',
                        $pending_data['contact_number'] ?? '',
                        $pending_data['address'] ?? '',
                        $pending_data['date_of_birth'] ?? null,
                        $user['email'],
                        $pending_data['rfid_number'] ?? null,
                        $pending_data['student_number'] ?? null,
                        $pending_data['profile_picture'] ?? null,
                        $pending_data['membership_plan'] ?? null,
                        $pending_data['cor_document'] ?? null,
                        $pending_data['medical_certificate'] ?? null,
                        $pending_data['id_card'] ?? null,
                        $pending_data['registration_date'] ?? date('Y-m-d'),
                        $user['user_id']
                      ]);
                  } elseif ($account_type === 'faculty') {
                    // Generate faculty ID
                    $faculty_id = generateID('FAC', 'trainers', 'trainer_id');

                    // Create trainer record
                    $stmt = $pdo->prepare(
                      "INSERT INTO trainers (
                        trainer_id, username, first_name, last_name, middle_name,
                        gender, contact_number, email, address, date_of_birth,
                        specialization, status, hire_date, profile_picture
                      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active', ?, ?)"
                    );
                    $stmt->execute([
                      $faculty_id,
                      $user['username'],
                      $pending_data['first_name'] ?? '',
                      $pending_data['last_name'] ?? '',
                      $pending_data['middle_name'] ?? '',
                      $pending_data['gender'] ?? '',
                      $pending_data['contact_number'] ?? '',
                      $user['email'],
                      $pending_data['address'] ?? '',
                      $pending_data['date_of_birth'] ?? null,
                      $pending_data['specialization'] ?? '',
                      $pending_data['hire_date'] ?? date('Y-m-d'),
                      $pending_data['profile_picture'] ?? null
                    ]);
                  } elseif ($account_type === 'staff') {
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
                      $pending_data['first_name'] ?? '',
                      $pending_data['last_name'] ?? '',
                      $pending_data['middle_name'] ?? '',
                      $pending_data['gender'] ?? '',
                      $pending_data['contact_number'] ?? '',
                      $user['email'],
                      $pending_data['address'] ?? '',
                      $pending_data['date_of_birth'] ?? null,
                      $pending_data['staff_number'] ?? '',
                      $pending_data['profile_picture'] ?? null,
                      $pending_data['registration_date'] ?? date('Y-m-d'),
                      $user['user_id']
                    ]);
                  }

                  // Activate user account, set the final role, and clear pending data
                  $stmt = $pdo->prepare("UPDATE users SET is_active = 1, pending_data = NULL, role = ? WHERE user_id = ?");
                  $stmt->execute([$final_role, $user['user_id']]);
                    
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
        // Create user account and member record
        try {
          $user_stmt = $pdo->prepare("INSERT INTO users (user_id, username, email, password, role, is_active) VALUES (?, ?, ?, ?, 'student', 1)");
          $default_password = password_hash('password123', PASSWORD_DEFAULT);
          $user_stmt->execute([$user_id, $username, $email, $default_password]);

          $stmt = $pdo->prepare("INSERT INTO members (member_id, user_id, username, email, first_name, middle_name, last_name, gender, contact_number, address, date_of_birth, rfid_card_number, profile_picture, membership_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active')");
          $stmt->execute([
            $member_id, $user_id, $username, $email, $first_name, $middle_name, $last_name,
            $gender, $contact_number, $address, $date_of_birth,
            $rfid_card_number, $profile_picture
          ]);

          header('Location: members.php?success=Member added successfully');
          exit();
        } catch (PDOException $e) {
          error_log('Add member error: ' . $e->getMessage());
          header('Location: members.php?error=Failed to add member');
          exit();
        }

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

    // Get member for editing
  $edit_member = null;
  if (isset($_GET['edit'])) {
      $edit_id = sanitizeInput($_GET['edit']);
      $stmt = $pdo->prepare("SELECT * FROM members WHERE member_id = ?");
      $stmt->execute([$edit_id]);
      $edit_member = $stmt->fetch();
  }
  ?>
    <?php
    // Ensure search and listing variables are defined to avoid notices and fatal errors
    $search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
    $search_type = 'unified'; // Always use unified search now
    $membership_type = '';
    $membership_status = '';
    $members = [];
      $pending_members = [];
      $search_result = null;
      $search_result_type = null; // 'member' or 'pending'
    $active_count = 0;
    $pending_count = 0;
    if (isset($pdo)) {
      try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM members WHERE membership_status = 'Active'");
        $stmt->execute();
        $active_count = (int)$stmt->fetchColumn();

        // Count users who are inactive or have pending_data (covers all signup flows)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE (is_active = 0 OR pending_data IS NOT NULL)");
        $stmt->execute();
        $pending_count = (int)$stmt->fetchColumn();

        // Populate pending_members for display (merge pending_data JSON when present)
        $pending_members = [];
        $stmt = $pdo->prepare("SELECT user_id, username, email, role, is_active, pending_data, created_at FROM users WHERE (is_active = 0 OR pending_data IS NOT NULL) ORDER BY created_at DESC");
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
          $decoded = [];
          if (!empty($r['pending_data'])) {
            $d = json_decode($r['pending_data'], true);
            if (is_array($d)) $decoded = $d;
          }
          $pending_members[] = array_merge($r, $decoded);
        }

        // Build RFID choices for combo (from approved members and pending users)
        $rfid_choices = [];
        try {
          // Approved members RFIDs
          $stmt = $pdo->prepare("SELECT DISTINCT rfid_card_number FROM members WHERE rfid_card_number IS NOT NULL AND rfid_card_number != '' LIMIT 500");
          $stmt->execute();
          $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
          foreach ($rows as $r) {
            $r = trim($r);
            if ($r !== '') $rfid_choices[$r] = $r;
          }

          // Pending users: extract rfid_number from pending_data JSON (fallback if users.rfid_number column not present)
          $stmt = $pdo->prepare("SELECT pending_data, COALESCE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(pending_data, '$.rfid_number')), 'null'), '') AS rfid_json FROM users WHERE pending_data IS NOT NULL");
          $stmt->execute();
          $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
          foreach ($rows as $r) {
            if (!empty($r['rfid_json'])) {
              $val = trim($r['rfid_json']);
              if ($val !== '') $rfid_choices[$val] = $val;
            } else {
              // fallback: decode in PHP
              if (!empty($r['pending_data'])) {
                $d = json_decode($r['pending_data'], true);
                if (is_array($d) && !empty($d['rfid_number'])) {
                  $val = trim($d['rfid_number']);
                  if ($val !== '') $rfid_choices[$val] = $val;
                }
              }
            }
          }
        } catch (PDOException $e) {
          error_log('RFID choice build error: ' . $e->getMessage());
        }
        // If a search was submitted, search across all fields (unified search)
        if (!empty($search)) {
          error_log('Members unified search requested: ' . $search);
          
          // Search in members table - search across all relevant fields
          $members_query = "SELECT * FROM members WHERE (
            rfid_card_number = ? OR
            member_id LIKE ? OR
            first_name LIKE ? OR
            last_name LIKE ? OR
            CONCAT(first_name, ' ', last_name) LIKE ? OR
            email LIKE ?
          ) LIMIT 10";
          
          $search_like = '%' . $search . '%';
          $members_params = [
            $search,           // exact RFID match
            $search_like,      // member_id
            $search_like,      // first_name
            $search_like,      // last_name
            $search_like,      // full name
            $search_like       // email
          ];
          
          $stmt = $pdo->prepare($members_query);
          $stmt->execute($members_params);
          $members_found = $stmt->fetchAll(PDO::FETCH_ASSOC);
          
          if (count($members_found) > 0) {
            error_log('Members search: ' . count($members_found) . ' approved member(s) found');
            $members = $members_found;
            $search_result = $members_found[0];
            $search_result_type = 'member';
          } else {
            // Also search in pending users across all fields
            $stmt = $pdo->prepare("SELECT user_id, username, email, role, is_active, pending_data, created_at FROM users WHERE (is_active = 0 OR pending_data IS NOT NULL) LIMIT 500");
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $found_pending_list = [];
            
            foreach ($rows as $r) {
              $decoded = [];
              if (!empty($r['pending_data'])) {
                $d = json_decode($r['pending_data'], true);
                if (is_array($d)) $decoded = $d;
              }
              
              $merged = array_merge($r, $decoded);
              
              // Check if search term matches any field
              $match = false;
              $search_lower = strtolower($search);
              
              // Check RFID
              $usr_rfid = strtolower(trim($decoded['rfid_number'] ?? ''));
              if ($usr_rfid === $search_lower) {
                $match = true;
              }
              
              // Check name
              if (!$match) {
                $first_name = strtolower($decoded['first_name'] ?? '');
                $last_name = strtolower($decoded['last_name'] ?? '');
                $full_name = $first_name . ' ' . $last_name;
                if (stripos($first_name, $search_lower) !== false || 
                    stripos($last_name, $search_lower) !== false || 
                    stripos($full_name, $search_lower) !== false) {
                  $match = true;
                }
              }
              
              // Check email
              if (!$match) {
                if (stripos($r['email'] ?? '', $search_lower) !== false) {
                  $match = true;
                }
              }
              
              // Check username/ID
              if (!$match) {
                if (stripos($r['username'] ?? '', $search_lower) !== false) {
                  $match = true;
                }
              }
              
              if ($match) {
                $found_pending_list[] = $merged;
              }
            }
            
            if (count($found_pending_list) > 0) {
              error_log('Members search: ' . count($found_pending_list) . ' pending user(s) found');
              $pending_members = array_slice($found_pending_list, 0, 10);
              $search_result = $found_pending_list[0];
              $search_result_type = 'pending';
            }
          }
        } else {
          // No search performed
          $search_result_type = null;
          $members = [];
          $pending_members = [];
        }
      } catch (PDOException $e) {
        error_log('Search query error: ' . $e->getMessage());
      }
    }
    // Load active membership plans for billing form
    $plans = [];
    if (isset($pdo)) {
      try {
        $plans = $pdo->query("SELECT plan_id, plan_name, price, duration_days FROM membership_plans WHERE is_active = 1 ORDER BY price, plan_name")->fetchAll(PDO::FETCH_ASSOC);
      } catch (PDOException $e) {
        error_log('Failed to load membership plans: ' . $e->getMessage());
      }
    }
    $page_title = 'Members Management - UEP Fitness Gym'; include '../header.php'; ?>

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

  /* Dropdown styles */
  #memberDropdown {
    max-height: 300px;
    overflow-y: auto;
    border-radius: 0.5rem;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
  }

  .dropdown-item {
    padding: 0.75rem 1rem;
    cursor: pointer;
    border-bottom: 1px solid #e5e7eb;
    transition: background-color 0.15s;
  }

  .dropdown-item:hover {
    background-color: #f3f4f6;
  }

  .dropdown-item:last-child {
    border-bottom: none;
  }

  .dropdown-item-name {
    font-weight: 600;
    color: #1f2937;
  }

  .dropdown-item-email {
    font-size: 0.875rem;
    color: #6b7280;
  }

  .dropdown-item-status {
    font-size: 0.75rem;
    color: #059669;
    font-weight: 500;
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

      <!-- Add New Member button removed per request -->

      <!-- Search Filter with Combo Box -->
      <div class="search-form-container mb-8">
        <h3 class="search-form-title flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5 text-blue-600">
            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607z"/>
          </svg>
          Search Member
        </h3>
        <form method="GET" class="flex flex-col sm:flex-row gap-4 items-end">
          <div class="flex-1">
            <label class="block text-sm font-medium text-gray-700 mb-2">Select or Search Member</label>
            <div class="search-input-container">
              <input type="text" name="search" id="memberSearchBox" placeholder="Search by name, email, member ID, or RFID card..." value="<?php echo htmlspecialchars($search); ?>" class="form-input w-full" autocomplete="off">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="search-input-icon w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607z"/>
              </svg>
              
              <!-- Custom Dropdown -->
              <div id="memberDropdown" class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto hidden">
                <div id="memberDropdownItems"></div>
              </div>
            </div>
          </div>

          <!-- Search Form Actions -->
          <div class="search-form-actions">
            <button type="submit" class="btn btn-primary px-6 py-2.5 flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607z"/>
              </svg>
              Search
            </button>

            <a href="?tab=active" class="btn btn-secondary px-6 py-2.5 flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
              </svg>
              Clear
            </a>
          </div>
        </form>
      </div>

      <!-- Summary Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-8">
        <a href="?tab=active" class="card card-stats card-stats-blue p-6 rounded-xl shadow-sm flex items-center gap-4 bg-white hover:shadow-lg transition-all duration-300">
          <div class="w-16 h-16 flex items-center justify-center rounded-full bg-blue-50 flex-shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 0 0-3-3h-2" />
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 20H4v-2a3 3 0 0 1 3-3h2" />
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z" />
            </svg>
          </div>
          <div class="flex-1 min-w-0">
            <div class="text-sm text-gray-500 font-medium">Active Members</div>
            <div class="text-3xl font-bold text-gray-900"><?php echo $active_count; ?></div>
          </div>
        </a>

        <a href="?tab=pending" class="card card-stats p-6 rounded-xl shadow-sm flex items-center gap-4 bg-white hover:shadow-lg transition-all duration-300">
          <div class="w-16 h-16 flex items-center justify-center rounded-full bg-yellow-50 flex-shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-yellow-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3" />
              <path stroke-linecap="round" stroke-linejoin="round" d="M21 12A9 9 0 1 1 3 12a9 9 0 0 1 18 0z" />
            </svg>
          </div>
          <div class="flex-1 min-w-0">
            <div class="text-sm text-gray-500 font-medium">Pending Approvals</div>
            <div class="text-3xl font-bold text-gray-900"><?php echo $pending_count; ?></div>
          </div>
        </a>
      </div>

    <!-- Member lists removed; modal-only workflow retained -->
    <div id="active-members-tab" class="tab-content active">
      <!-- Lists intentionally hidden — use search to open Account Details modal -->
    </div>

    <div id="pending-members-tab" class="tab-content" style="display: none;">
      <!-- Pending list intentionally hidden — approvals via modal -->
    </div>

    </div>

    <!-- Add/Edit Member Modal -->
    <!-- Search Result Modal -->
    <?php if (!empty($search_result)): ?>
    <div id="searchResultModal" class="modal-overlay" style="display:block;">
      <div class="modal-content" style="max-width: 48rem;">
            <div class="modal-header">
              <h3 id="modalHeaderTitle" class="card-header-title">Account Details</h3>
          <button type="button" id="closeSearchModal" class="modal-close-btn">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1.5rem; height: 1.5rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
          </button>
        </div>
        <div id="accountDetailsArea" class="p-6">
          <?php $s = $search_result; ?>
          <div id="accountMainContent">
          <div class="flex gap-4 items-center mb-6">
            <div class="w-20 h-20 rounded-xl overflow-hidden bg-gray-100 flex items-center justify-center flex-shrink-0">
              <?php $img = resolveProfileImagePath($s['profile_picture'] ?? null); if ($img): ?>
                <img src="<?php echo htmlspecialchars($img); ?>" class="w-full h-full object-cover" />
              <?php else: ?>
                <span class="font-bold text-2xl text-gray-600"><?php echo strtoupper(substr($s['first_name'] ?? ($s['username'] ?? 'U'), 0, 1)); ?></span>
              <?php endif; ?>
            </div>
            <div class="min-w-0 flex-1">
              <div class="text-xl font-bold text-gray-900" data-credit-balance="<?php echo htmlspecialchars($s['credit_balance'] ?? 0); ?>"><?php echo htmlspecialchars(trim(($s['first_name'] ?? '') . ' ' . (($s['middle_name'] ?? '') ? ($s['middle_name'] . ' ') : '') . ($s['last_name'] ?? '')) ?: ($s['username'] ?? 'Unknown')); ?></div>
              <div class="text-gray-600 mt-1"><?php echo htmlspecialchars($s['email'] ?? ''); ?></div>
            </div>
          </div>

          <?php
            // Determine displayable account status
            $account_status = 'N/A';
            if (!empty($search_result_type) && $search_result_type === 'member') {
              $account_status = !empty($s['membership_status']) ? $s['membership_status'] : ((!empty($s['is_active']) && $s['is_active']) ? 'Active' : 'Pending');
            } else {
              // pending users are considered Pending unless is_active flagged
              $account_status = (!empty($s['is_active']) && $s['is_active']) ? 'Active' : 'Pending';
            }

            $membership_plan_label = 'N/A';
            $membership_plan_raw = $s['membership_plan'] ?? null;
            if (!empty($membership_plan_raw)) {
              $plans_map = [];
              if (!empty($plans) && is_array($plans)) {
                foreach ($plans as $p) {
                  if (isset($p['plan_id'])) {
                    $plans_map[(string)$p['plan_id']] = $p['plan_name'] ?? $p['plan_id'];
                  }
                }
              }
              $membership_plan_label = $plans_map[(string)$membership_plan_raw] ?? $membership_plan_raw;
            }
          ?>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                          <div class="bg-slate-50 p-4 rounded-lg">
                            <div class="text-xs text-slate-500 uppercase tracking-wide">Member Type</div>
                            <div class="font-semibold text-gray-900 capitalize"><?php echo htmlspecialchars($s['member_type'] ?? ($s['account_type'] ?? 'N/A')); ?></div>
                          </div>
                          <div class="bg-slate-50 p-4 rounded-lg">
                            <div class="text-xs text-slate-500 uppercase tracking-wide">RFID</div>
                            <div class="font-semibold text-gray-900"><?php echo htmlspecialchars($s['rfid_card_number'] ?? ($s['rfid_number'] ?? ($s['rfid_json'] ?? 'N/A'))); ?></div>
                          </div>
                          <div class="bg-slate-50 p-4 rounded-lg">
                            <div class="text-xs text-slate-500 uppercase tracking-wide">Status</div>
                            <div class="font-semibold text-gray-900"><?php echo htmlspecialchars($account_status); ?></div>
                          </div>
                          <div class="bg-slate-50 p-4 rounded-lg">
                            <div class="text-xs text-slate-500 uppercase tracking-wide">Membership Plan</div>
                            <div class="font-semibold text-gray-900"><?php echo htmlspecialchars($membership_plan_label); ?></div>
                          </div>
                        </div>

                        <?php
                          // Show the appropriate identification number based on member_type
                          $id_label = '';
                          $id_value = '';
                          $mt = strtolower(trim($s['member_type'] ?? ($s['account_type'] ?? '')));
                          if ($mt === 'student') {
                            $id_label = 'Student Number';
                            $id_value = $s['student_number'] ?? '';
                          } elseif ($mt === 'faculty') {
                            $id_label = 'Faculty Number';
                            $id_value = $s['faculty_number'] ?? '';
                          } elseif ($mt === 'staff') {
                            $id_label = 'Staff Number';
                            $id_value = $s['staff_number'] ?? '';
                          }
                        ?>
                        <?php if (!empty($id_label) && !empty($id_value)): ?>
                          <div class="mb-6">
                            <div class="bg-slate-50 p-4 rounded-lg">
                              <div class="text-xs text-slate-500 uppercase tracking-wide"><?php echo htmlspecialchars($id_label); ?></div>
                              <div class="font-semibold text-gray-900"><?php echo htmlspecialchars($id_value); ?></div>
                            </div>
                          </div>
                        <?php endif; ?>
          
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
            <div class="bg-slate-50 p-4 rounded-lg">
              <div class="text-xs text-slate-500 uppercase tracking-wide">Contact</div>
              <div class="font-semibold text-gray-900"><?php echo htmlspecialchars($s['contact_number'] ?? 'N/A'); ?></div>
            </div>
            <div class="bg-slate-50 p-4 rounded-lg">
              <div class="text-xs text-slate-500 uppercase tracking-wide">Gender</div>
              <div class="font-semibold text-gray-900"><?php echo htmlspecialchars($s['gender'] ?? 'N/A'); ?></div>
            </div>
          </div>

          <div class="mb-6 text-gray-700">
            <div><strong>Date of Birth:</strong> <?php echo !empty($s['date_of_birth']) ? formatDate($s['date_of_birth']) : 'N/A'; ?></div>
            <div><strong>Address:</strong> <?php echo htmlspecialchars($s['address'] ?? 'N/A'); ?></div>
          </div>

          <?php if (!empty($s['credit_balance']) && $s['credit_balance'] > 0): ?>
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
              <div class="text-sm text-green-800 font-semibold">Available Credit Balance</div>
              <div class="text-2xl text-green-900 font-bold mt-1">₱<?php echo number_format($s['credit_balance'], 2); ?></div>
              <div class="text-xs text-green-700 mt-1">Can be applied to new bills or used for payments</div>
            </div>
          <?php endif; ?>

          <?php if (!empty($s['cor_document']) || !empty($s['medical_certificate']) || !empty($s['id_card'])): ?>
            <div class="mb-6">
              <div class="font-semibold mb-3 text-gray-900">Documents</div>
              <div class="flex flex-col gap-2">
                <?php if (!empty($s['cor_document'])): ?><button class="document-link text-left bg-none border-none text-blue-600 cursor-pointer underline p-0 font-inherit hover:text-blue-800" onclick="openDocumentModal('<?php echo htmlspecialchars(resolveFileUrl($s['cor_document'])); ?>', 'Certificate of Registration')" style="text-align:left;background:none;border:none;color:#0066cc;cursor:pointer;text-decoration:underline;padding:0;font:inherit;">View Certificate of Registration</button><?php endif; ?>
                <?php if (!empty($s['medical_certificate'])): ?><button class="document-link text-left bg-none border-none text-blue-600 cursor-pointer underline p-0 font-inherit hover:text-blue-800" onclick="openDocumentModal('<?php echo htmlspecialchars(resolveFileUrl($s['medical_certificate'])); ?>', 'Medical Certificate')" style="text-align:left;background:none;border:none;color:#0066cc;cursor:pointer;text-decoration:underline;padding:0;font:inherit;">View Medical Certificate</button><?php endif; ?>
                <?php if (!empty($s['id_card'])): ?><button class="document-link text-left bg-none border-none text-blue-600 cursor-pointer underline p-0 font-inherit hover:text-blue-800" onclick="openDocumentModal('<?php echo htmlspecialchars(resolveFileUrl($s['id_card'])); ?>', 'ID Card')" style="text-align:left;background:none;border:none;color:#0066cc;cursor:pointer;text-decoration:underline;padding:0;font:inherit;">View ID Card</button><?php endif; ?>
              </div>
            </div>
          <?php endif; ?>
          </div> <!-- #accountMainContent -->



          <!-- Inline Billing Form (hidden by default) -->
          <div id="billingForm" class="hidden mt-6 pt-6 border-t border-gray-200">
            <form id="inlineBillingForm" method="POST" action="billing.php">
              <input type="hidden" name="action" value="add_billing">
              <input type="hidden" name="member_id" id="bf_member_id" value="<?php echo htmlspecialchars($s['member_id'] ?? ''); ?>">
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-end mb-4">
                <div>
                  <label class="form-label">Plan</label>
                  <select name="plan_id" id="bf_plan_id" class="form-select" required>
                    <option value="">-- Select Plan --</option>
                    <?php foreach ($plans as $p): ?>
                      <option value="<?php echo $p['plan_id']; ?>" data-price="<?php echo $p['price']; ?>"><?php echo htmlspecialchars($p['plan_name'] . ' (₱' . number_format($p['price'],2) . ')'); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div>
                  <label class="form-label">Amount (calculated)</label>
                  <input type="text" id="bf_amount_display" class="form-input" disabled>
                  <input type="hidden" name="amount" id="bf_amount" value="">
                </div>
              </div>

              <div class="flex items-center gap-3 mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <input type="checkbox" id="bf_apply_credit" name="apply_credit" class="w-5 h-5 cursor-pointer">
                <label for="bf_apply_credit" class="cursor-pointer text-sm text-blue-800">Apply available credit to reduce this bill</label>
                <div id="bf_credit_display" class="ml-auto font-semibold text-blue-800"></div>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                <div>
                  <label class="form-label">Additional Charges</label>
                  <div id="bf_charges_list" class="flex flex-col gap-3 mb-3"></div>
                  <button type="button" id="bf_add_charge" class="btn btn-secondary w-full">+ Add Charge</button>
                </div>
                <div>
                  <label class="form-label">Due Date</label>
                  <input type="date" name="due_date" id="bf_due_date" class="form-input" required value="<?php echo date('Y-m-d'); ?>">
                </div>
              </div>

              <div class="flex flex-col sm:flex-row gap-3">
                <button type="submit" class="btn btn-primary flex-1">Save Billing</button>
              </div>
              <div class="mt-4">
                <div class="font-semibold mb-2 text-gray-900">Generated Bills</div>
                <div id="bf_generated_list"></div>
              </div>
            </form>
          </div>

          <!-- Inline Payment Form (hidden by default) -->
          <div id="paymentForm" class="hidden mt-6 pt-6 border-t border-gray-200">
            <form id="inlinePaymentForm" method="POST">
              <input type="hidden" name="action" value="add_payment">
              <input type="hidden" name="member_id" id="pf_member_id" value="<?php echo htmlspecialchars($s['member_id'] ?? ''); ?>">
              
              <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <div class="font-semibold text-blue-900 mb-2">Available Credit</div>
                <div id="pf_available_credit" class="text-2xl font-bold text-blue-800">₱0.00</div>
                <div class="text-sm text-blue-700 mt-1">You can apply available credit toward this payment</div>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                <div>
                  <label class="form-label">Select Bill</label>
                  <select name="selected_billing_id" id="pf_select_bill" class="form-select" required>
                    <option value="">-- Select a bill --</option>
                  </select>
                </div>
                <div>
                  <label class="form-label">Payment Type</label>
                  <select name="payment_type" id="pf_payment_type" class="form-select" required>
                    <option value="full">Full Payment</option>
                    <option value="advance">Advance Payment</option>
                    <option value="installment">Installment</option>
                  </select>
                </div>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                <div>
                  <label class="form-label">Payment Amount</label>
                  <input type="number" step="0.01" min="0.01" name="payment_amount" id="pf_payment_amount" class="form-input" required>
                </div>
                <div id="pf_installment_no_div" class="hidden">
                  <label class="form-label">Installment #</label>
                  <input type="number" name="installment_no" id="pf_installment_no" class="form-input" min="1">
                </div>
              </div>

              <div class="flex items-center gap-3 mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                <input type="checkbox" id="pf_apply_credit" name="apply_credit" class="w-5 h-5 cursor-pointer">
                <label for="pf_apply_credit" class="cursor-pointer text-sm text-yellow-800">Apply available credit to reduce amount owed</label>
              </div>

              <div id="pf_bill_details" class="mb-4 p-4 bg-gray-50 border border-gray-200 rounded-lg hidden">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                  <div><div class="text-xs text-gray-500 uppercase tracking-wide">Plan Amount</div><div id="pf_plan_amount" class="font-semibold text-gray-900">₱0.00</div></div>
                  <div><div class="text-xs text-gray-500 uppercase tracking-wide">Total Due</div><div id="pf_total_amount" class="font-semibold text-gray-900">₱0.00</div></div>
                  <div><div class="text-xs text-gray-500 uppercase tracking-wide">Paid</div><div id="pf_paid_amount" class="font-semibold text-gray-900">₱0.00</div></div>
                  <div><div class="text-xs text-gray-500 uppercase tracking-wide">Balance</div><div id="pf_balance_amount" class="font-semibold text-red-600">₱0.00</div></div>
                </div>
              </div>

              <div id="pf_payment_history" class="mb-4 p-4 bg-gray-50 border border-gray-200 rounded-lg hidden">
                <div class="font-semibold mb-3 text-gray-900">Payment History</div>
                <div id="pf_history_list" class="flex flex-col gap-2 text-sm"></div>
              </div>

              <div class="mb-4">
                <label class="form-label">Transaction ID (Optional)</label>
                <input type="text" name="transaction_id" id="pf_transaction_id" class="form-input" placeholder="Reference or check number">
              </div>

              <div class="flex flex-col sm:flex-row gap-3">
                <button type="submit" class="btn btn-primary flex-1">Record Payment</button>
                <button type="reset" class="btn btn-secondary flex-1">Clear</button>
              </div>
            </form>
          </div>

          <!-- Inline Vital Signs Form (hidden by default) -->
          <div id="vitalSignsForm" style="display:none;margin-top:0.75rem;border-top:1px solid #e5e7eb;padding-top:0.75rem;">
            <form id="inlineVitalSignsForm" method="POST">
              <input type="hidden" name="action" value="add_vital_signs">
              <input type="hidden" name="member_id" id="vs_member_id" value="">

              <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;">
                <div>
                  <label class="form-label">Date of Recording</label>
                  <input type="date" name="date_of_recording" id="vs_date_input" class="form-input" required value="<?php echo date('Y-m-d'); ?>">
                </div>
              </div>

              <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;">
                <div>
                  <label class="form-label">Weight (kg)</label>
                  <input type="number" name="weight" id="vs_weight_input" class="form-input" step="0.01" min="0" placeholder="0.00">
                </div>
                <div>
                  <label class="form-label">Height (cm)</label>
                  <input type="number" name="height" id="vs_height_input" class="form-input" step="0.01" min="0" placeholder="0.00">
                </div>
              </div>

              <div style="display:grid;grid-template-columns:1fr;gap:1rem;margin-bottom:1rem;">
                <div>
                  <label class="form-label">BMI</label>
                  <input type="number" name="bmi" id="vs_bmi_input" class="form-input" step="0.01" min="0" placeholder="Auto-calculated" readonly style="background:#f3f4f6;cursor:not-allowed;">
                </div>
              </div>

              <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;">
                <div>
                  <label class="form-label">Blood Pressure - Systolic (mmHg)</label>
                  <input type="number" name="blood_pressure_systolic" id="vs_systolic_input" class="form-input" min="0" max="300" placeholder="120">
                </div>
                <div>
                  <label class="form-label">Blood Pressure - Diastolic (mmHg)</label>
                  <input type="number" name="blood_pressure_diastolic" id="vs_diastolic_input" class="form-input" min="0" max="200" placeholder="80">
                </div>
              </div>

              <div style="display:grid;grid-template-columns:1fr;gap:1rem;margin-bottom:1rem;">
                <div>
                  <label class="form-label">Heart Rate (bpm)</label>
                  <input type="number" name="heart_rate" id="vs_heart_rate_input" class="form-input" min="0" max="300" placeholder="70">
                </div>
              </div>

              <div style="display:grid;grid-template-columns:1fr;gap:1rem;margin-bottom:1rem;">
                <!-- notes field removed -->
              </div>

              <div style="display:flex;gap:0.5rem;">
                <button type="submit" class="btn btn-primary" style="flex:1;background:linear-gradient(to right,#ef4444,#dc2626);color:#fff">Save Vital Signs</button>
                <button type="reset" class="btn" style="flex:1;background:#f3f4f6;color:#374151">Clear</button>
              </div>
            </form>

            <div id="latestVitalsContainer" style="margin-top:1rem;padding:0.75rem;background:#f8fafc;border:1px solid #e5e7eb;border-radius:8px;">
              <div style="font-weight:700;margin-bottom:0.5rem">Latest Vital Signs</div>
              <div id="latestVitalsContent" style="display:grid;grid-template-columns:repeat(3,1fr);gap:0.75rem;font-size:0.9rem;">
                <div style="grid-column:1 / -1;color:#6b7280;">No vital signs recorded.</div>
              </div>
            </div>
          </div>

          <!-- Inline Attendance Form (hidden by default) -->
          <div id="attendanceForm" class="hidden mt-6 pt-6 border-t border-gray-200">
            <div class="flex flex-col sm:flex-row gap-3 mb-6">
              <button type="button" id="attTimeInBtn" class="btn btn-primary flex-1">Time In</button>
              <button type="button" id="attTimeOutBtn" class="btn btn-primary flex-1">Time Out</button>
            </div>

            <div id="attStatus" class="mb-4 p-4 bg-gray-50 border border-gray-200 rounded-lg text-gray-900">
              Status: Not checked in today.
            </div>

            <div id="attHistory" class="p-4 bg-gray-50 border border-gray-200 rounded-lg">
              <div class="font-semibold mb-3 text-gray-900">Attendance History</div>
              <div id="attHistoryList" class="flex flex-col gap-2 text-sm">
                <div class="text-gray-500">No attendance records.</div>
              </div>
            </div>
          </div>

          <div class="flex flex-col sm:flex-row gap-3 mt-6 pt-6 border-t border-gray-200">
            <?php if ($search_result_type === 'member'): ?>
              <div class="flex flex-col sm:flex-row gap-3 w-full">
                <button type="button" id="showAccountBtn" class="btn btn-primary flex-1 text-center" data-member-id="<?php echo htmlspecialchars($s['member_id'] ?? ''); ?>">Account Details</button>
                <button type="button" id="openBillingBtn" class="btn btn-primary flex-1 text-center" data-member-id="<?php echo htmlspecialchars($s['member_id'] ?? ''); ?>">Billing</button>
                <button type="button" id="openPaymentBtn" class="btn btn-secondary flex-1 text-center" data-member-id="<?php echo htmlspecialchars($s['member_id'] ?? ''); ?>">Payment</button>
                <button type="button" id="openVitalsBtn" class="btn btn-red flex-1 text-center" data-member-id="<?php echo htmlspecialchars($s['member_id'] ?? ''); ?>">Vital Signs</button>
                <a href="attendance.php?member_id=<?php echo urlencode($s['member_id'] ?? ''); ?>" id="attendanceBtn" data-member-id="<?php echo htmlspecialchars($s['member_id'] ?? ''); ?>" class="btn btn-primary flex-1 text-center">Attendance</a>
              </div>
            <?php else: ?>
              <form method="POST" class="flex-1">
                <input type="hidden" name="action" value="approve_member">
                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($s['user_id']); ?>">
                <button type="submit" class="btn btn-primary w-full" onclick="return confirm('Approve this registration?');">Approve</button>
              </form>
              <form method="POST" class="flex-1">
                <input type="hidden" name="action" value="reject_member">
                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($s['user_id']); ?>">
                <button type="submit" class="btn btn-red w-full" onclick="return confirm('Reject this registration? This will delete the pending account.');">Reject</button>
              </form>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
    <script>
      // Wait for DOM to be ready before accessing elements
      document.addEventListener('DOMContentLoaded', function() {
        // Close handler for search modal
        (function(){
          const modal = document.getElementById('searchResultModal');
          const btn = document.getElementById('closeSearchModal');
          if (btn && modal) {
            btn.addEventListener('click', function(){ modal.style.display = 'none'; });
            modal.addEventListener('click', function(e){ if (e.target === modal) modal.style.display = 'none'; });
          }
        })();
        // Billing form show/hide and amount calculation — replace account details when opened
        (function(){
        const openBillingBtn = document.getElementById('openBillingBtn');
        const billingForm = document.getElementById('billingForm');
        const detailsArea = document.getElementById('accountMainContent');
        const showAccountBtn = document.getElementById('showAccountBtn');
        const planSelect = document.getElementById('bf_plan_id');
        const chargesList = document.getElementById('bf_charges_list');
        const addChargeBtn = document.getElementById('bf_add_charge');
        const additionalHidden = document.getElementById('bf_additional_charges');
        const amountDisplay = document.getElementById('bf_amount_display');
        const amountHidden = document.getElementById('bf_amount');
        const modalHeaderTitle = document.getElementById('modalHeaderTitle');
        const applyCreditsChk = document.getElementById('bf_apply_credit');
        const creditDisplay = document.getElementById('bf_credit_display');
        let memberCreditBalance = 0;

        function recalc() {
          let price = 0;
          if (planSelect && planSelect.selectedOptions.length) {
            price = parseFloat(planSelect.selectedOptions[0].dataset.price || 0) || 0;
          }
          // sum additional charges from charge rows
          let add = 0;
          const rows = chargesList ? Array.from(chargesList.querySelectorAll('.bf-charge-row')) : [];
          rows.forEach(r => {
            const amt = parseFloat(r.querySelector('.bf-charge-amount').value || 0) || 0;
            add += amt;
          });
          // update hidden JSON with charge details
          if (additionalHidden) additionalHidden.value = JSON.stringify(rows.map(r => ({ name: r.querySelector('.bf-charge-name').value || '', amount: parseFloat(r.querySelector('.bf-charge-amount').value || 0) || 0 })));
          let total = (price + add);
          // Apply credit if checked
          if (applyCreditsChk && applyCreditsChk.checked && memberCreditBalance > 0) {
            total = Math.max(0, total - memberCreditBalance);
          }
          total = total.toFixed(2);
          if (amountDisplay) amountDisplay.value = '₱' + total;
          if (amountHidden) amountHidden.value = total;
          // Update credit display
          if (creditDisplay && memberCreditBalance > 0 && applyCreditsChk && applyCreditsChk.checked) {
            creditDisplay.textContent = '₱' + memberCreditBalance.toFixed(2);
          } else if (creditDisplay) {
            creditDisplay.textContent = '';
          }
        }

        function createChargeRow(name = '', amount = '') {
          const row = document.createElement('div');
          row.className = 'bf-charge-row';
          row.style.display = 'flex';
          row.style.gap = '0.5rem';
          row.innerHTML = `
            <input type="text" placeholder="Charge name" class="bf-charge-name form-input" style="flex:2;" value="${escapeHtml(name)}">
            <input type="number" step="0.01" min="0" placeholder="Amount" class="bf-charge-amount form-input" style="flex:1;" value="${escapeHtml(amount)}">
            <button type="button" class="bf-charge-remove btn" style="background:#fee2e2;color:#b91c1c">Remove</button>
          `;
          // attach listeners
          row.querySelector('.bf-charge-amount').addEventListener('input', recalc);
          row.querySelector('.bf-charge-name').addEventListener('input', recalc);
          row.querySelector('.bf-charge-remove').addEventListener('click', function(){ row.remove(); recalc(); });
          return row;
        }

        function escapeHtml(s){ return (''+s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }

        // Show Account Details
        if (showAccountBtn) {
          showAccountBtn.addEventListener('click', function(){
            if (detailsArea) detailsArea.style.display = 'block';
            if (billingForm) billingForm.style.display = 'none';
            const paymentForm = document.getElementById('paymentForm');
            if (paymentForm) paymentForm.style.display = 'none';
            if (modalHeaderTitle) modalHeaderTitle.textContent = 'Account Details';
          });
        }

        // Show Billing Form
        if (openBillingBtn) {
          openBillingBtn.addEventListener('click', function(e){
            e.preventDefault();
            const memberId = this.getAttribute('data-member-id');
            if (document.getElementById('bf_member_id')) {
              document.getElementById('bf_member_id').value = memberId;
            }
            // hide account details and show billing form
            if (detailsArea) detailsArea.style.display = 'none';
            if (billingForm) billingForm.style.display = 'block';
            const paymentForm = document.getElementById('paymentForm');
            if (paymentForm) paymentForm.style.display = 'none';
            if (modalHeaderTitle) modalHeaderTitle.textContent = 'Billing';
            // clear existing charges list
            if (chargesList) {
              chargesList.innerHTML = '';
            }
            // Load member credit balance from search result
            const creditBalElems = document.querySelectorAll('[data-credit-balance]');
            creditBalElems.forEach(elem => {
              const bal = parseFloat(elem.getAttribute('data-credit-balance')) || 0;
              memberCreditBalance = bal;
            });
            // Enable/disable credit checkbox based on balance
            if (applyCreditsChk) {
              if (memberCreditBalance > 0) {
                applyCreditsChk.disabled = false;
              } else {
                applyCreditsChk.disabled = true;
                applyCreditsChk.checked = false;
              }
            }
            // add one empty row by default
            if (chargesList) chargesList.appendChild(createChargeRow());
            recalc();
            // Load recent bills for this member
            const memberIdInput = document.getElementById('bf_member_id');
            const genList = document.getElementById('bf_generated_list');
            if (genList) genList.innerHTML = '';
            if (memberIdInput && memberIdInput.value) {
              fetch('billing.php?member_id=' + encodeURIComponent(memberIdInput.value) + '&fetch_recent=1')
                .then(r => r.json())
                .then(resp => {
                  if (resp && resp.success && Array.isArray(resp.bills)) {
                    resp.bills.forEach(b => {
                      const item = document.createElement('div');
                      const amt = (typeof b.billing_amount !== 'undefined') ? Number(b.billing_amount).toFixed(2) : (typeof b.amount !== 'undefined' ? Number(b.amount).toFixed(2) : '0.00');
                      const due = b.due_date ? b.due_date : '';
                      item.innerHTML = `<a href="billing_view.php?billing_id=${b.billing_id}" target="_blank">Bill #${b.billing_id}</a> - ₱${amt}${due ? ' - Due: ' + due : ''}`;
                      if (genList) genList.appendChild(item);
                    });
                  }
                })
                .catch(err => { console.error('Failed to load recent bills', err); });
            }
          });
        }

        if (planSelect) planSelect.addEventListener('change', recalc);
        if (applyCreditsChk) applyCreditsChk.addEventListener('change', recalc);
        if (addChargeBtn) addChargeBtn.addEventListener('click', function(){ if (chargesList) { chargesList.appendChild(createChargeRow()); } });

        // Handle billing form submission via AJAX to open in new window
        const inlineBillingForm = document.getElementById('inlineBillingForm');
        if (inlineBillingForm) {
          inlineBillingForm.addEventListener('submit', function(e){
            e.preventDefault();
            const formData = new FormData(this);
            fetch('billing.php', {
              method: 'POST',
              body: formData,
              headers: {
                'X-Requested-With': 'XMLHttpRequest'
              }
            })
            .then(res => res.json())
            .then(data => {
              if (data.success && data.billing_id) {
                window.open('billing_view.php?billing_id=' + data.billing_id, '_blank', 'width=900,height=1000');
                // Show generated bill below the Save button
                const genList = document.getElementById('bf_generated_list');
                if (genList) {
                  const item = document.createElement('div');
                  const amt = (typeof data.billing_amount !== 'undefined') ? Number(data.billing_amount).toFixed(2) : '';
                  const due = data.due_date ? data.due_date : '';
                  item.innerHTML = `<a href="billing_view.php?billing_id=${data.billing_id}" target="_blank">Bill #${data.billing_id}</a> - ₱${amt}${due ? ' - Due: ' + due : ''}`;
                  genList.prepend(item);
                }
                // clear entered charges and reset plan selection
                if (chargesList) chargesList.innerHTML = '';
                if (planSelect) planSelect.selectedIndex = 0;
                recalc();
              } else {
                alert('Error creating billing record: ' + (data.error || 'Unknown error'));
              }
            })
            .catch(err => {
              alert('Error: ' + err.message);
            });
          });
        }
      })();
      }); // Close DOMContentLoaded event listener

    </script>
    <?php endif; ?>

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

  // Attach listeners to tab buttons (avoid inline onclick usage)
  document.addEventListener('DOMContentLoaded', function() {
    const activeBtn = document.getElementById('tab-active');
    const pendingBtn = document.getElementById('tab-pending');
    if (activeBtn) activeBtn.addEventListener('click', function(e) { e.preventDefault(); switchTab('active'); });
    if (pendingBtn) pendingBtn.addEventListener('click', function(e) { e.preventDefault(); switchTab('pending'); });
    // Ensure initial state
    switchTab('active');
  });

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
  
  // ===========================
  // Vital Signs Form Logic (inline in search modal)
  // ===========================
  (function(){
    const openVitalsBtn = document.getElementById('openVitalsBtn');
    const vitalsFormDiv = document.getElementById('vitalSignsForm');
    const detailsArea = document.getElementById('accountMainContent');
    const billingFormDiv = document.getElementById('billingForm');
    const paymentFormDiv = document.getElementById('paymentForm');
    const modalHeaderTitle = document.getElementById('modalHeaderTitle');
    const vsMemberId = document.getElementById('vs_member_id');
    const attendanceBtn = document.getElementById('attendanceBtn');
    const vsWeightInput = document.getElementById('vs_weight_input');
    const vsHeightInput = document.getElementById('vs_height_input');
    const vsBmiInput = document.getElementById('vs_bmi_input');
    const inlineVitalSignsForm = document.getElementById('inlineVitalSignsForm');
    const latestVitalsContent = document.getElementById('latestVitalsContent');

    if (openVitalsBtn) {
      openVitalsBtn.addEventListener('click', function(e){
        e.preventDefault();
        const memberId = this.getAttribute('data-member-id');
        if (vsMemberId) vsMemberId.value = memberId;
        // Hide other views and show vital signs form
        if (detailsArea) detailsArea.style.display = 'none';
        if (billingFormDiv) billingFormDiv.style.display = 'none';
        if (paymentFormDiv) paymentFormDiv.style.display = 'none';
        if (vitalsFormDiv) vitalsFormDiv.style.display = 'block';
        if (modalHeaderTitle) modalHeaderTitle.textContent = 'Vital Signs';
        if (memberId) loadLatestVitals(memberId);
      });
    }

    function todayString() {
      const now = new Date();
      const yyyy = now.getFullYear();
      const mm = String(now.getMonth() + 1).padStart(2, '0');
      const dd = String(now.getDate()).padStart(2, '0');
      return `${yyyy}-${mm}-${dd}`;
    }

    function setAttendanceEnabled(isEnabled) {
      if (!attendanceBtn) return;
      if (!attendanceBtn.dataset.originalStyle) {
        attendanceBtn.dataset.originalStyle = attendanceBtn.getAttribute('style') || '';
      }
      if (isEnabled) {
        attendanceBtn.style.cssText = attendanceBtn.dataset.originalStyle;
        attendanceBtn.style.pointerEvents = 'auto';
        attendanceBtn.style.opacity = '1';
        attendanceBtn.setAttribute('aria-disabled', 'false');
      } else {
        attendanceBtn.style.pointerEvents = 'none';
        attendanceBtn.style.background = '#9ca3af';
        attendanceBtn.style.color = '#f3f4f6';
        attendanceBtn.style.opacity = '0.7';
        attendanceBtn.setAttribute('aria-disabled', 'true');
      }
    }

    function renderLatestVitals(data) {
      if (!latestVitalsContent) return;
      if (!data || !data.success) {
        latestVitalsContent.innerHTML = '<div style="grid-column:1 / -1;color:#6b7280;">No vital signs recorded.</div>';
        setAttendanceEnabled(false);
        return;
      }

      const display = [
        { label: 'Date', value: data.date_of_recording || '-' },
        { label: 'Weight (kg)', value: data.weight || '-' },
        { label: 'Height (cm)', value: data.height || '-' },
        { label: 'BMI', value: data.bmi || '-' },
        { label: 'BP Systolic', value: data.blood_pressure_systolic || '-' },
        { label: 'BP Diastolic', value: data.blood_pressure_diastolic || '-' },
        { label: 'Heart Rate', value: data.heart_rate || '-' }
      ];

      latestVitalsContent.innerHTML = display.map(item => (
        `<div><div style="font-size:0.75rem;color:#64748b">${item.label}</div><div style="font-weight:600;color:#0f172a">${item.value}</div></div>`
      )).join('');

      const hasTodayVitals = data.date_of_recording === todayString();
      setAttendanceEnabled(hasTodayVitals);
    }

    function loadLatestVitals(memberId) {
      if (!memberId) return;
      fetch(`billing.php?member_id=${encodeURIComponent(memberId)}&fetch_vitals=1`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
      .then(res => res.json())
      .then(data => renderLatestVitals(data))
      .catch(() => renderLatestVitals(null));
    }

    const initialMemberId = (openVitalsBtn && openVitalsBtn.getAttribute('data-member-id')) || (vsMemberId && vsMemberId.value);
    if (initialMemberId) loadLatestVitals(initialMemberId);

    // Auto-calculate BMI when weight or height changes
    function calculateBMI() {
      const weight = parseFloat(vsWeightInput.value) || 0;
      const height = parseFloat(vsHeightInput.value) || 0;
      if (weight > 0 && height > 0) {
        const heightInMeters = height / 100;
        const bmi = (weight / (heightInMeters * heightInMeters)).toFixed(2);
        vsBmiInput.value = bmi;
      } else {
        vsBmiInput.value = '';
      }
    }

    if (vsWeightInput) vsWeightInput.addEventListener('input', calculateBMI);
    if (vsHeightInput) vsHeightInput.addEventListener('input', calculateBMI);

    // Handle form submission
    if (inlineVitalSignsForm) {
      inlineVitalSignsForm.addEventListener('submit', function(e){
        e.preventDefault();
        const formData = new FormData(this);
        fetch('billing.php', {
          method: 'POST',
          body: formData,
          headers: {'X-Requested-With': 'XMLHttpRequest'}
        })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            alert('Vital signs recorded successfully!');
            if (vitalsFormDiv) vitalsFormDiv.style.display = 'none';
            if (detailsArea) detailsArea.style.display = 'block';
            if (modalHeaderTitle) modalHeaderTitle.textContent = 'Account Details';
            inlineVitalSignsForm.reset();
            vsBmiInput.value = '';
            if (vsMemberId && vsMemberId.value) {
              loadLatestVitals(vsMemberId.value);
            }
          } else {
            alert('Error: ' + (data.error || 'Failed to record vital signs'));
          }
        })
        .catch(err => {
          alert('Error: ' + err.message);
        });
      });
    }
  })();

  // ===========================
  // Attendance Modal Logic (inline in search modal)
  // ===========================
  (function(){
    const attendanceBtn = document.getElementById('attendanceBtn');
    const attendanceFormDiv = document.getElementById('attendanceForm');
    const detailsArea = document.getElementById('accountMainContent');
    const billingFormDiv = document.getElementById('billingForm');
    const paymentFormDiv = document.getElementById('paymentForm');
    const vitalsFormDiv = document.getElementById('vitalSignsForm');
    const modalHeaderTitle = document.getElementById('modalHeaderTitle');
    const attTimeInBtn = document.getElementById('attTimeInBtn');
    const attTimeOutBtn = document.getElementById('attTimeOutBtn');
    const attStatus = document.getElementById('attStatus');
    const attHistoryList = document.getElementById('attHistoryList');

    let currentMemberId = null;

    function setButtons(checkedIn) {
      if (attTimeInBtn) attTimeInBtn.disabled = !!checkedIn;
      if (attTimeOutBtn) attTimeOutBtn.disabled = !checkedIn;
    }

    function renderAttendance(data) {
      if (!attHistoryList) return;
      if (!data || !data.success) {
        attHistoryList.innerHTML = '<div style="color:#6b7280;">No attendance records.</div>';
        if (attStatus) attStatus.textContent = 'Status: Not checked in today.';
        setButtons(false);
        return;
      }

      const records = Array.isArray(data.records) ? data.records : [];
      if (records.length === 0) {
        attHistoryList.innerHTML = '<div style="color:#6b7280;">No attendance records.</div>';
      } else {
        // Show only the latest attendance record
        const latestRecord = records[0];
        const date = latestRecord.date || '';
        const timeIn = latestRecord.time_in || '-';
        const timeOut = latestRecord.time_out || '-';
        attHistoryList.innerHTML = `<div style="padding:0.5rem 0;border-bottom:1px solid #e5e7eb;">${date} — In: ${timeIn} — Out: ${timeOut}</div>`;
      }

      const active = data.active || {};
      if (active.checked_in) {
        const timeIn = active.time_in || '-';
        if (attStatus) attStatus.textContent = `Status: Checked in today at ${timeIn}.`;
        setButtons(true);
      } else {
        if (attStatus) attStatus.textContent = 'Status: Not checked in today.';
        setButtons(false);
      }
    }

    function loadAttendance(memberId) {
      if (!memberId) return;
      fetch(`attendance.php?member_id=${encodeURIComponent(memberId)}&fetch_attendance=1`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
      .then(res => res.json())
      .then(data => renderAttendance(data))
      .catch(() => renderAttendance(null));
    }

    function postAttendance(action) {
      if (!currentMemberId) return;
      const formData = new FormData();
      formData.append('action', action);
      formData.append('member_id', currentMemberId);
      fetch('attendance.php', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
      .then(res => res.json())
      .then(data => {
        if (data && data.success) {
          loadAttendance(currentMemberId);
        } else {
          alert('Error: ' + (data.error || 'Failed to update attendance'));
        }
      })
      .catch(err => alert('Error: ' + err.message));
    }

    if (attendanceBtn) {
      attendanceBtn.addEventListener('click', function(e){
        e.preventDefault();
        if (attendanceBtn.getAttribute('aria-disabled') === 'true') return;
        currentMemberId = attendanceBtn.getAttribute('data-member-id') || null;
        if (!currentMemberId && document.getElementById('vs_member_id')) {
          currentMemberId = document.getElementById('vs_member_id').value || null;
        }
        if (!currentMemberId) return;
        if (detailsArea) detailsArea.style.display = 'none';
        if (billingFormDiv) billingFormDiv.style.display = 'none';
        if (paymentFormDiv) paymentFormDiv.style.display = 'none';
        if (vitalsFormDiv) vitalsFormDiv.style.display = 'none';
        if (attendanceFormDiv) attendanceFormDiv.style.display = 'block';
        if (modalHeaderTitle) modalHeaderTitle.textContent = 'Attendance';
        loadAttendance(currentMemberId);
      });
    }

    if (attTimeInBtn) {
      attTimeInBtn.addEventListener('click', function(){
        postAttendance('member_time_in');
      });
    }

    if (attTimeOutBtn) {
      attTimeOutBtn.addEventListener('click', function(){
        postAttendance('member_time_out');
      });
    }
  })();

  // ===========================
  // Payment Form Logic (inline in search modal)
  // ===========================
  (function(){
    const openPaymentBtn = document.getElementById('openPaymentBtn');
    const paymentFormDiv = document.getElementById('paymentForm');
    const detailsArea = document.getElementById('accountMainContent');
    const billingFormDiv = document.getElementById('billingForm');
    const pfSelectBill = document.getElementById('pf_select_bill');
    const pfPaymentType = document.getElementById('pf_payment_type');
    const pfInstallmentNoDiv = document.getElementById('pf_installment_no_div');
    const pfApplyCreditChk = document.getElementById('pf_apply_credit');
    const pfAvailableCredit = document.getElementById('pf_available_credit');
    const inlinePaymentForm = document.getElementById('inlinePaymentForm');
    const modalHeaderTitle = document.getElementById('modalHeaderTitle');

    if (openPaymentBtn) {
      openPaymentBtn.addEventListener('click', function(e){
        e.preventDefault();
        const memberId = this.getAttribute('data-member-id');
        if (document.getElementById('pf_member_id')) {
          document.getElementById('pf_member_id').value = memberId;
        }
        // Hide other views and show payment form
        if (detailsArea) detailsArea.style.display = 'none';
        if (billingFormDiv) billingFormDiv.style.display = 'none';
        if (paymentFormDiv) paymentFormDiv.style.display = 'block';
        if (modalHeaderTitle) modalHeaderTitle.textContent = 'Payment';
        // Load member credit balance from database
        fetch('billing.php?member_id=' + encodeURIComponent(memberId) + '&fetch_credit=1')
          .then(r => r.json())
          .then(resp => {
            if (resp && resp.success) {
              const bal = parseFloat(resp.credit_balance) || 0;
              if (pfAvailableCredit) pfAvailableCredit.textContent = '₱' + bal.toFixed(2);
              // Enable/disable credit checkbox based on balance
              if (pfApplyCreditChk) {
                if (bal > 0) {
                  pfApplyCreditChk.disabled = false;
                } else {
                  pfApplyCreditChk.disabled = true;
                  pfApplyCreditChk.checked = false;
                }
              }
            }
          })
          .catch(err => console.error('Failed to load credit balance', err));
        // Load bills for this member
        loadMemberBillsForPayment(memberId);
      });
    }

    // Show/hide installment number field
    if (pfPaymentType && pfInstallmentNoDiv) {
      pfPaymentType.addEventListener('change', function(){
        if (this.value === 'installment') {
          pfInstallmentNoDiv.style.display = 'block';
        } else {
          pfInstallmentNoDiv.style.display = 'none';
        }
      });
    }

    // Load bills when selected
    if (pfSelectBill) {
      pfSelectBill.addEventListener('change', function(){
        const billingId = this.value;
        if (billingId) {
          loadBillDetailsForPayment(billingId);
          loadPaymentHistoryForPayment(billingId);
        } else {
          const detailsDiv = document.getElementById('pf_bill_details');
          const historyDiv = document.getElementById('pf_payment_history');
          if (detailsDiv) detailsDiv.style.display = 'none';
          if (historyDiv) historyDiv.style.display = 'none';
        }
      });
    }

    function loadMemberBillsForPayment(memberId) {
      fetch('billing.php?member_id=' + encodeURIComponent(memberId) + '&fetch_recent=1')
        .then(r => r.json())
        .then(resp => {
          if (resp && resp.success && Array.isArray(resp.bills)) {
            const select = document.getElementById('pf_select_bill');
            if (select) {
              select.innerHTML = '<option value="">-- Select a bill --</option>';
              resp.bills.forEach(b => {
                const opt = document.createElement('option');
                opt.value = b.billing_id;
                const amt = (typeof b.billing_amount !== 'undefined') ? Number(b.billing_amount).toFixed(2) : '0.00';
                opt.textContent = `Bill #${b.billing_id} - ₱${amt} - ${b.payment_status || 'Pending'}`;
                select.appendChild(opt);
              });
            }
          }
        })
        .catch(err => console.error('Failed to load bills', err));
    }

    function loadBillDetailsForPayment(billingId) {
      fetch('billing.php?billing_id=' + encodeURIComponent(billingId) + '&fetch_details=1')
        .then(r => r.json())
        .then(resp => {
          if (resp && resp.success) {
            const planAmt = document.getElementById('pf_plan_amount');
            const totalAmt = document.getElementById('pf_total_amount');
            const paidAmt = document.getElementById('pf_paid_amount');
            const balanceAmt = document.getElementById('pf_balance_amount');
            const detailsDiv = document.getElementById('pf_bill_details');
            if (planAmt) planAmt.textContent = '₱' + Number(resp.plan_amount || 0).toFixed(2);
            if (totalAmt) totalAmt.textContent = '₱' + Number(resp.billing_amount || 0).toFixed(2);
            if (paidAmt) paidAmt.textContent = '₱' + Number(resp.paid_amount || 0).toFixed(2);
            if (balanceAmt) balanceAmt.textContent = '₱' + Number(resp.balance || 0).toFixed(2);
            if (detailsDiv) detailsDiv.style.display = 'block';
          }
        })
        .catch(err => console.error('Failed to load bill details', err));
    }

    function loadPaymentHistoryForPayment(billingId) {
      fetch('billing.php?billing_id=' + encodeURIComponent(billingId) + '&fetch_payments=1')
        .then(r => r.json())
        .then(resp => {
          if (resp && resp.success && Array.isArray(resp.payments)) {
            const histDiv = document.getElementById('pf_payment_history');
            const listDiv = document.getElementById('pf_history_list');
            if (resp.payments.length > 0 && listDiv) {
              listDiv.innerHTML = resp.payments.map(p => {
                const amt = Number(p.payment_amount || 0).toFixed(2);
                const date = p.payment_date || '';
                const type = p.payment_type || 'full';
                return `<div style="padding:0.5rem 0;border-bottom:1px solid #e5e7eb;">₱${amt} - ${date} (${type})</div>`;
              }).join('');
              if (histDiv) histDiv.style.display = 'block';
            } else {
              if (histDiv) histDiv.style.display = 'none';
            }
          }
        })
        .catch(err => console.error('Failed to load payment history', err));
    }

    // Handle payment form submission
    if (inlinePaymentForm) {
      inlinePaymentForm.addEventListener('submit', function(e){
        e.preventDefault();
        const formData = new FormData(this);
        fetch('billing.php', {
          method: 'POST',
          body: formData,
          headers: {'X-Requested-With': 'XMLHttpRequest'}
        })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            alert('Payment recorded successfully!');
            if (paymentFormDiv) paymentFormDiv.style.display = 'none';
            if (detailsArea) detailsArea.style.display = 'block';
            if (modalHeaderTitle) modalHeaderTitle.textContent = 'Account Details';
            inlinePaymentForm.reset();
          } else {
            alert('Error: ' + (data.error || 'Failed to record payment'));
          }
        })
        .catch(err => {
          alert('Error: ' + err.message);
        });
      });
    }
  })();

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
  
  // Backup add-button listener removed (Add button intentionally hidden)

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

<script>
  // Document Modal Functions
  function openDocumentModal(fileUrl, docTitle) {
    const modal = document.getElementById('documentModal');
    const imgElement = document.getElementById('documentImage');
    const pdfElement = document.getElementById('documentPdf');
    const loadingElement = document.getElementById('documentLoading');
    const downloadLink = document.getElementById('documentDownloadLink');
    const titleElement = document.getElementById('documentModalTitle');

    // Set title and download link
    titleElement.textContent = docTitle;
    downloadLink.href = fileUrl;
    downloadLink.download = docTitle + (fileUrl.includes('.pdf') ? '.pdf' : '');

    // Hide both viewers initially
    imgElement.style.display = 'none';
    pdfElement.style.display = 'none';
    loadingElement.style.display = 'block';

    // Determine file type and show appropriate viewer
    if (fileUrl.toLowerCase().endsWith('.pdf')) {
      pdfElement.src = fileUrl;
      pdfElement.onload = function() {
        loadingElement.style.display = 'none';
        pdfElement.style.display = 'block';
      };
      pdfElement.onerror = function() {
        loadingElement.innerHTML = '<p style="color:#dc2626;">Failed to load PDF. <a href="' + fileUrl + '" target="_blank" style="color:#0066cc;">Open in new tab</a></p>';
      };
    } else {
      // Image file
      imgElement.src = fileUrl;
      imgElement.onload = function() {
        loadingElement.style.display = 'none';
        imgElement.style.display = 'block';
      };
      imgElement.onerror = function() {
        loadingElement.innerHTML = '<p style="color:#dc2626;">Failed to load image. <a href="' + fileUrl + '" target="_blank" style="color:#0066cc;">Open in new tab</a></p>';
      };
    }

    // Show modal
    modal.style.display = 'flex';
  }

  function closeDocumentModal() {
    const modal = document.getElementById('documentModal');
    const pdfElement = document.getElementById('documentPdf');
    modal.style.display = 'none';
    pdfElement.src = ''; // Clear PDF to stop loading
  }

  // Close modal when clicking outside of it
  document.addEventListener('DOMContentLoaded', function() {
    const documentModal = document.getElementById('documentModal');
    if (documentModal) {
      documentModal.addEventListener('click', function(e) {
        if (e.target === this) {
          closeDocumentModal();
        }
      });
    }
  });

  // Close modal with Escape key
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      closeDocumentModal();
    }
  });

  // Live Search Dropdown
  document.addEventListener('DOMContentLoaded', function() {
    const searchInput    = document.getElementById('memberSearchBox');
    const searchDropdown = document.getElementById('memberDropdown');
    const dropdownItems  = document.getElementById('memberDropdownItems');
    const searchForm     = searchInput ? searchInput.closest('form') : null;

    if (!searchInput || !searchDropdown || !dropdownItems) return;

    // Move dropdown to <body> so ancestor overflow:hidden cannot clip it.
    // Use position:fixed so it sits relative to the viewport directly under the input.
    document.body.appendChild(searchDropdown);
    searchDropdown.style.position    = 'fixed';
    searchDropdown.style.zIndex      = '99999';
    searchDropdown.style.right        = '';
    searchDropdown.style.minWidth      = '';
    searchDropdown.style.width         = '';
    searchDropdown.style.top           = '-9999px';
    searchDropdown.style.left          = '-9999px';
    searchDropdown.style.borderRadius  = '0.75rem';
    searchDropdown.style.border        = '2px solid var(--blue-300)';
    searchDropdown.style.background    = 'white';
    searchDropdown.style.boxShadow     = '0 20px 25px -5px rgba(0,0,0,0.12), 0 8px 10px -6px rgba(0,0,0,0.08)';
    searchDropdown.style.maxHeight     = '18rem';
    searchDropdown.style.overflow      = 'hidden';
    searchDropdown.style.display       = 'none';
    dropdownItems.style.overflowY  = 'auto';
    dropdownItems.style.flex       = '1';
    dropdownItems.style.minHeight  = '0';

    let debounceTimer = null;
    let currentQuery  = '';

    function positionDropdown() {
      const rect = searchInput.getBoundingClientRect();
      searchDropdown.style.top   = (rect.bottom + 4) + 'px';
      searchDropdown.style.left  = rect.left + 'px';
      searchDropdown.style.width = rect.width + 'px';
    }

    function hideDropdown() {
      searchDropdown.style.display = 'none';
      // Clear any selected items
      const selectedItems = dropdownItems.querySelectorAll('.dropdown-item.selected');
      selectedItems.forEach(item => item.classList.remove('selected'));
    }

    function escapeRegex(str) {
      return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    function renderDropdown(results, query) {
      dropdownItems.innerHTML = '';
      if (results.length === 0) {
        dropdownItems.innerHTML = '<div class="p-4 text-gray-500 text-sm text-center flex items-center justify-center gap-2"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M9.172 16.172a4 4 0 0 1 5.656 0M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2z"/></svg>No members found</div>';
        positionDropdown();
        searchDropdown.style.display = 'block';
        return;
      }
      const fragment = document.createDocumentFragment();
      const re = new RegExp('(' + escapeRegex(query) + ')', 'gi');
      results.forEach(function(r, i) {
        const item = document.createElement('div');
        item.className = 'dropdown-item';

        const initials = r.name.split(' ').filter(Boolean).map(function(n){ return n[0]; }).slice(0, 2).join('').toUpperCase();
        const isActive  = r.status === 'Active';
        const statusBg  = isActive ? 'var(--green-100)' : 'var(--yellow-100)';
        const statusClr = isActive ? 'var(--green-700)' : 'var(--yellow-700)';
        const highlightedName = r.name.replace(re, '<mark>$1</mark>');
        const emailLine = r.email ? ' · ' + r.email : '';

        item.innerHTML =
          '<div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 text-white flex items-center justify-center text-sm font-bold flex-shrink-0">' + initials + '</div>' +
          '<div class="flex-1 min-w-0 overflow-hidden">' +
            '<div class="dropdown-item-name">' + highlightedName + '</div>' +
            '<div class="text-xs text-gray-500 whitespace-nowrap overflow-hidden text-ellipsis">' + r.id + (emailLine ? ' &middot; ' + r.email : '') + '</div>' +
          '</div>' +
          '<span class="px-2 py-1 rounded-full text-xs font-medium flex-shrink-0" style="background:' + statusBg + ';color:' + statusClr + ';">' + (r.status || 'N/A') + '</span>';

        item.addEventListener('mousedown', function(e) {
          e.preventDefault();
          searchInput.value = r.value;
          hideDropdown();
          if (searchForm) searchForm.submit();
        });
        fragment.appendChild(item);
      });
      dropdownItems.appendChild(fragment);
      positionDropdown();
      searchDropdown.style.display = 'block';
    }

    async function fetchSuggestions(query) {
      if (!query) { hideDropdown(); return; }
      currentQuery = query;
      dropdownItems.innerHTML = '<div class="p-4 text-gray-500 text-sm text-center flex items-center justify-center gap-2"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4 animate-spin"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 0 0 4.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 0 1-15.357-2m15.357 2H15"/></svg>Searching...</div>';
      positionDropdown();
      searchDropdown.style.display = 'block';
      try {
        const url = '<?php echo htmlspecialchars($_SERVER["PHP_SELF"], ENT_QUOTES); ?>?action=search_suggestions&q=' + encodeURIComponent(query);
        const res  = await fetch(url, { credentials: 'same-origin' });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const text = await res.text();
        // Guard: only parse if response looks like JSON (handles PHP warnings mixed in)
        const jsonStart = text.indexOf('{');
        const data = jsonStart >= 0 ? JSON.parse(text.slice(jsonStart)) : { results: [] };
        if (query === currentQuery) renderDropdown(data.results || [], query);
      } catch (err) {
        if (query === currentQuery) {
          dropdownItems.innerHTML = '<div class="p-4 text-red-500 text-sm text-center flex items-center justify-center gap-2"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>Could not load suggestions</div>';
        }
      }
    }

    searchInput.addEventListener('input', function() {
      const query = this.value.trim();
      clearTimeout(debounceTimer);
      if (!query) { hideDropdown(); return; }
      debounceTimer = setTimeout(function() { fetchSuggestions(query); }, 280);
    });

    searchInput.addEventListener('focus', function() {
      const query = this.value.trim();
      if (query) {
        fetchSuggestions(query);
      } else {
        // Show helpful message when focused but empty
        dropdownItems.innerHTML = '<div class="p-4 text-gray-500 text-sm text-center"><div class="font-medium mb-1">Start typing to search members</div><div class="text-xs">Search by name, email, member ID, or RFID card number</div></div>';
        positionDropdown();
        searchDropdown.style.display = 'block';
      }
    });

    // Delay so mousedown on a dropdown item fires before blur closes the list
    searchInput.addEventListener('blur', function() {
      setTimeout(hideDropdown, 200);
    });

    searchInput.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') { hideDropdown(); }
      if (e.key === 'Enter')  { hideDropdown(); }
      
      // Handle arrow key navigation
      if (searchDropdown.style.display === 'block') {
        const items = dropdownItems.querySelectorAll('.dropdown-item');
        if (items.length === 0) return;
        
        let currentIndex = -1;
        items.forEach((item, index) => {
          if (item.classList.contains('selected')) {
            currentIndex = index;
            item.classList.remove('selected');
          }
        });
        
        if (e.key === 'ArrowDown') {
          e.preventDefault();
          currentIndex = Math.min(currentIndex + 1, items.length - 1);
          items[currentIndex].classList.add('selected');
          items[currentIndex].scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'ArrowUp') {
          e.preventDefault();
          currentIndex = Math.max(currentIndex - 1, 0);
          items[currentIndex].classList.add('selected');
          items[currentIndex].scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'Enter' && currentIndex >= 0) {
          e.preventDefault();
          items[currentIndex].click();
        }
      }
    });

    // Reposition on scroll/resize so dropdown stays aligned
    window.addEventListener('scroll', function() {
      if (searchDropdown.style.display === 'flex') positionDropdown();
    }, true);
    window.addEventListener('resize', function() {
      if (searchDropdown.style.display === 'flex') positionDropdown();
    });

    document.addEventListener('click', function(e) {
      if (e.target !== searchInput && !searchDropdown.contains(e.target)) hideDropdown();
    });
  });
</script>

  <?php include '../footer.php'; ?>

  <!-- Document Viewer Modal -->
  <div id="documentModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;">
    <div style="background:white;border-radius:0.5rem;max-width:90%;max-height:90%;width:600px;display:flex;flex-direction:column;box-shadow:0 20px 25px -5px rgba(0,0,0,0.1);">
      <div style="display:flex;justify-content:space-between;align-items:center;padding:1.5rem;border-bottom:1px solid #e5e7eb;">
        <h3 id="documentModalTitle" style="font-size:1.25rem;font-weight:700;color:#1f2937;margin:0;"></h3>
        <button onclick="closeDocumentModal()" style="background:none;border:none;font-size:1.5rem;cursor:pointer;color:#6b7280;">&times;</button>
      </div>
      <div style="flex:1;overflow:auto;padding:1.5rem;display:flex;align-items:center;justify-content:center;">
        <img id="documentImage" style="display:none;max-width:100%;max-height:100%;object-fit:contain;margin-top:1rem;" />
        <iframe id="documentPdf" style="display:none;width:100%;height:100%;border:none;margin-top:2rem;"></iframe>
        <div id="documentLoading" style="text-align:center;color:#6b7280;">
          <p>Loading document...</p>
        </div>
      </div>
      <div style="display:flex;justify-content:flex-end;gap:0.75rem;padding:1.5rem;border-top:1px solid #e5e7eb;">
        <a id="documentDownloadLink" href="#" download style="padding:0.5rem 1rem;background:#0066cc;color:white;border-radius:0.375rem;text-decoration:none;cursor:pointer;">Download</a>
        <button onclick="closeDocumentModal()" style="padding:0.5rem 1rem;background:#e5e7eb;color:#1f2937;border:none;border-radius:0.375rem;cursor:pointer;">Close</button>
      </div>
    </div>
  </div>