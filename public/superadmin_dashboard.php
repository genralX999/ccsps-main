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
$usersForSelect = $pdo->query("SELECT id, monitor_id_code, username FROM users WHERE role = 'user' ORDER BY id DESC")->fetchAll();
$superadminCodes = $pdo->query("SELECT monitor_id_code FROM users WHERE role = 'superadmin'")->fetchAll(PDO::FETCH_COLUMN);
$regions = $pdo->query("SELECT id, name FROM regions ORDER BY name")->fetchAll();
$eventTypes = $pdo->query("SELECT id, name FROM event_types ORDER BY name")->fetchAll();

ob_start();
?>
<h1 class="text-2xl font-semibold mb-4" style="color:#025529">Superadmin Dashboard</h1>
<div class="flex items-center justify-end mb-3">
  <label class="inline-flex items-center text-sm"><input id="toggleLegend" type="checkbox" class="mr-2" />Show legend on charts</label>
</div>

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
    <div class="h-48 flex items-center justify-center">
      <div style="width:100%;max-width:520px;height:192px;">
        <canvas id="regionDonut" style="width:100%;height:100%;"></canvas>
      </div>
    </div>
  </div>
  <div class="bg-white p-4 rounded shadow">
    <h2 class="font-semibold mb-3">Encoded Data by Event Type</h2>
    <div class="h-48 flex items-center justify-center">
      <div style="width:100%;max-width:520px;height:192px;">
        <canvas id="eventTypeDonut" style="width:100%;height:100%;"></canvas>
      </div>
    </div>
  </div>
  <div class="bg-white p-4 rounded shadow">
    <h2 class="font-semibold mb-3">Encoded Data by User</h2>
    <div class="h-48 flex items-center justify-center">
      <div style="width:100%;max-width:520px;height:192px;">
        <canvas id="userDonut" style="width:100%;height:100%;"></canvas>
      </div>
    </div>
  </div>
  <div class="bg-white p-4 rounded shadow">
    <h2 class="font-semibold mb-3">Encoded Data by Rating</h2>
    <div class="h-48 flex items-center justify-center">
      <div style="width:100%;max-width:520px;height:192px;">
        <canvas id="ratingDonut" style="width:100%;height:100%;"></canvas>
      </div>
    </div>
  </div>
</div>

