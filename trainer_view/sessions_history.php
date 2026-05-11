<?php
require_once '../config/functions.php';

// Get trainer_id from session
$trainer_id = null;
if (isset($_SESSION['username'])) {
    $stmt = $pdo->prepare('SELECT trainer_id FROM trainers WHERE username = ? LIMIT 1');
    $stmt->execute([$_SESSION['username']]);
    $trainer = $stmt->fetch();
    $trainer_id = $trainer['trainer_id'] ?? null;
}

if (!$trainer_id) {
    echo '<div style="background-color: #fee2e2; border: 1px solid #fecaca; border-radius: 0.75rem; padding: 1.5rem; margin: 2rem;">
        <p style="color: #991b1b;"><strong>Error:</strong> Trainer ID not found. Please log in again.</p>
    </div>';
    exit;
}

// Get trainer info
$stmt = $pdo->prepare('SELECT first_name, last_name, specialization FROM trainers WHERE trainer_id = ?');
$stmt->execute([$trainer_id]);
$trainer_info = $stmt->fetch();

// Get past sessions for this trainer
$history_sessions = [];
$stmt = $pdo->prepare('
    SELECT 
        mts.session_id,
        mts.session_date,
        mts.session_time,
        mts.session_duration,
        mts.status,
        mts.notes,
        m.member_id,
        m.first_name,
        m.last_name,
        mfg.fitness_goal,
        mfg.program_data
    FROM member_training_sessions mts
    JOIN members m ON mts.member_id = m.member_id COLLATE utf8mb4_unicode_ci
    LEFT JOIN member_fitness_goals mfg ON m.member_id = mfg.member_id COLLATE utf8mb4_unicode_ci
    WHERE mts.trainer_id = ? AND (mts.session_date < CURDATE() OR (mts.session_date = CURDATE() AND mts.status IN ("completed", "cancelled")))
    ORDER BY mts.session_date DESC, mts.session_time DESC
');
$stmt->execute([$trainer_id]);
$history_sessions = $stmt->fetchAll();

$page_title = 'Session History - Trainer Dashboard';
include '../header.php';
?>
<style>
<?php include 'sessions_styles.css'; ?>
* {
  box-sizing: border-box;
}

body {
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
  min-height: 100vh;
}

.sessions-container {
	max-width: 1280px;
	margin: 0 auto;
	padding: 2rem 1rem;
}

.page-header {
	margin-bottom: 3rem;
}

.page-title {
	font-size: 2.5rem;
	font-weight: 800;
	margin-bottom: 0.5rem;
	background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
	-webkit-background-clip: text;
	-webkit-text-fill-color: transparent;
	background-clip: text;
	letter-spacing: -0.5px;
}

.page-subtitle {
	font-size: 1.125rem;
	color: #64748b;
	font-weight: 500;
}

.card {
	background: white;
	border-radius: 1.25rem;
	padding: 2rem;
	box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.08);
	border: 1px solid rgba(255, 255, 255, 0.8);
	margin-bottom: 2rem;
}

.section-title {
	font-size: 1.5rem;
	font-weight: 800;
	color: #0f172a;
	margin-bottom: 1.5rem;
	display: flex;
	align-items: center;
	gap: 0.75rem;
}

.section-title svg {
	width: 1.75rem;
	height: 1.75rem;
	color: #667eea;
}

.session-grid {
	display: grid;
	gap: 1.5rem;
	margin-bottom: 2rem;
}

.session-card {
	background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
	border: 1px solid #e2e8f0;
	border-radius: 1rem;
	padding: 1.5rem;
	transition: all 0.3s ease;
}

.session-card:hover {
	box-shadow: 0 12px 24px -6px rgba(102, 126, 234, 0.15);
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
	font-size: 1.25rem;
	font-weight: 800;
	color: #0f172a;
}

.session-status {
	display: inline-block;
	padding: 0.5rem 1rem;
	border-radius: 0.5rem;
	font-size: 0.875rem;
	font-weight: 700;
	text-transform: capitalize;
}

.status-scheduled {
	background: #dbeafe;
	color: #1e40af;
}

.status-ongoing {
	background: #ddf4ff;
	color: #0369a1;
}

.status-completed {
	background: #dcfce7;
	color: #15803d;
}

.status-cancelled {
	background: #fee2e2;
	color: #991b1b;
}

.status-not-completed {
	background: #fef3c7;
	color: #92400e;
}

.session-details {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
	gap: 1rem;
	margin-bottom: 1rem;
}

.detail-item {
	display: flex;
	flex-direction: column;
	gap: 0.25rem;
}

.detail-label {
	font-size: 0.75rem;
	font-weight: 700;
	color: #64748b;
	text-transform: uppercase;
	letter-spacing: 0.5px;
}

.detail-value {
	font-size: 0.95rem;
	font-weight: 700;
	color: #334155;
}

.program-badge {
	display: inline-block;
	padding: 0.5rem 1rem;
	background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
	color: white;
	border-radius: 0.5rem;
	font-size: 0.875rem;
	font-weight: 700;
	margin-top: 0.75rem;
}

