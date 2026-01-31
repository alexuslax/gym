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

// Fetch attendance history (last 30 days)
$stmt = $pdo->prepare('SELECT * FROM attendance WHERE member_id = ? ORDER BY date DESC, time_in DESC LIMIT 30');
$stmt->execute([$member_id]);
$attendance = $stmt->fetchAll();

// Attendance summary
$stmt = $pdo->prepare('SELECT COUNT(*) FROM attendance WHERE member_id = ? AND YEARWEEK(date, 1) = YEARWEEK(CURDATE(), 1)');
$stmt->execute([$member_id]);
$visits_this_week = $stmt->fetchColumn();
$stmt = $pdo->prepare('SELECT COUNT(*) FROM attendance WHERE member_id = ? AND MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE())');
$stmt->execute([$member_id]);
$visits_this_month = $stmt->fetchColumn();

// Streaks (consecutive days attended)
$stmt = $pdo->prepare('SELECT date FROM attendance WHERE member_id = ? ORDER BY date DESC LIMIT 30');
$stmt->execute([$member_id]);
$dates = array_column($stmt->fetchAll(), 'date');
$streak = 0;
$today = date('Y-m-d');
foreach ($dates as $i => $date) {
		if ($i === 0 && $date === $today) {
				$streak = 1;
		} elseif ($i > 0 && date('Y-m-d', strtotime($dates[$i-1] . ' -1 day')) === $date) {
				$streak++;
		} else {
				break;
		}
}

// Missed days (in last 30 days)
$missed_days = 0;
if (count($dates) > 1) {
		$period = new DatePeriod(new DateTime($dates[array_key_last($dates)]), new DateInterval('P1D'), new DateTime($dates[0]));
		$attended = array_flip($dates);
		foreach ($period as $dt) {
				if (!isset($attended[$dt->format('Y-m-d')])) $missed_days++;
		}
}

// Last RFID scan
$stmt = $pdo->prepare('SELECT * FROM attendance WHERE member_id = ? ORDER BY date DESC, time_in DESC LIMIT 1');
$stmt->execute([$member_id]);
$last_scan = $stmt->fetch();

$page_title = 'Attendance';
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

.attendance-container {
	max-width: 1280px;
	margin: 0 auto;
	padding: 2rem 1rem;
}

.attendance-card {
	background: white;
	border-radius: 1.25rem;
	box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.08);
	border: 1px solid rgba(255, 255, 255, 0.8);
	padding: 2rem;
	margin-bottom: 2rem;
	overflow: hidden;
	animation: slideIn 0.6s ease;
}

.attendance-title {
	font-size: 2.5rem;
	font-weight: 800;
	margin-bottom: 0.5rem;
	background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
	-webkit-background-clip: text;
	-webkit-text-fill-color: transparent;
	background-clip: text;
	letter-spacing: -0.5px;
}

.attendance-subtitle {
	color: #64748b;
	font-size: 1.125rem;
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
	background: white;
	border-radius: 1.25rem;
	padding: 2rem;
	box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.08);
	border: 1px solid rgba(255, 255, 255, 0.8);
	overflow: hidden;
	transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
	cursor: default;
}

.stat-card:hover {
	box-shadow: 0 30px 60px -15px rgba(0, 0, 0, 0.12);
	transform: translateY(-8px);
}
.stat-card-green {
	background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
}

.stat-card-green:hover {
	box-shadow: 0 30px 60px -15px rgba(34, 197, 94, 0.15);
}
.stat-card-blue {
	background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
}

