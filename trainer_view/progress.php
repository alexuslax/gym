<?php
require_once '../config/functions.php';

// Authentication
if (session_status() === PHP_SESSION_NONE) session_start();
$role = $_SESSION['role'] ?? '';
if (!isset($_SESSION['user_id']) || !in_array($role, ['staff', 'trainer'], true)) {
    header('Location: ../index.php');
    exit();
}
$is_staff = $role === 'staff';
$username = $_SESSION['username'] ?? '';
$trainer_id = null;

if (!$is_staff) {
    // Resolve trainer_id from username
    $stmt = $pdo->prepare('SELECT trainer_id FROM trainers WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $trainer = $stmt->fetch(PDO::FETCH_ASSOC);
    $trainer_id = $trainer['trainer_id'] ?? null;

    if (!$trainer_id) {
        echo '<div class="error-message">Trainer session not found.</div>';
        exit();
    }
}

$page_title = 'Progress Tracking';
$message = $_GET['success'] ?? '';
$error = '';
$member_filter = sanitizeInput($_GET['member_id'] ?? '');

// Build member dropdowns
if ($is_staff) {
    $stmt = $pdo->query("SELECT member_id, first_name, middle_name, last_name FROM members WHERE membership_status = 'Active' ORDER BY first_name, last_name");
    $members_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $pdo->prepare("SELECT DISTINCT m.member_id, m.first_name, m.middle_name, m.last_name
        FROM trainer_assignments ta
        JOIN members m ON ta.member_id COLLATE utf8mb4_unicode_ci = m.member_id COLLATE utf8mb4_unicode_ci
        WHERE ta.trainer_id = ?
        ORDER BY m.first_name, m.last_name");
    $stmt->execute([$trainer_id]);
    $members_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle progress submission (trainers only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_progress' && !$is_staff) {
    $member_id = sanitizeInput($_POST['member_id'] ?? '');
    $progress_date = $_POST['progress_date'] ?? date('Y-m-d');
    $exercise_name = sanitizeInput($_POST['exercise_name'] ?? '');
    $sets = $_POST['sets'] !== '' ? (int)$_POST['sets'] : null;
    $reps = $_POST['reps'] !== '' ? (int)$_POST['reps'] : null;
    $weight = $_POST['weight'] !== '' ? (float)$_POST['weight'] : null;
    $duration_minutes = $_POST['duration_minutes'] !== '' ? (int)$_POST['duration_minutes'] : null;
    $notes = trim($_POST['notes'] ?? '');

    $assigned_ids = array_column($members_list, 'member_id');

    if (!in_array($member_id, $assigned_ids, true)) {
        $error = 'You are not assigned to this member.';
    } elseif (empty($member_id) || empty($progress_date) || empty($exercise_name)) {
        $error = 'Member, date, and exercise name are required.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO progress (member_id, progress_date, exercise_name, sets, reps, weight, duration_minutes, notes, trainer_id, recorded_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$member_id, $progress_date, $exercise_name, $sets, $reps, $weight, $duration_minutes, $notes, $trainer_id, $trainer_id]);
        header('Location: progress.php?success=' . urlencode('Progress recorded successfully') . ($member_filter ? '&member_id=' . urlencode($member_filter) : ''));
        exit();
    }
}

// Fetch progress records (latest first)
$where_conditions = [];
$params = [];
$sql = "SELECT p.*, m.first_name, m.middle_name, m.last_name, m.member_id
        FROM progress p
        JOIN members m ON p.member_id COLLATE utf8mb4_unicode_ci = m.member_id COLLATE utf8mb4_unicode_ci";

if (!$is_staff) {
    $sql .= "\n        JOIN trainer_assignments ta ON ta.member_id COLLATE utf8mb4_unicode_ci = p.member_id COLLATE utf8mb4_unicode_ci";
    $where_conditions[] = 'ta.trainer_id = ?';
    $params[] = $trainer_id;
}

if (!empty($member_filter)) {
    $where_conditions[] = 'p.member_id = ?';
    $params[] = $member_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
$sql .= "\n        $where_clause\n        ORDER BY p.member_id, p.progress_date DESC, p.progress_id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$progress_records = $stmt->fetchAll(PDO::FETCH_ASSOC);

$grouped_progress = [];
foreach ($progress_records as $record) {
    $member_id = $record['member_id'];
    $member_name = trim(($record['first_name'] ?? '') . ' ' . (($record['middle_name'] ?? '') ? $record['middle_name'] . ' ' : '') . ($record['last_name'] ?? ''));

    if (!isset($grouped_progress[$member_id])) {
        $grouped_progress[$member_id] = [
            'member_id' => $member_id,
            'member_name' => $member_name,
            'sessions' => [],
        ];
    }

    $grouped_progress[$member_id]['sessions'][] = [
        'progress_id' => $record['progress_id'] ?? null,
        'progress_date' => $record['progress_date'] ?? '',
        'exercise_name' => $record['exercise_name'] ?? '',
        'sets' => $record['sets'],
        'reps' => $record['reps'],
        'weight' => $record['weight'],
        'duration_minutes' => $record['duration_minutes'],
        'notes' => $record['notes'] ?? '',
    ];
}

$grouped_progress = array_values($grouped_progress);

include '../header.php';
?>
<style>
.progress-page .filter-bar {display: flex; gap: 0.75rem; align-items: center; margin-bottom: 1rem; flex-wrap: wrap;}
.progress-page .filter-bar select {padding: 0.65rem 0.75rem; border: 1px solid #cbd5e1; border-radius: 0.5rem; font-size: 0.9rem;}
.progress-page .filter-bar button {padding: 0.65rem 1rem; background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); color: white; border: none; border-radius: 0.5rem; font-weight: 700; cursor: pointer;}
.progress-page .progress-grid {display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.25rem;}
.progress-page .progress-card {background: white; border: 1px solid #e2e8f0; border-radius: 0.75rem; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);}
.progress-page .primary-btn {padding: 0.8rem 1.35rem; border-radius: 0.65rem; border: none; color: white; font-weight: 700; font-size: 0.95rem; cursor: pointer; background: linear-gradient(135deg, #7c3aed 0%, #5b21b6 100%); box-shadow: 0 10px 20px rgba(91, 33, 182, 0.25); transition: transform 0.15s ease, box-shadow 0.15s ease;}
.progress-page .primary-btn:hover {transform: translateY(-1px); box-shadow: 0 12px 24px rgba(91, 33, 182, 0.28);}
.progress-page .primary-btn:active {transform: translateY(0); box-shadow: 0 8px 18px rgba(91, 33, 182, 0.22);}
.modal-overlay {position: fixed; inset: 0; background: rgba(15, 23, 42, 0.45); display: none; align-items: center; justify-content: center; z-index: 1000; padding: 1rem;}
.modal-overlay.show {display: flex;}
.progress-page .empty-state-container {padding: 2rem; border: 1px dashed #e2e8f0; border-radius: 0.75rem; text-align: center; background: #f8fafc;}
.progress-page .empty-state-title {font-size: 1rem; font-weight: 700; color: #0f172a; margin: 0.5rem 0 0.25rem 0;}
.progress-page .empty-state-subtitle {color: #475569; margin: 0; font-size: 0.95rem;}

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
<div class="dashboard-container progress-page">
	<div class="page-header">
		<h1 class="page-title">Progress Tracking</h1>
		<p class="page-subtitle">Monitor and record member progress over time</p>
	</div>

	<div style="margin-bottom: 2rem;">
        <?php if (!$is_staff): ?>
		<button id="openProgressModal" class="primary-btn">Record Progress</button>
		<?php endif; ?>
	</div>

<?php if (!empty($message)): ?>
<div class="alert success" id="alertMessage"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
<div class="alert error" id="alertMessage"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<form class="filter-bar" method="GET">
<label for="member_id" style="font-weight: 700; color: #334155;">Member:</label>
<select name="member_id" id="member_id">
<option value="">All Members</option>
<?php foreach ($members_list as $member): ?>
<?php $opt_id = $member['member_id']; ?>
<option value="<?php echo htmlspecialchars($opt_id); ?>" <?php echo ($member_filter === $opt_id) ? 'selected' : ''; ?>>
<?php echo htmlspecialchars(trim($member['first_name'] . ' ' . ($member['middle_name'] ? $member['middle_name'] . ' ' : '') . $member['last_name']) . ' (' . $opt_id . ')'); ?>
</option>
<?php endforeach; ?>
</select>
<button type="submit">Filter</button>
</form>

<?php if (!empty($grouped_progress)): ?>
<div class="progress-grid">
<?php foreach ($grouped_progress as $member_data): ?>
<?php $latest_session = $member_data['sessions'][0] ?? null; ?>
<?php $history_sessions = array_slice($member_data['sessions'], 1); ?>
<?php if ($latest_session): ?>
<div 
class="card progress-card" 
data-member-id="<?php echo htmlspecialchars($member_data['member_id']); ?>"
data-member-name="<?php echo htmlspecialchars($member_data['member_name']); ?>"
data-history="<?php echo htmlspecialchars(json_encode($history_sessions), ENT_QUOTES, 'UTF-8'); ?>"
style="margin-bottom: 0; cursor: pointer;">
<div style="padding: 1.5rem; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border-bottom: 1px solid #e2e8f0; border-radius: 0.75rem 0.75rem 0 0;">
<div style="display: flex; align-items: center; gap: 1rem;">
<div style="width: 3rem; height: 3rem; border-radius: 0.75rem; background: linear-gradient(135deg, #8b5cf6, #7c3aed); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 1.25rem; box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);">
<?php echo strtoupper(substr($member_data['member_name'], 0, 1)); ?>
</div>
<div>
<h2 style="margin: 0; font-size: 1.25rem; font-weight: 700; color: #1e293b;">
<?php echo htmlspecialchars($member_data['member_name']); ?>
</h2>
<p style="margin: 0.25rem 0 0 0; font-size: 0.875rem; color: #64748b;">ID: <strong><?php echo htmlspecialchars($member_data['member_id']); ?></strong></p>
</div>
</div>
</div>

<div style="padding: 1.5rem;">
<div style="margin-bottom: 1.25rem; padding-bottom: 1.25rem; border-bottom: 1px solid #e2e8f0;">
<div style="margin-bottom: 1rem; padding: 0.75rem; background: #eef2ff; border-radius: 0.5rem; border-left: 3px solid #8b5cf6; display: flex; justify-content: space-between; align-items: center;">
<div>
<p style="margin: 0; font-size: 0.75rem; color: #6d28d9; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700;">Latest Progress</p>
<p style="margin: 0.35rem 0 0 0; font-size: 1.125rem; font-weight: 700; color: #1e293b;">
<?php echo formatDate($latest_session['progress_date']); ?>
</p>
</div>
<span style="font-size: 0.8rem; color: #6d28d9; font-weight: 700;">Tap to view history</span>
</div>

<div style="margin-bottom: 1rem;">
<h3 style="margin: 0 0 0.75rem 0; font-size: 1rem; font-weight: 700; color: #1e293b;">
<?php echo htmlspecialchars($latest_session['exercise_name']); ?>
</h3>
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(90px, 1fr)); gap: 0.75rem;">
<?php if (!empty($latest_session['sets'])): ?>
<div style="padding: 0.75rem; background: linear-gradient(135deg, #f3e8ff 0%, #f0e4ff 100%); border: 1px solid #e9d5ff; border-radius: 0.5rem;">
<p style="margin: 0; font-size: 0.7rem; color: #6d28d9; font-weight: 600; text-transform: uppercase;">Sets</p>
<p style="margin: 0.5rem 0 0 0; font-size: 1.125rem; font-weight: 700; color: #5b21b6;">
<?php echo htmlspecialchars($latest_session['sets']); ?>
</p>
</div>
<?php endif; ?>
<?php if (!empty($latest_session['reps'])): ?>
<div style="padding: 0.75rem; background: linear-gradient(135deg, #f3e8ff 0%, #f0e4ff 100%); border: 1px solid #e9d5ff; border-radius: 0.5rem;">
<p style="margin: 0; font-size: 0.7rem; color: #6d28d9; font-weight: 600; text-transform: uppercase;">Reps</p>
<p style="margin: 0.5rem 0 0 0; font-size: 1.125rem; font-weight: 700; color: #5b21b6;">
<?php echo htmlspecialchars($latest_session['reps']); ?>
</p>
</div>
<?php endif; ?>
<?php if (!empty($latest_session['weight'])): ?>
<div style="padding: 0.75rem; background: linear-gradient(135deg, #f3e8ff 0%, #f0e4ff 100%); border: 1px solid #e9d5ff; border-radius: 0.5rem;">
<p style="margin: 0; font-size: 0.7rem; color: #6d28d9; font-weight: 600; text-transform: uppercase;">Weight</p>
<p style="margin: 0.5rem 0 0 0; font-size: 1.125rem; font-weight: 700; color: #5b21b6;">
<?php echo htmlspecialchars($latest_session['weight']); ?> <span style="font-size: 0.8rem; font-weight: 500;">kg</span>
</p>
</div>
<?php endif; ?>
<?php if (!empty($latest_session['duration_minutes'])): ?>
<div style="padding: 0.75rem; background: linear-gradient(135deg, #f3e8ff 0%, #f0e4ff 100%); border: 1px solid #e9d5ff; border-radius: 0.5rem;">
<p style="margin: 0; font-size: 0.7rem; color: #6d28d9; font-weight: 600; text-transform: uppercase;">Duration</p>
<p style="margin: 0.5rem 0 0 0; font-size: 1.125rem; font-weight: 700; color: #5b21b6;">
<?php echo htmlspecialchars($latest_session['duration_minutes']); ?> <span style="font-size: 0.8rem; font-weight: 500;">min</span>
</p>
</div>
<?php endif; ?>
</div>
</div>

<?php if (!empty($latest_session['notes'])): ?>
<div style="padding: 0.85rem; background: #fff7ed; border-left: 4px solid #f97316; border-radius: 0.4rem;">
<p style="margin: 0; font-size: 0.72rem; font-weight: 700; color: #9a3412; text-transform: uppercase; letter-spacing: 0.5px;">Trainer Notes</p>
<p style="margin: 0.35rem 0 0 0; font-size: 0.85rem; color: #7c2d12; line-height: 1.5;">
<?php echo nl2br(htmlspecialchars($latest_session['notes'])); ?>
</p>
</div>
<?php endif; ?>
</div>
</div>
<?php endif; ?>
</div>
<?php endforeach; ?>
</div>
<?php else: ?>
<div class="empty-state-container">
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="empty-state-icon" style="width: 48px; height: 48px; color: #cbd5e1;">
<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/>
</svg>
<p class="empty-state-title">No progress records found</p>
<p class="empty-state-subtitle"><?php echo !empty($member_filter) ? 'This member has no progress records yet.' : ($is_staff ? 'No progress records available.' : 'No progress records available for your assigned members.'); ?></p>
</div>
<?php endif; ?>
</div>

<?php if (!$is_staff): ?>
<div id="progressModal" class="modal-overlay">
<div class="modal-content" onclick="event.stopPropagation()" style="max-width: 42rem; background: white; border-radius: 0.75rem; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
<div class="modal-header" style="padding: 1.5rem; border-bottom: 1px solid #e2e8f0; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border-radius: 0.75rem 0.75rem 0 0; display: flex; justify-content: space-between; align-items: center;">
<h3 style="margin: 0; font-size: 1.25rem; font-weight: 700; color: #1e293b;">Record Progress</h3>
<button type="button" onclick="document.getElementById('progressModal').classList.remove('show')" style="background: none; border: none; color: #64748b; cursor: pointer; padding: 0.5rem; display: flex; align-items: center; justify-content: center; border-radius: 0.5rem; transition: all 0.2s ease;">
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1.5rem; height: 1.5rem;">
<path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
</svg>
</button>
</div>
<form method="POST" style="padding: 1.5rem;">
<input type="hidden" name="action" value="add_progress">
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
<div>
<label style="display: block; margin-bottom: 0.5rem; font-size: 0.875rem; font-weight: 700; color: #1e293b;">Member <span style="color: #ef4444;">*</span></label>
<select name="member_id" required style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 0.5rem; font-size: 0.875rem; color: #1e293b; background-color: #f8fafc;">
<option value="">Select Member</option>
<?php foreach ($members_list as $member): ?>
<option value="<?php echo $member['member_id']; ?>">
<?php echo htmlspecialchars(trim($member['first_name'] . ' ' . ($member['middle_name'] ? $member['middle_name'] . ' ' : '') . $member['last_name']) . ' (' . $member['member_id'] . ')'); ?>
</option>
<?php endforeach; ?>
</select>
</div>
<div>
<label style="display: block; margin-bottom: 0.5rem; font-size: 0.875rem; font-weight: 700; color: #1e293b;">Session Date <span style="color: #ef4444;">*</span></label>
<input type="date" name="progress_date" value="<?php echo date('Y-m-d'); ?>" required style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 0.5rem; font-size: 0.875rem; color: #1e293b; background-color: #f8fafc;">
</div>
<div style="grid-column: 1 / -1;">
<label style="display: block; margin-bottom: 0.5rem; font-size: 0.875rem; font-weight: 700; color: #1e293b;">Exercise Name <span style="color: #ef4444;">*</span></label>
<input type="text" name="exercise_name" required style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 0.5rem; font-size: 0.875rem; color: #1e293b; background-color: #f8fafc;" placeholder="e.g., Bench Press, Squats, Treadmill">
</div>
<div>
<label style="display: block; margin-bottom: 0.5rem; font-size: 0.875rem; font-weight: 700; color: #1e293b;">Sets</label>
<input type="number" name="sets" min="0" style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 0.5rem; font-size: 0.875rem; color: #1e293b; background-color: #f8fafc;">
</div>
<div>
<label style="display: block; margin-bottom: 0.5rem; font-size: 0.875rem; font-weight: 700; color: #1e293b;">Reps</label>
<input type="number" name="reps" min="0" style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 0.5rem; font-size: 0.875rem; color: #1e293b; background-color: #f8fafc;">
</div>
<div>
<label style="display: block; margin-bottom: 0.5rem; font-size: 0.875rem; font-weight: 700; color: #1e293b;">Weight / Resistance (kg)</label>
<input type="number" name="weight" step="0.1" min="0" style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 0.5rem; font-size: 0.875rem; color: #1e293b; background-color: #f8fafc;" placeholder="Optional for cardio">
</div>
<div>
<label style="display: block; margin-bottom: 0.5rem; font-size: 0.875rem; font-weight: 700; color: #1e293b;">Duration (minutes)</label>
<input type="number" name="duration_minutes" min="0" style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 0.5rem; font-size: 0.875rem; color: #1e293b; background-color: #f8fafc;" placeholder="For cardio or circuit training">
</div>
</div>
<div style="margin-bottom: 1.5rem;">
<label style="display: block; margin-bottom: 0.5rem; font-size: 0.875rem; font-weight: 700; color: #1e293b;">Trainer Notes</label>
<textarea name="notes" rows="4" style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 0.5rem; font-size: 0.875rem; color: #1e293b; background-color: #f8fafc; font-family: inherit; resize: vertical;" placeholder="Performance feedback, form correction, difficulty level..."></textarea>
</div>
<div style="display: flex; gap: 1rem; justify-content: flex-end;">
<button type="button" onclick="document.getElementById('progressModal').classList.remove('show')" style="padding: 0.75rem 1.5rem; background-color: #e2e8f0; color: #1e293b; border: none; border-radius: 0.5rem; font-weight: 600; cursor: pointer;">Cancel</button>
<button type="submit" style="padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); color: white; border: none; border-radius: 0.5rem; font-weight: 600; cursor: pointer; box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);">Record Progress</button>
</div>
</form>
</div>
</div>
<?php endif; ?>

<!-- History Modal (for viewing full history on card click) -->
<div id="historyModal" class="modal-overlay" style="display: none;">
<div class="modal-content" onclick="event.stopPropagation()" style="max-width: 50rem; background: white; border-radius: 0.75rem; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
<div class="modal-header" style="padding: 1.25rem 1.5rem; border-bottom: 1px solid #e2e8f0; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border-radius: 0.75rem 0.75rem 0 0; display: flex; justify-content: space-between; align-items: center;">
<div>
<p style="margin: 0; font-size: 0.8rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700;">Progress History</p>
<h3 id="historyMember" style="margin: 0.25rem 0 0 0; font-size: 1.2rem; font-weight: 700; color: #1e293b;"></h3>
</div>
<button type="button" id="historyClose" style="background: none; border: none; color: #64748b; cursor: pointer; padding: 0.5rem; display: flex; align-items: center; justify-content: center; border-radius: 0.5rem; transition: all 0.2s ease;">
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1.25rem; height: 1.25rem;">
<path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
</svg>
</button>
</div>
<div id="historyContent" style="padding: 1.25rem 1.5rem 1.5rem 1.5rem; max-height: 70vh; overflow-y: auto;"></div>
</div>
</div>

<script>
const alertMessage = document.getElementById('alertMessage');
if (alertMessage) {
setTimeout(function() {
alertMessage.style.transition = 'opacity 0.5s ease-out';
alertMessage.style.opacity = '0';
setTimeout(function() {
alertMessage.style.display = 'none';
}, 500);
}, 5000);
}

document.getElementById('progressModal')?.addEventListener('click', function(e) {
if (e.target === this) {
this.classList.remove('show');
}
});

const openProgress = document.getElementById('openProgressModal');
openProgress?.addEventListener('click', function() {
document.getElementById('progressModal').classList.add('show');
});

const historyModal = document.getElementById('historyModal');
const historyContent = document.getElementById('historyContent');
const historyMember = document.getElementById('historyMember');
const closeHistory = document.getElementById('historyClose');

const formatDate = function(dateStr) {
const date = new Date(dateStr);
if (isNaN(date)) return dateStr;
return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
};

const renderHistory = function(card) {
const memberName = card.getAttribute('data-member-name') || 'Member';
const memberId = card.getAttribute('data-member-id') || '';
const historyJson = card.getAttribute('data-history') || '[]';
let history = [];
try {
history = JSON.parse(historyJson);
} catch (e) {
history = [];
}

historyMember.textContent = memberId ? `${memberName} (${memberId})` : memberName;
historyContent.innerHTML = '';

if (!history.length) {
const empty = document.createElement('p');
empty.textContent = 'No additional history for this member yet.';
empty.style.margin = '0';
empty.style.color = '#475569';
empty.style.fontSize = '0.95rem';
historyContent.appendChild(empty);
} else {
history.forEach(function(session) {
const cardEl = document.createElement('div');
cardEl.style.background = 'white';
cardEl.style.border = '1px solid #e2e8f0';
cardEl.style.borderRadius = '0.75rem';
cardEl.style.padding = '1rem';
cardEl.style.marginBottom = '0.9rem';

const header = document.createElement('div');
header.style.display = 'flex';
header.style.justifyContent = 'space-between';
header.style.alignItems = 'center';
header.style.marginBottom = '0.75rem';

const dateWrap = document.createElement('div');
const dateLabel = document.createElement('p');
dateLabel.textContent = 'Progress Date';
dateLabel.style.margin = '0';
dateLabel.style.fontSize = '0.75rem';
dateLabel.style.color = '#64748b';
dateLabel.style.textTransform = 'uppercase';
dateLabel.style.letterSpacing = '0.5px';
dateLabel.style.fontWeight = '700';

const dateValue = document.createElement('p');
dateValue.textContent = formatDate(session.progress_date);
dateValue.style.margin = '0.35rem 0 0 0';
dateValue.style.fontSize = '1rem';
dateValue.style.fontWeight = '700';
dateValue.style.color = '#1e293b';

dateWrap.appendChild(dateLabel);
dateWrap.appendChild(dateValue);

const badge = document.createElement('p');
badge.textContent = 'History';
badge.style.margin = '0';
badge.style.fontSize = '0.8rem';
badge.style.color = '#6d28d9';
badge.style.fontWeight = '700';

header.appendChild(dateWrap);
header.appendChild(badge);
cardEl.appendChild(header);

const title = document.createElement('h4');
title.textContent = session.exercise_name || 'Session';
title.style.margin = '0 0 0.5rem 0';
title.style.fontSize = '0.95rem';
title.style.fontWeight = '700';
title.style.color = '#1e293b';
cardEl.appendChild(title);

const grid = document.createElement('div');
grid.style.display = 'grid';
grid.style.gridTemplateColumns = 'repeat(auto-fit, minmax(80px, 1fr))';
grid.style.gap = '0.65rem';

const addStat = function(label, value, suffix) {
if (value === null || value === undefined || value === '') return;
const stat = document.createElement('div');
stat.style.padding = '0.6rem';
stat.style.background = '#f8fafc';
stat.style.border = '1px solid #e2e8f0';
stat.style.borderRadius = '0.4rem';

const lbl = document.createElement('p');
lbl.textContent = label;
lbl.style.margin = '0';
lbl.style.fontSize = '0.7rem';
lbl.style.color = '#475569';
lbl.style.fontWeight = '600';
lbl.style.textTransform = 'uppercase';

const val = document.createElement('p');
val.textContent = suffix ? `${value} ${suffix}` : value;
val.style.margin = '0.35rem 0 0 0';
val.style.fontSize = '1rem';
val.style.fontWeight = '700';
val.style.color = '#1e293b';

stat.appendChild(lbl);
stat.appendChild(val);
grid.appendChild(stat);
};

addStat('Sets', session.sets);
addStat('Reps', session.reps);
addStat('Weight', session.weight, 'kg');
addStat('Duration', session.duration_minutes, 'min');

if (grid.childElementCount > 0) {
cardEl.appendChild(grid);
}

if (session.notes) {
const notes = document.createElement('div');
notes.style.padding = '0.85rem';
notes.style.background = '#fff7ed';
notes.style.borderLeft = '4px solid #f97316';
notes.style.borderRadius = '0.4rem';
notes.style.marginTop = '0.75rem';

const notesLabel = document.createElement('p');
notesLabel.textContent = 'Trainer Notes';
notesLabel.style.margin = '0';
notesLabel.style.fontSize = '0.72rem';
notesLabel.style.fontWeight = '700';
notesLabel.style.color = '#9a3412';
notesLabel.style.textTransform = 'uppercase';
notesLabel.style.letterSpacing = '0.5px';

const notesText = document.createElement('p');
notesText.textContent = session.notes;
notesText.style.margin = '0.35rem 0 0 0';
notesText.style.fontSize = '0.85rem';
notesText.style.color = '#7c2d12';
notesText.style.lineHeight = '1.5';

notes.appendChild(notesLabel);
notes.appendChild(notesText);
cardEl.appendChild(notes);
}

historyContent.appendChild(cardEl);
});
}

historyModal.style.display = 'flex';
historyModal.classList.add('show');
};

const closeHistoryModal = function() {
historyModal.classList.remove('show');
setTimeout(function() {
historyModal.style.display = 'none';
}, 150);
};

closeHistory?.addEventListener('click', function() {
closeHistoryModal();
});

historyModal?.addEventListener('click', function(e) {
if (e.target === this) {
closeHistoryModal();
}
});

document.querySelectorAll('.progress-card').forEach(function(card) {
card.addEventListener('click', function(event) {
if (event.target.closest('button, a, select, input, textarea')) return;
renderHistory(card);
});
});
</script>

<?php include '../footer.php'; ?>