.empty-state {
	text-align: center;
	padding: 2rem;
	color: #64748b;
	background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
	border-radius: 1rem;
	border: 1px solid #e2e8f0;
}

.empty-state svg {
	width: 3rem;
	height: 3rem;
	margin: 0 auto 1rem;
	opacity: 0.5;
}

@media (max-width: 768px) {
	.page-title {
		font-size: 2rem;
	}
	
	.session-header {
		flex-direction: column;
	}
	
	.session-details {
		grid-template-columns: 1fr 1fr;
	}
}
</style>

<div class="sessions-container">
	<div class="page-header">
		<h2 class="page-title">Training Session History</h2>
		<p class="page-subtitle">Review your past training sessions</p>
	</div>

	<!-- Navigation Links -->
	<div class="sessions-nav">
		<a href="/gym/trainer_view/sessions.php" class="sessions-nav-link">Today</a>
		<a href="/gym/trainer_view/sessions_upcoming.php" class="sessions-nav-link">Upcoming</a>
		<a href="/gym/trainer_view/sessions_history.php" class="sessions-nav-link active">History</a>
	</div>

	<!-- Session History Section -->
	<div class="card">
		<h3 class="section-title">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
				<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
			</svg>
			Past Sessions
		</h3>

		<?php if (count($history_sessions) > 0): ?>
			<div class="session-grid">
				<?php 
				$current_month = null;
				$today = date('Y-m-d');
				foreach ($history_sessions as $session): 
					$session_month = date('F Y', strtotime($session['session_date']));
					$display_status = strtolower((string)($session['status'] ?? 'scheduled'));
					if ($session['session_date'] < $today && in_array($display_status, ['scheduled', 'ongoing'], true)) {
						$display_status = 'not-completed';
					}

					if ($current_month !== $session_month) {
						if ($current_month !== null) echo '</div>';
						$current_month = $session_month;
						echo '<div class="month-group"><h4 class="month-header">' . $session_month . '</h4>';
					}
				?>
					<div class="session-card history" onclick="openExerciseModal(<?php echo htmlspecialchars(json_encode(['session_id' => $session['session_id'], 'member_name' => $session['first_name'] . ' ' . $session['last_name'], 'session_date' => $session['session_date'], 'session_time' => $session['session_time'], 'fitness_goal' => $session['fitness_goal'], 'program_data' => $session['program_data']])); ?>)">
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
							<span class="session-status status-<?php echo htmlspecialchars($display_status); ?>">
								<?php 
									if ($display_status === 'completed') {
										echo '✓ Done';
									} elseif ($display_status === 'cancelled') {
										echo '✗ Cancelled';
									} elseif ($display_status === 'not-completed') {
										echo '⚠ Not Completed';
									} else {
										echo ucfirst($display_status);
									}
								?>
							</span>
						</div>

						<div class="session-details">
							<div class="detail-item">
								<span class="detail-label">Date</span>
								<span class="detail-value"><?php echo date('M d, Y', strtotime($session['session_date'])); ?></span>
							</div>
							<div class="detail-item">
								<span class="detail-label">Time</span>
								<span class="detail-value"><?php echo date('g:i A', strtotime($session['session_time'])); ?></span>
							</div>
							<div class="detail-item">
								<span class="detail-label">Duration</span>
								<span class="detail-value"><?php echo $session['session_duration']; ?> mins</span>
							</div>
							<div class="detail-item">
								<span class="detail-label">End Time</span>
								<span class="detail-value"><?php echo date('g:i A', strtotime($session['session_time']) + ($session['session_duration'] * 60)); ?></span>
							</div>
						</div>

						<?php if (!empty($session['notes'])): ?>
							<div style="margin-top: 1rem; padding: 0.75rem; background: #eff6ff; border-left: 3px solid #667eea; border-radius: 0.5rem; font-size: 0.875rem; color: #1e40af;">
								<strong>Notes:</strong> <?php echo htmlspecialchars($session['notes']); ?>
							</div>
						<?php endif; ?>
					</div>
				<?php 
				endforeach;
				if ($current_month !== null) echo '</div>';
				?>
			</div>
		<?php else: ?>
			<div class="empty-state">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
				</svg>
				<p>No past sessions found</p>
			</div>
		<?php endif; ?>
	</div>

</div>

<!-- Exercise Modal -->
<div id="exerciseModal" class="exercise-modal">
	<div class="exercise-modal-content">
		<div class="exercise-modal-header">
			<button class="exercise-modal-close" onclick="closeExerciseModal()">&times;</button>
			<h3 class="exercise-modal-title" id="modalMemberName">Member Name</h3>
			<p class="exercise-modal-subtitle" id="modalSessionInfo">Session Info</p>
		</div>
		<div class="exercise-modal-body" id="modalExerciseContent">
			<!-- Exercise content will be loaded here -->
		</div>
	</div>
</div>

<script src="exercise_modal.js"></script>
<?php include '../footer.php'; ?>
