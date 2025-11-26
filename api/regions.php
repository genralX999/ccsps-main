<?php
require_once __DIR__ . '/../includes/init.php';
header('Content-Type: application/json');

// Require auth for all region operations
if (!isset($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['error'=>'unauth']); exit; }

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'GET') {
    $rows = $pdo->query('SELECT id, name, is_active FROM regions ORDER BY name')->fetchAll();
    echo json_encode($rows); exit;
}

// Non-GET methods require superadmin
$actorStmt = $pdo->prepare("SELECT role FROM users WHERE id = :id");
$actorStmt->execute([':id'=>$_SESSION['user_id']]);
$actor = $actorStmt->fetchColumn();
if ($actor !== 'superadmin') { http_response_code(403); echo json_encode(['error'=>'forbidden']); exit; }

$data = json_decode(file_get_contents('php://input'), true) ?: [];

if ($method === 'POST') {
    $name = trim($data['name'] ?? '');
    if (!$name) { http_response_code(400); echo json_encode(['error'=>'name required']); exit; }
    // duplicate name check (case-insensitive)
    $chk = $pdo->prepare('SELECT COUNT(*) FROM regions WHERE LOWER(name) = LOWER(:name)');
    $chk->execute([':name'=>$name]);
    if ($chk->fetchColumn()) { http_response_code(400); echo json_encode(['error'=>'name_exists']); exit; }

    $stmt = $pdo->prepare('INSERT INTO regions (name, is_active, created_at, updated_at) VALUES (:name, 1, NOW(), NOW())');
    $stmt->execute([':name'=>$name]); $id = $pdo->lastInsertId();
    logActivity($pdo, $_SESSION['user_id'], 'create', 'regions', $id, ['name'=>$name]);
    echo json_encode(['success'=>true, 'id'=>$id]); exit;
}

if ($method === 'PUT') {
    $id = intval($data['id'] ?? 0);
    if (!$id) { http_response_code(400); echo json_encode(['error'=>'id_required']); exit; }
    if (isset($data['is_active'])) {
        $isActive = intval($data['is_active']) ? 1 : 0;
        $stmt = $pdo->prepare('UPDATE regions SET is_active = :ia, updated_at = NOW() WHERE id = :id');
        $stmt->execute([':ia'=>$isActive, ':id'=>$id]);
        logActivity($pdo, $_SESSION['user_id'], 'update_status', 'regions', $id, ['is_active'=>$isActive]);
        echo json_encode(['success'=>true]); exit;
    }
    $name = trim($data['name'] ?? '');
    if ($name === '') { http_response_code(400); echo json_encode(['error'=>'name_required']); exit; }
    // duplicate name check excluding current id
    $chk = $pdo->prepare('SELECT COUNT(*) FROM regions WHERE LOWER(name) = LOWER(:name) AND id != :id');
    $chk->execute([':name'=>$name, ':id'=>$id]);
    if ($chk->fetchColumn()) { http_response_code(400); echo json_encode(['error'=>'name_exists']); exit; }

    $stmt = $pdo->prepare('UPDATE regions SET name = :name, updated_at = NOW() WHERE id = :id');
    $stmt->execute([':name'=>$name, ':id'=>$id]);
    logActivity($pdo, $_SESSION['user_id'], 'update', 'regions', $id, ['name'=>$name]);
    echo json_encode(['success'=>true]); exit;
}

if ($method === 'DELETE') {
    $id = intval($data['id'] ?? 0);
    if (!$id) { http_response_code(400); echo json_encode(['error'=>'id_required']); exit; }
    $stmt = $pdo->prepare('UPDATE regions SET is_active = 0, updated_at = NOW() WHERE id = :id');
    $stmt->execute([':id'=>$id]);
    logActivity($pdo, $_SESSION['user_id'], 'delete', 'regions', $id, []);
    echo json_encode(['success'=>true]); exit;
}

http_response_code(405);
