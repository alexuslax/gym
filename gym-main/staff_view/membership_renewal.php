<?php
session_start();
require_once '../config/functions.php';

// Check if user is staff or admin
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['staff', 'admin'])) {
    header('Location: ../login.php');
    exit;
}

// Get filter parameters
$renewal_status = isset($_GET['renewal_status']) ? sanitizeInput($_GET['renewal_status']) : 'upcoming'; // upcoming, expired, all
$days_range = isset($_GET['days_range']) ? intval($_GET['days_range']) : 30; // days until expiry

// Build query based on status
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
        m.membership_start_date,
        m.membership_end_date,
        mp.plan_name,
        mp.price,
        mp.duration_days,
        DATEDIFF(m.membership_end_date, CURDATE()) as days_remaining,
        CASE 
            WHEN m.membership_end_date IS NULL THEN 'No Date'
            WHEN DATEDIFF(m.membership_end_date, CURDATE()) < 0 THEN 'Expired'
            WHEN DATEDIFF(m.membership_end_date, CURDATE()) = 0 THEN 'Expires Today'
            WHEN DATEDIFF(m.membership_end_date, CURDATE()) <= ? THEN 'Expiring Soon'
            ELSE 'Active'
        END as renewal_status_label
    FROM members m
    LEFT JOIN membership_plans mp ON m.membership_plan = mp.plan_id
    WHERE m.membership_status = 'Active'
";

$params = [$days_range];

if ($renewal_status === 'upcoming') {
    // Members expiring in the next X days
    $query .= " AND m.membership_end_date IS NOT NULL
                AND m.membership_end_date >= CURDATE()
                AND DATEDIFF(m.membership_end_date, CURDATE()) <= ?
                ORDER BY m.membership_end_date ASC";
    $params[] = $days_range;
} elseif ($renewal_status === 'expired') {
    // Members with expired membership
    $query .= " AND m.membership_end_date IS NOT NULL
                AND m.membership_end_date < CURDATE()
                ORDER BY m.membership_end_date DESC";
} else {
    // All active members with renewal info
    $query .= " AND m.membership_end_date IS NOT NULL
                ORDER BY m.membership_end_date ASC";
}

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $renewal_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Renewal query error: ' . $e->getMessage());
    $renewal_list = [];
}

// Get statistics
$stats = [];
try {
    // Count expiring soon (within 30 days)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count FROM members 
        WHERE membership_status = 'Active' 
        AND membership_end_date IS NOT NULL
        AND membership_end_date >= CURDATE()
        AND DATEDIFF(membership_end_date, CURDATE()) <= 30
    ");
    $stmt->execute();
    $stats['expiring_soon'] = $stmt->fetch()['count'];

    // Count expired
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count FROM members 
        WHERE membership_status = 'Active' 
        AND membership_end_date IS NOT NULL
        AND membership_end_date < CURDATE()
    ");
    $stmt->execute();
    $stats['expired'] = $stmt->fetch()['count'];

    // Count active (not expiring soon)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count FROM members 
        WHERE membership_status = 'Active' 
        AND membership_end_date IS NOT NULL
        AND DATEDIFF(membership_end_date, CURDATE()) > 30
    ");
    $stmt->execute();
    $stats['active'] = $stmt->fetch()['count'];
} catch (PDOException $e) {
    error_log('Stats query error: ' . $e->getMessage());
}

$page_title = 'Membership Renewal Status - UEP Fitness Gym';
include '../header.php';
?>

