<?php
require_once __DIR__ . '/../includes/init.php';
requireAuth();
$user = currentUser($pdo);
if ($user['role'] !== 'superadmin') { header('Location: ' . baseUrl() . '/'); exit; }
ob_start();
?>
<h1 class="text-2xl font-semibold mb-4" style="color:#025529">Manage Taxonomy</h1>

<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
  <div class="bg-white p-4 rounded shadow">
    <h2 class="font-semibold mb-2">Create Action</h2>
    <form id="createActionForm" data-endpoint="<?= dirname(baseUrl()) ?>/api/taxonomy.php">
      <input name="name" placeholder="Action name" class="w-full p-2 border rounded mb-2" required />
      <input type="hidden" name="type" value="action" />
      <button type="submit" id="createActionBtn" class="px-3 py-2 btn-brand text-white rounded">Create</button>
    </form>
    <div id="actionResult" class="mt-2 text-sm"></div>
  </div>

  <div class="bg-white p-4 rounded shadow">
    <h2 class="font-semibold mb-2">Create Event Type</h2>
    <form id="createEventTypeForm" data-endpoint="<?= dirname(baseUrl()) ?>/api/taxonomy.php">
      <input name="name" placeholder="Event type name" class="w-full p-2 border rounded mb-2" required />
      <input type="hidden" name="type" value="event_type" />
      <button type="submit" id="createEventTypeBtn" class="px-3 py-2 btn-brand text-white rounded">Create</button>
    </form>
    <div id="eventTypeResult" class="mt-2 text-sm"></div>
  </div>

  <div class="bg-white p-4 rounded shadow">
    <h2 class="font-semibold mb-2">Create Sub Event Type</h2>
    <form id="createSubEventTypeForm" data-endpoint="<?= dirname(baseUrl()) ?>/api/taxonomy.php">
      <select name="event_type_id" id="selectEventType" class="w-full p-2 border rounded mb-2" required>
        <option value="">Select event type</option>
      </select>
      <input name="name" placeholder="Sub event type name" class="w-full p-2 border rounded mb-2" required />
      <input type="hidden" name="type" value="sub_event_type" />
      <button type="submit" id="createSubEventTypeBtn" class="px-3 py-2 btn-brand text-white rounded">Create</button>
    </form>
    <div id="subEventTypeResult" class="mt-2 text-sm"></div>
  </div>

  <div class="bg-white p-4 rounded shadow">
    <h2 class="font-semibold mb-2">Create Region</h2>
    <form id="createRegionForm" data-endpoint="<?= dirname(baseUrl()) ?>/api/regions.php">
      <input name="name" placeholder="Region name" class="w-full p-2 border rounded mb-2" required />
      <button type="submit" id="createRegionBtn" class="px-3 py-2 btn-brand text-white rounded">Create</button>
    </form>
    <div id="regionResult" class="mt-2 text-sm"></div>
  </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
  <div class="bg-white p-4 rounded shadow">
    <h3 class="font-semibold mb-2">Actions</h3>
    <div id="actionsList">Loading...</div>
  </div>

  <div class="bg-white p-4 rounded shadow">
    <h3 class="font-semibold mb-2">Event Types</h3>
    <div id="eventTypesList">Loading...</div>
  </div>

  <div class="bg-white p-4 rounded shadow">
    <h3 class="font-semibold mb-2">Sub Event Types</h3>
    <div id="subEventTypesList">Loading...</div>
  </div>

  <div class="bg-white p-4 rounded shadow">
    <h3 class="font-semibold mb-2">Regions</h3>
    <div id="regionsList">Loading...</div>
  </div>
</div>

<script>
async function loadEventTypes() {
  const sel = document.getElementById('selectEventType');
  sel.innerHTML = '<option>Loading...</option>';
  const res = await fetch('<?= dirname(baseUrl()) ?>/api/taxonomy.php?type=event_types');
  const rows = await res.json();
  sel.innerHTML = '<option value="">Select event type</option>';
  rows.forEach(r => {
    const o = document.createElement('option'); o.value = r.id; o.textContent = r.name; sel.appendChild(o);
  });
}

async function handleFormSubmit(formId, resultId, btnId) {
  const form = document.getElementById(formId);
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById(btnId);
    const result = document.getElementById(resultId);
    result.innerText = '';
    btn.disabled = true;
    try {
      const data = Object.fromEntries(new FormData(form).entries());
      const endpoint = form.getAttribute('data-endpoint');
      const res = await fetch(endpoint, { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(data) });
      const json = await res.json();
      if (json.success) {
        result.innerText = 'Created successfully.';
        // reload lists for management (refreshes event types, regions, etc.)
        await loadLists();
      } else {
        // map common server errors to friendly messages
        if (json.error === 'name_exists') {
          result.innerText = 'An item with that name already exists.';
        } else {
          result.innerText = json.error || JSON.stringify(json);
        }
      }
    } catch (err) {
      result.innerText = 'Request failed: ' + (err.message||err);
    } finally { btn.disabled = false; }
  });
}

