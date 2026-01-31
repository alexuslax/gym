<?php

require_once '../config/functions.php';

// Get member_id from session
$member_id = null;
if (isset($_SESSION['member_id'])) {
    $member_id = $_SESSION['member_id'];
} elseif (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare('SELECT member_id FROM members WHERE user_id = ? LIMIT 1');
    $stmt->execute([$_SESSION['user_id']]);
    $member = $stmt->fetch();
    $member_id = $member['member_id'] ?? null;
}

if (!$member_id) {
    echo '<div style="background-color: #fee2e2; border: 1px solid #fecaca; border-radius: 0.75rem; padding: 1.5rem; margin: 2rem;">
        <p style="color: #991b1b;"><strong>Error:</strong> Member ID not found. Please log in again.</p>
    </div>';
    exit;
}

// Fetch member info with plan from billing table
$stmt = $pdo->prepare('
    SELECT m.first_name, m.membership_status, m.registration_date, mp.plan_name
    FROM members m
    LEFT JOIN (
        SELECT member_id, plan_id, MAX(created_at) as latest
        FROM billing
        GROUP BY member_id
    ) latest_billing ON m.member_id = latest_billing.member_id
    LEFT JOIN billing b ON latest_billing.member_id = b.member_id AND latest_billing.latest = b.created_at
    LEFT JOIN membership_plans mp ON b.plan_id = mp.plan_id
    WHERE m.member_id = ?
');
$stmt->execute([$member_id]);
$member = $stmt->fetch();

// Membership status, plan, expiry
$status = $member['membership_status'] ?? 'N/A';
$plan = $member['plan_name'] ?? 'N/A';
$reg_date = $member['registration_date'] ?? null;
$expiry = '';
if ($reg_date && $plan && $plan !== 'N/A') {
		$date = new DateTime($reg_date);
		switch (strtolower($plan)) {
				case 'monthly': $date->modify('+1 month'); break;
				case 'quarterly': $date->modify('+3 months'); break;
				case 'annual': $date->modify('+1 year'); break;
		}
		$expiry = $date->format('M d, Y');
}

// Attendance stats
$stmt = $pdo->prepare('SELECT COUNT(*) FROM attendance WHERE member_id = ? AND MONTH(date) = MONTH(CURRENT_DATE()) AND YEAR(date) = YEAR(CURRENT_DATE())');
$stmt->execute([$member_id]);
$attendance_this_month = $stmt->fetchColumn();
$stmt = $pdo->prepare('SELECT COUNT(*) FROM attendance WHERE member_id = ?');
$stmt->execute([$member_id]);
$attendance_total = $stmt->fetchColumn();

// Last 5 attendance logs
$stmt = $pdo->prepare('SELECT * FROM attendance WHERE member_id = ? ORDER BY date DESC, time_in DESC LIMIT 5');
$stmt->execute([$member_id]);
$attendance_logs = $stmt->fetchAll();

// Last payment
$stmt = $pdo->prepare('SELECT * FROM billing WHERE member_id = ? ORDER BY payment_date DESC LIMIT 1');
$stmt->execute([$member_id]);
$last_payment = $stmt->fetch();

// Last progress
$stmt = $pdo->prepare('SELECT * FROM progress WHERE member_id = ? ORDER BY progress_date DESC LIMIT 1');
$stmt->execute([$member_id]);
$last_progress = $stmt->fetch();

// Page title and header
$page_title = 'Member Dashboard';
include '../header.php';
?>
<style>
* {
  box-sizing: border-box;
}

body {
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
  min-height: 100vh;
}

.dashboard-container {
	max-width: 1280px;
	margin: 0 auto;
	padding: 2rem 1rem;
}

.dashboard-card {
	background: white;
	padding: 2rem;
	margin-bottom: 2rem;
	overflow: hidden;
	border-radius: 1.25rem;
	box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.08);
	border: 1px solid rgba(255, 255, 255, 0.8);
	animation: slideIn 0.6s ease;
}

.dashboard-title {
	font-size: 2.5rem;
	font-weight: 800;
	margin-bottom: 0.5rem;
	background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
	-webkit-background-clip: text;
	-webkit-text-fill-color: transparent;
	background-clip: text;
	letter-spacing: -0.5px;
}