<style>
    .page-header {
        margin-bottom: 2.5rem;
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
    }

    .page-subtitle {
        color: #64748b;
        font-size: 1rem;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        background: white;
        padding: 1.5rem;
        border-radius: 0.75rem;
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .stat-card.warning {
        border-left: 4px solid #f59e0b;
        background: linear-gradient(135deg, #fef3c7 0%, #fffbeb 100%);
    }

    .stat-card.danger {
        border-left: 4px solid #ef4444;
        background: linear-gradient(135deg, #fee2e2 0%, #fef2f2 100%);
    }

    .stat-card.success {
        border-left: 4px solid #10b981;
        background: linear-gradient(135deg, #d1fae5 0%, #f0fdf4 100%);
    }

    .stat-value {
        font-size: 2rem;
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 0.25rem;
    }

    .stat-label {
        font-size: 0.875rem;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .card {
        background: white;
        border-radius: 0.75rem;
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .filters {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .form-select {
        padding: 0.5rem 0.75rem;
        border: 1px solid #e2e8f0;
        border-radius: 0.375rem;
        font-size: 0.875rem;
        background-color: white;
    }

    .form-select:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .btn {
        padding: 0.5rem 1rem;
        border: none;
        border-radius: 0.375rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
    }

    .btn-primary {
        background: #667eea;
        color: white;
    }

    .btn-primary:hover {
        background: #5568d3;
    }

    .btn-secondary {
        background: #e2e8f0;
        color: #0f172a;
    }

    .btn-secondary:hover {
        background: #cbd5e1;
    }

    .table-container {
        overflow-x: auto;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.875rem;
    }

    table thead {
        background: #f8fafc;
        border-bottom: 2px solid #e2e8f0;
    }

    table th {
        padding: 1rem;
        text-align: left;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    table td {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #e2e8f0;
        color: #334155;
    }

    table tbody tr:hover {
        background: #f8fafc;
    }

    .badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .badge-warning {
        background: #fef3c7;
        color: #92400e;
    }

    .badge-danger {
        background: #fee2e2;
        color: #991b1b;
    }

    .badge-success {
        background: #d1fae5;
        color: #065f46;
    }

    .empty-state {
        text-align: center;
        padding: 3rem;
        color: #64748b;
    }

    .empty-state svg {
        width: 4rem;
        height: 4rem;
        margin: 0 auto 1rem;
        opacity: 0.3;
    }

    @media (max-width: 768px) {
        .page-title {
            font-size: 1.875rem;
        }

        .stats-grid {
            grid-template-columns: 1fr;
        }

        table {
            font-size: 0.75rem;
        }

        table th, table td {
            padding: 0.5rem;
        }

        .filters {
            grid-template-columns: 1fr;
        }
    }
</style>

<div style="max-width: 1280px; margin: 0 auto; padding: 2rem 1rem;">
    <div class="page-header">
        <h1 class="page-title">Membership Renewal Status</h1>
        <p class="page-subtitle">Track and manage member renewals</p>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card warning">
            <div class="stat-value"><?php echo $stats['expiring_soon'] ?? 0; ?></div>
            <div class="stat-label">Expiring Soon (30 days)</div>
        </div>
        <div class="stat-card danger">
            <div class="stat-value"><?php echo $stats['expired'] ?? 0; ?></div>
            <div class="stat-label">Expired Members</div>
        </div>
        <div class="stat-card success">
            <div class="stat-value"><?php echo $stats['active'] ?? 0; ?></div>
            <div class="stat-label">Active Members</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card">
        <h3 style="margin-bottom: 1rem; font-weight: 600; color: #0f172a;">Filters</h3>
        <form method="GET" class="filters">
            <div>
                <label style="display: block; font-size: 0.875rem; margin-bottom: 0.5rem; color: #64748b; font-weight: 600;">Renewal Status</label>
                <select name="renewal_status" class="form-select" style="width: 100%;">
                    <option value="upcoming" <?php echo $renewal_status === 'upcoming' ? 'selected' : ''; ?>>Expiring Soon</option>
                    <option value="expired" <?php echo $renewal_status === 'expired' ? 'selected' : ''; ?>>Expired</option>
                    <option value="all" <?php echo $renewal_status === 'all' ? 'selected' : ''; ?>>All</option>
                </select>
            </div>
            <div>
                <label style="display: block; font-size: 0.875rem; margin-bottom: 0.5rem; color: #64748b; font-weight: 600;">Days Range</label>
                <select name="days_range" class="form-select" style="width: 100%;">
                    <option value="7" <?php echo $days_range == 7 ? 'selected' : ''; ?>>7 days</option>
                    <option value="14" <?php echo $days_range == 14 ? 'selected' : ''; ?>>14 days</option>
                    <option value="30" <?php echo $days_range == 30 ? 'selected' : ''; ?>>30 days</option>
                    <option value="60" <?php echo $days_range == 60 ? 'selected' : ''; ?>>60 days</option>
                </select>
            </div>
            <div style="display: flex; gap: 0.5rem;">
                <button type="submit" class="btn btn-primary">Apply Filters</button>
                <a href="?renewal_status=upcoming&days_range=30" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>

    <!-- Renewal List -->
    <div class="card">
        <h3 style="margin-bottom: 1rem; font-weight: 600; color: #0f172a;">Members List</h3>
        
        <?php if (count($renewal_list) > 0): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Member Name</th>
                            <th>Member ID</th>
                            <th>Type</th>
                            <th>Plan</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Days Remaining</th>
                            <th>Status</th>
                            <th>Contact</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($renewal_list as $member): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($member['member_id']); ?></td>
                                <td><?php echo htmlspecialchars(ucfirst($member['member_type'] ?? 'N/A')); ?></td>
                                <td><?php echo htmlspecialchars($member['plan_name'] ?? 'N/A'); ?></td>
                                <td><?php echo $member['membership_start_date'] ? date('M d, Y', strtotime($member['membership_start_date'])) : 'N/A'; ?></td>
                                <td><?php echo $member['membership_end_date'] ? date('M d, Y', strtotime($member['membership_end_date'])) : 'N/A'; ?></td>
                                <td>
                                    <?php 
                                    if ($member['days_remaining'] < 0) {
                                        echo '<strong style="color: #ef4444;">' . abs($member['days_remaining']) . ' days ago</strong>';
                                    } else {
                                        echo '<strong>' . $member['days_remaining'] . ' days</strong>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    if ($member['days_remaining'] < 0) {
                                        echo '<span class="badge badge-danger">Expired</span>';
                                    } elseif ($member['days_remaining'] <= 7) {
                                        echo '<span class="badge badge-danger">Expires in ' . $member['days_remaining'] . ' days</span>';
                                    } elseif ($member['days_remaining'] <= 30) {
                                        echo '<span class="badge badge-warning">Expiring Soon</span>';
                                    } else {
                                        echo '<span class="badge badge-success">Active</span>';
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($member['email'] ?? $member['contact_number'] ?? 'N/A'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p style="margin-top: 1rem; color: #64748b; font-size: 0.875rem;">
                Showing <?php echo count($renewal_list); ?> member(s)
            </p>
        <?php else: ?>
            <div class="empty-state">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
                <p style="font-size: 1.125rem; margin-bottom: 0.5rem;">No members found</p>
                <p style="font-size: 0.875rem;">Try adjusting your filters or check back later.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../footer.php'; ?>