<div class="mt-6 bg-white p-4 rounded shadow">
  <h3 class="font-semibold mb-3">Monitored Records</h3>
  <div class="flex gap-3 mb-4">
    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-3 w-full items-end">
      <div>
        <label for="filterRegion" class="text-sm text-gray-600 block mb-1">Region</label>
        <select id="filterRegion" class="p-2 rounded border w-full">
          <option value="">All regions</option>
          <?php foreach($regions as $r): ?>
          <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label for="filterEventType" class="text-sm text-gray-600 block mb-1">Event type</label>
        <select id="filterEventType" class="p-2 rounded border w-full">
          <option value="">All event types</option>
          <?php foreach($eventTypes as $et): ?>
          <option value="<?= $et['id'] ?>"><?= htmlspecialchars($et['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label for="filterUser" class="text-sm text-gray-600 block mb-1">User</label>
        <select id="filterUser" class="p-2 rounded border w-full">
          <option value="">All users</option>
          <?php foreach($usersForSelect as $u): ?>
          <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['monitor_id_code']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label for="filterDateFrom" class="text-sm text-gray-600 block mb-1">From (date)</label>
        <input id="filterDateFrom" type="date" class="p-2 rounded border w-full" />
      </div>
      <div>
        <label for="filterDateTo" class="text-sm text-gray-600 block mb-1">To (date)</label>
        <input id="filterDateTo" type="date" class="p-2 rounded border w-full" />
      </div>
      <div class="flex items-center justify-end gap-2">
        <button id="applyFilters" class="px-4 py-2 rounded btn-brand text-white">Apply</button>
        <button id="clearFilters" class="px-4 py-2 rounded border">Clear</button>
      </div>
    </div>

    <button id="exportAdminMonitoredXlsx" class="ml-2 px-4 py-2 rounded bg-blue-600 hover:bg-blue-700 text-white">Export XLSX</button>
  </div>
  <div id="monitoredList"></div>
</div>
<script src="<?= dirname(baseUrl()) ?>/public/js/ui-charts.js"></script>

<script>
async function fetchChart(type, params = {}) {
  const q = new URLSearchParams({...params, type});
  const res = await fetch('<?= dirname(baseUrl()) ?>/api/stats.php?' + q.toString());
  return res.json();
}
// page-level palette helper: prefer shared `window.getChartColors` when available
function pageGenerateColors(n, sat=62, light=56, hueOffset=0) {
  if (window.getChartColors) return window.getChartColors(n, sat, light, hueOffset);
  if (!n || n <= 0) return ['hsl(200,60%,60%)'];
  return Array.from({length: n}, (_, i) => `hsl(${Math.round((i * 360 / n) + hueOffset) % 360}, ${sat}%, ${light}%)`);
}
// Prefer the shared createDonut helper from ui-charts.js; fallback to local creation
function localCreateDonut(ctx, labels, data, extraOptions = {}) {
  try {
    const filteredLabels = [];
    const filteredData = [];
    const force = extraOptions && extraOptions.forceRenderZeros;
    for (let i = 0; i < (data || []).length; i++) {
      const v = Number(data[i] || 0);
      const lab = labels && labels[i] ? String(labels[i]) : '';
      if (!force) {
        if (v === 0 || lab === '') continue;
      } else {
        if (lab === '') continue;
      }
      filteredLabels.push(lab);
      filteredData.push(v);
    }
    if (!filteredLabels.length) return null;
    const defaultOptions = { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: true, position: 'right' } } };
    const options = Object.assign({}, defaultOptions, extraOptions);
    const colors = (extraOptions && extraOptions.colors) ? extraOptions.colors : (window.getChartColors ? window.getChartColors(filteredLabels.length) : undefined);
    return new Chart(ctx, {
      type: 'doughnut',
      data: { labels: filteredLabels, datasets: [{ data: filteredData, backgroundColor: colors, borderColor: '#ffffff', borderWidth: 1 }] },
      options
    });
  } catch (e) {
    console.error('localCreateDonut error', e);
    return null;
  }
}
  // charts use legend only (labelsOutside plugin removed)

