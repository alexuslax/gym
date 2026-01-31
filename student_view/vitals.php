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

// Debug: Check if member_id is set
if (!$member_id) {
    echo '<div style="background-color: #fee2e2; border: 1px solid #fecaca; border-radius: 0.75rem; padding: 1.5rem; margin: 2rem;">
        <p style="color: #991b1b;"><strong>Debug:</strong> Member ID not found in session. Please log in again.</p>
        <p style="color: #991b1b; font-size: 0.875rem;">Session data: ' . htmlspecialchars(print_r($_SESSION, true)) . '</p>
    </div>';
}

// Latest vitals
$stmt = $pdo->prepare('SELECT * FROM vital_signs WHERE member_id = ? ORDER BY date_of_recording DESC LIMIT 1');
$stmt->execute([$member_id]);
$latest = $stmt->fetch();

// Vital history (last 12 records)
$stmt = $pdo->prepare('SELECT * FROM vital_signs WHERE member_id = ? ORDER BY date_of_recording DESC LIMIT 12');
$stmt->execute([$member_id]);
$history = $stmt->fetchAll();

// Calculate BMI
function calc_bmi($weight, $height) {
    if (!$weight || !$height) return null;
    $height_m = $height / 100;
    return round($weight / ($height_m * $height_m), 1);
}

$page_title = 'Vital Signs - UEP Fitness Gym';
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

.vitals-container {
  max-width: 1280px;
  margin: 0 auto;
  padding: 2rem 1rem;
}

.vitals-header {
  margin-bottom: 3rem;
  animation: slideIn 0.6s ease;
}

.vitals-title {
  font-size: 2.5rem;
  font-weight: 800;
  margin-bottom: 0.5rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  letter-spacing: -0.5px;
}

.vitals-subtitle {
  color: #64748b;
  font-size: 1.125rem;
  font-weight: 500;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
  margin-bottom: 3rem;
}

.stat-card {
  position: relative;
  background: white;
  border-radius: 1.25rem;
  padding: 1.75rem;
  box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.8);
  overflow: hidden;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  text-align: center;
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

