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

// Credit and Balance from members table and billing table
$stmt = $pdo->prepare('SELECT credit_balance FROM members WHERE member_id = ?');
$stmt->execute([$member_id]);
$member_balance = $stmt->fetch();
$total_credit = $member_balance['credit_balance'] ?? 0;

// Calculate outstanding balance (sum of unpaid bills)
$stmt = $pdo->prepare("SELECT COALESCE(SUM(billing_amount), 0) FROM billing WHERE member_id = ? AND payment_status IN ('Pending','Overdue')");
$stmt->execute([$member_id]);
$total_balance = $stmt->fetchColumn() ?: 0;

// Last progress
$stmt = $pdo->prepare('SELECT * FROM progress WHERE member_id = ? ORDER BY progress_date DESC LIMIT 1');
$stmt->execute([$member_id]);
$last_progress = $stmt->fetch();

// Get fitness goal
$stmt = $pdo->prepare('SELECT fitness_goal FROM member_fitness_goals WHERE member_id = ? LIMIT 1');
$stmt->execute([$member_id]);
$fitness_goal = $stmt->fetch();

// Get upcoming sessions
$stmt = $pdo->prepare('
    SELECT 
        mts.session_id,
        mts.session_date,
        mts.session_time,
        mts.session_duration,
        t.first_name,
        t.last_name,
        t.specialization
    FROM member_training_sessions mts
    JOIN trainers t ON mts.trainer_id = t.trainer_id
    WHERE mts.member_id = ? AND mts.session_date >= CURDATE() AND mts.status != "cancelled"
    ORDER BY mts.session_date, mts.session_time
    LIMIT 1
');
$stmt->execute([$member_id]);
$next_session = $stmt->fetch();

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

.stat-card-red {
	background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
}

.stat-card-red:hover {
	box-shadow: 0 30px 60px -15px rgba(239, 68, 68, 0.15);
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

.stat-icon-red {
	background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
	box-shadow: 0 8px 16px -4px rgba(239, 68, 68, 0.3);
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
	justify-content: space-between;
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
			<!-- Membership Status Card -->
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
				</div>
			</div>

			<!-- Credit Card -->
			<div class="stat-card stat-card-green">
				<div class="stat-card-content">
					<div class="stat-icon stat-icon-green">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0l.879-.659m-6-2.318a6 6 0 0 1 12 0M3.75 4.5h16.5a1.5 1.5 0 0 1 1.5 1.5v2.25a1.5 1.5 0 0 1-1.5 1.5H3.75a1.5 1.5 0 0 1-1.5-1.5V6a1.5 1.5 0 0 1 1.5-1.5z"/>
						</svg>
					</div>
					<div class="stat-label stat-label-green">Credit</div>
					<div class="stat-value" style="font-size: 1.5rem;">₱<?php echo number_format($total_credit, 2); ?></div>
					<div class="stat-subtext">Available balance</div>
				</div>
			</div>

			<!-- Balance Card -->
			<div class="stat-card stat-card-red">
				<div class="stat-card-content">
					<div class="stat-icon stat-icon-red">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0zm-9 3.75h.008v.008H12v-.008z"/>
						</svg>
					</div>
					<div class="stat-label" style="color: #991b1b;">Amount Due</div>
					<div class="stat-value" style="font-size: 1.5rem;">₱<?php echo number_format($total_balance, 2); ?></div>
					<div class="stat-subtext">Outstanding balance</div>
				</div>
			</div>

			<!-- Program Card -->
			<div class="stat-card stat-card-green">
				<div class="stat-card-content">
					<div class="stat-icon stat-icon-green">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" d="M9 4.5v15m6-15v15m-11.08.5H2.25A2.25 2.25 0 0 0 0 19.5v.75c0 1.036.84 1.875 1.95 1.875h15.1c1.11 0 1.95-.84 1.95-1.875v-.75A2.25 2.25 0 0 0 21.75 19.5h-2.17"/>
						</svg>
					</div>
					<div class="stat-label stat-label-green">Current Program</div>
					<?php if ($fitness_goal): 
						$goal_names = ['weight_loss' => 'Weight Loss', 'muscle_gain' => 'Muscle Gain', 'endurance' => 'Endurance', 'general_fitness' => 'General Fitness'];
						$program_name = $goal_names[$fitness_goal['fitness_goal']] ?? 'Not Set';
					?>
						<div class="stat-value"><?php echo htmlspecialchars($program_name); ?></div>
						<div class="stat-subtext">Active program</div>
					<?php else: ?>
						<div class="stat-subtext">No program selected yet</div>
						<a href="program.php" style="margin-top: 0.75rem; color: #22c55e; font-weight: 700; text-decoration: none;">Create Program →</a>
					<?php endif; ?>
				</div>
			</div>

			<!-- Upcoming Session Card -->
			<div class="stat-card stat-card-amber">
				<div class="stat-card-content">
					<div class="stat-icon stat-icon-amber">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5-13.5H3.75A2.25 2.25 0 0 0 1.5 3.75v16.5A2.25 2.25 0 0 0 3.75 22.5h16.5a2.25 2.25 0 0 0 2.25-2.25V3.75A2.25 2.25 0 0 0 20.25 1.5Z"/>
						</svg>
					</div>
					<div class="stat-label stat-label-amber" style="color: #b45309;">Upcoming Session</div>
					<?php if ($next_session): ?>
						<div class="stat-value" style="font-size: 1rem;"><?php echo date('M d, Y', strtotime($next_session['session_date'])); ?></div>
						<div class="stat-subtext">Time: <span class="stat-subtext-bold"><?php echo date('g:i A', strtotime($next_session['session_time'])); ?></span></div>
						<div class="stat-subtext">Trainer: <span class="stat-subtext-bold"><?php echo htmlspecialchars($next_session['first_name'] . ' ' . $next_session['last_name']); ?></span></div>
					<?php else: ?>
						<div class="stat-subtext">No upcoming sessions</div>
						<a href="sessions.php" style="margin-top: 0.75rem; color: #f59e0b; font-weight: 700; text-decoration: none;">Book Session →</a>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
</div>
<?php include '../footer.php'; ?>
