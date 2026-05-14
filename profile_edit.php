<?php
session_start();
require_once 'auth_guard.php';
require_once 'db.php';

$stmt = $pdo->prepare("SELECT e.*, d.name AS dept_name FROM employees e LEFT JOIN departments d ON d.id=e.department_id WHERE e.user_id=?");
$stmt->execute([$_SESSION['user_id']]);
$emp = $stmt->fetch();

$msg = ''; $msgType = ''; $activeTab = 'personal';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_phone' && $emp) {
        $phone = trim($_POST['phone'] ?? '');
        $pdo->prepare("UPDATE employees SET phone=? WHERE user_id=?")->execute([$phone, $_SESSION['user_id']]);
        $msg = 'Phone number updated!'; $msgType = 'success'; $activeTab = 'personal';
        $stmt = $pdo->prepare("SELECT e.*, d.name AS dept_name FROM employees e LEFT JOIN departments d ON d.id=e.department_id WHERE e.user_id=?");
        $stmt->execute([$_SESSION['user_id']]); $emp = $stmt->fetch();

    } elseif ($action === 'update_password') {
        $activeTab = 'password';
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $user = $pdo->prepare("SELECT * FROM users WHERE id=?"); $user->execute([$_SESSION['user_id']]); $user = $user->fetch();
        if (!password_verify($current, $user['password']))    { $msg = 'Current password is incorrect.'; $msgType = 'error'; }
        elseif (strlen($new) < 8)                             { $msg = 'New password must be at least 8 characters.'; $msgType = 'error'; }
        elseif ($new !== $confirm)                            { $msg = 'Passwords do not match.'; $msgType = 'error'; }
        else {
            $pdo->prepare("UPDATE users SET password=? WHERE id=?")->execute([password_hash($new, PASSWORD_BCRYPT), $_SESSION['user_id']]);
            $msg = 'Password updated successfully!'; $msgType = 'success';
        }

    } elseif ($action === 'apply_leave' && $emp) {
        $activeTab = 'leave';
        $start  = $_POST['start_date'] ?? ''; $end = $_POST['end_date'] ?? '';
        $type   = $_POST['leave_type'] ?? 'annual'; $reason = trim($_POST['reason'] ?? '');
        if ($start && $end && strtotime($end) >= strtotime($start)) {
            $days = (int)((strtotime($end) - strtotime($start)) / 86400) + 1;
            $pdo->prepare("INSERT INTO leave_requests (employee_id,leave_type,start_date,end_date,days,reason) VALUES (?,?,?,?,?,?)")->execute([$emp['id'],$type,$start,$end,$days,$reason]);
            $msg = 'Leave request submitted!'; $msgType = 'success';
        } else { $msg = 'Please check your dates.'; $msgType = 'error'; }
    }
}

