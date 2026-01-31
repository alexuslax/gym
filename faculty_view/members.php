<?php
require_once '../config/database.php';
require_once '../config/functions.php';

// Authentication Check
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'trainer') {
    header('Location: ../index.php');
    exit();
}

// Get trainer_id from username
$username = $_SESSION['username'] ?? null;
if (!$username) {
    header('Location: ../index.php');
    exit();
}

$stmt = $pdo->prepare("SELECT trainer_id FROM trainers WHERE username = ?");
$stmt->execute([$username]);
$trainer = $stmt->fetch();
$trainer_id = $trainer['trainer_id'] ?? null;

if (!$trainer_id) {
    header('Location: ../index.php');
    exit();
}

$page_title = 'My Members';
$search = $_GET['search'] ?? '';

// Fetch assigned members
$where_clause = "WHERE ta.trainer_id = ?";
$params = [$trainer_id];

if (!empty($search)) {
    $where_clause .= " AND (m.first_name LIKE ? OR m.last_name LIKE ? OR m.member_id LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$sql = "SELECT DISTINCT m.*, 
        COUNT(DISTINCT ta.assignment_id) as total_sessions,
        COUNT(DISTINCT CASE WHEN ta.status = 'Completed' THEN ta.assignment_id END) as completed_sessions
        FROM members m
        INNER JOIN trainer_assignments ta ON m.member_id COLLATE utf8mb4_unicode_ci = ta.member_id COLLATE utf8mb4_unicode_ci
        $where_clause
        GROUP BY m.member_id
        ORDER BY m.first_name, m.last_name";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

include '../header.php';
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
</style>

<div class="members-container">
	<!-- Page Header -->
	<div class="page-header">
		<h1 class="page-title">My Members</h1>
		<p class="page-subtitle">View and manage your assigned members.</p>
	</div>

	<!-- Search and Filters -->
	<div class="card search-card">
		<form method="GET" action="" class="search-form">
			<div class="search-input-wrapper">
				<div class="search-icon">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-sm">
						<path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
					</svg>
				</div>
				<input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
					   placeholder="Search by name or member ID..." 
					   class="search-input">
			</div>
			<button type="submit" class="btn-search">
				Search
			</button>
		</form>
	</div>

	<!-- Members Grid -->
	<?php if (!empty($members)): ?>
		<div class="members-grid">
			<?php foreach ($members as $member): ?>
				<div class="member-card">
					<div class="member-card-body">
						<div class="member-card-header">
							<div class="member-card-avatar">
								<?php 
								$profile_pic = null;
								if (!empty($member['profile_picture'])) {
									$pic = trim($member['profile_picture']);
									// Check if it's an absolute URL
									if (preg_match('#^https?://#', $pic)) {
										$profile_pic = $pic;
									} else {
										// Check for paths starting with img/profiles/, uploads/, assets/
										if (preg_match('#^(img|uploads|assets)/#', $pic)) {
											// Try with different extensions if no extension provided
											$ext = pathinfo($pic, PATHINFO_EXTENSION);
											if ($ext) {
												if (file_exists(__DIR__ . '/../' . $pic)) {
													$profile_pic = '../' . $pic;
												}
											} else {
												// No extension, try common image extensions
												foreach (['jpg', 'jpeg', 'png', 'gif'] as $ext) {
													if (file_exists(__DIR__ . '/../' . $pic . '.' . $ext)) {
														$profile_pic = '../' . $pic . '.' . $ext;
														break;
													}
												}
											}
										} else {
											// Bare filename: look under img/profiles/
											$ext = pathinfo($pic, PATHINFO_EXTENSION);
											if ($ext) {
												if (file_exists(__DIR__ . '/../img/profiles/' . $pic)) {
													$profile_pic = '../img/profiles/' . $pic;
												}
											} else {
												// No extension, try common image extensions
												foreach (['jpg', 'jpeg', 'png', 'gif'] as $ext) {
													if (file_exists(__DIR__ . '/../img/profiles/' . $pic . '.' . $ext)) {
														$profile_pic = '../img/profiles/' . $pic . '.' . $ext;
														break;
													}
												}
											}
										}
									}
								}
								
								if ($profile_pic): ?>
									<img src="<?php echo htmlspecialchars($profile_pic); ?>" class="member-avatar-img" alt="">
								<?php else: ?>
									<span class="member-card-initial">
										<?php echo strtoupper(substr($member['first_name'], 0, 1)); ?>
									</span>
								<?php endif; ?>
							</div>
						</div>
							<div class="member-card-info">
								<h3 class="member-card-name">
									<?php echo htmlspecialchars(trim($member['first_name'] . ' ' . ($member['middle_name'] ? $member['middle_name'] . ' ' : '') . $member['last_name'])); ?>
								</h3>
								<p class="member-card-id"><?php echo htmlspecialchars($member['member_id']); ?></p>
								<p class="member-card-contact"><?php echo htmlspecialchars($member['contact_number']); ?></p>
							</div>
						</div>

						<div class="member-card-stats">
							<div class="member-stat-box member-stat-blue">
								<p class="member-stat-label">Total Sessions</p>
								<p class="member-stat-value"><?php echo $member['total_sessions']; ?></p>
							</div>
							<div class="member-stat-box member-stat-green">
								<p class="member-stat-label">Completed</p>
								<p class="member-stat-value"><?php echo $member['completed_sessions']; ?></p>
							</div>
						</div>

						<div class="member-card-footer">
							<span class="status-badge <?php echo $member['membership_status'] === 'Active' ? 'status-badge-green' : 'status-badge-gray'; ?>">
								<?php echo htmlspecialchars($member['membership_status']); ?>
							</span>
							<a href="../member_profile.php?id=<?php echo $member['member_id']; ?>&return=trainer_view/members.php" 
							   class="btn-view-profile">
								<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24\" fill="none" stroke="currentColor" stroke-width="2" class="icon-xs">
									<path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/>
									<path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
								</svg>
								View Profile
							</a>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	<?php else: ?>
		<div class="empty-state">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="empty-state-icon">
				<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.5v-1.125A3.375 3.375 0 0 0 11.625 15h-3.75A3.375 3.375 0 0 0 4.5 18.375V19.5M12 10.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0M18 8.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0M20.25 19.5v-1.125A3.375 3.375 0 0 0 17.25 15h-1.125"/>
			</svg>
			<p class="empty-state-title">No members found</p>
			<p class="empty-state-subtitle"><?php echo !empty($search) ? 'Try adjusting your search criteria.' : 'You don\'t have any assigned members yet.'; ?></p>
		</div>
	<?php endif; ?>
</div>

<?php include '../footer.php'; ?>

