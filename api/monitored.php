<?php
require_once __DIR__ . '/../includes/init.php';
$region = isset($_GET['region_id']) ? intval($_GET['region_id']) : null;
$event_type = isset($_GET['event_type_id']) ? intval($_GET['event_type_id']) : null;
$user = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;
$page = max(1, intval($_GET['page'] ?? 1));
$exportCsv = isset($_GET['export']) && $_GET['export'] === 'csv';
$per = 15;
$offset = ($page - 1) * $per;

$where = "mi.is_deleted = 0";
$params = [];

if ($region) { $where .= " AND mi.region_id = :region"; $params[':region'] = $region; }
if ($event_type) { $where .= " AND mi.event_type_id = :etype"; $params[':etype'] = $event_type; }
if ($user) { $where .= " AND mi.user_id = :user"; $params[':user'] = $user; }

// prepare query with sub event alias 'se' to avoid reserved word
$sql = "SELECT mi.*, r.name as region_name, et.name as event_type_name, se.name AS sub_event_name, a.name AS action_name, u.monitor_id_code, u.username
  FROM monitored_information mi
  JOIN regions r ON mi.region_id = r.id
  JOIN event_types et ON mi.event_type_id = et.id
  JOIN sub_event_types se ON mi.sub_event_type_id = se.id
  JOIN actions a ON mi.action_id = a.id
  JOIN users u ON mi.user_id = u.id
  WHERE $where
   ORDER BY mi.id DESC

  ";

// If not exporting, apply pagination
if (!$exportCsv) {
  $sql .= " LIMIT :limit OFFSET :offset";
}

$stmt = $pdo->prepare($sql);
foreach ($params as $k=>$v) $stmt->bindValue($k,$v);
if (!$exportCsv) {
  $stmt->bindValue(':limit', $per, PDO::PARAM_INT);
  $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
}
$stmt->execute();
$rows = $stmt->fetchAll();

// If exporting to CSV, stream the results as a downloadable file
if ($exportCsv) {
  // prepare CSV headers
  $filename = 'monitored_export_' . date('Y-m-d') . '.csv';
  header('Content-Type: text/csv; charset=UTF-8');
  header('Content-Disposition: attachment; filename="' . $filename . '"');
  // output UTF-8 BOM for Excel compatibility
  echo "\xEF\xBB\xBF";
  $out = fopen('php://output','w');
  // column headers
  fputcsv($out, ['ID','Monitor ID','Event Date','Region','Location','Event Type','Sub Event','Action','Source URL','Notes','Fatalities','Rating','Encoder']);
  foreach ($rows as $r) {
    fputcsv($out, [
      $r['id'],
      $r['monitor_id_code'] ?? '',
      $r['event_date'] ?? '',
      $r['region_name'] ?? '',
      $r['location'] ?? '',
      $r['event_type_name'] ?? '',
      $r['sub_event_name'] ?? '',
      $r['action_name'] ?? '',
      $r['source_url'] ?? '',
      $r['notes'] ?? '',
      $r['fatalities'] ?? '',
      $r['rating'] ?? '',
      ($r['username'] ?? '')
    ]);
  }
  fclose($out);
  exit;
}

// total count for pagination
$countSql = "SELECT COUNT(*) FROM monitored_information mi WHERE $where";
$countStmt = $pdo->prepare($countSql);
foreach ($params as $k=>$v) $countStmt->bindValue($k,$v);
$countStmt->execute();
$total = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $per));