$leaves = [];
if ($emp) {
    $lv = $pdo->prepare("SELECT * FROM leave_requests WHERE employee_id=? ORDER BY created_at DESC LIMIT 10");
    $lv->execute([$emp['id']]); $leaves = $lv->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>StaffCore — My Profile</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet"/>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --bg: #080c10; --surface: #0d1117; --surface2: #161b22;
      --border: rgba(255,255,255,0.07); --accent: #00e5ff; --accent2: #0066ff;
      --text: #e6edf3; --muted: #7d8590;
      --green: #3fb950; --orange: #d29922; --red: #ff4757; --purple: #a371f7;
    }
    body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; }

    /* Topbar */
    .topbar { background: var(--surface); border-bottom: 1px solid var(--border); padding: 0 32px; height: 60px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 50; }
    .brand { display: flex; align-items: center; gap: 10px; font-family: 'Syne', sans-serif; font-weight: 800; font-size: 1.05rem; text-decoration: none; color: var(--text); }
    .brand-icon { width: 34px; height: 34px; background: var(--surface2); border: 1px solid var(--border); border-radius: 8px; display: flex; align-items: center; justify-content: center; }
    .topbar-right { display: flex; align-items: center; gap: 8px; }
    .nav-btn { padding: 6px 14px; border-radius: 8px; font-size: 0.82rem; font-weight: 500; text-decoration: none; border: 1px solid var(--border); background: var(--surface2); color: var(--muted); transition: all 0.2s; }
    .nav-btn:hover { color: var(--accent); border-color: rgba(0,229,255,0.3); }
    .nav-btn.active { color: var(--accent); border-color: rgba(0,229,255,0.3); background: rgba(0,229,255,0.06); }
    .logout-btn { display: flex; align-items: center; gap: 6px; background: rgba(255,71,87,0.08); border: 1px solid rgba(255,71,87,0.2); color: var(--red); padding: 6px 14px; border-radius: 8px; font-size: 0.82rem; font-weight: 500; text-decoration: none; }
    .logout-btn:hover { background: rgba(255,71,87,0.15); }

    /* Page */
    .page { max-width: 860px; margin: 0 auto; padding: 40px 24px; display: flex; flex-direction: column; gap: 20px; }

    /* Hero */
    .hero { background: var(--surface); border: 1px solid var(--border); border-radius: 20px; padding: 30px 32px; display: flex; align-items: center; gap: 22px; position: relative; overflow: hidden; }
    .hero::before { content:''; position:absolute; top:0; left:0; right:0; height:3px; background:linear-gradient(90deg,var(--accent2),var(--accent)); }
    .hero-glow { position:absolute; top:-50px; right:-50px; width:180px; height:180px; background:radial-gradient(circle,rgba(0,229,255,0.05),transparent 70%); pointer-events:none; }
    .avatar { width:68px; height:68px; border-radius:50%; flex-shrink:0; background:linear-gradient(135deg,var(--accent),var(--accent2)); display:flex; align-items:center; justify-content:center; font-family:'Syne',sans-serif; font-weight:800; font-size:1.7rem; color:#080c10; box-shadow:0 0 0 4px rgba(0,229,255,0.12); }
    .hero-text { flex: 1; }
    .hero-name { font-family:'Syne',sans-serif; font-size:1.6rem; font-weight:800; letter-spacing:-0.5px; }
    .hero-pos { color:var(--accent); font-size:0.85rem; font-weight:500; margin-top:2px; }
    .hero-meta { display:flex; flex-wrap:wrap; gap:14px; margin-top:10px; }
    .hero-meta span { display:flex; align-items:center; gap:5px; font-size:0.79rem; color:var(--muted); }
    .status-dot { display:inline-flex; align-items:center; gap:5px; padding:3px 10px; border-radius:20px; font-size:0.72rem; font-weight:600; background:rgba(63,185,80,0.12); color:var(--green); border:1px solid rgba(63,185,80,0.2); }
    .status-dot::before { content:''; width:6px; height:6px; border-radius:50%; background:var(--green); display:inline-block; }

    /* Info pills row */
    .info-row { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; }
    .info-pill { background:var(--surface); border:1px solid var(--border); border-radius:12px; padding:16px 18px; transition:border-color 0.2s,transform 0.2s; }
    .info-pill:hover { border-color:rgba(0,229,255,0.15); transform:translateY(-2px); }
    .pill-label { font-size:0.68rem; color:var(--muted); text-transform:uppercase; letter-spacing:0.5px; margin-bottom:6px; }
    .pill-value { font-family:'Syne',sans-serif; font-size:1rem; font-weight:700; }
    .pill-value.green { color:var(--green); }
    .pill-value.accent { color:var(--accent); }

    /* Tabs */
    .tabs { display:flex; gap:2px; background:var(--surface2); border:1px solid var(--border); border-radius:12px; padding:4px; }
    .tab { flex:1; padding:9px; border-radius:9px; font-size:0.84rem; font-weight:500; color:var(--muted); cursor:pointer; border:none; background:none; font-family:'DM Sans',sans-serif; transition:all 0.2s; text-align:center; }
    .tab.active { background:rgba(0,229,255,0.08); color:var(--accent); border:1px solid rgba(0,229,255,0.15); }

    /* Card */
    .card { background:var(--surface); border:1px solid var(--border); border-radius:16px; padding:28px; animation:fadeUp 0.35s ease both; }
    .card-title { font-family:'Syne',sans-serif; font-weight:700; font-size:1rem; margin-bottom:4px; }
    .card-sub { font-size:0.78rem; color:var(--muted); margin-bottom:22px; }

    /* Form */
    .form-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
    .form-grid .full { grid-column:1/-1; }
    .field { display:flex; flex-direction:column; gap:6px; }
    .field label { font-size:0.7rem; color:var(--muted); text-transform:uppercase; letter-spacing:0.5px; font-weight:500; }
    .field input, .field select, .field textarea { background:var(--surface2); border:1px solid var(--border); border-radius:8px; padding:10px 12px; color:var(--text); font-family:'DM Sans',sans-serif; font-size:0.87rem; outline:none; transition:border-color 0.2s,box-shadow 0.2s; width:100%; }
    .field input:focus, .field select:focus, .field textarea:focus { border-color:rgba(0,229,255,0.4); box-shadow:0 0 0 3px rgba(0,229,255,0.06); }
    .field input:disabled { opacity:0.45; cursor:not-allowed; }
    .field input::placeholder, .field textarea::placeholder { color:var(--muted); opacity:0.6; }
    .field select option { background:var(--surface2); }

    .form-actions { display:flex; justify-content:flex-end; margin-top:20px; }
    .btn-primary { display:inline-flex; align-items:center; gap:8px; padding:10px 20px; background:linear-gradient(135deg,var(--accent2),var(--accent)); border:none; border-radius:8px; color:#080c10; font-family:'Syne',sans-serif; font-size:0.88rem; font-weight:700; cursor:pointer; transition:opacity 0.2s,transform 0.15s; box-shadow:0 4px 14px rgba(0,229,255,0.2); }
    .btn-primary:hover { opacity:0.9; transform:translateY(-1px); }

    /* Alert */
    .alert { border-radius:9px; padding:11px 16px; font-size:0.84rem; margin-bottom:18px; display:flex; align-items:center; gap:8px; }
    .alert-success { background:rgba(63,185,80,0.1); border:1px solid rgba(63,185,80,0.3); color:var(--green); }
    .alert-error   { background:rgba(255,71,87,0.1);  border:1px solid rgba(255,71,87,0.3);  color:var(--red); }

    /* Tab content */
    .tab-pane { display:none; }
    .tab-pane.active { display:block; }

    /* Leave table */
    table { width:100%; border-collapse:collapse; font-size:0.83rem; margin-top:20px; }
    thead th { text-align:left; padding:9px 12px; color:var(--muted); font-weight:500; font-size:0.68rem; text-transform:uppercase; letter-spacing:0.5px; background:var(--surface2); border-bottom:1px solid var(--border); }
    tbody td { padding:11px 12px; border-bottom:1px solid rgba(255,255,255,0.04); vertical-align:middle; }
    tbody tr:last-child td { border-bottom:none; }
    tbody tr:hover td { background:rgba(255,255,255,0.02); }
    .badge { display:inline-block; padding:2px 9px; border-radius:20px; font-size:0.68rem; font-weight:600; }
    .badge-active   { background:rgba(63,185,80,0.12);  color:var(--green);  border:1px solid rgba(63,185,80,0.2); }
    .badge-inactive { background:rgba(255,71,87,0.12);  color:var(--red);    border:1px solid rgba(255,71,87,0.2); }
    .badge-leave    { background:rgba(210,153,34,0.12); color:var(--orange); border:1px solid rgba(210,153,34,0.2); }

    .divider { border:none; border-top:1px solid var(--border); margin:22px 0; }

    @keyframes fadeUp { from{opacity:0;transform:translateY(12px)} to{opacity:1;transform:translateY(0)} }
    @media(max-width:700px) { .info-row{grid-template-columns:1fr 1fr} .form-grid{grid-template-columns:1fr} .hero{flex-direction:column;text-align:center} }
  </style>
</head>
<body>

<div class="topbar">
  <a href="staff_dashboard.php" class="brand">
    <div class="brand-icon">
      <svg width="20" height="20" viewBox="0 0 28 28" fill="none"><rect x="2" y="2" width="10" height="10" rx="2" fill="#00e5ff"/><rect x="16" y="2" width="10" height="10" rx="2" fill="#00e5ff" opacity="0.5"/><rect x="2" y="16" width="10" height="10" rx="2" fill="#00e5ff" opacity="0.5"/><rect x="16" y="16" width="10" height="10" rx="2" fill="#00e5ff"/></svg>
    </div>
    StaffCore
  </a>
  <div class="topbar-right">
    <a href="staff_dashboard.php" class="nav-btn">Dashboard</a>
    <a href="profile_edit.php" class="nav-btn active">My Profile</a>
    <a href="logout.php" class="logout-btn">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
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

  <!-- Hero -->
  <div class="hero">
    <div class="hero-glow"></div>
    <div class="avatar"><?= strtoupper(substr($emp['name'],0,1)) ?></div>
    <div class="hero-text">
      <div class="hero-name"><?= htmlspecialchars($emp['name']) ?></div>
      <div class="hero-pos"><?= htmlspecialchars($emp['position']??'Staff Member') ?></div>
      <div class="hero-meta">
        <span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg><?= htmlspecialchars($emp['dept_name']??'—') ?></span>
        <span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,12 2,6"/></svg><?= htmlspecialchars($emp['email']) ?></span>
        <?php if($emp['phone']): ?><span><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07"/></svg><?= htmlspecialchars($emp['phone']) ?></span><?php endif; ?>
        <span class="status-dot"><?= ucfirst(str_replace('_',' ',$emp['status'])) ?></span>
      </div>
    </div>
  </div>

  <!-- Info pills -->
  <div class="info-row">
    <div class="info-pill"><div class="pill-label">Monthly Salary</div><div class="pill-value green">RM <?= number_format($emp['salary'],2) ?></div></div>
    <div class="info-pill"><div class="pill-label">Hire Date</div><div class="pill-value"><?= $emp['hire_date']?date('d M Y',strtotime($emp['hire_date'])):'—' ?></div></div>
    <div class="info-pill"><div class="pill-label">Department</div><div class="pill-value"><?= htmlspecialchars($emp['dept_name']??'—') ?></div></div>
    <div class="info-pill"><div class="pill-label">Years of Service</div><div class="pill-value accent"><?= $emp['hire_date']?floor((time()-strtotime($emp['hire_date']))/31536000).' yr(s)':'—' ?></div></div>
  </div>

  <!-- Alert -->
  <?php if($msg): ?>
  <div class="alert alert-<?= $msgType ?>">
    <?= $msgType==='success' ? '✓' : '✕' ?> <?= htmlspecialchars($msg) ?>
  </div>
  <?php endif; ?>

  <!-- Tabs -->
  <div class="tabs">
    <button class="tab <?= $activeTab==='personal'?'active':'' ?>" onclick="switchTab('personal',this)">Personal Info</button>
    <button class="tab <?= $activeTab==='password'?'active':'' ?>" onclick="switchTab('password',this)">Change Password</button>
    <button class="tab <?= $activeTab==='leave'?'active':'' ?>" onclick="switchTab('leave',this)">Leave Request</button>
  </div>

  <!-- Personal Info -->
  <div class="tab-pane <?= $activeTab==='personal'?'active':'' ?>" id="pane-personal">
    <div class="card">
      <div class="card-title">Personal Information</div>
      <div class="card-sub">Only phone number can be updated</div>
      <form method="POST">
        <input type="hidden" name="action" value="update_phone"/>
        <div class="form-grid">
          <div class="field"><label>Full Name</label><input type="text" value="<?= htmlspecialchars($emp['name']) ?>" disabled/></div>
          <div class="field"><label>Email</label><input type="email" value="<?= htmlspecialchars($emp['email']) ?>" disabled/></div>
          <div class="field"><label>Phone Number</label><input type="text" name="phone" value="<?= htmlspecialchars($emp['phone']??'') ?>" placeholder="012-3456789"/></div>
          <div class="field"><label>Department</label><input type="text" value="<?= htmlspecialchars($emp['dept_name']??'—') ?>" disabled/></div>
          <div class="field"><label>Position</label><input type="text" value="<?= htmlspecialchars($emp['position']??'—') ?>" disabled/></div>
          <div class="field"><label>Hire Date</label><input type="text" value="<?= $emp['hire_date']?date('d M Y',strtotime($emp['hire_date'])):'—' ?>" disabled/></div>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn-primary">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            Save Changes
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Change Password -->
  <div class="tab-pane <?= $activeTab==='password'?'active':'' ?>" id="pane-password">
    <div class="card">
      <div class="card-title">Change Password</div>
      <div class="card-sub">Minimum 8 characters</div>
      <form method="POST">
        <input type="hidden" name="action" value="update_password"/>
        <div class="form-grid">
          <div class="field full"><label>Current Password</label><input type="password" name="current_password" placeholder="Your current password" required/></div>
          <div class="field"><label>New Password</label><input type="password" name="new_password" placeholder="Min. 8 characters" required minlength="8"/></div>
          <div class="field"><label>Confirm Password</label><input type="password" name="confirm_password" placeholder="Repeat new password" required/></div>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn-primary">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            Update Password
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Leave Request -->
  <div class="tab-pane <?= $activeTab==='leave'?'active':'' ?>" id="pane-leave">
    <div class="card">
      <div class="card-title">Apply for Leave</div>
      <div class="card-sub">Submit a new leave application</div>
      <form method="POST">
        <input type="hidden" name="action" value="apply_leave"/>
        <div class="form-grid">
          <div class="field"><label>Leave Type</label>
            <select name="leave_type">
              <option value="annual">Annual Leave</option>
              <option value="sick">Sick Leave</option>
              <option value="emergency">Emergency Leave</option>
              <option value="unpaid">Unpaid Leave</option>
            </select>
          </div>
          <div class="field"><!-- spacer --></div>
          <div class="field"><label>Start Date</label><input type="date" name="start_date" required min="<?= date('Y-m-d') ?>"/></div>
          <div class="field"><label>End Date</label><input type="date" name="end_date" required min="<?= date('Y-m-d') ?>"/></div>
          <div class="field full"><label>Reason</label><textarea name="reason" rows="3" placeholder="Brief reason for leave..."></textarea></div>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn-primary">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Submit Request
          </button>
        </div>
      </form>

      <?php if(!empty($leaves)): ?>
      <hr class="divider"/>
      <div style="font-family:'Syne',sans-serif;font-weight:700;font-size:0.9rem;margin-bottom:4px">My Leave History</div>
      <div style="font-size:0.75rem;color:var(--muted);margin-bottom:2px">Last 10 requests</div>
      <table>
        <thead><tr><th>Type</th><th>From</th><th>To</th><th>Days</th><th>Status</th></tr></thead>
        <tbody>
          <?php foreach($leaves as $l):
            $cls = $l['status']==='approved'?'badge-active':($l['status']==='rejected'?'badge-inactive':'badge-leave');
          ?>
          <tr>
            <td><?= ucfirst($l['leave_type']) ?></td>
            <td style="color:var(--muted)"><?= date('d M Y',strtotime($l['start_date'])) ?></td>
            <td style="color:var(--muted)"><?= date('d M Y',strtotime($l['end_date'])) ?></td>
            <td><?= $l['days'] ?>d</td>
            <td><span class="badge <?= $cls ?>"><?= ucfirst($l['status']) ?></span></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>

<?php endif; ?>
</div>

<script>
function switchTab(name, el) {
  document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
  el.classList.add('active');
  document.getElementById('pane-' + name).classList.add('active');
}
</script>
</body>
</html>