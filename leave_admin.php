<?php
session_start();
require_once 'auth_guard.php';
require_once 'db.php';

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action'] ?? '';
    $id      = $_POST['id'] ?? 0;
    $remarks = trim($_POST['remarks'] ?? '');

    if ($action === 'approve') {
        $pdo->prepare("UPDATE leave_requests SET status='approved', reviewed_by=?, reviewed_at=NOW(), remarks=? WHERE id=?")
            ->execute([$_SESSION['user_id'], $remarks, $id]);

        // Get employee info to update status if needed
        $leave = $pdo->prepare("SELECT * FROM leave_requests WHERE id=?");
        $leave->execute([$id]); $leave = $leave->fetch();

        $pdo->prepare("INSERT INTO activity_log (user_id,action,target,target_id) VALUES (?,?,?,?)")
            ->execute([$_SESSION['user_id'], 'Approved leave request', 'leave', $id]);
        $msg = 'Leave approved!';

    } elseif ($action === 'reject') {
        $pdo->prepare("UPDATE leave_requests SET status='rejected', reviewed_by=?, reviewed_at=NOW(), remarks=? WHERE id=?")
            ->execute([$_SESSION['user_id'], $remarks, $id]);
        $pdo->prepare("INSERT INTO activity_log (user_id,action,target,target_id) VALUES (?,?,?,?)")
            ->execute([$_SESSION['user_id'], 'Rejected leave request', 'leave', $id]);
        $msg = 'Leave rejected.';
    }
}

// Fetch all leave requests
$filter = $_GET['status'] ?? '';
$q = "SELECT lr.*, e.name AS emp_name, e.position, d.name AS dept_name
      FROM leave_requests lr
      JOIN employees e ON e.id = lr.employee_id
      LEFT JOIN departments d ON d.id = e.department_id";
if ($filter) $q .= " WHERE lr.status = '$filter'";
$q .= " ORDER BY lr.created_at DESC";
$leaves = $pdo->query($q)->fetchAll();

