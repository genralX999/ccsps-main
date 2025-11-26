<?php
require_once __DIR__ . '/../includes/init.php';
requireAuth();
$user = currentUser($pdo);
if ($user['role'] !== 'superadmin') { header('Location: /'); exit; }
$users = $pdo->query('SELECT id, monitor_id_code, username, email, role, status, is_active FROM users ORDER BY id DESC')->fetchAll();
ob_start();
?>
<h1 class="text-2xl font-semibold mb-4" style="color:#025529">Manage Users</h1>

<!-- Toast container for inline messages -->
<div id="toastContainer" class="fixed top-6 right-6 z-50 space-y-2"></div>

<div class="bg-white p-4 rounded shadow mb-4">
  <h2 class="font-semibold mb-2"></h2>
  <!-- <form id="createUserForm" class="grid grid-cols-1 md:grid-cols-4 gap-2" data-endpoint="<?= dirname(baseUrl()) ?>/api/users.php">
    <input name="username" placeholder="username" class="p-2 border rounded" required />
    <input name="email" type="email" placeholder="email (optional)" class="p-2 border rounded" />
    <input name="password" placeholder="password" class="p-2 border rounded" />
    
    <select name="role" class="p-2 border rounded">
      <option value="user">User</option>
      <option value="admin">Admin</option>
      <option value="superadmin">Superadmin</option>
    </select>
    
    <div></div>
    <button class="p-2 rounded btn-brand text-white" type="submit" id="createUserBtn">Create</button>
  </form> -->
  <div id="createResult" class="mt-2"></div>
</div>

<div class="bg-white p-4 rounded shadow">
  <h2 class="font-semibold mb-2">Users List</h2>
  <table class="w-full table-auto border-collapse">
    <thead>
      <tr>
        <th class="px-4 py-2 text-left bg-gray-100">ID</th>
        <th class="px-4 py-2 text-left bg-gray-100">Code</th>
        <th class="px-4 py-2 text-left bg-gray-100">Username</th>
        <th class="px-4 py-2 text-left bg-gray-100">Email</th>
        <th class="px-4 py-2 text-left bg-gray-100">Role</th>
        <th class="px-4 py-2 text-left bg-gray-100">Status</th>
        <th class="px-4 py-2 text-left bg-gray-100">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($users as $u): ?>
      <?php $isActive = !empty($u['is_active']) ? 1 : 0; ?>
      <tr class="border-t odd:bg-white even:bg-gray-50" data-user='<?= json_encode($u, JSON_HEX_APOS|JSON_HEX_QUOT) ?>'>
        <td class="px-4 py-2 text-sm"><?= $u['id'] ?></td>
        <td class="px-4 py-2 text-sm"><?= htmlspecialchars($u['monitor_id_code']) ?></td>
        <td class="px-4 py-2 text-sm"><?= htmlspecialchars($u['username']) ?></td>
        <td class="px-4 py-2 text-sm"><?= htmlspecialchars($u['email']) ?></td>
        <td class="px-4 py-2 text-sm"><?= htmlspecialchars($u['role']) ?></td>
        <td class="px-4 py-2 text-sm"><?= htmlspecialchars($u['status'] ?? '') ?></td>
       <td class="px-4 py-2 text-sm">
  <div class="flex items-center gap-2">

    <!-- Edit -->
    <button class="px-2 py-1 rounded bg-yellow-500 text-white text-sm edit-user" data-id="<?= $u['id'] ?>">Edit</button>

    <!-- Resend email (only if not approved) -->
    <?php if (($u['status'] ?? '') !== 'approved'): ?>
      <button class="px-2 py-1 rounded bg-blue-600 text-white text-sm resend-verify" data-id="<?= $u['id'] ?>">Resend</button>
    <?php endif; ?>

    <!-- Reset Password -->
    <button class="px-2 py-1 rounded bg-red-600 text-white text-sm send-reset" data-email="<?= htmlspecialchars($u['email']) ?>">Reset</button>

    <!-- Approve / Decline (only if not approved) -->
    <?php if (($u['status'] ?? '') !== 'approved'): ?>
      <button class="px-2 py-1 rounded bg-green-600 text-white text-sm approve-user" data-id="<?= $u['id'] ?>">Approve</button>
      <button class="px-2 py-1 rounded bg-gray-600 text-white text-sm decline-user" data-id="<?= $u['id'] ?>">Decline</button>
    <?php endif; ?>

    <!-- Enable/Disable (only ONE button) -->
    <button
      class="px-2 py-1 rounded text-white text-sm toggle-active"
      data-id="<?= $u['id'] ?>"
      data-active="<?= $isActive ?>"
      style="background-color:<?= $isActive ? '#6B7280' : '#10B981' ?>">
      <?= $isActive ? 'Disable' : 'Enable' ?>
    </button>

  </div>
