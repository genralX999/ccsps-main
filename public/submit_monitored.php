<?php
require_once __DIR__ . '/../includes/init.php';
requireAuth();
$user = currentUser($pdo);
// load selects (only active records)
$regions = $pdo->query('SELECT id,name FROM regions WHERE is_active = 1 ORDER BY name')->fetchAll();
$event_types = $pdo->query('SELECT id,name FROM event_types WHERE is_active = 1 ORDER BY name')->fetchAll();
$actions = $pdo->query('SELECT id,name FROM actions WHERE is_active = 1 ORDER BY name')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $event_date = $_POST['event_date'] ?? null;
  $region_id = intval($_POST['region_id'] ?? 0);
  $location = trim($_POST['location'] ?? '');
  $event_type_id = intval($_POST['event_type_id'] ?? 0);
  $sub_event_type_id = intval($_POST['sub_event_type_id'] ?? 0);
  $action_id = intval($_POST['action_id'] ?? 0);
  $source_url = trim($_POST['source_url'] ?? '');
  $notes = $_POST['notes'] ?? '';
  $fatalities = intval($_POST['fatalities'] ?? 0);
  $rating = isset($_POST['rating']) && $_POST['rating'] !== '' ? intval($_POST['rating']) : null;
  if ($rating !== null && ($rating < 1 || $rating > 10)) { $rating = null; }

  // check for existing similar record and skip create if found
  $checkSql = 'SELECT id FROM monitored_information WHERE is_deleted = 0
                AND event_date = :event_date
                AND region_id = :region_id
                AND TRIM(location) = :location
                AND event_type_id = :event_type_id
                AND sub_event_type_id = :sub_event_type_id
                AND action_id = :action_id
                AND COALESCE(source_url,"") = :source_url
                AND COALESCE(fatalities,0) = :fatalities
                AND COALESCE(rating, -1) = COALESCE(:rating, -1)
                LIMIT 1';
  $chk = $pdo->prepare($checkSql);
  $chk->execute([
    ':event_date'=>$event_date,
    ':region_id'=>$region_id,
    ':location'=>$location,
    ':event_type_id'=>$event_type_id,
    ':sub_event_type_id'=>$sub_event_type_id,
    ':action_id'=>$action_id,
    ':source_url'=>$source_url,
    ':fatalities'=>$fatalities,
    ':rating'=>$rating,
  ]);
  $existing = $chk->fetchColumn();
  if ($existing) {
    $successMessage = 'A similar submission already exists (ID: ' . $existing . '). Submission was not duplicated.';
    $_POST = [];
  } else {
    $stmt = $pdo->prepare('INSERT INTO monitored_information (user_id, event_date, region_id, location, event_type_id, sub_event_type_id, action_id, source_url, notes, fatalities, rating, created_at, updated_at)
                 VALUES (:user_id, :event_date, :region_id, :location, :event_type_id, :sub_event_type_id, :action_id, :source_url, :notes, :fatalities, :rating, NOW(), NOW())');
    $stmt->execute([
      ':user_id'=>$user['id'],
      ':event_date'=>$event_date,
      ':region_id'=>$region_id,
      ':location'=>$location,
      ':event_type_id'=>$event_type_id,
      ':sub_event_type_id'=>$sub_event_type_id,
      ':action_id'=>$action_id,
      ':source_url'=>$source_url,
      ':notes'=>$notes,
      ':fatalities'=>$fatalities,
      ':rating'=>$rating,
    ]);
    $id = $pdo->lastInsertId();
  logActivity($pdo, $user['id'], 'create', 'monitored_information', $id, ['location'=>$location]);

  // set a success flag and keep the form available for new entries
  $successMessage = 'Submission saved successfully.';
    // clear POST values so the form shows empty inputs
    $_POST = [];
  }
}