$pending  = $pdo->query("SELECT COUNT(*) FROM leave_requests WHERE status='pending'")->fetchColumn();
$approved = $pdo->query("SELECT COUNT(*) FROM leave_requests WHERE status='approved'")->fetchColumn();
$rejected = $pdo->query("SELECT COUNT(*) FROM leave_requests WHERE status='rejected'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>StaffCore — Leave Management</title>
  <?php include 'sidebar.php'; ?>
  <link rel="stylesheet" href="admin.css"/>
</head>
<body>
<main class="main">
  <header class="topbar">
    <div class="topbar-left">
      <div><h1 class="page-title">Leave Management</h1><p class="page-sub">Review and manage staff leave requests</p></div>
    </div>
    <div class="topbar-right">
      <form method="GET" style="display:flex;gap:8px;">
        <select name="status" class="btn btn-ghost" onchange="this.form.submit()" style="padding:8px 12px;">
          <option value="">All Requests</option>
          <option value="pending"  <?= $filter==='pending' ?'selected':'' ?>>Pending</option>
          <option value="approved" <?= $filter==='approved'?'selected':'' ?>>Approved</option>
          <option value="rejected" <?= $filter==='rejected'?'selected':'' ?>>Rejected</option>
        </select>
      </form>
    </div>
  </header>

  <div class="content">

    <!-- Stats -->
    <div class="mini-stats">
      <div class="mini-card"><div class="mini-icon orange"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div><div><div class="mini-label">Pending</div><div class="mini-value"><?= $pending ?></div></div></div>
      <div class="mini-card"><div class="mini-icon green"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg></div><div><div class="mini-label">Approved</div><div class="mini-value"><?= $approved ?></div></div></div>
      <div class="mini-card"><div class="mini-icon red"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg></div><div><div class="mini-label">Rejected</div><div class="mini-value"><?= $rejected ?></div></div></div>
      <div class="mini-card"><div class="mini-icon blue"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></div><div><div class="mini-label">Total</div><div class="mini-value"><?= $pending+$approved+$rejected ?></div></div></div>
    </div>

    <?php if($msg): ?>
    <div style="background:rgba(63,185,80,0.1);border:1px solid rgba(63,185,80,0.3);color:var(--green);border-radius:8px;padding:10px 16px;font-size:0.85rem"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <div class="card fade-up">
      <div class="card-header">
        <div><div class="card-title">Leave Requests</div><div class="card-sub"><?= count($leaves) ?> records</div></div>
      </div>

      <?php if(empty($leaves)): ?>
      <div style="text-align:center;padding:50px;color:var(--muted);font-size:0.88rem">No leave requests found.</div>
      <?php else: ?>
      <table class="data-table">
        <thead><tr><th>Employee</th><th>Type</th><th>From</th><th>To</th><th>Days</th><th>Reason</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
          <?php foreach($leaves as $l): ?>
          <tr>
            <td>
              <strong><?= htmlspecialchars($l['emp_name']) ?></strong>
              <br><span style="color:var(--muted);font-size:0.75rem"><?= htmlspecialchars($l['dept_name']??'') ?></span>
            </td>
            <td><?= ucfirst($l['leave_type']) ?></td>
            <td style="color:var(--muted)"><?= date('d M Y',strtotime($l['start_date'])) ?></td>
            <td style="color:var(--muted)"><?= date('d M Y',strtotime($l['end_date'])) ?></td>
            <td><?= $l['days'] ?>d</td>
            <td style="color:var(--muted);font-size:0.8rem;max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($l['reason']??'—') ?></td>
            <td>
              <?php
                $cls = $l['status']==='approved'?'badge-active':($l['status']==='rejected'?'badge-inactive':'badge-leave');
              ?>
              <span class="badge <?= $cls ?>"><?= ucfirst($l['status']) ?></span>
            </td>
            <td>
              <?php if($l['status']==='pending'): ?>
              <div style="display:flex;gap:6px;">
                <button class="btn btn-ghost btn-sm" style="color:var(--green);border-color:rgba(63,185,80,0.3)" onclick="reviewLeave(<?= $l['id'] ?>, 'approve')">✓ Approve</button>
                <button class="btn btn-danger btn-sm" onclick="reviewLeave(<?= $l['id'] ?>, 'reject')">✕ Reject</button>
              </div>
              <?php else: ?>
              <span style="color:var(--muted);font-size:0.78rem"><?= $l['reviewed_at'] ? date('d M',strtotime($l['reviewed_at'])) : '—' ?></span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>
</main>

<!-- Review Modal -->
<div class="modal-overlay" id="reviewModal">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title" id="reviewTitle">Review Leave</span>
      <button class="close-btn" onclick="closeModal('reviewModal')">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" id="reviewAction"/>
      <input type="hidden" name="id" id="reviewId"/>
      <div class="field">
        <label>Remarks (optional)</label>
        <textarea name="remarks" rows="3" placeholder="Add a note for the employee..."></textarea>
      </div>
      <div class="form-actions">
        <button type="button" class="btn btn-ghost" onclick="closeModal('reviewModal')">Cancel</button>
        <button type="submit" class="btn btn-primary" id="reviewSubmitBtn">Confirm</button>
      </div>
    </form>
  </div>
</div>

<script>
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

function reviewLeave(id, action) {
  document.getElementById('reviewId').value     = id;
  document.getElementById('reviewAction').value = action;
  document.getElementById('reviewTitle').textContent = action === 'approve' ? '✓ Approve Leave' : '✕ Reject Leave';
  document.getElementById('reviewSubmitBtn').textContent = action === 'approve' ? 'Approve' : 'Reject';
  document.getElementById('reviewSubmitBtn').style.background = action === 'approve'
    ? 'linear-gradient(135deg,#0066ff,#00e5ff)'
    : 'rgba(255,71,87,0.2)';
  document.getElementById('reviewSubmitBtn').style.color = action === 'approve' ? '#080c10' : 'var(--red)';
  openModal('reviewModal');
}

document.querySelectorAll('.modal-overlay').forEach(o => {
  o.addEventListener('click', e => { if(e.target===o) o.classList.remove('open'); });
});
</script>
</body>
</html>