<?php
session_start();
require_once 'auth_guard.php';
require_once 'db.php';

// Get employee record
$stmt = $pdo->prepare("SELECT * FROM employees WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$emp = $stmt->fetch();

$msg = ''; $msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $emp) {
    $action = $_POST['action'] ?? '';

    if ($action === 'apply') {
        $start = $_POST['start_date'] ?? '';
        $end   = $_POST['end_date']   ?? '';
        $type  = $_POST['leave_type'] ?? 'annual';
        $reason= trim($_POST['reason'] ?? '');

        if ($start && $end) {
            $days = (int)((strtotime($end) - strtotime($start)) / 86400) + 1;
            $pdo->prepare("INSERT INTO leave_requests (employee_id,leave_type,start_date,end_date,days,reason) VALUES (?,?,?,?,?,?)")
                ->execute([$emp['id'], $type, $start, $end, $days, $reason]);
            $pdo->prepare("INSERT INTO activity_log (user_id,action,target) VALUES (?,?,?)")
                ->execute([$_SESSION['user_id'], 'Submitted leave request', 'leave']);
            $msg = 'Leave request submitted!'; $msgType = 'success';
        } else {
            $msg = 'Please fill in all fields.'; $msgType = 'error';
        }

    } elseif ($action === 'cancel') {
        $pdo->prepare("DELETE FROM leave_requests WHERE id=? AND employee_id=? AND status='pending'")
            ->execute([$_POST['id'], $emp['id']]);
        $msg = 'Request cancelled.'; $msgType = 'success';
    }
}

// Fetch this employee's leave requests
$leaves = [];
if ($emp) {
    $leaves = $pdo->prepare("SELECT * FROM leave_requests WHERE employee_id=? ORDER BY created_at DESC");
    $leaves->execute([$emp['id']]);
    $leaves = $leaves->fetchAll();
}

