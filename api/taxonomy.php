<?php
require_once __DIR__ . '/../includes/init.php';
header('Content-Type: application/json');

// Must be authenticated
if (!isset($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['error'=>'unauth']); exit; }

$method = $_SERVER['REQUEST_METHOD'];
// Any non-GET method requires superadmin
if ($method !== 'GET') {
    $actorStmt = $pdo->prepare("SELECT role FROM users WHERE id = :id");
    $actorStmt->execute([':id'=>$_SESSION['user_id']]);
    $actor = $actorStmt->fetchColumn();
    if ($actor !== 'superadmin') { http_response_code(403); echo json_encode(['error'=>'forbidden']); exit; }
}

if ($method === 'GET') {
    $type = $_GET['type'] ?? null;
    if ($type === 'event_types') {
        $rows = $pdo->query('SELECT id, name FROM event_types WHERE is_active = 1 ORDER BY name')->fetchAll();
        echo json_encode($rows); exit;
    }
    if ($type === 'actions') {
        $rows = $pdo->query('SELECT id, name FROM actions WHERE is_active = 1 ORDER BY name')->fetchAll();
        echo json_encode($rows); exit;
    }
    if ($type === 'sub_event_types' && isset($_GET['event_type_id'])) {
        $eid = intval($_GET['event_type_id']);
        $stmt = $pdo->prepare('SELECT id, name FROM sub_event_types WHERE event_type_id = :eid AND is_active = 1 ORDER BY name');
        $stmt->execute([':eid'=>$eid]);
        echo json_encode($stmt->fetchAll()); exit;
    }
    // admin/all lists
    if ($type === 'event_types_all') {
        $rows = $pdo->query('SELECT id, name, is_active FROM event_types ORDER BY name')->fetchAll(); echo json_encode($rows); exit;
    }
    if ($type === 'actions_all') {
        $rows = $pdo->query('SELECT id, name, is_active FROM actions ORDER BY name')->fetchAll(); echo json_encode($rows); exit;
    }
    if ($type === 'sub_event_types_all') {
        $rows = $pdo->query('SELECT id, event_type_id, name, is_active FROM sub_event_types ORDER BY event_type_id, name')->fetchAll(); echo json_encode($rows); exit;
    }
    http_response_code(400); echo json_encode(['error'=>'invalid_request']); exit;
}

// Read input JSON for non-GET
$data = json_decode(file_get_contents('php://input'), true) ?: [];
$type = $data['type'] ?? null;

