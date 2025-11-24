<?php
require_once __DIR__ . '/../includes/init.php';
header('Content-Type: application/json');

$id = intval($_POST['id'] ?? 0);
if (!$id || !isset($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['error'=>'unauth']); exit; }

// allow user to request resend for themselves or superadmin to request for others
if ($_SESSION['user_id'] !== $id) {
    $actorStmt = $pdo->prepare("SELECT role FROM users WHERE id = :id");
    $actorStmt->execute([':id'=>$_SESSION['user_id']]);
    $actor = $actorStmt->fetchColumn();
    if ($actor !== 'superadmin') { http_response_code(403); echo json_encode(['error'=>'forbidden']); exit; }
}

$stmt = $pdo->prepare("SELECT email, monitor_id_code FROM users WHERE id = :id LIMIT 1");
$stmt->execute([':id'=>$id]);
$row = $stmt->fetch();
if (!$row || empty($row['email'])) { echo json_encode(['success'=>false]); exit; }

require_once __DIR__ . '/../includes/helpers.php';
$sent = sendVerificationEmail($pdo, $id, $row['email'], $row['monitor_id_code']);
echo json_encode(['success' => (bool)$sent]);
