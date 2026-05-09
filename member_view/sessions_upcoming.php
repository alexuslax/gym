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

$session_error = null;
$upcoming_sessions = [];

// Get upcoming sessions for this member (after today)
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
        WHERE mts.member_id = ? AND mts.session_date > CURDATE() AND mts.status != "cancelled"
        ORDER BY mts.session_date, mts.session_time
    ');
    $stmt->execute([$member_id]);
    $upcoming_sessions = $stmt->fetchAll();
}

// Handle session cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_session') {
    $session_id = $_POST['session_id'] ?? null;
    if ($session_id) {
        try {
            $stmt = $pdo->prepare('
                UPDATE member_training_sessions 
                SET status = "cancelled" 
                WHERE session_id = ? AND member_id = ?
            ');
            $stmt->execute([$session_id, $member_id]);
            
            // Refresh sessions
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
                WHERE mts.member_id = ? AND mts.session_date > CURDATE() AND mts.status != "cancelled"
                ORDER BY mts.session_date, mts.session_time
            ');
            $stmt->execute([$member_id]);
            $upcoming_sessions = $stmt->fetchAll();
        } catch (Exception $e) {
            $session_error = 'Error cancelling session.';
        }
    }
}

$page_title = 'Upcoming Sessions - UEP Fitness Gym';
include '../header.php';
?>
<style>
<?php include 'sessions_styles.css'; ?>
</style>

<div class="sessions-container">
  <div class="sessions-header">
    <h2 class="sessions-title">Upcoming Training Sessions</h2>
    <p class="sessions-subtitle">View all your scheduled future training sessions</p>
  </div>

  <!-- Navigation Links -->
  <div class="sessions-nav">
    <a href="/gym/member_view/sessions.php" class="sessions-nav-link">Today</a>
    <a href="/gym/member_view/sessions_upcoming.php" class="sessions-nav-link active">Upcoming</a>
    <a href="/gym/member_view/sessions_history.php" class="sessions-nav-link">History</a>
  </div>

  <!-- Error Message -->
  <?php if ($session_error): ?>
    <div class="alert-box alert-error">
      <div class="alert-title">⚠ Error</div>
      <p><?php echo htmlspecialchars($session_error); ?></p>
    </div>
  <?php endif; ?>

  <!-- Upcoming Sessions List -->
  <div class="booking-section">
    <h3 class="section-title">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/>
      </svg>
      Your Scheduled Sessions
    </h3>

    <?php if (!empty($upcoming_sessions)): ?>
      <div class="sessions-list">
        <?php 
        $current_date = null;
        foreach ($upcoming_sessions as $session): 
          $session_date = date('Y-m-d', strtotime($session['session_date']));
          if ($current_date !== $session_date) {
            if ($current_date !== null) echo '</div>';
            $current_date = $session_date;
            echo '<div class="date-group"><h4 class="date-header">' . date('l, F j, Y', strtotime($session['session_date'])) . '</h4>';
          }
        ?>
          <div class="session-item <?php echo htmlspecialchars($session['status']); ?>">
            <div class="session-details">
              <div class="session-time">
                🕐 <strong><?php echo date('g:i A', strtotime($session['session_time'])); ?></strong>
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
              <span class="status-badge status-<?php echo htmlspecialchars($session['status']); ?>">
                <?php echo ucfirst($session['status']); ?>
              </span>
              <?php if ($session['status'] === 'scheduled'): ?>
                <form method="POST" action="" style="display: inline;">
                  <input type="hidden" name="action" value="cancel_session">
                  <input type="hidden" name="session_id" value="<?php echo htmlspecialchars($session['session_id']); ?>">
                  <button type="submit" class="cancel-btn" onclick="return confirm('Are you sure you want to cancel this session?');">Cancel</button>
                </form>
              <?php endif; ?>
            </div>
          </div>
        <?php 
        endforeach;
        if ($current_date !== null) echo '</div>';
        ?>
      </div>
    <?php else: ?>
      <div class="no-sessions-message">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5m-9-6h.008v.008H12v-.008ZM12 15h.008v.008H12V15Zm0 2.25h.008v.008H12v-.008ZM9.75 15h.008v.008H9.75V15Zm0 2.25h.008v.008H9.75v-.008ZM7.5 15h.008v.008H7.5V15Zm0 2.25h.008v.008H7.5v-.008Zm6.75-4.5h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V15Zm0 2.25h.008v.008h-.008v-.008Zm2.25-4.5h.008v.008H16.5v-.008Zm0 2.25h.008v.008H16.5V15Z"/>
        </svg>
        <p>No upcoming sessions scheduled</p>
        <a href="/gym/member_view/sessions.php" class="book-link">Book a new session</a>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php include '../footer.php'; ?>
