<?php
// ===============================
// Authentication & Access Control
// ===============================
require_once '../config/database.php';
$page_title = 'My Weekly Schedule';
require_once '../header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'trainer') {
    header('Location: ../index.php');
    exit();
}

// ===============================
// Get Trainer ID from username
// ===============================
$username = $_SESSION['username'] ?? null;
$trainer_id = null;
if ($username) {
    $stmt = $pdo->prepare('SELECT trainer_id FROM trainers WHERE username = ?');
    $stmt->execute([$username]);
    $row = $stmt->fetch();
    $trainer_id = $row['trainer_id'] ?? null;
}
if (!$trainer_id) {
    header('Location: ../index.php');
    exit();
}

// ===============================
// Handle Start/Finish Session Actions (POST)
// ===============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['session_id'])) {
    $session_id = intval($_POST['session_id']);
    $action = $_POST['action'];
    // Fetch session and check ownership
    $stmt = $pdo->prepare('SELECT * FROM trainer_assignments WHERE assignment_id = ? AND trainer_id = ?');
    $stmt->execute([$session_id, $trainer_id]);
    $session = $stmt->fetch();
    if ($session) {
        if ($action === 'start' && strtolower($session['status']) === 'scheduled') {
            // Start session: set status only
            $stmt = $pdo->prepare('UPDATE trainer_assignments SET status = ? WHERE assignment_id = ?');
            $stmt->execute(['Ongoing', $session_id]);
        } elseif ($action === 'finish' && strtolower($session['status']) === 'ongoing') {
            // Finish session: set status only
            $stmt = $pdo->prepare('UPDATE trainer_assignments SET status = ? WHERE assignment_id = ?');
            $stmt->execute(['Completed', $session_id]);
        }
    }
    // Redirect to avoid form resubmission
    header('Location: schedule.php?week=' . urlencode($_GET['week'] ?? ''));
    exit();
}

// ===============================
// Week Navigation Logic
// ===============================
$today = new DateTime();
$week = $_GET['week'] ?? $today->format('Y-m-d');
$week_start = new DateTime($week);
$week_start->modify('monday this week');
$week_end = clone $week_start;
$week_end->modify('+6 days');

// Previous/Next/Current week links
$prev_week = clone $week_start;
$prev_week->modify('-7 days');
$next_week = clone $week_start;
$next_week->modify('+7 days');
$current_week = (new DateTime())->format('Y-m-d');

// ===============================
// Fetch Sessions for the Week
// ===============================

$stmt = $pdo->prepare('SELECT * FROM trainer_assignments WHERE trainer_id = ? AND session_date BETWEEN ? AND ? ORDER BY session_date, start_time');
$stmt->execute([$trainer_id, $week_start->format('Y-m-d'), $week_end->format('Y-m-d')]);
$sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group sessions by date and fetch all member names for each session
$sessions_by_date = [];
foreach ($sessions as $session) {
    $date = $session['session_date'];
    // Fetch member name for this assignment (member_id is in trainer_assignments)
    $member_names = [];
    try {
        if (!empty($session['member_id'])) {
            $stmt_names = $pdo->prepare("SELECT CONCAT(first_name, ' ', COALESCE(CONCAT(middle_name, ' '), ''), last_name) as full_name FROM members WHERE member_id = ?");
            $stmt_names->execute([$session['member_id']]);
            $result = $stmt_names->fetch();
            if ($result) {
                $member_names[] = $result['full_name'];
            }
        }
    } catch (PDOException $e) {
        $member_names = [];
    }
    $session['member_names'] = $member_names;
    if (!isset($sessions_by_date[$date])) {
        $sessions_by_date[$date] = [];
    }
    $sessions_by_date[$date][] = $session;
}

// ===============================
// UI Rendering (inside header/footer layout)
// ===============================
?>

