<?php
session_start();
require_once '../config/functions.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_billing':
                try {
                    $member_id = sanitizeInput($_POST['member_id']);
                    $plan_id = isset($_POST['plan_id']) ? sanitizeInput($_POST['plan_id']) : null;
                    $amount = isset($_POST['amount']) ? sanitizeInput($_POST['amount']) : 0;
                    $due_date = sanitizeInput($_POST['due_date']);
                    $description = sanitizeInput($_POST['description'] ?? '');
                    $apply_credit = isset($_POST['apply_credit']) ? (int)$_POST['apply_credit'] : 0;
                    // Additional charges (JSON array of {name,amount})
                    $additional_charges_json = isset($_POST['additional_charges']) ? $_POST['additional_charges'] : '[]';
                    $additional_charges = json_decode($additional_charges_json, true);
                    if (!is_array($additional_charges)) $additional_charges = [];

                    // Get billing_type and price from selected plan if available
                    $billing_type = '';
                    $billing_amount = floatval($amount);
                    if ($plan_id) {
                        $plan_stmt = $pdo->prepare("SELECT plan_type, price FROM membership_plans WHERE plan_id = ?");
                        $plan_stmt->execute([$plan_id]);
                        $plan_row = $plan_stmt->fetch(PDO::FETCH_ASSOC);
                        if ($plan_row) {
                            $billing_type = $plan_row['plan_type'];
                            $billing_amount = floatval($plan_row['price']);
                        }
                    }

                    // Add additional charges amounts
                    $sum_add = 0.0;
                    foreach ($additional_charges as $c) {
                        $amt = isset($c['amount']) ? floatval($c['amount']) : 0.0;
                        $sum_add += $amt;
                    }
                    $billing_amount += $sum_add;

                    // Apply credit if checked
                    $credit_applied = 0.0;
                    if ($apply_credit) {
                        $stmt = $pdo->prepare("SELECT credit_balance FROM members WHERE member_id = ?");
                        $stmt->execute([$member_id]);
                        $member_credit = floatval($stmt->fetchColumn() ?? 0);
                        
                        if ($member_credit > 0) {
                            $credit_applied = min($member_credit, $billing_amount);
                            $billing_amount -= $credit_applied;
                            // Deduct credit from member balance
                            $stmt = $pdo->prepare("UPDATE members SET credit_balance = credit_balance - ? WHERE member_id = ?");
                            $stmt->execute([$credit_applied, $member_id]);
                        }
                    }

                    // Append charges detail to description
                    if (!empty($additional_charges)) {
                        $charges_note = 'Additional charges: ' . json_encode($additional_charges);
                        $description = trim(($description ? $description . "\n" : '') . $charges_note);
                    }
                    if ($credit_applied > 0) {
                        $description = trim(($description ? $description . "\n" : '') . 'Credit applied: ₱' . number_format($credit_applied, 2));
                    }

                    $stmt = $pdo->prepare("INSERT INTO billing (member_id, plan_id, billing_amount, due_date, description, created_by) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$member_id, $plan_id, $billing_amount, $due_date, $description, $_SESSION['user_id']]);
                    
                    // Get the last inserted billing_id (auto-increment)
                    $billing_id = $pdo->lastInsertId();
                    
                    // Check if it's an AJAX request
                    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                        header('Content-Type: application/json');
                        echo json_encode([
                            'success' => true,
                            'billing_id' => $billing_id,
                            'billing_amount' => floatval($billing_amount),
                            'due_date' => $due_date
                        ]);
                        exit();
                    } else {
                        header('Location: billing_view.php?billing_id=' . $billing_id);
                        exit();
                    }
                } catch (PDOException $e) {
                    header('Location: billing.php?error=' . urlencode('Error adding billing record: ' . $e->getMessage()));
                    exit();
                } catch (Exception $e) {
                    header('Location: billing.php?error=' . urlencode('Error: ' . $e->getMessage()));
                    exit();
                }
                break;
                
            case 'update_payment':
                $billing_id = sanitizeInput($_POST['billing_id']);
                $payment_date = sanitizeInput($_POST['payment_date']);
                $transaction_id = sanitizeInput($_POST['transaction_id']);
                
                $stmt = $pdo->prepare("UPDATE billing SET transaction_id = ?, payment_status = 'Paid' WHERE billing_id = ?");
                $stmt->execute([$transaction_id, $billing_id]);
                
                header('Location: billing.php?success=Payment recorded successfully');
                exit();
                break;
                
            case 'add_payment':
                try {
                    $billing_id = sanitizeInput($_POST['selected_billing_id'] ?? ($_POST['billing_id'] ?? ''));
                    $member_id = sanitizeInput($_POST['member_id'] ?? '');
                    $payment_amount = floatval(sanitizeInput($_POST['payment_amount'] ?? 0));
                    $payment_date = sanitizeInput($_POST['payment_date'] ?? '');
                    $transaction_id = sanitizeInput($_POST['transaction_id'] ?? '');
                    $payment_method = sanitizeInput($_POST['payment_method'] ?? '');
                    $payment_type = sanitizeInput($_POST['payment_type'] ?? 'full');
                    $installment_no = isset($_POST['installment_no']) ? intval($_POST['installment_no']) : 0;
                    $note = sanitizeInput($_POST['note'] ?? '');
                    $apply_credit = isset($_POST['apply_credit']) ? (int)$_POST['apply_credit'] : 0;

                    if ($billing_id === '' || $member_id === '') {
                        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                            header('Content-Type: application/json');
                            echo json_encode(['success' => false, 'error' => 'Missing billing or member ID.']);
                            exit();
                        }
                        header('Location: billing.php?error=' . urlencode('Missing billing or member ID.'));
                        exit();
                    }

                    if ($payment_amount <= 0) {
                        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                            header('Content-Type: application/json');
                            echo json_encode(['success' => false, 'error' => 'Payment amount must be greater than 0.']);
                            exit();
                        }
                        header('Location: billing.php?error=' . urlencode('Payment amount must be greater than 0.'));
                        exit();
                    }

                    if ($payment_date === '') {
                        $payment_date = date('Y-m-d H:i:s');
                    } else {
                        // Normalize date-only values to include time
                        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $payment_date)) {
                            $payment_date .= ' 00:00:00';
                        }
                    }
                    
                    // Apply credit if checked
                    $credit_to_deduct = 0.0;
                    if ($apply_credit) {
                        $stmt = $pdo->prepare("SELECT credit_balance FROM members WHERE member_id = ?");
                        $stmt->execute([$member_id]);
                        $member_credit = floatval($stmt->fetchColumn() ?? 0);
                        
                        if ($member_credit > 0) {
                            $credit_to_deduct = min($member_credit, $payment_amount);
                            // Deduct credit from member balance
                            $stmt = $pdo->prepare("UPDATE members SET credit_balance = credit_balance - ? WHERE member_id = ?");
                            $stmt->execute([$credit_to_deduct, $member_id]);
                        }
                    }
                    
                    // Insert payment record
                    $stmt = $pdo->prepare("INSERT INTO payments (billing_id, member_id, payment_amount, payment_date, transaction_id, payment_method, payment_type, installment_no, note, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$billing_id, $member_id, $payment_amount, $payment_date, $transaction_id, $payment_method, $payment_type, $installment_no, $note, $_SESSION['user_id']]);
                    
                    // Calculate total paid for this billing
                    $stmt = $pdo->prepare("SELECT COALESCE(SUM(payment_amount), 0) as total_paid FROM payments WHERE billing_id = ?");
                    $stmt->execute([$billing_id]);
                    $total_paid = floatval($stmt->fetchColumn());
                    
                    // Get billing amount
                    $stmt = $pdo->prepare("SELECT billing_amount, plan_id FROM billing WHERE billing_id = ?");
                    $stmt->execute([$billing_id]);
                    $billing_row = $stmt->fetch(PDO::FETCH_ASSOC);
                    $billing_amount = floatval($billing_row['billing_amount'] ?? 0);
                    $plan_id = $billing_row['plan_id'] ?? null;
                    
                    // Update billing payment status
                    if ($total_paid >= $billing_amount) {
                        $stmt = $pdo->prepare("UPDATE billing SET payment_status = 'Paid' WHERE billing_id = ?");
                        $stmt->execute([$billing_id]);
                        
                        // Calculate overpayment (advance payment) and add to credit balance
                        $overpayment = $total_paid - $billing_amount;
                        if ($overpayment > 0.01) { // Use small threshold to avoid floating point issues
                            $stmt = $pdo->prepare("UPDATE members SET credit_balance = credit_balance + ? WHERE member_id = ?");
                            $stmt->execute([$overpayment, $member_id]);
                        }
                        
                        // Activate membership plan if plan_id exists
                        if ($plan_id) {
                            $stmt = $pdo->prepare("SELECT duration_days FROM membership_plans WHERE plan_id = ?");
                            $stmt->execute([$plan_id]);
                            $plan = $stmt->fetch(PDO::FETCH_ASSOC);
                            
                            if ($plan) {
                                $start_date = date('Y-m-d');
                                $end_date = date('Y-m-d', strtotime('+' . $plan['duration_days'] . ' days'));
                                
                                // Update member's plan dates
                                $stmt = $pdo->prepare("UPDATE members SET membership_plan = ?, membership_start_date = ?, membership_end_date = ?, membership_status = 'Active' WHERE member_id = ?");
                                $stmt->execute([$plan_id, $start_date, $end_date, $member_id]);
                            }
                        }
                    } elseif ($total_paid > 0) {
                        $stmt = $pdo->prepare("UPDATE billing SET payment_status = 'Partial' WHERE billing_id = ?");
                        $stmt->execute([$billing_id]);
                    }
                    
                    // Return success for AJAX
                    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => true, 'message' => 'Payment recorded successfully']);
                        exit();
                    } else {
                        header('Location: billing.php?success=Payment recorded successfully');
                        exit();
                    }
                } catch (PDOException $e) {
                    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
                        exit();
                    } else {
                        header('Location: billing.php?error=' . urlencode('Error recording payment: ' . $e->getMessage()));
                        exit();
                    }
                }
                break;
        }
    }
}

