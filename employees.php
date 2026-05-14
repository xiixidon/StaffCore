<?php
session_start();
require_once 'auth_guard.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_once 'db.php';

// Handle CRUD actions
$msg = ''; $msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $stmt = $pdo->prepare("INSERT INTO employees (name,email,phone,department_id,position,salary,status,hire_date) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->execute([$_POST['name'],$_POST['email'],$_POST['phone'],$_POST['department_id'],$_POST['position'],$_POST['salary'],$_POST['status'],$_POST['hire_date']]);
        $pdo->prepare("INSERT INTO activity_log (user_id,action,target,target_id) VALUES (?,?,?,?)")->execute([$_SESSION['user_id'],'Added employee: '.$_POST['name'],'employee',$pdo->lastInsertId()]);
        $msg = 'Employee added successfully!'; $msgType = 'success';

    } elseif ($action === 'edit') {
        $stmt = $pdo->prepare("UPDATE employees SET name=?,email=?,phone=?,department_id=?,position=?,salary=?,status=?,hire_date=? WHERE id=?");
        $stmt->execute([$_POST['name'],$_POST['email'],$_POST['phone'],$_POST['department_id'],$_POST['position'],$_POST['salary'],$_POST['status'],$_POST['hire_date'],$_POST['id']]);
        $pdo->prepare("INSERT INTO activity_log (user_id,action,target,target_id) VALUES (?,?,?,?)")->execute([$_SESSION['user_id'],'Updated employee: '.$_POST['name'],'employee',$_POST['id']]);
        $msg = 'Employee updated!'; $msgType = 'success';

    } elseif ($action === 'delete') {
        $emp = $pdo->prepare("SELECT name FROM employees WHERE id=?"); $emp->execute([$_POST['id']]); $e = $emp->fetch();
        $pdo->prepare("DELETE FROM employees WHERE id=?")->execute([$_POST['id']]);
        $pdo->prepare("INSERT INTO activity_log (user_id,action,target,target_id) VALUES (?,?,?,?)")->execute([$_SESSION['user_id'],'Deleted employee: '.($e['name']??''),'employee',$_POST['id']]);
        $msg = 'Employee deleted.'; $msgType = 'success';
    }
}

// Fetch data
$search = $_GET['search'] ?? '';
$filter = $_GET['status'] ?? '';
$q = "SELECT e.*, d.name AS dept_name FROM employees e LEFT JOIN departments d ON d.id=e.department_id WHERE 1=1";
$params = [];
if ($search) { $q .= " AND (e.name LIKE ? OR e.email LIKE ? OR e.position LIKE ?)"; $params = array_merge($params, ["%$search%","%$search%","%$search%"]); }
if ($filter) { $q .= " AND e.status=?"; $params[] = $filter; }
$q .= " ORDER BY e.created_at DESC";
$stmt = $pdo->prepare($q); $stmt->execute($params);
$employees = $stmt->fetchAll();

