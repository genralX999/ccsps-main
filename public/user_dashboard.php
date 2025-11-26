<?php
require_once __DIR__ . '/../includes/init.php';
requireAuth();
$user = currentUser($pdo);
if ($user['role'] === 'superadmin') { header('Location: ' . baseUrl() . '/superadmin_dashboard.php'); exit; }
// load lists for filters
// regions, event types, and users
$regions = $pdo->query('SELECT id, name FROM regions ORDER BY name')->fetchAll();
$eventTypes = $pdo->query('SELECT id, name FROM event_types ORDER BY name')->fetchAll();
$usersForSelect = $pdo->query("SELECT id, monitor_id_code, username FROM users WHERE role = 'user' ORDER BY id DESC")->fetchAll();

ob_start();
?>
<h1 class="text-2xl font-semibold mb-4" style="color:#025529">User Dashboard</h1>
<div class="flex items-center justify-between mb-4">
		<p class="mb-0">Welcome, <?= htmlspecialchars($user['username']) ?></p>
		<a href="<?= baseUrl() ?>/submit_monitored.php" class="px-3 py-2 btn-brand text-white rounded">Submit New Report</a>
</div>

<!-- Charts: placed at page level (not inside filters card) -->
<div class="flex items-center justify-between mb-3">
		<div class="text-sm text-gray-600">Charts</div>
		<div class="flex items-center gap-3">
			<label class="inline-flex items-center text-sm"><input id="toggleLegend" type="checkbox" class="mr-2" />Show legend</label>
		</div>
</div>
<div id="chartsGrid" class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
	<div class="bg-white p-4 rounded shadow">
		<h3 class="font-semibold mb-3">Reports by Event Type</h3>
		<div class="h-48 flex items-center justify-center">
			<div style="width:100%;max-width:520px;height:192px;">
				<canvas id="eventTypeDonut" style="width:100%;height:100%;"></canvas>
			</div>
		</div>
	</div>
	<div class="bg-white p-4 rounded shadow">
		<h3 class="font-semibold mb-3">Encoded Data by Region</h3>
		<div class="h-48 flex items-center justify-center">
			<div style="width:100%;max-width:520px;height:192px;">
				<canvas id="regionDonut" style="width:100%;height:100%;"></canvas>
			</div>
		</div>
	</div>
	<div class="bg-white p-4 rounded shadow">
		<h3 class="font-semibold mb-3">Encoded Data by User</h3>
		<div class="h-48 flex items-center justify-center">
			<div style="width:100%;max-width:520px;height:192px;">
				<canvas id="userDonut" style="width:100%;height:100%;"></canvas>
			</div>
		</div>
	</div>
	<div class="bg-white p-4 rounded shadow">
		<h3 class="font-semibold mb-3">Encoded Data by Rating</h3>
		<div class="h-48 flex items-center justify-center">
			<div style="width:100%;max-width:520px;height:192px;">
				<canvas id="ratingDonut" style="width:100%;height:100%;"></canvas>
			</div>
		</div>
	</div>
</div>

<div class="bg-white p-4 rounded shadow mb-4">
	<h2 class="font-semibold mb-3">Filters</h2>
	<!-- total submissions removed (not needed) -->
	<div class="mb-3 grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-3 items-end">
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
					<option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['monitor_id_code'].' - '.$u['username']) ?></option>
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
			<button id="applyFilters" class="px-4 py-2 rounded btn-brand text-white text-sm">Apply filters</button>
			<button id="clearFilters" class="px-4 py-2 rounded border text-sm">Clear</button>
		</div>
	</div>
	<div class="flex items-center justify-end gap-2">
		<button id="exportMonitoredBtn" class="px-3 py-1 rounded bg-green-700 hover:bg-green-800 text-white text-sm">Export CSV</button>
		<button id="exportXlsxBtn" class="ml-2 px-3 py-1 rounded bg-blue-600 hover:bg-blue-700 text-white text-sm">Export XLSX</button>
	</div>
