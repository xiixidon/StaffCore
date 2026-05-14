<?php
session_start();
require_once 'auth_guard.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_once 'db.php';

$msg = '';
$today = date('Y-m-d');
$date = $_GET['date'] ?? $today;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        foreach ($_POST['attendance'] as $emp_id => $row) {
            $stmt = $pdo->prepare("INSERT INTO attendance (employee_id,date,check_in,check_out,status) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE check_in=VALUES(check_in),check_out=VALUES(check_out),status=VALUES(status)");
            $stmt->execute([$emp_id, $date, $row['check_in']?:null, $row['check_out']?:null, $row['status']]);
        }
        $pdo->prepare("INSERT INTO activity_log (user_id,action,target) VALUES (?,?,?)")->execute([$_SESSION['user_id'],"Updated attendance for $date",'attendance']);
        $msg = 'Attendance saved!';
    }
}

// Get all employees with attendance for selected date
$employees = $pdo->prepare("
    SELECT e.id, e.name, e.position, d.name AS dept_name,
           a.check_in, a.check_out, a.status AS att_status
    FROM employees e
    LEFT JOIN departments d ON d.id = e.department_id
    LEFT JOIN attendance a ON a.employee_id = e.id AND a.date = ?
    WHERE e.status = 'active'
    ORDER BY e.name
");
$employees->execute([$date]);
$employees = $employees->fetchAll();

// Summary counts
$summary = $pdo->prepare("SELECT status, COUNT(*) as cnt FROM attendance WHERE date=? GROUP BY status");
$summary->execute([$date]);
$counts = ['present'=>0,'absent'=>0,'late'=>0,'half_day'=>0];
foreach($summary->fetchAll() as $row) $counts[$row['status']] = $row['cnt'];
$totalActive = $pdo->query("SELECT COUNT(*) FROM employees WHERE status='active'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>StaffCore — Attendance</title>
  <?php include 'sidebar.php'; ?>
  <link rel="stylesheet" href="admin.css"/>
</head>
<body>
<main class="main">
  <header class="topbar">
    <div class="topbar-left">
      <div><h1 class="page-title">Attendance</h1><p class="page-sub">Track daily attendance</p></div>
    </div>
    <div class="topbar-right">
      <form method="GET" style="display:flex;align-items:center;gap:10px;">
        <input type="date" name="date" value="<?= $date ?>" class="btn btn-ghost" style="padding:8px 12px;" onchange="this.form.submit()"/>
      </form>
    </div>
  </header>

  <div class="content">
    <!-- Summary cards -->
    <div class="mini-stats">
      <div class="mini-card"><div class="mini-icon green"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg></div><div><div class="mini-label">Present</div><div class="mini-value"><?= $counts['present'] ?></div></div></div>
      <div class="mini-card"><div class="mini-icon red"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg></div><div><div class="mini-label">Absent</div><div class="mini-value"><?= $counts['absent'] ?></div></div></div>
      <div class="mini-card"><div class="mini-icon orange"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div><div><div class="mini-label">Late</div><div class="mini-value"><?= $counts['late'] ?></div></div></div>
      <div class="mini-card"><div class="mini-icon blue"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div><div><div class="mini-label">Total Staff</div><div class="mini-value"><?= $totalActive ?></div></div></div>
    </div>

    <div class="card fade-up">
      <div class="card-header">
        <div>
          <div class="card-title">Attendance — <?= date('d F Y', strtotime($date)) ?></div>
          <div class="card-sub">Mark attendance for all active employees</div>
        </div>
      </div>

      <?php if($msg): ?>
      <div style="background:rgba(63,185,80,0.1);border:1px solid rgba(63,185,80,0.3);color:var(--green);border-radius:8px;padding:10px 14px;margin-bottom:16px;font-size:0.85rem"><?= htmlspecialchars($msg) ?></div>
      <?php endif; ?>

      <form method="POST">
        <input type="hidden" name="action" value="save"/>
        <table class="data-table">
          <thead><tr><th>#</th><th>Employee</th><th>Department</th><th>Status</th><th>Check In</th><th>Check Out</th></tr></thead>
          <tbody>
            <?php foreach($employees as $i=>$e): 
              $status = $e['att_status'] ?? 'present';
            ?>
            <tr>
              <td style="color:var(--muted)"><?= $i+1 ?></td>
              <td><strong><?= htmlspecialchars($e['name']) ?></strong><br><span style="color:var(--muted);font-size:0.75rem"><?= htmlspecialchars($e['position']??'') ?></span></td>
              <td style="color:var(--muted)"><?= htmlspecialchars($e['dept_name']??'—') ?></td>
              <td>
                <select name="attendance[<?= $e['id'] ?>][status]" class="att-status" data-id="<?= $e['id'] ?>" style="background:var(--surface2);border:1px solid var(--border);border-radius:6px;padding:6px 10px;color:var(--text);font-family:'DM Sans',sans-serif;font-size:0.83rem;outline:none;">
                  <option value="present"  <?= $status==='present' ?'selected':'' ?>>Present</option>
                  <option value="absent"   <?= $status==='absent'  ?'selected':'' ?>>Absent</option>
                  <option value="late"     <?= $status==='late'    ?'selected':'' ?>>Late</option>
                  <option value="half_day" <?= $status==='half_day'?'selected':'' ?>>Half Day</option>
                </select>
              </td>
              <td><input type="time" name="attendance[<?= $e['id'] ?>][check_in]" value="<?= $e['check_in']??'' ?>" style="background:var(--surface2);border:1px solid var(--border);border-radius:6px;padding:6px 10px;color:var(--text);font-family:'DM Sans',sans-serif;font-size:0.83rem;outline:none;"/></td>
              <td><input type="time" name="attendance[<?= $e['id'] ?>][check_out]" value="<?= $e['check_out']??'' ?>" style="background:var(--surface2);border:1px solid var(--border);border-radius:6px;padding:6px 10px;color:var(--text);font-family:'DM Sans',sans-serif;font-size:0.83rem;outline:none;"/></td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($employees)): ?>
            <tr><td colspan="6"><div class="empty-state"><p>No active employees found.</p></div></td></tr>
            <?php endif; ?>
          </tbody>
        </table>
        <?php if(!empty($employees)): ?>
        <div style="display:flex;justify-content:flex-end;margin-top:20px;">
          <button type="submit" class="btn btn-primary">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            Save Attendance
          </button>
        </div>
        <?php endif; ?>
      </form>
    </div>
  </div>
</main>
</body>
</html>