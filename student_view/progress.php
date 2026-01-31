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

// Assigned training program (latest assignment)
$stmt = $pdo->prepare('SELECT ta.*, TRIM(CONCAT(COALESCE(t.first_name, ""), " ", COALESCE(t.middle_name, ""), " ", COALESCE(t.last_name, ""))) as trainer_name FROM trainer_assignments ta JOIN trainers t ON ta.trainer_id COLLATE utf8mb4_unicode_ci = t.trainer_id COLLATE utf8mb4_unicode_ci WHERE ta.member_id = ? ORDER BY ta.session_date DESC LIMIT 1');
$stmt->execute([$member_id]);
$program = $stmt->fetch();

// Progress logs (last 20)
$stmt = $pdo->prepare('SELECT * FROM progress WHERE member_id = ? ORDER BY progress_date DESC LIMIT 20');
$stmt->execute([$member_id]);
$logs = $stmt->fetchAll();

// Completed/remaining sessions (count assignments)
$stmt = $pdo->prepare('SELECT COUNT(*) FROM trainer_assignments WHERE member_id = ? AND status = "Completed"');
$stmt->execute([$member_id]);
$completed_sessions = $stmt->fetchColumn();
$stmt = $pdo->prepare('SELECT COUNT(*) FROM trainer_assignments WHERE member_id = ?');
$stmt->execute([$member_id]);
$total_sessions = $stmt->fetchColumn();
$remaining_sessions = $total_sessions - $completed_sessions;

// Trainer feedback (latest progress note)
$trainer_feedback = null;
if ($logs) {
    foreach ($logs as $log) {
        if (!empty($log['notes'])) {
            $trainer_feedback = $log['notes'];
            break;
        }
    }
}

// Progress status (simple logic)
$progress_status = 'Active';
if ($completed_sessions > 0 && $remaining_sessions == 0) {
    $progress_status = 'Completed';
} elseif ($logs && isset($logs[0]['calories_burned']) && $logs[0]['calories_burned'] < 100) {
    $progress_status = 'Needs Improvement';
}

$page_title = 'Progress - UEP Fitness Gym';
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

.progress-container {
  max-width: 1280px;
  margin: 0 auto;
  padding: 2rem 1rem;
}

.progress-header {
  margin-bottom: 3rem;
  animation: slideIn 0.6s ease;
}

.progress-title {
  font-size: 2.5rem;
  font-weight: 800;
  margin-bottom: 0.5rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  letter-spacing: -0.5px;
}

.progress-subtitle {
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
  border-color: rgba(255, 255, 255, 1);
}

