<?php
require_once __DIR__ . '/../includes/init.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$username = trim($input['username'] ?? '');
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

if (!$username || !$email || !$password) { http_response_code(400); echo json_encode(['success'=>false,'error'=>'username,email,password required']); exit; }

try {
    // uniqueness checks
    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = :u LIMIT 1'); $stmt->execute([':u'=>$username]);
    if ($stmt->fetch()) { echo json_encode(['success'=>false,'error'=>'username taken']); exit; }
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :e LIMIT 1'); $stmt->execute([':e'=>$email]);
    if ($stmt->fetch()) { echo json_encode(['success'=>false,'error'=>'email already used']); exit; }

    // generate monitor id if possible
    $monitor_number = null; $monitor_code = null;
    if (function_exists('generateMonitorId')) {
        $res = generateMonitorId($pdo, require __DIR__ . '/../includes/config.php');
        if ($res) { $monitor_number = $res['monitor_number']; $monitor_code = $res['monitor_id_code']; }
    }

    // create user with status pending and email_verified = 0
    $pwHash = passwordHash($password);
    $stmt = $pdo->prepare('INSERT INTO users (monitor_number, monitor_id_code, username, email, password_hash, role, status, email_verified, created_at, updated_at) VALUES (:mn, :mc, :u, :e, :pw, :role, :status, 0, NOW(), NOW())');
    $stmt->execute([
        ':mn' => $monitor_number,
        ':mc' => $monitor_code,
        ':u' => $username,
        ':e' => $email,
        ':pw' => $pwHash,
        ':role' => 'user',
        ':status' => 'pending'
    ]);
    $id = (int)$pdo->lastInsertId();

    // send verification email
    require_once __DIR__ . '/../includes/helpers.php';
    $sent = sendVerificationEmail($pdo, $id, $email, $monitor_code);

    echo json_encode(['success'=>true,'message'=>'Registration successful; verification email sent','sent' => (bool)$sent]);
} catch (Exception $e) {
    http_response_code(500); echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}
