<?php
// ============================
// api/dashboard_stats.php
// Returns JSON for the dashboard
// ============================

session_start();
require_once '../db.php';

// Auth guard
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

// --- Totals ---
$total    = $pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn();
$active   = $pdo->query("SELECT COUNT(*) FROM employees WHERE status='active'")->fetchColumn();
$on_leave = $pdo->query("SELECT COUNT(*) FROM employees WHERE status='on_leave'")->fetchColumn();
$inactive = $pdo->query("SELECT COUNT(*) FROM employees WHERE status='inactive'")->fetchColumn();
$depts    = $pdo->query("SELECT COUNT(*) FROM departments")->fetchColumn();

// --- By Department ---
$byDept = $pdo->query("
    SELECT d.name, COUNT(e.id) AS count
    FROM departments d
    LEFT JOIN employees e ON e.department_id = d.id
    GROUP BY d.id, d.name
    ORDER BY count DESC
")->fetchAll(PDO::FETCH_ASSOC);

// --- Recent Employees (last 10) ---
$recent = $pdo->query("
    SELECT e.name, e.position, e.status, d.name AS department
    FROM employees e
    LEFT JOIN departments d ON d.id = e.department_id
    ORDER BY e.created_at DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// --- Activity Log (last 8) ---
$activity = $pdo->query("
    SELECT a.action, a.created_at, u.name AS admin_name
    FROM activity_log a
    LEFT JOIN users u ON u.id = a.user_id
    ORDER BY a.created_at DESC
    LIMIT 8
")->fetchAll(PDO::FETCH_ASSOC);

// Format timestamps
foreach ($activity as &$log) {
    $log['created_at'] = date('d M Y, h:i A', strtotime($log['created_at']));
}

echo json_encode([
    'total'            => (int)$total,
    'active'           => (int)$active,
    'on_leave'         => (int)$on_leave,
    'inactive'         => (int)$inactive,
    'departments'      => (int)$depts,
    'by_department'    => $byDept,
    'recent_employees' => $recent,
    'activity_log'     => $activity,
]);
?>