.stat-card-blue:hover {
	box-shadow: 0 30px 60px -15px rgba(59, 130, 246, 0.15);
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

.stat-card-amber::before {
	background: #f59e0b;
}
.stat-card-green::before {
	background: #22c55e;
}
.stat-card:hover::before {
	top: -30%;
	right: -30%;
}

.stat-content {
	position: relative;
	z-index: 1;
}

.stat-icon {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	width: 3.5rem;
	height: 3.5rem;
	background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
	border-radius: 1rem;
	margin-bottom: 1.25rem;
	box-shadow: 0 8px 16px -4px rgba(34, 197, 94, 0.3);
	transition: all 0.3s ease;
}

.stat-card:hover .stat-icon {
	transform: scale(1.1);
	box-shadow: 0 12px 24px -6px rgba(34, 197, 94, 0.4);
}

.stat-card-blue .stat-icon {
	background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
	box-shadow: 0 8px 16px -4px rgba(59, 130, 246, 0.3);
}

.stat-card-amber .stat-icon {
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
	color: #15803d;
	margin-bottom: 0.5rem;
	text-transform: uppercase;
	letter-spacing: 0.5px;
}

.stat-card-blue .stat-label {
	color: #1d4ed8;
}

.stat-card-amber .stat-label {
	color: #b45309;
}

.stat-value {
	font-size: 2rem;
	font-weight: 800;
	color: #0f172a;
	line-height: 1.2;
}

.stat-subtext {
	font-size: 0.875rem;
	color: #64748b;
	margin-top: 0.5rem;
	font-weight: 500;
}

.stat-subtext-bold {
	font-weight: 700;
	color: #334155;
}

.table-wrapper {
	overflow-x: auto;
	border-radius: 1.25rem;
	border: 1px solid rgba(255, 255, 255, 0.8);
	box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.08);
	margin-bottom: 2rem;
	background: white;
	animation: slideIn 0.6s ease 0.1s both;
}

.table {
	width: 100%;
	border-collapse: collapse;
}

