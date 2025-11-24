<?php
require_once __DIR__ . '/../includes/init.php';

$id = intval($_GET['id'] ?? 0);
$token = $_GET['token'] ?? '';

if (!$id || !$token) { http_response_code(400); echo 'Invalid request'; exit; }

$hash = hash('sha256', $token);
$stmt = $pdo->prepare("SELECT verification_expires, status FROM users WHERE id = :id AND verification_token_hash = :h LIMIT 1");
$stmt->execute([':id'=>$id, ':h'=>$hash]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) { echo '<!doctype html><html><head><meta charset="utf-8"><title>Verification</title></head><body><div style="max-width:600px;margin:40px auto;font-family:Arial, sans-serif">Invalid or already used token.</div></body></html>'; exit; }
if (new DateTime() > new DateTime($row['verification_expires'])) { echo '<!doctype html><html><head><meta charset="utf-8"><title>Verification</title></head><body><div style="max-width:600px;margin:40px auto;font-family:Arial, sans-serif">Token expired.</div></body></html>'; exit; }

$u = $pdo->prepare("UPDATE users SET email_verified = 1, verification_token_hash = NULL, verification_expires = NULL WHERE id = :id");
$u->execute([':id'=>$id]);
logActivity($pdo, $id, 'verify_email', 'users', $id, []);

$status = $row['status'] ?? 'pending';
// show nice HTML message
if ($status === 'approved') {
	$msg = 'Email verified. Your account is approved — you may now <a href="' . baseUrl() . '/login.php">log in</a>.';
} else {
	$msg = 'Email verified. Thank you — your account is awaiting approval by an administrator. You will be notified when approved.';
}

echo '<!doctype html><html><head><meta charset="utf-8"><title>Verification</title><link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet"></head><body class="bg-gray-100 min-h-screen flex items-center justify-center"><div class="w-full max-w-lg bg-white p-6 rounded shadow text-center"><h1 class="text-xl font-semibold mb-3">Verification complete</h1><p class="mb-4">'. $msg .'</p><div><a class="px-3 py-2 rounded bg-green-700 text-white" href="' . baseUrl() . '/login.php">Go to login</a></div></div></body></html>';
