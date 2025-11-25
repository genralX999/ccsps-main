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
    <h2 class="font-semibold mb-3">Encoded Data by User</h2>
    <!-- user filter removed — chart shows all users (superadmin monitor IDs are excluded) -->
    <div class="h-48 flex items-center justify-center">
      <div style="width:100%;max-width:520px;height:192px;">
        <canvas id="userDonut" style="width:100%;height:100%;"></canvas>
      </div>
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
  const defaultOptions = { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } };
  const options = Object.assign({}, defaultOptions, extraOptions);
  return new Chart(ctx, {
    type: 'doughnut',
    data: { labels, datasets: [{ data }] },
    options,
    plugins: [labelsPlugin]
  });
}
  // plugin to draw labels outside each arc with value and percentage
  const labelsPlugin = {
    id: 'labelsOutside',
    afterDraw: (chart) => {
      const ctx = chart.ctx;
      const data = chart.data;
      const meta = chart.getDatasetMeta(0);
      const total = (data.datasets && data.datasets[0] && data.datasets[0].data) ? data.datasets[0].data.reduce((s, v) => s + Number(v || 0), 0) : 0;
      ctx.save();
      const padding = 8;
      const topBound = (chart.chartArea && chart.chartArea.top != null) ? (chart.chartArea.top + padding) : padding;
      const bottomBound = (chart.chartArea && chart.chartArea.bottom != null) ? (chart.chartArea.bottom - padding) : (chart.canvas.height - padding);
      // First pass: compute desired label positions
      const baseOffset = 18;
      const minDist = 14; // minimum vertical distance between labels
      const items = [];
      meta.data.forEach((arc, i) => {
        if (!arc) return;
        const start = arc.startAngle;
        const end = arc.endAngle;
        const mid = (start + end) / 2;
        const outer = arc.outerRadius || 0;
        const lineStartX = arc.x + Math.cos(mid) * outer;
        const lineStartY = arc.y + Math.sin(mid) * outer;
        const desiredX = arc.x + Math.cos(mid) * (outer + baseOffset);
        const desiredY = arc.y + Math.sin(mid) * (outer + baseOffset);
        items.push({ arc, i, mid, outer, lineStartX, lineStartY, desiredX, desiredY });
      });
      // sort by desiredY to resolve vertical collisions
      items.sort((a,b) => a.desiredY - b.desiredY);
      // forward pass: ensure spacing from top
      for (let k = 0; k < items.length; k++) {
        const it = items[k];
        it.drawY = Math.max(it.desiredY, topBound);
        if (k > 0) {
          const prev = items[k-1];
          if (it.drawY - prev.drawY < minDist) {
            it.drawY = prev.drawY + minDist;
          }
        }
      }
      // backward pass: ensure spacing from bottom
      for (let k = items.length - 1; k >= 0; k--) {
        const it = items[k];
        if (it.drawY > bottomBound) it.drawY = bottomBound;
        if (k < items.length - 1) {
          const next = items[k+1];
          if (next.drawY - it.drawY < minDist) {
            it.drawY = next.drawY - minDist;
          }
        }
      }
      // final clamp to topBound
      const shiftDown = topBound - (items[0] ? items[0].drawY : topBound);
      if (shiftDown > 0) {
        for (let k = 0; k < items.length; k++) items[k].drawY += shiftDown;
      }
      // draw connectors and labels in original order
      items.sort((a,b) => a.i - b.i);
      items.forEach(it => {
        const arc = it.arc; const i = it.i;
        const labelX = it.desiredX;
        const labelY = Math.max(topBound, Math.min(bottomBound, it.drawY));
        ctx.strokeStyle = 'rgba(0,0,0,0.25)'; ctx.lineWidth = 1; ctx.beginPath(); ctx.moveTo(it.lineStartX, it.lineStartY); ctx.lineTo(labelX, labelY); ctx.stroke();
        const value = (data.datasets[0].data[i] == null) ? 0 : data.datasets[0].data[i];
        const pct = total ? Math.round((Number(value) / total) * 100) : 0;
        const text = `${data.labels[i]}: ${value} (${pct}%)`;
        ctx.fillStyle = '#222'; ctx.font = '12px sans-serif';
        ctx.textAlign = (Math.cos(it.mid) >= 0) ? 'left' : 'right'; ctx.textBaseline = 'middle';
        const tx = (Math.cos(it.mid) >= 0) ? labelX + 6 : labelX - 6;
        ctx.fillText(text, tx, labelY);
      });
      ctx.restore();
    }
  };

// side legend removed — labels drawn on-chart by plugin
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

  // pagination and read-more handling inside the monitored list
  document.getElementById('monitoredList').addEventListener('click', async (e) => {
    const btn = e.target.closest('.pagination-btn');
    if (btn) {
      e.preventDefault();
      const page = btn.getAttribute('data-page');
      const r = document.getElementById('filterRegion').value;
      const et = document.getElementById('filterEventType').value;
      const u = document.getElementById('filterUser').value;
      const qs = new URLSearchParams({ region_id: r, event_type_id: et, user_id: u, page });
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
