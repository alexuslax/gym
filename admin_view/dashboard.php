<?php
session_start();
require_once '../config/functions.php';
$timezone = 'Asia/Manila';
date_default_timezone_set($timezone);
$page_title = 'System Dashboard - UEP Fitness Gym';
include '../header.php';

// Determine system status (DB reachable)
$system_status = 'Offline';
try {
    $stmt = $pdo->query("SELECT 1");
    $system_status = 'Online';
} catch (Exception $e) {
    $system_status = 'Offline';
}
// Collect monitored data
$counts = ['total_users' => 'N/A', 'active_sessions_today' => 'N/A', 'attendance_today' => 'N/A', 'billing_today' => 'N/A'];
$security = ['logins_today' => 'N/A', 'failed_today' => 'N/A', 'suspicious' => 'N/A'];
$recent_critical = [];
$health = ['storage_percent' => 'N/A', 'db_size_mb' => 'N/A', 'last_backup' => 'N/A'];

// Time range helper
$today_start = date('Y-m-d') . " 00:00:00";
$today_end = date('Y-m-d') . " 23:59:59";

try {
  // Total registered users
  $stmt = $pdo->query("SELECT COUNT(*) FROM users");
  $counts['total_users'] = (int)$stmt->fetchColumn();
} catch (Exception $e) {}

try {
  // Active sessions today approximated by LOGIN entries in system_logs
  $stmt = $pdo->prepare("SELECT COUNT(*) FROM system_logs WHERE action = 'LOGIN' AND created_at BETWEEN ? AND ?");
  $stmt->execute([$today_start, $today_end]);
  $counts['active_sessions_today'] = (int)$stmt->fetchColumn();
} catch (Exception $e) {}

try {
  // Attendance records today (best-effort)
  $stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE DATE(created_at) = CURDATE()");
  $stmt->execute();
  $counts['attendance_today'] = (int)$stmt->fetchColumn();
} catch (Exception $e) {}

try {
  // Billing records today (best-effort)
  $stmt = $pdo->prepare("SELECT COUNT(*) FROM billing WHERE DATE(created_at) = CURDATE()");
  $stmt->execute();
  $counts['billing_today'] = (int)$stmt->fetchColumn();
} catch (Exception $e) {}

// Security summary
try {
  $stmt = $pdo->prepare("SELECT COUNT(*) FROM system_logs WHERE action = 'LOGIN' AND created_at BETWEEN ? AND ?");
  $stmt->execute([$today_start, $today_end]);
  $security['logins_today'] = (int)$stmt->fetchColumn();
} catch (Exception $e) {}
try {
  $stmt = $pdo->prepare("SELECT COUNT(*) FROM system_logs WHERE action = 'LOGIN_FAILED' AND created_at BETWEEN ? AND ?");
  $stmt->execute([$today_start, $today_end]);
  $security['failed_today'] = (int)$stmt->fetchColumn();
} catch (Exception $e) {}

// Suspicious or locked attempts: look for explicit LOCKED/SUSPICIOUS actions, otherwise detect multiple failed attempts per user in last hour
try {
  $stmt = $pdo->query("SELECT COUNT(*) FROM system_logs WHERE action IN ('LOCKED','SUSPICIOUS')");
  $s = (int)$stmt->fetchColumn();
  if ($s > 0) {
    $security['suspicious'] = $s;
  } else {
    // heuristic: users with >=5 failed attempts in last 1 hour
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT user_id) FROM (SELECT user_id, COUNT(*) AS c FROM system_logs WHERE action = 'LOGIN_FAILED' AND created_at >= (NOW() - INTERVAL 1 HOUR) GROUP BY user_id HAVING c >= 5) x");
    $stmt->execute();
    $security['suspicious'] = (int)$stmt->fetchColumn();
  }
} catch (Exception $e) {}

// Recent critical system events (read-only alerts)
try {
  $actions = ['ERROR','EXCEPTION','LOGIN_FAILED','LOCKED','CONFIG_UPDATED','MULTI_LOGIN_ATTEMPT'];
  $placeholders = implode(',', array_fill(0, count($actions), '?'));
  $sql = "SELECT created_at, user_id, action, details FROM system_logs WHERE action IN ($placeholders) ORDER BY created_at DESC LIMIT 10";
  $stmt = $pdo->prepare($sql);
  $stmt->execute($actions);
  $recent_critical = $stmt->fetchAll();
} catch (Exception $e) {}

// Health indicators
try {
  // Storage usage for project root
  $root = realpath(__DIR__ . '/..');
  if ($root !== false) {
    $total = disk_total_space($root);
    $free = disk_free_space($root);
    if ($total > 0) {
      $used = $total - $free;
      $health['storage_percent'] = round(($used / $total) * 100, 1) . '%';
    }
  }
} catch (Exception $e) {}

