<?php
require_once __DIR__ . '/../includes/init.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$username || !$password) {
        $errors[] = 'Username and password required';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id, password_hash, role, email, email_verified, status FROM users WHERE username = :username LIMIT 1");
        $stmt->execute([':username' => $username]);
        $u = $stmt->fetch();

        if ($u && password_verify($password, $u['password_hash'])) {

          // if user has an email and it's not verified, block login
          if (!empty($u['email']) && empty($u['email_verified'])) {
            $errors[] = 'Please verify your email before logging in. Check your inbox or contact your admin.';
          } elseif (isset($u['status']) && $u['status'] !== 'approved') {
            $errors[] = 'Your account is pending approval. An admin must approve your account before you can log in.';
          } else {
            // store session
            $_SESSION['user_id'] = $u['id'];
            $_SESSION['role'] = $u['role'];

            // log activity
            logActivity($pdo, $u['id'], 'login', null, null, ['username' => $username]);

            // redirect to project dashboard
            header('Location: ' . baseUrl() . '/index.php');
            exit;
          }

        } else {
            $errors[] = 'Invalid credentials';
        }
    }
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<link rel="icon" type="image/png" sizes="32x32" href="/img/smalllogo.png?v=3">
<link rel="icon" type="image/png" sizes="16x16" href="/img/smalllogo.png?v=3">
<link rel="apple-touch-icon" href="/img/smalllogo.png?v=3">
<link rel="shortcut icon" href="/img/smalllogo.png?v=3">

  <title>Login - CCSPS TRACKING TOOL</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script>
    // Forgot password modal handling — attach after DOM is ready
    document.addEventListener('DOMContentLoaded', function(){
      const forgotBtn = document.getElementById('forgotBtn');
      const modal = document.getElementById('forgotModal');
      const close = document.getElementById('forgotClose');
      const cancel = document.getElementById('forgotCancel');
      const form = document.getElementById('forgotForm');
      const msg = document.getElementById('forgotMsg');
      function hide() { if (modal) { modal.classList.add('hidden'); modal.classList.remove('flex'); } if (msg) { msg.innerText = ''; } if (form) { form.reset(); } }
      function show() { if (modal) { modal.classList.remove('hidden'); modal.classList.add('flex'); } }
      if (forgotBtn && modal && form) {
        forgotBtn.addEventListener('click', (e)=>{ e.preventDefault(); show(); });
        if (close) close.addEventListener('click', hide);
        if (cancel) cancel.addEventListener('click', hide);
        modal.addEventListener('click', (ev)=>{ if (ev.target === modal) hide(); });
        form.addEventListener('submit', async (ev)=>{
          ev.preventDefault(); if (msg) msg.innerText = 'Sending...';
          const data = { email: form.email.value.trim() };
          try {
            const res = await fetch('<?= dirname(baseUrl()) ?>/api/request_reset.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(data)});
            const j = await res.json();
            if (j && j.success) {
              if (msg) { msg.className = 'mb-2 text-green-600'; msg.innerText = 'If an account exists, a reset link was sent.'; }
            } else {
              if (msg) { msg.className = 'mb-2 text-red-600'; msg.innerText = 'Request failed. Try again later.'; }
            }
          } catch (err) {
            if (msg) { msg.className = 'mb-2 text-red-600'; msg.innerText = 'Request failed.'; }
          }
        });
      }
    });
    </script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">

  <div class="w-full max-w-md bg-white p-6 rounded shadow">
    <div class="flex flex-col items-center mb-4">
      <img src="/img/CECOE-logo.png" alt="CECOE" class="h-12 md:h-14 w-auto mb-3" />
      <h1 class="text-lg md:text-xl font-semibold text-center" style="color:#025529">CECOE’s Civic Spacescanning (CCSPS) tracking tool</h1>
    </div>

    <?php if (!empty($errors)): ?>
      <div class="mb-3 text-red-600">
        <?= htmlspecialchars(implode(', ', $errors)) ?>
      </div>
    <?php endif; ?>

    <form method="post">
      <label class="block mb-2">Username
        <input name="username" class="w-full p-2 border rounded" />
      </label>

      <label class="block mb-4">Password
        <input name="password" type="password" class="w-full p-2 border rounded" />
      </label>

      <button class="w-full p-2 rounded bg-green-700 hover:bg-green-800 text-white font-semibold">
        Login
      </button>
    </form>
    <div class="mt-3 text-center">
      <div class="space-x-3">
        <button id="forgotBtn" class="text-sm text-blue-600 hover:underline">Forgot password?</button>
        <a href="register.php" class="text-sm text-green-700 hover:underline">Create account</a>
      </div>
    </div>

    <!-- Forgot password modal -->
    <div id="forgotModal" class="fixed inset-0 bg-black bg-opacity-40 hidden items-center justify-center p-4">
      <div class="bg-white rounded shadow w-full max-w-sm p-4">
        <div class="flex justify-between items-center mb-3">
          <h3 class="font-semibold">Reset password</h3>
          <button id="forgotClose" class="text-gray-500">✕</button>
        </div>
        <div id="forgotMsg" class="mb-2 text-sm"></div>
        <form id="forgotForm">
          <label class="block mb-2">Email
            <input name="email" type="email" required class="w-full p-2 border rounded" />
          </label>
          <div class="flex items-center gap-2">
            <button type="submit" class="px-3 py-2 rounded btn-brand text-white">Send reset link</button>
            <button type="button" id="forgotCancel" class="px-3 py-2 rounded border">Cancel</button>
          </div>
        </form>
      </div>
    </div>

  </div>

</body>
</html>