// side legend is provided via Chart.js legend
// wrap dashboard logic to catch errors and report visibly
// read persisted legend preference
window.dashboardShowLegend = (localStorage.getItem('ccsps_dashboard_show_legend') === '1');
const saToggleEl = document.getElementById('toggleLegend'); if (saToggleEl) saToggleEl.checked = !!window.dashboardShowLegend;
async function renderSuperadminCharts(){
  try {
    // load and render 4 charts: region, event type, user (encoders), rating
      const regionData = await fetchChart('region');
    try {
      console.debug('regionData', regionData);
      const regionColors = pageGenerateColors((regionData && regionData.labels ? regionData.labels.length : 0) || 1);
      const c = (window.createDonut || localCreateDonut)(document.getElementById('regionDonut'), regionData.labels, regionData.data, Object.assign({ colors: regionColors }, window.dashboardShowLegend ? { plugins: { legend: { display: true, position: 'right' } } } : {}));
      if (!c) {
        const card = document.getElementById('regionDonut').closest('.bg-white');
        if (card) { const canvasEl = card.querySelector('canvas'); if (canvasEl) canvasEl.remove(); card.insertAdjacentHTML('beforeend', '<div class="mt-3 text-sm text-gray-500">No data available.</div>'); }
      }
    } catch(e){ console.error('region chart failed', e); }

    const eventTypeData = await fetchChart('event_type');
    // generate colors for event type chart
    const eventColors = pageGenerateColors((eventTypeData && eventTypeData.labels && eventTypeData.labels.length) || 1);
    try {
      console.debug('eventTypeData', eventTypeData);
      const c2 = (window.createDonut || localCreateDonut)(document.getElementById('eventTypeDonut'), eventTypeData.labels, eventTypeData.data, Object.assign({ colors: eventColors }, window.dashboardShowLegend ? { plugins: { legend: { display: true, position: 'right' } } } : {}));
      if (!c2) { const card = document.getElementById('eventTypeDonut').closest('.bg-white'); if (card) { const canvasEl = card.querySelector('canvas'); if (canvasEl) canvasEl.remove(); card.insertAdjacentHTML('beforeend', '<div class="mt-3 text-sm text-gray-500">No data available.</div>'); } }
    } catch(e){ console.error('eventType chart failed', e); }

    const userData = await fetchChart('user');
    // filter out any superadmin monitor codes from labels and data
    const superadminCodes = <?= json_encode(array_values($superadminCodes)) ?>;
    const filtered = { labels: [], data: [] };
    (userData && userData.labels || []).forEach((lab, i) => {
      if (!superadminCodes.includes(lab) && (userData.data && Number(userData.data[i] || 0) > 0)) {
        filtered.labels.push(lab);
        filtered.data.push(userData.data[i]);
      }
    });
    try {
      console.debug('userData(filtered)', filtered);
      const userColors = pageGenerateColors((filtered && filtered.labels ? filtered.labels.length : 0) || 1);
      const cu = (window.createDonut || localCreateDonut)(document.getElementById('userDonut'), filtered.labels, filtered.data, Object.assign({ colors: userColors }, window.dashboardShowLegend ? { plugins: { legend: { display: true, position: 'right' } } } : {}));
      if (!cu) { const card = document.getElementById('userDonut').closest('.bg-white'); if (card) { const canvasEl = card.querySelector('canvas'); if (canvasEl) canvasEl.remove(); card.insertAdjacentHTML('beforeend', '<div class="mt-3 text-sm text-gray-500">No data available.</div>'); } }
    } catch(e){ console.error('user chart failed', e); }

    try {
      const ratingData = await fetchChart('rating');
      console.debug('ratingData', ratingData);
      const ratingColors = pageGenerateColors((ratingData && ratingData.labels ? ratingData.labels.length : 0) || 1, 56, 48);
      const cr = (window.createDonut || localCreateDonut)(document.getElementById('ratingDonut'), ratingData.labels, ratingData.data, Object.assign({ colors: ratingColors }, window.dashboardShowLegend ? { plugins: { legend: { display: true, position: 'right' } } } : {}));
      if (!cr) { const card = document.getElementById('ratingDonut').closest('.bg-white'); if (card) { const canvasEl = card.querySelector('canvas'); if (canvasEl) canvasEl.remove(); card.insertAdjacentHTML('beforeend', '<div class="mt-3 text-sm text-gray-500">No data available.</div>'); } }
    } catch(e){ console.error('rating chart failed', e); }
    } catch (err) {
    console.error('Dashboard init error', err);
    if (window.showToast) showToast('Dashboard error: ' + (err && err.message ? err.message : String(err)), 'error', 6000);
  }

    // end charts
}

// initial render
renderSuperadminCharts();

// wire legend toggle
const saToggle = document.getElementById('toggleLegend');
  if (saToggle) {
  saToggle.addEventListener('change', (e) => {
    window.dashboardShowLegend = !!e.target.checked;
    try { localStorage.setItem('ccsps_dashboard_show_legend', window.dashboardShowLegend ? '1' : '0'); } catch (err) {}
    // reload page to ensure plugin state is consistent
    window.location.reload();
  });
}

