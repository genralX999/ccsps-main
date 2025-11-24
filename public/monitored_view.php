<?php
require_once __DIR__ . '/../includes/init.php';
requireAuth();
$user = currentUser($pdo);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$id) { http_response_code(404); echo 'Not found'; exit; }

// load record
$stmt = $pdo->prepare("SELECT mi.*, r.name AS region_name, et.name AS event_type_name, se.name AS sub_event_name, a.name AS action_name, u.username, u.monitor_id_code
                       FROM monitored_information mi
                       JOIN regions r ON mi.region_id = r.id
                       JOIN event_types et ON mi.event_type_id = et.id
                       JOIN sub_event_types se ON mi.sub_event_type_id = se.id
                       JOIN actions a ON mi.action_id = a.id
                       JOIN users u ON mi.user_id = u.id
                       WHERE mi.id = :id AND mi.is_deleted = 0");
$stmt->execute([':id'=>$id]);
$row = $stmt->fetch();
if (!$row) { http_response_code(404); echo 'Not found'; exit; }

// determine ownership (used to toggle edit/delete controls)
$isOwner = ($user['id'] === $row['user_id']);

// detect AJAX requests
$isAjax = (isset($_GET['ajax']) && $_GET['ajax']) || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');


// load selects for form
$regions = $pdo->query('SELECT id,name FROM regions ORDER BY name')->fetchAll();
$event_types = $pdo->query('SELECT id,name FROM event_types ORDER BY name')->fetchAll();
$actions = $pdo->query('SELECT id,name FROM actions ORDER BY name')->fetchAll();
$sub_event_types = $pdo->prepare('SELECT id,name FROM sub_event_types WHERE event_type_id = :eid ORDER BY name');
$sub_event_types->execute([':eid'=>$row['event_type_id']]);
$sub_event_types = $sub_event_types->fetchAll();

$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $act = $_POST['act'] ?? 'update';
  // enforce server-side permission: only owner or superadmin may modify/delete
  if (!$isOwner && ($user['role'] ?? '') !== 'superadmin') {
    if ($isAjax) {
      http_response_code(403);
      header('Content-Type: application/json');
      echo json_encode(['success' => false, 'message' => 'Forbidden']);
      exit;
    }
    http_response_code(403); echo 'Forbidden'; exit;
  }
    if ($act === 'delete') {
        // soft delete
    $d = $pdo->prepare('UPDATE monitored_information SET is_deleted = 1, updated_at = NOW() WHERE id = :id');
    $d->execute([':id'=>$id]);
    logActivity($pdo, $user['id'], 'delete', 'monitored_information', $id, []);
    if ($isAjax) {
      header('Content-Type: application/json');
      echo json_encode(['success'=>true, 'deleted'=>true, 'message'=>'Record deleted']);
      exit;
    }
    header('Location: ' . baseUrl() . '/index.php'); exit;
    } else {
        // update
        $event_date = $_POST['event_date'] ?? null;
        $region_id = intval($_POST['region_id'] ?? 0);
        $location = $_POST['location'] ?? '';
        $event_type_id = intval($_POST['event_type_id'] ?? 0);
        $sub_event_type_id = intval($_POST['sub_event_type_id'] ?? 0);
        $action_id = intval($_POST['action_id'] ?? 0);
        $source_url = trim($_POST['source_url'] ?? '');
        $notes = $_POST['notes'] ?? '';
        $fatalities = intval($_POST['fatalities'] ?? 0);
        $rating = isset($_POST['rating']) && $_POST['rating'] !== '' ? intval($_POST['rating']) : null;
        if ($rating !== null && ($rating < 1 || $rating > 10)) { $rating = null; }

        try {
            $u = $pdo->prepare('UPDATE monitored_information SET event_date = :event_date, region_id = :region_id, location = :location, event_type_id = :event_type_id, sub_event_type_id = :sub_event_type_id, action_id = :action_id, source_url = :source_url, notes = :notes, fatalities = :fatalities, rating = :rating, updated_at = NOW() WHERE id = :id');
            $u->execute([
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
              ':id'=>$id,
            ]);
            logActivity($pdo, $user['id'], 'update', 'monitored_information', $id, []);
            $successMessage = 'Record updated successfully.';
            // reload row and sub_event_types
            $stmt->execute([':id'=>$id]); $row = $stmt->fetch();
            $sub_event_types = $pdo->prepare('SELECT id,name FROM sub_event_types WHERE event_type_id = :eid ORDER BY name');
            $sub_event_types->execute([':eid'=>$row['event_type_id']]);
            $sub_event_types = $sub_event_types->fetchAll();
        } catch (PDOException $e) {
            $errorMessage = $e->getMessage();
        }
    }
}

