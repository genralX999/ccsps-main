<?php
require_once __DIR__ . '/../includes/init.php';
requireAuth();

$user = currentUser($pdo);
$errors = [];
$success = false;

if (!$user) {
    // Just in case
    header('Location: /login.php'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    // Fetch stored hash
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $user['id']]);
    $row = $stmt->fetch();
    $stored = $row['password_hash'] ?? null;

    if (!$stored || !verifyPassword($current, $stored)) {
        $errors[] = 'Current password is incorrect.';
    }

    if (strlen($new) < 8) {
        $errors[] = 'New password must be at least 8 characters.';
    }

    if ($new !== $confirm) {
        $errors[] = 'New password and confirmation do not match.';
    }

    if (empty($errors)) {
        $hash = passwordHash($new);
        $u = $pdo->prepare("UPDATE users SET password_hash = :hash, updated_at = NOW() WHERE id = :id");
        $u->execute([':hash' => $hash, ':id' => $user['id']]);

        // Log activity
        logActivity($pdo, $user['id'], 'change_password', 'users', $user['id'], []);

        $success = true;
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Profile - CCSPS Tracking Tool</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" sizes="32x32" href="/img/smalllogo.png?v=3">
    <link rel="icon" type="image/png" sizes="16x16" href="/img/smalllogo.png?v=3">
    <link rel="apple-touch-icon" href="/img/smalllogo.png?v=3">
    <link rel="shortcut icon" href="/img/smalllogo.png?v=3">

    <!-- Site uses Tailwind via CDN on public pages -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/public/js/quickview.css"> <!-- optional existing helper CSS -->
</head>
<body class="bg-gray-100 min-h-screen">
<?php include __DIR__ . '/../views/navbar.php'; ?>

<main class="max-w-4xl mx-auto mt-24 p-4">
  <div class="bg-white p-6 rounded shadow">
    <h2 class="text-2xl font-semibold mb-3" style="color:#025529">Profile</h2>

    <div class="mb-4">
      <p><strong>Monitor ID:</strong> <?= htmlspecialchars($user['monitor_id_code'] ?? '') ?></p>
      <p><strong>Username:</strong> <?= htmlspecialchars($user['username'] ?? '') ?></p>
    </div>

    <hr class="my-4" />

    <h3 class="text-lg font-medium mb-3">Change password</h3>

    <?php if (!empty($errors)): ?>
      <div class="mb-3 text-red-600">
        <?php foreach ($errors as $e): ?>
          <div><?= htmlspecialchars($e) ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="mb-3 text-green-600">Password changed successfully.</div>
    <?php endif; ?>

    <form method="post" class="space-y-4">
      <label class="block">
        <span class="text-sm font-medium">Current password</span>
        <input type="password" name="current_password" required class="mt-1 w-full p-2 border rounded" />
      </label>

      <label class="block">
        <span class="text-sm font-medium">New password</span>
        <input type="password" name="new_password" required class="mt-1 w-full p-2 border rounded" />
      </label>

      <label class="block">
        <span class="text-sm font-medium">Confirm new password</span>
        <input type="password" name="confirm_password" required class="mt-1 w-full p-2 border rounded" />
      </label>

      <div>
        <button type="submit" class="px-4 py-2 rounded bg-green-700 hover:bg-green-800 text-white font-semibold">Change password</button>
      </div>
    </form>
  </div>
</main>

</body>
</html>