// Get search parameters
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$type_filter = isset($_GET['type']) ? sanitizeInput($_GET['type']) : '';

// AJAX: return recent bills for a specific member
if (isset($_GET['member_id']) && isset($_GET['fetch_recent'])) {
    $mid = sanitizeInput($_GET['member_id']);
    $stmt = $pdo->prepare("SELECT billing_id, billing_amount, due_date, payment_status, created_at FROM billing WHERE member_id = ? ORDER BY created_at DESC LIMIT 10");
    $stmt->execute([$mid]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'bills' => $rows]);
    exit();
}

// AJAX: return bill details with payment info
if (isset($_GET['billing_id']) && isset($_GET['fetch_details'])) {
    $bid = sanitizeInput($_GET['billing_id']);
    $stmt = $pdo->prepare("SELECT b.billing_amount, b.plan_id, p.price as plan_amount FROM billing b LEFT JOIN membership_plans p ON b.plan_id = p.plan_id WHERE b.billing_id = ?");
    $stmt->execute([$bid]);
    $bill = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($bill) {
        // Calculate total paid
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(payment_amount), 0) as total_paid FROM payments WHERE billing_id = ?");
        $stmt->execute([$bid]);
        $paid = floatval($stmt->fetchColumn());
        
        $billing_amount = floatval($bill['billing_amount']);
        $plan_amount = floatval($bill['plan_amount'] ?? 0);
        $balance = max(0, $billing_amount - $paid);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'billing_amount' => $billing_amount,
            'plan_amount' => $plan_amount,
            'paid_amount' => $paid,
            'balance' => $balance
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Bill not found']);
    }
    exit();
}

