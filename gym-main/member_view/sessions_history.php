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

$history_sessions = [];

// Get past sessions for this member
if ($member_id) {
    $stmt = $pdo->prepare('
        SELECT 
            mts.session_id,
            mts.session_date,
            mts.session_time,
            mts.session_duration,
            mts.status,
            mts.notes,
            t.trainer_id,
            t.first_name,
            t.last_name,
            t.specialization
        FROM member_training_sessions mts
        JOIN trainers t ON mts.trainer_id = t.trainer_id
        WHERE mts.member_id = ? AND (mts.session_date < CURDATE() OR (mts.session_date = CURDATE() AND mts.status IN ("completed", "cancelled")))
        ORDER BY mts.session_date DESC, mts.session_time DESC
    ');
    $stmt->execute([$member_id]);
    $history_sessions = $stmt->fetchAll();
}

$page_title = 'Session History - UEP Fitness Gym';
include '../header.php';
?>
<style>
<?php include 'sessions_styles.css'; ?>
</style>

<div class="sessions-container">
  <div class="sessions-header">
    <h2 class="sessions-title">Training Session History</h2>
    <p class="sessions-subtitle">Review your past training sessions</p>
  </div>

  <!-- Navigation Links -->
  <div class="sessions-nav">
    <a href="/gym/member_view/sessions.php" class="sessions-nav-link">Today</a>
    <a href="/gym/member_view/sessions_upcoming.php" class="sessions-nav-link">Upcoming</a>
    <a href="/gym/member_view/sessions_history.php" class="sessions-nav-link active">History</a>
  </div>

  <!-- Session History List -->
  <div class="booking-section">
    <h3 class="section-title">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
      </svg>
      Past Sessions
    </h3>

    <?php if (!empty($history_sessions)): ?>
      <div class="sessions-list">
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
          <div class="session-item history <?php echo htmlspecialchars($display_status); ?>">
            <div class="session-details">
              <div class="session-date-time">
                📅 <strong><?php echo date('M j, Y', strtotime($session['session_date'])); ?></strong> 
                at <strong><?php echo date('g:i A', strtotime($session['session_time'])); ?></strong>
                <span class="duration-badge"><?php echo $session['session_duration']; ?> min</span>
              </div>
              <div class="session-trainer">
                👤 Trainer: <strong><?php echo htmlspecialchars($session['first_name'] . ' ' . $session['last_name']); ?></strong> 
                <span style="color: #94a3b8;">(<?php echo htmlspecialchars($session['specialization']); ?>)</span>
              </div>
              <?php if (!empty($session['notes'])): ?>
                <div class="session-notes">📝 <?php echo htmlspecialchars($session['notes']); ?></div>
              <?php endif; ?>
            </div>
            <div class="session-actions">
              <span class="status-badge status-<?php echo htmlspecialchars($display_status); ?>">
                <?php 
                  if ($display_status === 'completed') {
                    echo '✓ Completed';
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
          </div>
        <?php 
        endforeach;
        if ($current_month !== null) echo '</div>';
        ?>
      </div>
    <?php else: ?>
      <div class="no-sessions-message">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
        </svg>
        <p>No past sessions found</p>
        <a href="/gym/member_view/sessions.php" class="book-link">Book your first session</a>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php include '../footer.php'; ?>
