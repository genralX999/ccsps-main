<?php
require_once __DIR__ . '/../includes/init.php';
$user = currentUser($pdo);
?>
<nav class="fixed top-0 left-0 right-0 z-50 bg-white shadow p-4">
  <div class="max-w-6xl mx-auto flex items-center justify-between">
    <div class="flex items-center gap-4">
      <a href="<?= baseUrl() ?>/index.php" class="flex items-center gap-3">
        <img src="/img/CECOE-logo.png" alt="CECOE" class="h-10 md:h-12 w-auto" />
        <span class="font-semibold text-lg" style="color:#025529">CCSPS tracking tool</span>
      </a>
      <?php if($user): ?>
        <a href="<?= baseUrl() ?>/index.php" class="text-sm">Dashboard</a>
        <a href="<?= baseUrl() ?>/profile.php" class="text-sm">Profile</a>
        <?php if(!isset($user['role']) || $user['role'] !== 'superadmin'): ?>
          <a href="<?= baseUrl() ?>/submit_monitored.php" class="text-sm">Submit</a>
        <?php endif; ?>
        <?php if(isset($user['role']) && $user['role'] === 'superadmin'): ?>
          <a href="<?= baseUrl() ?>/superadmin_users.php" class="text-sm">Users</a>
          <a href="<?= baseUrl() ?>/superadmin_taxonomy.php" class="text-sm">Taxonomy</a>
        <?php endif; ?>
      <?php endif; ?>
    </div>
    <div>
      <?php if($user): ?>
        <span class="text-sm mr-3"><?= htmlspecialchars($user['monitor_id_code'] . ' / ' . $user['username']) ?></span>
        <a href="<?= baseUrl() ?>/logout.php" class="px-3 py-1 btn-brand text-white rounded">Logout</a>
      <?php else: ?>
        <a href="<?= baseUrl() ?>/login.php" class="px-3 py-1 btn-brand text-white rounded">Login</a>
      <?php endif; ?>
    </div>
  </div>
</nav>