(function(){
  loadEventTypes();
  handleFormSubmit('createActionForm', 'actionResult', 'createActionBtn');
  handleFormSubmit('createEventTypeForm', 'eventTypeResult', 'createEventTypeBtn');
  handleFormSubmit('createSubEventTypeForm', 'subEventTypeResult', 'createSubEventTypeBtn');
  handleFormSubmit('createRegionForm', 'regionResult', 'createRegionBtn');
  // also load lists for management
  loadLists();
})();

async function loadLists() {
  await loadEventTypes();
  // actions
  const ares = await fetch('<?= dirname(baseUrl()) ?>/api/taxonomy.php?type=actions_all');
  const actions = await ares.json();
  renderActions(actions);
  // event types
  const etres = await fetch('<?= dirname(baseUrl()) ?>/api/taxonomy.php?type=event_types_all');
  const ets = await etres.json();
  renderEventTypes(ets);
  // sub event types
  const sres = await fetch('<?= dirname(baseUrl()) ?>/api/taxonomy.php?type=sub_event_types_all');
  const subs = await sres.json();
  renderSubEventTypes(subs);
  // regions
  const rres = await fetch('<?= dirname(baseUrl()) ?>/api/regions.php');
  const regs = await rres.json();
  renderRegions(regs);
}

function el(html) { const d = document.createElement('div'); d.innerHTML = html; return d.firstElementChild; }

function renderActions(rows) {
  const container = document.getElementById('actionsList');
  if (!rows.length) { container.innerHTML = '<div class="text-sm">No actions yet.</div>'; return; }
  const table = ['<table class="w-full text-sm"><thead><tr><th>Name</th><th class="text-right">Actions</th></tr></thead><tbody>'];
  rows.forEach(r => {
    table.push(`<tr class="border-t" data-id="${r.id}"><td class="p-2 name">${escapeHtml(r.name)}</td><td class="p-2 text-right"><button class="edit-action px-2 py-1 mr-2 bg-yellow-300 rounded text-sm">Edit</button></td></tr>`);
  });
  table.push('</tbody></table>'); container.innerHTML = table.join('');
  container.querySelectorAll('.edit-action').forEach(b => b.addEventListener('click', onEditAction));
  container.querySelectorAll('.delete-action').forEach(b => b.addEventListener('click', onDeleteAction));
}

function renderEventTypes(rows) {
  const container = document.getElementById('eventTypesList');
  if (!rows.length) { container.innerHTML = '<div class="text-sm">No event types yet.</div>'; return; }
  const table = ['<table class="w-full text-sm"><thead><tr><th>Name</th><th class="text-right">Actions</th></tr></thead><tbody>'];
  rows.forEach(r => {
    table.push(`<tr class="border-t" data-id="${r.id}"><td class="p-2 name">${escapeHtml(r.name)}</td><td class="p-2 text-right"><button class="edit-et px-2 py-1 mr-2 bg-yellow-300 rounded text-sm">Edit</button></td></tr>`);
  });
  table.push('</tbody></table>'); container.innerHTML = table.join('');
  container.querySelectorAll('.edit-et').forEach(b => b.addEventListener('click', onEditEventType));
  container.querySelectorAll('.delete-et').forEach(b => b.addEventListener('click', onDeleteEventType));
}

function renderSubEventTypes(rows) {
  const container = document.getElementById('subEventTypesList');
  if (!rows.length) { container.innerHTML = '<div class="text-sm">No sub event types yet.</div>'; return; }
  const table = ['<table class="w-full text-sm"><thead><tr><th>Event Type ID</th><th>Name</th><th class="text-right">Actions</th></tr></thead><tbody>'];
  rows.forEach(r => {
    table.push(`<tr class="border-t" data-id="${r.id}"><td class="p-2">${r.event_type_id}</td><td class="p-2 name">${escapeHtml(r.name)}</td><td class="p-2 text-right"><button class="edit-sub px-2 py-1 mr-2 bg-yellow-300 rounded text-sm">Edit</button></td></tr>`);
  });
  table.push('</tbody></table>'); container.innerHTML = table.join('');
  container.querySelectorAll('.edit-sub').forEach(b => b.addEventListener('click', onEditSub));
  container.querySelectorAll('.delete-sub').forEach(b => b.addEventListener('click', onDeleteSub));
}

function renderRegions(rows) {
  const container = document.getElementById('regionsList');
  if (!rows.length) { container.innerHTML = '<div class="text-sm">No regions yet.</div>'; return; }
  const table = ['<table class="w-full text-sm"><thead><tr><th>Name</th><th class="text-right">Actions</th></tr></thead><tbody>'];
  rows.forEach(r => {
    table.push(`<tr class="border-t" data-id="${r.id}"><td class="p-2 name">${escapeHtml(r.name)}</td><td class="p-2 text-right"><button class="edit-reg px-2 py-1 mr-2 bg-yellow-300 rounded text-sm">Edit</button></td></tr>`);
  });
  table.push('</tbody></table>'); container.innerHTML = table.join('');
  container.querySelectorAll('.edit-reg').forEach(b => b.addEventListener('click', onEditRegion));
  container.querySelectorAll('.delete-reg').forEach(b => b.addEventListener('click', onDeleteRegion));
}

