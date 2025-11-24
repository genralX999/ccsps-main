<?php
// Simple mail test page — prints PHPMailer SMTP debug inline
require_once __DIR__ . '/../includes/init.php';
$config = require __DIR__ . '/../includes/config.php';
$to = trim($_GET['to'] ?? ($_POST['to'] ?? ''));

// try to load composer autoload
$autoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload)) require_once $autoload;

$sent = null;
$debugOutput = '';

if ($to) {
    // prefer PHPMailer when available
    if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        $mailCfg = $config['mail'] ?? [];
      $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        try {
            if (!empty($mailCfg['smtp_host'])) {
                $mail->isSMTP();
                $mail->Host = $mailCfg['smtp_host'];
                $mail->SMTPAuth = true;
                $mail->Username = $mailCfg['smtp_user'] ?? '';
                $mail->Password = $mailCfg['smtp_pass'] ?? '';
                $mail->SMTPSecure = $mailCfg['smtp_secure'] ?? 'tls';
                $mail->Port = $mailCfg['smtp_port'] ?? 587;
                // collect debug output inline
                $mail->SMTPDebug = 2;
                $mail->Debugoutput = function($str, $level) use (&$debugOutput) {
                  $debugOutput .= "[level {$level}] " . $str . "\n";
                };
            }
            $mail->setFrom($mailCfg['from'] ?? 'no-reply@example.com', $mailCfg['from_name'] ?? '');
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = 'CCSPS Mail Test';
            $mail->Body = '<p>This is a test message from your CCSPS application.</p>';
            $mail->AltBody = 'This is a test message from your CCSPS application.';
            $mail->send();
            $sent = true;
        } catch (Throwable $e) {
          $sent = false;
          $debugOutput .= "PHPMailer exception: " . $e->getMessage() . "\n";
        }
    } else {
        // fallback to mail()
        $headers = 'From: ' . ($config['mail']['from'] ?? 'no-reply@example.com') . "\r\n";
        $headers .= "MIME-Version: 1.0\r\nContent-type: text/html; charset=UTF-8\r\n";
        $sent = mail($to, 'CCSPS Mail Test', '<p>Test</p>', $headers);
        if (!$sent) $debugOutput = "mail() returned false; check PHP mail configuration.";
    }
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Mail test</title>
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
  <div class="w-full max-w-lg bg-white p-6 rounded shadow">
    <h1 class="text-lg font-semibold mb-4">CCSPS Mail Test</h1>
    <p class="mb-3 text-sm text-gray-600">Enter a recipient email to send a test message (this page prints PHPMailer debug output).</p>
    <form method="post" class="mb-4">
      <label class="block mb-2">To
        <input name="to" type="email" value="<?= htmlspecialchars($to) ?>" required class="w-full p-2 border rounded" />
      </label>
      <div class="flex gap-2">
        <button class="px-3 py-2 rounded btn-brand text-white">Send test</button>
        <a class="px-3 py-2 rounded border" href="<?= htmlspecialchars(basename(__FILE__)) ?>">Clear</a>
      </div>
    </form>

    <?php if ($to): ?>
      <div class="mb-3">
        <strong>To:</strong> <?= htmlspecialchars($to) ?><br>
        <strong>Result:</strong> <?= $sent ? '<span class="text-green-600">Sent</span>' : '<span class="text-red-600">Failed</span>' ?>
      </div>
      <div class="bg-black text-white p-3 rounded text-sm whitespace-pre-wrap overflow-auto" style="max-height:320px;">
        <?= nl2br(htmlspecialchars($debugOutput)) ?>
      </div>
    <?php endif; ?>

  </div>
</body>
</html>
