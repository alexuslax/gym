<?php
require_once '../config/database.php';
require_once '../config/functions.php';

// Authentication Check
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'trainer') {
    header('Location: ../index.php');
    exit();
}

$page_title = 'Attendance Management';


// Date Selection
$selected_date = $_GET['date'] ?? date('Y-m-d');

// Fetch Attendance Records
$sql = "SELECT a.*, m.first_name, m.last_name, m.profile_picture, m.member_id 
        FROM attendance a
        JOIN members m ON a.member_id = m.member_id
        WHERE a.date = ?
        ORDER BY a.time_in DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$selected_date]);
$attendance_records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate Stats
$total_present = count($attendance_records);
$total_late = 0;
$currently_in = 0;

foreach ($attendance_records as $record) {
    if ($record['status'] === 'Late') $total_late++;
    if ($record['time_out'] === null) $currently_in++;
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

<div class="attendance-container">
	<!-- Page Header -->
	<div class="page-header">
		<h1 class="page-title">Attendance Management</h1>
		<p class="page-subtitle">Track and manage member attendance records.</p>
	</div>


	<!-- Stats Grid -->
	<div class="stats-grid">
		<div class="card-stats card-stats-blue">
			<div class="stat-glow-blue"></div>
			<div class="stat-content">
				<div class="stat-icon stat-icon-blue">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-md">
						<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h11.25c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z"/>
					</svg>
				</div>
				<h3 class="stat-label">Total Check-ins</h3>
				<p class="stat-value"><?php echo $total_present; ?></p>
			</div>
		</div>
		
		<div class="card-stats card-stats-green">
			<div class="stat-glow-green"></div>
			<div class="stat-content">
				<div class="stat-icon stat-icon-green">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-md">
						<path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0 1 12 21 8.25 8.25 0 0 1 6.038 7.047 8.287 8.287 0 0 0 9 9.601a8.983 8.983 0 0 1 2.361-6.867 8.21 8.21 0 0 0 4 4.465M15 21a3 3 0 0 1-6 0"/>
					</svg>
				</div>
				<h3 class="stat-label">Currently in Gym</h3>
				<p class="stat-value"><?php echo $currently_in; ?></p>
			</div>
		</div>

		<div class="card-stats card-stats-orange">
			<div class="stat-glow-orange"></div>
			<div class="stat-content">
				<div class="stat-icon stat-icon-orange">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-md">
						<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
					</svg>
				</div>
				<h3 class="stat-label">Late Arrivals</h3>
				<p class="stat-value"><?php echo $total_late; ?></p>
			</div>
		</div>
	</div>

	<!-- Controls & Table -->
	<div class="table-container">
		<!-- Toolbar -->
		<div class="table-toolbar">
			<div class="toolbar-left">
				<h2 class="card-title">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-sm text-blue">
						<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h11.25c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z"/>
					</svg>
					Attendance Log
				</h2>
				<span class="date-badge-inline">
					<?php echo date('F j, Y', strtotime($selected_date)); ?>
				</span>
			</div>
			
			<form action="" method="GET" class="date-filter-form">
				<input type="date" name="date" 
					   value="<?php echo $selected_date; ?>" 
					   class="date-input"
					   onchange="this.form.submit()">
			</form>
		</div>

		<!-- Table -->
		<div class="table-scroll">
			<table class="data-table">
				<thead>
					<tr>
						<th>Member</th>
						<th>Time In</th>
						<th>Time Out</th>
						<th>Duration</th>
						<th>Status</th>
					</tr>
				</thead>
				<tbody>
					<?php if (count($attendance_records) > 0): ?>
						<?php foreach ($attendance_records as $record): ?>
							<tr>
							<td>
								<div class="member-cell">
									<div class="member-avatar">
										<?php 
										$profile_pic = null;
										if (!empty($record['profile_picture'])) {
											$pic = trim($record['profile_picture']);
											// Check if it's an absolute URL
											if (preg_match('#^https?://#', $pic)) {
												$profile_pic = $pic;
											} else {
												// Check for paths starting with img/profiles/, uploads/, assets/
												if (preg_match('#^(img|uploads|assets)/#', $pic)) {
													// Try with different extensions if no extension provided
													$ext = pathinfo($pic, PATHINFO_EXTENSION);
													if ($ext) {
														if (file_exists(__DIR__ . '/../' . $pic)) {
															$profile_pic = '../' . $pic;
														}
													} else {
														// No extension, try common image extensions
														foreach (['jpg', 'jpeg', 'png', 'gif'] as $ext) {
															if (file_exists(__DIR__ . '/../' . $pic . '.' . $ext)) {
																$profile_pic = '../' . $pic . '.' . $ext;
																break;
															}
														}
													}
												} else {
													// Bare filename: look under img/profiles/
													$ext = pathinfo($pic, PATHINFO_EXTENSION);
													if ($ext) {
														if (file_exists(__DIR__ . '/../img/profiles/' . $pic)) {
															$profile_pic = '../img/profiles/' . $pic;
														}
													} else {
														// No extension, try common image extensions
														foreach (['jpg', 'jpeg', 'png', 'gif'] as $ext) {
															if (file_exists(__DIR__ . '/../img/profiles/' . $pic . '.' . $ext)) {
																$profile_pic = '../img/profiles/' . $pic . '.' . $ext;
																break;
															}
														}
													}
												}
											}
										}
										
										if ($profile_pic): ?>
											<img src="<?php echo htmlspecialchars($profile_pic); ?>" class="member-avatar-img" alt="">
										<?php else: ?>
											<div class="member-avatar-initial">
												<?php echo strtoupper(substr($record['first_name'], 0, 1)); ?>
											</div>
										<?php endif; ?>
									</div>
									<div class="member-info">
										<div class="member-info-name"><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></div>
										<div class="member-info-id"><?php echo htmlspecialchars($record['member_id']); ?></div>
									</div>
								</div>
							</td>
							<td>
								<?php echo date('h:i A', strtotime($record['time_in'])); ?>
							</td>
							<td>
									<?php echo $record['time_out'] ? date('h:i A', strtotime($record['time_out'])) : '<span class="duration-active">Active</span>'; ?>
								</td>
								<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
									<?php 
										if ($record['time_out']) {
											// Calculate duration from time_in to time_out
											$start = new DateTime($record['date'] . ' ' . $record['time_in']);
											$end = new DateTime($record['date'] . ' ' . $record['time_out']);
											$diff = $start->diff($end);
											$total_minutes = ($diff->h * 60) + $diff->i;
											echo $total_minutes . ' mins';
										} else {
											// Calculate duration from time_in to now
											$start = new DateTime($record['date'] . ' ' . $record['time_in']);
											$now = new DateTime();
											// Only if today
											if ($record['date'] === date('Y-m-d')) {
												$diff = $start->diff($now);
								echo $diff->h . 'h ' . $diff->i . 'm <span class="duration-pulse"></span>';
											} else {
												echo '-';
											}
										}
									?>
								</td>
							<td>
									<?php 
										$status_classes = [
										'Present' => 'status-badge-green',
										'Late' => 'status-badge-orange',
										'Absent' => 'status-badge-red'
									];
									$cls = $status_classes[$record['status']] ?? 'status-badge-gray';
								?>
								<span class="status-badge <?php echo $cls; ?>">
										<?php echo htmlspecialchars($record['status']); ?>
									</span>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php else: ?>
						<tr>
							<td colspan="5" class="empty-state">
								<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="empty-icon">
									<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h11.25c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z"/>
								</svg>
								<p class="empty-state-title">No attendance records found for this date.</p>
							</td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>

<?php include '../footer.php'; ?>
