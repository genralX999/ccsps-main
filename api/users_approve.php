<?php
require_once __DIR__ . '/../includes/init.php';
header('Content-Type: application/json');

requireAuth();
$actor = currentUser($pdo);
if (!$actor || ($actor['role'] ?? '') !== 'superadmin') {
    http_response_code(403); echo json_encode(['success'=>false,'error'=>'forbidden']); exit;
}

$id = intval($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';
if (!$id || !in_array($action, ['approve','decline'], true)) { echo json_encode(['success'=>false,'error'=>'invalid']); exit; }

try {
    $status = $action === 'approve' ? 'approved' : 'declined';
    $stmt = $pdo->prepare('UPDATE users SET status = :s, updated_at = NOW() WHERE id = :id');
    $stmt->execute([':s'=>$status, ':id'=>$id]);
    logActivity($pdo, $actor['id'], 'user_' . $action, 'users', $id, ['status'=>$status]);
    echo json_encode(['success'=>true,'status'=>$status]);
} catch (Exception $e) {
    http_response_code(500); echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}
