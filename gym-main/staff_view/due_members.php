<?php
session_start();
require_once '../config/functions.php';

// Check if user is staff or admin
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['staff', 'admin'])) {
    header('Location: ../login.php');
    exit;
}

// Get filter parameters
$filter_status = isset($_GET['filter_status']) ? sanitizeInput($_GET['filter_status']) : 'due_soon'; // due_soon, expired, all

// Build query for due members
$query = "
    SELECT 
        m.member_id,
        m.first_name,
        m.last_name,
        m.email,
        m.contact_number,
        m.member_type,
        m.membership_plan,
        m.membership_status,
        m.membership_end_date,
        mp.plan_name,
        mp.price,
        DATEDIFF(m.membership_end_date, CURDATE()) as days_remaining,
        CASE 
            WHEN m.membership_end_date IS NULL THEN 'No Date Set'
            WHEN DATEDIFF(m.membership_end_date, CURDATE()) < 0 THEN 'EXPIRED'
            WHEN DATEDIFF(m.membership_end_date, CURDATE()) = 0 THEN 'Due Today'
            WHEN DATEDIFF(m.membership_end_date, CURDATE()) <= 7 THEN 'Due Soon (< 7 days)'
            WHEN DATEDIFF(m.membership_end_date, CURDATE()) <= 30 THEN 'Due Soon (< 30 days)'
            ELSE 'Active'
        END as due_status
    FROM members m
    LEFT JOIN membership_plans mp ON m.membership_plan = mp.plan_id
    WHERE m.membership_status = 'Active'
        AND m.membership_end_date IS NOT NULL
";

if ($filter_status === 'due_soon') {
    // Members expiring within 30 days
    $query .= " AND DATEDIFF(m.membership_end_date, CURDATE()) <= 30
                AND DATEDIFF(m.membership_end_date, CURDATE()) >= 0
                ORDER BY m.membership_end_date ASC";
} elseif ($filter_status === 'expired') {
    // Members with expired membership
    $query .= " AND DATEDIFF(m.membership_end_date, CURDATE()) < 0
                ORDER BY m.membership_end_date DESC";
} else {
    // All members with expiring dates
    $query .= " ORDER BY m.membership_end_date ASC";
}

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $due_members = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Due members query error: ' . $e->getMessage());
    $due_members = [];
}

