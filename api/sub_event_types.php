<?php
require_once __DIR__ . '/../includes/init.php';
$event_type_id = isset($_GET['event_type_id']) ? intval($_GET['event_type_id']) : null;
if (!$event_type_id) { echo json_encode([]); exit; }
$stmt = $pdo->prepare('SELECT id, name FROM sub_event_types WHERE event_type_id = :eid AND is_active = 1 ORDER BY name');
$stmt->execute([':eid'=>$event_type_id]);
$rows = $stmt->fetchAll();
header('Content-Type: application/json');
echo json_encode($rows);
