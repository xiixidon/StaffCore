<?php
$current = basename($_SERVER['PHP_SELF'], '.php');
?>
<link rel="stylesheet" href="shared.css"/>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet"/>

<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <div class="brand-icon">
      <svg width="22" height="22" viewBox="0 0 28 28" fill="none">
        <rect x="2" y="2" width="10" height="10" rx="2" fill="#00e5ff"/>
        <rect x="16" y="2" width="10" height="10" rx="2" fill="#00e5ff" opacity="0.5"/>
        <rect x="2" y="16" width="10" height="10" rx="2" fill="#00e5ff" opacity="0.5"/>
        <rect x="16" y="16" width="10" height="10" rx="2" fill="#00e5ff"/>
      </svg>
    </div>
    <span>StaffCore</span>
  </div>
  <nav class="sidebar-nav">
    <a href="admin_dashboard.php" class="nav-item <?= $current==='admin_dashboard'?'active':'' ?>">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
      Dashboard
    </a>
    <a href="employees.php" class="nav-item <?= $current==='employees'?'active':'' ?>">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      Employees
    </a>
    <a href="departments.php" class="nav-item <?= $current==='departments'?'active':'' ?>">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
      Departments
    </a>
    <a href="attendance.php" class="nav-item <?= $current==='attendance'?'active':'' ?>">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
      Attendance
    </a>
    <a href="leave_admin.php" class="nav-item <?= $current==='leave_admin'?'active':'' ?>">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
      Leave
      <?php
        // Show badge if pending leaves
        try {
          global $pdo;
          if(isset($pdo)) {
            $p = $pdo->query("SELECT COUNT(*) FROM leave_requests WHERE status='pending'")->fetchColumn();
            if($p > 0) echo "<span style='margin-left:auto;background:rgba(210,153,34,0.2);color:var(--orange);border-radius:20px;padding:1px 7px;font-size:0.68rem;font-weight:700'>$p</span>";
          }
        } catch(Exception $e) {}
      ?>
    </a>
    <a href="reports.php" class="nav-item <?= $current==='reports'?'active':'' ?>">
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
      Reports
    </a>
  </nav>
  <div class="sidebar-footer">
    <div class="admin-pill">
      <div class="admin-avatar"><?= strtoupper(substr($_SESSION['user_name']??'A',0,1)) ?></div>
      <div>
        <div class="admin-name"><?= htmlspecialchars($_SESSION['user_name']??'Admin') ?></div>
        <div class="admin-role">Administrator</div>
      </div>
    </div>
    <a href="logout.php" class="logout-btn" title="Logout">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
    </a>
  </div>
</aside>