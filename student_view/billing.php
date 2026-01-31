<?php
require_once '../config/functions.php';

// Get member_id from user_id
$member_id = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare('SELECT member_id FROM members WHERE user_id = ? LIMIT 1');
    $stmt->execute([$_SESSION['user_id']]);
    $member = $stmt->fetch();
    $member_id = $member['member_id'] ?? null;
}

// Fetch member info with plan details from billing table
$member = ['membership_plan' => null, 'plan_duration' => null, 'plan_price' => null];
if ($member_id) {
    $stmt = $pdo->prepare('
        SELECT mp.plan_name, mp.duration_days, mp.price
        FROM members m
        LEFT JOIN (
            SELECT member_id, plan_id, MAX(created_at) as latest
            FROM billing
            GROUP BY member_id
        ) latest_billing ON m.member_id = latest_billing.member_id
        LEFT JOIN billing b ON latest_billing.member_id = b.member_id AND latest_billing.latest = b.created_at
        LEFT JOIN membership_plans mp ON b.plan_id = mp.plan_id
        WHERE m.member_id = ?
    ');
    $stmt->execute([$member_id]);
    $member_data = $stmt->fetch();
    if ($member_data) {
        $member['membership_plan'] = $member_data['plan_name'];
        // Convert duration_days to readable format
        if (!empty($member_data['duration_days'])) {
            $days = $member_data['duration_days'];
            if ($days >= 365) {
                $member['plan_duration'] = round($days / 365) . ' Year(s)';
            } elseif ($days >= 30) {
                $member['plan_duration'] = round($days / 30) . ' Month(s)';
            } else {
                $member['plan_duration'] = $days . ' Day(s)';
            }
        }
        $member['plan_price'] = $member_data['price'] ?? null;
    }
}

// Outstanding balance (sum of unpaid bills)
$outstanding = 0;
if ($member_id) {
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(payment_amount), 0) FROM billing WHERE member_id = ? AND payment_status IN ('Pending','Overdue')");
    $stmt->execute([$member_id]);
    $outstanding = $stmt->fetchColumn() ?: 0;
}

// Payment history
$payments = [];
if ($member_id) {
    $stmt = $pdo->prepare('SELECT b.*, mp.plan_type FROM billing b LEFT JOIN membership_plans mp ON b.plan_id = mp.plan_id WHERE b.member_id = ? ORDER BY b.created_at DESC, b.due_date DESC');
    $stmt->execute([$member_id]);
    $payments = $stmt->fetchAll();
}

// Next due date from billing table
$next_due = null;
if ($member_id) {
    // Get the next upcoming due date or the most recent one
    $stmt = $pdo->prepare("SELECT due_date FROM billing WHERE member_id = ? ORDER BY due_date DESC LIMIT 1");
    $stmt->execute([$member_id]);
    $next_due = $stmt->fetchColumn() ?: null;
}

$page_title = 'Billing - UEP Fitness Gym';
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

.billing-container {
  max-width: 1280px;
  margin: 0 auto;
  padding: 2rem 1rem;
}

.billing-header {
  margin-bottom: 3rem;
  animation: slideIn 0.6s ease;
}

.billing-title {
  font-size: 2.5rem;
  font-weight: 800;
  margin-bottom: 0.5rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  letter-spacing: -0.5px;
}