function escapeHtml(s){ return (s+'').replace(/[&<>"]+/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]||c)); }

async function onEditAction(e){
  const tr = e.target.closest('tr'); startInlineEdit(tr, 'action');
}
async function onDeleteAction(e){
  const tr = e.target.closest('tr'); await doDelete('action', tr.dataset.id); }
async function onEditEventType(e){ startInlineEdit(e.target.closest('tr'), 'event_type'); }
async function onDeleteEventType(e){ await doDelete('event_type', e.target.closest('tr').dataset.id); }
async function onEditSub(e){ startInlineEdit(e.target.closest('tr'), 'sub_event_type'); }
async function onDeleteSub(e){ await doDelete('sub_event_type', e.target.closest('tr').dataset.id); }
async function onEditRegion(e){ startInlineEdit(e.target.closest('tr'), 'region'); }
async function onDeleteRegion(e){ await doDelete('region', e.target.closest('tr').dataset.id); }

function startInlineEdit(tr, type) {
  const nameCell = tr.querySelector('.name');
  const id = tr.dataset.id;
  const current = nameCell.textContent.trim();
  nameCell.innerHTML = `<input class="w-full p-1 border rounded" value="${escapeHtml(current)}" />`;
  const actionsCell = tr.querySelector('td:last-child');
  actionsCell.innerHTML = `<button class="save btn-save px-2 py-1 mr-2 bg-green-500 text-white rounded text-sm">Save</button><button class="btn-cancel px-2 py-1 bg-gray-300 rounded text-sm">Cancel</button>`;
  actionsCell.querySelector('.btn-cancel').addEventListener('click', ()=>{ nameCell.textContent = current; actionsCell.innerHTML = `<button class="edit px-2 py-1 mr-2 bg-yellow-300 rounded text-sm">Edit</button>`; attachRowHandlers(tr, type); });
    actionsCell.querySelector('.btn-save').addEventListener('click', async ()=>{
    const newName = nameCell.querySelector('input').value.trim();
      if (!newName) { showToast('Name required', 'error'); return; }
    if (type === 'region') {
      const res = await fetch('<?= dirname(baseUrl()) ?>/api/regions.php', { method: 'PUT', headers:{'Content-Type':'application/json'}, body: JSON.stringify({id: id, name: newName}) });
      const j = await res.json();
      if (j.success) { await loadLists(); } else { showToast(j.error === 'name_exists' ? 'A region with that name already exists.' : (j.error||JSON.stringify(j)), 'error'); }
    } else {
      const payload = {type: type, id: id, name: newName};
      // include event_type_id when editing sub_event_type inline if present on the row
      const evtIdCell = tr.querySelector('td:first-child');
      if (type === 'sub_event_type' && evtIdCell) {
        // the first column contains event_type_id in sub_event_types list
        const evt = tr.querySelector('td')?.textContent?.trim();
        if (evt) payload.event_type_id = parseInt(evt, 10) || undefined;
      }
      const res = await fetch('<?= dirname(baseUrl()) ?>/api/taxonomy.php', { method: 'PUT', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
      const j = await res.json();
      if (j.success) { await loadLists(); } else { showToast(j.error === 'name_exists' ? 'An item with that name already exists.' : (j.error||JSON.stringify(j)), 'error'); }
    }
  });
}

function attachRowHandlers(tr, type) {
  tr.querySelector('.edit')?.addEventListener('click', ()=> startInlineEdit(tr, type));
  tr.querySelector('.del')?.addEventListener('click', ()=> doDelete(type, tr.dataset.id));
}

async function doDelete(type, id) {
  if (!confirm('Delete this item?')) return;
  try {
    if (type === 'region') {
        const res = await fetch('<?= dirname(baseUrl()) ?>/api/regions.php', { method: 'DELETE', headers:{'Content-Type':'application/json'}, body: JSON.stringify({id: id}) });
          const j = await res.json(); if (j.success) await loadLists(); else showToast(j.error||JSON.stringify(j), 'error');
    } else {
          const res = await fetch('<?= dirname(baseUrl()) ?>/api/taxonomy.php', { method: 'DELETE', headers:{'Content-Type':'application/json'}, body: JSON.stringify({type: type, id: id}) });
          const j = await res.json(); if (j.success) await loadLists(); else showToast(j.error||JSON.stringify(j), 'error');
    }
  } catch (err) { showToast('Request failed: '+(err.message||err), 'error'); }
}

</script>

<?php
$slot = ob_get_clean();
include view_path('views/layout.php');
?>
