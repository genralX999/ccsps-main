<?php
require_once __DIR__ . '/../includes/init.php';
requireAuth();
$user = currentUser($pdo);
if ($user['role'] !== 'superadmin') { header('Location: /'); exit; }

// totals
$totalEncoders = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$totalMonitored = $pdo->query("SELECT COUNT(*) FROM monitored_information WHERE is_deleted = 0")->fetchColumn();
$latestActivityCount = $pdo->query("SELECT COUNT(*) FROM activity_logs")->fetchColumn();

// lists for selects
$usersForSelect = $pdo->query("SELECT id, monitor_id_code, username FROM users ORDER BY id DESC")->fetchAll();
$superadminCodes = $pdo->query("SELECT monitor_id_code FROM users WHERE role = 'superadmin'")->fetchAll(PDO::FETCH_COLUMN);
$regions = $pdo->query("SELECT id, name FROM regions ORDER BY name")->fetchAll();
$eventTypes = $pdo->query("SELECT id, name FROM event_types ORDER BY name")->fetchAll();

ob_start();
?>
<h1 class="text-2xl font-semibold mb-4" style="color:#025529">Superadmin Dashboard</h1>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
  <div class="bg-white p-4 rounded shadow">
    <div class="text-sm text-gray-500">Registered Encoders</div>
    <div class="text-3xl font-bold"><?= $totalEncoders ?></div>
  </div>
  <div class="bg-white p-4 rounded shadow">
    <div class="text-sm text-gray-500">Monitored Records</div>
    <div class="text-3xl font-bold"><?= $totalMonitored ?></div>
  </div>
  <div class="bg-white p-4 rounded shadow">
    <div class="text-sm text-gray-500">Activity Logs</div>
    <div class="text-3xl font-bold"><?= $latestActivityCount ?></div>
  </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
  <div class="bg-white p-4 rounded shadow">
    <h2 class="font-semibold mb-3">Encoded Data by Region</h2>
    <div class="h-48">
      <canvas id="regionDonut" style="max-height:100%;"></canvas>
    </div>
  </div>
  <div class="bg-white p-4 rounded shadow">
    <h2 class="font-semibold mb-3">Encoded Data by User</h2>
    <!-- user filter removed — chart shows all users (superadmin monitor IDs are excluded) -->
    <div class="flex flex-col md:flex-row items-start gap-4">
      <div class="md:w-1/2 h-48">
        <canvas id="userDonut" style="max-height:100%;"></canvas>
      </div>
      <div id="userDonutLegend" class="md:w-1/2 max-h-48 overflow-auto p-2"></div>
    </div>
  </div>
</div>

<div class="mt-6 bg-white p-4 rounded shadow">
  <h3 class="font-semibold mb-3">Monitored Records</h3>
  <div class="flex gap-3 mb-4">
    <select id="filterRegion" class="p-2 rounded border">
      <option value="">All regions</option>
      <?php foreach($regions as $r): ?>
      <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <select id="filterEventType" class="p-2 rounded border">
      <option value="">All event types</option>
      <?php foreach($eventTypes as $et): ?>
      <option value="<?= $et['id'] ?>"><?= htmlspecialchars($et['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <select id="filterUser" class="p-2 rounded border">
      <option value="">All users</option>
      <?php foreach($usersForSelect as $u): ?>
      <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['monitor_id_code']) ?></option>
      <?php endforeach; ?>
    </select>
    <button id="applyFilters" class="ml-auto px-4 py-2 rounded btn-brand text-white">Apply</button>

    <button id="exportAdminMonitoredXlsx" class="ml-2 px-4 py-2 rounded bg-blue-600 hover:bg-blue-700 text-white">Export XLSX</button>
  </div>
  <div id="monitoredList"></div>
</div>

<script>
async function fetchChart(type, params = {}) {
  const q = new URLSearchParams({...params, type});
  const res = await fetch('<?= dirname(baseUrl()) ?>/api/stats.php?' + q.toString());
  return res.json();
}
function createDonut(ctx, labels, data, extraOptions = {}) {
  const defaultOptions = { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } };
  const options = Object.assign({}, defaultOptions, extraOptions);
  return new Chart(ctx, {
    type: 'doughnut',
    data: { labels, datasets: [{ data }] },
    options
  });
}

