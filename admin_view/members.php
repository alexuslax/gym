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
                              <?php
                              session_start();
                              require_once '../config/functions.php';
                              $page_title = 'System Tools - UEP Fitness Gym';
                              include '../header.php';
                              ?>

                              <style>
                                .page-header { margin-bottom: 1.5rem; }
                                .tools-list { display:flex; flex-direction:column; gap:0.75rem; }
                                .tool-item { padding:0.75rem 1rem; border-radius:0.5rem; background:#f8fafc; border:1px solid #e2e8f0; }
                              </style>

                              <div class="page-header">
                                <h2 class="page-title">System Tools</h2>
                                <p class="page-subtitle">Technical utilities for administrators (DB migrations, scripts, and diagnostics).</p>
                              </div>

                              <div class="card">
                                <h3 class="text-lg font-semibold">Quick Actions</h3>
                                <div class="tools-list" style="margin-top:1rem;">
                                  <a class="tool-item" href="../setup_database.php">Run Database Setup</a>
                                  <a class="tool-item" href="../add_staff_role.sql">View `add_staff_role.sql`</a>
                                  <a class="tool-item" href="../add_staff_table.sql">View `add_staff_table.sql`</a>
                                  <a class="tool-item" href="../fix_foreign_key.sql">View `fix_foreign_key.sql`</a>
                                  <a class="tool-item" href="../gym_management.sql">Download DB Dump (`gym_management.sql`)</a>
                                  <a class="tool-item" href="../COMPLETE_MAINTENANCE_FEATURE.md">Maintenance Guide</a>
                                </div>
                              </div>

                              <?php include '../footer.php'; ?>
                        ");
