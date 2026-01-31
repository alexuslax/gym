<?php
session_start();
require_once '../config/functions.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_equipment':
                $equipment_name = sanitizeInput($_POST['equipment_name']);
                $category = sanitizeInput($_POST['category']);
                $quantity_to_add = (int)sanitizeInput($_POST['quantity_to_add']);
                $purchase_date = sanitizeInput($_POST['purchase_date']);
                $purchase_price = sanitizeInput($_POST['purchase_price']);
                $notes = sanitizeInput($_POST['notes']) ?: '';
                
                // Check if this equipment type already exists (same name, category)
                $check_stmt = $pdo->prepare("SELECT equipment_id, total_quantity, quantity_available FROM equipment WHERE equipment_name = ? AND category = ?");
                $check_stmt->execute([$equipment_name, $category]);
                $existing = $check_stmt->fetch();
                
                if ($existing) {
                    // Equipment exists - update quantities and create new units
                    $new_total = $existing['total_quantity'] + $quantity_to_add;
                    $new_available = $existing['quantity_available'] + $quantity_to_add;
                    
                    // Get current max unit number
                    $max_unit_stmt = $pdo->prepare("SELECT MAX(unit_number) as max_unit FROM equipment_units WHERE equipment_id = ?");
                    $max_unit_stmt->execute([$existing['equipment_id']]);
                    $max_unit = $max_unit_stmt->fetchColumn() ?: 0;
                    
                    // Create new units
                    $unit_insert = $pdo->prepare("INSERT INTO equipment_units (equipment_id, unit_number, status, purchase_date) VALUES (?, ?, 'Available', ?)");
                    for ($i = 1; $i <= $quantity_to_add; $i++) {
                        $unit_insert->execute([$existing['equipment_id'], $max_unit + $i, $purchase_date]);
                    }
                    
                    // Update equipment totals
                    $update_stmt = $pdo->prepare("UPDATE equipment SET total_quantity = ?, quantity_available = ? WHERE equipment_id = ?");
                    $update_stmt->execute([$new_total, $new_available, $existing['equipment_id']]);
                    
                    header('Location: equipment.php?success=Equipment quantity updated successfully');
                } else {
                    // New equipment type - insert and create units
                    $status = 'Working'; // New equipment is always working
                    
                    $insert_stmt = $pdo->prepare("INSERT INTO equipment (equipment_name, category, purchase_date, purchase_price, status, quantity_available, total_quantity, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $insert_stmt->execute([$equipment_name, $category, $purchase_date, $purchase_price, $status, $quantity_to_add, $quantity_to_add, $notes]);
                    
                    $equipment_id = $pdo->lastInsertId();
                    
                    // Create units for new equipment (if trigger didn't auto-create them)
                    $check_units = $pdo->prepare("SELECT COUNT(*) as unit_count FROM equipment_units WHERE equipment_id = ?");
                    $check_units->execute([$equipment_id]);
                    $unit_count = $check_units->fetchColumn();
                    
                    // Only create units if they don't already exist (from trigger)
                    if ($unit_count == 0) {
                        $unit_insert = $pdo->prepare("INSERT INTO equipment_units (equipment_id, unit_number, status, purchase_date) VALUES (?, ?, 'Available', ?)");
                        for ($i = 1; $i <= $quantity_to_add; $i++) {
                            $unit_insert->execute([$equipment_id, $i, $purchase_date]);
                        }
                    }
                    
                    header('Location: equipment.php?success=Equipment added successfully');
                }
                exit();
                break;
                
            case 'update_equipment':
                $equipment_id = sanitizeInput($_POST['equipment_id']);
                $equipment_name = sanitizeInput($_POST['equipment_name']);
                $category = sanitizeInput($_POST['category']);
                $purchase_date = sanitizeInput($_POST['purchase_date']);
                $purchase_price = sanitizeInput($_POST['purchase_price']);
                $notes = sanitizeInput($_POST['notes']) ?: '';
                
                $stmt = $pdo->prepare("UPDATE equipment SET equipment_name=?, category=?, purchase_date=?, purchase_price=?, notes=? WHERE equipment_id=?");
                $stmt->execute([$equipment_name, $category, $purchase_date, $purchase_price, $notes, $equipment_id]);
                
                header('Location: equipment.php?success=Equipment updated successfully');
                exit();
                break;
                
            case 'delete_equipment':
                $equipment_id = sanitizeInput($_POST['equipment_id']);
                $stmt = $pdo->prepare("DELETE FROM equipment WHERE equipment_id = ?");
                $stmt->execute([$equipment_id]);
                
                header('Location: equipment.php?success=Equipment deleted successfully');
                exit();
                break;
                
            case 'add_maintenance':
                $equipment_id = sanitizeInput($_POST['equipment_id']);
                $maintenance_type = sanitizeInput($_POST['maintenance_type']);
                $unit_ids = isset($_POST['unit_ids']) ? $_POST['unit_ids'] : [];
                $description = sanitizeInput($_POST['description']);
                $cost = sanitizeInput($_POST['cost']);
                $performed_by = sanitizeInput($_POST['performed_by']);
                $maintenance_status = 'In Progress'; // Default status
                
                if (empty($unit_ids)) {
                    header('Location: equipment.php?error=Please select at least one unit');
                    exit();
                }
                
                // Insert maintenance record for each selected unit
                $stmt = $pdo->prepare("INSERT INTO equipment_maintenance (equipment_id, unit_id, maintenance_type, maintenance_date, description, cost, performed_by, status) VALUES (?, ?, ?, NOW(), ?, ?, ?, ?)");
                
                foreach ($unit_ids as $unit_id) {
                    $stmt->execute([$equipment_id, $unit_id, $maintenance_type, $description, $cost, $performed_by, $maintenance_status]);
                    
                    // Update unit status to Under Maintenance
                    $update_unit = $pdo->prepare("UPDATE equipment_units SET status = 'Under Maintenance', last_maintenance_date = CURDATE() WHERE unit_id = ?");
                    $update_unit->execute([$unit_id]);
                }
                
                // Update equipment availability counts
                $count_stmt = $pdo->prepare("SELECT 
                    COUNT(CASE WHEN status = 'Available' THEN 1 END) as available,
                    COUNT(*) as total
                    FROM equipment_units WHERE equipment_id = ?");
                $count_stmt->execute([$equipment_id]);
                $counts = $count_stmt->fetch();
                
                $update_equipment = $pdo->prepare("UPDATE equipment SET quantity_available = ?, total_quantity = ? WHERE equipment_id = ?");
                $update_equipment->execute([$counts['available'], $counts['total'], $equipment_id]);
                
                header('Location: equipment.php?success=Maintenance record added for ' . count($unit_ids) . ' unit(s)');
                exit();
                break;

            case 'complete_maintenance':
                $equipment_id = sanitizeInput($_POST['equipment_id']);
                $unit_ids = isset($_POST['unit_ids']) ? $_POST['unit_ids'] : [];
                
                if (empty($unit_ids)) {
                    header('Location: equipment.php?error=Please select at least one unit to complete');
                    exit();
                }
                
                // Mark selected units as completed
                $update_unit = $pdo->prepare("UPDATE equipment_units SET status = 'Available' WHERE unit_id = ? AND equipment_id = ?");
                
                foreach ($unit_ids as $unit_id) {
                    $update_unit->execute([$unit_id, $equipment_id]);
                    
                    // Update maintenance record status to completed
                    $update_maintenance = $pdo->prepare("UPDATE equipment_maintenance SET status = 'Completed', completion_date = NOW() WHERE unit_id = ? AND equipment_id = ? AND status != 'Completed'");
                    $update_maintenance->execute([$unit_id, $equipment_id]);
                }
                
                // Update equipment availability counts
                $count_stmt = $pdo->prepare("SELECT 
                    COUNT(CASE WHEN status = 'Available' THEN 1 END) as available,
                    COUNT(*) as total
                    FROM equipment_units WHERE equipment_id = ?");
                $count_stmt->execute([$equipment_id]);
                $counts = $count_stmt->fetch();
                
                $update_equipment = $pdo->prepare("UPDATE equipment SET quantity_available = ?, total_quantity = ? WHERE equipment_id = ?");
                $update_equipment->execute([$counts['available'], $counts['total'], $equipment_id]);
                
                header('Location: equipment.php?success=Maintenance completed for ' . count($unit_ids) . ' unit(s)');
                exit();
                break;

            case 'mark_broken':
                $equipment_id = sanitizeInput($_POST['equipment_id']);
                $unit_ids = isset($_POST['unit_ids']) ? $_POST['unit_ids'] : [];
                
                if (empty($unit_ids)) {
                    header('Location: equipment.php?error=Please select at least one unit to mark as broken');
                    exit();
                }
                
                // Create maintenance record for broken equipment
                $insert_maintenance = $pdo->prepare("INSERT INTO equipment_maintenance (equipment_id, unit_id, maintenance_type, maintenance_date, description, status) VALUES (?, ?, 'Repair', NOW(), 'Unit marked as out of order', 'Out of Order')");
                
                // Mark selected units as out of order
                $update_unit = $pdo->prepare("UPDATE equipment_units SET status = 'Out of Order' WHERE unit_id = ? AND equipment_id = ?");
                
                foreach ($unit_ids as $unit_id) {
                    // Create maintenance record
                    $insert_maintenance->execute([$equipment_id, $unit_id]);
                    
                    // Update unit status
                    $update_unit->execute([$unit_id, $equipment_id]);
                }
                
                // Update equipment availability counts
                $count_stmt = $pdo->prepare("SELECT 
                    COUNT(CASE WHEN status = 'Available' THEN 1 END) as available,
                    COUNT(*) as total
                    FROM equipment_units WHERE equipment_id = ?");
                $count_stmt->execute([$equipment_id]);
                $counts = $count_stmt->fetch();
                
                $update_equipment = $pdo->prepare("UPDATE equipment SET quantity_available = ?, total_quantity = ? WHERE equipment_id = ?");
                $update_equipment->execute([$counts['available'], $counts['total'], $equipment_id]);
                
                header('Location: equipment.php?success=' . count($unit_ids) . ' unit(s) marked as out of order');
                exit();
                break;
        }
    }
}

// Get search parameters
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$category_filter = isset($_GET['category']) ? sanitizeInput($_GET['category']) : '';

// Build query
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(equipment_name LIKE ? OR equipment_id LIKE ? OR brand LIKE ? OR model LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}


if (!empty($status_filter)) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
}