function generateUserLegend(chart, containerId = 'userDonutLegend') {
  const container = document.getElementById(containerId);
  if (!container) return;

  const labels = chart.data.labels || [];
  const data = chart.data.datasets && chart.data.datasets[0] && chart.data.datasets[0].data ? chart.data.datasets[0].data : [];
  const colors = chart.data.datasets && chart.data.datasets[0] && chart.data.datasets[0].backgroundColor ? chart.data.datasets[0].backgroundColor : [];

  // build entries with original index, label, count and color
  const entries = labels.map((lab, i) => ({ index: i, label: lab, count: Number(data[i] || 0), color: colors[i] || '#ddd' }));

  // sort descending by count
  entries.sort((a, b) => b.count - a.count);

  // render
  container.innerHTML = '';
  if (entries.length === 0) {
    container.innerHTML = '<div class="text-sm text-gray-500">No data</div>';
    return;
  }

  const list = document.createElement('div');
  list.className = 'space-y-2';

  entries.forEach(e => {
    const row = document.createElement('div');
    row.className = 'flex items-center justify-between text-sm';

    const left = document.createElement('div');
    left.className = 'flex items-center gap-2';
    const swatch = document.createElement('span');
    swatch.style.background = e.color;
    swatch.style.width = '12px';
    swatch.style.height = '12px';
    swatch.style.display = 'inline-block';
    swatch.style.borderRadius = '3px';
    left.appendChild(swatch);
    const lbl = document.createElement('span');
    lbl.textContent = e.label;
    left.appendChild(lbl);

    const right = document.createElement('div');
    right.className = 'text-gray-700 font-medium';
    right.textContent = e.count + (e.count === 1 ? ' report' : ' reports');

    row.appendChild(left);
    row.appendChild(right);
    list.appendChild(row);
  });

  container.appendChild(list);
}
(async function(){
  const regionData = await fetchChart('region');
  createDonut(document.getElementById('regionDonut'), regionData.labels, regionData.data);

  const userData = await fetchChart('user');
  // filter out any superadmin monitor codes from labels and data
  const superadminCodes = <?= json_encode(array_values($superadminCodes)) ?>;
  const filtered = { labels: [], data: [] };
  userData.labels.forEach((lab, i) => {
    if (!superadminCodes.includes(lab)) {
      filtered.labels.push(lab);
      filtered.data.push(userData.data[i]);
    }
  });

  const userChart = createDonut(document.getElementById('userDonut'), filtered.labels, filtered.data, { plugins: { legend: { display: false } } });
  generateUserLegend(userChart);

  async function loadMonitored() {
    const r = document.getElementById('filterRegion').value;
    const et = document.getElementById('filterEventType').value;
    const u = document.getElementById('filterUser').value;
    const qs = new URLSearchParams({ region_id: r, event_type_id: et, user_id: u });
    const res = await fetch('<?= dirname(baseUrl()) ?>/api/monitored.php?' + qs.toString());
    const html = await res.text();
    document.getElementById('monitoredList').innerHTML = html;
  }
  document.getElementById('applyFilters').addEventListener('click', loadMonitored);
  loadMonitored();

  const csvBtn = document.getElementById('exportAdminMonitored');
  if (csvBtn) {
    csvBtn.addEventListener('click', () => {
      const r = document.getElementById('filterRegion').value;
      const et = document.getElementById('filterEventType').value;
      const u = document.getElementById('filterUser').value;
      const qs = new URLSearchParams({ region_id: r, event_type_id: et, user_id: u, export: 'csv' });
      window.location = '<?= dirname(baseUrl()) ?>/api/monitored.php?' + qs.toString();
    });
  }

  const exportAdminMonitoredXlsxBtn = document.getElementById('exportAdminMonitoredXlsx');
  if (exportAdminMonitoredXlsxBtn) {
    exportAdminMonitoredXlsxBtn.addEventListener('click', () => {
      const r = document.getElementById('filterRegion').value;
      const et = document.getElementById('filterEventType').value;
      const u = document.getElementById('filterUser').value;
      const qs = new URLSearchParams({ region_id: r, event_type_id: et, user_id: u });
      // disable button briefly to prevent duplicate navigation
      exportAdminMonitoredXlsxBtn.disabled = true;
      window.location = '<?= dirname(baseUrl()) ?>/api/export_monitored_xlsx.php?' + qs.toString();
      setTimeout(() => { exportAdminMonitoredXlsxBtn.disabled = false; }, 1500);
    });
  }

  // quick view modal for non-owners (delegated)
  document.getElementById('monitoredList').addEventListener('click', async (e) => {
    const modalBtn = e.target.closest('.open-modal');
    if (modalBtn) {
      e.preventDefault();
      const id = modalBtn.getAttribute('data-id');
      const res = await fetch('<?= dirname(baseUrl()) ?>/public/monitored_view.php?id=' + encodeURIComponent(id) + '&modal=1');
      const html = await res.text();
      if (window.showQuickViewModal) {
        await window.showQuickViewModal(html, id);
      } else if (window.showQuickViewModal === undefined) {
        // fallback to inline behavior
        const existing = document.getElementById('quickViewModal'); if (existing) existing.remove();
        const wrap = document.createElement('div');
        wrap.id = 'quickViewModal';
        wrap.className = 'fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40 p-4';
        wrap.innerHTML = `
          <div class="bg-white rounded-lg shadow-lg w-full max-w-xl max-h-[90vh] flex flex-col overflow-hidden">
            <div class="p-3 border-b bg-white z-10 flex justify-end" style="flex:0 0 auto;">
              <button id="closeModal" class="text-gray-600 px-3 py-1 rounded hover:bg-gray-100">Close</button>
            </div>
            <div id="quickViewContent" class="p-4 overflow-auto" style="flex:1 1 auto; max-height: calc(90vh - 56px);">${html}</div>
          </div>
        `;
        document.body.appendChild(wrap);
      }
      return;
    }
  });
  // reload list when quick-view delete occurs
  document.addEventListener('quickview:deleted', loadMonitored);
})();

</script>

<script>window.CECOE_BASE = '<?= baseUrl() ?>';</script>
<script src="<?= baseUrl() ?>/js/quickview.js"></script>

<?php
$slot = ob_get_clean();
include view_path('views/layout.php');
?>