// AJAX: return payment history for a billing
if (isset($_GET['billing_id']) && isset($_GET['fetch_payments'])) {
    $bid = sanitizeInput($_GET['billing_id']);
    $stmt = $pdo->prepare("SELECT payment_id, payment_amount, payment_date, payment_type, transaction_id, payment_method FROM payments WHERE billing_id = ? ORDER BY payment_date DESC");
    $stmt->execute([$bid]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'payments' => $payments]);
    exit();
}

// AJAX: return member credit balance
if (isset($_GET['member_id']) && isset($_GET['fetch_credit'])) {
    $mid = sanitizeInput($_GET['member_id']);
    $stmt = $pdo->prepare("SELECT credit_balance FROM members WHERE member_id = ?");
    $stmt->execute([$mid]);
    $credit = floatval($stmt->fetchColumn() ?? 0);
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'credit_balance' => $credit]);
    exit();
}

// AJAX: return latest vital signs for a member
if (isset($_GET['member_id']) && isset($_GET['fetch_vitals'])) {
    $mid = sanitizeInput($_GET['member_id']);
    $stmt = $pdo->prepare("SELECT date_of_recording, weight, height_cm, bmi, blood_pressure_systolic, blood_pressure_diastolic, heart_rate, trainer_id FROM vital_signs WHERE member_id = ? ORDER BY date_of_recording DESC LIMIT 1");
    $stmt->execute([$mid]);
    $vitals = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($vitals) {
        // Get trainer name
        $trainer_name = '';
        if (!empty($vitals['trainer_id'])) {
            $trainer_stmt = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) as name FROM trainers WHERE trainer_id = ?");
            $trainer_stmt->execute([$vitals['trainer_id']]);
            $trainer_result = $trainer_stmt->fetch(PDO::FETCH_ASSOC);
            $trainer_name = $trainer_result['name'] ?? '-';
        }
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'date_of_recording' => $vitals['date_of_recording'] ?? '-',
            'weight' => !empty($vitals['weight']) ? $vitals['weight'] : '-',
            'height' => !empty($vitals['height_cm']) ? $vitals['height_cm'] : '-',
            'bmi' => !empty($vitals['bmi']) ? $vitals['bmi'] : '-',
            'blood_pressure_systolic' => $vitals['blood_pressure_systolic'] ?? '-',
            'blood_pressure_diastolic' => $vitals['blood_pressure_diastolic'] ?? '-',
            'heart_rate' => $vitals['heart_rate'] ?? '-',
            'trainer_name' => $trainer_name
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'No vital signs recorded']);
    }
    exit();
}

// AJAX: Record vital signs for a member
if (isset($_POST['action']) && $_POST['action'] === 'add_vital_signs') {
    $member_id = sanitizeInput($_POST['member_id']);
    $date_of_recording = sanitizeInput($_POST['date_of_recording']);
    $weight = !empty($_POST['weight']) ? floatval($_POST['weight']) : null;
    $height = !empty($_POST['height']) ? floatval($_POST['height']) : null;
    $bmi = !empty($_POST['bmi']) ? floatval($_POST['bmi']) : null;
    $blood_pressure_systolic = !empty($_POST['blood_pressure_systolic']) ? intval($_POST['blood_pressure_systolic']) : null;
    $blood_pressure_diastolic = !empty($_POST['blood_pressure_diastolic']) ? intval($_POST['blood_pressure_diastolic']) : null;
    $heart_rate = !empty($_POST['heart_rate']) ? intval($_POST['heart_rate']) : null;
    $trainer_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    
    try {
        // Generate unique record_id
        $record_id = uniqid('VS_', true);
        
        $stmt = $pdo->prepare("INSERT INTO vital_signs (record_id, member_id, date_of_recording, weight, height_cm, bmi, blood_pressure_systolic, blood_pressure_diastolic, heart_rate, trainer_id, created_at) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$record_id, $member_id, $date_of_recording, $weight, $height, $bmi, $blood_pressure_systolic, $blood_pressure_diastolic, $heart_rate, $trainer_id]);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Vital signs recorded successfully']);
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Failed to record vital signs: ' . $e->getMessage()]);
    }
    exit();
}

// Build query
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "((CONCAT(m.first_name, ' ', m.middle_name, ' ', m.last_name)) LIKE ? OR b.billing_id LIKE ? OR b.transaction_id LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($status_filter)) {
    $where_conditions[] = "b.payment_status = ?";
    $params[] = $status_filter;
}

// Type filter - skip if billing_type column doesn't exist in database
// Uncomment and adjust column name if needed
// if (!empty($type_filter)) {
//     $where_conditions[] = "b.billing_type = ?";
//     $params[] = $type_filter;
// }

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get billing records - use b.* to get all columns, alias billing_amount as amount for compatibility
$sql = "SELECT b.*, 
    b.billing_amount as amount,
    m.first_name, m.middle_name, m.last_name,
    p.plan_type, p.plan_name
    FROM billing b 
    JOIN members m ON b.member_id COLLATE utf8mb4_unicode_ci = m.member_id COLLATE utf8mb4_unicode_ci 
    LEFT JOIN membership_plans p ON b.plan_id = p.plan_id
    $where_clause 
    ORDER BY b.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$billing_records = $stmt->fetchAll();

