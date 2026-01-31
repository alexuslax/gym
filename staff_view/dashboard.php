<?php
session_start();
require_once '../config/functions.php';

// Get dashboard statistics
$stats = getDashboardStats();
$recent_activities = getRecentActivity();
?>
  <?php $page_title = 'Dashboard - UEP Fitness Gym'; include '../header.php'; ?>

<style>
  .staff-dashboard {
    padding: 2rem 0;
  }

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

  .stats-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 1.5rem;
    margin-bottom: 3rem;
  }

  @media (max-width: 1600px) {
    .stats-grid {
      grid-template-columns: repeat(5, 1fr);
    }
  }

  @media (max-width: 1200px) {
    .stats-grid {
      grid-template-columns: repeat(3, 1fr);
    }
  }

  @media (max-width: 768px) {
    .stats-grid {
      grid-template-columns: 1fr;
    }
  }

  .card-stats {
    position: relative;
    background: white;
    border-radius: 1.25rem;
    padding: 1.25rem;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(0, 0, 0, 0.05);
    overflow: hidden;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    flex-direction: column;
  }

  .card-stats:hover {
    transform: translateY(-6px);
    box-shadow: 0 25px 40px -5px rgba(0, 0, 0, 0.15);
  }

  .card-stats-blue {
    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
  }

  .card-stats-blue:hover {
    box-shadow: 0 30px 60px -15px rgba(59, 130, 246, 0.15);
  }

  .card-stats-green {
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
  }

  .card-stats-green:hover {
    box-shadow: 0 30px 60px -15px rgba(34, 197, 94, 0.15);
  }

  .card-stats-purple {
    background: linear-gradient(135deg, #faf5ff 0%, #f3e8ff 100%);
  }

  .card-stats-purple:hover {
    box-shadow: 0 30px 60px -15px rgba(168, 85, 247, 0.15);
  }

  .card-stats-amber {
    background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
  }

  .card-stats-amber:hover {
    box-shadow: 0 30px 60px -15px rgba(245, 158, 11, 0.15);
  }

  .card-stats-indigo {
    background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);
  }

  .card-stats-indigo:hover {
    box-shadow: 0 30px 60px -15px rgba(99, 102, 241, 0.15);
  }

  .stats-decoration {
    position: absolute;
    opacity: 0.08;
    border-radius: 9999px;
  }

  .stats-decoration:hover {
    opacity: 0.12;
  }

  .stats-decoration-blue {
    width: 220px;
    height: 220px;
    background: #667eea;
    top: -60px;
    right: -60px;
  }

  .stats-decoration-green {
    width: 220px;
    height: 220px;
    background: #22c55e;
    top: -60px;
    right: -60px;
  }

  .stats-decoration-purple {
    width: 220px;
    height: 220px;
    background: #a855f7;
    top: -60px;
    right: -60px;
  }

  .stats-decoration-amber {
    width: 220px;
    height: 220px;
    background: #f59e0b;
    top: -60px;
    right: -60px;
  }

  .stats-decoration-indigo {
    width: 220px;
    height: 220px;
    background: #6366f1;
    top: -60px;
    right: -60px;
  }
  .stat-card-blue::before {
	background: #3b82f6;
}

.stat-card-amber::before {
	background: #f59e0b;
}

.stat-card-green::before {
	background: #22c55e;
}