.billing-subtitle {
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

.stat-card-red {
  background: linear-gradient(135deg, #fef2f2 0%, #fce4ec 100%);
}

.stat-card-red:hover {
  box-shadow: 0 30px 60px -15px rgba(239, 68, 68, 0.15);
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

.stat-card-red::before {
  background: #ef4444;
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

.stat-card-red .stat-icon {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
  box-shadow: 0 8px 16px -4px rgba(239, 68, 68, 0.3);
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

.status-badge {
  display: inline-block;
  padding: 0.625rem 1rem;
  font-size: 0.8rem;
  font-weight: 700;
  border-radius: 0.5rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  transition: all 0.3s ease;
}

.status-paid {
  background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
  color: #15803d;
  box-shadow: 0 4px 12px rgba(22, 163, 74, 0.15);
}

.status-pending {
  background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
  color: #92400e;
  box-shadow: 0 4px 12px rgba(180, 83, 9, 0.15);
}

.status-overdue {
  background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
  color: #7f1d1d;
  box-shadow: 0 4px 12px rgba(153, 27, 27, 0.15);
}

.status-cancelled {
  background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
  color: #4b5563;
  box-shadow: 0 4px 12px rgba(75, 85, 99, 0.1);
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
  .billing-title {
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
}
</style>

<div class="billing-container">
  <div class="billing-header">
    <h2 class="billing-title">Billing & Payments</h2>
    <p class="billing-subtitle">View your payment history and manage subscriptions</p>
  </div>

  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-content">
        <div class="stat-icon">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"/>
          </svg>
        </div>
        <div class="stat-label">Current Plan</div>
        <div class="stat-value" style="font-size: 1.25rem;"><?php echo htmlspecialchars($member['membership_plan'] ?? 'N/A'); ?></div>
        <?php if (!empty($member['plan_duration'])): ?>
          <div class="stat-subtext">Duration: <span class="stat-subtext-bold"><?php echo htmlspecialchars($member['plan_duration']); ?></span></div>
        <?php endif; ?>
        <?php if (!empty($member['plan_price'])): ?>
          <div class="stat-subtext">Price: <span class="stat-subtext-bold">₱<?php echo number_format($member['plan_price'], 2); ?></span></div>
        <?php endif; ?>
      </div>
    </div>

    <div class="stat-card stat-card-red">
      <div class="stat-content">
        <div class="stat-icon">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
          </svg>
        </div>
        <div class="stat-label">Outstanding Balance</div>
        <div class="stat-value" style="font-size: 2rem;">₱<?php echo number_format($outstanding, 2); ?></div>
      </div>
    </div>

    <div class="stat-card stat-card-amber">
      <div class="stat-content">
        <div class="stat-icon">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5A2.25 2.25 0 0 1 5.25 5.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25M3 18.75A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75M3 18.75v-8.25A2.25 2.25 0 0 1 5.25 8.25h13.5A2.25 2.25 0 0 1 21 10.5v8.25"/>
          </svg>
        </div>
        <div class="stat-label">Next Due Date</div>
        <div class="stat-value" style="font-size: 1.125rem;"><?php echo $next_due ? date('M d, Y', strtotime($next_due)) : 'No upcoming dues'; ?></div>
      </div>
    </div>
  </div>

  <!-- Payment History Table -->
  <div class="table-card">
    <div class="table-header">
      <h3 class="table-title">Payment History</h3>
    </div>
    <div class="table-wrapper">
      <table class="table">
        <thead>
          <tr>
            <th>Billing ID</th>
            <th>Type</th>
            <th>Amount</th>
            <th>Due Date</th>
            <th>Payment Date</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($payments)): ?>
            <tr>
              <td colspan="6" style="text-align: center; padding: 3rem;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="width: 3rem; height: 3rem; margin: 0 auto 0.75rem; color: #d1d5db; stroke-width: 1.5;">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                </svg>
                <p>No payment records found</p>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($payments as $row): ?>
              <tr>
                <td>
                  <strong><?php echo htmlspecialchars($row['billing_id']); ?></strong>
                </td>
                <td>
                  <?php echo htmlspecialchars($row['plan_type'] ?? 'N/A'); ?>
                </td>
                <td>
                  <strong>₱<?php echo number_format($row['payment_amount'], 2); ?></strong>
                </td>
                <td>
                  <?php echo $row['due_date'] ? date('M d, Y', strtotime($row['due_date'])) : '-'; ?>
                </td>
                <td>
                  <?php echo $row['payment_date'] ? date('M d, Y', strtotime($row['payment_date'])) : '-'; ?>
                </td>
                <td>
                  <?php
                  $status = htmlspecialchars($row['payment_status']);
                  $status_class = match($status) {
                    'Paid' => 'status-paid',
                    'Pending' => 'status-pending',
                    'Overdue' => 'status-overdue',
                    'Cancelled' => 'status-cancelled',
                    default => 'status-cancelled'
                  };
                  ?>
                  <span class="status-badge <?php echo $status_class; ?>">
                    <?php echo $status; ?>
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div style="margin-top: 1.5rem; padding: 1rem; background-color: #eff6ff; border: 1px solid #bfdbfe; border-radius: 0.75rem;">
    <p style="font-size: 0.875rem; color: #1e40af;">
      <strong>Note:</strong> For official receipts, please contact the front desk.
    </p>
  </div>
</div>

<?php include '../footer.php'; ?>