</div>

<div id="monitoredTableCard" class="bg-white p-4 rounded shadow">
	<h3 class="font-semibold mb-3">Monitored Records</h3>
	<div id="monitoredTable"></div>
</div>

<script src="<?= dirname(baseUrl()) ?>/public/js/ui-charts.js"></script>



<script>
// toggle behavior for legend rendering
const toggleLegend = document.getElementById('toggleLegend');
	if (toggleLegend) {
	toggleLegend.addEventListener('change', (e) => {
		window.dashboardShowLegend = !!e.target.checked;
		try { localStorage.setItem('ccsps_dashboard_show_legend', window.dashboardShowLegend ? '1' : '0'); } catch (e) {}
		// reload page so charts render with consistent options
		window.location.reload();
	});
}
</script>

	<script>
	// load monitored entries table for this user (full table under chart)
	const tableContainer = document.getElementById('monitoredTable');

	async function fetchTable(page = 1) {
		const region = document.getElementById('filterRegion') ? document.getElementById('filterRegion').value : '';
	    const et = document.getElementById('filterEventType') ? document.getElementById('filterEventType').value : '';
	    const uid = document.getElementById('filterUser') ? document.getElementById('filterUser').value : '';
		const dateFrom = document.getElementById('filterDateFrom') ? document.getElementById('filterDateFrom').value : '';
		const dateTo = document.getElementById('filterDateTo') ? document.getElementById('filterDateTo').value : '';
	    let qs = new URLSearchParams({ page });
	    if (region) qs.set('region_id', region);
	    if (et) qs.set('event_type_id', et);
		if (dateFrom) qs.set('date_from', dateFrom);
		if (dateTo) qs.set('date_to', dateTo);
		// user filter: only include when explicitly selected
		if (uid) qs.set('user_id', uid);
	    const url = '<?= dirname(baseUrl()) ?>/api/monitored.php?' + qs.toString();
	    const res = await fetch(url);
	    const html = await res.text();
	    tableContainer.innerHTML = html;
	    const hasRow = tableContainer.querySelector('tbody tr');
	    if (!hasRow) {
	        tableContainer.innerHTML = '<div class="text-sm text-gray-600">No submissions yet.</div>';
	    }
	}

	// delegate pagination and read-more handling
	document.getElementById('monitoredTable').addEventListener('click', async (e) => {
	    const btn = e.target.closest('.pagination-btn');
	    if (btn) {
	        e.preventDefault();
	        const page = btn.getAttribute('data-page');
	        await fetchTable(page);
	        return;
	    }
	    const rm = e.target.closest('.read-more');
	    if (rm) {
	        e.preventDefault();
	        const cell = rm.closest('td');
	        const short = cell.querySelector('.note-short');
	        const full = cell.querySelector('.note-full');
	        if (full.classList.contains('hidden')) {
	            full.classList.remove('hidden');
	            short.classList.add('hidden');
	            rm.textContent = 'Show less';
	        } else {
	            full.classList.add('hidden');
	            short.classList.remove('hidden');
	            rm.textContent = 'Read more';
	        }
	        return;
	    }
	});

	// quick view modal for non-owners/owners (delegates to shared helper)
	async function showModal(html, id) {
	    if (window.showQuickViewModal) return window.showQuickViewModal(html, id);
	    // fallback: simple modal if helper not yet loaded
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
	    const prevOverflow = document.body.style.overflow; document.body.style.overflow = 'hidden';
	    const close = () => { if (wrap && wrap.parentNode) wrap.parentNode.removeChild(wrap); document.body.style.overflow = prevOverflow || ''; };
	    const btn = document.getElementById('closeModal'); if (btn) btn.addEventListener('click', close);
	    wrap.addEventListener('click', (ev) => { if (ev.target === wrap) close(); });
	}

	document.getElementById('monitoredTable').addEventListener('click', async (e) => {
	    const modalBtn = e.target.closest('.open-modal');
	    if (modalBtn) {
	        e.preventDefault();
	        const id = modalBtn.getAttribute('data-id');
	        const res = await fetch('<?= dirname(baseUrl()) ?>/public/monitored_view.php?id=' + encodeURIComponent(id) + '&modal=1');
	        const html = await res.text();
	        await showModal(html, id);
	        return;
	    }
	});

	document.getElementById('applyFilters').addEventListener('click', async () => { await fetchTable(1); });

	// Clear filters button resets inputs and reloads table
	const clearBtn = document.getElementById('clearFilters');
	if (clearBtn) {
		clearBtn.addEventListener('click', (e) => {
			e.preventDefault();
			const f = document.getElementById('filterRegion'); if (f) f.value = '';
			const fe = document.getElementById('filterEventType'); if (fe) fe.value = '';
			const fu = document.getElementById('filterUser'); if (fu) fu.value = '';
			const df = document.getElementById('filterDateFrom'); if (df) df.value = '';
			const dt = document.getElementById('filterDateTo'); if (dt) dt.value = '';
			fetchTable(1);
		});
	}

	// export CSV
	document.getElementById('exportMonitoredBtn').addEventListener('click', async () => {
		const region = document.getElementById('filterRegion') ? document.getElementById('filterRegion').value : '';
		const et = document.getElementById('filterEventType') ? document.getElementById('filterEventType').value : '';
		const uid = document.getElementById('filterUser') ? document.getElementById('filterUser').value : '';
		const dateFrom = document.getElementById('filterDateFrom') ? document.getElementById('filterDateFrom').value : '';
		const dateTo = document.getElementById('filterDateTo') ? document.getElementById('filterDateTo').value : '';
		let url = '<?= dirname(baseUrl()) ?>/api/monitored.php?export=csv';
		if (region) url += '&region_id=' + encodeURIComponent(region);
		if (et) url += '&event_type_id=' + encodeURIComponent(et);
		if (uid) url += '&user_id=' + encodeURIComponent(uid);
		if (dateFrom) url += '&date_from=' + encodeURIComponent(dateFrom);
		if (dateTo) url += '&date_to=' + encodeURIComponent(dateTo);
		// navigate to url to trigger download
		window.location = url;
	});

	// export XLSX
	const exportXlsxBtn = document.getElementById('exportXlsxBtn');
	if (exportXlsxBtn) {
	    exportXlsxBtn.addEventListener('click', async () => {
			const region = document.getElementById('filterRegion') ? document.getElementById('filterRegion').value : '';
			const et = document.getElementById('filterEventType') ? document.getElementById('filterEventType').value : '';
			const uid = document.getElementById('filterUser') ? document.getElementById('filterUser').value : '';
			const dateFrom = document.getElementById('filterDateFrom') ? document.getElementById('filterDateFrom').value : '';
			const dateTo = document.getElementById('filterDateTo') ? document.getElementById('filterDateTo').value : '';
			let url = '<?= dirname(baseUrl()) ?>/api/export_monitored_xlsx.php?';
			const qs = new URLSearchParams();
			if (region) qs.set('region_id', region);
			if (et) qs.set('event_type_id', et);
			if (uid) qs.set('user_id', uid);
			if (dateFrom) qs.set('date_from', dateFrom);
			if (dateTo) qs.set('date_to', dateTo);
			url += qs.toString();
			// prevent double navigation by disabling briefly
			exportXlsxBtn.disabled = true;
			window.location = url;
			setTimeout(() => { exportXlsxBtn.disabled = false; }, 1500);
	    });
	}

	// initial load
	// reload table when a quick-view delete occurs
	document.addEventListener('quickview:deleted', async () => { await fetchTable(1); });
	fetchTable(1).catch(err => console.error('fetchTable init failed', err));
	</script>

<script>window.CECOE_BASE = '<?= baseUrl() ?>';</script>
<script src="<?= baseUrl() ?>/js/quickview.js"></script>


<?php
$slot = ob_get_clean();
include view_path('views/layout.php');
?>
