<?php
require_once __DIR__ . '/../includes/init.php';
requireAuth();
$user = currentUser($pdo);
if ($user['role'] !== 'superadmin') { header('Location: /'); exit; }
$regions = $pdo->query('SELECT id, name, is_active FROM regions ORDER BY name')->fetchAll();
ob_start();
?>
<h1 class="text-2xl font-semibold mb-4" style="color:#025529">Manage Regions</h1>
<div class="bg-white p-4 rounded shadow mb-4">
  <form id="createRegionForm" class="flex gap-2" data-endpoint="<?= dirname(baseUrl()) ?>/api/regions.php">
    <input name="name" placeholder="Region name" class="p-2 border rounded" required />
    <button type="submit" id="createRegionBtn" class="p-2 rounded btn-brand text-white">Create</button>
  </form>
  <div id="regionResult" class="mt-2"></div>
</div>

<div class="bg-white p-4 rounded shadow">
  <table class="w-full">
    <thead><tr><th>ID</th><th>Name</th><th>Active</th></tr></thead>
    <tbody>
      <?php foreach($regions as $r): ?>
      <tr class="border-t"><td><?= $r['id'] ?></td><td><?= htmlspecialchars($r['name']) ?></td><td><?= $r['is_active'] ? 'Yes' : 'No' ?></td></tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<script>
document.getElementById('createRegionForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const form = e.target;
  const btn = document.getElementById('createRegionBtn');
  const result = document.getElementById('regionResult');
  result.innerText = '';
  btn.disabled = true;
  try {
    const f = new FormData(form);
    const body = Object.fromEntries(f.entries());
    const endpoint = form.getAttribute('data-endpoint');
    const res = await fetch(endpoint, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(body) });
    const text = await res.text();
    let json;
    try { json = JSON.parse(text); }
    catch(e) { result.innerText = 'Invalid JSON response:\n' + text; return; }
    result.innerText = json.success ? 'Created successfully.' : (json.error || JSON.stringify(json));
    if (json.success) setTimeout(() => location.reload(), 700);
  } catch (err) {
    result.innerText = 'Request failed: ' + (err.message || err);
  } finally {
    btn.disabled = false;
  }
});
</script>

<?php
$slot = ob_get_clean();
include view_path('views/layout.php');
?>
