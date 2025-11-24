<?php
require_once __DIR__ . '/../includes/init.php';
// Only superadmin can create users in this endpoint (simple check)
if (!isset($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['error'=>'unauth']); exit; }
$actorStmt = $pdo->prepare("SELECT role FROM users WHERE id = :id");
$actorStmt->execute([':id'=>$_SESSION['user_id']]);
$actor = $actorStmt->fetchColumn();
if ($actor !== 'superadmin') { http_response_code(403); echo json_encode(['error'=>'forbidden']); exit; }

$data = json_decode(file_get_contents('php://input'), true);
header('Content-Type: application/json');
$username = trim($data['username'] ?? '');
$email = trim($data['email'] ?? ''); // optional now; may be empty
$password = trim($data['password'] ?? 'password');

if (!$username) { http_response_code(400); echo json_encode(['error'=>'username required']); exit; }

$cfg = isset($config) ? $config : (require __DIR__ . '/../includes/config.php');
$gen = generateMonitorId($pdo, $cfg);
if (empty($gen) || !is_array($gen)) {
    $maxAllowed = intval($cfg['site']['monitor_max'] ?? 99);
    http_response_code(400);
    echo json_encode(['error' => "No available monitor numbers (limit {$maxAllowed})."]);
    exit;
}
$monitor_number = $gen['monitor_number'];
$monitor_id_code = $gen['monitor_id_code'];
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost'=>12]);

$sql = "INSERT INTO users (monitor_number, monitor_id_code, username, email, password_hash, role, status, created_at, updated_at)
        VALUES (:monitor_number, :monitor_id_code, :username, :email, :password_hash, :role, :status, NOW(), NOW())";
$stmt = $pdo->prepare($sql);
try {
    $stmt->execute([
        ':monitor_number'=>$monitor_number, ':monitor_id_code'=>$monitor_id_code,
        ':username'=>$username, ':email'=>$email, ':password_hash'=>$hash,
        ':role'=>$data['role'] ?? 'user', ':status'=>'approved'
    ]);
    $newId = $pdo->lastInsertId();
    // send verification email if email provided
    $verification_sent = false;
    if (!empty($email)) {
        require_once __DIR__ . '/../includes/helpers.php';
        $verification_sent = (bool) sendVerificationEmail($pdo, (int)$newId, $email, $monitor_id_code);
    }

    logActivity($pdo, $_SESSION['user_id'], 'create', 'users', $newId, ['monitor_id_code'=>$monitor_id_code, 'username'=>$username, 'verification_sent'=>$verification_sent]);
    echo json_encode(['success'=>true, 'id'=>$newId, 'monitor_id_code'=>$monitor_id_code, 'verification_sent'=>$verification_sent]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error'=>$e->getMessage()]);
}
