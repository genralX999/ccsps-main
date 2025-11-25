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

<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
		<div class="bg-white p-4 rounded shadow">
				<h2 class="font-semibold mb-3">Reports by Event Type (All Monitors)</h2>
					<div class="mb-3">
					<span class="text-sm text-gray-600">Total submissions:</span>
					<span id="totalSubmissions" class="ml-2 font-bold">0</span>
						<?php foreach($eventTypes as $et): ?>
							<option value="<?= $et['id'] ?>"><?= htmlspecialchars($et['name']) ?></option>
						<?php endforeach; ?>
					</select>
					<select id="filterUser" class="p-2 rounded border">
						<option value="">All users</option>
						<?php foreach($usersForSelect as $u): ?>
							<option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['monitor_id_code'].' - '.$u['username']) ?></option>
						<?php endforeach; ?>
					</select>
					<button id="applyFilters" class="ml-auto px-3 py-1 rounded btn-brand text-white text-sm">Apply</button>
				</div>
				<div class="flex items-center justify-end gap-2 mb-2">
					<button id="exportMonitoredBtn" class="px-3 py-1 rounded bg-green-700 hover:bg-green-800 text-white text-sm"></button>
					<button id="exportXlsxBtn" class="ml-2 px-3 py-1 rounded bg-blue-600 hover:bg-blue-700 text-white text-sm">Export XLSX</button>
				</div>
				<div id="monitoredTable"></div>
		</div>

<script src="<?= dirname(baseUrl()) ?>/public/js/ui-charts.js"></script>

<script>
async function fetchChart(type, params = {}) {
	const q = new URLSearchParams({...params, type});
	const res = await fetch('<?= dirname(baseUrl()) ?>/api/stats.php?' + q.toString());
	return res.json();
}

(async function initDashboard(){
	try {
		// Event type (all monitors)
		const eventTypeData = await fetchChart('event_type', { exclude_superadmin: 1 });
		console.debug('eventTypeData', eventTypeData);
		if (eventTypeData && eventTypeData.labels && eventTypeData.data) {
			const total = eventTypeData.data.reduce((s, v) => s + Number(v || 0), 0);
			document.getElementById('totalSubmissions').textContent = total;

			function generateColors(n, sat=62, light=56) {
				return Array.from({length: n}, (_, i) => `hsl(${Math.round(i * 360 / n)}, ${sat}%, ${light}%)`);
			}
			const eventColors = generateColors(eventTypeData.labels.length || 1);
			const ce = (window.createDonut || function(){ return null; })(document.getElementById('eventTypeDonut'), eventTypeData.labels, eventTypeData.data, { colors: eventColors });
			if (!ce) document.getElementById('eventTypeDonut').closest('.bg-white').insertAdjacentHTML('beforeend', '<div class="mt-3 text-sm text-gray-500">No data available.</div>');
		} else {
			document.getElementById('totalSubmissions').textContent = '0';
			document.getElementById('eventTypeDonut').closest('.bg-white').insertAdjacentHTML('beforeend', '<div class="mt-3 text-sm text-gray-500">No data available.</div>');
		}

		// User donut (overall users)
		const userData = await fetchChart('user');
		console.debug('userData', userData);
		function colorForString(s, sat=48, light=50, hueOffset=180) {
			let h = 0;
			for (let i = 0; i < s.length; i++) h = (h * 31 + s.charCodeAt(i)) % 360;
			h = (h + hueOffset) % 360;
			return `hsl(${h}, ${sat}%, ${light}%)`;
		}
		const userColors = (userData && userData.labels ? userData.labels : []).map(l => colorForString(l || String(Math.random())));
		const cu = (window.createDonut || function(){ return null; })(document.getElementById('userDonut'), userData.labels, userData.data, { colors: userColors });
		if (!cu) document.getElementById('userDonut').closest('.bg-white').insertAdjacentHTML('beforeend', '<div class="mt-3 text-sm text-gray-500">No data available.</div>');

		// Region chart
		const regionData = await fetchChart('region');
		console.debug('regionData', regionData);
		const cr = (window.createDonut || function(){ return null; })(document.getElementById('regionDonut'), regionData.labels, regionData.data);
		if (!cr) document.getElementById('regionDonut').closest('.bg-white').insertAdjacentHTML('beforeend', '<div class="mt-3 text-sm text-gray-500">No data available.</div>');

		// Rating chart
		const ratingData = await fetchChart('rating');
		console.debug('ratingData', ratingData);
		const cr2 = (window.createDonut || function(){ return null; })(document.getElementById('ratingDonut'), ratingData.labels, ratingData.data);
		if (!cr2) document.getElementById('ratingDonut').closest('.bg-white').insertAdjacentHTML('beforeend', '<div class="mt-3 text-sm text-gray-500">No data available.</div>');

	} catch (err) {
		console.error('Dashboard init error', err);
		if (window.showToast) showToast('Dashboard error: ' + (err && err.message ? err.message : String(err)), 'error', 6000);
	}
})();
</script>

	<script>
	// load monitored entries table for this user (full table under chart)
	const tableContainer = document.getElementById('monitoredTable');

	async function fetchTable(page = 1) {
	    const showAll = document.getElementById('showAll') ? document.getElementById('showAll').checked : false;
	    const region = document.getElementById('filterRegion') ? document.getElementById('filterRegion').value : '';
	    const et = document.getElementById('filterEventType') ? document.getElementById('filterEventType').value : '';
	    const uid = document.getElementById('filterUser') ? document.getElementById('filterUser').value : '';
	    let qs = new URLSearchParams({ page });
	    if (region) qs.set('region_id', region);
	    if (et) qs.set('event_type_id', et);
	    // user filter precedence: if a user is selected use it; else if showAll not checked default to current user
	    if (uid) {
	        qs.set('user_id', uid);
	    } else if (!showAll) {
	        qs.set('user_id', '<?= (int)$user['id'] ?>');
	    }
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

	// react to showAll toggle
	const showAllEl = document.getElementById('showAll');
	if (showAllEl) showAllEl.addEventListener('change', async () => { await fetchTable(1); });

	document.getElementById('applyFilters').addEventListener('click', async () => { await fetchTable(1); });

	// export CSV
	document.getElementById('exportMonitoredBtn').addEventListener('click', async () => {
	    const showAll = document.getElementById('showAll') ? document.getElementById('showAll').checked : false;
	    let url = '<?= dirname(baseUrl()) ?>/api/monitored.php?export=csv';
	    if (!showAll) url += '&user_id=<?= (int)$user['id'] ?>';
	    // navigate to url to trigger download
	    window.location = url;
	});

	// export XLSX
	const exportXlsxBtn = document.getElementById('exportXlsxBtn');
	if (exportXlsxBtn) {
	    exportXlsxBtn.addEventListener('click', async () => {
	        const showAll = document.getElementById('showAll') ? document.getElementById('showAll').checked : false;
	        let url = '<?= dirname(baseUrl()) ?>/api/export_monitored_xlsx.php?';
	        if (!showAll) url += 'user_id=<?= (int)$user['id'] ?>'; else url += 'show_all=1';
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