$pending  = count(array_filter($leaves, fn($l) => $l['status'] === 'pending'));
$approved = count(array_filter($leaves, fn($l) => $l['status'] === 'approved'));
$rejected = count(array_filter($leaves, fn($l) => $l['status'] === 'rejected'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>StaffCore — Leave Requests</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="shared.css"/>
  <style>
    body { background: var(--bg); }
    .staff-layout { display: flex; flex-direction: column; min-height: 100vh; }
    .staff-topbar { background: var(--surface); border-bottom: 1px solid var(--border); padding: 0 40px; height: 64px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 50; }
    .brand { display: flex; align-items: center; gap: 10px; font-family: 'Syne', sans-serif; font-weight: 800; font-size: 1.1rem; }
    .brand-icon { width: 36px; height: 36px; background: var(--surface2); border: 1px solid var(--border); border-radius: 8px; display: flex; align-items: center; justify-content: center; }
    .page { max-width: 980px; margin: 0 auto; padding: 36px 24px; display: flex; flex-direction: column; gap: 20px; }
    .nav-links { display: flex; gap: 8px; }
    .nav-link { padding: 7px 14px; border-radius: 8px; font-size: 0.83rem; font-weight: 500; color: var(--muted); text-decoration: none; border: 1px solid var(--border); background: var(--surface2); transition: all 0.2s; }
    .nav-link:hover, .nav-link.active { color: var(--accent); border-color: rgba(0,229,255,0.3); }
    .logout-btn { display: flex; align-items: center; gap: 7px; background: rgba(255,71,87,0.08); border: 1px solid rgba(255,71,87,0.2); color: var(--red); padding: 7px 14px; border-radius: 8px; font-family: 'DM Sans', sans-serif; font-size: 0.83rem; font-weight: 500; text-decoration: none; }
    .logout-btn:hover { background: rgba(255,71,87,0.15); }
  </style>
</head>
<body>
<div class="staff-layout">
  <div class="staff-topbar">
    <div class="brand">
      <div class="brand-icon">
        <svg width="22" height="22" viewBox="0 0 28 28" fill="none"><rect x="2" y="2" width="10" height="10" rx="2" fill="#00e5ff"/><rect x="16" y="2" width="10" height="10" rx="2" fill="#00e5ff" opacity="0.5"/><rect x="2" y="16" width="10" height="10" rx="2" fill="#00e5ff" opacity="0.5"/><rect x="16" y="16" width="10" height="10" rx="2" fill="#00e5ff"/></svg>
      </div>
      StaffCore
    </div>
    <div style="display:flex;align-items:center;gap:10px;">
      <div class="nav-links">
        <a href="staff_dashboard.php" class="nav-link">Dashboard</a>
        <a href="leave_request.php" class="nav-link active">Leave</a>
      </div>
      <a href="logout.php" class="logout-btn">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        Logout
      </a>
    </div>
  </div>

  <div class="page">

    <?php if(!$emp): ?>
    <div class="card" style="text-align:center;padding:60px">
      <div style="color:var(--muted)">No employee profile linked. Contact your administrator.</div>
    </div>
    <?php else: ?>

    <!-- Stats -->
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;">
      <div class="card" style="display:flex;align-items:center;gap:14px;">
        <div class="mini-icon orange" style="width:40px;height:40px;border-radius:10px;flex-shrink:0"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
        <div><div style="font-size:0.7rem;color:var(--muted);text-transform:uppercase;letter-spacing:0.5px">Pending</div><div style="font-family:'Syne',sans-serif;font-size:1.6rem;font-weight:800"><?= $pending ?></div></div>
      </div>
      <div class="card" style="display:flex;align-items:center;gap:14px;">
        <div class="mini-icon green" style="width:40px;height:40px;border-radius:10px;flex-shrink:0"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg></div>
        <div><div style="font-size:0.7rem;color:var(--muted);text-transform:uppercase;letter-spacing:0.5px">Approved</div><div style="font-family:'Syne',sans-serif;font-size:1.6rem;font-weight:800"><?= $approved ?></div></div>
      </div>
      <div class="card" style="display:flex;align-items:center;gap:14px;">
        <div class="mini-icon red" style="width:40px;height:40px;border-radius:10px;flex-shrink:0"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg></div>
        <div><div style="font-size:0.7rem;color:var(--muted);text-transform:uppercase;letter-spacing:0.5px">Rejected</div><div style="font-family:'Syne',sans-serif;font-size:1.6rem;font-weight:800"><?= $rejected ?></div></div>
      </div>
    </div>

    <!-- Apply form -->
    <div class="card fade-up">
      <div class="card-header">
        <div><div class="card-title">Apply for Leave</div><div class="card-sub">Submit a new leave request</div></div>
      </div>

      <?php if($msg): ?>
      <div style="background:<?= $msgType==='success'?'rgba(63,185,80,0.1)':'rgba(255,71,87,0.1)' ?>;border:1px solid <?= $msgType==='success'?'rgba(63,185,80,0.3)':'rgba(255,71,87,0.3)' ?>;color:<?= $msgType==='success'?'var(--green)':'var(--red)' ?>;border-radius:8px;padding:10px 14px;margin-bottom:16px;font-size:0.85rem"><?= htmlspecialchars($msg) ?></div>
      <?php endif; ?>

      <form method="POST">
        <input type="hidden" name="action" value="apply"/>
        <div class="form-grid">
          <div class="field">
            <label>Leave Type</label>
            <select name="leave_type">
              <option value="annual">Annual Leave</option>
              <option value="sick">Sick Leave</option>
              <option value="emergency">Emergency Leave</option>
              <option value="unpaid">Unpaid Leave</option>
            </select>
          </div>
          <div class="field"><!-- spacer --></div>
          <div class="field">
            <label>Start Date</label>
            <input type="date" name="start_date" required min="<?= date('Y-m-d') ?>"/>
          </div>
          <div class="field">
            <label>End Date</label>
            <input type="date" name="end_date" required min="<?= date('Y-m-d') ?>"/>
          </div>
          <div class="field full">
            <label>Reason</label>
            <textarea name="reason" rows="3" placeholder="Brief reason for leave..."></textarea>
          </div>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn btn-primary">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Submit Request
          </button>
        </div>
      </form>
    </div>

    <!-- Leave history -->
    <div class="card fade-up">
      <div class="card-header">
        <div><div class="card-title">My Leave Requests</div><div class="card-sub"><?= count($leaves) ?> total requests</div></div>
      </div>
      <?php if(empty($leaves)): ?>
      <div style="text-align:center;padding:40px;color:var(--muted);font-size:0.88rem">No leave requests yet.</div>
      <?php else: ?>
      <table class="data-table">
        <thead><tr><th>Type</th><th>From</th><th>To</th><th>Days</th><th>Status</th><th>Reason</th><th>Action</th></tr></thead>
        <tbody>
          <?php foreach($leaves as $l): ?>
          <tr>
            <td><?= ucfirst($l['leave_type']) ?></td>
            <td style="color:var(--muted)"><?= date('d M Y',strtotime($l['start_date'])) ?></td>
            <td style="color:var(--muted)"><?= date('d M Y',strtotime($l['end_date'])) ?></td>
            <td><?= $l['days'] ?> day<?= $l['days']>1?'s':'' ?></td>
            <td>
              <?php
                $cls = $l['status']==='approved'?'badge-active':($l['status']==='rejected'?'badge-inactive':'badge-leave');
              ?>
              <span class="badge <?= $cls ?>"><?= ucfirst($l['status']) ?></span>
            </td>
            <td style="color:var(--muted);font-size:0.8rem;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($l['reason']??'—') ?></td>
            <td>
              <?php if($l['status']==='pending'): ?>
              <form method="POST" style="display:inline" onsubmit="return confirm('Cancel this request?')">
                <input type="hidden" name="action" value="cancel"/>
                <input type="hidden" name="id" value="<?= $l['id'] ?>"/>
                <button type="submit" class="btn btn-danger btn-sm">Cancel</button>
              </form>
              <?php elseif($l['remarks']): ?>
              <span style="color:var(--muted);font-size:0.78rem" title="<?= htmlspecialchars($l['remarks']) ?>">Note ℹ</span>
              <?php else: ?><span style="color:var(--muted)">—</span><?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>

    <?php endif; ?>
  </div>
</div>
</body>
</html>