<style>
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

  /* Modal Enhancements */
  .modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(8px);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    padding: 1rem;
    animation: fadeIn 0.2s ease;
  }

  .modal-overlay.show {
    display: flex !important;
  }

  @keyframes fadeIn {
    from {
      opacity: 0;
    }
    to {
      opacity: 1;
    }
  }

  @keyframes slideUp {
    from {
      opacity: 0;
      transform: translateY(20px) scale(0.95);
    }
    to {
      opacity: 1;
      transform: translateY(0) scale(1);
    }
  }

  .modal-content {
    background: white;
    border-radius: 1rem;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
    max-width: 500px;
    width: 100%;
    max-height: 90vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    animation: slideUp 0.3s ease;
  }

  .modal-header {
    padding: 1.5rem;
    border-bottom: 1px solid #e2e8f0;
    background: linear-gradient(135deg, #fef9c3 0%, #fef3c7 100%);
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-shrink: 0;
  }

  .modal-title {
    font-size: 1.35rem;
    font-weight: 700;
    color: #854d0e;
    margin: 0;
  }

  .modal-close {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 0.5rem;
    border: none;
    background: rgba(255, 255, 255, 0.8);
    color: #6b7280;
    font-size: 1.75rem;
    line-height: 1;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 300;
  }

  .modal-close:hover {
    background: rgba(255, 255, 255, 1);
    color: #dc2626;
    transform: rotate(90deg);
  }

  .modal-body {
    padding: 1.5rem;
    overflow-y: auto;
    flex: 1;
  }

  .badge {
    display: inline-block;
    padding: 0.35rem 0.85rem;
    border-radius: 9999px;
    font-size: 0.8rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.025em;
  }

  .badge-gray {
    background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
    color: #475569;
    border: 1px solid #cbd5e1;
  }

  .badge-blue {
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    color: #1e40af;
    border: 1px solid #93c5fd;
  }

  .badge-green {
    background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
    color: #166534;
    border: 1px solid #86efac;
  }

  /* Card Styling */
  .card {
    background: white;
    border-radius: 0.75rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
    border: 1px solid #e5e7eb;
  }

  .card-body {
    padding: 1.5rem;
  }

  /* Button Styling */
  .btn {
    padding: 0.65rem 1.25rem;
    border-radius: 0.5rem;
    border: none;
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    text-decoration: none;
  }

  .btn-secondary {
    background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
    color: #475569;
    border: 1px solid #cbd5e1;
  }

  .btn-secondary:hover {
    background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  }

  .btn-primary {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: white;
    border: none;
  }

  .btn-primary:hover {
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    transform: translateY(-1px);
    box-shadow: 0 6px 12px -2px rgba(37, 99, 235, 0.4);
  }

  /* Utilities */
  .mb-6 {
    margin-bottom: 1.5rem;
  }

  .w-full {
    width: 100%;
  }

  .w-5 {
    width: 1.25rem;
  }

  .h-5 {
    height: 1.25rem;
  }

  .gap-2 {
    gap: 0.5rem;
  }

  .gap-4 {
    gap: 1rem;
  }

  .space-y-4 > * + * {
    margin-top: 1rem;
  }

  .overflow-x-auto {
    overflow-x: auto;
  }

  .table-header {
    background: linear-gradient(135deg, #fef9c3 0%, #fef3c7 100%);
  }
</style>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">My Weekly Schedule</h1>
        <p class="page-subtitle">Manage and track your training sessions</p>
    </div>

<!-- Week Navigation -->
    <div class="card mb-6">
        <div class="card-body">
            <div class="flex items-center justify-between flex-wrap gap-4">
    <form method="get" class="flex gap-2">
                    <button type="submit" name="week" value="<?= $prev_week->format('Y-m-d') ?>" class="btn btn-secondary">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        Previous
                    </button>
                    <button type="submit" name="week" value="<?= $current_week ?>" class="btn" style="background: linear-gradient(to right, var(--yellow-500), var(--yellow-600)); color: white; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
                        Current Week
                    </button>
                    <button type="submit" name="week" value="<?= $next_week->format('Y-m-d') ?>" class="btn btn-secondary">
                        Next
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
    </form>
                <div class="text-lg font-semibold text-gray-700">
        <?= $week_start->format('M d, Y') ?> &ndash; <?= $week_end->format('M d, Y') ?>
    </div>
</div>
        </div>
    </div>

<!-- Weekly Calendar Table -->
    <div class="card">
<div class="overflow-x-auto">
            <table class="w-full">
        <thead>
                    <tr class="table-header">
                <?php foreach ([0,1,2,3,4,5,6] as $i): ?>
                    <?php $date = clone $week_start; $date->modify("+{$i} days"); ?>
                            <th class="py-4 px-4 text-center bg-gradient-to-br from-yellow-50 to-yellow-100 text-yellow-800 font-bold border-b-2 border-yellow-200">
                                <div class="text-lg"><?= $date->format('D') ?></div>
                                <div class="text-sm text-gray-600 font-normal mt-1"><?= $date->format('M d') ?></div>
                    </th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <tr>
                <?php foreach ([0,1,2,3,4,5,6] as $i): ?>
                    <?php $date = clone $week_start; $date->modify("+{$i} days"); $date_str = $date->format('Y-m-d'); ?>
                            <td class="align-top p-3 border-r border-gray-100 min-w-[200px] bg-gray-50/30">
                        <?php if (!empty($sessions_by_date[$date_str])): ?>
                                    <div class="space-y-3">
                            <?php foreach ($sessions_by_date[$date_str] as $session): ?>
                                            <div class="session-card p-4 rounded-lg border-2 border-yellow-200 bg-gradient-to-br from-yellow-50 to-white shadow-md hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
                                                <div class="mb-3">
                                                    <h3 class="font-bold text-yellow-900 text-base">
                                                        <?= htmlspecialchars($session['session_type'] ?? $session['session_name'] ?? 'Session') ?>
                                                    </h3>
                                    </div>
                                                
                                                <div class="flex items-center gap-2 text-sm text-gray-700 mb-3">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-yellow-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    <span class="font-medium"><?= date('h:i A', strtotime($session['start_time'])) ?> - <?= date('h:i A', strtotime($session['end_time'])) ?></span>
                                    </div>
                                                
                                                <div class="mb-3">
                                                    <span class="badge 
                                                        <?php 
                                                        if (strtolower($session['status']) === 'scheduled') echo 'badge-gray';
                                                        elseif (strtolower($session['status']) === 'ongoing') echo 'badge-blue';
                                                        elseif (strtolower($session['status']) === 'completed') echo 'badge-green';
                                                        ?>">
                                            <?= ucfirst(strtolower($session['status'])) ?>
                                        </span>
                                    </div>
                                                
                                                <button type="button" class="view-details-btn inline-flex items-center justify-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white rounded-lg shadow-sm transition-all duration-200" style="background: linear-gradient(to right, var(--yellow-500), var(--yellow-600));" 
                                                    data-session='<?= htmlspecialchars(json_encode($session), ENT_QUOTES, 'UTF-8') ?>'
                                                    onmouseover="this.style.background='linear-gradient(to right, var(--yellow-600), var(--yellow-700))'"
                                                    onmouseout="this.style.background='linear-gradient(to right, var(--yellow-500), var(--yellow-600))'">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                        View Details
                                    </button>
                                </div>
                                        <?php endforeach; ?>
                            </div>
                                <?php else: ?>
                                    <div class="text-center py-8 text-gray-400 text-sm italic">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                        </svg>
                                        No sessions
                                    </div>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Session Details Modal (moved outside loop) -->
<div id="session-modal" class="modal-overlay">
    <div class="modal-content max-w-md">
        <div class="modal-header">
            <h2 class="modal-title text-yellow-800" id="modal-session-type"></h2>
            <button id="close-modal" class="modal-close hover:text-yellow-600">&times;</button>
        </div>
        
        <div class="modal-body">
            <div class="space-y-4">
                <div class="flex items-center gap-3 text-gray-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-yellow-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span class="font-medium" id="modal-session-date"></span>
                </div>
                
                <div class="flex items-center gap-3 text-gray-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-yellow-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="font-medium" id="modal-session-time"></span>
                </div>
                
                <div class="text-gray-700">
                    <span class="font-semibold">Status:</span>
                    <span id="modal-session-status" class="font-bold ml-2"></span>
                </div>
                
                <div class="text-gray-700" id="modal-session-notes"></div>
                
                <div class="text-gray-700" id="modal-session-members"></div>
            </div>
            
                                    <form id="modal-action-form" method="post" class="mt-6 hidden">
                                        <input type="hidden" name="session_id" id="modal-session-id">
                                        <input type="hidden" name="action" id="modal-session-action">
                <button type="submit" id="modal-action-btn" class="btn w-full"></button>
                                    </form>
                                </div>
                            </div>
</div>

                            <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                const modal = document.getElementById('session-modal');
                                const closeModal = document.getElementById('close-modal');
                                const modalType = document.getElementById('modal-session-type');
                                const modalDate = document.getElementById('modal-session-date');
                                const modalTime = document.getElementById('modal-session-time');
                                const modalStatus = document.getElementById('modal-session-status');
                                const modalNotes = document.getElementById('modal-session-notes');
                                const modalForm = document.getElementById('modal-action-form');
                                const modalId = document.getElementById('modal-session-id');
                                const modalAction = document.getElementById('modal-session-action');
                                const modalBtn = document.getElementById('modal-action-btn');
                                const modalMembers = document.getElementById('modal-session-members');

                                function showModal(session) {
                                    modalType.textContent = session.session_type || session.session_name || 'Session';
        modalDate.textContent = new Date(session.session_date).toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        modalTime.textContent = `${formatTime(session.start_time)} - ${formatTime(session.end_time)}`;
                                    modalStatus.textContent = session.status;
        
        // Set status badge styling
        const statusClass = session.status.toLowerCase() === 'scheduled' ? 'badge-gray' : 
                           session.status.toLowerCase() === 'ongoing' ? 'badge-blue' : 'badge-green';
        modalStatus.className = 'badge ' + statusClass;
        
        modalNotes.innerHTML = session.notes ? `<div class="mt-2 p-3 bg-gray-50 rounded-lg"><strong>Notes:</strong> ${session.notes}</div>` : '';
        
                                    if (Array.isArray(session.member_names) && session.member_names.length > 0) {
            modalMembers.innerHTML = '<div class="mt-2"><strong class="text-gray-900">Members:</strong><ul class="list-disc ml-6 mt-2 space-y-1">' + 
                session.member_names.map(n => `<li class="text-gray-700">${n}</li>`).join('') + '</ul></div>';
                                    } else {
            modalMembers.innerHTML = '<div class="text-gray-500 italic">No members assigned</div>';
                                    }
        
                                    modalForm.classList.add('hidden');
                                    if (session.status.toLowerCase() === 'scheduled') {
                                        modalForm.classList.remove('hidden');
                                        modalId.value = session.assignment_id;
                                        modalAction.value = 'start';
                                        modalBtn.textContent = 'Start Session';
            modalBtn.className = 'btn btn-primary w-full';
                                    } else if (session.status.toLowerCase() === 'ongoing') {
                                        modalForm.classList.remove('hidden');
                                        modalId.value = session.assignment_id;
                                        modalAction.value = 'finish';
                                        modalBtn.textContent = 'Finish Session';
            modalBtn.className = 'btn w-full';
            modalBtn.style.cssText = 'background: linear-gradient(to right, var(--green-600), var(--green-700)); color: white;';
                                    }
                                    modal.classList.add('show');
                                }

    function formatTime(timeStr) {
        const [hours, minutes] = timeStr.split(':');
        const hour = parseInt(hours);
        const ampm = hour >= 12 ? 'PM' : 'AM';
        const displayHour = hour % 12 || 12;
        return `${displayHour}:${minutes} ${ampm}`;
    }

                                document.querySelectorAll('.view-details-btn').forEach(btn => {
                                    btn.addEventListener('click', function() {
                                        const session = JSON.parse(this.getAttribute('data-session'));
                                        showModal(session);
                                    });
                                });
    
                                closeModal.addEventListener('click', function() {
                                    modal.classList.remove('show');
                                });
    
                                window.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.classList.contains('show')) {
            modal.classList.remove('show');
                                    }
                                });
    
                                modal.addEventListener('click', function(e) {
                                    if (e.target === modal) {
            modal.classList.remove('show');
                                    }
                                });
                            });
                            </script>

<?php include '../footer.php'; ?>

<!--
EXAMPLE SQL for table:
CREATE TABLE trainer_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trainer_id VARCHAR(20) NOT NULL,
    session_name VARCHAR(100) NOT NULL,
    session_date DATE NOT NULL,
    scheduled_start TIME NOT NULL,
    scheduled_end TIME NOT NULL,
    actual_start DATETIME DEFAULT NULL,
    actual_end DATETIME DEFAULT NULL,
    status ENUM('scheduled','ongoing','completed') DEFAULT 'scheduled',
    INDEX (trainer_id),
    INDEX (session_date)
);
-->
