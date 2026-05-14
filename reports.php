<<<<<<< HEAD
<?php
session_start();
require_once 'auth_guard.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_once 'db.php';

// Data for charts
$byDept = $pdo->query("SELECT d.name, COUNT(e.id) AS cnt FROM departments d LEFT JOIN employees e ON e.department_id=d.id GROUP BY d.id,d.name ORDER BY cnt DESC")->fetchAll();
$byStatus = $pdo->query("SELECT status, COUNT(*) AS cnt FROM employees GROUP BY status")->fetchAll();
$salaryByDept = $pdo->query("SELECT d.name, AVG(e.salary) AS avg_sal FROM departments d LEFT JOIN employees e ON e.department_id=d.id WHERE e.salary>0 GROUP BY d.id,d.name")->fetchAll();

// Attendance last 7 days
$attWeek = $pdo->query("SELECT date, COUNT(*) AS present FROM attendance WHERE status='present' AND date >= DATE_SUB(CURDATE(),INTERVAL 7 DAY) GROUP BY date ORDER BY date")->fetchAll();

$total  = $pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn();
$active = $pdo->query("SELECT COUNT(*) FROM employees WHERE status='active'")->fetchColumn();
$totalSalary = $pdo->query("SELECT SUM(salary) FROM employees WHERE status='active'")->fetchColumn();
$depts  = $pdo->query("SELECT COUNT(*) FROM departments")->fetchColumn();