ob_start();
?>
<div class="max-w-3xl mx-auto">
  <div class="mb-6">
    <h1 class="text-2xl font-semibold" style="color:#025529">Submit Monitored Information</h1>
    <p class="text-sm text-gray-600">Fill out the form below to record an observation.</p>
  </div>

  <?php if (!empty($successMessage)): ?>
    <div class="mb-4 p-3 rounded bg-green-100 text-green-800"><?= htmlspecialchars($successMessage) ?></div>
  <?php endif; ?>

  <form method="post" class="bg-white p-6 rounded-lg shadow">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium text-gray-700">Event date</label>
        <input type="date" name="event_date" value="<?= htmlspecialchars($_POST['event_date'] ?? '') ?>" class="mt-1 block w-full p-2 border rounded" required>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Region</label>
        <select name="region_id" class="mt-1 block w-full p-2 border rounded" required>
          <option value="">Select</option>
          <?php foreach($regions as $r): ?>
          <option value="<?= $r['id'] ?>" <?= (isset($_POST['region_id']) && $_POST['region_id'] == $r['id']) ? 'selected' : '' ?>><?= htmlspecialchars($r['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Event type</label>
        <select id="event_type" name="event_type_id" class="mt-1 block w-full p-2 border rounded" required>
          <option value="">Select</option>
          <?php foreach($event_types as $et): ?>
          <option value="<?= $et['id'] ?>" <?= (isset($_POST['event_type_id']) && $_POST['event_type_id'] == $et['id']) ? 'selected' : '' ?>><?= htmlspecialchars($et['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Sub event type</label>
        <select id="sub_event_type" name="sub_event_type_id" class="mt-1 block w-full p-2 border rounded" required>
          <option value="">Select event type first</option>
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Action</label>
        <select name="action_id" class="mt-1 block w-full p-2 border rounded" required>
          <option value="">Select</option>
          <?php foreach($actions as $a): ?>
          <option value="<?= $a['id'] ?>" <?= (isset($_POST['action_id']) && $_POST['action_id'] == $a['id']) ? 'selected' : '' ?>><?= htmlspecialchars($a['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Location</label>
        <input name="location" value="<?= htmlspecialchars($_POST['location'] ?? '') ?>" class="mt-1 block w-full p-2 border rounded" />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Source (URL)</label>
        <input name="source_url" value="<?= htmlspecialchars($_POST['source_url'] ?? '') ?>" placeholder="https://example.com/article" class="mt-1 block w-full p-2 border rounded" />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Fatalities</label>
        <input type="number" min="0" name="fatalities" value="<?= htmlspecialchars($_POST['fatalities'] ?? '0') ?>" class="mt-1 block w-full p-2 border rounded" />
      </div>

      <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700">Notes</label>
        <textarea name="notes" class="mt-1 block w-full p-2 border rounded" rows="4"><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Rating</label>
        <select name="rating" class="mt-1 block w-full p-2 border rounded">
          <option value="">(none)</option>
          <?php for ($r=1;$r<=10;$r++): ?>
              <option value="<?= $r ?>" <?= (isset($_POST['rating']) && strval($_POST['rating']) === (string)$r) ? 'selected' : '' ?>><?= $r ?></option>
            <?php endfor; ?>
        </select>
      </div>
    </div>

    <div class="mt-4 flex items-center justify-end gap-3">
      <a href="<?= baseUrl() ?>/user_dashboard.php" class="px-4 py-2 border rounded text-gray-700">Cancel</a>
      <button type="submit" class="px-4 py-2 rounded btn-brand text-white">Submit</button>
    </div>
  </form>
</div>

<script>
document.getElementById('event_type').addEventListener('change', async (e) => {
  const id = e.target.value;
  const sel = document.getElementById('sub_event_type');
  sel.innerHTML = '<option>Loading...</option>';
  const res = await fetch('<?= dirname(baseUrl()) ?>/api/sub_event_types.php?event_type_id=' + encodeURIComponent(id));
  const rows = await res.json();
  sel.innerHTML = '<option value="">Select</option>';
  rows.forEach(r => {
    const o = document.createElement('option');
    o.value = r.id; o.textContent = r.name;
    sel.appendChild(o);
  });
});
</script>

<?php
$slot = ob_get_clean();
include view_path('views/layout.php');
?>
