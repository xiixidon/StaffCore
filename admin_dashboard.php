<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php'); exit;
}
require_once 'db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>StaffCore — Admin Dashboard</title>
  <?php include 'sidebar.php'; ?>
  <link rel="stylesheet" href="admin.css"/>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
<main class="main">

  <header class="topbar">
    <div class="topbar-left">
      <div>
        <h1 class="page-title">Dashboard</h1>
        <p class="page-sub">Welcome back, <?= htmlspecialchars($_SESSION['user_name']) ?></p>
      </div>
    </div>
    <div class="topbar-right">
      <div class="date-badge" id="dateBadge"></div>
      <button class="notif-btn">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
        <span class="notif-dot"></span>
      </button>
    </div>
  </header>

  <div class="content">

    <!-- STAT CARDS -->
    <section class="stats-grid">
      <div class="stat-card" style="--delay:0.05s">
        <div class="stat-icon blue">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <div class="stat-info">
          <span class="stat-label">Total Employees</span>
          <span class="stat-value" id="statTotal">—</span>
        </div>
        <div class="stat-trend up">↑ 3 this month</div>
      </div>

      <div class="stat-card" style="--delay:0.1s">
        <div class="stat-icon green">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
        </div>
        <div class="stat-info">
          <span class="stat-label">Active Staff</span>
          <span class="stat-value" id="statActive">—</span>
        </div>
        <div class="stat-trend up">↑ Good retention</div>
      </div>

      <div class="stat-card" style="--delay:0.15s">
        <div class="stat-icon orange">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        </div>
        <div class="stat-info">
          <span class="stat-label">On Leave</span>
          <span class="stat-value" id="statLeave">—</span>
        </div>
        <div class="stat-trend neutral">— unchanged</div>
      </div>

      <div class="stat-card" style="--delay:0.2s">
        <div class="stat-icon purple">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
        </div>
        <div class="stat-info">
          <span class="stat-label">Departments</span>
          <span class="stat-value" id="statDepts">—</span>
        </div>
        <div class="stat-trend neutral">— unchanged</div>
      </div>
    </section>

    <!-- CHARTS ROW -->
    <section class="charts-row">
      <div class="chart-card wide">
        <div class="chart-header">
          <span class="chart-title">Staff by Department</span>
          <span class="chart-sub">Headcount distribution</span>
        </div>
        <canvas id="deptChart" height="200"></canvas>
      </div>
      <div class="chart-card">
        <div class="chart-header">
          <span class="chart-title">Status Breakdown</span>
          <span class="chart-sub">Active / Leave / Inactive</span>
        </div>
        <canvas id="statusChart" height="220"></canvas>
      </div>
    </section>

    <!-- BOTTOM ROW -->
    <section class="bottom-row">
      <div class="table-card">
        <div class="table-header">
          <span class="chart-title">Recent Employees</span>
          <a href="employees.php" class="view-all">View all →</a>
        </div>
        <div class="search-bar">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input type="text" id="tableSearch" placeholder="Search employees..." oninput="filterTable()"/>
        </div>
        <table class="emp-table" id="empTable">
          <thead><tr><th>Name</th><th>Department</th><th>Position</th><th>Status</th></tr></thead>
          <tbody id="empTableBody"><tr><td colspan="4" class="loading-row">Loading...</td></tr></tbody>
        </table>
      </div>

      <div class="activity-card">
        <div class="chart-header">
          <span class="chart-title">Activity Log</span>
          <span class="chart-sub">Recent admin actions</span>
        </div>
        <ul class="activity-list" id="activityList">
          <li class="loading-row">Loading...</li>
        </ul>
      </div>
    </section>

  </div>
</main>

<script src="admin.js"></script>
</body>
</html>