// Get billing statistics
$stats = [
    'total_revenue' => 0,
    'pending_payments' => 0,
    'overdue_payments' => 0,
    'this_month_revenue' => 0
];

$stmt = $pdo->query("SELECT 
    SUM(CASE WHEN payment_status = 'Paid' THEN billing_amount ELSE 0 END) as total_revenue,
    COUNT(CASE WHEN payment_status = 'Pending' THEN 1 END) as pending_payments,
    COUNT(CASE WHEN payment_status = 'Overdue' THEN 1 END) as overdue_payments,
    SUM(CASE WHEN payment_status = 'Paid' AND MONTH(payment_date) = MONTH(CURDATE()) AND YEAR(payment_date) = YEAR(CURDATE()) THEN billing_amount ELSE 0 END) as this_month_revenue
    FROM billing");
$stats = $stmt->fetch();

// Get all members for dropdown
$stmt = $pdo->query("SELECT member_id, first_name, middle_name, last_name FROM members WHERE membership_status = 'Active' ORDER BY first_name, last_name");
$active_members = $stmt->fetchAll();

// Get billing for editing
?>
  <?php $page_title = 'Billing Management - UEP Fitness Gym'; include '../header.php'; ?>

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
</style>

        <!-- Success Message -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="alert-icon">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                </svg>
                <span class="alert-message"><?php echo htmlspecialchars($_GET['success']); ?></span>
            </div>
        <?php endif; ?>
        
        <!-- Error Message -->
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="alert-icon">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                </svg>
                <span class="alert-message"><?php echo htmlspecialchars($_GET['error']); ?></span>
            </div>
        <?php endif; ?>

        <!-- Page Header -->
        <div class="page-header">
            <h2 class="page-title">Billing Management</h2>
            <p class="page-subtitle">Manage member payments, subscriptions, and billing records.</p>
        </div>

        <div style="margin-bottom: 2rem;">
            <button type="button" id="addBillingBtn" class="btn btn-primary" style="cursor: pointer; z-index: 10;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1.25rem; height: 1.25rem;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                Add Billing Record
            </button>
        </div>

        <!-- Billing Statistics -->
        <div class="stats-grid" style="grid-template-columns: repeat(4, minmax(0, 1fr)); gap: var(--spacing-6); margin-bottom: var(--spacing-8);">
                <div class="card-stats" style="background: #e6f4ea; border-left: 5px solid #34d399;">
                    <div class="stats-decoration" style="position: absolute; background: #bbf7d0;"></div>
                    <div style="position: relative;">
                        <div class="stats-icon-container" style="background: #34d399;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" style="color: #ffffff; width: 1.5rem; height: 1.5rem;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"/>
                        </svg>
                    </div>
                    <h3 class="stats-label">Total Revenue</h3>
                    <p class="stats-value">₱<?php echo number_format($stats['total_revenue'], 2); ?></p>
                </div>
            </div>
            <div class="card-stats" style="background: #e0f2fe; border-left: 5px solid #38bdf8;">
                <div class="stats-decoration" style="position: absolute; background: #bae6fd;"></div>
                <div style="position: relative;">
                    <div class="stats-icon-container" style="background: #38bdf8;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" style="color: #ffffff; width: 1.5rem; height: 1.5rem;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5A2.25 2.25 0 0 1 5.25 5.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25M3 18.75A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75M3 18.75v-8.25A2.25 2.25 0 0 1 5.25 8.25h13.5A2.25 2.25 0 0 1 21 10.5v8.25"/>
                        </svg>
                    </div>
                    <h3 class="stats-label">This Month</h3>
                    <p class="stats-value">₱<?php echo number_format($stats['this_month_revenue'], 2); ?></p>
                </div>
            </div>
            <div class="card-stats" style="background: #fef9c3; border-left: 5px solid #fde047;">
                <div class="stats-decoration" style="position: absolute; background: #fef08a;"></div>
                <div style="position: relative;">
                    <div class="stats-icon-container" style="background: #fde047;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" style="color: #ffffff; width: 1.5rem; height: 1.5rem;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                        </svg>
                    </div>
                    <h3 class="stats-label">Pending Payments</h3>
                    <p class="stats-value"><?php echo $stats['pending_payments']; ?></p>
                </div>
            </div>
            <div class="card-stats" style="background: #fce7f3; border-left: 5px solid #fb7185;">
                <div class="stats-decoration" style="position: absolute; background: #fda4af;"></div>
                <div style="position: relative;">
                    <div class="stats-icon-container" style="background: #fb7185;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" style="color: #ffffff; width: 1.5rem; height: 1.5rem;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                        </svg>
                    </div>
                    <h3 class="stats-label">Overdue Payments</h3>
                    <p class="stats-value"><?php echo $stats['overdue_payments']; ?></p>
                </div>
            </div>
        </div>

        <!-- Search and Filters -->
        <div class="card" style="margin-bottom: var(--spacing-8);">
            <h3 class="card-header-title" style="margin-bottom: var(--spacing-4);">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="card-header-icon" style="color: var(--blue-600);">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607z"/>
                </svg>
                Search & Filter Billing
            </h3>
            <form method="GET" style="display: grid; grid-template-columns: repeat(1, minmax(0, 1fr)); gap: var(--spacing-4);">
                <style>
                    @media (min-width: 768px) {
                        form[method="GET"] {
                            grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
                        }
                    }
                </style>
                <div style="position: relative;">
                    <input type="text" name="search" placeholder="Search by name, ID, or transaction" value="<?php echo htmlspecialchars($search); ?>" class="form-input" style="padding-left: 2.5rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1.25rem; height: 1.25rem; color: var(--gray-400); position: absolute; left: 0.75rem; top: 0.875rem;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607z"/>
                    </svg>
                </div>
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="Pending" <?php echo $status_filter == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="Paid" <?php echo $status_filter == 'Paid' ? 'selected' : ''; ?>>Paid</option>
                    <option value="Overdue" <?php echo $status_filter == 'Overdue' ? 'selected' : ''; ?>>Overdue</option>
                    <option value="Cancelled" <?php echo $status_filter == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
                <select name="type" class="form-select">
                    <option value="">All Types</option>
                    <option value="Membership" <?php echo $type_filter == 'Membership' ? 'selected' : ''; ?>>Membership</option>
                    <option value="Personal Training" <?php echo $type_filter == 'Personal Training' ? 'selected' : ''; ?>>Personal Training</option>
                    <option value="Equipment Rental" <?php echo $type_filter == 'Equipment Rental' ? 'selected' : ''; ?>>Equipment Rental</option>
                    <option value="Other" <?php echo $type_filter == 'Other' ? 'selected' : ''; ?>>Other</option>
                </select>
                <button type="submit" class="btn btn-primary">
                    Search
                </button>
            </form>
        </div>

        <!-- Billing Records -->
        <div class="card" style="overflow: hidden;">
            <div class="card-header">
                <h3 class="card-header-title">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="card-header-icon" style="color: var(--blue-600);">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 7.5A2.25 2.25 0 0 1 4.5 5.25h15A2.25 2.25 0 0 1 21.75 7.5v9A2.25 2.25 0 0 1 19.5 18.75h-15A2.25 2.25 0 0 1 2.25 16.5v-9z"/>
                    </svg>
                    Billing Records
                </h3>
            </div>
            <div style="overflow-x: auto;">
                <table class="table">
                    <thead class="table-header">
                        <tr>
                            <th style="text-align: center; width: 8%;">Billing ID</th>
                            <th style="text-align: center; width: 18%;">Member</th>
                            <th style="text-align: center;">Type</th>
                            <th style="text-align: center;">Amount</th>
                            <th style="text-align: center;">Due Date</th>
                            <th style="text-align: center;">Payment Date</th>
                            <th style="text-align: center;">Status</th>
                            <th style="text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($billing_records as $record): ?>
                        <tr>
                            <td style="text-align: center;">
                                <span><?php echo htmlspecialchars($record['billing_id']); ?></span>
                            </td>
                            <td>
                                <div>
                                    <div style="font-size: 0.9em;"><?php echo htmlspecialchars(trim($record['first_name'] . ' ' . $record['middle_name'] . ' ' . $record['last_name'])); ?></div>
                                    <div style="font-size: 0.75em; opacity: 0.6;"><?php echo htmlspecialchars($record['member_id']); ?></div>
                                </div>
                            </td>
                            <td style="text-align: center;">
                                <span><?php echo isset($record['plan_type']) ? htmlspecialchars($record['plan_type']) : ''; ?></span>
                            </td>
                            <td style="text-align: center;">
                                <span>₱<?php echo number_format($record['amount'], 2); ?></span>
                            </td>
                            <td style="text-align: center;">
                                <span><?php echo formatDate($record['due_date']); ?></span>
                            </td>
                            <td style="text-align: center;">
                                <span>
                                    <?php echo $record['payment_date'] ? formatDate($record['payment_date']) : '<span>-</span>'; ?>
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <?php
                                    $rs = isset($record['payment_status']) ? strtolower($record['payment_status']) : '';
                                    $badgeStyle = 'display:inline-block;padding:0.25em 0.65em;border-radius:999px;font-weight:600;font-size:0.95em;';
                                    if ($rs === 'paid') {
                                        $badgeStyle .= 'background:#ecfdf5;color:#065f46;border:1px solid #34d399;';
                                    } elseif ($rs === 'pending') {
                                        $badgeStyle .= 'background:#fffbeb;color:#92400e;border:1px solid #fbbf24;';
                                    } elseif ($rs === 'overdue') {
                                        $badgeStyle .= 'background:#fff1f2;color:#991b1b;border:1px solid #f87171;';
                                    } else {
                                        $badgeStyle .= 'background:#f3f4f6;color:#374151;border:1px solid #e5e7eb;';
                                    }
                                ?>
                                <span style="<?php echo $badgeStyle; ?>"><?php echo htmlspecialchars($record['payment_status']); ?></span>
                            </td>
                            <td style="text-align: center;">
                                <div style="display: flex; justify-content: center; align-items: center; gap: 0.3rem;">
                                <a href="billing_view.php?billing_id=<?php echo urlencode($record['billing_id']); ?>" onclick="event.preventDefault(); window.location.href=this.href;" style="background: linear-gradient(90deg, #6366f1 0%, #60a5fa 100%); color: #fff; border: none; border-radius: 4px; padding: 0.3em 0.7em; font-size: 0.85em; font-weight: 500; margin: 0 0.1em; box-shadow: 0 1px 4px rgba(99,102,241,0.08); cursor: pointer; transition: background 0.2s; display: inline-flex; align-items: center; gap: 0.3em; text-decoration: none;">
                                    🧾 <span>Generate Bill</span>
                                </a>
                                <?php if (strtolower($record['payment_status']) !== 'paid'): ?>
                                <button onclick="openPaymentModal('<?php echo htmlspecialchars($record['billing_id']); ?>', '<?php echo htmlspecialchars(trim($record['first_name'] . ' ' . $record['middle_name'] . ' ' . $record['last_name']) . ' (' . $record['member_id'] . ')'); ?>', '<?php echo isset($record['plan_type']) ? htmlspecialchars($record['plan_type']) : 'N/A'; ?>', '<?php echo $record['amount']; ?>', '<?php echo formatDate($record['due_date']); ?>', '<?php echo htmlspecialchars($record['payment_status']); ?>')" style="background: linear-gradient(90deg, #10b981 0%, #34d399 100%); color: #fff; border: none; border-radius: 4px; padding: 0.3em 0.7em; font-size: 0.85em; font-weight: 500; margin: 0 0.1em; box-shadow: 0 1px 4px rgba(16,185,129,0.08); cursor: pointer; transition: background 0.2s; display: flex; align-items: center; gap: 0.3em;">
                                    💸 <span>Record Payment</span>
                                </button>
                                <?php else: ?>
                                <button disabled style="background: #e5e7eb; color: #9ca3af; border: none; border-radius: 4px; padding: 0.3em 1em; font-size: 0.85em; font-weight: 500; margin: 0 0.1em; cursor: not-allowed; display: flex; align-items: center; gap: 0.3em; opacity: 0.6; min-width: 145px; justify-content: center;">
                                    ✓ <span>Paid</span>
                                </button>
                                <?php endif; ?>
                                    <!-- Generate Bill Modal -->
                                    <div id="generateBillModal" class="modal-overlay" style="display: none;" onclick="if(event.target === this) closeGenerateBillModal()">
                                        <div class="modal-content" style="max-width: 28rem;" onclick="event.stopPropagation()">
                                            <div class="modal-header">
                                                <h3 class="modal-title">Generate Bill</h3>
                                                <button type="button" onclick="closeGenerateBillModal()" class="modal-close-btn">
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1.5rem; height: 1.5rem;">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                <p style="margin-bottom: 1rem;">This will generate a printable bill for the selected record.</p>
                                                <div id="billDetails" style="background: #f9f9f9; padding: 1rem; border-radius: 6px; margin-bottom: 1rem; font-size: 1rem;">
                                                    <!-- Bill details will be loaded here -->
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" onclick="closeGenerateBillModal()" class="btn-secondary">Close</button>
                                                    <button type="button" onclick="printBill()" class="btn btn-primary">Print Bill</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <script>
                                    function openGenerateBillModal(billingId) {
                                        var modal = document.getElementById('generateBillModal');
                                        var details = document.getElementById('billDetails');
                                        modal.dataset.billingId = billingId;
                                        details.innerHTML = '<strong>Loading bill details...</strong>';
                                        setTimeout(function() {
                                            details.innerHTML = '<strong>Bill ID:</strong> ' + billingId + '<br>' +
                                                '<strong>Member:</strong> (Member details here)<br>' +
                                                '<strong>Type:</strong> (Type here)<br>' +
                                                '<strong>Amount:</strong> (Amount here)<br>' +
                                                '<strong>Due Date:</strong> (Due date here)';
                                        }, 400);
                                        modal.style.display = 'flex';
                                        modal.classList.add('show');
                                    }
                                    function closeGenerateBillModal() {
                                        var modal = document.getElementById('generateBillModal');
                                        modal.style.display = 'none';
                                        modal.classList.remove('show');
                                    }
                                    function printBill() {
                                        var modal = document.getElementById('generateBillModal');
                                        var billingId = modal ? modal.dataset.billingId : null;
                                        if (billingId) {
                                            // Navigate to the billing view in the same tab (no new window)
                                            window.location.href = 'billing_view.php?billing_id=' + encodeURIComponent(billingId);
                                        } else {
                                            // Fallback: just print the modal contents inline
                                            window.print();
                                        }
                                    }
                                    </script>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Add/Edit Billing Modal -->
    <div id="billingModal" class="modal-overlay" style="display: none;" onclick="if(event.target === this) closeModal()">
        <div class="modal-content" style="max-width: 28rem;" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">Add Billing Record</h3>
                <button type="button" id="closeBillingBtn" class="modal-close-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1.5rem; height: 1.5rem;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
                </div>
                <form method="POST" class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="add_billing">
                    <input type="hidden" name="billing_id" id="editBillingId">
                    
                    <div class="modal-form-spacing">
                        <!-- Member Information -->
                        <div class="form-group">
                            <label class="form-label">Member <span class="required">*</span></label>
                            <select name="member_id" id="member_id" required class="form-select">
                                <option value="">Select Member</option>
                                <?php foreach ($active_members as $member): ?>
                                    <option value="<?php echo $member['member_id']; ?>">
                                        <?php echo htmlspecialchars(trim($member['first_name'] . ' ' . $member['middle_name'] . ' ' . $member['last_name']) . ' (' . $member['member_id'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <!-- Membership Plan -->
                        <div class="form-group">
                            <label class="form-label">Membership Plan</label>
                            <select name="plan_id" id="plan_id" class="form-select" onchange="updatePlanDetails()">
                                <option value="">Select Plan</option>
                                <?php
                                $plans = $pdo->query("SELECT plan_id, plan_name, plan_type, price, duration_days FROM membership_plans WHERE is_active = 1 ORDER BY price, plan_name")->fetchAll();
                                foreach ($plans as $plan): ?>
                                    <option value="<?php echo $plan['plan_id']; ?>" data-price="<?php echo $plan['price']; ?>" data-duration="<?php echo $plan['duration_days']; ?>">
                                        <?php echo htmlspecialchars($plan['plan_name'] . ' (' . $plan['plan_type'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Price</label>
                            <input type="text" id="plan_price_display" class="form-input" readonly>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Duration (days)</label>
                            <input type="text" id="plan_duration_display" class="form-input" readonly>
                        </div>
                        <!-- Status (hidden, always pending) -->
                        <input type="hidden" name="payment_status" value="Pending">
                        <!-- Due Date and Description -->
                        <div class="form-group">
                            <label class="form-label">Due Date <span class="required">*</span></label>
                            <input type="date" name="due_date" id="due_date" required class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Description</label>
                            <textarea name="description" id="description" rows="3" class="form-textarea"></textarea>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">
                            Save
                        </button>
                        <script>
                        // Update plan details display and auto-fill due date
                        function updatePlanDetails() {
                            var planSelect = document.getElementById('plan_id');
                            var selected = planSelect.options[planSelect.selectedIndex];
                            document.getElementById('plan_price_display').value = selected.getAttribute('data-price') || '';
                            var duration = selected.getAttribute('data-duration') || '';
                            document.getElementById('plan_duration_display').value = duration;
                            // Auto-fill due date if duration is set
                            if (duration) {
                                var today = new Date();
                                today.setHours(0,0,0,0);
                                var dueDate = new Date(today.getTime() + (parseInt(duration) * 24 * 60 * 60 * 1000));
                                var yyyy = dueDate.getFullYear();
                                var mm = String(dueDate.getMonth() + 1).padStart(2, '0');
                                var dd = String(dueDate.getDate()).padStart(2, '0');
                                document.getElementById('due_date').value = yyyy + '-' + mm + '-' + dd;
                            } else {
                                document.getElementById('due_date').value = '';
                            }
                        }
                        </script>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Payment Modal -->
    <div id="paymentModal" class="modal-overlay" style="display: none;" onclick="if(event.target === this) closePaymentModal()">
        <div class="modal-content" style="max-width: 28rem;" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h3 class="modal-title">Record Payment</h3>
                <button type="button" onclick="closePaymentModal()" class="modal-close-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1.5rem; height: 1.5rem;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
                </div>
                <form method="POST" class="modal-body">
                    <input type="hidden" name="action" value="update_payment">
                    <input type="hidden" name="billing_id" id="paymentBillingId">
                    
                    <div class="modal-form-spacing">
                        <!-- Billing Information Section -->
                        <div style="background: #f9fafb; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">
                            <h4 style="font-size: 0.95em; font-weight: 600; color: #374151; margin-bottom: 0.75rem;">Billing Information</h4>
                            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.75rem; font-size: 0.9em;">
                                <div>
                                    <div style="color: #6b7280; font-size: 0.85em;">Billing ID</div>
                                    <div style="color: #111827; font-weight: 500;" id="paymentBillingIdDisplay">-</div>
                                </div>
                                <div>
                                    <div style="color: #6b7280; font-size: 0.85em;">Status</div>
                                    <div style="color: #111827; font-weight: 500;" id="paymentStatusDisplay">-</div>
                                </div>
                                <div style="grid-column: span 2;">
                                    <div style="color: #6b7280; font-size: 0.85em;">Member</div>
                                    <div style="color: #111827; font-weight: 500;" id="paymentMemberDisplay">-</div>
                                </div>
                                <div>
                                    <div style="color: #6b7280; font-size: 0.85em;">Membership Plan</div>
                                    <div style="color: #111827; font-weight: 500;" id="paymentPlanDisplay">-</div>
                                </div>
                                <div>
                                    <div style="color: #6b7280; font-size: 0.85em;">Amount</div>
                                    <div style="color: #2563eb; font-weight: 600; font-size: 1.1em;" id="paymentAmountDisplay">-</div>
                                </div>
                                <div>
                                    <div style="color: #6b7280; font-size: 0.85em;">Due Date</div>
                                    <div style="color: #111827; font-weight: 500;" id="paymentDueDateDisplay">-</div>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Details Section -->
                        <h4 style="font-size: 0.95em; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">Payment Details</h4>
                        <div class="form-group">
                            <label class="form-label">Payment Date <span class="required">*</span></label>
                            <input type="date" name="payment_date" id="payment_date" value="<?php echo date('Y-m-d'); ?>" required class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Transaction ID / Receipt Number <span style="font-size: 0.75rem; font-weight: normal; color: var(--gray-500);">(Optional)</span></label>
                            <input type="text" name="transaction_id" class="form-input" placeholder="Enter transaction or receipt number">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Notes / Description <span style="font-size: 0.75rem; font-weight: normal; color: var(--gray-500);">(Optional)</span></label>
                            <textarea name="notes" rows="3" class="form-textarea" placeholder="Any additional remarks or notes about this payment"></textarea>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="submit" class="btn" style="background: linear-gradient(to right, var(--green-600), var(--green-700)); color: white;">
                            💰 Record Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Modal functions - Define immediately and ensure they're available
        function openAddModal() {
            console.log('=== openAddModal called ===');
            const modal = document.getElementById('billingModal');
            console.log('Modal element:', modal);
            if (!modal) {
                console.error('Billing modal not found!');
                alert('Error: Billing modal not found. Please refresh the page.');
                return;
            }
            // Reset form fields for add
            document.getElementById('modalTitle').textContent = 'Add Billing Record';
            document.getElementById('formAction').value = 'add_billing';
            document.getElementById('editBillingId').value = '';
            document.querySelector('#billingModal form').reset();
            modal.classList.add('show');
            modal.style.removeProperty('display'); // <-- Fix: remove inline display:none
        }
        
        // Also set as window property to ensure it's globally accessible
        window.openAddModal = openAddModal;
        
        // Ensure function is available after DOM loads (in case footer.php overrides it)
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                window.openAddModal = openAddModal;
                console.log('openAddModal function registered on DOMContentLoaded');
            });
        } else {
            // DOM already loaded
            window.openAddModal = openAddModal;
            console.log('openAddModal function registered (DOM already loaded)');
        }
        
        // Also override after footer.php loads (if it runs)
        setTimeout(function() {
            window.openAddModal = openAddModal;
            console.log('openAddModal function re-registered after 100ms');
        }, 100);
        
        setTimeout(function() {
            window.openAddModal = openAddModal;
            console.log('openAddModal function re-registered after 500ms');
        }, 500);
        
        setTimeout(function() {
            window.openAddModal = openAddModal;
            console.log('openAddModal function re-registered after 1000ms');
        }, 1000);
        
        // Also override on window load
        window.addEventListener('load', function() {
            window.openAddModal = openAddModal;
            console.log('openAddModal function re-registered on window load');
        });
        
        // Add event listener to Add Billing Record button (copy logic from equipment.php)
        document.addEventListener('DOMContentLoaded', function() {
            const addBtn = document.getElementById('addBillingBtn');
            if (addBtn) {
                addBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    // Show modal directly
                    const modal = document.getElementById('billingModal');
                    if (!modal) {
                        console.error('Billing modal not found!');
                        return;
                    }
                    // Set form values
                    const modalTitle = document.getElementById('modalTitle');
                    const formAction = document.getElementById('formAction');
                    const editBillingId = document.getElementById('editBillingId');
                    if (modalTitle) modalTitle.textContent = 'Add Billing Record';
                    if (formAction) formAction.value = 'add_billing';
                    if (editBillingId) editBillingId.value = '';
                    // Reset form
                    const form = document.querySelector('#billingModal form');
                    if (form) {
                        form.reset();
                    }
                    // Show modal - force with inline styles
                    modal.classList.add('show');
                    modal.style.display = 'flex';
                    modal.style.visibility = 'visible';
                    modal.style.opacity = '1';
                    modal.style.position = 'fixed';
                    modal.style.inset = '0';
                    modal.style.zIndex = '9999';
                    modal.style.alignItems = 'center';
                    modal.style.justifyContent = 'center';
                    modal.style.backgroundColor = 'rgba(0, 0, 0, 0.6)';
                    modal.style.backdropFilter = 'blur(4px)';
                    modal.style.padding = 'var(--spacing-4)';
                });
            }
        });

        function closeModal() {
            const modal = document.getElementById('billingModal');
            if (modal) {
                modal.classList.remove('show');
                modal.style.display = 'none';
                modal.style.removeProperty('visibility');
                modal.style.removeProperty('opacity');
                modal.style.removeProperty('position');
                modal.style.removeProperty('inset');
                modal.style.removeProperty('z-index');
                modal.style.removeProperty('align-items');
                modal.style.removeProperty('justify-content');
                modal.style.removeProperty('background-color');
                modal.style.removeProperty('backdrop-filter');
                modal.style.removeProperty('padding');
            }
        }

        function openPaymentModal(billingId, member, plan, amount, dueDate, status) {
            const modal = document.getElementById('paymentModal');
            if (!modal) {
                console.error('Payment modal not found!');
                return;
            }
            
            // Set form fields
            document.getElementById('paymentBillingId').value = billingId;
            
            // Display billing information
            document.getElementById('paymentBillingIdDisplay').textContent = billingId;
            document.getElementById('paymentMemberDisplay').textContent = member || 'N/A';
            document.getElementById('paymentPlanDisplay').textContent = plan || 'N/A';
            document.getElementById('paymentAmountDisplay').textContent = '₱' + parseFloat(amount).toFixed(2);
            document.getElementById('paymentDueDateDisplay').textContent = dueDate || 'N/A';
            document.getElementById('paymentStatusDisplay').textContent = status || 'Pending';
            
            // Reset payment date to today
            document.getElementById('payment_date').value = new Date().toISOString().split('T')[0];
            
            // Show modal - force with inline styles to override any CSS conflicts
            modal.classList.add('show');
            modal.style.display = 'flex';
            modal.style.visibility = 'visible';
            modal.style.opacity = '1';
            modal.style.position = 'fixed';
            modal.style.inset = '0';
            modal.style.zIndex = '9999';
            modal.style.alignItems = 'center';
            modal.style.justifyContent = 'center';
            modal.style.backgroundColor = 'rgba(0, 0, 0, 0.6)';
            modal.style.backdropFilter = 'blur(4px)';
            modal.style.padding = 'var(--spacing-4)';
        }

        function closePaymentModal() {
            const modal = document.getElementById('paymentModal');
            if (modal) {
                modal.classList.remove('show');
                // Remove inline styles to allow CSS to control display
                modal.style.removeProperty('display');
                modal.style.removeProperty('opacity');
                modal.style.removeProperty('visibility');
                modal.style.removeProperty('z-index');
                modal.style.removeProperty('position');
                modal.style.removeProperty('inset');
                modal.style.removeProperty('align-items');
                modal.style.removeProperty('justify-content');
                modal.style.removeProperty('background-color');
                modal.style.removeProperty('backdrop-filter');
                modal.style.removeProperty('padding');
            }
        }

        // Close modals when clicking outside
        const billingModal = document.getElementById('billingModal');
        const paymentModal = document.getElementById('paymentModal');
        
        if (billingModal) {
            billingModal.addEventListener('click', function(e) {
                if (e.target === billingModal) closeModal();
            });
        }
        
        if (paymentModal) {
            paymentModal.addEventListener('click', function(e) {
                if (e.target === paymentModal) closePaymentModal();
            });
        }

        // Add close button event listeners
        const closeBillingBtn = document.getElementById('closeBillingBtn');
        if (closeBillingBtn) {
            closeBillingBtn.addEventListener('click', closeModal);
        }

        // Close modals on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                if (billingModal && billingModal.classList.contains('show')) closeModal();
                if (paymentModal && paymentModal.classList.contains('show')) closePaymentModal();
            }
        });

        // Edit billing functionality removed

    </script>

 <?php include '../footer.php'; ?>
