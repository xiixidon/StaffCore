<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
if ($_SESSION['user_role'] === 'admin') { header('Location: admin_dashboard.html'); exit; }
require_once 'db.php';

$stmt = $pdo->prepare("SELECT e.*, d.name AS dept_name FROM employees e LEFT JOIN departments d ON d.id = e.department_id WHERE e.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$emp = $stmt->fetch();

$att = [];
if ($emp) {
    $stmt2 = $pdo->prepare("SELECT * FROM attendance WHERE employee_id = ? ORDER BY date DESC LIMIT 30");
    $stmt2->execute([$emp['id']]);
    $att = $stmt2->fetchAll();
}

$present = count(array_filter($att, fn($a) => $a['status'] === 'present'));
$absent  = count(array_filter($att, fn($a) => $a['status'] === 'absent'));
$late    = count(array_filter($att, fn($a) => $a['status'] === 'late'));
$total   = count($att);
$rate    = $total > 0 ? round(($present / $total) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>StaffCore — My Portal</title>
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

    .topbar {
      background: var(--surface); border-bottom: 1px solid var(--border);
      padding: 0 40px; height: 64px;
      display: flex; align-items: center; justify-content: space-between;
      position: sticky; top: 0; z-index: 50;
    }
    .brand { display: flex; align-items: center; gap: 10px; font-family: 'Syne', sans-serif; font-weight: 800; font-size: 1.1rem; }
    .brand-icon { width: 36px; height: 36px; background: var(--surface2); border: 1px solid var(--border); border-radius: 8px; display: flex; align-items: center; justify-content: center; }
    .portal-label { font-size: 0.78rem; color: var(--muted); background: var(--surface2); border: 1px solid var(--border); padding: 4px 12px; border-radius: 20px; }
    .logout-btn { display: flex; align-items: center; gap: 7px; background: rgba(255,71,87,0.08); border: 1px solid rgba(255,71,87,0.2); color: var(--red); padding: 7px 14px; border-radius: 8px; font-family: 'DM Sans', sans-serif; font-size: 0.83rem; font-weight: 500; text-decoration: none; transition: all 0.2s; }
    .logout-btn:hover { background: rgba(255,71,87,0.15); }

    .page { max-width: 980px; margin: 0 auto; padding: 36px 24px; display: flex; flex-direction: column; gap: 20px; }

    .hero-card { background: var(--surface); border: 1px solid var(--border); border-radius: 20px; padding: 32px; position: relative; overflow: hidden; animation: fadeUp 0.5s ease both; }
    .hero-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, var(--accent2), var(--accent)); }
    .hero-glow { position: absolute; top: -60px; right: -60px; width: 220px; height: 220px; background: radial-gradient(circle, rgba(0,229,255,0.05), transparent 70%); pointer-events: none; }
    .hero-inner { display: flex; align-items: center; gap: 24px; position: relative; flex-wrap: wrap; }
    .avatar { width: 80px; height: 80px; border-radius: 50%; flex-shrink: 0; background: linear-gradient(135deg, var(--accent), var(--accent2)); display: flex; align-items: center; justify-content: center; font-family: 'Syne', sans-serif; font-weight: 800; font-size: 2rem; color: #080c10; box-shadow: 0 0 0 4px rgba(0,229,255,0.15); }
    .hero-text { flex: 1; min-width: 200px; }
    .hero-name { font-family: 'Syne', sans-serif; font-size: 1.8rem; font-weight: 800; letter-spacing: -0.5px; }
    .hero-pos { color: var(--accent); font-size: 0.9rem; font-weight: 500; margin-top: 3px; }
    .hero-meta { display: flex; flex-wrap: wrap; gap: 14px; margin-top: 14px; align-items: center; }
    .meta-item { display: flex; align-items: center; gap: 6px; font-size: 0.82rem; color: var(--muted); }
    .status-pill { display: inline-flex; align-items: center; gap: 5px; padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; background: rgba(63,185,80,0.12); color: var(--green); border: 1px solid rgba(63,185,80,0.2); }
    .att-rate { text-align: center; background: var(--surface2); border: 1px solid var(--border); border-radius: 14px; padding: 18px 28px; flex-shrink: 0; }
    .rate-value { font-family: 'Syne', sans-serif; font-size: 2.4rem; font-weight: 800; color: var(--accent); }
    .rate-label { font-size: 0.7rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px; margin-top: 2px; }

    .grid-4 { display: grid; grid-template-columns: repeat(4,1fr); gap: 14px; }
    .grid-3 { display: grid; grid-template-columns: repeat(3,1fr); gap: 14px; }

    .info-card { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 20px; animation: fadeUp 0.5s ease both; transition: border-color 0.2s, transform 0.2s; }
    .info-card:hover { border-color: rgba(0,229,255,0.15); transform: translateY(-2px); }
    .info-label { font-size: 0.7rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; display: flex; align-items: center; gap: 6px; }
    .info-value { font-family: 'Syne', sans-serif; font-size: 1.1rem; font-weight: 700; }
    .info-value.big { font-size: 1.5rem; }
    .info-value.green { color: var(--green); }
    .info-value.accent { color: var(--accent); }

    .stat-card { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 18px 20px; display: flex; align-items: center; gap: 14px; animation: fadeUp 0.5s ease both; }
    .stat-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .stat-icon.green  { background: rgba(63,185,80,0.12);  color: var(--green); }
    .stat-icon.red    { background: rgba(255,71,87,0.12);  color: var(--red); }
    .stat-icon.orange { background: rgba(210,153,34,0.12); color: var(--orange); }
    .stat-label { font-size: 0.72rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px; }
    .stat-value { font-family: 'Syne', sans-serif; font-size: 1.6rem; font-weight: 800; margin-top: 1px; }

    .table-card { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; overflow: hidden; animation: fadeUp 0.5s ease both; }
    .table-header { padding: 20px 24px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
    .table-title { font-family: 'Syne', sans-serif; font-weight: 700; font-size: 0.95rem; }
    .table-sub { font-size: 0.75rem; color: var(--muted); margin-top: 2px; }
    .record-count { font-size: 0.78rem; color: var(--muted); background: var(--surface2); border: 1px solid var(--border); padding: 3px 10px; border-radius: 20px; }

    table { width: 100%; border-collapse: collapse; font-size: 0.84rem; }
    thead th { text-align: left; padding: 11px 24px; color: var(--muted); font-weight: 500; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px; background: var(--surface2); border-bottom: 1px solid var(--border); }
    tbody td { padding: 13px 24px; border-bottom: 1px solid rgba(255,255,255,0.04); vertical-align: middle; }
    tbody tr:last-child td { border-bottom: none; }
    tbody tr:hover td { background: rgba(255,255,255,0.02); }

    .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: 600; }
    .badge-present  { background: rgba(63,185,80,0.12);  color: var(--green);  border: 1px solid rgba(63,185,80,0.2); }
    .badge-absent   { background: rgba(255,71,87,0.12);  color: var(--red);    border: 1px solid rgba(255,71,87,0.2); }
    .badge-late     { background: rgba(210,153,34,0.12); color: var(--orange); border: 1px solid rgba(210,153,34,0.2); }
    .badge-half_day { background: rgba(163,113,247,0.12);color: var(--purple); border: 1px solid rgba(163,113,247,0.2); }

    .empty { text-align: center; padding: 50px; color: var(--muted); font-size: 0.88rem; }
    @keyframes fadeUp { from { opacity:0; transform:translateY(16px); } to { opacity:1; transform:translateY(0); } }
    @media (max-width: 768px) { .grid-4,.grid-3 { grid-template-columns: 1fr 1fr; } .hero-inner { flex-direction: column; } .att-rate { width: 100%; } }
  </style>
</head>
<body>

<div class="topbar">
  <div class="brand">
    <div class="brand-icon">
      <svg width="22" height="22" viewBox="0 0 28 28" fill="none">
        <rect x="2" y="2" width="10" height="10" rx="2" fill="#00e5ff"/>
        <rect x="16" y="2" width="10" height="10" rx="2" fill="#00e5ff" opacity="0.5"/>
        <rect x="2" y="16" width="10" height="10" rx="2" fill="#00e5ff" opacity="0.5"/>
        <rect x="16" y="16" width="10" height="10" rx="2" fill="#00e5ff"/>
      </svg>
    </div>
    StaffCore
  </div>
  <div style="display:flex;align-items:center;gap:10px;">
    <div style="display:flex;gap:6px;">
      <a href="staff_dashboard.php" style="padding:7px 14px;border-radius:8px;font-size:0.83rem;font-weight:500;color:var(--accent);text-decoration:none;border:1px solid rgba(0,229,255,0.3);background:rgba(0,229,255,0.08);">Dashboard</a>
      <a href="profile_edit.php" style="padding:7px 14px;border-radius:8px;font-size:0.83rem;font-weight:500;color:var(--muted);text-decoration:none;border:1px solid var(--border);background:var(--surface2);transition:all 0.2s;">My Profile & Leave</a>
    </div>
    <a href="logout.php" style="display:flex;align-items:center;gap:7px;background:rgba(255,71,87,0.08);border:1px solid rgba(255,71,87,0.2);color:var(--red);padding:7px 14px;border-radius:8px;font-size:0.83rem;font-weight:500;text-decoration:none;">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      Logout
    </a>
  </div>
</div>

<div class="page">
  <?php if (!$emp): ?>
  <div class="table-card" style="padding:60px;text-align:center;">
    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--muted)" stroke-width="1.5" style="margin:0 auto 16px;display:block;opacity:0.3"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
    <div style="font-family:'Syne',sans-serif;font-size:1.1rem;font-weight:700;margin-bottom:8px">No Profile Linked</div>
    <div style="color:var(--muted);font-size:0.85rem">Contact your administrator to link your account.</div>
  </div>
  <?php else: ?>

  <!-- Hero -->
  <div class="hero-card">
    <div class="hero-glow"></div>
    <div class="hero-inner">
      <div class="avatar"><?= strtoupper(substr($emp['name'],0,1)) ?></div>
      <div class="hero-text">
        <div class="hero-name"><?= htmlspecialchars($emp['name']) ?></div>
        <div class="hero-pos"><?= htmlspecialchars($emp['position']??'Staff Member') ?></div>
        <div class="hero-meta">
          <div class="meta-item"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg><?= htmlspecialchars($emp['dept_name']??'No Department') ?></div>
          <div class="meta-item"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,12 2,6"/></svg><?= htmlspecialchars($emp['email']) ?></div>
          <?php if($emp['phone']): ?><div class="meta-item"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13.6 19.79 19.79 0 0 1 1.61 5 2 2 0 0 1 3.6 2.87h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 10.09a16 16 0 0 0 6 6l.92-.92a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21.73 17.5z"/></svg><?= htmlspecialchars($emp['phone']) ?></div><?php endif; ?>
          <span class="status-pill"><span style="width:6px;height:6px;border-radius:50%;background:var(--green);display:inline-block"></span><?= ucfirst(str_replace('_',' ',$emp['status'])) ?></span>
        </div>
      </div>
      <div class="att-rate">
        <div class="rate-value"><?= $rate ?>%</div>
        <div class="rate-label">Attendance Rate</div>
      </div>
    </div>
  </div>

  <!-- Info cards -->
  <div class="grid-4">
    <div class="info-card">
      <div class="info-label"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>Monthly Salary</div>
      <div class="info-value big green">RM <?= number_format($emp['salary'],2) ?></div>
    </div>
    <div class="info-card">
      <div class="info-label"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>Hire Date</div>
      <div class="info-value"><?= $emp['hire_date'] ? date('d M Y', strtotime($emp['hire_date'])) : '—' ?></div>
    </div>
    <div class="info-card">
      <div class="info-label"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>Department</div>
      <div class="info-value"><?= htmlspecialchars($emp['dept_name']??'—') ?></div>
    </div>
    <div class="info-card">
      <div class="info-label"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>Years of Service</div>
      <div class="info-value accent"><?= $emp['hire_date'] ? floor((time()-strtotime($emp['hire_date']))/31536000).' yr(s)' : '—' ?></div>
    </div>
  </div>

  <!-- Attendance stats -->
  <div class="grid-3">
    <div class="stat-card">
      <div class="stat-icon green"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg></div>
      <div><div class="stat-label">Present</div><div class="stat-value"><?= $present ?></div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon red"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg></div>
      <div><div class="stat-label">Absent</div><div class="stat-value"><?= $absent ?></div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon orange"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
      <div><div class="stat-label">Late</div><div class="stat-value"><?= $late ?></div></div>
    </div>
  </div>

  <!-- Attendance table -->
  <div class="table-card">
    <div class="table-header">
      <div><div class="table-title">Attendance History</div><div class="table-sub">Last 30 days</div></div>
      <span class="record-count"><?= $total ?> records</span>
    </div>
    <?php if(empty($att)): ?>
    <div class="empty">No attendance records found.</div>
    <?php else: ?>
    <table>
      <thead><tr><th>Date</th><th>Status</th><th>Check In</th><th>Check Out</th><th>Hours Worked</th></tr></thead>
      <tbody>
        <?php foreach($att as $a):
          $hours = '—';
          if ($a['check_in'] && $a['check_out']) {
            $diff = strtotime($a['check_out']) - strtotime($a['check_in']);
            $hours = round($diff/3600,1).'h';
          }
        ?>
        <tr>
          <td><strong><?= date('d M Y',strtotime($a['date'])) ?></strong> <span style="color:var(--muted);font-size:0.75rem"><?= date('D',strtotime($a['date'])) ?></span></td>
          <td><span class="badge badge-<?= $a['status'] ?>"><?= ucfirst(str_replace('_',' ',$a['status'])) ?></span></td>
          <td style="color:var(--muted)"><?= $a['check_in'] ? date('h:i A',strtotime($a['check_in'])) : '—' ?></td>
          <td style="color:var(--muted)"><?= $a['check_out'] ? date('h:i A',strtotime($a['check_out'])) : '—' ?></td>
          <td style="color:var(--green);font-weight:600"><?= $hours ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>

  <?php endif; ?>
</div>
</body>
</html>