.stat-card-indigo::before {
	background: #6366f1;
}

  .stats-icon-container {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2.75rem;
    height: 2.75rem;
    border-radius: 0.75rem;
    margin: 0 0 0.75rem 0;
    padding: 0;
    color: white;
    font-weight: 600;
    position: relative;
    z-index: 1;
    flex-shrink: 0;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  }

  .card-stats:hover .stats-icon-container {
    transform: rotate(5deg) scale(1.1);
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.3);
  }

  .stats-icon-container-blue {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    box-shadow: 0 8px 16px rgba(102, 126, 234, 0.3);
  }

  .stats-icon-container-green {
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    box-shadow: 0 8px 16px rgba(34, 197, 94, 0.3);
  }

  .stats-icon-container-purple {
    background: linear-gradient(135deg, #a855f7 0%, #7e22ce 100%);
    box-shadow: 0 8px 16px rgba(168, 85, 247, 0.3);
  }

  .stats-icon-container-amber {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    box-shadow: 0 8px 16px rgba(245, 158, 11, 0.3);
  }

  .stats-icon-container-indigo {
    background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
    box-shadow: 0 8px 16px rgba(99, 102, 241, 0.3);
  }

  .stats-icon-container-slate {
    background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
    box-shadow: 0 8px 16px rgba(99, 102, 241, 0.3);
  }

  .stats-label {
    font-size: 0.7rem;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.4rem;
    position: relative;
    z-index: 1;
    transition: all 0.3s ease;
  }

  .card-stats:hover .stats-label {
    color: #475569;
    letter-spacing: 0.7px;
  }

  .stats-value {
    font-size: 1.75rem;
    font-weight: 900;
    color: #0f172a;
    line-height: 1;
    position: relative;
    z-index: 1;
    transition: all 0.3s ease;
  }

  .card-stats:hover .stats-value {
    transform: scale(1.05);
  }

  .equipment-status-list {
    margin-top: 0.5rem;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
  }

  .equipment-status-item {
    font-size: 0.75rem;
    color: #475569;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .equipment-status-value {
    font-weight: 700;
    padding: 0.25rem 0.75rem;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    transition: all 0.3s ease;
    cursor: pointer;
  }

  .equipment-status-value:hover {
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  }

  .equipment-status-value-green {
    background: #dcfce7;
    color: #166534;
  }

  .equipment-status-value-blue {
    background: #dbeafe;
    color: #1e40af;
  }

  .equipment-status-value-red {
    background: #fee2e2;
    color: #991b1b;
  }

  .section-header {
    margin: 3rem 0 2rem 0;
    transition: all 0.3s ease;
  }

  .section-header:hover {
    transform: translateX(5px);
  }

  .section-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 0.5rem;
    position: relative;
    display: inline-block;
  }

  .section-title::after {
    content: '';
    position: absolute;
    width: 0;
    height: 3px;
    bottom: -5px;
    left: 0;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    transition: width 0.3s ease;
  }

  .section-header:hover .section-title::after {
    width: 60px;
  }

  .section-subtitle {
    font-size: 0.9375rem;
    color: #64748b;
  }

  .quick-access-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-bottom: 3rem;
  }

  .quick-access-card {
    position: relative;
    display: block;
    padding: 1.75rem;
    border-radius: 1rem;
    background: white;
    border: 1px solid rgba(0, 0, 0, 0.05);
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
    text-decoration: none;
    color: inherit;
  }

  .quick-access-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 20px 40px -5px rgba(0, 0, 0, 0.12);
  }

  .quick-access-overlay {
    position: absolute;
    opacity: 0.08;
    border-radius: 9999px;
  }

  .quick-access-overlay-blue {
    width: 250px;
    height: 250px;
    background: #667eea;
    top: -100px;
    right: -100px;
  }

  .quick-access-overlay-green {
    width: 250px;
    height: 250px;
    background: #22c55e;
    top: -100px;
    right: -100px;
  }

  .quick-access-overlay-purple {
    width: 250px;
    height: 250px;
    background: #a855f7;
    top: -100px;
    right: -100px;
  }

  .quick-access-overlay-indigo {
    width: 250px;
    height: 250px;
    background: #e11d48;
    top: -100px;
    right: -100px;
  }

  .quick-access-overlay-amber {
    width: 250px;
    height: 250px;
    background: #eab308;
    top: -100px;
    right: -100px;
  }

  .quick-access-overlay-red {
    width: 250px;
    height: 250px;
    background: #dc2626;
    top: -100px;
    right: -100px;
  }

  .quick-access-overlay-teal {
    width: 250px;
    height: 250px;
    background: #06b6d4;
    top: -100px;
    right: -100px;
  }

  .quick-access-icon-container {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }

  .quick-access-content {
    flex: 1;
  }

  .quick-access-title {
    font-size: 1.125rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
  }

  .quick-access-title-blue {
    color: #2563eb;
  }

  .quick-access-title-green {
    color: #16a34a;
  }

  .quick-access-title-purple {
    color: #f97316;
  }

  .quick-access-title-indigo {
    color: #e11d48;
  }

  .quick-access-title-amber {
    color: #eab308;
  }

  .quick-access-title-red {
    color: #dc2626;
  }

  .quick-access-title-teal {
    color: #06b6d4;
  }

  .quick-access-description {
    font-size: 0.875rem;
    color: #64748b;
  }

  .quick-access-arrow {
    color: #94a3b8;
    transition: all 0.3s ease;
  }

  .quick-access-card:hover .quick-access-arrow {
    color: #0f172a;
    transform: translateX(4px);
  }

  .recent-activity-list {
    padding: 1.5rem;
  }

  .table-divide {
    list-style: none;
    padding: 0;
    margin: 0;
  }

  .recent-activity-item {
    padding: 1rem 0;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    align-items: flex-start;
    transition: all 0.3s ease;
    border-radius: 0.5rem;
    margin: 0 -0.5rem;
    padding: 1rem 0.5rem;
  }

  .recent-activity-item:hover {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    transform: translateX(5px);
    border-left: 3px solid #667eea;
  }

  .recent-activity-item:last-child {
    border-bottom: none;
  }

  .recent-activity-dot {
    width: 0.5rem;
    height: 0.5rem;
    border-radius: 9999px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    flex-shrink: 0;
    margin-top: 0.375rem;
    transition: all 0.3s ease;
  }

  .recent-activity-item:hover .recent-activity-dot {
    transform: scale(1.5);
    box-shadow: 0 0 12px rgba(102, 126, 234, 0.5);
  }

  .recent-activity-message {
    font-size: 0.9375rem;
    color: #0f172a;
    margin-bottom: 0.25rem;
    transition: all 0.3s ease;
  }

  .recent-activity-item:hover .recent-activity-message {
    color: #667eea;
    font-weight: 600;
  }

  .recent-activity-time {
    font-size: 0.8125rem;
    color: #94a3b8;
  }

  .empty-activity-state {
    text-align: center;
    padding: 2rem !important;
    border: none !important;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
  }

  .empty-activity-icon {
    width: 3rem;
    height: 3rem;
    color: #cbd5e1;
  }

  .empty-activity-state p {
    color: #94a3b8;
    font-size: 0.9375rem;
  }

  @media (max-width: 1024px) {
    .quick-access-grid {
      grid-template-columns: 1fr;
    }
  }

  @media (max-width: 768px) {
    .quick-access-grid {
      grid-template-columns: 1fr;
    }

    .page-title {
      font-size: 1.875rem;
    }
  }