// Get statistics
$stats = [];
try {
    // Count due soon (within 7 days)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count FROM members 
        WHERE membership_status = 'Active' 
        AND membership_end_date IS NOT NULL
        AND DATEDIFF(membership_end_date, CURDATE()) <= 7
        AND DATEDIFF(membership_end_date, CURDATE()) >= 0
    ");
    $stmt->execute();
    $stats['due_7days'] = $stmt->fetch()['count'];

    // Count due within 30 days
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count FROM members 
        WHERE membership_status = 'Active' 
        AND membership_end_date IS NOT NULL
        AND DATEDIFF(membership_end_date, CURDATE()) <= 30
        AND DATEDIFF(membership_end_date, CURDATE()) >= 0
    ");
    $stmt->execute();
    $stats['due_30days'] = $stmt->fetch()['count'];

    // Count expired
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count FROM members 
        WHERE membership_status = 'Active' 
        AND membership_end_date IS NOT NULL
        AND DATEDIFF(membership_end_date, CURDATE()) < 0
    ");
    $stmt->execute();
    $stats['expired'] = $stmt->fetch()['count'];
} catch (PDOException $e) {
    error_log('Stats query error: ' . $e->getMessage());
    $stats = ['due_7days' => 0, 'due_30days' => 0, 'expired' => 0];
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Due Members - Staff</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .status-due-7 {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .status-due-30 {
            background-color: #fef3c7;
            color: #92400e;
        }
        .status-expired {
            background-color: #fecaca;
            color: #7f1d1d;
        }
        .table-container {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        th {
            background-color: #f3f4f6;
            padding: 0.75rem;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #e5e7eb;
        }
        td {
            padding: 0.75rem;
            border-bottom: 1px solid #e5e7eb;
        }
        tr:hover {
            background-color: #f9fafb;
        }
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #1f2937;
        }
        .stat-label {
            font-size: 0.875rem;
            color: #6b7280;
        }
        .filter-buttons {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .filter-btn {
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            border: none;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
        }
        .filter-btn.active {
            background-color: #2563eb;
            color: white;
        }
        .filter-btn:not(.active) {
            background-color: #e5e7eb;
            color: #374151;
        }
        .filter-btn:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <?php include '../header.php'; ?>
    
    <div class="container mx-auto px-4 py-8">
        <div class="page-header mb-8">
            <h2 class="page-title">Due Members</h2>
            <p class="page-subtitle">Manage members with memberships that are due or have expired.</p>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
            <div class="stat-card" style="border-left: 4px solid #ef4444;">
                <div class="stat-number"><?php echo $stats['due_7days']; ?></div>
                <div class="stat-label">Due this week (7 days)</div>
            </div>
            <div class="stat-card" style="border-left: 4px solid #f59e0b;">
                <div class="stat-number"><?php echo $stats['due_30days']; ?></div>
                <div class="stat-label">Due within 30 days</div>
            </div>
            <div class="stat-card" style="border-left: 4px solid #dc2626;">
                <div class="stat-number"><?php echo $stats['expired']; ?></div>
                <div class="stat-label">Expired memberships</div>
            </div>
        </div>

        <!-- Generate Button -->
        <div class="card">
            <h3 class="text-lg font-semibold text-slate-900 mb-4">Generate Report</h3>
            <p class="text-gray-600 mb-6">Click the button below to generate a list of members that are due for renewal. You can then print or export the report.</p>
            
            <div style="display: flex; gap: 1rem;">
                <button type="button" id="generateBtn" class="btn btn-primary px-6" onclick="generateReport('due_soon')">
                    📋 Generate Due Soon Report
                </button>
                <button type="button" id="generateExpiredBtn" class="btn btn-primary px-6" onclick="generateReport('expired')">
                    ⚠️ Generate Expired Report
                </button>
                <button type="button" id="generateAllBtn" class="btn btn-primary px-6" onclick="generateReport('all')">
                    📊 Generate All Members Report
                </button>
            </div>
        </div>
    </div>

    <!-- Print Modal -->
    <div id="reportModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 0.5rem; max-width: 90%; max-height: 90%; width: 900px; display: flex; flex-direction: column; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
            <!-- Modal Header -->
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 1.5rem; border-bottom: 1px solid #e5e7eb;">
                <h3 id="reportTitle" style="font-size: 1.25rem; font-weight: 700; color: #1f2937; margin: 0;">Due Members Report</h3>
                <button type="button" onclick="closeReportModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #6b7280;">&times;</button>
            </div>

            <!-- Modal Body -->
            <div id="reportContent" style="flex: 1; overflow-y: auto; padding: 1.5rem;"></div>

            <!-- Modal Footer -->
            <div style="display: flex; justify-content: flex-end; gap: 0.75rem; padding: 1.5rem; border-top: 1px solid #e5e7eb;">
                <button type="button" onclick="printReport()" class="btn btn-primary px-6">🖨️ Print</button>
                <button type="button" onclick="closeReportModal()" class="btn btn-secondary px-6">Close</button>
            </div>
        </div>
    </div>

    <script>
        function generateReport(filterType) {
            // Get the members data from PHP
            const memberData = <?php echo json_encode($due_members); ?>;
            const filterStatus = '<?php echo htmlspecialchars($filter_status); ?>';
            
            // Filter data based on report type
            let filtered = memberData;
            let title = 'Due Members Report';
            
            if (filterType === 'due_soon') {
                filtered = memberData.filter(m => m.days_remaining >= 0 && m.days_remaining <= 30);
                title = 'Members Due Soon (Next 30 Days)';
            } else if (filterType === 'expired') {
                filtered = memberData.filter(m => m.days_remaining < 0);
                title = 'Expired Memberships';
            }
            
            // Build HTML table
            let html = '<h3 style="font-size: 1.5rem; font-weight: bold; margin-bottom: 1rem;">' + title + '</h3>';
            html += '<p style="color: #6b7280; margin-bottom: 1.5rem;">Generated on: ' + new Date().toLocaleDateString() + '</p>';
            
            if (filtered.length === 0) {
                html += '<p style="color: #9ca3af; text-align: center; padding: 2rem;">No members found for this filter.</p>';
            } else {
                html += '<table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">';
                html += '<thead>';
                html += '<tr style="background-color: #f3f4f6; border-bottom: 2px solid #e5e7eb;">';
                html += '<th style="padding: 0.75rem; text-align: left; border: 1px solid #e5e7eb;">Member ID</th>';
                html += '<th style="padding: 0.75rem; text-align: left; border: 1px solid #e5e7eb;">Name</th>';
                html += '<th style="padding: 0.75rem; text-align: left; border: 1px solid #e5e7eb;">Email</th>';
                html += '<th style="padding: 0.75rem; text-align: left; border: 1px solid #e5e7eb;">Plan</th>';
                html += '<th style="padding: 0.75rem; text-align: left; border: 1px solid #e5e7eb;">End Date</th>';
                html += '<th style="padding: 0.75rem; text-align: left; border: 1px solid #e5e7eb;">Days Remaining</th>';
                html += '<th style="padding: 0.75rem; text-align: left; border: 1px solid #e5e7eb;">Status</th>';
                html += '</tr>';
                html += '</thead>';
                html += '<tbody>';
                
                filtered.forEach(member => {
                    const endDate = new Date(member.membership_end_date).toLocaleDateString();
                    const daysText = member.days_remaining < 0 ? member.days_remaining + ' days ago' : member.days_remaining + ' days';
                    
                    html += '<tr style="border-bottom: 1px solid #e5e7eb;">';
                    html += '<td style="padding: 0.75rem; border: 1px solid #e5e7eb;"><strong>' + (member.member_id || 'N/A') + '</strong></td>';
                    html += '<td style="padding: 0.75rem; border: 1px solid #e5e7eb;">' + (member.first_name + ' ' + member.last_name) + '</td>';
                    html += '<td style="padding: 0.75rem; border: 1px solid #e5e7eb;">' + (member.email || 'N/A') + '</td>';
                    html += '<td style="padding: 0.75rem; border: 1px solid #e5e7eb;">' + (member.plan_name || 'N/A') + '</td>';
                    html += '<td style="padding: 0.75rem; border: 1px solid #e5e7eb;">' + endDate + '</td>';
                    html += '<td style="padding: 0.75rem; border: 1px solid #e5e7eb;"><strong>' + daysText + '</strong></td>';
                    html += '<td style="padding: 0.75rem; border: 1px solid #e5e7eb;"><span style="padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; background-color: #fee2e2; color: #991b1b;">' + (member.due_status || 'N/A') + '</span></td>';
                    html += '</tr>';
                });
                
                html += '</tbody>';
                html += '</table>';
                html += '<p style="margin-top: 1.5rem; color: #6b7280; font-size: 0.875rem;">Total Members: ' + filtered.length + '</p>';
            }
            
            // Update modal
            document.getElementById('reportTitle').textContent = title;
            document.getElementById('reportContent').innerHTML = html;
            document.getElementById('reportModal').style.display = 'flex';
        }
        
        function closeReportModal() {
            document.getElementById('reportModal').style.display = 'none';
        }
        
        function printReport() {
            const printWindow = window.open('', '', 'width=900,height=700');
            const content = document.getElementById('reportContent').innerHTML;
            const title = document.getElementById('reportTitle').innerText;
            
            printWindow.document.write('<html><head><title>' + title + '</title>');
            printWindow.document.write('<style>');
            printWindow.document.write('body { font-family: Arial, sans-serif; padding: 1rem; }');
            printWindow.document.write('table { width: 100%; border-collapse: collapse; margin-top: 1rem; }');
            printWindow.document.write('th, td { padding: 0.5rem; border: 1px solid #ccc; text-align: left; }');
            printWindow.document.write('th { background-color: #f3f4f6; font-weight: bold; }');
            printWindow.document.write('h3 { font-size: 1.5rem; margin-bottom: 0.5rem; }');
            printWindow.document.write('</style></head><body>');
            printWindow.document.write(content);
            printWindow.document.write('</body></html>');
            printWindow.document.close();
            printWindow.print();
        }
        
        // Close modal when clicking outside
        document.getElementById('reportModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeReportModal();
            }
        });
    </script>

    <?php include '../footer.php'; ?>
</body>
</html>