// Export CSV
if (isset($_GET['export'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="staffcore_report_'.date('Y-m-d').'.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Name','Email','Phone','Department','Position','Salary','Status','Hire Date']);
    $rows = $pdo->query("SELECT e.name,e.email,e.phone,d.name AS dept,e.position,e.salary,e.status,e.hire_date FROM employees e LEFT JOIN departments d ON d.id=e.department_id")->fetchAll();
    foreach($rows as $r) fputcsv($out, $r);
    fclose($out); exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>StaffCore — Reports</title>
  <?php include 'sidebar.php'; ?>
  <link rel="stylesheet" href="admin.css"/>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
<main class="main">
  <header class="topbar">
    <div class="topbar-left">
      <div><h1 class="page-title">Reports</h1><p class="page-sub">Analytics & data exports</p></div>
    </div>
    <div class="topbar-right">
      <a href="?export=1" class="btn btn-ghost">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Export CSV
      </a>
    </div>
  </header>

  <div class="content">
    <!-- Summary -->
    <div class="mini-stats">
      <div class="mini-card"><div class="mini-icon blue"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div><div><div class="mini-label">Total Staff</div><div class="mini-value"><?= $total ?></div></div></div>
      <div class="mini-card"><div class="mini-icon green"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg></div><div><div class="mini-label">Active</div><div class="mini-value"><?= $active ?></div></div></div>
      <div class="mini-card"><div class="mini-icon purple"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div><div><div class="mini-label">Monthly Payroll</div><div class="mini-value">RM <?= number_format($totalSalary/1000,1) ?>k</div></div></div>
      <div class="mini-card"><div class="mini-icon orange"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg></div><div><div class="mini-label">Departments</div><div class="mini-value"><?= $depts ?></div></div></div>
    </div>

    <!-- Charts row 1 -->
    <div style="display:grid;grid-template-columns:1.5fr 1fr;gap:16px;">
      <div class="card fade-up">
        <div class="chart-header" style="margin-bottom:16px">
          <div class="card-title">Staff by Department</div>
          <div class="card-sub">Headcount per department</div>
        </div>
        <canvas id="deptChart" height="180"></canvas>
      </div>
      <div class="card fade-up">
        <div class="chart-header" style="margin-bottom:16px">
          <div class="card-title">Employment Status</div>
          <div class="card-sub">Active / Leave / Inactive</div>
        </div>
        <canvas id="statusChart" height="220"></canvas>
      </div>
    </div>

    <!-- Charts row 2 -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
      <div class="card fade-up">
        <div class="chart-header" style="margin-bottom:16px">
          <div class="card-title">Average Salary by Department</div>
          <div class="card-sub">RM per month</div>
        </div>
        <canvas id="salaryChart" height="200"></canvas>
      </div>
      <div class="card fade-up">
        <div class="chart-header" style="margin-bottom:16px">
          <div class="card-title">Attendance — Last 7 Days</div>
          <div class="card-sub">Daily present count</div>
        </div>
        <canvas id="attChart" height="200"></canvas>
      </div>
    </div>
  </div>
</main>

<script>
const CHART_OPTS = {
  plugins: {
    legend: { labels: { color:'#7d8590', font:{ family:'DM Sans' }, boxWidth:10, borderRadius:3, padding:14 } },
    tooltip: { backgroundColor:'#161b22', titleColor:'#e6edf3', bodyColor:'#7d8590', borderColor:'rgba(255,255,255,0.07)', borderWidth:1 }
  }
};

// Dept bar
new Chart(document.getElementById('deptChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode(array_column($byDept,'name')) ?>,
    datasets: [{ label:'Headcount', data: <?= json_encode(array_column($byDept,'cnt')) ?>,
      backgroundColor:['rgba(0,229,255,0.7)','rgba(88,166,255,0.7)','rgba(163,113,247,0.7)','rgba(63,185,80,0.7)','rgba(210,153,34,0.7)'],
      borderRadius:6, borderSkipped:false }]
  },
  options: { ...CHART_OPTS, plugins:{...CHART_OPTS.plugins,legend:{display:false}},
    scales: { x:{grid:{color:'rgba(255,255,255,0.04)'},ticks:{color:'#7d8590'}}, y:{grid:{color:'rgba(255,255,255,0.04)'},ticks:{color:'#7d8590'},beginAtZero:true} } }
});

// Status donut
const statusData = <?= json_encode($byStatus) ?>;
const statusMap = {active:'Active',on_leave:'On Leave',inactive:'Inactive'};
new Chart(document.getElementById('statusChart'), {
  type: 'doughnut',
  data: {
    labels: statusData.map(s=>statusMap[s.status]||s.status),
    datasets:[{ data:statusData.map(s=>s.cnt), backgroundColor:['rgba(63,185,80,0.8)','rgba(210,153,34,0.8)','rgba(255,71,87,0.8)'], borderWidth:0, hoverOffset:6 }]
  },
  options: { ...CHART_OPTS, cutout:'68%' }
});

// Salary bar
new Chart(document.getElementById('salaryChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode(array_column($salaryByDept,'name')) ?>,
    datasets: [{ label:'Avg Salary (RM)', data: <?= json_encode(array_map(fn($r)=>round($r['avg_sal'],2),$salaryByDept)) ?>,
      backgroundColor:'rgba(163,113,247,0.7)', borderRadius:6, borderSkipped:false }]
  },
  options: { ...CHART_OPTS, plugins:{...CHART_OPTS.plugins,legend:{display:false}},
    scales: { x:{grid:{color:'rgba(255,255,255,0.04)'},ticks:{color:'#7d8590'}}, y:{grid:{color:'rgba(255,255,255,0.04)'},ticks:{color:'#7d8590'},beginAtZero:true} } }
});

// Attendance line
const attData = <?= json_encode($attWeek) ?>;
new Chart(document.getElementById('attChart'), {
  type: 'line',
  data: {
    labels: attData.map(r=>r.date),
    datasets:[{ label:'Present', data:attData.map(r=>r.present),
      borderColor:'rgba(0,229,255,0.8)', backgroundColor:'rgba(0,229,255,0.08)',
      borderWidth:2, pointBackgroundColor:'#00e5ff', fill:true, tension:0.4 }]
  },
  options: { ...CHART_OPTS,
    scales: { x:{grid:{color:'rgba(255,255,255,0.04)'},ticks:{color:'#7d8590'}}, y:{grid:{color:'rgba(255,255,255,0.04)'},ticks:{color:'#7d8590'},beginAtZero:true} } }
});
</script>
</body>
=======
<?php
session_start();
require_once 'auth_guard.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
require_once 'db.php';

// Data for charts
$byDept = $pdo->query("SELECT d.name, COUNT(e.id) AS cnt FROM departments d LEFT JOIN employees e ON e.department_id=d.id GROUP BY d.id,d.name ORDER BY cnt DESC")->fetchAll();
$byStatus = $pdo->query("SELECT status, COUNT(*) AS cnt FROM employees GROUP BY status")->fetchAll();
$salaryByDept = $pdo->query("SELECT d.name, AVG(e.salary) AS avg_sal FROM departments d LEFT JOIN employees e ON e.department_id=d.id WHERE e.salary>0 GROUP BY d.id,d.name")->fetchAll();

// Attendance last 7 days
$attWeek = $pdo->query("SELECT date, COUNT(*) AS present FROM attendance WHERE status='present' AND date >= DATE_SUB(CURDATE(),INTERVAL 7 DAY) GROUP BY date ORDER BY date")->fetchAll();

$total  = $pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn();
$active = $pdo->query("SELECT COUNT(*) FROM employees WHERE status='active'")->fetchColumn();
$totalSalary = $pdo->query("SELECT SUM(salary) FROM employees WHERE status='active'")->fetchColumn();
$depts  = $pdo->query("SELECT COUNT(*) FROM departments")->fetchColumn();

// Export CSV
if (isset($_GET['export'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="staffcore_report_'.date('Y-m-d').'.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Name','Email','Phone','Department','Position','Salary','Status','Hire Date']);
    $rows = $pdo->query("SELECT e.name,e.email,e.phone,d.name AS dept,e.position,e.salary,e.status,e.hire_date FROM employees e LEFT JOIN departments d ON d.id=e.department_id")->fetchAll();
    foreach($rows as $r) fputcsv($out, $r);
    fclose($out); exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>StaffCore — Reports</title>
  <?php include 'sidebar.php'; ?>
  <link rel="stylesheet" href="admin.css"/>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
<main class="main">
  <header class="topbar">
    <div class="topbar-left">
      <div><h1 class="page-title">Reports</h1><p class="page-sub">Analytics & data exports</p></div>
    </div>
    <div class="topbar-right">
      <a href="?export=1" class="btn btn-ghost">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Export CSV
      </a>
    </div>
  </header>

  <div class="content">
    <!-- Summary -->
    <div class="mini-stats">
      <div class="mini-card"><div class="mini-icon blue"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div><div><div class="mini-label">Total Staff</div><div class="mini-value"><?= $total ?></div></div></div>
      <div class="mini-card"><div class="mini-icon green"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg></div><div><div class="mini-label">Active</div><div class="mini-value"><?= $active ?></div></div></div>
      <div class="mini-card"><div class="mini-icon purple"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div><div><div class="mini-label">Monthly Payroll</div><div class="mini-value">RM <?= number_format($totalSalary/1000,1) ?>k</div></div></div>
      <div class="mini-card"><div class="mini-icon orange"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg></div><div><div class="mini-label">Departments</div><div class="mini-value"><?= $depts ?></div></div></div>
    </div>

    <!-- Charts row 1 -->
    <div style="display:grid;grid-template-columns:1.5fr 1fr;gap:16px;">
      <div class="card fade-up">
        <div class="chart-header" style="margin-bottom:16px">
          <div class="card-title">Staff by Department</div>
          <div class="card-sub">Headcount per department</div>
        </div>
        <canvas id="deptChart" height="180"></canvas>
      </div>
      <div class="card fade-up">
        <div class="chart-header" style="margin-bottom:16px">
          <div class="card-title">Employment Status</div>
          <div class="card-sub">Active / Leave / Inactive</div>
        </div>
        <canvas id="statusChart" height="220"></canvas>
      </div>
    </div>

    <!-- Charts row 2 -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
      <div class="card fade-up">
        <div class="chart-header" style="margin-bottom:16px">
          <div class="card-title">Average Salary by Department</div>
          <div class="card-sub">RM per month</div>
        </div>
        <canvas id="salaryChart" height="200"></canvas>
      </div>
      <div class="card fade-up">
        <div class="chart-header" style="margin-bottom:16px">
          <div class="card-title">Attendance — Last 7 Days</div>
          <div class="card-sub">Daily present count</div>
        </div>
        <canvas id="attChart" height="200"></canvas>
      </div>
    </div>
  </div>
</main>

<script>
const CHART_OPTS = {
  plugins: {
    legend: { labels: { color:'#7d8590', font:{ family:'DM Sans' }, boxWidth:10, borderRadius:3, padding:14 } },
    tooltip: { backgroundColor:'#161b22', titleColor:'#e6edf3', bodyColor:'#7d8590', borderColor:'rgba(255,255,255,0.07)', borderWidth:1 }
  }
};

// Dept bar
new Chart(document.getElementById('deptChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode(array_column($byDept,'name')) ?>,
    datasets: [{ label:'Headcount', data: <?= json_encode(array_column($byDept,'cnt')) ?>,
      backgroundColor:['rgba(0,229,255,0.7)','rgba(88,166,255,0.7)','rgba(163,113,247,0.7)','rgba(63,185,80,0.7)','rgba(210,153,34,0.7)'],
      borderRadius:6, borderSkipped:false }]
  },
  options: { ...CHART_OPTS, plugins:{...CHART_OPTS.plugins,legend:{display:false}},
    scales: { x:{grid:{color:'rgba(255,255,255,0.04)'},ticks:{color:'#7d8590'}}, y:{grid:{color:'rgba(255,255,255,0.04)'},ticks:{color:'#7d8590'},beginAtZero:true} } }
});

// Status donut
const statusData = <?= json_encode($byStatus) ?>;
const statusMap = {active:'Active',on_leave:'On Leave',inactive:'Inactive'};
new Chart(document.getElementById('statusChart'), {
  type: 'doughnut',
  data: {
    labels: statusData.map(s=>statusMap[s.status]||s.status),
    datasets:[{ data:statusData.map(s=>s.cnt), backgroundColor:['rgba(63,185,80,0.8)','rgba(210,153,34,0.8)','rgba(255,71,87,0.8)'], borderWidth:0, hoverOffset:6 }]
  },
  options: { ...CHART_OPTS, cutout:'68%' }
});

// Salary bar
new Chart(document.getElementById('salaryChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode(array_column($salaryByDept,'name')) ?>,
    datasets: [{ label:'Avg Salary (RM)', data: <?= json_encode(array_map(fn($r)=>round($r['avg_sal'],2),$salaryByDept)) ?>,
      backgroundColor:'rgba(163,113,247,0.7)', borderRadius:6, borderSkipped:false }]
  },
  options: { ...CHART_OPTS, plugins:{...CHART_OPTS.plugins,legend:{display:false}},
    scales: { x:{grid:{color:'rgba(255,255,255,0.04)'},ticks:{color:'#7d8590'}}, y:{grid:{color:'rgba(255,255,255,0.04)'},ticks:{color:'#7d8590'},beginAtZero:true} } }
});

// Attendance line
const attData = <?= json_encode($attWeek) ?>;
new Chart(document.getElementById('attChart'), {
  type: 'line',
  data: {
    labels: attData.map(r=>r.date),
    datasets:[{ label:'Present', data:attData.map(r=>r.present),
      borderColor:'rgba(0,229,255,0.8)', backgroundColor:'rgba(0,229,255,0.08)',
      borderWidth:2, pointBackgroundColor:'#00e5ff', fill:true, tension:0.4 }]
  },
  options: { ...CHART_OPTS,
    scales: { x:{grid:{color:'rgba(255,255,255,0.04)'},ticks:{color:'#7d8590'}}, y:{grid:{color:'rgba(255,255,255,0.04)'},ticks:{color:'#7d8590'},beginAtZero:true} } }
});
</script>
</body>
>>>>>>> 98e1e05841e4235727ebf4e70697bce65fb3fe24
</html>