try {
    if ($method === 'POST') {
        $name = trim($data['name'] ?? '');
        if (!$type || !$name) { http_response_code(400); echo json_encode(['error'=>'type_and_name_required']); exit; }
        if ($type === 'action') {
            // duplicate name check (case-insensitive)
            $chk = $pdo->prepare('SELECT COUNT(*) FROM actions WHERE LOWER(name) = LOWER(:name)');
            $chk->execute([':name'=>$name]);
            if ($chk->fetchColumn()) { http_response_code(400); echo json_encode(['error'=>'name_exists']); exit; }

            $stmt = $pdo->prepare('INSERT INTO actions (name, is_active, created_at, updated_at) VALUES (:name,1,NOW(),NOW())');
            $stmt->execute([':name'=>$name]); $id = $pdo->lastInsertId();
            logActivity($pdo, $_SESSION['user_id'], 'create', 'actions', $id, ['name'=>$name]);
            echo json_encode(['success'=>true, 'id'=>$id]); exit;
        }
        if ($type === 'event_type') {
            // duplicate name check (case-insensitive)
            $chk = $pdo->prepare('SELECT COUNT(*) FROM event_types WHERE LOWER(name) = LOWER(:name)');
            $chk->execute([':name'=>$name]);
            if ($chk->fetchColumn()) { http_response_code(400); echo json_encode(['error'=>'name_exists']); exit; }

            $stmt = $pdo->prepare('INSERT INTO event_types (name, is_active, created_at, updated_at) VALUES (:name,1,NOW(),NOW())');
            $stmt->execute([':name'=>$name]); $id = $pdo->lastInsertId();
            logActivity($pdo, $_SESSION['user_id'], 'create', 'event_types', $id, ['name'=>$name]);
            echo json_encode(['success'=>true, 'id'=>$id]); exit;
        }
        if ($type === 'sub_event_type') {
            $eid = intval($data['event_type_id'] ?? 0);
            if (!$eid) { http_response_code(400); echo json_encode(['error'=>'event_type_required']); exit; }
            $exists = $pdo->prepare('SELECT COUNT(*) FROM event_types WHERE id = :id'); $exists->execute([':id'=>$eid]);
            if (!$exists->fetchColumn()) { http_response_code(400); echo json_encode(['error'=>'invalid_event_type']); exit; }
            // duplicate name check within the same event_type (case-insensitive)
            $chk = $pdo->prepare('SELECT COUNT(*) FROM sub_event_types WHERE event_type_id = :eid AND LOWER(name) = LOWER(:name)');
            $chk->execute([':eid'=>$eid, ':name'=>$name]);
            if ($chk->fetchColumn()) { http_response_code(400); echo json_encode(['error'=>'name_exists']); exit; }

            $stmt = $pdo->prepare('INSERT INTO sub_event_types (event_type_id, name, is_active, created_at, updated_at) VALUES (:eid, :name,1,NOW(),NOW())');
            $stmt->execute([':eid'=>$eid, ':name'=>$name]); $id = $pdo->lastInsertId();
            logActivity($pdo, $_SESSION['user_id'], 'create', 'sub_event_types', $id, ['name'=>$name, 'event_type_id'=>$eid]);
            echo json_encode(['success'=>true, 'id'=>$id]); exit;
        }
        http_response_code(400); echo json_encode(['error'=>'unknown_type']); exit;
    }

    if ($method === 'PUT') {
        $id = intval($data['id'] ?? 0);
        // support status updates (restore) via is_active in PUT
        if (!$id || !$type) { http_response_code(400); echo json_encode(['error'=>'id_and_type_required']); exit; }
        if (isset($data['is_active'])) {
            $isActive = intval($data['is_active']) ? 1 : 0;
            if ($type === 'action') {
                $stmt = $pdo->prepare('UPDATE actions SET is_active = :ia, updated_at = NOW() WHERE id = :id'); $stmt->execute([':ia'=>$isActive, ':id'=>$id]);
                logActivity($pdo, $_SESSION['user_id'], 'update_status', 'actions', $id, ['is_active'=>$isActive]);
                echo json_encode(['success'=>true]); exit;
            }
            if ($type === 'event_type') {
                $stmt = $pdo->prepare('UPDATE event_types SET is_active = :ia, updated_at = NOW() WHERE id = :id'); $stmt->execute([':ia'=>$isActive, ':id'=>$id]);
                logActivity($pdo, $_SESSION['user_id'], 'update_status', 'event_types', $id, ['is_active'=>$isActive]);
                echo json_encode(['success'=>true]); exit;
            }
            if ($type === 'sub_event_type') {
                $stmt = $pdo->prepare('UPDATE sub_event_types SET is_active = :ia, updated_at = NOW() WHERE id = :id'); $stmt->execute([':ia'=>$isActive, ':id'=>$id]);
                logActivity($pdo, $_SESSION['user_id'], 'update_status', 'sub_event_types', $id, ['is_active'=>$isActive]);
                echo json_encode(['success'=>true]); exit;
            }
            http_response_code(400); echo json_encode(['error'=>'unknown_type']); exit;
        }
        $name = trim($data['name'] ?? '');
        if ($name === '') { http_response_code(400); echo json_encode(['error'=>'name_required']); exit; }
        if ($type === 'action') {
            // duplicate name check excluding current id
            $chk = $pdo->prepare('SELECT COUNT(*) FROM actions WHERE LOWER(name) = LOWER(:name) AND id != :id');
            $chk->execute([':name'=>$name, ':id'=>$id]);
            if ($chk->fetchColumn()) { http_response_code(400); echo json_encode(['error'=>'name_exists']); exit; }

            $stmt = $pdo->prepare('UPDATE actions SET name = :name, updated_at = NOW() WHERE id = :id');
            $stmt->execute([':name'=>$name, ':id'=>$id]);
            logActivity($pdo, $_SESSION['user_id'], 'update', 'actions', $id, ['name'=>$name]);
            echo json_encode(['success'=>true]); exit;
        }
        if ($type === 'event_type') {
            // duplicate name check excluding current id
            $chk = $pdo->prepare('SELECT COUNT(*) FROM event_types WHERE LOWER(name) = LOWER(:name) AND id != :id');
            $chk->execute([':name'=>$name, ':id'=>$id]);
            if ($chk->fetchColumn()) { http_response_code(400); echo json_encode(['error'=>'name_exists']); exit; }

            $stmt = $pdo->prepare('UPDATE event_types SET name = :name, updated_at = NOW() WHERE id = :id');
            $stmt->execute([':name'=>$name, ':id'=>$id]);
            logActivity($pdo, $_SESSION['user_id'], 'update', 'event_types', $id, ['name'=>$name]);
            echo json_encode(['success'=>true]); exit;
        }
        if ($type === 'sub_event_type') {
            // need event_type scoping; ensure no duplicate name within same event_type excluding current id
            $evt = intval($data['event_type_id'] ?? 0);
            if ($evt) {
                $chk = $pdo->prepare('SELECT COUNT(*) FROM sub_event_types WHERE event_type_id = :eid AND LOWER(name) = LOWER(:name) AND id != :id');
                $chk->execute([':eid'=>$evt, ':name'=>$name, ':id'=>$id]);
                if ($chk->fetchColumn()) { http_response_code(400); echo json_encode(['error'=>'name_exists']); exit; }
            }

            $stmt = $pdo->prepare('UPDATE sub_event_types SET name = :name, updated_at = NOW() WHERE id = :id');
            $stmt->execute([':name'=>$name, ':id'=>$id]);
            logActivity($pdo, $_SESSION['user_id'], 'update', 'sub_event_types', $id, ['name'=>$name]);
            echo json_encode(['success'=>true]); exit;
        }
        http_response_code(400); echo json_encode(['error'=>'unknown_type']); exit;
    }

    if ($method === 'DELETE') {
        $id = intval($data['id'] ?? 0);
        if (!$id || !$type) { http_response_code(400); echo json_encode(['error'=>'id_and_type_required']); exit; }
        if ($type === 'action') {
            $stmt = $pdo->prepare('UPDATE actions SET is_active = 0, updated_at = NOW() WHERE id = :id'); $stmt->execute([':id'=>$id]);
            logActivity($pdo, $_SESSION['user_id'], 'delete', 'actions', $id, []);
            echo json_encode(['success'=>true]); exit;
        }
        if ($type === 'event_type') {
            $stmt = $pdo->prepare('UPDATE event_types SET is_active = 0, updated_at = NOW() WHERE id = :id'); $stmt->execute([':id'=>$id]);
            logActivity($pdo, $_SESSION['user_id'], 'delete', 'event_types', $id, []);
            echo json_encode(['success'=>true]); exit;
        }
        if ($type === 'sub_event_type') {
            $stmt = $pdo->prepare('UPDATE sub_event_types SET is_active = 0, updated_at = NOW() WHERE id = :id'); $stmt->execute([':id'=>$id]);
            logActivity($pdo, $_SESSION['user_id'], 'delete', 'sub_event_types', $id, []);
            echo json_encode(['success'=>true]); exit;
        }
        http_response_code(400); echo json_encode(['error'=>'unknown_type']); exit;
    }
} catch (PDOException $e) {
    http_response_code(500); echo json_encode(['error'=>$e->getMessage()]); exit;
}

http_response_code(405); echo json_encode(['error'=>'method_not_allowed']);
