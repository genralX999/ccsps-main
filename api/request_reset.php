<?php
require_once __DIR__ . '/../includes/init.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$email = trim($input['email'] ?? '');
if (!$email) { http_response_code(400); echo json_encode(['error'=>'email required']); exit; }

$stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
$stmt->execute([':email'=>$email]);
$id = $stmt->fetchColumn();

// Always return success to avoid user enumeration
if (!$id) { echo json_encode(['success'=>true]); exit; }

require_once __DIR__ . '/../includes/helpers.php';
$sent = sendResetEmail($pdo, (int)$id, $email);
echo json_encode(['success' => (bool)$sent]);