async function loadMonitored() {
    const r = document.getElementById('filterRegion') ? document.getElementById('filterRegion').value : '';
    const et = document.getElementById('filterEventType') ? document.getElementById('filterEventType').value : '';
    const u = document.getElementById('filterUser') ? document.getElementById('filterUser').value : '';
    const dateFrom = document.getElementById('filterDateFrom') ? document.getElementById('filterDateFrom').value : '';
    const dateTo = document.getElementById('filterDateTo') ? document.getElementById('filterDateTo').value : '';
    const qs = new URLSearchParams();
    if (r) qs.set('region_id', r);
    if (et) qs.set('event_type_id', et);
    if (u) qs.set('user_id', u);
    if (dateFrom) qs.set('date_from', dateFrom);
    if (dateTo) qs.set('date_to', dateTo);
    const res = await fetch('<?= dirname(baseUrl()) ?>/api/monitored.php?' + qs.toString());
    if (!res.ok) {
      document.getElementById('monitoredList').innerHTML = '<div class="text-sm text-red-600">Failed to load monitored records.</div>';
      return;
    }
    const html = await res.text();
    if (!html || html.trim().length === 0) {
      document.getElementById('monitoredList').innerHTML = '<div class="text-sm text-gray-600">No monitored records.</div>';
    } else {
      document.getElementById('monitoredList').innerHTML = html;
    }
  }
  document.getElementById('applyFilters').addEventListener('click', loadMonitored);
  loadMonitored();

  // pagination and read-more handling inside the monitored list
  document.getElementById('monitoredList').addEventListener('click', async (e) => {
    const btn = e.target.closest('.pagination-btn');
    if (btn) {
      e.preventDefault();
      const page = btn.getAttribute('data-page');
      const r = document.getElementById('filterRegion') ? document.getElementById('filterRegion').value : '';
      const et = document.getElementById('filterEventType') ? document.getElementById('filterEventType').value : '';
      const u = document.getElementById('filterUser') ? document.getElementById('filterUser').value : '';
      const dateFrom = document.getElementById('filterDateFrom') ? document.getElementById('filterDateFrom').value : '';
      const dateTo = document.getElementById('filterDateTo') ? document.getElementById('filterDateTo').value : '';
      const qs = new URLSearchParams();
      if (r) qs.set('region_id', r);
      if (et) qs.set('event_type_id', et);
      if (u) qs.set('user_id', u);
      if (dateFrom) qs.set('date_from', dateFrom);
      if (dateTo) qs.set('date_to', dateTo);
      qs.set('page', page);
      const res = await fetch('<?= dirname(baseUrl()) ?>/api/monitored.php?' + qs.toString());
      const html = await res.text();
      document.getElementById('monitoredList').innerHTML = html;
      return;
    }
    const rm = e.target.closest('.read-more');
    if (rm) {
      e.preventDefault();
      const cell = rm.closest('td');
      const short = cell.querySelector('.note-short');
      const full = cell.querySelector('.note-full');
      if (short && full) { short.classList.toggle('hidden'); full.classList.toggle('hidden'); rm.textContent = rm.textContent === 'Read more' ? 'Show less' : 'Read more'; }
    }
  });

  const csvBtn = document.getElementById('exportAdminMonitored');
  if (csvBtn) {
    csvBtn.addEventListener('click', () => {
      const r = document.getElementById('filterRegion') ? document.getElementById('filterRegion').value : '';
      const et = document.getElementById('filterEventType') ? document.getElementById('filterEventType').value : '';
      const u = document.getElementById('filterUser') ? document.getElementById('filterUser').value : '';
      const dateFrom = document.getElementById('filterDateFrom') ? document.getElementById('filterDateFrom').value : '';
      const dateTo = document.getElementById('filterDateTo') ? document.getElementById('filterDateTo').value : '';
      const qs = new URLSearchParams();
      if (r) qs.set('region_id', r);
      if (et) qs.set('event_type_id', et);
      if (u) qs.set('user_id', u);
      if (dateFrom) qs.set('date_from', dateFrom);
      if (dateTo) qs.set('date_to', dateTo);
      qs.set('export', 'csv');
      window.location = '<?= dirname(baseUrl()) ?>/api/monitored.php?' + qs.toString();
    });
  }

  const exportAdminMonitoredXlsxBtn = document.getElementById('exportAdminMonitoredXlsx');
  if (exportAdminMonitoredXlsxBtn) {
    exportAdminMonitoredXlsxBtn.addEventListener('click', () => {
      const r = document.getElementById('filterRegion') ? document.getElementById('filterRegion').value : '';
      const et = document.getElementById('filterEventType') ? document.getElementById('filterEventType').value : '';
      const u = document.getElementById('filterUser') ? document.getElementById('filterUser').value : '';
      const dateFrom = document.getElementById('filterDateFrom') ? document.getElementById('filterDateFrom').value : '';
      const dateTo = document.getElementById('filterDateTo') ? document.getElementById('filterDateTo').value : '';
      const qs = new URLSearchParams();
      if (r) qs.set('region_id', r);
      if (et) qs.set('event_type_id', et);
      if (u) qs.set('user_id', u);
      if (dateFrom) qs.set('date_from', dateFrom);
      if (dateTo) qs.set('date_to', dateTo);
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

</script>

<script>window.CECOE_BASE = '<?= baseUrl() ?>';</script>
<script src="<?= baseUrl() ?>/js/quickview.js"></script>

<?php
$slot = ob_get_clean();
include view_path('views/layout.php');
?>