.table thead {
	background: linear-gradient(to right, #f8fafc, #f1f5f9);
}

.table th {
	padding: 1.5rem 2rem;
	text-align: left;
	font-size: 0.75rem;
	font-weight: 700;
	color: #64748b;
	text-transform: uppercase;
	letter-spacing: 1px;
	border-bottom: 2px solid #e2e8f0;
}

.table tbody tr {
	border-bottom: 1px solid #f1f5f9;
	transition: all 0.2s ease;
}

.table tbody tr:hover {
	background-color: #f8fafc;
}

.table td {
	padding: 1.5rem 2rem;
	font-size: 0.95rem;
	color: #475569;
	font-weight: 500;
}

.table td strong {
	color: #0f172a;
	font-weight: 700;
}

.table-empty {
	text-align: center;
	padding: 3rem;
	color: #6b7280;
	background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
}

.table-empty svg {
	width: 3rem;
	height: 3rem;
	margin: 0 auto 0.75rem;
	color: #cbd5e1;
	stroke-width: 1.5;
}

.icon-inline {
	display: inline-flex;
	align-items: center;
	gap: 0.5rem;
}

.icon-green svg {
	color: #22c55e;
	width: 1.25rem;
	height: 1.25rem;
	stroke-width: 2;
}

.icon-blue svg {
	color: #3b82f6;
	width: 1.25rem;
	height: 1.25rem;
	stroke-width: 2;
}

.badge {
	display: inline-flex;
	align-items: center;
	padding: 0.5rem 0.875rem;
	border-radius: 0.75rem;
	font-size: 0.8rem;
	font-weight: 700;
	text-transform: uppercase;
	letter-spacing: 0.5px;
	background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
	color: #1e40af;
	box-shadow: 0 4px 12px rgba(30, 64, 175, 0.1);
}

.rfid-section {
	margin-top: 2rem;
	background: linear-gradient(135deg, #eff6ff 0%, #e0f2fe 100%);
	border-radius: 1.25rem;
	padding: 2rem;
	border: 1px solid rgba(219, 234, 254, 0.7);
	box-shadow: 0 20px 40px -10px rgba(59, 130, 246, 0.08);
}

.rfid-title {
	font-size: 1.25rem;
	font-weight: 800;
	color: #0f172a;
	margin-bottom: 1.25rem;
	display: flex;
	align-items: center;
	gap: 0.75rem;
}

.rfid-title svg {
	width: 1.5rem;
	height: 1.5rem;
	color: #2563eb;
	stroke-width: 2;
}

.rfid-content {
	display: flex;
	flex-wrap: wrap;
	gap: 1rem;
	background-color: white;
	border-radius: 0.75rem;
	padding: 1rem;
	box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.rfid-item {
	display: flex;
	align-items: center;
	gap: 0.5rem;
}

.rfid-label {
	font-size: 0.875rem;
	color: #4b5563;
}

.rfid-value {
	font-size: 0.875rem;
	font-weight: 600;
	color: #1f2937;
}

.status-badge {
	display: inline-flex;
	align-items: center;
	padding: 0.25rem 0.75rem;
	border-radius: 9999px;
	font-size: 0.75rem;
	font-weight: 600;
}

.status-present {
	background-color: #dcfce7;
	color: #166534;
}

.status-absent {
	background-color: #fee2e2;
	color: #991b1b;
}

.trends-section {
	margin-top: 2rem;
}

.trends-title {
	font-size: 1.25rem;
	font-weight: 700;
	color: #1e293b;
	margin-bottom: 1rem;
}

.trends-chart {
	background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
	padding: 2.5rem 2rem;
	border-radius: 1.5rem;
	box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.08), 0 0 0 1px rgba(0, 0, 0, 0.02);
	border: 1px solid rgba(255, 255, 255, 0.8);
	position: relative;
	overflow: hidden;
}

.trends-chart::before {
	content: '';
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	height: 4px;
	background: linear-gradient(90deg, #22c55e, #10b981, #14b8a6);
	border-radius: 1.5rem 1.5rem 0 0;
}

.chart-bars {
	display: flex;
	align-items: flex-end;
	justify-content: space-between;
	height: 280px;
	gap: 0.75rem;
	margin-bottom: 1rem;
	padding: 2rem 0.5rem 1rem;
	position: relative;
	background: linear-gradient(to bottom, transparent 0%, transparent 25%, rgba(241, 245, 249, 0.3) 25%, rgba(241, 245, 249, 0.3) 50%, transparent 50%, transparent 75%, rgba(241, 245, 249, 0.3) 75%, rgba(241, 245, 249, 0.3) 100%);
	border-radius: 0.75rem;
}

.chart-bars::after {
	content: '';
	position: absolute;
	bottom: 0;
	left: 0;
	right: 0;
	height: 2px;
	background: linear-gradient(90deg, #cbd5e1, #94a3b8, #cbd5e1);
}

.chart-bar-wrapper {
	flex: 1;
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 0.75rem;
	min-width: 0;
}

.chart-bar {
	width: 100%;
	max-width: 48px;
	background: linear-gradient(180deg, #10b981 0%, #059669 50%, #047857 100%);
	border-radius: 0.5rem 0.5rem 0 0;
	transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
	cursor: pointer;
	position: relative;
	min-height: 8px;
	box-shadow: 0 -2px 8px rgba(16, 185, 129, 0.2), 0 0 0 1px rgba(16, 185, 129, 0.1);
}

.chart-bar::before {
	content: '';
	position: absolute;
	top: 0;
	left: 50%;
	transform: translateX(-50%);
	width: 80%;
	height: 6px;
	background: rgba(255, 255, 255, 0.4);
	border-radius: 0.25rem;
	opacity: 0.6;
}

.chart-bar:hover {
	background: linear-gradient(180deg, #14b8a6 0%, #0d9488 50%, #0f766e 100%);
	transform: translateY(-4px) scaleX(1.1);
	box-shadow: 0 -4px 16px rgba(16, 185, 129, 0.4), 0 0 0 2px rgba(16, 185, 129, 0.2);
}

.chart-bar-count {
	position: absolute;
	top: -2rem;
	left: 50%;
	transform: translateX(-50%);
	font-size: 0.8rem;
	font-weight: 800;
	color: #0f766e;
	background: linear-gradient(135deg, #ffffff 0%, #f0fdfa 100%);
	padding: 0.25rem 0.625rem;
	border-radius: 0.5rem;
	white-space: nowrap;
	box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
	border: 1px solid #d1fae5;
	opacity: 0;
	transition: all 0.3s ease;
}

.chart-bar:hover .chart-bar-count {
	opacity: 1;
	top: -2.5rem;
}

.chart-bar-label {
	font-size: 0.7rem;
	color: #475569;
	font-weight: 700;
	text-align: center;
	white-space: nowrap;
	letter-spacing: 0.3px;
	padding: 0.25rem 0.5rem;
	background: white;
	border-radius: 0.375rem;
	box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
	border: 1px solid #e2e8f0;
}

.trends-placeholder {
	height: 12rem;
	display: flex;
	align-items: center;
	justify-content: center;
	background: linear-gradient(to bottom right, #f8fafc, #f1f5f9);
	border-radius: 1rem;
	border: 2px dashed #cbd5e1;
	box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
	text-align: center;
	color: #64748b;
}

.trends-placeholder svg {
	width: 3rem;
	height: 3rem;
	margin-bottom: 0.5rem;
	stroke-width: 1.5;
}

.trends-text {
	font-size: 0.875rem;
	font-weight: 500;
}

.empty-text {
	background-color: white;
	border-radius: 0.75rem;
	padding: 1rem;
	box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
	text-align: center;
	color: #6b7280;
	font-size: 0.875rem;
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

.stat-card {
	animation: slideIn 0.6s ease forwards;
}

.stat-card:nth-child(1) {
	animation-delay: 0.1s;
}

.stat-card:nth-child(2) {
	animation-delay: 0.2s;
}

.stat-card:nth-child(3) {
	animation-delay: 0.3s;
}

@media (max-width: 768px) {
	.attendance-container {
		padding: 1rem;
	}

	.attendance-card {
		padding: 1rem;
	}

	.attendance-title {
		font-size: 1.5rem;
	}

	.stats-grid {
		grid-template-columns: 1fr;
	}

	.table-wrapper {
		overflow-x: auto;
	}

	table {
		font-size: 0.85rem;
	}

	th, td {
		padding: 0.75rem 1rem !important;
	}

	.trends-section {
		margin-top: 1rem;
	}

	.trends-title {
		font-size: 1rem;
	}

	.chart-bars {
		height: 200px;
		padding: 1.5rem 0.25rem 1rem;
		gap: 0.5rem;
	}

	.chart-bar {
		max-width: 36px;
	}

	.chart-bar-label {
		font-size: 0.65rem;
		padding: 0.2rem 0.35rem;
	}

	.chart-bar-count {
		font-size: 0.7rem;
		padding: 0.2rem 0.5rem;
	}

	.trends-placeholder {
		height: 10rem;
	}

	.trends-placeholder svg {
		width: 2rem;
		height: 2rem;
	}
}
</style>

<div class="attendance-container">
	<div class="attendance-card">
		<div style="margin-bottom: 2rem;">
			<h2 class="attendance-title">Attendance History</h2>
			<p class="attendance-subtitle">Track your gym visits and attendance patterns</p>
		</div>
		<div style="margin-bottom: 2rem;">
			<div class="stats-grid">
				<div class="stat-card stat-card-green">
					<div class="stat-content">
						<div class="stat-icon">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5A2.25 2.25 0 0 1 5.25 5.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25M3 18.75A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75M3 18.75v-8.25A2.25 2.25 0 0 1 5.25 8.25h13.5A2.25 2.25 0 0 1 21 10.5v8.25"/>
							</svg>
						</div>
						<div class="stat-label">Visits This Week</div>
						<div class="stat-value"><?php echo $visits_this_week; ?></div>
					</div>
				</div>
				<div class="stat-card stat-card-blue">
					<div class="stat-content">
						<div class="stat-icon">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5A2.25 2.25 0 0 1 5.25 5.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25M3 18.75A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75M3 18.75v-8.25A2.25 2.25 0 0 1 5.25 8.25h13.5A2.25 2.25 0 0 1 21 10.5v8.25"/>
							</svg>
						</div>
						<div class="stat-label">Visits This Month</div>
						<div class="stat-value"><?php echo $visits_this_month; ?></div>
					</div>
				</div>
				<div class="stat-card stat-card-amber">
					<div class="stat-content">
						<div class="stat-icon">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
								<path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0 1 12 21 8.25 8.25 0 0 1 6.038 7.047 8.287 8.287 0 0 0 9 9.601a8.983 8.983 0 0 1 7.5-2.87 8.25 8.25 0 0 1 0 11.314m-6.5-2.5a8.25 8.25 0 0 1-5.314-5.314M12 9.75a8.25 8.25 0 0 1 5.314-5.314M12 9.75v4.5m0-4.5a8.25 8.25 0 0 0-5.314 5.314"/>
							</svg>
						</div>
						<div class="stat-label">Current Streak</div>
						<div class="stat-value"><?php echo $streak; ?> days</div>
						<div class="stat-subtext">Missed: <span class="stat-subtext-bold"><?php echo $missed_days; ?> days</span></div>
					</div>
				</div>
			</div>
			<div class="table-wrapper">
				<table class="table">
					<thead>
						<tr>
							<th>Date</th>
							<th>Check-in</th>
							<th>Check-out</th>
							<th>Duration</th>
						</tr>
					</thead>
					<tbody>
						<?php if (empty($attendance)): ?>
							<tr>
								<td colspan="4" class="table-empty">
									<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
										<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/>
									</svg>
									<p>No attendance records found</p>
								</td>
							</tr>
						<?php else: ?>
							<?php foreach ($attendance as $row): ?>
								<tr>
									<td><strong><?php echo htmlspecialchars($row['date']); ?></strong></td>
									<td>
										<?php if ($row['time_in']): ?>
											<span class="icon-inline icon-green">
												<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
													<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
												</svg>
												<?php echo formatTime($row['time_in']); ?>
											</span>
										<?php else: ?>
											<span style="color: #9ca3af;">-</span>
										<?php endif; ?>
									</td>
									<td>
										<?php if ($row['time_out']): ?>
											<span class="icon-inline icon-blue">
												<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
													<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
												</svg>
												<?php echo formatTime($row['time_out']); ?>
											</span>
										<?php else: ?>
											<span style="color: #9ca3af;">-</span>
										<?php endif; ?>
									</td>
									<td>
										<?php if ($row['time_in'] && $row['time_out']): ?>
											<span class="badge">
												<?php echo calculateDuration($row['time_in'], $row['time_out']); ?>
											</span>
										<?php else: ?>
											<span style="color: #9ca3af;">-</span>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
		<div class="rfid-section">
			<h3 class="rfid-title">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
					<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
				</svg>
				RFID Log Confirmation
			</h3>
			<?php if ($last_scan): ?>
				<div class="rfid-content">
					<div class="rfid-item">
						<span class="rfid-label">Last Scan:</span>
						<span class="rfid-value"><?php echo htmlspecialchars($last_scan['date']); ?> <?php echo $last_scan['time_in'] ? formatTime($last_scan['time_in']) : ''; ?></span>
					</div>
					<div class="rfid-item">
						<span class="rfid-label">Status:</span>
						<span class="status-badge <?php echo ($last_scan['status'] === 'Present') ? 'status-present' : 'status-absent'; ?>">
							<?php echo htmlspecialchars($last_scan['status']); ?>
						</span>
					</div>
				</div>
			<?php else: ?>
				<div class="empty-text">
					No RFID scan records found.
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>
<?php include '../footer.php'; ?>
