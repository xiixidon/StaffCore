<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_once 'db.php';

$msg = ''; $msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $pdo->prepare("INSERT INTO departments (name) VALUES (?)")->execute([$_POST['name']]);
        $pdo->prepare("INSERT INTO activity_log (user_id,action,target) VALUES (?,?,?)")->execute([$_SESSION['user_id'],'Added department: '.$_POST['name'],'department']);
        $msg = 'Department added!'; $msgType = 'success';
    } elseif ($action === 'edit') {
        $pdo->prepare("UPDATE departments SET name=? WHERE id=?")->execute([$_POST['name'],$_POST['id']]);
        $msg = 'Department updated!'; $msgType = 'success';
    } elseif ($action === 'delete') {
        $pdo->prepare("UPDATE employees SET department_id=NULL WHERE department_id=?")->execute([$_POST['id']]);
        $pdo->prepare("DELETE FROM departments WHERE id=?")->execute([$_POST['id']]);
        $msg = 'Department deleted.'; $msgType = 'success';
    }
}

$departments = $pdo->query("
    SELECT d.*, COUNT(e.id) AS emp_count
    FROM departments d
    LEFT JOIN employees e ON e.department_id = d.id
    GROUP BY d.id ORDER BY d.name
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>StaffCore — Departments</title>
  <?php include 'sidebar.php'; ?>
  <link rel="stylesheet" href="admin.css"/>
</head>
<body>
<main class="main">
  <header class="topbar">
    <div class="topbar-left">
      <div><h1 class="page-title">Departments</h1><p class="page-sub">Manage company departments</p></div>
    </div>
    <div class="topbar-right">
      <button class="btn btn-primary" onclick="openModal('addModal')">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add Department
      </button>
    </div>
  </header>

  <div class="content">
    <!-- Department Cards -->
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;">
      <?php foreach($departments as $d): ?>
      <div class="card fade-up" style="display:flex;flex-direction:column;gap:16px;">
        <div style="display:flex;align-items:center;gap:14px;">
          <div class="mini-icon blue" style="width:48px;height:48px;border-radius:12px;flex-shrink:0;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
          </div>
          <div>
            <div style="font-family:'Syne',sans-serif;font-weight:700;font-size:1rem"><?= htmlspecialchars($d['name']) ?></div>
            <div style="color:var(--muted);font-size:0.78rem;margin-top:2px"><?= $d['emp_count'] ?> employee<?= $d['emp_count']!=1?'s':'' ?></div>
          </div>
        </div>
        <div style="display:flex;gap:8px;border-top:1px solid var(--border);padding-top:14px;">
          <button class="btn btn-ghost btn-sm" style="flex:1" onclick="editDept(<?= $d['id'] ?>, '<?= htmlspecialchars($d['name']) ?>')">Edit</button>
          <button class="btn btn-danger btn-sm" style="flex:1" onclick="deleteDept(<?= $d['id'] ?>, '<?= htmlspecialchars($d['name']) ?>')">Delete</button>
        </div>
      </div>
      <?php endforeach; ?>

      <?php if(empty($departments)): ?>
      <div class="card"><div class="empty-state"><p>No departments yet. Add one!</p></div></div>
      <?php endif; ?>
    </div>
  </div>
</main>

<!-- Add Modal -->
<div class="modal-overlay" id="addModal">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">Add Department</span>
      <button class="close-btn" onclick="closeModal('addModal')">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="add"/>
      <div class="field"><label>Department Name</label><input type="text" name="name" placeholder="e.g. Engineering" required/></div>
      <div class="form-actions">
        <button type="button" class="btn btn-ghost" onclick="closeModal('addModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Add Department</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editModal">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">Edit Department</span>
      <button class="close-btn" onclick="closeModal('editModal')">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="edit"/>
      <input type="hidden" name="id" id="editId"/>
      <div class="field"><label>Department Name</label><input type="text" name="name" id="editName" required/></div>
      <div class="form-actions">
        <button type="button" class="btn btn-ghost" onclick="closeModal('editModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>

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
function editDept(id, name) {
  document.getElementById('editId').value   = id;
  document.getElementById('editName').value = name;
  openModal('editModal');
}
function deleteDept(id, name) {
  if (confirm('Delete department "' + name + '"? Employees will be unassigned.')) {
    document.getElementById('deleteId').value = id;
    document.getElementById('deleteForm').submit();
  }
}
document.querySelectorAll('.modal-overlay').forEach(o => {
  o.addEventListener('click', e => { if(e.target===o) o.classList.remove('open'); });
});
</script>
</body>
</html>