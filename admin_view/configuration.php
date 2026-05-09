<?php
session_start();
require_once '../config/functions.php';

// Only allow admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$page_title = 'System Configuration - UEP Fitness Gym';
include '../header.php';

$config_path = __DIR__ . '/../config/system_config.json';
$defaults = [
    'system_name' => 'UEP Fitness Gym',
    'logo' => '',
    'date_time_format' => 'Y-m-d H:i',
    'session_timeout_minutes' => 30,
    'password_rules' => [
        'min_length' => 8,
        'require_number' => true,
        'require_special' => false,
        'require_upper' => true,
        'require_lower' => true
    ]
];

$message = '';
$errors = [];

// Load current config if exists
if (file_exists($config_path)) {
    $raw = file_get_contents($config_path);
    $current = json_decode($raw, true) ?: $defaults;
} else {
    $current = $defaults;
}

// Handle POST (save)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $system_name = trim($_POST['system_name'] ?? '');
    $date_time_format = trim($_POST['date_time_format'] ?? 'Y-m-d H:i');
    $session_timeout = (int) ($_POST['session_timeout_minutes'] ?? 30);
    $pw_min = (int) ($_POST['pw_min_length'] ?? 8);
    $pw_num = isset($_POST['pw_require_number']) ? true : false;
    $pw_special = isset($_POST['pw_require_special']) ? true : false;
    $pw_upper = isset($_POST['pw_require_upper']) ? true : false;
    $pw_lower = isset($_POST['pw_require_lower']) ? true : false;

    if ($system_name === '') {
        $errors[] = 'System name is required.';
    }
    if ($session_timeout <= 0) {
        $errors[] = 'Session timeout must be a positive number of minutes.';
    }
    if ($pw_min < 4) {
        $errors[] = 'Password minimum length must be at least 4 characters.';
    }

    // Handle logo upload
    $logo_path = $current['logo'] ?? '';
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        $allowed = ['png','jpg','jpeg','gif','svg'];
        if (!in_array($ext, $allowed, true)) {
            $errors[] = 'Logo must be an image (png, jpg, jpeg, gif, svg).';
        } else {
            $img_dir = __DIR__ . '/../img/';
            if (!is_dir($img_dir)) mkdir($img_dir, 0755, true);
            $filename = 'logo.' . $ext;
            $dest = $img_dir . $filename;
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $dest)) {
                // Store relative path from web root
                $logo_path = 'img/' . $filename;
            } else {
                $errors[] = 'Failed to move uploaded logo file.';
            }
        }
    }

    if (empty($errors)) {
        $new = [
            'system_name' => $system_name,
            'logo' => $logo_path,
            'date_time_format' => $date_time_format,
            'session_timeout_minutes' => $session_timeout,
            'password_rules' => [
                'min_length' => $pw_min,
                'require_number' => $pw_num,
                'require_special' => $pw_special,
                'require_upper' => $pw_upper,
                'require_lower' => $pw_lower
            ]
        ];

        // Save atomically
        $tmp = $config_path . '.tmp';
        if (file_put_contents($tmp, json_encode($new, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) !== false) {
            rename($tmp, $config_path);
            $message = 'Configuration saved.';
            $current = $new;
        } else {
            $errors[] = 'Failed to write configuration file.';
        }
    }
}
?>

<style>
  .config-form { max-width:900px; margin:1rem auto; background:#fff; padding:1rem; border-radius:6px; }
  .field { margin-bottom:0.75rem; }
  label { display:block; margin-bottom:0.25rem; font-weight:600; }
  input[type="text"], input[type="number"], select { width:100%; padding:0.5rem; border:1px solid #e5e7eb; border-radius:4px; }
  .checkbox-row { display:flex; gap:1rem; align-items:center; }
</style>

<div class="page-header">
  <h2 class="page-title">System Configuration</h2>
  <p class="page-subtitle">Technical settings only — no gym operations here.</p>
</div>

<div class="config-form">
  <?php if ($message): ?>
    <div style="padding:0.5rem; background:#ecfdf5; border:1px solid #bbf7d0;"><?php echo htmlspecialchars($message); ?></div>
  <?php endif; ?>
  <?php if (!empty($errors)): ?>
    <div style="padding:0.5rem; background:#fff1f2; border:1px solid #fecaca; margin-bottom:0.5rem;">
      <ul>
        <?php foreach ($errors as $e): ?>
          <li><?php echo htmlspecialchars($e); ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data">
    <div class="field">
      <label>System Name</label>
      <input type="text" name="system_name" value="<?php echo htmlspecialchars($current['system_name'] ?? ''); ?>" required>
    </div>

    <div class="field">
      <label>Current Logo</label>
      <?php if (!empty($current['logo'])): ?>
        <div style="margin-bottom:0.5rem;"><img src="<?php echo htmlspecialchars('../' . $current['logo']); ?>" alt="Logo" style="max-height:80px;"></div>
      <?php else: ?>
        <div class="small">No logo configured.</div>
      <?php endif; ?>
      <label>Upload New Logo (png/jpg/gif/svg)</label>
      <input type="file" name="logo" accept="image/*">
    </div>

    <div class="field">
      <label>Date & Time Format</label>
      <select name="date_time_format">
        <?php $formats = ['Y-m-d H:i' => '2026-02-01 14:30 (Y-m-d H:i)', 'd/m/Y H:i' => '01/02/2026 14:30 (d/m/Y H:i)', 'm/d/Y h:i A' => '02/01/2026 02:30 PM (m/d/Y h:i A)']; ?>
        <?php foreach ($formats as $fmt => $label): ?>
          <option value="<?php echo htmlspecialchars($fmt); ?>" <?php echo ($current['date_time_format'] ?? '') === $fmt ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="field">
      <label>Session Timeout (minutes)</label>
      <input type="number" name="session_timeout_minutes" min="1" value="<?php echo (int)($current['session_timeout_minutes'] ?? 30); ?>">
    </div>

    <fieldset style="border:1px solid #e5e7eb; padding:0.75rem; margin-bottom:0.75rem;">
      <legend style="font-weight:700;">Password Rules</legend>
      <div class="field">
        <label>Minimum Length</label>
        <input type="number" name="pw_min_length" min="4" value="<?php echo (int)($current['password_rules']['min_length'] ?? 8); ?>">
      </div>
      <div class="field checkbox-row">
        <label><input type="checkbox" name="pw_require_number" <?php echo !empty($current['password_rules']['require_number']) ? 'checked' : ''; ?>> Require number</label>
        <label><input type="checkbox" name="pw_require_special" <?php echo !empty($current['password_rules']['require_special']) ? 'checked' : ''; ?>> Require special char</label>
        <label><input type="checkbox" name="pw_require_upper" <?php echo !empty($current['password_rules']['require_upper']) ? 'checked' : ''; ?>> Require uppercase</label>
        <label><input type="checkbox" name="pw_require_lower" <?php echo !empty($current['password_rules']['require_lower']) ? 'checked' : ''; ?>> Require lowercase</label>
      </div>
    </fieldset>

    <div style="display:flex; gap:0.5rem;">
      <button type="submit">Save Configuration</button>
      <a href="dashboard.php" style="align-self:center;">Cancel</a>
    </div>
  </form>
</div>

<?php include '../footer.php'; ?>