$departments = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll();
$total  = $pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn();
$active = $pdo->query("SELECT COUNT(*) FROM employees WHERE status='active'")->fetchColumn();
$leave  = $pdo->query("SELECT COUNT(*) FROM employees WHERE status='on_leave'")->fetchColumn();
$inactive=$pdo->query("SELECT COUNT(*) FROM employees WHERE status='inactive'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>StaffCore — Employees</title>
  <?php include 'sidebar.php'; ?>
  <link rel="stylesheet" href="admin.css"/>
</head>
<body>
<main class="main">
  <header class="topbar">
    <div class="topbar-left">
      <div><h1 class="page-title">Employees</h1><p class="page-sub">Manage all staff records</p></div>
    </div>
    <div class="topbar-right">
      <button class="btn btn-primary" onclick="openModal('addModal')">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add Employee
      </button>
    </div>
  </header>

  <div class="content">
    <!-- Mini stats -->
    <div class="mini-stats">
      <div class="mini-card"><div class="mini-icon blue"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div><div><div class="mini-label">Total</div><div class="mini-value"><?= $total ?></div></div></div>
      <div class="mini-card"><div class="mini-icon green"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg></div><div><div class="mini-label">Active</div><div class="mini-value"><?= $active ?></div></div></div>
      <div class="mini-card"><div class="mini-icon orange"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></div><div><div class="mini-label">On Leave</div><div class="mini-value"><?= $leave ?></div></div></div>
      <div class="mini-card"><div class="mini-icon red"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg></div><div><div class="mini-label">Inactive</div><div class="mini-value"><?= $inactive ?></div></div></div>
    </div>

    <!-- Table card -->
    <div class="card fade-up">
      <div class="card-header">
        <div><div class="card-title">All Employees</div><div class="card-sub"><?= count($employees) ?> records found</div></div>
        <div style="display:flex;gap:10px;align-items:center;">
          <form method="GET" style="display:flex;gap:10px;">
            <div class="search-wrap">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
              <input type="text" name="search" placeholder="Search..." value="<?= htmlspecialchars($search) ?>"/>
            </div>
            <select name="status" class="btn btn-ghost" onchange="this.form.submit()" style="padding:8px 12px;">
              <option value="">All Status</option>
              <option value="active" <?= $filter==='active'?'selected':'' ?>>Active</option>
              <option value="on_leave" <?= $filter==='on_leave'?'selected':'' ?>>On Leave</option>
              <option value="inactive" <?= $filter==='inactive'?'selected':'' ?>>Inactive</option>
            </select>
          </form>
        </div>
      </div>

      <table class="data-table">
        <thead><tr><th>#</th><th>Name</th><th>Department</th><th>Position</th><th>Salary</th><th>Status</th><th>Hire Date</th><th>Actions</th></tr></thead>
        <tbody>
          <?php if(empty($employees)): ?>
          <tr><td colspan="8"><div class="empty-state"><p>No employees found.</p></div></td></tr>
          <?php else: foreach($employees as $i=>$e): ?>
          <tr>
            <td style="color:var(--muted)"><?= $i+1 ?></td>
            <td><strong><?= htmlspecialchars($e['name']) ?></strong><br><span style="color:var(--muted);font-size:0.75rem"><?= htmlspecialchars($e['email']) ?></span></td>
            <td style="color:var(--muted)"><?= htmlspecialchars($e['dept_name']??'—') ?></td>
            <td><?= htmlspecialchars($e['position']??'—') ?></td>
            <td style="color:var(--green)">RM <?= number_format($e['salary'],2) ?></td>
            <td><span class="badge badge-<?= $e['status']==='on_leave'?'leave':$e['status'] ?>"><?= ucfirst(str_replace('_',' ',$e['status'])) ?></span></td>
            <td style="color:var(--muted)"><?= $e['hire_date'] ? date('d M Y',strtotime($e['hire_date'])) : '—' ?></td>
            <td>
              <div style="display:flex;gap:6px;">
                <button class="btn btn-ghost btn-sm" onclick="editEmployee(<?= htmlspecialchars(json_encode($e)) ?>)">Edit</button>
                <button class="btn btn-danger btn-sm" onclick="deleteEmployee(<?= $e['id'] ?>, '<?= htmlspecialchars($e['name']) ?>')">Delete</button>
              </div>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>

<!-- Add Modal -->
<div class="modal-overlay" id="addModal">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">Add New Employee</span>
      <button class="close-btn" onclick="closeModal('addModal')">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="add"/>
      <div class="form-grid">
        <div class="field"><label>Full Name</label><input type="text" name="name" placeholder="e.g. Alice Tan" required/></div>
        <div class="field"><label>Email</label><input type="email" name="email" placeholder="alice@company.com" required/></div>
        <div class="field"><label>Phone</label><input type="text" name="phone" placeholder="012-3456789"/></div>
        <div class="field"><label>Department</label>
          <select name="department_id">
            <option value="">— Select —</option>
            <?php foreach($departments as $d): ?><option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="field"><label>Position</label><input type="text" name="position" placeholder="e.g. Engineer"/></div>
        <div class="field"><label>Salary (RM)</label><input type="number" name="salary" step="0.01" placeholder="5000.00"/></div>
        <div class="field"><label>Status</label>
          <select name="status">
            <option value="active">Active</option>
            <option value="on_leave">On Leave</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
        <div class="field"><label>Hire Date</label><input type="date" name="hire_date"/></div>
      </div>
      <div class="form-actions">
        <button type="button" class="btn btn-ghost" onclick="closeModal('addModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Add Employee</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editModal">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">Edit Employee</span>
      <button class="close-btn" onclick="closeModal('editModal')">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="edit"/>
      <input type="hidden" name="id" id="editId"/>
      <div class="form-grid">
        <div class="field"><label>Full Name</label><input type="text" name="name" id="editName" required/></div>
        <div class="field"><label>Email</label><input type="email" name="email" id="editEmail" required/></div>
        <div class="field"><label>Phone</label><input type="text" name="phone" id="editPhone"/></div>
        <div class="field"><label>Department</label>
          <select name="department_id" id="editDept">
            <option value="">— Select —</option>
            <?php foreach($departments as $d): ?><option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="field"><label>Position</label><input type="text" name="position" id="editPosition"/></div>
        <div class="field"><label>Salary (RM)</label><input type="number" name="salary" id="editSalary" step="0.01"/></div>
        <div class="field"><label>Status</label>
          <select name="status" id="editStatus">
            <option value="active">Active</option>
            <option value="on_leave">On Leave</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
        <div class="field"><label>Hire Date</label><input type="date" name="hire_date" id="editHireDate"/></div>
      </div>
      <div class="form-actions">
        <button type="button" class="btn btn-ghost" onclick="closeModal('editModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<!-- Delete Form -->
<form method="POST" id="deleteForm" style="display:none">
  <input type="hidden" name="action" value="delete"/>
  <input type="hidden" name="id" id="deleteId"/>
</form>

<?php if($msg): ?>
<div class="toast <?= $msgType ?> show" id="toast"><?= htmlspecialchars($msg) ?></div>
<script>setTimeout(()=>document.getElementById('toast').classList.remove('show'),3000);</script>
<?php endif; ?>

<script>
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

function editEmployee(e) {
  document.getElementById('editId').value       = e.id;
  document.getElementById('editName').value     = e.name;
  document.getElementById('editEmail').value    = e.email;
  document.getElementById('editPhone').value    = e.phone || '';
  document.getElementById('editDept').value     = e.department_id || '';
  document.getElementById('editPosition').value = e.position || '';
  document.getElementById('editSalary').value   = e.salary || '';
  document.getElementById('editStatus').value   = e.status;
  document.getElementById('editHireDate').value = e.hire_date || '';
  openModal('editModal');
}

function deleteEmployee(id, name) {
  if (confirm('Delete ' + name + '? This cannot be undone.')) {
    document.getElementById('deleteId').value = id;
    document.getElementById('deleteForm').submit();
  }
}

// Close modal on overlay click
document.querySelectorAll('.modal-overlay').forEach(o => {
  o.addEventListener('click', e => { if(e.target===o) o.classList.remove('open'); });
});
</script>
</body>
</html>