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

// Check if member has a fitness goal
$has_fitness_goal = false;
if ($member_id) {
    $stmt = $pdo->prepare('SELECT fitness_goal FROM member_fitness_goals WHERE member_id = ? LIMIT 1');
    $stmt->execute([$member_id]);
    $goal_result = $stmt->fetch();
    $has_fitness_goal = !empty($goal_result);
}

$session_created = false;
$session_error = null;
$upcoming_sessions = [];
$all_trainers = [];

// Get all active trainers
$stmt = $pdo->query('SELECT trainer_id, first_name, last_name, specialization FROM trainers WHERE status = "Active" ORDER BY first_name');
$all_trainers = $stmt->fetchAll();

// Get today's sessions for this member
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
        WHERE mts.member_id = ? AND mts.session_date = CURDATE() AND mts.status != "cancelled"
        ORDER BY mts.session_time
    ');
    $stmt->execute([$member_id]);
    $upcoming_sessions = $stmt->fetchAll();
}

// Handle session booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'book_session') {
    if (!$has_fitness_goal) {
        $session_error = 'You must select a fitness goal first before booking a session!';
    } else {
        $trainer_id = $_POST['trainer_id'] ?? null;
        $session_date = $_POST['session_date'] ?? null;
        $session_time = $_POST['session_time'] ?? null;
        $session_duration = $_POST['session_duration'] ?? 60;

        if ($trainer_id && $session_date && $session_time && $member_id) {
            // Check if session date is in the future
            if (strtotime($session_date) < strtotime(date('Y-m-d'))) {
                $session_error = 'Session date must be in the future.';
            } else {
                // Check if trainer is available at the requested time
                $requested_start = strtotime($session_time);
                $requested_end = $requested_start + ($session_duration * 60);
                
                $stmt = $pdo->prepare('
                    SELECT session_time, session_duration 
                    FROM member_training_sessions 
                    WHERE trainer_id = ? AND session_date = ? AND status IN ("scheduled", "ongoing")
                ');
                $stmt->execute([$trainer_id, $session_date]);
                $booked_sessions = $stmt->fetchAll();
                
                $is_available = true;
                foreach ($booked_sessions as $booked) {
                    $booked_start = strtotime($booked['session_time']);
                    $booked_end = $booked_start + ($booked['session_duration'] * 60);
                    
                    // Check if times overlap
                    if (($requested_start < $booked_end) && ($requested_end > $booked_start)) {
                        $is_available = false;
                        break;
                    }
                }
                
                if (!$is_available) {
                    $session_error = 'This trainer is not available at the selected time. Please choose a different time or trainer.';
                } else {
                    try {
                        $stmt = $pdo->prepare('
                            INSERT INTO member_training_sessions 
                            (member_id, trainer_id, session_date, session_time, session_duration, status)
                            VALUES (?, ?, ?, ?, ?, "scheduled")
                    ');
                    $stmt->execute([$member_id, $trainer_id, $session_date, $session_time, $session_duration]);
                    $session_created = true;
                    
                    // Refresh upcoming sessions
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
                        WHERE mts.member_id = ? AND mts.session_date >= CURDATE() AND mts.status != "cancelled"
                        ORDER BY mts.session_date, mts.session_time
                    ');
                    $stmt->execute([$member_id]);
                    $upcoming_sessions = $stmt->fetchAll();
                    } catch (Exception $e) {
                        $session_error = 'Error booking session. Please try again.';
                    }
                }
            }
        } else {
            $session_error = 'Please fill in all required fields.';
        }
    }
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
            
            // Refresh today's sessions
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
                WHERE mts.member_id = ? AND mts.session_date = CURDATE() AND mts.status != "cancelled"
                ORDER BY mts.session_time
            ');
            $stmt->execute([$member_id]);
            $upcoming_sessions = $stmt->fetchAll();
        } catch (Exception $e) {
            $session_error = 'Error cancelling session.';
        }
    }
}

$page_title = 'Today\'s Sessions - UEP Fitness Gym';
include '../header.php';
?>
<style>
<?php include 'sessions_styles.css'; ?>
</style>