ob_start();
?>
<div class="overflow-x-auto">
<table class="min-w-full border border-gray-200 border-collapse text-sm md:text-base rounded">
  <thead class="bg-gray-100">
    <tr>
      <th class="px-4 py-3 text-left text-xs md:text-sm font-medium text-gray-600 uppercase border-b border-gray-300">Monitor ID</th>
      <th class="px-4 py-3 text-left text-xs md:text-sm font-medium text-gray-600 uppercase border-b border-gray-300">Event date</th>
      <th class="px-4 py-3 text-left text-xs md:text-sm font-medium text-gray-600 uppercase border-b border-gray-300">Region</th>
      <th class="px-4 py-3 text-left text-xs md:text-sm font-medium text-gray-600 uppercase border-b border-gray-300">Location</th>
      <th class="px-4 py-3 text-left text-xs md:text-sm font-medium text-gray-600 uppercase border-b border-gray-300">Event type</th>
      <th class="px-4 py-3 text-left text-xs md:text-sm font-medium text-gray-600 uppercase border-b border-gray-300">Sub event</th>
      <th class="px-4 py-3 text-left text-xs md:text-sm font-medium text-gray-600 uppercase border-b border-gray-300">Action</th>
      <th class="px-4 py-3 text-left text-xs md:text-sm font-medium text-gray-600 uppercase border-b border-gray-300">Source</th>
      <th class="px-4 py-3 text-left text-xs md:text-sm font-medium text-gray-600 uppercase border-b border-gray-300">Notes</th>
      <th class="px-4 py-3 text-left text-xs md:text-sm font-medium text-gray-600 uppercase border-b border-gray-300">Fatalities</th>
      <th class="px-4 py-3 text-left text-xs md:text-sm font-medium text-gray-600 uppercase border-b border-gray-300">Rating</th>
      <th class="px-4 py-3 text-left text-xs md:text-sm font-medium text-gray-600 uppercase border-b border-gray-300">Actions</th>
    </tr>
  </thead>
  <tbody class="bg-white">
    <?php foreach($rows as $r): ?>
    <tr class="odd:bg-white even:bg-gray-50 hover:bg-gray-100 align-top">
      <td class="px-4 py-3 text-gray-700 whitespace-normal break-words border-b border-gray-100"><?= htmlspecialchars($r['monitor_id_code']) ?></td>
      <td class="px-4 py-3 text-gray-700 whitespace-normal break-words border-b border-gray-100"><?= htmlspecialchars($r['event_date']) ?></td>
      <td class="px-4 py-3 text-gray-700 whitespace-normal break-words border-b border-gray-100"><?= htmlspecialchars($r['region_name']) ?></td>
      <td class="px-4 py-3 text-gray-700 whitespace-normal break-words border-b border-gray-100"><?= htmlspecialchars($r['location']) ?></td>
      <td class="px-4 py-3 text-gray-700 whitespace-normal break-words border-b border-gray-100"><?= htmlspecialchars($r['event_type_name']) ?></td>
      <td class="px-4 py-3 text-gray-700 whitespace-normal break-words border-b border-gray-100"><?= htmlspecialchars($r['sub_event_name']) ?></td>
      <td class="px-4 py-3 text-gray-700 whitespace-normal break-words border-b border-gray-100"><?= htmlspecialchars($r['action_name']) ?></td>
      <td class="px-4 py-3 text-blue-600 whitespace-normal break-words border-b border-gray-100"><?php if ($r['source_url']): $su = htmlspecialchars($r['source_url']); ?><a href="<?= $su ?>" target="_blank" rel="noopener noreferrer">Source</a><?php endif; ?></td>
      <td class="px-4 py-3 text-gray-700 break-words max-w-xs border-b border-gray-100" style="max-width:340px;">
        <?php
          $notes = $r['notes'] ?? '';
          $short = mb_strlen($notes) > 120 ? htmlspecialchars(mb_substr($notes,0,120)) . '...' : htmlspecialchars($notes);
          $full = nl2br(htmlspecialchars($notes));
        ?>
        <span class="note-short"><?= $short ?></span>
        <span class="note-full hidden"><?= $full ?></span>
        <?php if (mb_strlen($notes) > 120): ?>
          <a href="#" class="read-more ml-2 text-sm text-blue-600">Read more</a>
        <?php endif; ?>
      </td>
      <td class="px-4 py-3 text-gray-700 border-b border-gray-100"><?= htmlspecialchars($r['fatalities']) ?></td>
      <td class="px-4 py-3 text-gray-700 border-b border-gray-100"><?= htmlspecialchars($r['rating']) ?></td>
      <td class="px-4 py-3 text-blue-600 border-b border-gray-100">
        <a href="<?= rtrim(dirname(baseUrl()), '/') ?>/public/monitored_view.php?id=<?= $r['id'] ?>" class="text-sm">View</a>
        <button class="ml-2 open-modal text-xs text-gray-600 hover:text-gray-800" data-id="<?= $r['id'] ?>"></button>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php if ($total > $per): ?>
  <div class="mt-3 flex items-center justify-end gap-2">
    <?php if ($page > 1): ?>
      <button class="pagination-btn px-3 py-1 border rounded" data-page="<?= $page-1 ?>">Previous</button>
    <?php endif; ?>
    <?php
      for ($p = 1; $p <= $totalPages; $p++) {
        if ($p == 1 || $p == $totalPages || ($p >= $page - 2 && $p <= $page + 2)) {
          $cls = $p == $page ? 'bg-gray-200 px-2 py-1 rounded' : 'px-2 py-1 border rounded';
          echo "<button class=\"pagination-btn $cls\" data-page=\"$p\">$p</button>";
        } else if ($p == $page - 3 || $p == $page + 3) {
          echo '<span class="px-2">...</span>';
        }
      }
    ?>
    <?php if ($page < $totalPages): ?>
      <button class="pagination-btn px-3 py-1 border rounded" data-page="<?= $page+1 ?>">Next</button>
    <?php endif; ?>
  </div>
<?php endif; ?>
<?php
echo ob_get_clean();
