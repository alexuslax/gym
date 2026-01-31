<?php
require_once '../config/functions.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'trainer') {
    header('Location: ../index.php');
    exit();
}

// Get trainer_id from username
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
    echo '<div class="error-message">Trainer session not found.</div>';
    exit();
}

// Get trainer name
$stmt = $pdo->prepare("SELECT first_name, middle_name, last_name FROM trainers WHERE trainer_id = ?");
$stmt->execute([$trainer_id]);
$trainer_info = $stmt->fetch();
$trainer_name = trim(($trainer_info['first_name'] ?? '') . ' ' . ($trainer_info['middle_name'] ?? '') . ' ' . ($trainer_info['last_name'] ?? ''));

// Fetch statistics
// Assigned members count
$stmt = $pdo->prepare("SELECT COUNT(DISTINCT member_id) as count FROM trainer_assignments WHERE trainer_id = ?");
$stmt->execute([$trainer_id]);
$assigned_members = $stmt->fetchColumn() ?? 0;

// Classes this week
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM trainer_assignments WHERE trainer_id = ? AND session_date >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY) AND session_date < DATE_ADD(DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY), INTERVAL 7 DAY)");
$stmt->execute([$trainer_id]);
$classes_this_week = $stmt->fetchColumn() ?? 0;

