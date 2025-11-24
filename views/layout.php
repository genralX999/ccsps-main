<?php
if (!isset($page_title)) $page_title = 'CECOE Monitoring';
$config = isset($config) ? $config : (require __DIR__ . '/../includes/config.php');
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <link rel="icon" type="image/png" sizes="32x32" href="/img/smalllogo.png?v=3">
<link rel="icon" type="image/png" sizes="16x16" href="/img/smalllogo.png?v=3">
<link rel="apple-touch-icon" href="/img/smalllogo.png?v=3">
<link rel="shortcut icon" href="/img/smalllogo.png?v=3">
  <title><?= htmlspecialchars($page_title) ?></title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
 

  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    /* Utility for the project's brand buttons — avoids Tailwind arbitrary color syntax */
    .btn-brand { background: #025529; color: #fff; }
    .btn-brand:focus { outline: 2px solid rgba(2,85,41,0.4); outline-offset: 2px; }
  </style>
</head>
<body class="bg-gray-50 min-h-screen pt-16">
  <?php include __DIR__ . '/navbar.php'; ?>
  <main class="p-6">
    <?= $slot ?? '' ?>
  </main>
</body>
</html>
