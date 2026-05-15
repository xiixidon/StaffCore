<?php
// ============================
// AUTH_GUARD.PHP — StaffCore
// Include at top of every page
// ============================

// Session timeout: 30 mins of inactivity
define('SESSION_TIMEOUT', 1800);

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); exit;
}

// Check session timeout
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
    session_unset();
    session_destroy();
    header('Location: login.php?timeout=1'); exit;
}
$_SESSION['last_activity'] = time();

// Role-based access
// Pages only admins can access
$admin_only_pages = ['employees', 'departments', 'attendance', 'reports', 'admin_dashboard'];
$current_page = basename($_SERVER['PHP_SELF'], '.php');

if (in_array($current_page, $admin_only_pages) && $_SESSION['user_role'] !== 'admin') {
    header('Location: staff_dashboard.php'); exit;
}

// Staff dashboard only for staff
if ($current_page === 'staff_dashboard' && $_SESSION['user_role'] === 'admin') {
    header('Location: admin_dashboard.php'); exit;
}
?>