<div class="sessions-container">
  <div class="sessions-header">
    <h2 class="sessions-title">Book Training Sessions</h2>
    <p class="sessions-subtitle">View and manage your sessions scheduled for today</p>
  </div>

  <!-- Navigation Links -->
  <div class="sessions-nav">
    <a href="/gym/member_view/sessions.php" class="sessions-nav-link active">Book</a>
    <a href="/gym/member_view/sessions_upcoming.php" class="sessions-nav-link">Upcoming</a>
    <a href="/gym/member_view/sessions_history.php" class="sessions-nav-link">History</a>
  </div>

  <!-- Success Message -->
  <?php if ($session_created): ?>
    <div class="alert-box alert-success">
      <div class="alert-title">✓ Session Booked Successfully!</div>
      <p>Your training session has been scheduled. Check your upcoming sessions below.</p>
    </div>
  <?php endif; ?>

  <!-- Error Message -->
  <?php if ($session_error): ?>
    <div class="alert-box alert-error">
      <div class="alert-title">⚠ Error</div>
      <p><?php echo htmlspecialchars($session_error); ?></p>
    </div>
  <?php endif; ?>

  <!-- No Program Alert -->
  <?php if (!$has_fitness_goal): ?>
    <div class="no-program-alert">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c.866 1.5 2.926 3.374 5.555 3.374 2.763 0 5.144 1.093 6.342 2.135a4.5 4.5 0 0 0 5.686-4.172M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6-3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 13.5h13.5"/>
      </svg>
      <div class="no-program-alert-title">No Fitness Goal Selected</div>
      <p class="no-program-alert-text">You must create a fitness program first before booking a training session.</p>
      <a href="program.php" class="program-link">Create Your Fitness Program</a>
    </div>
  <?php endif; ?>

  <!-- Booking Form -->
  <?php if ($has_fitness_goal): ?>
    <div class="booking-section">
      <h3 class="section-title">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5a2.25 2.25 0 0 1 2.25-2.25h13.5a2.25 2.25 0 0 1 2.25 2.25v7.5"/>
        </svg>
        Book a Training Session
      </h3>

      <form method="POST" action="">
        <input type="hidden" name="action" value="book_session">

        <!-- Date and Time Selection -->
        <div class="booking-form">
          <div class="form-group">
            <label class="form-label">Session Date</label>
            <input type="date" name="session_date" class="form-input" min="<?php echo date('Y-m-d'); ?>" required>
          </div>

          <div class="form-group">
            <label class="form-label">Session Time</label>
            <input type="time" name="session_time" class="form-input" required>
          </div>

          <div class="form-group">
            <label class="form-label">Duration (minutes)</label>
            <select name="session_duration" class="form-select">
              <option value="30">30 minutes</option>
              <option value="60" selected>60 minutes (1 hour)</option>
              <option value="90">90 minutes</option>
              <option value="120">120 minutes (2 hours)</option>
            </select>
          </div>
        </div>

        <!-- Trainer Selection -->
        <div>
          <label class="form-label" style="margin-bottom: 1.5rem;">Select Your Trainer</label>
          <div class="trainer-selector">
            <?php foreach ($all_trainers as $trainer): 
              $trainer_name = $trainer['first_name'] . ' ' . $trainer['last_name'];
              ?>
              <div class="trainer-card">
                <input type="radio" name="trainer_id" value="<?php echo htmlspecialchars($trainer['trainer_id']); ?>" id="trainer_<?php echo htmlspecialchars($trainer['trainer_id']); ?>" required>
                <label for="trainer_<?php echo htmlspecialchars($trainer['trainer_id']); ?>" class="trainer-info">
                  <div class="trainer-name"><?php echo htmlspecialchars($trainer_name); ?></div>
                  <div class="trainer-spec"><?php echo htmlspecialchars($trainer['specialization']); ?></div>
                  <div class="trainer-availability">
                    <span>✓ Available for booking</span>
                  </div>
                </label>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Submit Button -->
        <div style="margin-top: 2rem;">
          <button type="submit" class="submit-btn">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Book Session
          </button>
        </div>
      </form>
    </div>
  <?php endif; ?>

  <!-- Upcoming Sessions -->
  <?php if ($has_fitness_goal): ?>
    <div class="booking-section">
      <h3 class="section-title">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.007H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.007H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 5.25h.007v.007H3.75v-.007Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/>
        </svg>
        Upcoming Sessions
      </h3>

      <?php if (!empty($upcoming_sessions)): ?>
        <div class="sessions-list">
          <?php foreach ($upcoming_sessions as $session): ?>
            <div class="session-item <?php echo htmlspecialchars($session['status']); ?>">
              <div class="session-details">
                <div class="session-date-time">
                  📅 <?php echo date('M d, Y', strtotime($session['session_date'])); ?> @ 
                  <strong><?php echo date('g:i A', strtotime($session['session_time'])); ?></strong>
                </div>
                <div class="session-trainer">
                  👤 Trainer: <strong><?php echo htmlspecialchars($session['first_name'] . ' ' . $session['last_name']); ?></strong> 
                  <span style="color: #94a3b8;">(<?php echo htmlspecialchars($session['specialization']); ?>)</span>
                </div>
                <div class="session-trainer">
                  ⏱ Duration: <?php echo htmlspecialchars($session['session_duration']); ?> minutes
                </div>
                <div>
                  <span class="session-status <?php echo htmlspecialchars($session['status']); ?>">
                    <?php echo ucfirst(htmlspecialchars($session['status'])); ?>
                  </span>
                </div>
              </div>
              <div class="session-actions">
                <?php if ($session['status'] === 'scheduled'): ?>
                  <form method="POST" action="" style="display: inline;">
                    <input type="hidden" name="action" value="cancel_session">
                    <input type="hidden" name="session_id" value="<?php echo htmlspecialchars($session['session_id']); ?>">
                    <button type="submit" class="cancel-btn" onclick="return confirm('Are you sure you want to cancel this session?');">Cancel</button>
                  </form>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="no-sessions">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8.25v13.5M6.375 6.375a4.5 4.5 0 1 0 9 0 4.5 4.5 0 0 0-9 0ZM12 2.25h.005v.006H12V2.25Z"/>
          </svg>
          <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem;">No Upcoming Sessions</h3>
          <p>You haven't booked any training sessions yet. Select a trainer above and book your first session!</p>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>

<?php include '../footer.php'; ?>