</td>

      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const editModal = document.getElementById('editModal');
  const editForm = document.getElementById('editForm');
  const editMsg = document.getElementById('editMsg');
  const openEdit = (user) => {
    editForm.id.value = user.id;
    editForm.username.value = user.username || '';
    editForm.email.value = user.email || '';
    editForm.password.value = '';
    setModalMessage(editMsg, '', '');
    editModal.classList.remove('hidden');
    editModal.classList.add('flex');
  };
  const closeEdit = () => { editModal.classList.add('hidden'); editModal.classList.remove('flex'); };

  // Guard the create form listener (form is commented out by default)
  const createForm = document.getElementById('createUserForm');
  if (createForm) {
    createForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const form = e.target;
      const btn = document.getElementById('createUserBtn');
      const result = document.getElementById('createResult');
      result.innerText = '';
      btn.disabled = true;
      try {
        const f = new FormData(form);
        const body = Object.fromEntries(f.entries());
        const endpoint = form.getAttribute('data-endpoint') || ('<?= dirname(baseUrl()) ?>/api/users.php');
        const res = await fetch(endpoint, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(body) });
        const text = await res.text();
        let json;
        try { json = JSON.parse(text); }
        catch(e) { result.innerText = 'Invalid JSON response:\n' + text; return; }
        if (json.success) {
          const code = json.monitor_id_code ? (' - ' + json.monitor_id_code) : '';
          result.innerText = 'Created successfully' + code + '.';
          setTimeout(() => location.reload(), 900);
        } else {
          result.innerText = (json.error || JSON.stringify(json));
        }
      } catch (err) {
        result.innerText = 'Request failed: ' + (err.message || err);
      } finally {
        btn.disabled = false;
      }
    });
  }

  // handler for toggling active state (Enable/Disable)
  // toast helper
  const toastContainer = document.getElementById('toastContainer');
  function showToast(message, type = 'success', timeout = 3000) {
    if (!toastContainer) return;
    const el = document.createElement('div');
    el.className = 'px-4 py-2 rounded shadow text-sm transition-opacity duration-300 opacity-0';
    el.style.pointerEvents = 'auto';
    if (type === 'error') el.classList.add('bg-red-600','text-white');
    else el.classList.add('bg-green-600','text-white');
    el.textContent = message;
    toastContainer.appendChild(el);
    // fade in
    requestAnimationFrame(() => { el.classList.remove('opacity-0'); el.classList.add('opacity-100'); });
    // remove after timeout
    setTimeout(() => { el.classList.remove('opacity-100'); el.classList.add('opacity-0'); setTimeout(() => el.remove(), 300); }, timeout);
  }
  // modal message helper: el - element, type: 'success'|'error'|''
  function setModalMessage(el, msg, type='') {
    if (!el) return;
    el.textContent = msg || '';
    el.classList.remove('text-green-600','text-red-600');
    if (type === 'success') el.classList.add('text-green-600');
    else if (type === 'error') el.classList.add('text-red-600');
  }
  document.querySelectorAll('.toggle-active').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      e.preventDefault();
      const id = btn.getAttribute('data-id');
      const current = btn.getAttribute('data-active') === '1';
      const newVal = current ? 0 : 1;
      const prevText = btn.textContent;
      btn.textContent = 'Updating...';
      btn.disabled = true;
      try {
        const res = await fetch('../api/users_update.php', {
          method: 'POST', headers: {'Content-Type':'application/json'},
          body: JSON.stringify({ id: id, is_active: newVal })
        });
        const j = await res.json();
        if (!j.success) { showToast('Failed: ' + (j.error || 'unknown'), 'error'); return; }
        // update row data and button state
        const rows = document.querySelectorAll('tr[data-user]');
        rows.forEach(r => {
          const u = JSON.parse(r.getAttribute('data-user'));
          if (String(u.id) === String(j.user.id)) {
            const newUser = Object.assign({}, u, j.user);
            r.setAttribute('data-user', JSON.stringify(newUser));
            const statusCell = r.querySelector('td:nth-child(6)');
            if (statusCell && j.user.status !== undefined) statusCell.textContent = j.user.status || '';
            const tog = r.querySelector('.toggle-active');
            if (tog) {
              tog.setAttribute('data-active', j.user.is_active ? '1' : '0');
              tog.textContent = j.user.is_active ? 'Disable' : 'Enable';
              tog.style.backgroundColor = j.user.is_active ? '#6B7280' : '#10B981';
            }
          }
        });
      } catch (err) {
        showToast('Network or server error', 'error');
        // restore previous label on error
        btn.textContent = prevText;
      } finally {
        btn.disabled = false;
      }
    });
  });

  document.querySelectorAll('.edit-user').forEach(btn => {
    btn.addEventListener('click', (e) => {
      const tr = e.target.closest('tr');
      const user = JSON.parse(tr.getAttribute('data-user'));
      openEdit(user);
    });
  });

  document.getElementById('editClose').addEventListener('click', closeEdit);
  document.getElementById('editCancel').addEventListener('click', (e) => { e.preventDefault(); closeEdit(); });

  editForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = new FormData(editForm);
    const payload = {
      id: form.get('id'),
      username: form.get('username'),
      email: form.get('email'),
      password: form.get('password')
    };
    setModalMessage(editMsg, 'Saving...', '');
    try {
      const res = await fetch('../api/users_update.php', {
        method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(payload)
      });
      const j = await res.json();
      if (!j.success) { setModalMessage(editMsg, j.error || 'Save failed', 'error'); return; }
      // update row
      const rows = document.querySelectorAll('tr[data-user]');
      rows.forEach(r => {
        const u = JSON.parse(r.getAttribute('data-user'));
        if (String(u.id) === String(j.user.id)) {
          r.querySelector('td:nth-child(3)').textContent = j.user.username || '';
          r.querySelector('td:nth-child(4)').textContent = j.user.email || '';
          r.querySelector('td:nth-child(5)').textContent = j.user.role || '';
          // update data-user attr
          r.setAttribute('data-user', JSON.stringify(j.user));
        }
      });
      setModalMessage(editMsg, 'Saved.', 'success');
      setTimeout(closeEdit, 600);
    } catch (err) { setModalMessage(editMsg, 'Error saving', 'error'); }
  });

  // Resend verification
  document.querySelectorAll('.resend-verify').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      const id = e.target.getAttribute('data-id');
      e.target.textContent = 'Sending...';
      try {
        const form = new FormData(); form.append('id', id);
        const res = await fetch('../api/request_resend_verification.php', {method:'POST', body: form});
        const j = await res.json();
        if (j.success) { showToast('Verification resent', 'success'); } else { showToast('Failed: ' + (j.error||'unknown'), 'error'); }
      } catch (err) { showToast('Network error', 'error'); }
      e.target.textContent = 'Resend';
    });
  });

  // Send reset
  document.querySelectorAll('.send-reset').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      const email = e.target.getAttribute('data-email');
      if (!email) { showToast('No email available for this user', 'error'); return; }
      if (!confirm('Send password reset to ' + email + '?')) return;
      e.target.textContent = 'Sending...';
      try {
        const res = await fetch('../api/request_reset.php', {
          method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({ email })
        });
        const j = await res.json();
        if (j.success) { showToast('Reset email sent', 'success'); } else { showToast('Failed: ' + (j.error||'unknown'), 'error'); }
      } catch (err) { showToast('Network error', 'error'); }
      e.target.textContent = 'Reset';
    });
  });

    // Approve / Decline
    document.querySelectorAll('.approve-user').forEach(btn => {
      btn.addEventListener('click', async (e) => {
        const id = e.target.getAttribute('data-id');
        if (!confirm('Approve this user?')) return;
        e.target.textContent = 'Approving...';
        try {
          const form = new FormData(); form.append('id', id); form.append('action', 'approve');
          const res = await fetch('../api/users_approve.php', { method:'POST', body: form });
          const j = await res.json();
          if (j.success) {
            showToast('User approved', 'success');
            // update status cell and row data; remove approve/decline buttons
            const tr = e.target.closest('tr');
            if (tr) {
              // update status cell
              const statusCell = tr.querySelector('td:nth-child(6)');
              if (statusCell) statusCell.textContent = j.status || '';
              // update data-user attribute
              try {
                const u = JSON.parse(tr.getAttribute('data-user')) || {};
                u.status = j.status || u.status;
                tr.setAttribute('data-user', JSON.stringify(u));
              } catch (ex) {}
              // remove both approve and decline buttons inside this row
              const approveBtn = tr.querySelector('.approve-user'); if (approveBtn) approveBtn.remove();
              const declineBtn = tr.querySelector('.decline-user'); if (declineBtn) declineBtn.remove();
              // also remove resend button as it's not needed for approved users
              const resendBtn = tr.querySelector('.resend-verify'); if (resendBtn) resendBtn.remove();
            }
          } else { showToast('Failed: ' + (j.error||'unknown'), 'error'); }
        } catch (err) { showToast('Network error', 'error'); }
      });
    });

    document.querySelectorAll('.decline-user').forEach(btn => {
      btn.addEventListener('click', async (e) => {
        const id = e.target.getAttribute('data-id');
        if (!confirm('Decline this user?')) return;
        e.target.textContent = 'Processing...';
        try {
          const form = new FormData(); form.append('id', id); form.append('action', 'decline');
          const res = await fetch('../api/users_approve.php', { method:'POST', body: form });
          const j = await res.json();
          if (j.success) {
            showToast('User declined', 'success');
            const tr = e.target.closest('tr');
            if (tr) {
              const statusCell = tr.querySelector('td:nth-child(6)');
              if (statusCell) statusCell.textContent = j.status || '';
              // update data-user attribute
              try {
                const u = JSON.parse(tr.getAttribute('data-user')) || {};
                u.status = j.status || u.status;
                tr.setAttribute('data-user', JSON.stringify(u));
              } catch (ex) {}
              // remove approve/decline buttons
              const approveBtn = tr.querySelector('.approve-user'); if (approveBtn) approveBtn.remove();
              const declineBtn = tr.querySelector('.decline-user'); if (declineBtn) declineBtn.remove();
            }
          } else { showToast('Failed: ' + (j.error||'unknown'), 'error'); }
        } catch (err) { showToast('Network error', 'error'); }
      });
    });
});
</script>

<!-- Edit user modal -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-40 hidden items-center justify-center p-4">
  <div class="bg-white rounded shadow w-full max-w-md p-4">
    <div class="flex justify-between items-center mb-3">
      <h3 class="font-semibold">Edit User</h3>
      <button id="editClose" class="text-gray-500">✕</button>
    </div>
    <div id="editMsg" class="mb-2 text-sm"></div>
    <form id="editForm">
      <input type="hidden" name="id" />
      <label class="block mb-2">Username
        <input name="username" class="w-full p-2 border rounded" required />
      </label>
      <label class="block mb-2">Email
        <input name="email" type="email" class="w-full p-2 border rounded" />
      </label>
      <!-- Role is not editable in this modal to avoid accidental privilege changes -->
      <label class="block mb-4">New password (leave empty to keep)
        <input name="password" type="password" class="w-full p-2 border rounded" />
      </label>
      <div class="flex items-center gap-2">
        <button type="submit" class="px-3 py-2 rounded btn-brand text-white">Save</button>
        <button type="button" id="editCancel" class="px-3 py-2 rounded border">Cancel</button>
      </div>
    </form>
  </div>
</div>



<?php
$slot = ob_get_clean();
include view_path('views/layout.php');
?>