ob_start();
?>
<div class="max-w-3xl mx-auto">
  <div class="mb-4">
    <h1 class="text-2xl font-semibold" style="color:#025529">Submission #<?= $row['id'] ?></h1>
    <div class="text-sm text-gray-600">By <?= htmlspecialchars($row['username'] . ' (' . $row['monitor_id_code'] . ')') ?> — Region: <?= htmlspecialchars($row['region_name']) ?> — Event: <?= htmlspecialchars($row['event_type_name']) ?></div>
  </div>

  <?php if ($successMessage): ?>
    <div class="mb-4 p-3 rounded bg-green-100 text-green-800"><?= htmlspecialchars($successMessage) ?></div>
  <?php endif; ?>
  <?php if ($errorMessage): ?>
    <div class="mb-4 p-3 rounded bg-red-100 text-red-800"><?= htmlspecialchars($errorMessage) ?></div>
  <?php endif; ?>

  <form method="post" class="bg-white p-6 rounded-lg shadow">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium text-gray-700">Event date</label>
        <input type="date" name="event_date" value="<?= htmlspecialchars($row['event_date']) ?>" class="mt-1 block w-full p-2 border rounded" required <?= $isOwner ? '' : 'disabled' ?> >
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Region</label>
        <select name="region_id" class="mt-1 block w-full p-2 border rounded" required <?= $isOwner ? '' : 'disabled' ?> >
          <option value="">Select</option>
          <?php foreach($regions as $r): ?>
          <option value="<?= $r['id'] ?>" <?= $r['id'] == $row['region_id'] ? 'selected' : '' ?>><?= htmlspecialchars($r['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Event type</label>
        <select id="event_type" name="event_type_id" class="mt-1 block w-full p-2 border rounded" required <?= $isOwner ? '' : 'disabled' ?> >
          <option value="">Select</option>
          <?php foreach($event_types as $et): ?>
          <option value="<?= $et['id'] ?>" <?= $et['id'] == $row['event_type_id'] ? 'selected' : '' ?>><?= htmlspecialchars($et['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Sub event type</label>
        <select id="sub_event_type" name="sub_event_type_id" class="mt-1 block w-full p-2 border rounded" data-current="<?= htmlspecialchars($row['sub_event_type_id']) ?>" required <?= $isOwner ? '' : 'disabled' ?> >
          <option value="">Select</option>
          <?php foreach($sub_event_types as $s): ?>
          <option value="<?= $s['id'] ?>" <?= $s['id'] == $row['sub_event_type_id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Action</label>
        <select name="action_id" class="mt-1 block w-full p-2 border rounded" required <?= $isOwner ? '' : 'disabled' ?> >
          <option value="">Select</option>
          <?php foreach($actions as $a): ?>
          <option value="<?= $a['id'] ?>" <?= $a['id'] == $row['action_id'] ? 'selected' : '' ?>><?= htmlspecialchars($a['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700">Location</label>
        <input name="location" value="<?= htmlspecialchars($row['location']) ?>" class="mt-1 block w-full p-2 border rounded" <?= $isOwner ? '' : 'disabled' ?> />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Source (URL)</label>
        <input name="source_url" value="<?= htmlspecialchars($row['source_url'] ?? '') ?>" placeholder="https://example.com/article" class="mt-1 block w-full p-2 border rounded" <?= $isOwner ? '' : 'disabled' ?> />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700">Fatalities</label>
        <input type="number" min="0" name="fatalities" value="<?= htmlspecialchars($row['fatalities'] ?? 0) ?>" class="mt-1 block w-full p-2 border rounded" <?= $isOwner ? '' : 'disabled' ?> />
      </div>

      <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700">Notes</label>
        <textarea name="notes" class="mt-1 block w-full p-2 border rounded" rows="4" <?= $isOwner ? '' : 'disabled' ?>><?= htmlspecialchars($row['notes']) ?></textarea>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Rating</label>
        <select name="rating" class="mt-1 block w-full p-2 border rounded" <?= $isOwner ? '' : 'disabled' ?> >
          <option value="">(none)</option>
          <?php for ($r=1;$r<=10;$r++): ?>
            <option value="<?= $r ?>" <?= ($row['rating'] !== null && (int)$row['rating'] === $r) ? 'selected' : '' ?>><?= $r ?></option>
          <?php endfor; ?>
        </select>
      </div>
    </div>

    <div class="mt-4 flex items-center justify-end gap-3">
      <a href="<?= baseUrl() ?>/user_dashboard.php" class="px-4 py-2 border rounded text-gray-700">Back</a>
      <?php if ($isOwner): ?>
        <button type="submit" name="act" value="delete" class="px-4 py-2 bg-red-600 text-white rounded" onclick="return confirm('Delete this submission?');">Delete</button>
        <button type="submit" name="act" value="update" class="px-4 py-2 rounded btn-brand text-white">Save</button>
      <?php else: ?>
        <span class="text-sm text-gray-500">You can view this submission but cannot edit or delete it.</span>
      <?php endif; ?>
    </div>
  </form>
</div>

<script>
document.getElementById('event_type').addEventListener('change', async (e) => {
  const id = e.target.value;
  const sel = document.getElementById('sub_event_type');
  sel.innerHTML = '<option>Loading...</option>';
  const res = await fetch('<?= dirname(baseUrl()) ?>/api/taxonomy.php?type=sub_event_types&event_type_id=' + encodeURIComponent(id));
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
// if requested as modal-only via AJAX or modal param, return appropriately
if ($isAjax && $_SERVER['REQUEST_METHOD'] === 'POST') {
  header('Content-Type: application/json');
  echo json_encode(['success'=>true, 'html'=>$slot, 'message'=>$successMessage]);
  exit;
}

if (isset($_GET['modal'])) {
  echo $slot;
  exit;
}

include view_path('views/layout.php');
?>