// Calculate average attendance from attendance records
$stmt = $pdo->prepare("SELECT 
    COUNT(DISTINCT a.member_id) as members_with_attendance,
    COUNT(DISTINCT ta.member_id) as total_assigned
    FROM trainer_assignments ta
    LEFT JOIN attendance a ON ta.member_id COLLATE utf8mb4_unicode_ci = a.member_id COLLATE utf8mb4_unicode_ci AND a.date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    WHERE ta.trainer_id = ?");
$stmt->execute([$trainer_id]);
$attendance_data = $stmt->fetch();
$total_assigned = $attendance_data['total_assigned'] ?? 1;
$members_with_attendance = $attendance_data['members_with_attendance'] ?? 0;
$avg_attendance = $total_assigned > 0 ? round(($members_with_attendance / $total_assigned) * 100) : 0;
$avg_attendance_display = $avg_attendance . '%';

// Completed sessions count
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM trainer_assignments WHERE trainer_id = ? AND status = 'Completed'");
$stmt->execute([$trainer_id]);
$completed_sessions = $stmt->fetchColumn() ?? 0;

// Upcoming sessions (next 7 days)
$stmt = $pdo->prepare("SELECT ta.*, m.first_name, m.middle_name, m.last_name 
                      FROM trainer_assignments ta 
                      JOIN members m ON ta.member_id COLLATE utf8mb4_unicode_ci = m.member_id COLLATE utf8mb4_unicode_ci 
                      WHERE ta.trainer_id = ? AND ta.session_date >= CURDATE() AND ta.session_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                      ORDER BY ta.session_date ASC, ta.start_time ASC 
                      LIMIT 10");
$stmt->execute([$trainer_id]);
$upcoming_sessions = $stmt->fetchAll();

// Notifications (placeholder - can be enhanced)
$notifications = [];

$page_title = 'Trainer Dashboard';
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

<div class="dashboard-container">
	<!-- Page Header -->
	<div class="page-header">
		<h1 class="page-title">
			Welcome back<?php echo !empty($trainer_name) ? ', ' . htmlspecialchars(explode(' ', $trainer_name)[0]) : ''; ?>!
		</h1>
		<p class="page-subtitle">Here's an overview of your training activities and upcoming sessions.</p>
	</div>

	<div style="margin-bottom: 2rem;">
		<div class="date-badge">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-sm text-blue">
				<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5A2.25 2.25 0 0 1 5.25 5.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25M3 18.75A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75M3 18.75v-8.25A2.25 2.25 0 0 1 5.25 8.25h13.5A2.25 2.25 0 0 1 21 10.5v8.25"/>
			</svg>
			<span class="date-text"><?php echo date('l, F j, Y'); ?></span>
		</div>
	</div>
	</div>

	<!-- Quick Stats -->
	<div class="stats-grid">
		<div class="card-stats card-stats-blue">
			<div class="stat-glow-blue"></div>
			<div class="stat-content">
				<div class="stat-icon stat-icon-blue">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-md">
						<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.5v-1.125A3.375 3.375 0 0 0 11.625 15h-3.75A3.375 3.375 0 0 0 4.5 18.375V19.5M12 10.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0M18 8.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0M20.25 19.5v-1.125A3.375 3.375 0 0 0 17.25 15h-1.125"/>
					</svg>
				</div>
				<h3 class="stat-label">Assigned Members</h3>
				<p class="stat-value"><?php echo $assigned_members; ?></p>
			</div>
		</div>
		<div class="card-stats card-stats-green">
			<div class="stat-glow-green"></div>
			<div class="stat-content">
				<div class="stat-icon stat-icon-green">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-md">
						<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5A2.25 2.25 0 0 1 5.25 5.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25M3 18.75A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75M3 18.75v-8.25A2.25 2.25 0 0 1 5.25 8.25h13.5A2.25 2.25 0 0 1 21 10.5v8.25"/>
					</svg>
				</div>
				<h3 class="stat-label">Classes This Week</h3>
				<p class="stat-value"><?php echo $classes_this_week; ?></p>
			</div>
		</div>
		<div class="card-stats card-stats-yellow">
			<div class="stat-glow-yellow"></div>
			<div class="stat-content">
				<div class="stat-icon stat-icon-yellow">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-md">
						<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
					</svg>
				</div>
				<h3 class="stat-label">Avg. Attendance</h3>
				<p class="stat-value"><?php echo $avg_attendance_display; ?></p>
			</div>
		</div>
		<div class="card-stats card-stats-purple">
			<div class="stat-glow-purple"></div>
			<div class="stat-content">
				<div class="stat-icon stat-icon-purple">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-md">
						<path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z"/>
					</svg>
				</div>
				<h3 class="stat-label">Completed Sessions</h3>
				<p class="stat-value"><?php echo $completed_sessions; ?></p>
			</div>
		</div>
	</div>

	<!-- Upcoming Sessions & Notifications -->
	<div class="content-grid">
		<!-- Upcoming Sessions -->
		<div class="content-grid-main card">
			<div class="card-header">
				<h2 class="card-title">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-sm text-blue">
						<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5A2.25 2.25 0 0 1 5.25 5.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25M3 18.75A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75M3 18.75v-8.25A2.25 2.25 0 0 1 5.25 8.25h13.5A2.25 2.25 0 0 1 21 10.5v8.25"/>
					</svg>
					Upcoming Sessions
				</h2>
			</div>
			<div class="card-body">
				<?php if (!empty($upcoming_sessions)): ?>
					<div class="sessions-list">
						<?php foreach ($upcoming_sessions as $sess): ?>
							<a href="schedule.php" class="session-link">
								<div class="card-session">
								<div class="session-icon-wrapper">
									<div class="session-icon">
										<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-md">
												<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
											</svg>
										</div>
									</div>
									<div class="session-content">
									<div class="session-header">
										<div class="session-details">
												<h3 class="session-title">
													<?php echo htmlspecialchars(trim($sess['first_name'] . ' ' . ($sess['middle_name'] ? $sess['middle_name'] . ' ' : '') . $sess['last_name'])); ?>
												</h3>
												<div class="session-meta">
												<span class="session-meta-badge">
													<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-xs">
															<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5A2.25 2.25 0 0 1 5.25 5.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25M3 18.75A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75M3 18.75v-8.25A2.25 2.25 0 0 1 5.25 8.25h13.5A2.25 2.25 0 0 1 21 10.5v8.25"/>
														</svg>
														<?php echo formatDate($sess['session_date']); ?>
													</span>
												<span class="session-meta-badge">
													<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-xs">
															<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
														</svg>
														<?php echo date('h:i A', strtotime($sess['start_time'])); ?> - <?php echo date('h:i A', strtotime($sess['end_time'])); ?>
													</span>
												<span class="session-meta-badge">
													<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-xs">
															<path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z"/>
															<path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z"/>
														</svg>
														<?php echo htmlspecialchars($sess['session_type']); ?>
													</span>
												</div>
											</div>
											<div class="session-status">
												<?php
												$status_badges = [
													'Scheduled' => 'badge-blue',
													'Completed' => 'badge-green',
													'Cancelled' => 'badge-red',
													'No Show' => 'badge-yellow'
												];
												$status = $sess['status'] ?? 'Scheduled';
												$badge_class = $status_badges[$status] ?? 'badge-gray';
												?>
												<span class="status-badge <?php echo $badge_class; ?>">
													<?php echo htmlspecialchars($status); ?>
												</span>
											</div>
										</div>
									</div>
								</div>
							</a>
						<?php endforeach; ?>
					</div>
				<?php else: ?>
					<div class="empty-state">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="empty-icon">
							<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5A2.25 2.25 0 0 1 5.25 5.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25M3 18.75A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75M3 18.75v-8.25A2.25 2.25 0 0 1 5.25 8.25h13.5A2.25 2.25 0 0 1 21 10.5v8.25"/>
						</svg>
						<p class="empty-state-title">No upcoming sessions</p>
						<p class="empty-state-subtitle">Your scheduled sessions will appear here</p>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<!-- Quick Stats -->
		<div class="card">
			<div class="card-header">
				<h2 class="card-title">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-sm text-purple">
						<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/>
					</svg>
					Quick Stats
				</h2>
			</div>
			<div class="card-body">
				<div class="quick-stats">
					<div class="quick-stat-item quick-stat-blue">
						<div class="quick-stat-left">
							<div class="quick-stat-icon quick-stat-icon-blue">
								<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-sm">
									<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
								</svg>
							</div>
							<div class="quick-stat-info">
								<p class="quick-stat-label">Total Sessions</p>
								<p class="quick-stat-sublabel">All time</p>
							</div>
						</div>
						<p class="quick-stat-value quick-stat-value-blue"><?php echo $completed_sessions; ?></p>
					</div>
					<div class="quick-stat-item quick-stat-green">
						<div class="quick-stat-left">
							<div class="quick-stat-icon quick-stat-icon-green">
								<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-sm">
									<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.5v-1.125A3.375 3.375 0 0 0 11.625 15h-3.75A3.375 3.375 0 0 0 4.5 18.375V19.5M12 10.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0M18 8.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0M20.25 19.5v-1.125A3.375 3.375 0 0 0 17.25 15h-1.125"/>
								</svg>
							</div>
							<div class="quick-stat-info">
								<p class="quick-stat-label">Active Members</p>
								<p class="quick-stat-sublabel">Currently assigned</p>
							</div>
						</div>
						<p class="quick-stat-value quick-stat-value-green"><?php echo $assigned_members; ?></p>
					</div>
					<div class="quick-stat-item quick-stat-purple">
						<div class="quick-stat-left">
							<div class="quick-stat-icon quick-stat-icon-purple">
								<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-sm">
									<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5A2.25 2.25 0 0 1 5.25 5.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25M3 18.75A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75M3 18.75v-8.25A2.25 2.25 0 0 1 5.25 8.25h13.5A2.25 2.25 0 0 1 21 10.5v8.25"/>
								</svg>
							</div>
							<div class="quick-stat-info">
								<p class="quick-stat-label">This Week</p>
								<p class="quick-stat-sublabel">Scheduled sessions</p>
							</div>
						</div>
						<p class="quick-stat-value quick-stat-value-purple"><?php echo $classes_this_week; ?></p>
					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- Quick Actions -->
	<div class="section-header">
		<h2 class="section-title">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-sm text-blue">
				<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z"/>
			</svg>
			Quick Actions
		</h2>
	</div>
	<div class="actions-grid">
		<a href="attendance.php" class="action-card action-card-blue">
			<div class="action-glow"></div>
			<div class="action-content">
				<div class="action-icon-wrapper">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-lg">
						<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h11.25c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z"/>
					</svg>
				</div>
				<span class="action-title">Attendance</span>
				<span class="action-subtitle">View records</span>
			</div>
		</a>
		<a href="members.php" class="action-card action-card-green">
			<div class="action-glow"></div>
			<div class="action-content">
				<div class="action-icon-wrapper">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-lg">
						<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.5v-1.125A3.375 3.375 0 0 0 11.625 15h-3.75A3.375 3.375 0 0 0 4.5 18.375V19.5M12 10.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0M18 8.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0M20.25 19.5v-1.125A3.375 3.375 0 0 0 17.25 15h-1.125"/>
					</svg>
				</div>
				<span class="action-title">Members</span>
				<span class="action-subtitle">Manage clients</span>
			</div>
		</a>
		<a href="schedule.php" class="action-card action-card-yellow">
			<div class="action-glow"></div>
			<div class="action-content">
				<div class="action-icon-wrapper">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-lg">
						<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5A2.25 2.25 0 0 1 5.25 5.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25M3 18.75A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75M3 18.75v-8.25A2.25 2.25 0 0 1 5.25 8.25h13.5A2.25 2.25 0 0 1 21 10.5v8.25"/>
					</svg>
				</div>
				<span class="action-title">Schedule</span>
				<span class="action-subtitle">View calendar</span>
			</div>
		</a>
		<a href="progress.php" class="action-card action-card-purple">
			<div class="action-glow"></div>
			<div class="action-content">
				<div class="action-icon-wrapper">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-lg">
						<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/>
					</svg>
				</div>
				<span class="action-title">Progress</span>
				<span class="action-subtitle">Track progress</span>
			</div>
		</a>
		<a href="vitals.php" class="action-card action-card-red">
			<div class="action-glow"></div>
			<div class="action-content">
				<div class="action-icon-wrapper">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-lg">
						<path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0 4.556-4.5 7.5-9 11.25-4.5-3.75-9-6.694-9-11.25a5.25 5.25 0 0 1 9-3.656A5.25 5.25 0 0 1 21 8.25Z"/>
					</svg>
				</div>
				<span class="action-title">Vital Signs</span>
				<span class="action-subtitle">Record vitals</span>
			</div>
		</a>
	</div>
</div>
<?php include '../footer.php'; ?>