.stat-card-green {
  background: linear-gradient(135deg, #f0fdf4 0%, #dbeafe 100%);
}

.stat-card-green:hover {
  box-shadow: 0 30px 60px -15px rgba(34, 197, 94, 0.15);
}

.stat-card-amber {
  background: linear-gradient(135deg, #fffbeb 0%, #fff3cd 100%);
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
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 1rem;
  margin-bottom: 1.25rem;
  box-shadow: 0 8px 16px -4px rgba(102, 126, 234, 0.3);
  transition: all 0.3s ease;
}

.stat-card:hover .stat-icon {
  transform: scale(1.1);
  box-shadow: 0 12px 24px -6px rgba(102, 126, 234, 0.4);
}

.stat-card-green .stat-icon {
  background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
  box-shadow: 0 8px 16px -4px rgba(34, 197, 94, 0.3);
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
  color: #94a3b8;
  font-weight: 600;
  margin-bottom: 0.5rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
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

.stat-subtext-bold {
  font-weight: 700;
  color: #334155;
}

.feedback-box {
  background: white;
  border-left: 5px solid #667eea;
  border-radius: 1.25rem;
  padding: 2rem;
  margin-bottom: 2rem;
  box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.08);
  display: flex;
  align-items: flex-start;
  gap: 1.5rem;
  animation: slideIn 0.6s ease 0.1s both;
}

.feedback-icon {
  flex-shrink: 0;
  width: 3.5rem;
  height: 3.5rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 1rem;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 8px 16px -4px rgba(102, 126, 234, 0.3);
}

.feedback-icon svg {
  width: 1.75rem;
  height: 1.75rem;
  color: white;
  stroke-width: 2;
}

.feedback-title {
  font-size: 1.25rem;
  font-weight: 800;
  color: #0f172a;
  margin-bottom: 0.5rem;
}

.feedback-text {
  color: #475569;
  line-height: 1.6;
  font-weight: 500;
}

.table-card {
  background: white;
  border-radius: 1.25rem;
  box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.8);
  overflow: hidden;
  animation: slideIn 0.6s ease 0.2s both;
}

.table-header {
  padding: 2rem;
  border-bottom: 2px solid #f1f5f9;
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
}

.table-title {
  font-size: 1.5rem;
  font-weight: 800;
  color: #0f172a;
  margin: 0;
}

.table-wrapper {
  overflow-x: auto;
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
  .progress-title {
    font-size: 2rem;
  }
  
  .stat-card {
    padding: 1.5rem;
  }
  
  .table th,
  .table td {
    padding: 1rem;
  }
  
  .stats-grid {
    gap: 1.5rem;
  }
  
  .feedback-box {
    flex-direction: column;
    gap: 1rem;
  }
}

.stat-card-green .stat-icon {
  background-color: #22c55e;
}

.stat-card-amber .stat-icon {
  background-color: #f59e0b;
}

.stat-icon svg {
  width: 1.5rem;
  height: 1.5rem;
  color: white;
  stroke-width: 2;
}

.stat-label {
  font-size: 0.875rem;
  color: #4b5563;
  font-weight: 600;
  margin-bottom: 0.25rem;
}

.stat-value {
  font-size: 1.875rem;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 0.25rem;
}

.stat-subtext {
  font-size: 0.875rem;
  color: #4b5563;
}

.stat-subtext-bold {
  font-weight: 600;
  color: #1f2937;
}

.feedback-box {
  background: linear-gradient(to right, #eff6ff, #e0f2fe);
  border-left: 4px solid #3b82f6;
  border-radius: 0.75rem;
  padding: 1.5rem;
  margin-bottom: 2rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
}

.feedback-icon {
  flex-shrink: 0;
  width: 2.5rem;
  height: 2.5rem;
  background-color: #3b82f6;
  border-radius: 0.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

.feedback-icon svg {
  width: 1.25rem;
  height: 1.25rem;
  color: white;
  stroke-width: 2;
}

.feedback-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #111827;
  margin-bottom: 0.5rem;
}

.feedback-text {
  color: #374151;
  line-height: 1.5;
}

.table-card {
  background-color: white;
  border-radius: 1rem;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
  border: 1px solid rgba(0, 0, 0, 0.05);
  overflow: hidden;
}

.table-header {
  padding: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
  background: linear-gradient(to right, white, #f9fafb);
}

.table-title {
  font-size: 1.25rem;
  font-weight: 700;
  color: #111827;
}

.table-wrapper {
  overflow-x: auto;
}

.table {
  width: 100%;
  border-collapse: collapse;
}

.table thead {
  background: linear-gradient(to right, #f9fafb, #f3f4f6);
}

.table th {
  padding: 1.5rem;
  text-align: left;
  font-size: 0.75rem;
  font-weight: 700;
  color: #374151;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  border-bottom: 1px solid #e5e7eb;
}

.table tbody tr {
  border-bottom: 1px solid #f3f4f6;
  transition: background-color 0.15s ease;
}

.table tbody tr:hover {
  background-color: #f0f4ff;
}

.table td {
  padding: 1.5rem;
  font-size: 0.875rem;
  color: #4b5563;
}

.table td strong {
  color: #1e293b;
  font-weight: 600;
}

.table-empty {
  text-align: center;
  padding: 3rem;
  color: #6b7280;
}

.table-empty svg {
  width: 3rem;
  height: 3rem;
  margin: 0 auto 0.75rem;
  color: #d1d5db;
  stroke-width: 1.5;
}
</style>

<div class="progress-container">
  <div class="progress-header">
    <h2 class="progress-title">Progress & Training</h2>
    <p class="progress-subtitle">Track your workout progress and training sessions</p>
  </div>

  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-content">
        <div class="stat-icon">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v-1.125A3.375 3.375 0 0 0 12.375 12.75h-3.75A3.375 3.375 0 0 0 5.25 16.125V17.25M12.75 9A3 3 0 1 1 6.75 9a3 3 0 0 1 6 0M18.75 8.25l2.25 2.25-6 6-2.25.75.75-2.25 6-6z"/>
          </svg>
        </div>
        <div class="stat-label">Assigned Program</div>
        <div class="stat-value" style="font-size: 1.25rem;"><?php echo $program ? htmlspecialchars($program['session_type']) : 'N/A'; ?></div>
        <?php if ($program && !empty($program['trainer_name'])): ?>
          <div class="stat-subtext">Trainer: <span class="stat-subtext-bold"><?php echo htmlspecialchars($program['trainer_name']); ?></span></div>
        <?php endif; ?>
        <?php if ($program && !empty($program['session_date'])): ?>
          <div class="stat-subtext">Date: <span class="stat-subtext-bold"><?php echo date('M d, Y', strtotime($program['session_date'])); ?></span></div>
        <?php endif; ?>
        <?php if ($program && !empty($program['start_time'])): ?>
          <div class="stat-subtext">Time: <span class="stat-subtext-bold"><?php echo date('g:i A', strtotime($program['start_time'])); ?></span></div>
        <?php endif; ?>
      </div>
    </div>

    <div class="stat-card stat-card-green">
      <div class="stat-content">
        <div class="stat-icon">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
          </svg>
        </div>
        <div class="stat-label">Sessions</div>
        <div class="stat-value" style="font-size: 2rem;"><?php echo $completed_sessions; ?> / <?php echo $total_sessions; ?></div>
        <div class="stat-subtext">Completed / Total</div>
      </div>
    </div>

    <div class="stat-card stat-card-amber">
      <div class="stat-content">
        <div class="stat-icon">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
          </svg>
        </div>
        <div class="stat-label" style="color: #b45309;">Progress Status</div>
        <div class="stat-value"><?php echo htmlspecialchars($progress_status); ?></div>
      </div>
    </div>
  </div>

  <?php if ($trainer_feedback): ?>
    <div class="feedback-box">
      <div class="feedback-icon">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z"/>
        </svg>
      </div>
      <div style="flex: 1;">
        <h3 class="feedback-title">Trainer Feedback</h3>
        <p class="feedback-text"><?php echo htmlspecialchars($trainer_feedback); ?></p>
      </div>
    </div>
  <?php endif; ?>

  <div class="table-card">
    <div class="table-header">
      <h3 class="table-title">Progress Logs</h3>
    </div>
    <div class="table-wrapper">
      <table class="table">
        <thead>
          <tr>
            <th>Date</th>
            <th>Exercise</th>
            <th>Sets x Reps</th>
            <th>Weight/Duration</th>
            <th>Notes</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($logs)): ?>
            <tr>
              <td colspan="5" style="text-align: center; padding: 3rem;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="width: 3rem; height: 3rem; margin: 0 auto 0.75rem; color: #d1d5db; stroke-width: 1.5;">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                </svg>
                <p>No progress records found. Start logging your workouts!</p>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($logs as $row): ?>
              <tr>
                <td>
                  <span><?php echo date('M d, Y', strtotime($row['progress_date'])); ?></span>
                </td>
                <td>
                  <strong><?php echo htmlspecialchars($row['exercise_name']); ?></strong>
                </td>
                <td>
                  <?php echo ($row['sets'] && $row['reps']) ? htmlspecialchars($row['sets']) . 'x' . htmlspecialchars($row['reps']) : '-'; ?>
                </td>
                <td>
                  <?php 
                  if ($row['weight']) {
                    echo htmlspecialchars($row['weight']) . ' kg';
                  } elseif ($row['duration_minutes']) {
                    echo htmlspecialchars($row['duration_minutes']) . ' mins';
                  } else {
                    echo '-';
                  }
                  ?>
                </td>
                <td>
                  <?php echo htmlspecialchars($row['notes'] ?? '-'); ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include '../footer.php'; ?>