.stat-card-purple {
  background: linear-gradient(135deg, #faf5ff 0%, #f3e8ff 100%);
}

.stat-card-purple:hover {
  box-shadow: 0 30px 60px -15px rgba(168, 85, 247, 0.15);
}

.stat-card-red {
  background: linear-gradient(135deg, #fef2f2 0%, #fce4ec 100%);
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

.stat-card-purple::before {
  background: #a855f7;
}

.stat-card-red::before {
  background: #ef4444;
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
  width: 3.25rem;
  height: 3.25rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 1rem;
  margin-bottom: 0.75rem;
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

.stat-card-purple .stat-icon {
  background: linear-gradient(135deg, #a855f7 0%, #9333ea 100%);
  box-shadow: 0 8px 16px -4px rgba(168, 85, 247, 0.3);
}

.stat-card-red .stat-icon {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
  box-shadow: 0 8px 16px -4px rgba(239, 68, 68, 0.3);
}

.stat-icon svg {
  width: 1.5rem;
  height: 1.5rem;
  color: white;
  stroke-width: 2;
}

.stat-label {
  font-size: 0.8rem;
  color: #94a3b8;
  font-weight: 700;
  margin-bottom: 0.5rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.stat-value {
  font-size: 1.75rem;
  font-weight: 800;
  color: #0f172a;
  margin-bottom: 0.25rem;
  line-height: 1.2;
}

.stat-subtext {
  font-size: 0.75rem;
  color: #64748b;
  font-weight: 600;
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
  .vitals-title {
    font-size: 2rem;
  }
  
  .stat-card {
    padding: 1.25rem;
  }
  
  .table th,
  .table td {
    padding: 1rem;
  }
  
  .stats-grid {
    gap: 1.25rem;
  }
}
.stat-card-blue {
	background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
}

.stat-card-blue:hover {
	box-shadow: 0 30px 60px -15px rgba(59, 130, 246, 0.15);
}
.stat-card-green .stat-icon {
  background-color: #22c55e;
}

.stat-card-amber .stat-icon {
  background-color: #f59e0b;
}

.stat-card-purple .stat-icon {
  background-color: #a855f7;
}

.stat-card-red .stat-icon {
  background-color: #ef4444;
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
  font-size: 0.75rem;
  color: #4b5563;
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
</style>

<div class="vitals-container">
  <div class="vitals-header">
    <h2 class="vitals-title">Vital Signs</h2>
    <p class="vitals-subtitle">Track your health metrics and fitness progress</p>
  </div>

  <?php if ($latest): ?>
    <div class="stats-grid">
      <div class="stat-card stat-card-blue">
        <div class="stat-content">
          <div class="stat-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v17.25m0 0c-1.472 0-2.882.265-4.185.75M12 20.25c1.472 0 2.882.265 4.185.75M18.75 4.97A48.224 48.224 0 0 0 12 4.5c-2.291 0-4.545.16-6.75.47m13.5 0c1.01.143 2.01.317 3 .52m-3-.52 2.62 10.726c.122.499-.106 1.028-.589 1.202a5.988 5.988 0 0 1-2.031.352 5.988 5.988 0 0 1-2.031-.352c-.483-.174-.711-.703-.589-1.202L18.75 4.971Zm-16.5.52c.99-.203 1.99-.377 3-.52m0 0 2.62 10.726c.122.499-.106 1.028-.589 1.202a5.989 5.989 0 0 1-2.031.352 5.989 5.989 0 0 1-2.031-.352c-.483-.174-.711-.703-.589-1.202L5.25 4.971Z"/>
            </svg>
          </div>
          <div class="stat-label">Weight</div>
          <div class="stat-value"><?php echo htmlspecialchars($latest['weight'] ?? '-'); ?> kg</div>
          <?php if (isset($latest['date_of_recording'])): ?>
            <div class="stat-subtext"><?php echo date('M d, Y', strtotime($latest['date_of_recording'])); ?></div>
          <?php endif; ?>
        </div>
      </div>

      <div class="stat-card stat-card-green">
        <div class="stat-content">
          <div class="stat-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0 4.556-4.5 7.5-9 11.25-4.5-3.75-9-6.694-9-11.25a5.25 5.25 0 0 1 9-3.656A5.25 5.25 0 0 1 21 8.25z"/>
            </svg>
          </div>
          <div class="stat-label">Blood Pressure</div>
          <div class="stat-value">
            <?php 
            if ($latest['blood_pressure_systolic'] && $latest['blood_pressure_diastolic']) {
              echo htmlspecialchars($latest['blood_pressure_systolic']) . '/' . htmlspecialchars($latest['blood_pressure_diastolic']);
            } else {
              echo '-';
            }
            ?>
          </div>
          <div class="stat-subtext">mmHg</div>
        </div>
      </div>

      <div class="stat-card stat-card-amber">
        <div class="stat-content">
          <div class="stat-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 9.563C9 9.252 9.252 9 9.563 9h4.874c.311 0 .563.252.563.563v4.874c0 .311-.252.563-.563.563H9.564A.562.562 0 0 1 9 14.437V9.564z"/>
            </svg>
          </div>
          <div class="stat-label">Heart Rate</div>
          <div class="stat-value"><?php echo $latest['heart_rate'] ? htmlspecialchars($latest['heart_rate']) : '-'; ?> bpm</div>
        </div>
      </div>

      <div class="stat-card stat-card-purple">
        <div class="stat-content">
          <div class="stat-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 4.5h14.25M3 9h9.75m-9.75 4.5h9.75m-9.75 4.5h14.25"/>
            </svg>
          </div>
          <div class="stat-label">Height</div>
          <div class="stat-value"><?php echo $latest['height_cm'] ? htmlspecialchars($latest['height_cm']) : '-'; ?> cm</div>
        </div>
      </div>

      <div class="stat-card stat-card-red">
        <div class="stat-content">
          <div class="stat-icon">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75Zm6.75-4.5c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625Zm6.75-4.5c0-.621.504-1.125 1.125-1.125h2.25C20.496 4 21 4.504 21 5.125v13.5c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/>
            </svg>
          </div>
          <div class="stat-label">BMI</div>
          <div class="stat-value">
            <?php 
            if ($latest && $latest['weight'] && $latest['height_cm']) {
              $bmi = calc_bmi($latest['weight'], $latest['height_cm']);
              echo $bmi;
            } else {
              echo '-';
            }
            ?>
          </div>
          <div class="stat-subtext">
            <?php
            if ($latest && $latest['weight'] && $latest['height_cm']) {
              $bmi = calc_bmi($latest['weight'], $latest['height_cm']);
              if ($bmi < 18.5) echo 'Underweight';
              elseif ($bmi < 25) echo 'Normal';
              elseif ($bmi < 30) echo 'Overweight';
              else echo 'Obese';
            }
            ?>
          </div>
        </div>
      </div>
    </div>
  <?php else: ?>
    <div style="background-color: #fef3c7; border: 1px solid #fcd34d; border-radius: 0.75rem; padding: 1.5rem; margin-bottom: 2rem;">
      <p style="color: #92400e;">No vital signs recorded yet. Please visit the front desk to have your vitals measured.</p>
    </div>
  <?php endif; ?>

  <!-- Vital History Table -->
  <div class="table-card">
    <div class="table-header">
      <h3 class="table-title">Vital History</h3>
    </div>
    <div class="table-wrapper">
      <table class="table">
        <thead>
          <tr>
            <th>Date</th>
            <th>Weight</th>
            <th>Height</th>
            <th>BP (mmHg)</th>
            <th>Heart Rate</th>
            <th>BMI</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($history)): ?>
            <tr>
              <td colspan="6" class="table-empty">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="width: 3rem; height: 3rem; margin: 0 auto 0.75rem; color: #d1d5db; stroke-width: 1.5;">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                </svg>
                <p>No vital signs history available</p>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($history as $row): ?>
              <tr>
                <td>
                  <strong><?php echo date('M d, Y', strtotime($row['date_of_recording'])); ?></strong>
                </td>
                <td>
                  <?php echo $row['weight'] ? htmlspecialchars($row['weight']) . ' kg' : '-'; ?>
                </td>
                <td>
                  <?php echo $row['height_cm'] ? htmlspecialchars($row['height_cm']) . ' cm' : '-'; ?>
                </td>
                <td>
                  <?php echo ($row['blood_pressure_systolic'] && $row['blood_pressure_diastolic']) ? htmlspecialchars($row['blood_pressure_systolic']) . '/' . htmlspecialchars($row['blood_pressure_diastolic']) : '-'; ?>
                </td>
                <td>
                  <?php echo $row['heart_rate'] ? htmlspecialchars($row['heart_rate']) . ' bpm' : '-'; ?>
                </td>
                <td>
                  <strong><?php echo ($row['weight'] && $row['height_cm']) ? calc_bmi($row['weight'], $row['height_cm']) : '-'; ?></strong>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Trends Placeholder -->
  <div style="margin-top: 2rem; background-color: white; border-radius: 1rem; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); border: 1px solid rgba(0, 0, 0, 0.05); overflow: hidden;">
    <div style="padding: 1.5rem; border-bottom: 1px solid #e5e7eb; background: linear-gradient(to right, white, #f9fafb);">
      <h3 style="font-size: 1.25rem; font-weight: 700; color: #111827;">Trends</h3>
    </div>
    <div style="padding: 3rem;">
      <div style="height: 16rem; display: flex; align-items: center; justify-content: center; background: linear-gradient(to bottom right, #f9fafb, #f3f4f6); border-radius: 0.75rem; border: 2px dashed #d1d5db; color: #9ca3af; text-align: center;">
        <div>
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="width: 4rem; height: 4rem; margin: 0 auto 0.75rem; color: currentColor; stroke-width: 1.5;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75Zm6.75-4.5c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625Zm6.75-4.5c0-.621.504-1.125 1.125-1.125h2.25C20.496 4 21 4.504 21 5.125v13.5c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/>
          </svg>
          <p style="font-size: 1.125rem; font-weight: 500;">Health trends chart coming soon</p>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include '../footer.php'; ?>
