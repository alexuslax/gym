<?php
session_start();
require_once '../config/functions.php';

// Use Philippines time for all displayed/parsed times
date_default_timezone_set('Asia/Manila');

// Only allow admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$page_title = 'System Logs - UEP Fitness Gym';
include '../header.php';

// Filters (optional)
$user_filter = isset($_GET['user']) ? trim($_GET['user']) : '';
$action_filter = isset($_GET['action']) ? trim($_GET['action']) : '';
$from_raw = isset($_GET['from']) ? trim($_GET['from']) : '';
$to_raw = isset($_GET['to']) ? trim($_GET['to']) : '';

// Normalize datetime-local inputs to server datetime string (YYYY-MM-DD HH:MM:SS)
$from = '';
$to = '';
if ($from_raw !== '') {
  $f = str_replace('T', ' ', $from_raw);
  if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $f)) $f .= ':00';
  $from = $f;
}
if ($to_raw !== '') {
  $t = str_replace('T', ' ', $to_raw);
  if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $t)) $t .= ':00';
  $to = $t;
}

$params = [];
$where = [];

if ($user_filter !== '') {
    $where[] = '(user_id = ? OR user_id LIKE ? OR user_id = ?)';
    $params[] = $user_filter;
    $params[] = $user_filter . '%';
    $params[] = $user_filter;
}
if ($action_filter !== '') {
    $where[] = 'action = ?';
    $params[] = $action_filter;
}
if ($from !== '') {
    $where[] = 'created_at >= ?';
    $params[] = $from;
}
if ($to !== '') {
    $where[] = 'created_at <= ?';
    $params[] = $to;
}

$where_sql = '';
if (!empty($where)) {
    $where_sql = 'WHERE ' . implode(' AND ', $where);
}

// Summary stats
$summary = ['total' => 0, 'logins' => 0, 'failed' => 0, 'errors' => 0];
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM system_logs");
    $summary['total'] = (int)$stmt->fetchColumn();
} catch (Exception $e) {}
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM system_logs WHERE action = 'LOGIN'");
    $stmt->execute();
    $summary['logins'] = (int)$stmt->fetchColumn();
} catch (Exception $e) {}
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM system_logs WHERE action = 'LOGIN_FAILED'");
    $stmt->execute();
    $summary['failed'] = (int)$stmt->fetchColumn();
} catch (Exception $e) {}
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM system_logs WHERE action = 'ERROR' OR action = 'EXCEPTION'");
    $stmt->execute();
    $summary['errors'] = (int)$stmt->fetchColumn();
} catch (Exception $e) {}

// Fetch recent logs with optional filters (limit 1000)
$logs = [];
try {
    $sql = "SELECT created_at, user_id, action, table_name, record_id, ip_address, user_agent, details FROM system_logs " . $where_sql . " ORDER BY created_at DESC LIMIT 1000";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll();
} catch (Exception $e) {}
?>

<style>
  .logs-header { margin-bottom:1rem; }
  .summary { display:flex; gap:1rem; margin-bottom:1rem; }
  .summary .card { padding:0.75rem; background:#fff; border:1px solid #e6eef8; border-radius:6px; }
  table.logs { width:100%; border-collapse:collapse; }
  table.logs th, table.logs td { padding:8px; border-top:1px solid #eef2f7; text-align:left; vertical-align:top; }
  .small { font-size:0.9rem; color:#6b7280; }
</style>

<div class="page-header logs-header">
  <h2 class="page-title">Audit Logs & Monitoring</h2>
  <p class="page-subtitle">Login history, failed attempts, user activity, and system errors (read-only).</p>
</div>

<div class="summary">
  <div class="card"><div class="small">Total entries</div><div style="font-weight:700"><?php echo htmlspecialchars($summary['total']); ?></div></div>
  <div class="card"><div class="small">Successful logins</div><div style="font-weight:700"><?php echo htmlspecialchars($summary['logins']); ?></div></div>
  <div class="card"><div class="small">Failed logins</div><div style="font-weight:700; color: #b91c1c"><?php echo htmlspecialchars($summary['failed']); ?></div></div>
  <div class="card"><div class="small">System errors</div><div style="font-weight:700; color:#b45309"><?php echo htmlspecialchars($summary['errors']); ?></div></div>
</div>

<div style="margin-bottom:1rem;">
  <form method="get" style="display:flex; gap:0.5rem; align-items:center; flex-wrap:wrap;">
    <input type="text" name="user" placeholder="User ID" value="<?php echo htmlspecialchars($user_filter); ?>">
    <select name="action">
      <option value="">All actions</option>
      <?php
        $actions = ['LOGIN','LOGIN_FAILED','CREATE','UPDATE','DELETE','ERROR','EXCEPTION','LOGOUT'];
        foreach ($actions as $a) {
          $sel = ($action_filter === $a) ? 'selected' : '';
          echo "<option value=\"" . htmlspecialchars($a) . "\" $sel>" . htmlspecialchars($a) . "</option>";
        }
      ?>
    </select>
    <?php
      // For input display convert server datetime back to datetime-local format (no seconds)
      $from_display = '';
      $to_display = '';
      if ($from) { $from_display = str_replace(' ', 'T', substr($from, 0, 16)); }
      if ($to) { $to_display = str_replace(' ', 'T', substr($to, 0, 16)); }
    ?>
    <label class="small">From <input type="datetime-local" name="from" value="<?php echo htmlspecialchars($from_display); ?>"></label>
    <label class="small">To <input type="datetime-local" name="to" value="<?php echo htmlspecialchars($to_display); ?>"></label>
    <button type="submit">Filter</button>
    <a href="logs.php" style="margin-left:0.5rem;">Reset</a>
  </form>
</div>

<div>
  <table class="logs">
    <thead>
      <tr>
        <th>Time</th>
        <th>User</th>
        <th>Action</th>
        <th>Table</th>
        <th>Record</th>
        <th>IP</th>
        <th>Details / Agent</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($logs)): ?>
        <tr><td colspan="7" class="small">No log entries found.</td></tr>
      <?php else: ?>
        <?php foreach ($logs as $row): ?>
          <tr>
            <td><?php
              $time = $row['created_at'] ?? '';
              $out = $time;
              if ($time) {
                try { $dt = new DateTime($time); $out = $dt->format('Y-m-d H:i:s'); } catch (Exception $e) { }
              }
              echo htmlspecialchars($out);
            ?></td>
            <td><?php echo htmlspecialchars($row['user_id'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($row['action']); ?></td>
            <td><?php echo htmlspecialchars($row['table_name']); ?></td>
            <td><?php echo htmlspecialchars($row['record_id']); ?></td>
            <td><?php echo htmlspecialchars($row['ip_address']); ?></td>
            <td><?php echo htmlspecialchars(substr(($row['details'] ?? '') . ' — ' . ($row['user_agent'] ?? ''), 0, 1000)); ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php include '../footer.php'; ?>
