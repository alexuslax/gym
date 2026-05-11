<?php
require_once '../config/database.php';
require_once '../config/functions.php';

// Authentication Check - Admin only
if (session_status() === PHP_SESSION_NONE) session_start();
$role = $_SESSION['role'] ?? '';
if (!isset($_SESSION['user_id']) || !in_array($role, ['admin', 'staff'])) {
    header('Location: ../index.php');
    exit();
}

$page_title = 'Vital Signs';
$message = '';
$error = '';

// Get member filter
$member_filter = $_GET['member_id'] ?? '';

// Fetch all active members for dropdown
$stmt = $pdo->query("SELECT member_id, first_name, middle_name, last_name 
                     FROM members 
                     WHERE membership_status = 'Active'
                     ORDER BY first_name, last_name");
$all_members = $stmt->fetchAll();

// Fetch vital signs records
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
        LIMIT 200";

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
	<!-- Page Header -->
	<div class="page-header">
		<h2 class="page-title">Member Vital Signs</h2>
		<p class="page-subtitle">View and monitor vital signs for all members.</p>
	</div>

	<!-- Success/Error Messages -->
	<?php if ($message): ?>
	<div class="alert alert-success">
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="alert-icon">
			<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
		</svg>
		<span class="alert-message"><?php echo htmlspecialchars($message); ?></span>
	</div>
	<?php endif; ?>
	<?php if ($error): ?>
	<div class="alert alert-error">
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="alert-icon">
			<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
		</svg>
		<span class="alert-message"><?php echo htmlspecialchars($error); ?></span>
	</div>
	<?php endif; ?>

	<!-- Filter Section -->
	<div class="card" style="margin-bottom: 1.5rem;">
		<div class="filter-section">
			<form method="GET" action="" class="filter-form">
				<label class="form-label">Filter by Member</label>
				<select name="member_id" onchange="this.form.submit()" 
						class="form-select form-select-red filter-select">
					<option value="">All Members</option>
					<?php foreach ($all_members as $member): ?>
						<option value="<?php echo $member['member_id']; ?>" <?php echo $member_filter === $member['member_id'] ? 'selected' : ''; ?>>
							<?php echo htmlspecialchars(trim($member['first_name'] . ' ' . ($member['middle_name'] ? $member['middle_name'] . ' ' : '') . $member['last_name'])); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</form>
		</div>
	</div>

	<!-- Vital Signs Records -->
	<div class="card" style="overflow: hidden;">
		<div class="card-header">
			<h3 style="font-size: 1.125rem; font-weight: 600; color: var(--slate-900); display: flex; align-items: center; gap: 0.5rem;">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1.25rem; height: 1.25rem; color: var(--red-600);">
					<path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0 4.556-4.5 7.5-9 11.25-4.5-3.75-9-6.694-9-11.25a5.25 5.25 0 0 1 9-3.656A5.25 5.25 0 0 1 21 8.25Z"/>
				</svg>
				Vital Signs Records
			</h3>
		</div>
		<div style="overflow-x: auto;">
			<table class="table">
				<thead class="table-header">
					<tr>
						<th>Member</th>
						<th>Date</th>
						<th>Weight</th>
						<th>Height</th>
						<th>BMI</th>
						<th>Body Fat %</th>
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
									<?php 
									$bmi = calc_bmi($record['weight'], $record['height_cm']);
									echo $bmi ? $bmi : '-';
									?>
								</td>
								<td class="whitespace-nowrap text-sm text-gray-900">
									<?php echo $record['body_fat_percentage'] ? number_format($record['body_fat_percentage'], 1) . '%' : '-'; ?>
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
							<td colspan="8" style="padding: 3rem; text-align: center;">
								<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="empty-state-icon">
									<path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0 4.556-4.5 7.5-9 11.25-4.5-3.75-9-6.694-9-11.25a5.25 5.25 0 0 1 9-3.656A5.25 5.25 0 0 1 21 8.25Z"/>
								</svg>
								<p class="empty-state-title">No vital signs records found</p>
								<p class="empty-state-subtitle"><?php echo !empty($member_filter) ? 'This member has no vital signs recorded yet.' : 'No vital signs have been recorded.'; ?></p>
							</td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>

<?php include '../footer.php'; ?>

