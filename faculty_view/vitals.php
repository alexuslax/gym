<?php
require_once '../config/database.php';
require_once '../config/functions.php';

// Authentication Check
if (session_status() === PHP_SESSION_NONE) session_start();
$role = $_SESSION['role'] ?? '';
$allowed_roles = ['trainer', 'staff'];
if (!isset($_SESSION['user_id']) || !in_array($role, $allowed_roles, true)) {
	header('Location: ../index.php');
	exit();
}

$is_staff = in_array($role, ['staff', 'admin'], true);
$trainer_id = null;
$trainer_user_id = $_SESSION['user_id'] ?? null;

if (!$is_staff) {
    // Get trainer_id from username for trainers
    $username = $_SESSION['username'] ?? null;
    if (!$username) {
        header('Location: ../index.php');
        exit();
    }

    $stmt = $pdo->prepare("SELECT trainer_id FROM trainers WHERE username = ?");
    $stmt->execute([$username]);
    $trainer = $stmt->fetch();
    $trainer_id = $trainer['trainer_id'] ?? null;

    if (!$trainer_id) {
        header('Location: ../index.php');
        exit();
    }
}

$page_title = 'Vital Signs';
$message = '';
$error = '';

// Handle form submissions (only trainers can add/edit vitals)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Only trainers can add/edit vitals
	if ($is_staff) {
        $error = "Only trainers can record vital signs.";
    } elseif (isset($_POST['action'])) {
        try {
            if ($_POST['action'] === 'add_vitals') {
                $member_id = sanitizeInput($_POST['member_id']);
                $date_of_recording = sanitizeInput($_POST['date_of_recording']);
                $weight = !empty($_POST['weight']) ? sanitizeInput($_POST['weight']) : null;
                $height = !empty($_POST['height']) ? sanitizeInput($_POST['height']) : null;
                $waist_circumference = !empty($_POST['waist_circumference']) ? sanitizeInput($_POST['waist_circumference']) : null;
                $body_fat_percentage = !empty($_POST['body_fat_percentage']) ? sanitizeInput($_POST['body_fat_percentage']) : null;
                
                // Calculate BMI if weight and height are provided
                $bmi = null;
                if ($weight && $height) {
                    $height_m = $height / 100; // Convert cm to meters
                    $bmi = round($weight / ($height_m * $height_m), 2);
                }
                
                // Parse blood pressure from format "120/80"
                $blood_pressure_systolic = null;
                $blood_pressure_diastolic = null;
                if (!empty($_POST['blood_pressure'])) {
                    $bp_input = sanitizeInput($_POST['blood_pressure']);
                    $bp_parts = explode('/', $bp_input);
                    if (count($bp_parts) == 2) {
                        $blood_pressure_systolic = trim($bp_parts[0]);
                        $blood_pressure_diastolic = trim($bp_parts[1]);
                    }
                }
                
                $heart_rate = !empty($_POST['heart_rate']) ? sanitizeInput($_POST['heart_rate']) : null;
                $notes = !empty($_POST['notes']) ? sanitizeInput($_POST['notes']) : null;
                
                // Verify member is assigned to this trainer (only for trainers, not admins)
				if (!$is_staff) {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM trainer_assignments WHERE trainer_id = ? AND member_id = ?");
                    $stmt->execute([$trainer_id, $member_id]);
                    $is_assigned = $stmt->fetchColumn();
                    
                    if (!$is_assigned) {
                        throw new Exception('You can only record vitals for your assigned members.');
                    }
                }
                
                // Generate unique record_id using uniqid
                $record_id = 'VS' . strtoupper(uniqid());
                
                $stmt = $pdo->prepare("INSERT INTO vital_signs (record_id, member_id, date_of_recording, height_cm, heart_rate, blood_pressure_systolic, blood_pressure_diastolic, weight, bmi, body_fat_percentage, waist_circumference, notes, trainer_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$record_id, $member_id, $date_of_recording, $height, $heart_rate, $blood_pressure_systolic, $blood_pressure_diastolic, $weight, $bmi, $body_fat_percentage, $waist_circumference, $notes, $trainer_user_id]);
                
                $message = "Vital signs recorded successfully.";
            }
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Get members for dropdown
if ($is_staff) {
	// Staff can see all active members
    $stmt = $pdo->query("SELECT member_id, first_name, middle_name, last_name 
                         FROM members 
                         WHERE membership_status = 'Active'
                         ORDER BY first_name, last_name");
    $assigned_members = $stmt->fetchAll();
} else {
    // Trainers can only see assigned members
    $stmt = $pdo->prepare("SELECT DISTINCT m.member_id, m.first_name, m.middle_name, m.last_name 
                           FROM members m
                           INNER JOIN trainer_assignments ta ON m.member_id COLLATE utf8mb4_unicode_ci = ta.member_id COLLATE utf8mb4_unicode_ci
                           WHERE ta.trainer_id = ? AND m.membership_status = 'Active'
                           ORDER BY m.first_name, m.last_name");
    $stmt->execute([$trainer_id]);
    $assigned_members = $stmt->fetchAll();
}

// Get member filter
$member_filter = $_GET['member_id'] ?? '';

// Fetch vital signs records
if ($is_staff) {
	// Staff can see all vital signs
    $where_conditions = [];
    $params = [];
    
    if (!empty($member_filter)) {
        $where_conditions[] = "v.member_id = ?";
        $params[] = $member_filter;
    }
    
    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
    
    $sql = "SELECT v.*, m.first_name, m.middle_name, m.last_name, m.member_id
            FROM vital_signs v
            JOIN members m ON v.member_id COLLATE utf8mb4_unicode_ci = m.member_id COLLATE utf8mb4_unicode_ci
            $where_clause
            ORDER BY v.date_of_recording DESC, v.created_at DESC
            LIMIT 100";
} else {
    // Trainers can only see vital signs for assigned members
    $where_clause = "WHERE ta.trainer_id = ?";
    $params = [$trainer_id];

    if (!empty($member_filter)) {
        $where_clause .= " AND v.member_id = ?";
        $params[] = $member_filter;
    }

    $sql = "SELECT DISTINCT v.*, m.first_name, m.middle_name, m.last_name, m.member_id
            FROM vital_signs v
            JOIN members m ON v.member_id COLLATE utf8mb4_unicode_ci = m.member_id COLLATE utf8mb4_unicode_ci
            JOIN trainer_assignments ta ON m.member_id COLLATE utf8mb4_unicode_ci = ta.member_id COLLATE utf8mb4_unicode_ci
            $where_clause
            ORDER BY v.date_of_recording DESC, v.created_at DESC
            LIMIT 50";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$vital_records = $stmt->fetchAll();

// Calculate BMI function
function calc_bmi($weight, $height) {
    if (!$weight || !$height) return null;
    $height_m = $height / 100;
    return round($weight / ($height_m * $height_m), 1);
}

include '../header.php';
?>

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

<div class="container progress-vitals-container">
	<!-- Success/Error Messages -->
	<?php if ($message): ?>
	<div class="alert alert-success" id="alertMessage">
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="alert-icon">
			<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
		</svg>
		<span class="alert-message"><?php echo htmlspecialchars($message); ?></span>
	</div>
	<?php endif; ?>
	<?php if ($error): ?>
	<div class="alert alert-error" id="alertMessage">
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="alert-icon">
			<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
		</svg>
		<span class="alert-message"><?php echo htmlspecialchars($error); ?></span>
	</div>
	<?php endif; ?>

	<!-- Page Header -->
	<div class="page-header">
		<h1 class="page-title">Vital Signs</h1>
		<p class="page-subtitle"><?php echo $is_staff ? 'View vital signs for all members.' : 'Record and track vital signs for your assigned members.'; ?></p>
	</div>

	<!-- Filter and Add Button -->
	<div class="card" style="margin-bottom: 1.5rem;">
		<div class="filter-section">
			<form method="GET" action="" class="filter-form">
				<select name="member_id" onchange="this.form.submit()" 
						class="form-select form-select-red filter-select">
					<option value="">All Assigned Members</option>
					<?php foreach ($assigned_members as $member): ?>
						<option value="<?php echo $member['member_id']; ?>" <?php echo $member_filter === $member['member_id'] ? 'selected' : ''; ?>>
							<?php echo htmlspecialchars(trim($member['first_name'] . ' ' . ($member['middle_name'] ? $member['middle_name'] . ' ' : '') . $member['last_name'])); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</form>
			<?php if (!$is_staff): ?>
			<button onclick="document.getElementById('vitalsModal').classList.add('show')" 
					class="btn btn-red">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1.25rem; height: 1.25rem;">
					<path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
				</svg>
				Record Vital Signs
			</button>
			<?php endif; ?>
		</div>
	</div>

	<!-- Vital Signs Records -->
	<div class="card overflow-hidden">
		<div class="card-header">
			<h3 style="font-size: 1.125rem; font-weight: 600; color: var(--slate-900); display: flex; align-items: center; gap: 0.5rem;">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1.25rem; height: 1.25rem; color: var(--red-600);">
					<path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0 4.556-4.5 7.5-9 11.25-4.5-3.75-9-6.694-9-11.25a5.25 5.25 0 0 1 9-3.656A5.25 5.25 0 0 1 21 8.25Z"/>
				</svg>
				Vital Signs Records
			</h3>
		</div>
		<div class="overflow-x-auto">
			<table class="table">
				<thead class="table-header">
					<tr>
						<th>Member</th>
						<th>Date</th>
						<th>Weight</th>
						<th>Height</th>
						<th>BMI</th>
						<th>Waist Circumference</th>
						<th>Blood Pressure</th>
						<th>Heart Rate</th>
					</tr>
				</thead>
				<tbody>
					<?php if (!empty($vital_records)): ?>
						<?php foreach ($vital_records as $record): ?>
							<tr class="table-row">
								<td class="whitespace-nowrap">
									<div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars(trim($record['first_name'] . ' ' . ($record['middle_name'] ? $record['middle_name'] . ' ' : '') . $record['last_name'])); ?></div>
									<div class="text-xs text-gray-500"><?php echo htmlspecialchars($record['member_id']); ?></div>
								</td>
								<td class="whitespace-nowrap text-sm text-gray-900">
									<?php echo formatDate($record['date_of_recording']); ?>
								</td>
								<td class="whitespace-nowrap text-sm text-gray-900">
									<?php echo $record['weight'] ? number_format($record['weight'], 1) . ' kg' : '-'; ?>
								</td>
								<td class="whitespace-nowrap text-sm text-gray-900">
									<?php echo $record['height_cm'] ? number_format($record['height_cm'], 1) . ' cm' : '-'; ?>
								</td>
								<td class="whitespace-nowrap text-sm font-semibold text-gray-900">
									<?php echo $record['bmi'] ? number_format($record['bmi'], 1) : '-'; ?>
								</td>
								<td class="whitespace-nowrap text-sm text-gray-900">
									<?php echo $record['waist_circumference'] ? number_format($record['waist_circumference'], 1) . ' cm' : '-'; ?>
								</td>
								<td class="whitespace-nowrap text-sm text-gray-900">
									<?php 
									if ($record['blood_pressure_systolic'] && $record['blood_pressure_diastolic']) {
										echo $record['blood_pressure_systolic'] . '/' . $record['blood_pressure_diastolic'] . ' mmHg';
									} else {
										echo '-';
									}
									?>
								</td>
								<td class="whitespace-nowrap text-sm text-gray-900">
									<?php echo $record['heart_rate'] ? $record['heart_rate'] . ' bpm' : '-'; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php else: ?>
						<tr>
							<td colspan="7" style="padding: 3rem; text-align: center;">
								<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="empty-state-icon">
									<path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0 4.556-4.5 7.5-9 11.25-4.5-3.75-9-6.694-9-11.25a5.25 5.25 0 0 1 9-3.656A5.25 5.25 0 0 1 21 8.25Z"/>
								</svg>
								<p class="empty-state-title">No vital signs records found</p>
								<p class="empty-state-subtitle"><?php echo !empty($member_filter) ? 'This member has no vital signs recorded yet.' : 'No vital signs have been recorded for your assigned members.'; ?></p>
							</td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>

<!-- Record Vital Signs Modal (Only visible for trainers) -->
<?php if (!$is_staff): ?>
<div id="vitalsModal" class="modal-overlay" onclick="if(event.target === this) this.classList.remove('show')">
	<div class="modal-content" onclick="event.stopPropagation()" style="max-width: 42rem;">
		<div class="modal-header">
			<h3 class="modal-title">Record Vital Signs</h3>
			<button type="button" onclick="document.getElementById('vitalsModal').classList.remove('show')" class="modal-close">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1.5rem; height: 1.5rem;">
					<path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
				</svg>
			</button>
		</div>
		<form method="POST" class="modal-body">
			<input type="hidden" name="action" value="add_vitals">
			
			<div class="modal-form-spacing">
				<div class="modal-form-grid">
					<div>
						<label class="form-label">Member <span class="required">*</span></label>
						<select name="member_id" required class="form-select form-select-red">
							<option value="">Select Member</option>
							<?php foreach ($assigned_members as $member): ?>
								<option value="<?php echo $member['member_id']; ?>">
									<?php echo htmlspecialchars(trim($member['first_name'] . ' ' . ($member['middle_name'] ? $member['middle_name'] . ' ' : '') . $member['last_name']) . ' (' . $member['member_id'] . ')'); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
					<div>
						<label class="form-label">Date <span class="required">*</span></label>
						<input type="date" name="date_of_recording" value="<?php echo date('Y-m-d'); ?>" required class="form-input form-input-red">
					</div>
					<div>
						<label class="form-label">Weight (kg)</label>
						<input type="number" name="weight" step="0.1" min="0" class="form-input form-input-red">
					</div>
					<div>
						<label class="form-label">Height (cm)</label>
						<input type="number" name="height" step="0.1" min="0" class="form-input form-input-red">
					</div>
					<div>
						<label class="form-label">Waist Circumference (cm)</label>
						<input type="number" name="waist_circumference" step="0.1" min="0" class="form-input form-input-red">
					</div>
					<div>
						<label class="form-label">Body Fat (%)</label>
						<input type="number" name="body_fat_percentage" step="0.1" min="0" max="100" class="form-input form-input-red">
					</div>
					<div>
						<label class="form-label">Blood Pressure (mmHg)</label>
						<input type="text" name="blood_pressure" placeholder="e.g., 120/80" class="form-input form-input-red">
					</div>
					<div>
						<label class="form-label">Heart Rate (bpm)</label>
						<input type="number" name="heart_rate" min="0" max="300" class="form-input form-input-red">
					</div>
				</div>
				<div>
					<label class="form-label">Notes</label>
					<textarea name="notes" rows="3" class="form-textarea form-input-red"></textarea>
				</div>
			</div>
			
			<div class="modal-footer">
				<button type="button" onclick="document.getElementById('vitalsModal').classList.remove('show')" class="btn-secondary">
					Cancel
				</button>
				<button type="submit" class="btn btn-red">
					Record Vital Signs
				</button>
			</div>
		</form>
	</div>
</div>
<?php endif; ?>

<script>
	// Auto-hide alert messages after 5 seconds
	const alertMessage = document.getElementById('alertMessage');
	if (alertMessage) {
		setTimeout(function() {
			alertMessage.style.transition = 'opacity 0.5s ease-out';
			alertMessage.style.opacity = '0';
			setTimeout(function() {
				alertMessage.style.display = 'none';
			}, 500);
		}, 5000);
	}

	// Close modal when clicking outside
	<?php if (!$is_staff): ?>
	document.getElementById('vitalsModal')?.addEventListener('click', function(e) {
		if (e.target === this) {
			this.classList.remove('show');
		}
	});
	<?php endif; ?>
</script>

<?php include '../footer.php'; ?>

