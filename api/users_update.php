<?php
require_once __DIR__ . '/../includes/init.php';

header('Content-Type: application/json');

requireAuth();
$actor = currentUser($pdo);
if (!$actor || ($actor['role'] ?? '') !== 'superadmin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Forbidden']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || empty($data['id'])) {
    echo json_encode(['success' => false, 'error' => 'Missing payload']);
    exit;
}

$id = (int)$data['id'];
$fields = [];
$params = [];

// allowed updatable fields
if (isset($data['username'])) {
    $fields[] = 'username = :username';
    $params[':username'] = trim($data['username']);
}
if (isset($data['email'])) {
    $fields[] = 'email = :email';
    $params[':email'] = trim($data['email']);
}
if (isset($data['role'])) {
    $fields[] = 'role = :role';
    $params[':role'] = $data['role'];
}
if (isset($data['disabled'])) {
    $fields[] = 'disabled = :disabled';
    $params[':disabled'] = $data['disabled'] ? 1 : 0;
}

try {
    if (isset($params[':username'])) {
        // check username unique
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = :username AND id != :id');
        $stmt->execute([':username' => $params[':username'], ':id' => $id]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Username already taken']);
            exit;
        }
    }
    if (isset($params[':email']) && $params[':email'] !== '') {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email AND id != :id');
        $stmt->execute([':email' => $params[':email'], ':id' => $id]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Email already in use']);
            exit;
        }
    }

    if (!empty($fields)) {
        $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $params[':id'] = $id;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }

    // handle password separately if provided
    if (!empty($data['password'])) {
        $pwHash = password_hash($data['password'], PASSWORD_BCRYPT);
        $stmt = $pdo->prepare('UPDATE users SET password = :pw WHERE id = :id');
        $stmt->execute([':pw' => $pwHash, ':id' => $id]);
    }

    // return updated row
    $stmt = $pdo->prepare('SELECT id, username, email, role, monitor_id_code, disabled FROM users WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'user' => $row]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

