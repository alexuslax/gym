<?php
require_once '../config/functions.php';

// Get trainer_id from session
$trainer_id = null;
if (isset($_SESSION['username'])) {
    $stmt = $pdo->prepare('SELECT trainer_id, first_name, last_name, specialization FROM trainers WHERE username = ? LIMIT 1');
    $stmt->execute([$_SESSION['username']]);
    $trainer = $stmt->fetch();
    $trainer_id = $trainer['trainer_id'] ?? null;
    $trainer_name = ($trainer['first_name'] ?? '') . ' ' . ($trainer['last_name'] ?? '');
    $specialization = $trainer['specialization'] ?? 'General Training';
}

if (!$trainer_id) {
    echo '<div style="background-color: #fee2e2; border: 1px solid #fecaca; border-radius: 0.75rem; padding: 1.5rem; margin: 2rem;">
        <p style="color: #991b1b;"><strong>Error:</strong> Trainer ID not found. Please log in again.</p>
    </div>';
    exit;
}

// Get today's sessions
$today_sessions = [];
$stmt = $pdo->prepare('
    SELECT 
        mts.session_id,
        mts.session_date,
        mts.session_time,
        mts.session_duration,
        mts.status,
        m.first_name,
        m.last_name,
        mfg.fitness_goal
    FROM member_training_sessions mts
    JOIN members m ON mts.member_id = m.member_id COLLATE utf8mb4_unicode_ci
    LEFT JOIN member_fitness_goals mfg ON m.member_id = mfg.member_id COLLATE utf8mb4_unicode_ci
    WHERE mts.trainer_id = ? AND mts.session_date = CURDATE() AND mts.status IN ("scheduled", "ongoing")
    ORDER BY mts.session_time
');
$stmt->execute([$trainer_id]);
$today_sessions = $stmt->fetchAll();

// Get upcoming sessions (next 7 days excluding today)
$upcoming_sessions = [];
$stmt = $pdo->prepare('
    SELECT 
        mts.session_id,
        mts.session_date,
        mts.session_time,
        mts.session_duration,
        mts.status,
        m.first_name,
        m.last_name,
        mfg.fitness_goal
    FROM member_training_sessions mts
    JOIN members m ON mts.member_id = m.member_id COLLATE utf8mb4_unicode_ci
    LEFT JOIN member_fitness_goals mfg ON m.member_id = mfg.member_id COLLATE utf8mb4_unicode_ci
    WHERE mts.trainer_id = ? 
        AND mts.session_date > CURDATE() 
        AND mts.session_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        AND mts.status IN ("scheduled", "ongoing")
    ORDER BY mts.session_date, mts.session_time
    LIMIT 5
');
$stmt->execute([$trainer_id]);
$upcoming_sessions = $stmt->fetchAll();

// Count upcoming sessions (includes today if time has not passed)
$stmt = $pdo->prepare('
	SELECT COUNT(*) as total
	FROM member_training_sessions
	WHERE trainer_id = ?
		AND status IN ("scheduled", "ongoing")
		AND session_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
		AND (
			session_date > CURDATE()
			OR (session_date = CURDATE() AND session_time >= CURTIME())
		)
');
$stmt->execute([$trainer_id]);
$upcoming_count = $stmt->fetchColumn();

// Get statistics
// Total sessions this month
$stmt = $pdo->prepare('
    SELECT COUNT(*) as total
    FROM member_training_sessions 
    WHERE trainer_id = ? 
        AND MONTH(session_date) = MONTH(CURDATE()) 
        AND YEAR(session_date) = YEAR(CURDATE())
        AND status IN ("scheduled", "ongoing", "completed")
');
$stmt->execute([$trainer_id]);
$monthly_sessions = $stmt->fetchColumn();

// Completed sessions this month
$stmt = $pdo->prepare('
    SELECT COUNT(*) as total
    FROM member_training_sessions 
    WHERE trainer_id = ? 
        AND MONTH(session_date) = MONTH(CURDATE()) 
        AND YEAR(session_date) = YEAR(CURDATE())
        AND status = "completed"
');
$stmt->execute([$trainer_id]);
$completed_sessions = $stmt->fetchColumn();

$page_title = 'Trainer Dashboard';
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
}

.section-title {
	font-size: 1.5rem;
	font-weight: 800;
	margin-bottom: 1.5rem;
	color: #0f172a;
	display: flex;
	align-items: center;
	gap: 0.75rem;
}

.section-title svg {
	width: 1.75rem;
	height: 1.75rem;
	color: #667eea;
}

.session-list {
	display: grid;
	gap: 1rem;
}

.session-item {
	background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
	border: 1px solid #e2e8f0;
	border-radius: 1rem;
	padding: 1.5rem;
	transition: all 0.3s ease;
}

.session-item:hover {
	box-shadow: 0 8px 16px -4px rgba(102, 126, 234, 0.15);
	border-color: #bfdbfe;
	transform: translateY(-2px);
}

.session-header {
	display: flex;
	justify-content: space-between;
	align-items: start;
	margin-bottom: 1rem;
	flex-wrap: wrap;
	gap: 1rem;
}

.session-member {
	font-size: 1.125rem;
	font-weight: 800;
	color: #0f172a;
}

.session-time {
	font-size: 1rem;
	font-weight: 700;
	color: #667eea;
}

.program-badge {
	display: inline-block;
	padding: 0.5rem 1rem;
	background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
	color: white;
	border-radius: 0.5rem;
	font-size: 0.75rem;
	font-weight: 700;
	margin-top: 0.5rem;
}

.session-details {
	display: flex;
	gap: 1.5rem;
	align-items: center;
	flex-wrap: wrap;
}

.detail-badge {
	display: inline-flex;
	align-items: center;
	gap: 0.5rem;
	padding: 0.5rem 0.75rem;
	background: white;
	border-radius: 0.5rem;
	font-size: 0.875rem;
	font-weight: 600;
	color: #64748b;
}

.detail-badge svg {
	width: 1rem;
	height: 1rem;
}

.empty-state {
	text-align: center;
	padding: 3rem 2rem;
	color: #64748b;
	background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
	border-radius: 1rem;
	border: 1px solid #e2e8f0;
}

.empty-state svg {
	width: 4rem;
	height: 4rem;
	margin: 0 auto 1rem;
	opacity: 0.5;
}

.empty-state p {
	font-size: 1.125rem;
	font-weight: 600;
	margin: 0;
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
	
	.session-header {
		flex-direction: column;
		gap: 0.5rem;
	}
}
</style>

<div class="dashboard-container">
	<div class="dashboard-card">
		<div style="margin-bottom: 2rem;">
			<h2 class="dashboard-title">Welcome, <?php echo htmlspecialchars($trainer_name); ?>!</h2>
			<p class="dashboard-subtitle">Specialization: <?php echo htmlspecialchars($specialization); ?></p>
		</div>

		<div class="stats-grid">
			<!-- Today's Sessions Card -->
			<div class="stat-card stat-card-blue">
				<div class="stat-card-content">
					<div class="stat-icon stat-icon-blue">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5-13.5H3.75A2.25 2.25 0 0 0 1.5 3.75v16.5A2.25 2.25 0 0 0 3.75 22.5h16.5a2.25 2.25 0 0 0 2.25-2.25V3.75A2.25 2.25 0 0 0 20.25 1.5Z"/>
						</svg>
					</div>
					<div class="stat-label stat-label-blue">Today's Sessions</div>
					<div class="stat-value"><?php echo count($today_sessions); ?></div>
					<div class="stat-subtext"><?php echo date('l, F j, Y'); ?></div>
				</div>
			</div>

			<!-- Monthly Sessions Card -->
			<div class="stat-card stat-card-green">
				<div class="stat-card-content">
					<div class="stat-icon stat-icon-green">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5A2.25 2.25 0 0 1 5.25 5.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25M3 18.75A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75"/>
						</svg>
					</div>
					<div class="stat-label stat-label-green">Sessions This Month</div>
					<div class="stat-value"><?php echo $monthly_sessions; ?></div>
					<div class="stat-subtext"><?php echo $completed_sessions; ?> completed</div>
				</div>
			</div>

			<!-- Upcoming Sessions Card -->
			<div class="stat-card stat-card-amber">
				<div class="stat-card-content">
					<div class="stat-icon stat-icon-amber">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0zM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
						</svg>
					</div>
					<div class="stat-label stat-label-amber">Upcoming Sessions</div>
					<div class="stat-value"><?php echo $upcoming_count; ?></div>
					<div class="stat-subtext">Next 7 days</div>
				</div>
			</div>
		</div>
	</div>

	<!-- Upcoming Sessions Section -->
	<?php if (count($upcoming_sessions) > 0): ?>
		<div class="dashboard-card">
			<h3 class="section-title">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5A2.25 2.25 0 0 1 5.25 5.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25"/>
				</svg>
				Upcoming Sessions (Next 7 Days)
			</h3>

			<div class="session-list">
				<?php foreach ($upcoming_sessions as $session): ?>
					<div class="session-item">
						<div class="session-header">
							<div>
								<div class="session-member"><?php echo htmlspecialchars($session['first_name'] . ' ' . $session['last_name']); ?></div>
								<?php if ($session['fitness_goal']): ?>
									<div class="program-badge">
										<?php 
											$goal_names = ['weight_loss' => 'Weight Loss', 'muscle_gain' => 'Muscle Gain', 'endurance' => 'Endurance', 'general_fitness' => 'General Fitness'];
											echo $goal_names[$session['fitness_goal']] ?? ucfirst(str_replace('_', ' ', $session['fitness_goal']));
										?>
									</div>
								<?php endif; ?>
							</div>
							<div class="session-time"><?php echo date('M d', strtotime($session['session_date'])) . ' - ' . date('g:i A', strtotime($session['session_time'])); ?></div>
						</div>

						<div class="session-details">
							<div class="detail-badge">
								<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
									<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5"/>
								</svg>
								<?php echo date('l', strtotime($session['session_date'])); ?>
							</div>
							<div class="detail-badge">
								<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
									<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5"/>
								</svg>
								<?php echo $session['session_duration']; ?> mins
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>
</div>

<?php include '../footer.php'; ?>
