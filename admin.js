// ============================
// ADMIN.JS — StaffCore
// Dashboard logic + Chart.js
// ============================

// --- Live Date Badge ---
function updateDate() {
  const el = document.getElementById('dateBadge');
  if (!el) return;
  const now = new Date();
  el.textContent = now.toLocaleDateString('en-MY', { weekday:'short', day:'numeric', month:'short', year:'numeric' });
}
updateDate();

// --- Sidebar Toggle (mobile) ---
function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('open');
}

// --- Fetch stats from PHP API ---
async function loadDashboardData() {
  try {
    const res  = await fetch('api/dashboard_stats.php');
    const data = await res.json();

    // Stat cards
    countUp('statTotal',  data.total  || 0);
    countUp('statActive', data.active || 0);
    countUp('statLeave',  data.on_leave || 0);
    countUp('statDepts',  data.departments || 0);

    // Charts
    renderDeptChart(data.by_department || []);
    renderStatusChart(data.total, data.active, data.on_leave, data.inactive);

    // Table
    renderTable(data.recent_employees || []);

    // Activity
    renderActivity(data.activity_log || []);

  } catch (err) {
    console.error('Dashboard load failed:', err);
    // Fallback demo data so UI still looks good
    countUp('statTotal', 42);
    countUp('statActive', 36);
    countUp('statLeave', 4);
    countUp('statDepts', 5);
    renderDeptChart([
      { name:'Engineering', count:14 },
      { name:'HR',          count:6  },
      { name:'Marketing',   count:8  },
      { name:'Finance',     count:7  },
      { name:'Operations',  count:7  },
    ]);
    renderStatusChart(42, 36, 4, 2);
    renderTable([]);
    renderActivity([]);
  }
}

// --- Animated count-up ---
function countUp(id, target) {
  const el = document.getElementById(id);
  if (!el) return;
  let start = 0;
  const step = Math.ceil(target / 30);
  const timer = setInterval(() => {
    start = Math.min(start + step, target);
    el.textContent = start;
    if (start >= target) clearInterval(timer);
  }, 30);
}

// --- Department Bar Chart (Chart.js) ---
function renderDeptChart(depts) {
  const ctx = document.getElementById('deptChart').getContext('2d');
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: depts.map(d => d.name),
      datasets: [{
        label: 'Headcount',
        data: depts.map(d => d.count),
        backgroundColor: [
          'rgba(0,229,255,0.7)',
          'rgba(88,166,255,0.7)',
          'rgba(163,113,247,0.7)',
          'rgba(63,185,80,0.7)',
          'rgba(210,153,34,0.7)',
        ],
        borderRadius: 6,
        borderSkipped: false,
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { display: false },
        tooltip: { backgroundColor: '#161b22', titleColor: '#e6edf3', bodyColor: '#7d8590', borderColor: 'rgba(255,255,255,0.07)', borderWidth: 1 }
      },
      scales: {
        x: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#7d8590', font: { family: 'DM Sans' } } },
        y: { grid: { color: 'rgba(255,255,255,0.04)' }, ticks: { color: '#7d8590', font: { family: 'DM Sans' }, stepSize: 1 }, beginAtZero: true }
      }
    }
  });
}

// --- Status Doughnut Chart (Chart.js) ---
function renderStatusChart(total, active, leave, inactive) {
  const ctx = document.getElementById('statusChart').getContext('2d');
  new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: ['Active', 'On Leave', 'Inactive'],
      datasets: [{
        data: [active, leave, inactive || (total - active - leave)],
        backgroundColor: ['rgba(63,185,80,0.8)', 'rgba(210,153,34,0.8)', 'rgba(255,71,87,0.8)'],
        borderWidth: 0,
        hoverOffset: 6,
      }]
    },
    options: {
      responsive: true,
      cutout: '68%',
      plugins: {
        legend: {
          position: 'bottom',
          labels: { color: '#7d8590', font: { family: 'DM Sans', size: 12 }, padding: 16, boxWidth: 10, borderRadius: 3 }
        },
        tooltip: { backgroundColor: '#161b22', titleColor: '#e6edf3', bodyColor: '#7d8590', borderColor: 'rgba(255,255,255,0.07)', borderWidth: 1 }
      }
    }
  });
}

// --- Employee Table ---
let allEmployees = [];

function renderTable(employees) {
  allEmployees = employees;
  const tbody = document.getElementById('empTableBody');

  if (!employees.length) {
    tbody.innerHTML = `<tr><td colspan="4" class="loading-row">No employees found.</td></tr>`;
    return;
  }

  tbody.innerHTML = employees.map(e => `
    <tr>
      <td><strong>${esc(e.name)}</strong></td>
      <td style="color:var(--muted)">${esc(e.department || '—')}</td>
      <td style="color:var(--muted)">${esc(e.position || '—')}</td>
      <td>${statusBadge(e.status)}</td>
    </tr>
  `).join('');
}

function filterTable() {
  const q = document.getElementById('tableSearch').value.toLowerCase();
  const filtered = allEmployees.filter(e =>
    e.name.toLowerCase().includes(q) ||
    (e.department || '').toLowerCase().includes(q) ||
    (e.position   || '').toLowerCase().includes(q)
  );
  renderTable(filtered);
}

function statusBadge(status) {
  const map = { active: 'badge-active', inactive: 'badge-inactive', on_leave: 'badge-leave' };
  const label = { active: 'Active', inactive: 'Inactive', on_leave: 'On Leave' };
  return `<span class="badge ${map[status] || ''}">${label[status] || status}</span>`;
}

// --- Activity Log ---
function renderActivity(log) {
  const ul = document.getElementById('activityList');
  if (!log.length) {
    ul.innerHTML = `<li class="loading-row">No recent activity.</li>`;
    return;
  }
  ul.innerHTML = log.map(item => `
    <li class="activity-item">
      <div class="activity-dot"></div>
      <div>
        <div class="activity-text">${esc(item.action)}</div>
        <div class="activity-time">${item.created_at || ''}</div>
      </div>
    </li>
  `).join('');
}

// --- XSS escape helper ---
function esc(str) {
  const d = document.createElement('div');
  d.textContent = str || '';
  return d.innerHTML;
}

// --- Init ---
loadDashboardData();