// Get equipment statistics (based on units)
$stats = [
    'total_equipment' => 0,
    'working' => 0,
    'maintenance' => 0,
    'repair' => 0
];

$stmt = $pdo->query("SELECT 
    (SELECT COUNT(*) FROM equipment_units) as total_equipment,
    (SELECT COUNT(*) FROM equipment_units WHERE status = 'Available') as working,
    (SELECT COUNT(*) FROM equipment_units WHERE status = 'Under Maintenance') as maintenance,
    (SELECT COUNT(*) FROM equipment_units WHERE status = 'Out of Order') as repair");
$stats = $stmt->fetch();

// Handle edit equipment
$edit_equipment = null;
if (isset($_GET['edit'])) {
    $edit_id = sanitizeInput($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM equipment WHERE equipment_id = ?");
    $stmt->execute([$edit_id]);
    $edit_equipment = $stmt->fetch();
}

// Get equipment with maintenance dates and unit counts
$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
$sql = "SELECT e.*, 
    (SELECT MAX(maintenance_date) FROM equipment_maintenance WHERE equipment_id = e.equipment_id) as last_maintenance,
    (SELECT MIN(next_maintenance_date) FROM equipment_maintenance WHERE equipment_id = e.equipment_id AND next_maintenance_date >= CURDATE()) as next_maintenance,
    (SELECT COUNT(*) FROM equipment_units WHERE equipment_id = e.equipment_id AND status = 'Available') as units_available,
    (SELECT COUNT(*) FROM equipment_units WHERE equipment_id = e.equipment_id AND status = 'Under Maintenance') as units_maintenance,
    (SELECT COUNT(*) FROM equipment_units WHERE equipment_id = e.equipment_id AND status = 'Out of Order') as units_broken,
    (SELECT COUNT(*) FROM equipment_units WHERE equipment_id = e.equipment_id) as units_total
    FROM equipment e $where_clause ORDER BY e.equipment_name";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$equipment = $stmt->fetchAll();

// Get category totals for auto-fill
$category_totals_stmt = $pdo->query("SELECT category, SUM(total_quantity) as total FROM equipment WHERE category IS NOT NULL GROUP BY category");
$category_totals = [];
while ($row = $category_totals_stmt->fetch()) {
    $category_totals[$row['category']] = $row['total'];
}
?>
<?php $page_title = 'Equipment Management - UEP Fitness Gym'; include '../header.php'; ?>

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
        <div class="alert alert-success" id="successAlert">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="alert-icon">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
            </svg>
            <span class="alert-message"><?php echo htmlspecialchars($_GET['success']); ?></span>
        </div>
        <script>
            setTimeout(function() {
                var alert = document.getElementById('successAlert');
                if (alert) {
                    alert.style.transition = 'opacity 0.3s ease';
                    alert.style.opacity = '0';
                    setTimeout(function() {
                        alert.style.display = 'none';
                    }, 300);
                }
            }, 5000);
        </script>
    <?php endif; ?>
    
    <!-- Error Message -->
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error" id="errorAlert">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="alert-icon">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
            </svg>
            <span class="alert-message"><?php echo htmlspecialchars($_GET['error']); ?></span>
        </div>
        <script>
            setTimeout(function() {
                var alert = document.getElementById('errorAlert');
                if (alert) {
                    alert.style.transition = 'opacity 0.3s ease';
                    alert.style.opacity = '0';
                    setTimeout(function() {
                        alert.style.display = 'none';
                    }, 300);
                }
            }, 5000);
        </script>
    <?php endif; ?>

    <!-- Page Header -->
    <div class="page-header">
        <h2 class="page-title">Equipment Management</h2>
        <p class="page-subtitle">Track gym equipment, maintenance records, and status.</p>
    </div>

    <div style="margin-bottom: 2rem;">
        <button type="button" id="addEquipmentBtn" onclick="openAddModal(); return false;" class="btn btn-primary" style="cursor: pointer;">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1.25rem; height: 1.25rem;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Add Equipment
        </button>
    </div>

    <!-- Equipment Statistics -->
    <div class="stats-grid" style="grid-template-columns: repeat(4, minmax(0, 1fr)); gap: var(--spacing-6); margin-bottom: var(--spacing-8);">
        <div class="card-stats card-stats-blue">
            <div class="stats-decoration stats-decoration-blue" style="position: absolute;"></div>
            <div style="position: relative;">
                <div class="stats-icon-container stats-icon-container-blue">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" style="color: #ffffff; width: 1.5rem; height: 1.5rem;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>
                    </svg>
                </div>
                <h3 class="stats-label">Total Equipment</h3>
                <p class="stats-value"><?php echo $stats['total_equipment']; ?></p>
            </div>
        </div>
        <div class="card-stats card-stats-green">
            <div class="stats-decoration stats-decoration-green" style="position: absolute;"></div>
            <div style="position: relative;">
                <div class="stats-icon-container stats-icon-container-green">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" style="color: #ffffff; width: 1.5rem; height: 1.5rem;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
                    </svg>
                </div>
                <h3 class="stats-label">Working</h3>
                <p class="stats-value"><?php echo $stats['working']; ?></p>
            </div>
        </div>
        <div class="card-stats card-stats-yellow">
            <div class="stats-decoration stats-decoration-amber" style="position: absolute;"></div>
            <div style="position: relative;">
                <div class="stats-icon-container stats-icon-container-amber">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" style="color: #ffffff; width: 1.5rem; height: 1.5rem;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                    </svg>
                </div>
                <h3 class="stats-label">Under Maintenance</h3>
                <p class="stats-value"><?php echo $stats['maintenance']; ?></p>
            </div>
        </div>
        
        <div class="card-stats card-stats-red">
            <div class="stats-decoration stats-decoration-red" style="position: absolute;"></div>
            <div style="position: relative;">
                <div class="stats-icon-container stats-icon-container-red">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" style="color: #ffffff; width: 1.5rem; height: 1.5rem;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                    </svg>
                </div>
                <h3 class="stats-label">Out of Order</h3>
                <p class="stats-value"><?php echo $stats['repair']; ?></p>
            </div>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="card" style="margin-bottom: var(--spacing-8);">
        <h3 class="section-title" style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: var(--spacing-4);">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1.25rem; height: 1.25rem; color: var(--blue-600);">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607z"/>
            </svg>
            Search & Filter Equipment
        </h3>
        <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--spacing-4); align-items: end;">
            <div style="position: relative;">
                <input type="text" name="search" placeholder="Search equipment" value="<?php echo htmlspecialchars($search); ?>" class="form-input" style="padding-left: 2.5rem;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1.25rem; height: 1.25rem; color: var(--gray-400); position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); pointer-events: none;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607z"/>
                </svg>
            </div>
            <select name="status" class="form-select">
                <option value="">All Status</option>
                <option value="Working" <?php echo $status_filter == 'Working' ? 'selected' : ''; ?>>Working</option>
                <option value="Under Maintenance" <?php echo $status_filter == 'Under Maintenance' ? 'selected' : ''; ?>>Under Maintenance</option>
                <option value="Out of Order" <?php echo $status_filter == 'Out of Order' ? 'selected' : ''; ?>>Out of Order</option>
            </select>
            <select name="category" class="form-select">
                <option value="">All Categories</option>
                <option value="Cardio" <?php echo $category_filter == 'Cardio' ? 'selected' : ''; ?>>Cardio</option>
                <option value="Strength" <?php echo $category_filter == 'Strength' ? 'selected' : ''; ?>>Strength</option>
                <option value="Accessories" <?php echo $category_filter == 'Accessories' ? 'selected' : ''; ?>>Accessories</option>
                <option value="Free Weights" <?php echo $category_filter == 'Free Weights' ? 'selected' : ''; ?>>Free Weights</option>
            </select>
            <button type="submit" class="btn btn-primary">
                Search
            </button>
        </form>
    </div>

    <!-- Equipment Cards Grid -->
    <div style="margin-bottom: 2rem;">
        <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.5rem;">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1.5rem; height: 1.5rem; color: var(--blue-600);">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h4.5v10.5h-4.5zM15.75 6.75h4.5v10.5h-4.5zM10.5 9.75h3v4.5h-3z"/>
            </svg>
            <h3 style="font-size: 1.25rem; font-weight: 700; color: var(--slate-900); margin: 0;">Equipment Inventory</h3>
        </div>
        
        <?php if (empty($equipment)): ?>
        <div class="card" style="padding: 3rem; text-align: center;">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 3rem; height: 3rem; margin: 0 auto 1rem; color: var(--gray-400);">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>
            </svg>
            <p style="color: var(--gray-600); font-size: 1rem; margin-bottom: 1rem;">No equipment found. Add your first equipment to get started.</p>
            <button type="button" onclick="openAddModal()" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 0.5rem;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1.25rem; height: 1.25rem;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                Add Equipment
            </button>
        </div>
        <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem;">
            <?php foreach ($equipment as $item): 
                // Determine equipment status based on units
                $units_total = $item['units_total'] ?? $item['total_quantity'];
                $units_available = $item['units_available'] ?? $item['quantity_available'];
                $units_maintenance = $item['units_maintenance'] ?? 0;
                $units_broken = $item['units_broken'] ?? 0;
                
                if ($units_available == $units_total) {
                    $computed_status = 'Available';
                } elseif ($units_available == 0) {
                    $computed_status = 'Out of Order';
                } elseif ($units_maintenance == $units_total) {
                    $computed_status = 'Under Maintenance';
                } else {
                    $computed_status = 'Mixed';
                }
                
                // Determine category badge style
                $category_styles = [
                    'Cardio' => 'background-color: var(--blue-100); color: var(--blue-800); border: 1px solid var(--blue-200);',
                    'Strength' => 'background-color: var(--purple-100); color: var(--purple-800); border: 1px solid var(--purple-200);',
                    'Free Weights' => 'background-color: var(--orange-100); color: var(--orange-800); border: 1px solid var(--orange-200);',
                    'Accessories' => 'background-color: var(--teal-100); color: var(--teal-800); border: 1px solid var(--teal-200);'
                ];
                $category_style = $category_styles[$item['category']] ?? 'background-color: var(--gray-100); color: var(--gray-800); border: 1px solid var(--gray-200);';
                
                // Determine status badge style
                $status_styles = [
                    'Available' => 'background-color: var(--green-100); color: var(--green-800); border: 1px solid var(--green-200);',
                    'Mixed' => 'background-color: var(--blue-100); color: var(--blue-800); border: 1px solid var(--blue-200);',
                    'Under Maintenance' => 'background-color: var(--yellow-100); color: var(--yellow-800); border: 1px solid var(--yellow-200);',
                    'Out of Order' => 'background-color: var(--red-100); color: var(--red-800); border: 1px solid var(--red-200);'
                ];
                $status_style = $status_styles[$computed_status] ?? 'background-color: var(--gray-100); color: var(--gray-800); border: 1px solid var(--gray-200);';
                
                // Status icon
                $status_icons = [
                    'Available' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>',
                    'Mixed' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                    'Under Maintenance' => '<path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z"/>',
                    'Out of Order' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>'
                ];
                $status_icon = $status_icons[$computed_status] ?? $status_icons['Out of Order'];
            ?>
            <div class="card" style="padding: 0; overflow: hidden; transition: transform 0.2s, box-shadow 0.2s; cursor: pointer;" onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 12px 24px rgba(0,0,0,0.15)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 1px 3px rgba(0,0,0,0.1)'" onclick="openEquipmentHistory('<?php echo $item['equipment_id']; ?>', '<?php echo htmlspecialchars($item['equipment_name']); ?>')">
                <!-- Card Header -->
                <div style="background: linear-gradient(135deg, var(--blue-600) 0%, var(--purple-600) 100%); padding: 1.25rem; color: white;">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.75rem;">
                        <h4 style="font-size: 1.125rem; font-weight: 700; margin: 0; color: white;"><?php echo htmlspecialchars($item['equipment_name']); ?></h4>
                        <span style="display: inline-flex; align-items: center; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; background-color: rgba(255,255,255,0.25); color: white; backdrop-filter: blur(10px);">
                            <?php echo htmlspecialchars($item['category']); ?>
                        </span>
                    </div>
                    <?php if (!empty($item['brand']) || !empty($item['model'])): ?>
                    <div style="font-size: 0.875rem; color: rgba(255,255,255,0.9); display: flex; align-items: center; gap: 0.5rem;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1rem; height: 1rem;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z"/>
                        </svg>
                        <?php echo htmlspecialchars(trim($item['brand'] . ' ' . $item['model'])); ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Card Body -->
                <div style="padding: 1.25rem;">
                    <!-- Availability -->
                    <div style="margin-bottom: 1rem; padding: 1rem; background-color: var(--gray-50); border-radius: 0.5rem; border: 1px solid var(--gray-200);">
                        <div style="font-size: 0.75rem; font-weight: 600; color: var(--gray-600); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.75rem;">Unit Status</div>
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.75rem;">
                            <div style="text-align: center; padding: 0.75rem; background-color: white; border-radius: 0.375rem; border: 1px solid var(--gray-200);">
                                <div style="font-size: 1.75rem; font-weight: 700; color: var(--slate-900);"><?php echo $item['units_total'] ?? $item['total_quantity']; ?></div>
                                <div style="font-size: 0.7rem; color: var(--gray-500); margin-top: 0.25rem;">TOTAL</div>
                            </div>
                            <div style="text-align: center; padding: 0.75rem; background-color: var(--green-50); border-radius: 0.375rem; border: 1px solid var(--green-200);">
                                <div style="font-size: 1.75rem; font-weight: 700; color: var(--green-700);"><?php echo $item['units_available'] ?? $item['quantity_available']; ?></div>
                                <div style="font-size: 0.7rem; color: var(--green-600); margin-top: 0.25rem;">AVAILABLE</div>
                            </div>
                            <div style="text-align: center; padding: 0.75rem; background-color: var(--yellow-50); border-radius: 0.375rem; border: 1px solid var(--yellow-200);">
                                <div style="font-size: 1.75rem; font-weight: 700; color: var(--yellow-700);"><?php echo $item['units_maintenance'] ?? 0; ?></div>
                                <div style="font-size: 0.7rem; color: var(--yellow-600); margin-top: 0.25rem;">MAINTENANCE</div>
                            </div>
                            <div style="text-align: center; padding: 0.75rem; background-color: var(--red-50); border-radius: 0.375rem; border: 1px solid var(--red-200);">
                                <div style="font-size: 1.75rem; font-weight: 700; color: var(--red-700);"><?php echo $item['units_broken'] ?? 0; ?></div>
                                <div style="font-size: 0.7rem; color: var(--red-600); margin-top: 0.25rem;">OUT OF ORDER</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Optional Details -->
                    <div style="border-top: 1px solid var(--gray-200); padding-top: 1rem; margin-top: 1rem;">
                        <?php if (!empty($item['purchase_price']) && $item['purchase_price'] > 0): ?>
                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.625rem;">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1rem; height: 1rem; color: var(--gray-400);">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"/>
                            </svg>
                            <span style="font-size: 0.8125rem; color: var(--gray-600);">Purchase: ₱<?php echo number_format($item['purchase_price'], 2); ?>/unit</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.625rem;">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1rem; height: 1rem; color: var(--gray-400);">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span style="font-size: 0.8125rem; color: var(--gray-600); font-weight: 600;">Total Value: ₱<?php echo number_format($item['purchase_price'] * $item['total_quantity'], 2); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (empty($item['purchase_price'])): ?>
                        <div style="text-align: center; padding: 0.5rem; color: var(--gray-400); font-size: 0.8125rem;">
                            No additional details
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Card Footer - Action Buttons -->
                <div style="padding: 1rem 1.25rem; background-color: var(--gray-50); border-top: 1px solid var(--gray-200); display: flex; gap: 0.5rem;" onclick="event.stopPropagation()">
                    <button onclick="openMaintenanceModal('<?php echo $item['equipment_id']; ?>', '<?php echo htmlspecialchars($item['equipment_name']); ?>', '<?php echo htmlspecialchars($item['category']); ?>', '<?php echo htmlspecialchars($item['status']); ?>'); event.stopPropagation();" class="btn btn-sm btn-primary" style="flex: 1; padding: 0.625rem 1rem; font-size: 0.8125rem; display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1rem; height: 1rem;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z"/>
                        </svg>
                        Add Maintenance
                    </button>
                    <button onclick="openMarkBrokenModal('<?php echo $item['equipment_id']; ?>', '<?php echo htmlspecialchars($item['equipment_name']); ?>'); event.stopPropagation();" class="btn btn-sm btn-danger" style="flex: 1; padding: 0.625rem 1rem; font-size: 0.8125rem; display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; background-color: #dc2626; border-color: #dc2626; color: white;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1rem; height: 1rem;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                        </svg>
                        Mark Broken
                    </button>
                    <?php if (($item['units_maintenance'] ?? 0) > 0): ?>
                    <button onclick="openCompleteMaintenanceModal('<?php echo $item['equipment_id']; ?>', '<?php echo htmlspecialchars($item['equipment_name']); ?>'); event.stopPropagation();" class="btn btn-sm btn-success" style="flex: 1; padding: 0.625rem 1rem; font-size: 0.8125rem; display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; background-color: #16a34a; border-color: #16a34a; color: white;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1rem; height: 1rem;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                        </svg>
                        Complete
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Equipment History Modal -->
    <div id="equipmentHistoryModal" class="modal-overlay" style="display: none;">
        <div class="modal-content" style="max-width: 60rem;" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h3 class="card-header-title">Equipment History</h3>
                <button type="button" id="closeHistoryBtn" class="modal-close-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1.5rem; height: 1.5rem;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="modal-form-spacing">
                <!-- Equipment Info -->
                <div style="margin-bottom: 1.5rem; padding: 1rem; background-color: var(--gray-50); border-radius: 0.5rem; border: 1px solid var(--gray-200);">
                    <h4 style="font-size: 1.125rem; font-weight: 700; color: var(--slate-900); margin: 0;" id="historyEquipmentName">Equipment Name</h4>
                </div>
                
                <!-- Maintenance History Table -->
                <div id="historyContent" style="max-height: 500px; overflow-y: auto;">
                    <p style="text-align: center; color: var(--gray-500); padding: 2rem;">Loading history...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Mark as Broken Modal -->
    <div id="markBrokenModal" class="modal-overlay" style="display: none;">
        <div class="modal-content" style="max-width: 42rem;" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h3 class="card-header-title">Mark Units as Out of Order</h3>
                <button type="button" id="closeMarkBrokenBtn" class="modal-close-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1.5rem; height: 1.5rem;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form method="POST" class="modal-form-spacing">
                <input type="hidden" name="action" value="mark_broken">
                <input type="hidden" name="equipment_id" id="brokenEquipmentId">
                
                <!-- Equipment Info Section -->
                <div style="margin-bottom: 2rem;">
                    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.25rem; padding-bottom: 0.75rem; border-bottom: 3px solid var(--red-500);">
                        <div style="width: 2.5rem; height: 2.5rem; border-radius: 0.75rem; background: linear-gradient(135deg, var(--red-500), var(--orange-600)); display: flex; align-items: center; justify-content: center;">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" style="width: 1.5rem; height: 1.5rem;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                            </svg>
                        </div>
                        <h4 style="font-size: 1.125rem; font-weight: 700; color: var(--slate-900); margin: 0;">Equipment Information</h4>
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="font-weight: 600; color: var(--slate-700); margin-bottom: 0.5rem; display: block;">
                            Equipment
                        </label>
                        <input type="text" id="brokenEquipmentDisplay" readonly class="form-input" style="background-color: var(--red-50); cursor: not-allowed; border: 2px solid var(--red-300); color: var(--slate-900); font-weight: 600;">
                    </div>
                    <div style="margin-top: 1rem; padding: 1rem; background: linear-gradient(135deg, var(--red-50), var(--orange-50)); border-left: 4px solid var(--red-500); border-radius: 0.5rem;">
                        <div style="display: flex; align-items: start; gap: 0.75rem;">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="var(--red-600)" stroke-width="2" style="width: 1.25rem; height: 1.25rem; flex-shrink: 0; margin-top: 0.125rem;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                            </svg>
                            <p style="font-size: 0.875rem; color: var(--red-700); margin: 0; line-height: 1.5;">
                                <strong>Warning:</strong> Marking units as out of order will make them unavailable for use and create a maintenance record.
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Units Selection Section -->
                <div style="margin-bottom: 2rem;">
                    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.25rem; padding-bottom: 0.75rem; border-bottom: 3px solid var(--orange-500);">
                        <div style="width: 2.5rem; height: 2.5rem; border-radius: 0.75rem; background: linear-gradient(135deg, var(--orange-500), var(--amber-600)); display: flex; align-items: center; justify-content: center;">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" style="width: 1.5rem; height: 1.5rem;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/>
                            </svg>
                        </div>
                        <h4 style="font-size: 1.125rem; font-weight: 700; color: var(--slate-900); margin: 0;">Select Units to Mark as Broken</h4>
                    </div>
                    <div id="brokenUnitsList" style="max-height: 250px; overflow-y: auto; padding: 1rem; border: 2px solid var(--red-300); border-radius: 0.5rem; background-color: var(--red-50);">
                        <p style="color: var(--gray-500); font-size: 0.875rem; text-align: center;">Loading units...</p>
                    </div>
                    <div style="display: flex; gap: 0.75rem; margin-top: 1rem;">
                        <button type="button" onclick="selectAllBroken()" class="btn btn-sm" style="flex: 1; font-size: 0.875rem; padding: 0.625rem; background-color: var(--orange-100); color: var(--orange-700); border: 2px solid var(--orange-300); font-weight: 600; border-radius: 0.5rem; transition: all 0.2s;" onmouseover="this.style.backgroundColor='var(--orange-200)'; this.style.borderColor='var(--orange-400)'" onmouseout="this.style.backgroundColor='var(--orange-100)'; this.style.borderColor='var(--orange-300)'">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1rem; height: 1rem; display: inline-block; vertical-align: middle; margin-right: 0.375rem;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                            </svg>
                            Select All
                        </button>
                        <button type="button" onclick="deselectAllBroken()" class="btn btn-sm" style="flex: 1; font-size: 0.875rem; padding: 0.625rem; background-color: var(--gray-100); color: var(--gray-700); border: 2px solid var(--gray-300); font-weight: 600; border-radius: 0.5rem; transition: all 0.2s;" onmouseover="this.style.backgroundColor='var(--gray-200)'; this.style.borderColor='var(--gray-400)'" onmouseout="this.style.backgroundColor='var(--gray-100)'; this.style.borderColor='var(--gray-300)'">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1rem; height: 1rem; display: inline-block; vertical-align: middle; margin-right: 0.375rem;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            Deselect All
                        </button>
                    </div>
                </div>
                
                <div class="modal-footer" style="border-top: 2px solid var(--gray-200); padding-top: 1.5rem; margin-top: 2rem;">
                    <button type="submit" class="btn btn-danger" style="background: linear-gradient(135deg, var(--red-600), var(--orange-600)); border: none; padding: 0.875rem 2rem; font-size: 1rem; font-weight: 600; border-radius: 0.5rem; box-shadow: 0 4px 6px rgba(220, 38, 38, 0.3); transition: all 0.3s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 12px rgba(220, 38, 38, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 6px rgba(220, 38, 38, 0.3)'">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1.25rem; height: 1.25rem; display: inline-block; vertical-align: middle; margin-right: 0.5rem;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                        </svg>
                        Mark as Out of Order
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Complete Maintenance Modal -->
    <div id="completeMaintenanceModal" class="modal-overlay" style="display: none;">
        <div class="modal-content" style="max-width: 42rem;" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h3 class="card-header-title">Complete Maintenance</h3>
                <button type="button" id="closeCompleteMaintenanceBtn" class="modal-close-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1.5rem; height: 1.5rem;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form method="POST" class="modal-form-spacing">
                <input type="hidden" name="action" value="complete_maintenance">
                <input type="hidden" name="equipment_id" id="completeEquipmentId">
                
                <!-- Equipment Info Section -->
                <div style="margin-bottom: 2rem;">
                    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.25rem; padding-bottom: 0.75rem; border-bottom: 3px solid var(--green-500);">
                        <div style="width: 2.5rem; height: 2.5rem; border-radius: 0.75rem; background: linear-gradient(135deg, var(--green-500), var(--emerald-600)); display: flex; align-items: center; justify-content: center;">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" style="width: 1.5rem; height: 1.5rem;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                            </svg>
                        </div>
                        <h4 style="font-size: 1.125rem; font-weight: 700; color: var(--slate-900); margin: 0;">Equipment Information</h4>
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="font-weight: 600; color: var(--slate-700); margin-bottom: 0.5rem; display: block;">
                            Equipment
                        </label>
                        <input type="text" id="completeEquipmentDisplay" readonly class="form-input" style="background-color: var(--green-50); cursor: not-allowed; border: 2px solid var(--green-300); color: var(--slate-900); font-weight: 600;">
                    </div>
                    <div style="margin-top: 1rem; padding: 1rem; background: linear-gradient(135deg, var(--green-50), var(--emerald-50)); border-left: 4px solid var(--green-500); border-radius: 0.5rem;">
                        <div style="display: flex; align-items: start; gap: 0.75rem;">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="var(--green-600)" stroke-width="2" style="width: 1.25rem; height: 1.25rem; flex-shrink: 0; margin-top: 0.125rem;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
                            </svg>
                            <p style="font-size: 0.875rem; color: var(--green-700); margin: 0; line-height: 1.5;">
                                <strong>Success:</strong> Completing maintenance will mark selected units as available and update their maintenance records.
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Units Under Maintenance Section -->
                <div style="margin-bottom: 2rem;">
                    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.25rem; padding-bottom: 0.75rem; border-bottom: 3px solid var(--blue-500);">
                        <div style="width: 2.5rem; height: 2.5rem; border-radius: 0.75rem; background: linear-gradient(135deg, var(--blue-500), var(--indigo-600)); display: flex; align-items: center; justify-content: center;">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" style="width: 1.5rem; height: 1.5rem;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/>
                            </svg>
                        </div>
                        <h4 style="font-size: 1.125rem; font-weight: 700; color: var(--slate-900); margin: 0;">Select Units to Mark as Complete</h4>
                    </div>
                    <div id="unitsUnderMaintenanceList" style="max-height: 250px; overflow-y: auto; padding: 1rem; border: 2px solid var(--blue-300); border-radius: 0.5rem; background-color: var(--blue-50);">
                        <p style="color: var(--gray-500); font-size: 0.875rem; text-align: center;">Loading units...</p>
                    </div>
                    <div style="display: flex; gap: 0.75rem; margin-top: 1rem;">
                        <button type="button" onclick="selectAllUnderMaintenance()" class="btn btn-sm" style="flex: 1; font-size: 0.875rem; padding: 0.625rem; background-color: var(--green-100); color: var(--green-700); border: 2px solid var(--green-300); font-weight: 600; border-radius: 0.5rem; transition: all 0.2s;" onmouseover="this.style.backgroundColor='var(--green-200)'; this.style.borderColor='var(--green-400)'" onmouseout="this.style.backgroundColor='var(--green-100)'; this.style.borderColor='var(--green-300)'">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1rem; height: 1rem; display: inline-block; vertical-align: middle; margin-right: 0.375rem;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                            </svg>
                            Select All
                        </button>
                        <button type="button" onclick="deselectAllUnderMaintenance()" class="btn btn-sm" style="flex: 1; font-size: 0.875rem; padding: 0.625rem; background-color: var(--gray-100); color: var(--gray-700); border: 2px solid var(--gray-300); font-weight: 600; border-radius: 0.5rem; transition: all 0.2s;" onmouseover="this.style.backgroundColor='var(--gray-200)'; this.style.borderColor='var(--gray-400)'" onmouseout="this.style.backgroundColor='var(--gray-100)'; this.style.borderColor='var(--gray-300)'">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1rem; height: 1rem; display: inline-block; vertical-align: middle; margin-right: 0.375rem;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            Deselect All
                        </button>
                    </div>
                </div>
                
                <div class="modal-footer" style="border-top: 2px solid var(--gray-200); padding-top: 1.5rem; margin-top: 2rem;">
                    <button type="submit" class="btn btn-primary" style="background: linear-gradient(135deg, var(--green-600), var(--emerald-600)); border: none; padding: 0.875rem 2rem; font-size: 1rem; font-weight: 600; border-radius: 0.5rem; box-shadow: 0 4px 6px rgba(34, 197, 94, 0.3); transition: all 0.3s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 12px rgba(34, 197, 94, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 6px rgba(34, 197, 94, 0.3)'">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1.25rem; height: 1.25rem; display: inline-block; vertical-align: middle; margin-right: 0.5rem;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                        </svg>
                        Mark as Completed
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Maintenance Modal -->
    <div id="equipmentModal" class="modal-overlay" style="display: none;">
        <div class="modal-content" style="max-width: 42rem;" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h3 class="card-header-title" id="modalTitle">Add Equipment</h3>
                <button type="button" id="closeEquipmentBtn" class="modal-close-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1.5rem; height: 1.5rem;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form method="POST" class="modal-form-spacing">
                <input type="hidden" name="action" id="formAction" value="add_equipment">
                <input type="hidden" name="equipment_id" id="editEquipmentId">
                
                <!-- Basic Information Section -->
                <div style="margin-bottom: 2rem;">
                    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.25rem; padding-bottom: 0.75rem; border-bottom: 3px solid var(--blue-500);">
                        <div style="width: 2.5rem; height: 2.5rem; border-radius: 0.75rem; background: linear-gradient(135deg, var(--blue-500), var(--purple-600)); display: flex; align-items: center; justify-content: center;">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" style="width: 1.5rem; height: 1.5rem;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h4.5v10.5h-4.5zM15.75 6.75h4.5v10.5h-4.5zM10.5 9.75h3v4.5h-3z"/>
                            </svg>
                        </div>
                        <h4 style="font-size: 1.125rem; font-weight: 700; color: var(--slate-900); margin: 0;">Basic Information</h4>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.25rem;">
                        <div class="form-group">
                            <label class="form-label" style="font-weight: 600; color: var(--slate-700); margin-bottom: 0.5rem; display: block;">
                                <span style="color: var(--red-500);">*</span> Equipment Name
                            </label>
                            <input type="text" name="equipment_name" id="equipment_name" required class="form-input" placeholder="e.g., Treadmill Model X-5000" style="border: 2px solid var(--gray-300); border-radius: 0.5rem; transition: all 0.2s;" onfocus="this.style.borderColor='var(--blue-500)'; this.style.boxShadow='0 0 0 3px rgba(59, 130, 246, 0.1)'" onblur="this.style.borderColor='var(--gray-300)'; this.style.boxShadow='none'">
                        </div>
                        <div class="form-group">
                            <label class="form-label" style="font-weight: 600; color: var(--slate-700); margin-bottom: 0.5rem; display: block;">
                                <span style="color: var(--red-500);">*</span> Category
                            </label>
                            <select name="category" id="category" required class="form-select" style="border: 2px solid var(--gray-300); border-radius: 0.5rem; transition: all 0.2s;" onfocus="this.style.borderColor='var(--blue-500)'; this.style.boxShadow='0 0 0 3px rgba(59, 130, 246, 0.1)'" onblur="this.style.borderColor='var(--gray-300)'; this.style.boxShadow='none'">
                                <option value="">Select Category</option>
                                <option value="Cardio">🏃 Cardio</option>
                                <option value="Strength">💪 Strength</option>
                                <option value="Accessories">🎯 Accessories</option>
                                <option value="Free Weights">🏋️ Free Weights</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Quantity Section -->
                <div style="margin-bottom: 2rem;">
                    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.25rem; padding-bottom: 0.75rem; border-bottom: 3px solid var(--purple-500);">
                        <div style="width: 2.5rem; height: 2.5rem; border-radius: 0.75rem; background: linear-gradient(135deg, var(--purple-500), var(--pink-600)); display: flex; align-items: center; justify-content: center;">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" style="width: 1.5rem; height: 1.5rem;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>
                            </svg>
                        </div>
                        <h4 style="font-size: 1.125rem; font-weight: 700; color: var(--slate-900); margin: 0;">Quantity</h4>
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="font-weight: 600; color: var(--slate-700); margin-bottom: 0.5rem; display: block;">
                            <span style="color: var(--red-500);">*</span> Quantity to Add
                        </label>
                        <input type="number" name="quantity_to_add" id="quantity_to_add" min="1" required class="form-input" placeholder="Enter number of units" style="border: 2px solid var(--gray-300); border-radius: 0.5rem; transition: all 0.2s;" onfocus="this.style.borderColor='var(--purple-500)'; this.style.boxShadow='0 0 0 3px rgba(168, 85, 247, 0.1)'" onblur="this.style.borderColor='var(--gray-300)'; this.style.boxShadow='none'">
                        <p style="font-size: 0.813rem; color: var(--slate-600); margin-top: 0.5rem; padding: 0.75rem; background-color: var(--blue-50); border-left: 3px solid var(--blue-500); border-radius: 0.375rem;">
                            💡 If this equipment already exists, the quantity will be added to existing stock.
                        </p>
                    </div>
                </div>

                <!-- Purchase Details Section -->
                <div style="margin-bottom: 2rem;">
                    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.25rem; padding-bottom: 0.75rem; border-bottom: 3px solid var(--green-500);">
                        <div style="width: 2.5rem; height: 2.5rem; border-radius: 0.75rem; background: linear-gradient(135deg, var(--green-500), var(--teal-600)); display: flex; align-items: center; justify-content: center;">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" style="width: 1.5rem; height: 1.5rem;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"/>
                            </svg>
                        </div>
                        <h4 style="font-size: 1.125rem; font-weight: 700; color: var(--slate-900); margin: 0;">Purchase Details <span style="font-size: 0.875rem; font-weight: 400; color: var(--slate-500);">(Optional)</span></h4>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.25rem;">
                        <div class="form-group">
                            <label class="form-label" style="font-weight: 600; color: var(--slate-700); margin-bottom: 0.5rem; display: block;">
                                📅 Purchase Date
                            </label>
                            <input type="date" name="purchase_date" id="purchase_date" class="form-input" style="border: 2px solid var(--gray-300); border-radius: 0.5rem; transition: all 0.2s;" onfocus="this.style.borderColor='var(--green-500)'; this.style.boxShadow='0 0 0 3px rgba(34, 197, 94, 0.1)'" onblur="this.style.borderColor='var(--gray-300)'; this.style.boxShadow='none'">
                        </div>
                        <div class="form-group">
                            <label class="form-label" style="font-weight: 600; color: var(--slate-700); margin-bottom: 0.5rem; display: block;">
                                💰 Purchase Price
                            </label>
                            <input type="number" name="purchase_price" id="purchase_price" step="0.01" min="0" class="form-input" placeholder="₱ 0.00" style="border: 2px solid var(--gray-300); border-radius: 0.5rem; transition: all 0.2s;" onfocus="this.style.borderColor='var(--green-500)'; this.style.boxShadow='0 0 0 3px rgba(34, 197, 94, 0.1)'" onblur="this.style.borderColor='var(--gray-300)'; this.style.boxShadow='none'">
                        </div>
                    </div>
                </div>

                <!-- Additional Section -->
                <div style="margin-bottom: 2rem;">
                    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.25rem; padding-bottom: 0.75rem; border-bottom: 3px solid var(--amber-500);">
                        <div style="width: 2.5rem; height: 2.5rem; border-radius: 0.75rem; background: linear-gradient(135deg, var(--amber-500), var(--orange-600)); display: flex; align-items: center; justify-content: center;">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" style="width: 1.5rem; height: 1.5rem;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>
                            </svg>
                        </div>
                        <h4 style="font-size: 1.125rem; font-weight: 700; color: var(--slate-900); margin: 0;">Additional Notes</h4>
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="font-weight: 600; color: var(--slate-700); margin-bottom: 0.5rem; display: block;">
                            Notes
                        </label>
                        <textarea name="notes" id="notes" rows="4" class="form-textarea" placeholder="Add any additional information about this equipment..." style="border: 2px solid var(--gray-300); border-radius: 0.5rem; transition: all 0.2s; resize: vertical;" onfocus="this.style.borderColor='var(--amber-500)'; this.style.boxShadow='0 0 0 3px rgba(245, 158, 11, 0.1)'" onblur="this.style.borderColor='var(--gray-300)'; this.style.boxShadow='none'"></textarea>
                    </div>
                </div>
                
                <div class="modal-footer" style="border-top: 2px solid var(--gray-200); padding-top: 1.5rem; margin-top: 2rem;">
                    <button type="submit" class="btn btn-primary" style="background: linear-gradient(135deg, var(--blue-600), var(--purple-600)); border: none; padding: 0.875rem 2rem; font-size: 1rem; font-weight: 600; border-radius: 0.5rem; box-shadow: 0 4px 6px rgba(59, 130, 246, 0.3); transition: all 0.3s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 12px rgba(59, 130, 246, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 6px rgba(59, 130, 246, 0.3)'">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1.25rem; height: 1.25rem; display: inline-block; vertical-align: middle; margin-right: 0.5rem;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                        </svg>
                        Save Equipment
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Maintenance Modal -->
    <div id="maintenanceModal" class="modal-overlay" style="display: none;">
        <div class="modal-content" style="max-width: 36rem;" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h3 class="card-header-title">Add Maintenance Record</h3>
                <button type="button" id="closeMaintenanceBtn" class="modal-close-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1.5rem; height: 1.5rem;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form method="POST" class="modal-form-spacing">
                <input type="hidden" name="action" value="add_maintenance">
                <input type="hidden" name="equipment_id" id="maintenanceEquipmentId">
                
                <!-- Equipment Info Section -->
                <div style="margin-bottom: 2rem;">
                    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.25rem; padding-bottom: 0.75rem; border-bottom: 3px solid var(--blue-500);">
                        <div style="width: 2.5rem; height: 2.5rem; border-radius: 0.75rem; background: linear-gradient(135deg, var(--blue-500), var(--indigo-600)); display: flex; align-items: center; justify-content: center;">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" style="width: 1.5rem; height: 1.5rem;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h4.5v10.5h-4.5zM15.75 6.75h4.5v10.5h-4.5zM10.5 9.75h3v4.5h-3z"/>
                            </svg>
                        </div>
                        <h4 style="font-size: 1.125rem; font-weight: 700; color: var(--slate-900); margin: 0;">Equipment Information</h4>
                    </div>
                    <div style="display: grid; gap: 1rem;">
                        <div class="form-group">
                            <label class="form-label" style="font-weight: 600; color: var(--slate-700); margin-bottom: 0.5rem; display: block;">
                                Equipment
                            </label>
                            <input type="text" id="maintenanceEquipmentDisplay" readonly class="form-input" style="background-color: var(--gray-100); cursor: not-allowed; border: 2px solid var(--gray-300);">
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                            <div class="form-group">
                                <label class="form-label" style="font-weight: 600; color: var(--slate-700); margin-bottom: 0.5rem; display: block;">
                                    Category
                                </label>
                                <input type="text" id="maintenanceCategory" readonly class="form-input" style="background-color: var(--gray-100); cursor: not-allowed; border: 2px solid var(--gray-300);">
                            </div>
                            <div class="form-group">
                                <label class="form-label" style="font-weight: 600; color: var(--slate-700); margin-bottom: 0.5rem; display: block;">
                                    Current Status
                                </label>
                                <input type="text" id="maintenanceCurrentStatus" readonly class="form-input" style="background-color: var(--gray-100); cursor: not-allowed; border: 2px solid var(--gray-300);">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Maintenance Details Section -->
                <div style="margin-bottom: 2rem;">
                    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.25rem; padding-bottom: 0.75rem; border-bottom: 3px solid var(--purple-500);">
                        <div style="width: 2.5rem; height: 2.5rem; border-radius: 0.75rem; background: linear-gradient(135deg, var(--purple-500), var(--pink-600)); display: flex; align-items: center; justify-content: center;">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" style="width: 1.5rem; height: 1.5rem;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z"/>
                            </svg>
                        </div>
                        <h4 style="font-size: 1.125rem; font-weight: 700; color: var(--slate-900); margin: 0;">Maintenance Details</h4>
                    </div>
                    <div style="display: grid; gap: 1.25rem;">
                        <div class="form-group">
                            <label class="form-label" style="font-weight: 600; color: var(--slate-700); margin-bottom: 0.5rem; display: block;">
                                <span style="color: var(--red-500);">*</span> Maintenance Type
                            </label>
                            <select name="maintenance_type" required class="form-select" style="border: 2px solid var(--gray-300); border-radius: 0.5rem; transition: all 0.2s;" onfocus="this.style.borderColor='var(--purple-500)'; this.style.boxShadow='0 0 0 3px rgba(168, 85, 247, 0.1)'" onblur="this.style.borderColor='var(--gray-300)'; this.style.boxShadow='none'">
                                <option value="">Select Type</option>
                                <option value="Routine">🔄 Routine Maintenance</option>
                                <option value="Repair">🔧 Repair</option>
                                <option value="Inspection">🔍 Inspection</option>
                                <option value="Cleaning">✨ Cleaning</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" style="font-weight: 600; color: var(--slate-700); margin-bottom: 0.5rem; display: block;">
                                <span style="color: var(--red-500);">*</span> Select Units to Maintain
                            </label>
                            <div id="unitsCheckboxList" style="max-height: 200px; overflow-y: auto; padding: 1rem; border: 2px solid var(--purple-300); border-radius: 0.5rem; background-color: var(--purple-50);">
                                <p style="color: var(--gray-500); font-size: 0.875rem; text-align: center;">Loading units...</p>
                            </div>
                            <p style="font-size: 0.813rem; color: var(--slate-600); margin-top: 0.5rem; padding: 0.75rem; background-color: var(--purple-50); border-left: 3px solid var(--purple-500); border-radius: 0.375rem;">
                                💡 Select which specific units need maintenance.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Description Section -->
                <div style="margin-bottom: 2rem;">
                    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.25rem; padding-bottom: 0.75rem; border-bottom: 3px solid var(--amber-500);">
                        <div style="width: 2.5rem; height: 2.5rem; border-radius: 0.75rem; background: linear-gradient(135deg, var(--amber-500), var(--orange-600)); display: flex; align-items: center; justify-content: center;">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" style="width: 1.5rem; height: 1.5rem;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>
                            </svg>
                        </div>
                        <h4 style="font-size: 1.125rem; font-weight: 700; color: var(--slate-900); margin: 0;">Description</h4>
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="font-weight: 600; color: var(--slate-700); margin-bottom: 0.5rem; display: block;">
                            <span style="color: var(--red-500);">*</span> Description / Notes
                        </label>
                        <textarea name="description" required rows="4" class="form-textarea" placeholder="Example: Replaced worn treadmill belt and lubricated motor" style="border: 2px solid var(--gray-300); border-radius: 0.5rem; transition: all 0.2s; resize: vertical;" onfocus="this.style.borderColor='var(--amber-500)'; this.style.boxShadow='0 0 0 3px rgba(245, 158, 11, 0.1)'" onblur="this.style.borderColor='var(--gray-300)'; this.style.boxShadow='none'"></textarea>
                    </div>
                </div>

                <!-- Cost & Personnel Section -->
                <div style="margin-bottom: 2rem;">
                    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.25rem; padding-bottom: 0.75rem; border-bottom: 3px solid var(--green-500);">
                        <div style="width: 2.5rem; height: 2.5rem; border-radius: 0.75rem; background: linear-gradient(135deg, var(--green-500), var(--teal-600)); display: flex; align-items: center; justify-content: center;">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" style="width: 1.5rem; height: 1.5rem;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"/>
                            </svg>
                        </div>
                        <h4 style="font-size: 1.125rem; font-weight: 700; color: var(--slate-900); margin: 0;">Cost & Personnel <span style="font-size: 0.875rem; font-weight: 400; color: var(--slate-500);">(Optional)</span></h4>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.25rem;">
                        <div class="form-group">
                            <label class="form-label" style="font-weight: 600; color: var(--slate-700); margin-bottom: 0.5rem; display: block;">
                                💰 Maintenance Cost
                            </label>
                            <input type="number" name="cost" step="0.01" min="0" value="0.00" class="form-input" placeholder="₱ 0.00" style="border: 2px solid var(--gray-300); border-radius: 0.5rem; transition: all 0.2s;" onfocus="this.style.borderColor='var(--green-500)'; this.style.boxShadow='0 0 0 3px rgba(34, 197, 94, 0.1)'" onblur="this.style.borderColor='var(--gray-300)'; this.style.boxShadow='none'">
                        </div>
                        <div class="form-group">
                            <label class="form-label" style="font-weight: 600; color: var(--slate-700); margin-bottom: 0.5rem; display: block;">
                                👤 Performed By
                            </label>
                            <input type="text" name="performed_by" class="form-input" placeholder="Technician name / Company" style="border: 2px solid var(--gray-300); border-radius: 0.5rem; transition: all 0.2s;" onfocus="this.style.borderColor='var(--green-500)'; this.style.boxShadow='0 0 0 3px rgba(34, 197, 94, 0.1)'" onblur="this.style.borderColor='var(--gray-300)'; this.style.boxShadow='none'">
                        </div>
                    </div>
                </div>

                <div class="modal-footer" style="border-top: 2px solid var(--gray-200); padding-top: 1.5rem; margin-top: 2rem;">
                    <button type="submit" class="btn btn-primary" style="background: linear-gradient(135deg, var(--green-600), var(--green-700)); border: none; padding: 0.875rem 2rem; font-size: 1rem; font-weight: 600; border-radius: 0.5rem; box-shadow: 0 4px 6px rgba(34, 197, 94, 0.3); transition: all 0.3s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 12px rgba(34, 197, 94, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 6px rgba(34, 197, 94, 0.3)'">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 1.25rem; height: 1.25rem; display: inline-block; vertical-align: middle; margin-right: 0.5rem;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
                        </svg>
                        Add Maintenance Record
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Move script to end of body for proper DOM loading -->
    
    <?php include '../footer.php'; ?>

    <script>
        // Category totals from database
        const categoryTotals = <?php echo json_encode($category_totals); ?>;
        
        // Modal functions - Define immediately and ensure they're available
        function openAddModal() {
            console.log('openAddModal called');
            const modal = document.getElementById('equipmentModal');
            if (!modal) {
                console.error('Equipment modal not found!');
                alert('Error: Equipment modal not found. Please refresh the page.');
                return;
            }
            
            console.log('Modal found, showing...');
            
            // Set form values
            const modalTitle = document.getElementById('modalTitle');
            const formAction = document.getElementById('formAction');
            const editEquipmentId = document.getElementById('editEquipmentId');
            
            if (modalTitle) modalTitle.textContent = 'Add Equipment';
            if (formAction) formAction.value = 'add_equipment';
            if (editEquipmentId) editEquipmentId.value = '';
            
            // Reset form
            const form = document.querySelector('#equipmentModal form');
            if (form) {
                form.reset();
            }
            
            // Show modal - force with inline styles to override any CSS conflicts
            modal.classList.add('show');
            
            console.log('Modal show class added');
            console.log('Modal classes:', modal.className);
            console.log('Modal display style:', window.getComputedStyle(modal).display);
            
            // Verify modal content is visible
            const modalContent = modal.querySelector('.modal-content');
            if (modalContent) {
                console.log('Modal content found');
                console.log('Modal content display:', window.getComputedStyle(modalContent).display);
            } else {
                console.error('Modal content not found!');
            }
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
            console.log('openAddModal function re-registered after timeout');
        }, 100);
        
        // Add event listener to button as backup - show modal directly
        document.addEventListener('DOMContentLoaded', function() {
            const addBtn = document.getElementById('addEquipmentBtn');
            if (addBtn) {
                addBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('Button clicked via event listener');
                    
                    // Show modal directly
                    const modal = document.getElementById('equipmentModal');
                    if (!modal) {
                        console.error('Equipment modal not found!');
                        return;
                    }
                    
                    console.log('Modal found, showing directly...');
                    
                    // Set form values
                    const modalTitle = document.getElementById('modalTitle');
                    const formAction = document.getElementById('formAction');
                    const editEquipmentId = document.getElementById('editEquipmentId');
                    
                    if (modalTitle) modalTitle.textContent = 'Add Equipment';
                    if (formAction) formAction.value = 'add_equipment';
                    if (editEquipmentId) editEquipmentId.value = '';
                    
                    // Reset form
                    const form = document.querySelector('#equipmentModal form');
                    if (form) {
                        form.reset();
                    }
                    
                    // Show modal - force with inline styles
                    modal.classList.add('show');
                    
                    console.log('Modal display style:', window.getComputedStyle(modal).display);
                    console.log('Modal z-index:', window.getComputedStyle(modal).zIndex);
                    
                    // Also try calling the function if it exists
                    if (typeof window.openAddModal === 'function') {
                        console.log('Also calling window.openAddModal...');
                        window.openAddModal();
                    }
                });
                console.log('Event listener added to Add Equipment button');
            } else {
                console.error('Add Equipment button not found!');
            }
        });

        function closeModal() {
            const modal = document.getElementById('equipmentModal');
            if (modal) {
                modal.classList.remove('show');
            }
        }
        
        // Also set as window property
        window.closeModal = closeModal;
        
        // Add event listener for close button
        document.addEventListener('DOMContentLoaded', function() {
            const closeBtn = document.getElementById('closeEquipmentBtn');
            if (closeBtn) {
                closeBtn.addEventListener('click', closeModal);
            }
            
            const closeMaintenanceBtn = document.getElementById('closeMaintenanceBtn');
            if (closeMaintenanceBtn) {
                closeMaintenanceBtn.addEventListener('click', closeMaintenanceModal);
            }

            const closeCompleteMaintenanceBtn = document.getElementById('closeCompleteMaintenanceBtn');
            if (closeCompleteMaintenanceBtn) {
                closeCompleteMaintenanceBtn.addEventListener('click', closeCompleteMaintenanceModal);
            }

            const closeMarkBrokenBtn = document.getElementById('closeMarkBrokenBtn');
            if (closeMarkBrokenBtn) {
                closeMarkBrokenBtn.addEventListener('click', closeMarkBrokenModal);
            }

            const closeHistoryBtn = document.getElementById('closeHistoryBtn');
            if (closeHistoryBtn) {
                closeHistoryBtn.addEventListener('click', closeHistoryModal);
            }
        });

        async function openMaintenanceModal(equipmentId, equipmentName, equipmentCategory, equipmentStatus) {
            // Set hidden equipment ID
            document.getElementById('maintenanceEquipmentId').value = equipmentId;
            
            // Set display fields
            document.getElementById('maintenanceEquipmentDisplay').value = equipmentName + ' (' + equipmentId + ')';
            document.getElementById('maintenanceCategory').value = equipmentCategory || 'N/A';
            document.getElementById('maintenanceCurrentStatus').value = equipmentStatus || 'N/A';
            
            // Load units for this equipment
            const unitsContainer = document.getElementById('unitsCheckboxList');
            unitsContainer.innerHTML = '<p style="color: var(--gray-500); font-size: 0.875rem; text-align: center;">Loading units...</p>';
            
            try {
                const response = await fetch('get_equipment_units.php?equipment_id=' + equipmentId);
                const units = await response.json();
                
                if (units.error) {
                    unitsContainer.innerHTML = '<p style="color: var(--red-600); font-size: 0.875rem; text-align: center;">' + units.error + '</p>';
                    return;
                }
                
                if (units.length === 0) {
                    unitsContainer.innerHTML = '<p style="color: var(--gray-500); font-size: 0.875rem; text-align: center;">No units found for this equipment.</p>';
                    return;
                }
                
                // Build checkbox list
                let html = '<div style="display: grid; gap: 0.5rem;">';
                units.forEach(unit => {
                    const statusColor = unit.status === 'Available' ? 'var(--green-600)' : (unit.status === 'Under Maintenance' ? 'var(--yellow-600)' : 'var(--red-600)');
                    html += `
                        <label style="display: flex; align-items: center; padding: 0.75rem; border: 1px solid var(--gray-200); border-radius: 0.375rem; cursor: pointer; background-color: white; transition: all 0.2s;" class="unit-checkbox-label">
                            <input type="checkbox" name="unit_ids[]" value="${unit.unit_id}" style="margin-right: 0.75rem; width: 1.125rem; height: 1.125rem; cursor: pointer;">
                            <div style="flex: 1;">
                                <div style="font-weight: 600; font-size: 0.875rem; color: var(--slate-900);">${equipmentName} #${unit.unit_number}</div>
                                ${unit.serial_number ? `<div style="font-size: 0.75rem; color: var(--gray-500); margin-top: 0.125rem;">SN: ${unit.serial_number}</div>` : ''}
                            </div>
                            <span style="display: inline-flex; align-items: center; padding: 0.25rem 0.625rem; border-radius: 9999px; font-size: 0.7rem; font-weight: 600; background-color: ${statusColor}; color: white;">
                                ${unit.status}
                            </span>
                        </label>
                    `;
                });
                html += '</div>';
                
                // Add Select All / Deselect All buttons
                html = `
                    <div style="display: flex; gap: 0.5rem; margin-bottom: 0.75rem;">
                        <button type="button" onclick="selectAllUnits()" class="btn btn-sm btn-secondary" style="flex: 1; font-size: 0.75rem; padding: 0.5rem;">Select All</button>
                        <button type="button" onclick="deselectAllUnits()" class="btn btn-sm btn-secondary" style="flex: 1; font-size: 0.75rem; padding: 0.5rem;">Deselect All</button>
                    </div>
                ` + html;
                
                unitsContainer.innerHTML = html;
            } catch (error) {
                unitsContainer.innerHTML = '<p style="color: var(--red-600); font-size: 0.875rem; text-align: center;">Error loading units. Please try again.</p>';
            }
            
            // Show modal
            document.getElementById('maintenanceModal').classList.add('show');
        }
        
        function selectAllUnits() {
            document.querySelectorAll('#unitsCheckboxList input[type="checkbox"]:not(:disabled)').forEach(cb => cb.checked = true);
        }
        
        function deselectAllUnits() {
            document.querySelectorAll('#unitsCheckboxList input[type="checkbox"]').forEach(cb => cb.checked = false);
        }

        function closeMaintenanceModal() {
            document.getElementById('maintenanceModal').classList.remove('show');
        }

        async function openCompleteMaintenanceModal(equipmentId, equipmentName) {
            // Set hidden equipment ID
            document.getElementById('completeEquipmentId').value = equipmentId;
            
            // Set display field
            document.getElementById('completeEquipmentDisplay').value = equipmentName + ' (' + equipmentId + ')';
            
            // Load units under maintenance for this equipment
            const unitsContainer = document.getElementById('unitsUnderMaintenanceList');
            unitsContainer.innerHTML = '<p style="color: var(--gray-500); font-size: 0.875rem; text-align: center;">Loading units...</p>';
            
            try {
                const response = await fetch('get_units_under_maintenance.php?equipment_id=' + equipmentId);
                const units = await response.json();
                
                if (units.error) {
                    unitsContainer.innerHTML = '<p style="color: var(--red-600); font-size: 0.875rem; text-align: center;">' + units.error + '</p>';
                    return;
                }
                
                if (units.length === 0) {
                    unitsContainer.innerHTML = '<p style="color: var(--gray-500); font-size: 0.875rem; text-align: center;">No units under maintenance found.</p>';
                    return;
                }
                
                // Build checkbox list
                let html = '<div style="display: grid; gap: 0.5rem;">';
                units.forEach(unit => {
                    html += `
                        <label style="display: flex; align-items: center; padding: 0.75rem; border: 1px solid var(--gray-200); border-radius: 0.375rem; cursor: pointer; background-color: white; transition: all 0.2s;" class="unit-checkbox-label">
                            <input type="checkbox" name="unit_ids[]" value="${unit.unit_id}" style="margin-right: 0.75rem; width: 1.125rem; height: 1.125rem; cursor: pointer;">
                            <div style="flex: 1;">
                                <div style="font-weight: 600; font-size: 0.875rem; color: var(--slate-900);">${equipmentName} #${unit.unit_number}</div>
                                ${unit.serial_number ? `<div style="font-size: 0.75rem; color: var(--gray-500); margin-top: 0.125rem;">SN: ${unit.serial_number}</div>` : ''}
                            </div>
                            <span style="display: inline-flex; align-items: center; padding: 0.25rem 0.625rem; border-radius: 9999px; font-size: 0.7rem; font-weight: 600; background-color: var(--yellow-600); color: white;">
                                Under Maintenance
                            </span>
                        </label>
                    `;
                });
                html += '</div>';
                
                unitsContainer.innerHTML = html;
            } catch (error) {
                unitsContainer.innerHTML = '<p style="color: var(--red-600); font-size: 0.875rem; text-align: center;">Error loading units. Please try again.</p>';
            }
            
            // Show modal
            document.getElementById('completeMaintenanceModal').classList.add('show');
        }
        
        function selectAllUnderMaintenance() {
            document.querySelectorAll('#unitsUnderMaintenanceList input[type="checkbox"]').forEach(cb => cb.checked = true);
        }
        
        function deselectAllUnderMaintenance() {
            document.querySelectorAll('#unitsUnderMaintenanceList input[type="checkbox"]').forEach(cb => cb.checked = false);
        }

        function closeCompleteMaintenanceModal() {
            document.getElementById('completeMaintenanceModal').classList.remove('show');
        }

        async function openMarkBrokenModal(equipmentId, equipmentName) {
            // Set hidden equipment ID
            document.getElementById('brokenEquipmentId').value = equipmentId;
            
            // Set display field
            document.getElementById('brokenEquipmentDisplay').value = equipmentName + ' (' + equipmentId + ')';
            
            // Load available and under maintenance units for this equipment
            const unitsContainer = document.getElementById('brokenUnitsList');
            unitsContainer.innerHTML = '<p style="color: var(--gray-500); font-size: 0.875rem; text-align: center;">Loading units...</p>';
            
            try {
                const response = await fetch('get_equipment_units.php?equipment_id=' + equipmentId);
                const units = await response.json();
                
                if (units.error) {
                    unitsContainer.innerHTML = '<p style="color: var(--red-600); font-size: 0.875rem; text-align: center;">' + units.error + '</p>';
                    return;
                }
                
                // Filter to show only Available and Under Maintenance units (not already broken)
                const availableUnits = units.filter(u => u.status === 'Available' || u.status === 'Under Maintenance');
                
                if (availableUnits.length === 0) {
                    unitsContainer.innerHTML = '<p style="color: var(--gray-500); font-size: 0.875rem; text-align: center;">No units available to mark as broken.</p>';
                    return;
                }
                
                // Build checkbox list
                let html = '<div style="display: grid; gap: 0.5rem;">';
                availableUnits.forEach(unit => {
                    const statusColor = unit.status === 'Available' ? 'var(--green-600)' : 'var(--yellow-600)';
                    html += `
                        <label style="display: flex; align-items: center; padding: 0.75rem; border: 1px solid var(--gray-200); border-radius: 0.375rem; cursor: pointer; background-color: white; transition: all 0.2s;" class="unit-checkbox-label">
                            <input type="checkbox" name="unit_ids[]" value="${unit.unit_id}" style="margin-right: 0.75rem; width: 1.125rem; height: 1.125rem; cursor: pointer;">
                            <div style="flex: 1;">
                                <div style="font-weight: 600; font-size: 0.875rem; color: var(--slate-900);">${equipmentName} #${unit.unit_number}</div>
                                ${unit.serial_number ? `<div style="font-size: 0.75rem; color: var(--gray-500); margin-top: 0.125rem;">SN: ${unit.serial_number}</div>` : ''}
                            </div>
                            <span style="display: inline-flex; align-items: center; padding: 0.25rem 0.625rem; border-radius: 9999px; font-size: 0.7rem; font-weight: 600; background-color: ${statusColor}; color: white;">
                                ${unit.status}
                            </span>
                        </label>
                    `;
                });
                html += '</div>';
                
                unitsContainer.innerHTML = html;
            } catch (error) {
                unitsContainer.innerHTML = '<p style="color: var(--red-600); font-size: 0.875rem; text-align: center;">Error loading units. Please try again.</p>';
            }
            
            // Show modal
            document.getElementById('markBrokenModal').classList.add('show');
        }

        function selectAllBroken() {
            document.querySelectorAll('#brokenUnitsList input[type="checkbox"]').forEach(cb => cb.checked = true);
        }

        function deselectAllBroken() {
            document.querySelectorAll('#brokenUnitsList input[type="checkbox"]').forEach(cb => cb.checked = false);
        }

        function closeMarkBrokenModal() {
            document.getElementById('markBrokenModal').classList.remove('show');
        }

        // Equipment History Functions
        async function openEquipmentHistory(equipmentId, equipmentName) {
            const modal = document.getElementById('equipmentHistoryModal');
            const nameDisplay = document.getElementById('historyEquipmentName');
            const contentDiv = document.getElementById('historyContent');
            
            // Set equipment name
            nameDisplay.innerHTML = `<h4 style="margin: 0; font-size: 1.125rem; color: var(--slate-900);">${equipmentName}</h4>`;
            
            // Show loading state
            contentDiv.innerHTML = '<p style="text-align: center; padding: 2rem; color: var(--gray-500);">Loading history...</p>';
            
            // Show modal
            modal.classList.add('show');
            
            try {
                const response = await fetch(`get_equipment_history.php?equipment_id=${equipmentId}`);
                if (!response.ok) throw new Error('Failed to fetch history');
                
                const history = await response.json();
                
                if (history.length === 0) {
                    contentDiv.innerHTML = '<p style="text-align: center; padding: 2rem; color: var(--gray-500);">No maintenance history found.</p>';
                    return;
                }
                
                // Build history table
                let html = `
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                            <thead>
                                <tr style="background-color: var(--gray-50); border-bottom: 2px solid var(--gray-200);">
                                    <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: var(--slate-700);">Date</th>
                                    <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: var(--slate-700);">Type</th>
                                    <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: var(--slate-700);">Units</th>
                                    <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: var(--slate-700);">Status</th>
                                    <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: var(--slate-700);">Cost</th>
                                    <th style="padding: 0.75rem; text-align: left; font-weight: 600; color: var(--slate-700);">Performed By</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                
                history.forEach((record, index) => {
                    const statusColor = record.status === 'Completed' ? 'var(--green-600)' : 
                                       record.status === 'In Progress' ? 'var(--yellow-600)' : 
                                       record.status === 'Scheduled' ? 'var(--blue-600)' : 'var(--gray-600)';
                    const bgColor = index % 2 === 0 ? 'white' : 'var(--gray-50)';
                    
                    html += `
                        <tr style="background-color: ${bgColor}; border-bottom: 1px solid var(--gray-200);">
                            <td style="padding: 0.75rem; color: var(--slate-600);">${record.maintenance_date}</td>
                            <td style="padding: 0.75rem; color: var(--slate-900); font-weight: 500;">${record.maintenance_type}</td>
                            <td style="padding: 0.75rem; color: var(--slate-600);">${record.units}</td>
                            <td style="padding: 0.75rem;">
                                <span style="color: ${statusColor}; font-weight: 600;">${record.status}</span>
                                ${record.completion_date ? `<div style="font-size: 0.75rem; color: var(--gray-500);">Completed: ${record.completion_date}</div>` : ''}
                            </td>
                            <td style="padding: 0.75rem; color: var(--slate-600);">₱${parseFloat(record.cost || 0).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                            <td style="padding: 0.75rem; color: var(--slate-600);">${record.performed_by || '-'}</td>
                        </tr>
                        ${record.description ? `
                        <tr style="background-color: ${bgColor};">
                            <td colspan="6" style="padding: 0 0.75rem 0.75rem 0.75rem; color: var(--gray-600); font-size: 0.813rem;">
                                <strong>Description:</strong> ${record.description}
                            </td>
                        </tr>
                        ` : ''}
                    `;
                });
                
                html += `
                            </tbody>
                        </table>
                    </div>
                `;
                
                contentDiv.innerHTML = html;
            } catch (error) {
                contentDiv.innerHTML = '<p style="text-align: center; padding: 2rem; color: var(--red-600);">Error loading history. Please try again.</p>';
                console.error('Error fetching equipment history:', error);
            }
        }

        function closeHistoryModal() {
            document.getElementById('equipmentHistoryModal').classList.remove('show');
        }

        // Close modals when clicking outside
        const equipmentModal = document.getElementById('equipmentModal');
        const maintenanceModal = document.getElementById('maintenanceModal');
        const completeMaintenanceModal = document.getElementById('completeMaintenanceModal');
        const markBrokenModal = document.getElementById('markBrokenModal');
        const equipmentHistoryModal = document.getElementById('equipmentHistoryModal');
        
        if (equipmentModal) {
            equipmentModal.addEventListener('click', function(e) {
                if (e.target === equipmentModal) closeModal();
            });
        }
        
        if (maintenanceModal) {
            maintenanceModal.addEventListener('click', function(e) {
                if (e.target === maintenanceModal) closeMaintenanceModal();
            });
        }

        if (completeMaintenanceModal) {
            completeMaintenanceModal.addEventListener('click', function(e) {
                if (e.target === completeMaintenanceModal) closeCompleteMaintenanceModal();
            });
        }

        if (markBrokenModal) {
            markBrokenModal.addEventListener('click', function(e) {
                if (e.target === markBrokenModal) closeMarkBrokenModal();
            });
        }

        if (equipmentHistoryModal) {
            equipmentHistoryModal.addEventListener('click', function(e) {
                if (e.target === equipmentHistoryModal) closeHistoryModal();
            });
        }

        // Close modals on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                if (equipmentModal && equipmentModal.classList.contains('show')) closeModal();
                if (maintenanceModal && maintenanceModal.classList.contains('show')) closeMaintenanceModal();
                if (completeMaintenanceModal && completeMaintenanceModal.classList.contains('show')) closeCompleteMaintenanceModal();
                if (markBrokenModal && markBrokenModal.classList.contains('show')) closeMarkBrokenModal();
                if (equipmentHistoryModal && equipmentHistoryModal.classList.contains('show')) closeHistoryModal();
            }
        });

        function deleteEquipment(equipmentId) {
            if (confirm('Are you sure you want to delete this equipment?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_equipment">
                    <input type="hidden" name="equipment_id" value="${equipmentId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }


    </script>

    <?php if ($edit_equipment): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('modalTitle').textContent = 'Edit Equipment';
        document.getElementById('formAction').value = 'update_equipment';
        document.getElementById('editEquipmentId').value = <?php echo json_encode($edit_equipment['equipment_id']); ?>;
        document.getElementById('equipment_name').value = <?php echo json_encode($edit_equipment['equipment_name']); ?>;
        document.getElementById('category').value = <?php echo json_encode($edit_equipment['category']); ?>;
        document.getElementById('purchase_date').value = <?php echo json_encode($edit_equipment['purchase_date']); ?>;
        document.getElementById('purchase_price').value = <?php echo json_encode($edit_equipment['purchase_price']); ?>;
        document.getElementById('notes').value = <?php echo json_encode($edit_equipment['notes']); ?>;
        // Change the quantity field label when editing
        var qtyLabel = document.querySelector('label[for="quantity_to_add"]');
        if (qtyLabel) {
            qtyLabel.innerHTML = 'Additional Quantity to Add (optional)';
        }
        var qtyInput = document.getElementById('quantity_to_add');
        if (qtyInput) {
            qtyInput.value = '0';
            qtyInput.min = '0';
            qtyInput.required = false;
        }
        // Only show modal if not already open
        var modal = document.getElementById('equipmentModal');
        if (modal && !modal.classList.contains('show')) {
            modal.classList.add('show');
        }
    });
    </script>
    <?php endif; ?>