try {
  // Database size (MB)
  $dbname = $pdo->query('SELECT DATABASE()')->fetchColumn();
  $stmt = $pdo->prepare("SELECT IFNULL(SUM(data_length + index_length)/1024/1024,0) FROM information_schema.TABLES WHERE table_schema = ?");
  $stmt->execute([$dbname]);
  $health['db_size_mb'] = round((float)$stmt->fetchColumn(), 2) . ' MB';
} catch (Exception $e) {}

try {
  // Last backup file in backups/ directory (if present)
  $backup_dir = __DIR__ . '/../backups';
  if (is_dir($backup_dir)) {
    $files = array_filter(glob($backup_dir . '/*'), 'is_file');
    usort($files, function($a, $b) { return filemtime($b) - filemtime($a); });
    if (!empty($files)) {
      $health['last_backup'] = date('Y-m-d H:i:s', filemtime($files[0]));
    }
  }
} catch (Exception $e) {}

?>

<style>
  .page-header { margin-bottom: 1rem; }
  .stats-grid { display:flex; gap:1rem; }
  .stat { padding:1rem; background:#fff; border:1px solid #e6eef8; border-radius:0.5rem; min-width:10rem; }
  .activities { margin-top:1rem; }
  .small { font-size:0.9rem; color:#6b7280; }
</style>

<div class="page-header">
  <h2 class="page-title">System Dashboard</h2>
  <p class="page-subtitle">Read-only overview — technical status and counts.</p>
  <div class="small">No buttons here; this is a read-only view.</div>
</div>

<div class="card">
  <div class="stats-grid" style="padding:1rem;">
    <div class="stat">
      <div class="small">System Status</div>
      <div style="font-weight:700; margin-top:0.5rem;"><?php echo htmlspecialchars($system_status); ?></div>
      <div class="small">Server time: <?php echo htmlspecialchars(date('Y-m-d H:i:s')); ?></div>
    </div>

    <div class="stat">
      <div class="small">Total Registered Users</div>
      <div style="font-weight:700; margin-top:0.5rem;"><?php echo htmlspecialchars($counts['total_users']); ?></div>
      <div class="small">Active sessions today: <?php echo htmlspecialchars($counts['active_sessions_today']); ?></div>
    </div>

    <div class="stat">
      <div class="small">Attendance Records Today</div>
      <div style="font-weight:700; margin-top:0.5rem;"><?php echo htmlspecialchars($counts['attendance_today']); ?></div>
      <div class="small">Billing Records Today: <?php echo htmlspecialchars($counts['billing_today']); ?></div>
    </div>

    <div class="stat">
      <div class="small">Security Summary (today)</div>
      <div style="font-weight:700; margin-top:0.5rem;">Logins: <?php echo htmlspecialchars($security['logins_today']); ?></div>
      <div class="small">Failed: <?php echo htmlspecialchars($security['failed_today']); ?> — Suspicious: <?php echo htmlspecialchars($security['suspicious']); ?></div>
    </div>
  </div>

  <div class="activities" style="padding:1rem;">
    <h3 class="text-lg">Recent Critical System Events</h3>
    <?php if (empty($recent_critical)): ?>
      <p class="small">No critical events detected.</p>
    <?php else: ?>
      <table style="width:100%; border-collapse:collapse; font-size:0.95rem;">
        <thead>
          <tr style="text-align:left; color:#374151; font-weight:600;">
            <th style="padding:8px;">Time</th>
            <th style="padding:8px;">Action</th>
            <th style="padding:8px;">User</th>
            <th style="padding:8px;">Details</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recent_critical as $r): ?>
            <tr style="border-top:1px solid #eef2f7;">
              <td style="padding:8px; vertical-align:middle;"><?php
                $t = $r['created_at'] ?? '';
                $out = $t;
                if ($t) {
                    try { $dt = new DateTime($t); $out = $dt->format('Y-m-d H:i:s'); } catch (Exception $e) { }
                }
                echo htmlspecialchars($out);
              ?></td>
              <td style="padding:8px; vertical-align:middle;"><?php echo htmlspecialchars($r['action']); ?></td>
              <td style="padding:8px; vertical-align:middle;"><?php echo htmlspecialchars($r['user_id'] ?? ''); ?></td>
              <td style="padding:8px; vertical-align:middle;"><?php echo htmlspecialchars(substr($r['details'] ?? '', 0, 300)); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <div style="padding:1rem; margin-top:0.5rem; border-top:1px solid #eef2f7;">
    <h3 class="text-lg">System Health</h3>
    <div class="small">Storage usage: <?php echo htmlspecialchars($health['storage_percent']); ?></div>
    <div class="small">Database size: <?php echo htmlspecialchars($health['db_size_mb']); ?></div>
    <div class="small">Last backup: <?php echo htmlspecialchars($health['last_backup']); ?></div>
  </div>
</div>

<?php include '../footer.php'; ?>