</style>

<div class="staff-dashboard">
  <!-- Welcome Message -->
  <div class="page-header">
    <h2 class="page-title">Welcome back, Staff!</h2>
    <p class="page-subtitle">Here's a quick overview of your gym operations.</p>
  </div>

  <!-- Quick Stats / Key Metrics -->
  <div class="stats-grid">
    <div class="card-stats card-stats-blue">
      <div class="stats-decoration stats-decoration-blue"></div>
      <div>
        <div class="stats-icon-container stats-icon-container-blue">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="1.8" style="width: 1.75rem; height: 1.75rem;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.5v-1.125A3.375 3.375 0 0 0 11.625 15h-3.75A3.375 3.375 0 0 0 4.5 18.375V19.5M12 10.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0M18 8.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0M20.25 19.5v-1.125A3.375 3.375 0 0 0 17.25 15h-1.125"/>
          </svg>
        </div>
        <h3 class="stats-label">Total Members</h3>
        <p class="stats-value"><?php echo $stats['total_members']; ?></p>
      </div>
    </div>
    
    <div class="card-stats card-stats-green">
      <div class="stats-decoration stats-decoration-green"></div>
      <div>
        <div class="stats-icon-container stats-icon-container-green">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="1.8" style="width: 1.75rem; height: 1.75rem;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/>
          </svg>
        </div>
        <h3 class="stats-label">Active Memberships</h3>
        <p class="stats-value"><?php echo $stats['active_members']; ?></p>
      </div>
    </div>
    
    <div class="card-stats card-stats-purple">
      <div class="stats-decoration stats-decoration-purple"></div>
      <div>
        <div class="stats-icon-container stats-icon-container-purple">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="1.8" style="width: 1.75rem; height: 1.75rem;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5A2.25 2.25 0 0 1 5.25 5.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25M3 18.75A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75M3 18.75v-8.25A2.25 2.25 0 0 1 5.25 8.25h13.5A2.25 2.25 0 0 1 21 10.5v8.25"/>
          </svg>
        </div>
        <h3 class="stats-label">Attendance Today</h3>
        <p class="stats-value"><?php echo $stats['today_attendance']; ?></p>
      </div>
    </div>
    
    <div class="card-stats card-stats-amber">
      <div class="stats-decoration stats-decoration-amber"></div>
      <div>
        <div class="stats-icon-container stats-icon-container-amber">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="1.8" style="width: 1.75rem; height: 1.75rem;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"/>
          </svg>
        </div>
        <h3 class="stats-label">Pending Payments</h3>
        <p class="stats-value"><?php echo $stats['pending_payments']; ?></p>
      </div>
    </div>
    
    <div class="card-stats card-stats-indigo">
      <div class="stats-decoration stats-decoration-indigo"></div>
      <div>
        <div class="stats-icon-container stats-icon-container-indigo">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="1.8" style="width: 1.75rem; height: 1.75rem;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h4.5v10.5h-4.5zM15.75 6.75h4.5v10.5h-4.5zM10.5 9.75h3v4.5h-3z"/>
          </svg>
        </div>
        <h3 class="stats-label">Equipment Status</h3>
        <div class="equipment-status-list">
          <p class="equipment-status-item">Avail: <span class="equipment-status-value equipment-status-value-green"><?php echo $stats['equipment']['working']; ?></span></p>
          <p class="equipment-status-item">In-use: <span class="equipment-status-value equipment-status-value-blue"><?php echo $stats['equipment']['maintenance']; ?></span></p>
          <p class="equipment-status-item">Maint: <span class="equipment-status-value equipment-status-value-red"><?php echo $stats['equipment']['repair']; ?></span></p>
        </div>
      </div>
    </div>
  </div>

  <div class="section-header">
    <h2 class="section-title">Quick Access</h2>
    <p class="section-subtitle">Navigate to key sections of your gym management system</p>
  </div>
  
  <!-- Dashboard Cards -->
  <div class="quick-access-grid">
    <a href="attendance.php" class="quick-access-card quick-access-card-blue">
      <div class="quick-access-overlay quick-access-overlay-blue"></div>
      <div style="position: relative; display: flex; align-items: flex-start; gap: 1rem;">
        <div class="quick-access-icon-container">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2.5" style="width: 1.75rem; height: 1.75rem;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5A2.25 2.25 0 0 1 5.25 5.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25M3 18.75A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75M3 18.75v-8.25A2.25 2.25 0 0 1 5.25 8.25h13.5A2.25 2.25 0 0 1 21 10.5v8.25"/>
          </svg>
        </div>
        <div class="quick-access-content">
          <h2 class="quick-access-title quick-access-title-blue">Attendance</h2>
          <p class="quick-access-description">View RFID attendance records</p>
        </div>
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="quick-access-arrow quick-access-arrow-blue" style="width: 1.5rem; height: 1.5rem; flex-shrink: 0;">
          <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
        </svg>
      </div>
    </a>

    <a href="billing.php" class="quick-access-card quick-access-card-green">
      <div class="quick-access-overlay quick-access-overlay-green"></div>
      <div style="position: relative; display: flex; align-items: flex-start; gap: 1rem;">
        <div class="quick-access-icon-container">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5" style="width: 1.75rem; height: 1.75rem;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 7.5A2.25 2.25 0 0 1 4.5 5.25h15A2.25 2.25 0 0 1 21.75 7.5v9A2.25 2.25 0 0 1 19.5 18.75h-15A2.25 2.25 0 0 1 2.25 16.5v-9z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 9.75h19.5"/>
          </svg>
        </div>
        <div class="quick-access-content">
          <h2 class="quick-access-title quick-access-title-green">Billing</h2>
          <p class="quick-access-description">Manage subscriptions & payments</p>
        </div>
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="quick-access-arrow quick-access-arrow-green" style="width: 1.5rem; height: 1.5rem; flex-shrink: 0;">
          <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
        </svg>
      </div>
    </a>

    <a href="equipment.php" class="quick-access-card quick-access-card-purple">
      <div class="quick-access-overlay quick-access-overlay-purple"></div>
      <div style="position: relative; display: flex; align-items: flex-start; gap: 1rem;">
        <div class="quick-access-icon-container">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#f97316" stroke-width="2.5" style="width: 1.75rem; height: 1.75rem;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h4.5v10.5h-4.5zM15.75 6.75h4.5v10.5h-4.5zM10.5 9.75h3v4.5h-3z"/>
          </svg>
        </div>
        <div class="quick-access-content">
          <h2 class="quick-access-title quick-access-title-purple">Equipment</h2>
          <p class="quick-access-description">Track gym equipment & maintenance</p>
        </div>
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="quick-access-arrow quick-access-arrow-purple" style="width: 1.5rem; height: 1.5rem; flex-shrink: 0;">
          <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
        </svg>
      </div>
    </a>

    <a href="members.php" class="quick-access-card quick-access-card-indigo">
      <div class="quick-access-overlay quick-access-overlay-indigo"></div>
      <div style="position: relative; display: flex; align-items: flex-start; gap: 1rem;">
        <div class="quick-access-icon-container">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#e11d48" stroke-width="2.5" style="width: 1.75rem; height: 1.75rem;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.5v-1.125A3.375 3.375 0 0 0 11.625 15h-3.75A3.375 3.375 0 0 0 4.5 18.375V19.5M12 10.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0M18 8.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0M20.25 19.5v-1.125A3.375 3.375 0 0 0 17.25 15h-1.125"/>
          </svg>
        </div>
        <div class="quick-access-content">
          <h2 class="quick-access-title quick-access-title-indigo">Members</h2>
          <p class="quick-access-description">Manage member profiles & subscriptions</p>
        </div>
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="quick-access-arrow quick-access-arrow-indigo" style="width: 1.5rem; height: 1.5rem; flex-shrink: 0;">
          <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
        </svg>
      </div>
    </a>

    <a href="trainers.php" class="quick-access-card quick-access-card-amber">
      <div class="quick-access-overlay quick-access-overlay-amber"></div>
      <div style="position: relative; display: flex; align-items: flex-start; gap: 1rem;">
        <div class="quick-access-icon-container">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#eab308" stroke-width="2.5" style="width: 1.75rem; height: 1.75rem;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v-1.125A3.375 3.375 0 0 0 12.375 12.75h-3.75A3.375 3.375 0 0 0 5.25 16.125V17.25M12.75 9A3 3 0 1 1 6.75 9a3 3 0 0 1 6 0M18.75 8.25l2.25 2.25-6 6-2.25.75.75-2.25 6-6z"/>
          </svg>
        </div>
        <div class="quick-access-content">
          <h2 class="quick-access-title quick-access-title-amber">Trainers</h2>
          <p class="quick-access-description">Add, edit, & manage trainers</p>
        </div>
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="quick-access-arrow quick-access-arrow-amber" style="width: 1.5rem; height: 1.5rem; flex-shrink: 0;">
          <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
        </svg>
      </div>
    </a>

    <a href="vitals.php" class="quick-access-card quick-access-card-red">
      <div class="quick-access-overlay quick-access-overlay-red"></div>
      <div style="position: relative; display: flex; align-items: flex-start; gap: 1rem;">
        <div class="quick-access-icon-container">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2.5" style="width: 1.75rem; height: 1.75rem;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0 4.556-4.5 7.5-9 11.25-4.5-3.75-9-6.694-9-11.25a5.25 5.25 0 0 1 9-3.656A5.25 5.25 0 0 1 21 8.25z"/>
          </svg>
        </div>
        <div class="quick-access-content">
          <h2 class="quick-access-title quick-access-title-red">Vital Signs</h2>
          <p class="quick-access-description">Track member health & progress</p>
        </div>
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="quick-access-arrow quick-access-arrow-red" style="width: 1.5rem; height: 1.5rem; flex-shrink: 0;">
          <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
        </svg>
      </div>
    </a>

    <a href="progress.php" class="quick-access-card quick-access-card-teal">
      <div class="quick-access-overlay quick-access-overlay-teal"></div>
      <div style="position: relative; display: flex; align-items: flex-start; gap: 1rem;">
        <div class="quick-access-icon-container">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#06b6d4" stroke-width="2.5" style="width: 1.75rem; height: 1.75rem;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
          </svg>
        </div>
        <div class="quick-access-content">
          <h2 class="quick-access-title quick-access-title-teal">Progress</h2>
          <p class="quick-access-description">View member progress & analytics</p>
        </div>
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="quick-access-arrow quick-access-arrow-teal" style="width: 1.5rem; height: 1.5rem; flex-shrink: 0;">
          <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
        </svg>
      </div>
    </a>
  </div>

  <!-- Recent Activity / Notifications -->
  <div>
    <h2 class="section-title" style="margin-bottom: 1rem;">Recent Activity</h2>
    <div class="card" style="overflow: hidden;">
      <div class="recent-activity-list">
        <ul class="table-divide">
          <?php if (empty($recent_activities)): ?>
            <li class="empty-activity-state">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="empty-activity-icon">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
              </svg>
              <p>No recent activity to display</p>
            </li>
          <?php else: ?>
            <?php foreach ($recent_activities as $index => $activity): ?>
              <li class="recent-activity-item">
                <div style="display: flex; align-items: flex-start; gap: 0.75rem; width: 100%;">
                  <div class="recent-activity-dot"></div>
                  <div style="flex: 1;">
                    <p class="recent-activity-message"><?php echo htmlspecialchars($activity['message']); ?></p>
                    <?php if (isset($activity['timestamp'])): ?>
                      <p class="recent-activity-time"><?php echo htmlspecialchars($activity['timestamp']); ?></p>
                    <?php endif; ?>
                  </div>
                </div>
              </li>
            <?php endforeach; ?>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </div>
</div>

<?php include '../footer.php'; ?>
