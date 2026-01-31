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

$page_title = 'Member Progress';
$message = '';
$error = '';

// Get member filter
$member_filter = $_GET['member_id'] ?? '';

// Fetch all active members for dropdown
$stmt = $pdo->query("SELECT member_id, first_name, middle_name, last_name 
                    FROM members 
                    WHERE membership_status = 'Active'
                    ORDER BY first_name, last_name");
$all_members = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch progress records
$where_conditions = [];
$params = [];

if (!empty($member_filter)) {
    $where_conditions[] = "p.member_id = ?";
    $params[] = $member_filter;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

$sql = "SELECT p.*, m.first_name, m.middle_name, m.last_name, m.member_id,
        t.first_name as trainer_first_name, t.last_name as trainer_last_name
        FROM progress p
        JOIN members m ON p.member_id COLLATE utf8mb4_unicode_ci = m.member_id COLLATE utf8mb4_unicode_ci
        LEFT JOIN trainers t ON p.trainer_id COLLATE utf8mb4_unicode_ci = t.trainer_id COLLATE utf8mb4_unicode_ci
        $where_clause
        ORDER BY p.progress_date DESC
        LIMIT 200";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$progress_records = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
		<h2 class="page-title">Member Progress</h2>
		<p class="page-subtitle">View and monitor member progress records across all members.</p>
	</div>

	<!-- Success/Error Messages -->
	<?php if (isset($_GET['success'])): ?>
	<div class="alert alert-success">
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="alert-icon">
			<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
		</svg>
		<span class="alert-message"><?php echo htmlspecialchars($_GET['success']); ?></span>
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
						class="form-select filter-select">
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

	<!-- Progress Records Table -->
	<div class="card" style="overflow: hidden;">
		<div class="card-header">
			<h3 class="card-header-title">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="card-header-icon card-header-icon-purple">
					<path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
				</svg>
				Progress Records
			</h3>
		</div>
		<div class="overflow-x-auto">
			<?php if (!empty($progress_records)): ?>
				<table class="table">
					<thead class="table-header">
						<tr>
							<th>Member</th>
							<th>Date</th>
							<th>Exercise Name</th>
							<th>Sets</th>
							<th>Reps</th>
							<th>Weight/Resistance</th>
							<th>Duration</th>
							<th>Trainer</th>
							<th>Notes</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($progress_records as $index => $record): ?>
							<tr class="table-row progress-table-row" data-index="<?php echo $index; ?>">
								<td class="whitespace-nowrap">
									<div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars(trim($record['first_name'] . ' ' . ($record['middle_name'] ? $record['middle_name'] . ' ' : '') . $record['last_name'])); ?></div>
									<div class="text-xs text-gray-500"><?php echo htmlspecialchars($record['member_id']); ?></div>
								</td>
								<td class="whitespace-nowrap">
									<div class="text-sm font-semibold text-gray-900"><?php echo formatDate($record['progress_date']); ?></div>
								</td>
								<td class="whitespace-nowrap text-sm text-gray-900">
									<?php echo !empty($record['exercise_name']) ? htmlspecialchars($record['exercise_name']) : '<span class="text-italic-gray">-</span>'; ?>
								</td>
								<td class="whitespace-nowrap text-sm text-gray-900">
									<?php echo !empty($record['sets']) ? htmlspecialchars($record['sets']) : '<span class="text-italic-gray">-</span>'; ?>
								</td>
								<td class="whitespace-nowrap text-sm text-gray-900">
									<?php echo !empty($record['reps']) ? htmlspecialchars($record['reps']) : '<span class="text-italic-gray">-</span>'; ?>
								</td>
								<td class="whitespace-nowrap text-sm text-gray-900">
									<?php echo !empty($record['weight']) ? htmlspecialchars($record['weight']) . ' kg' : '<span class="text-italic-gray">-</span>'; ?>
								</td>
								<td class="whitespace-nowrap text-sm text-gray-900">
									<?php echo !empty($record['duration_minutes']) ? htmlspecialchars($record['duration_minutes']) . ' min' : '<span class="text-italic-gray">-</span>'; ?>
								</td>
								<td class="whitespace-nowrap text-sm text-gray-900">
									<?php 
									if (!empty($record['trainer_first_name']) || !empty($record['trainer_last_name'])) {
										echo htmlspecialchars(trim(($record['trainer_first_name'] ?? '') . ' ' . ($record['trainer_last_name'] ?? '')));
									} else {
										echo '<span class="text-italic-gray">-</span>';
									}
									?>
								</td>
								<td class="text-sm text-gray-900">
									<?php echo !empty($record['notes']) ? htmlspecialchars(mb_substr($record['notes'], 0, 50)) . (mb_strlen($record['notes']) > 50 ? '...' : '') : '<span class="text-italic-gray">-</span>'; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else: ?>
				<div class="table-empty-state">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="empty-state-icon">
						<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/>
					</svg>
					<p class="empty-state-title">No progress records found</p>
					<p class="empty-state-subtitle"><?php echo !empty($member_filter) ? 'This member has no progress records yet.' : 'No progress records available.'; ?></p>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<script>
		function toggleDetails(index) {
			const detailsRow = document.getElementById('details-' + index);
			const btn = document.querySelector(`[data-index="${index}"]`);
			const icon = btn.querySelector('.progress-details-icon');
			
			if (detailsRow.style.display === 'none') {
				detailsRow.style.display = 'table-row';
				icon.style.transform = 'rotate(180deg)';
				btn.classList.add('active');
			} else {
				detailsRow.style.display = 'none';
				icon.style.transform = 'rotate(0deg)';
				btn.classList.remove('active');
			}
		}
	</script>
</div>

<?php include '../footer.php'; ?>

