<?php
require_once __DIR__ . '/../includes/init.php';
$errors = [];
$success = false;

$id = intval($_GET['id'] ?? 0);
$token = $_GET['token'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $token = $_POST['token'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($new !== $confirm) $errors[] = 'Passwords do not match';
    if (strlen($new) < 8) $errors[] = 'Password must be at least 8 characters';

    if (empty($errors)) {
        $hash = hash('sha256', $token);
        $stmt = $pdo->prepare("SELECT reset_expires FROM users WHERE id = :id AND reset_token_hash = :h LIMIT 1");
        $stmt->execute([':id'=>$id, ':h'=>$hash]);
        $exp = $stmt->fetchColumn();
        if (!$exp || new DateTime() > new DateTime($exp)) {
            $errors[] = 'Invalid or expired token';
        } else {
            $newHash = password_hash($new, PASSWORD_BCRYPT, ['cost'=>12]);
            $u = $pdo->prepare("UPDATE users SET password_hash = :ph, reset_token_hash = NULL, reset_expires = NULL WHERE id = :id");
            $u->execute([':ph'=>$newHash, ':id'=>$id]);
            logActivity($pdo, $id, 'reset_password', 'users', $id, []);
            $success = true;
        }
    }
}
?>
<!doctype html><html><head>
<meta charset="utf-8"><title>Reset password</title>
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head><body class="bg-gray-100 min-h-screen flex items-center justify-center">
<div class="w-full max-w-md bg-white p-6 rounded shadow">
  <h1 class="text-lg font-semibold mb-4">Reset password</h1>
  <?php if ($success): ?>
    <div class="text-green-600">Password changed. <a href="<?= baseUrl() ?>/login.php">Login</a></div>
  <?php else: ?>
    <?php if ($errors): foreach($errors as $e): ?>
      <div class="text-red-600 mb-2"><?= htmlspecialchars($e) ?></div>
    <?php endforeach; endif; ?>
    <form method="post">
      <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">
      <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
      <label class="block mb-2">New password
        <input type="password" name="new_password" class="w-full p-2 border rounded" required>
      </label>
      <label class="block mb-4">Confirm
        <input type="password" name="confirm_password" class="w-full p-2 border rounded" required>
      </label>
      <button class="w-full p-2 rounded bg-green-700 text-white">Set password</button>
    </form>
  <?php endif; ?>
</div>
</body></html>
