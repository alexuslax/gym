<?php
// billing_view.php
require_once '../config/functions.php';

$billing_id = isset($_GET['billing_id']) ? sanitizeInput($_GET['billing_id']) : '';
if (!$billing_id) {
    echo '<div style="padding:2rem;text-align:center;">No billing record selected.</div>';
    exit;
}

// Fetch billing record with member and plan details
$sql = "SELECT b.*, m.first_name, m.middle_name, m.last_name, m.member_id, p.plan_name, p.plan_type, p.price, p.duration_days
        FROM billing b
        JOIN members m ON b.member_id = m.member_id
        LEFT JOIN membership_plans p ON b.plan_id = p.plan_id
        WHERE b.billing_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$billing_id]);
$record = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$record) {
    echo '<div style="padding:2rem;text-align:center;">Billing record not found.</div>';
    exit;
}

$fullname = trim($record['first_name'] . ' ' . $record['middle_name'] . ' ' . $record['last_name']);
$plan = $record['plan_name'] ? $record['plan_name'] . ' (' . $record['plan_type'] . ')' : 'N/A';
$price = $record['price'] ? '₱' . number_format($record['price'], 2) : '₱' . number_format($record['payment_amount'], 2);
$due = $record['due_date'] ? date('F d, Y', strtotime($record['due_date'])) : 'N/A';
$status = $record['payment_status'] ?? 'Pending';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Billing Details - UEP Fitness Gym</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f7f7f7; margin: 0; }
        .bill-container {
            width: 210mm; min-height: 297mm; max-width: 100vw;
            margin: 2rem auto; background: #fff; border-radius: 10px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08); padding: 2rem;
            box-sizing: border-box;
        }
        .bill-header, .bill-header-center { text-align: center; margin-bottom: 1.5rem; }
        .bill-header h2 { margin: 0 0 0.5rem 0; font-size: 2rem; color: #374151; }
        .bill-header p { margin: 0; color: #6b7280; font-size: 1.1rem; }
        .bill-header-center { margin-bottom: 1.2rem; }
        .bill-header-center > * { text-align: center !important; margin-left: auto; margin-right: auto; }
        .bill-table { width: 100%; border-collapse: collapse; margin-bottom: 1.5rem; }
        .bill-table th, .bill-table td { padding: 0.7em 0.5em; text-align: left; }
        .bill-table th { color: #6b7280; font-weight: 500; font-size: 1em; }
        .bill-table td { color: #374151; font-size: 1.05em; }
        .bill-total { text-align: right; font-size: 1.2em; font-weight: bold; color: #2563eb; margin-top: 1.5rem; }
        .bill-status { display: inline-block; padding: 0.3em 1em; border-radius: 20px; font-size: 1em; font-weight: 500; background: #e0e7ff; color: #3730a3; margin-bottom: 1.5rem; }
        .bill-status.paid {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #34d399;
        }
        .bill-status.pending {
            background: #fffbeb;
            color: #92400e;
            border: 1px solid #fbbf24;
        }
        .bill-status.overdue {
            background: #fff1f2;
            color: #991b1b;
            border: 1px solid #f87171;
        }
        .bill-actions { text-align: center; margin-top: 2rem; }
        .bill-actions button { background: linear-gradient(90deg, #6366f1 0%, #60a5fa 100%); color: #fff; border: none; border-radius: 6px; padding: 0.5em 1.5em; font-size: 1em; font-weight: 500; margin: 0 0.2em; box-shadow: 0 2px 8px rgba(99,102,241,0.08); cursor: pointer; transition: background 0.2s; }
        .bill-actions button:hover { background: linear-gradient(90deg, #60a5fa 0%, #6366f1 100%); }
        @media (max-width: 600px) { .bill-container { padding: 1rem; width: 100vw; min-width: unset; } }
        @media print {
            body { background: #fff !important; }
            .bill-container { box-shadow: none; border-radius: 0; margin: 0; width: 210mm; min-height: 297mm; padding: 0.5in 0.7in; }
            .bill-actions { display: none !important; }
        }
        @page {
            margin: 0;
        }
        /* Center the logo and headers on print */
        @media print {
            .bill-header-center { justify-content: center !important; text-align: center !important; margin-left: 0 !important; }
            .bill-header-center > * { text-align: center !important; margin-left: auto !important; margin-right: auto !important; }
        }
    </style>
</head>
<body>
    <div class="bill-container">
        <div class="bill-header-center" style="position: relative; margin-bottom: 1.2rem; min-height: 80px;">
            <img src="../img/logo.jpg" alt="UEP Logo" style="height: 80px; width: 80px; object-fit: contain; display: block; position: absolute; left: 50px; top: 0;">
            <div style="text-align: center;">
                <div style="font-size: 1.05em; color: #374151; font-weight: 500; letter-spacing: 0.5px;">Republic of the Philippines</div>
                <div style="font-size: 1em; color: #1e293b; font-weight: bold; letter-spacing: 0.5px;">UNIVERSITY OF EASTERN PHILIPPINES</div>
                <div style="font-size: 1em; color: #374151;">University Town, Northern Samar</div>
                <div style="font-size: 1.1em; color: #2563eb; font-weight: 500; margin-top: 0.2em;">University Fitness Gym</div>
            </div>
        </div>
        <div class="bill-header">
            <div style="border-top: 2px solid #374151; width: 100%; margin-bottom: 0.7rem;"></div>
            <h3 style="margin-top: 0.7rem;">Billing Statement</h3>
        </div>
        <?php $statusClass = strtolower($status); ?>
        <div class="bill-status <?php echo in_array($statusClass, ['paid','pending','overdue']) ? $statusClass : ''; ?>">Status: <?php echo htmlspecialchars($status); ?></div>
        <table class="bill-table">
            <tr><th>Bill ID:</th><td><?php echo htmlspecialchars($record['billing_id']); ?></td></tr>
            <tr><th>Member:</th><td>
                <?php echo htmlspecialchars($fullname); ?><br>
                <span style="font-size:0.97em;color:#6b7280;">Member ID: <?php echo htmlspecialchars($record['member_id']); ?></span>
            </td></tr>
            <tr><th>Plan:</th><td><?php echo htmlspecialchars($plan); ?></td></tr>
            <tr><th>Due Date:</th><td><?php echo htmlspecialchars($due); ?></td></tr>
            <tr><th>Description:</th><td><?php echo htmlspecialchars($record['description']); ?></td></tr>
        </table>
        <div class="bill-total">Total: <?php echo $price; ?></div>
        <div class="bill-actions">
            <button onclick="window.print()">🖨️ Print Bill</button>
            <button onclick="window.location.href='billing.php'">Close</button>
        </div>
    </div>
</body>
</html>