.dashboard-subtitle {
	font-size: 1.125rem;
	color: #64748b;
	font-weight: 500;
}

.stats-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
	gap: 2rem;
	margin-bottom: 3rem;
}

.stat-card {
	position: relative;
	padding: 2rem;
	border-radius: 1.25rem;
	box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.08);
	border: 1px solid rgba(255, 255, 255, 0.8);
	overflow: hidden;
	transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
	background: white;
	cursor: default;
}

.stat-card:hover {
	box-shadow: 0 30px 60px -15px rgba(0, 0, 0, 0.12);
	transform: translateY(-8px);
}

.stat-card-blue {
	background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
}

.stat-card-blue:hover {
	box-shadow: 0 30px 60px -15px rgba(59, 130, 246, 0.15);
}

.stat-card-green {
	background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
}

.stat-card-green:hover {
	box-shadow: 0 30px 60px -15px rgba(34, 197, 94, 0.15);
}

.stat-card-amber {
	background: linear-gradient(135deg, #fffbeb 0%, #fed7aa 100%);
}

.stat-card-amber:hover {
	box-shadow: 0 30px 60px -15px rgba(245, 158, 11, 0.15);
}

.stat-card::before {
	content: '';
	position: absolute;
	top: -50%;
	right: -50%;
	width: 200px;
	height: 200px;
	border-radius: 50%;
	opacity: 0.05;
	transition: all 0.4s ease;
}

.stat-card-blue::before {
	background: #3b82f6;
}

.stat-card-green::before {
	background: #22c55e;
}

.stat-card-amber::before {
	background: #f59e0b;
}

.stat-card:hover::before {
	top: -30%;
	right: -30%;
}

.stat-card-content {
	position: relative;
	z-index: 1;
}

.stat-icon {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	width: 3.5rem;
	height: 3.5rem;
	border-radius: 1rem;
	margin-bottom: 1.25rem;
	box-shadow: 0 8px 16px -4px rgba(59, 130, 246, 0.3);
	transition: all 0.3s ease;
}

.stat-card:hover .stat-icon {
	transform: scale(1.1);
}

.stat-icon-blue {
	background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
	box-shadow: 0 8px 16px -4px rgba(59, 130, 246, 0.3);
}

.stat-icon-green {
	background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
	box-shadow: 0 8px 16px -4px rgba(34, 197, 94, 0.3);
}

.stat-icon-amber {
	background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
	box-shadow: 0 8px 16px -4px rgba(245, 158, 11, 0.3);
}

.stat-icon svg {
	width: 1.75rem;
	height: 1.75rem;
	color: white;
	stroke-width: 2;
}

.stat-label {
	font-size: 0.875rem;
	font-weight: 700;
	margin-bottom: 0.5rem;
	text-transform: uppercase;
	letter-spacing: 0.5px;
}

.stat-label-blue {
	color: #1d4ed8;
}

.stat-label-green {
	color: #15803d;
}

.stat-label-amber {
	color: #b45309;
}

.stat-value {
	font-size: 2rem;
	font-weight: 800;
	color: #0f172a;
	margin-bottom: 0.5rem;
	line-height: 1.2;
}

.stat-subtext {
	font-size: 0.875rem;
	color: #64748b;
	font-weight: 500;
	margin-bottom: 0.25rem;
}

.stat-subtext-bold {
	font-weight: 700;
	color: #334155;
}

.activity-section {
	margin-bottom: 2rem;
}

.activity-title {
	font-size: 1.5rem;
	font-weight: 800;
	margin-bottom: 1.5rem;
	color: #0f172a;
}

.activity-container {
	background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
	border-radius: 1.25rem;
	padding: 1.5rem;
	border: 1px solid #e2e8f0;
}

.activity-list {
	list-style: none;
	padding: 0;
	margin: 0;
}

.activity-item {
	display: flex;
	align-items: center;
	justify-between;
	padding: 1rem;
	background-color: white;
	border-radius: 0.75rem;
	box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
	margin-bottom: 0.75rem;
	transition: all 0.3s ease;
	border: 1px solid rgba(255, 255, 255, 0.8);
}

.activity-item:last-child {
	margin-bottom: 0;
}

.activity-item:hover {
	box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
	transform: translateY(-2px);
}

.activity-item-left {
	display: flex;
	align-items: center;
	gap: 1rem;
	flex: 1;
}

.activity-dot {
	width: 0.625rem;
	height: 0.625rem;
	border-radius: 50%;
	flex-shrink: 0;
	box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.activity-dot-green {
	background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
}

.activity-dot-blue {
	background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
}

.activity-dot-amber {
	background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.activity-text {
	font-size: 0.95rem;
	font-weight: 600;
	color: #0f172a;
}

.activity-text-sub {
	font-size: 0.8rem;
	color: #64748b;
	font-weight: 500;
	margin-top: 0.25rem;
}

.activity-value {
	font-size: 0.9rem;
	color: #475569;
	font-weight: 600;
}

.button-group {
	display: flex;
	flex-wrap: wrap;
	gap: 1rem;
}

.btn {
	display: inline-flex;
	align-items: center;
	gap: 0.625rem;
	padding: 0.875rem 1.75rem;
	border-radius: 0.75rem;
	font-size: 0.9rem;
	font-weight: 700;
	text-decoration: none;
	border: none;
	cursor: pointer;
	transition: all 0.3s ease;
	box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.btn:hover {
	transform: translateY(-2px);
	box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
}

.btn-primary {
	background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
	color: white;
}

.btn-primary:hover {
	background: linear-gradient(135deg, #5568d3 0%, #6b4090 100%);
}

.btn-green {
	background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
	color: white;
}

.btn-green:hover {
	background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
}

.btn-slate {
	background: linear-gradient(135deg, #64748b 0%, #475569 100%);
	color: white;
}

.btn-slate:hover {
	background: linear-gradient(135deg, #475569 0%, #334155 100%);
}

.btn svg {
	width: 1.25rem;
	height: 1.25rem;
	stroke: currentColor;
	stroke-width: 2;
}

.empty-state {
	padding: 1rem;
	text-align: center;
	color: #64748b;
	background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
	border-radius: 0.75rem;
	border: 1px solid #e2e8f0;
	font-weight: 500;
}

@keyframes slideIn {
	from {
		opacity: 0;
		transform: translateY(20px);
	}
	to {
		opacity: 1;
		transform: translateY(0);
	}
}

@media (max-width: 768px) {
	.dashboard-title {
		font-size: 2rem;
	}
	
	.stat-card {
		padding: 1.5rem;
	}
	
	.stats-grid {
		gap: 1.5rem;
	}
	
	.activity-item {
		padding: 0.75rem;
	}
	
	.btn {
		padding: 0.75rem 1.5rem;
		font-size: 0.85rem;
	}
}
</style>

<div class="dashboard-container">
	<div class="dashboard-card">
		<div style="margin-bottom: 2rem;">
			<h2 class="dashboard-title">Welcome back, <?php echo htmlspecialchars($member['first_name'] ?? 'Member'); ?>!</h2>
			<p class="dashboard-subtitle">Here's a quick overview of your membership and recent activity.</p>
		</div>
		<div class="stats-grid">
			<div class="stat-card stat-card-blue">
				<div class="stat-card-content">
					<div class="stat-icon stat-icon-blue">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
						</svg>
					</div>
					<div class="stat-label stat-label-blue">Membership Status</div>
					<div class="stat-value"><?php echo htmlspecialchars($status); ?></div>
					<div class="stat-subtext">Plan: <span class="stat-subtext-bold"><?php echo htmlspecialchars($plan); ?></span></div>
					<div class="stat-subtext">Expiry: <span class="stat-subtext-bold"><?php echo $expiry ?: 'N/A'; ?></span></div>
				</div>
			</div>
			<div class="stat-card stat-card-green">
				<div class="stat-card-content">
					<div class="stat-icon stat-icon-green">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5A2.25 2.25 0 0 1 5.25 5.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25M3 18.75A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75M3 18.75v-8.25A2.25 2.25 0 0 1 5.25 8.25h13.5A2.25 2.25 0 0 1 21 10.5v8.25"/>
						</svg>
					</div>
					<div class="stat-label stat-label-green">Attendance This Month</div>
					<div class="stat-value"><?php echo $attendance_this_month; ?></div>
					<div class="stat-subtext">Total Sessions: <span class="stat-subtext-bold"><?php echo $attendance_total; ?></span></div>
				</div>
			</div>
			<div class="stat-card stat-card-amber">
				<div class="stat-card-content">
					<div class="stat-icon stat-icon-amber">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
						</svg>
					</div>
					<div class="stat-label stat-label-amber" style="color: #b45309;">Progress Summary</div>
					<?php if ($last_progress): ?>
						<div class="stat-value" style="font-size: 1.125rem;"><?php echo htmlspecialchars($last_progress['summary'] ?? 'Updated'); ?></div>
						<div class="stat-subtext">Date: <span class="stat-subtext-bold"><?php echo htmlspecialchars($last_progress['progress_date']); ?></span></div>
					<?php else: ?>
						<div class="stat-subtext">No progress recorded yet.</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<div class="activity-section">
			<h3 class="activity-title">Recent Activity</h3>
			<div class="activity-container">
				<ul class="activity-list">
					<?php if ($attendance_logs): ?>
						<?php foreach (array_slice($attendance_logs, 0, 3) as $log): ?>
							<li class="activity-item">
								<div class="activity-item-left">
									<div class="activity-dot activity-dot-green"></div>
									<div>
										<span class="activity-text">Check-in: <?php echo htmlspecialchars($log['date']); ?> <?php echo htmlspecialchars($log['time_in']); ?></span>
										<?php if ($log['time_out']): ?>
											<span class="activity-text-sub" style="margin-left: 0.5rem;">• Check-out: <?php echo htmlspecialchars($log['time_out']); ?></span>
										<?php endif; ?>
									</div>
								</div>
							</li>
						<?php endforeach; ?>
					<?php else: ?>
						<li class="empty-state">No attendance records found.</li>
					<?php endif; ?>
					<li class="activity-item">
						<div class="activity-item-left">
							<div class="activity-dot activity-dot-blue"></div>
							<span class="activity-text">Last Payment:</span>
						</div>
						<span class="activity-value"><?php echo $last_payment ? '₱' . htmlspecialchars($last_payment['payment_amount']) . ' on ' . htmlspecialchars($last_payment['payment_date']) : 'No payments found.'; ?></span>
					</li>
					<li class="activity-item">
						<div class="activity-item-left">
							<div class="activity-dot activity-dot-amber"></div>
							<span class="activity-text">Last Progress Update:</span>
						</div>
						<span class="activity-value"><?php echo $last_progress ? htmlspecialchars($last_progress['progress_date']) : 'No progress found.'; ?></span>
					</li>
				</ul>
			</div>
		</div>
		<div class="button-group">
			<a href="attendance.php" class="btn btn-primary">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5A2.25 2.25 0 0 1 5.25 5.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25M3 18.75A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75M3 18.75v-8.25A2.25 2.25 0 0 1 5.25 8.25h13.5A2.25 2.25 0 0 1 21 10.5v8.25"/>
				</svg>
				View Attendance
			</a>
			<a href="billing.php" class="btn btn-green">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 7.5A2.25 2.25 0 0 1 4.5 5.25h15A2.25 2.25 0 0 1 21.75 7.5v9A2.25 2.25 0 0 1 19.5 18.75h-15A2.25 2.25 0 0 1 2.25 16.5v-9z"/>
				</svg>
				View Billing
			</a>
			<a href="profile.php" class="btn btn-slate">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v-1.125A3.375 3.375 0 0 0 12.375 12.75h-3.75A3.375 3.375 0 0 0 5.25 16.125V17.25M12.75 9A3 3 0 1 1 6.75 9a3 3 0 0 1 6 0M18.75 8.25l2.25 2.25-6 6-2.25.75.75-2.25 6-6z"/>
				</svg>
				Update Profile
			</a>
		</div>
	</div>
</div>
<?php include '../footer.php'; ?>
