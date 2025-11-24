<?php
require_once __DIR__ . '/db.php';
$config = require __DIR__ . '/config.php';

/**
 * Automatically detects the correct base URL of the project
 * Example: /cecoe_monitoring/public
 */
function baseUrl() {
    // Detect protocol
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';

    // Detect domain
    $host = $_SERVER['HTTP_HOST'];

    // Detect directory where the project actually lives
    // Example: /public
    $script_name = $_SERVER['SCRIPT_NAME'];        // e.g. /public/login.php
    $script_dir  = dirname($script_name);          // e.g. /public

    // Ensure no backslashes
    $script_dir = str_replace('\\', '/', $script_dir);

    // If script is inside /public, return domain root
    if ($script_dir === '/public' || $script_dir === '\public') {
        return $scheme . '://' . $host . '/public';
    }

    // If script is at domain root, return the domain
    if ($script_dir === '/' || $script_dir === '.') {
        return $scheme . '://' . $host;
    }

    // Default fallback
    return $scheme . '://' . $host . $script_dir;
}


/**
 * Redirect safely inside the project
 */
function redirect($path = '/') {
    $url = baseUrl() . $path;
    header("Location: $url");
    exit;
}

function generateMonitorId(PDO $pdo, array $config) {
    // prefer assigning the smallest available number between 1 and configured max
    $maxAllowed = intval($config['site']['monitor_max'] ?? 99);
    $pad = intval($config['site']['monitor_pad'] ?? 2);
    $prefix = $config['site']['monitor_prefix'] ?? 'CCSPM';

    // fetch currently used monitor_number values within the allowed range
    $stmt = $pdo->prepare("SELECT monitor_number FROM users WHERE monitor_number BETWEEN 1 AND :max ORDER BY monitor_number ASC");
    $stmt->execute([':max' => $maxAllowed]);
    $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $used = array_map('intval', $rows);

    // find the smallest missing number in 1..maxAllowed
    $next = null;
    for ($i = 1; $i <= $maxAllowed; $i++) {
        if (!in_array($i, $used, true)) { $next = $i; break; }
    }

    // if none available within the allowed range, return null to signal caller
    if ($next === null) {
        return null;
    }

    $code = sprintf("%s%s", $prefix, str_pad($next, $pad, "0", STR_PAD_LEFT));
    return ['monitor_number' => $next, 'monitor_id_code' => $code];
}

function logActivity(PDO $pdo, ?int $user_id, string $action_type, ?string $target_table = null, $target_id = null, array $details = []) {
    $sql = "INSERT INTO activity_logs (user_id, action_type, target_table, target_id, details, ip_address, created_at, updated_at)
            VALUES (:user_id, :action_type, :target_table, :target_id, :details, :ip, NOW(), NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':user_id' => $user_id,
        ':action_type' => $action_type,
        ':target_table' => $target_table,
        ':target_id' => $target_id,
        ':details' => json_encode($details, JSON_UNESCAPED_UNICODE),
        ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
    return $pdo->lastInsertId();
}

function passwordHash(string $password) {
    $config = require __DIR__ . '/config.php';
    $cost = intval($config['security']['bcrypt_cost'] ?? 12);
    return password_hash($password, PASSWORD_BCRYPT, ['cost'=>$cost]);
}

function verifyPassword(string $password, string $hash) {
    return password_verify($password, $hash);
}

/**
 * Create a cryptographically secure random token (hex)
 */
function createToken(int $bytes = 20) {
    return bin2hex(random_bytes($bytes));
}

/**
 * Hash token for storing in DB
 */
function hashToken(string $token) {
    return hash('sha256', $token);
}

/**
 * Send email using PHPMailer (composer) with SMTP config from config.php. Falls back to mail().
 */
function sendMailSMTP(string $to, string $subject, string $bodyHtml, string $bodyText = ''): bool {
    $cfg = require __DIR__ . '/config.php';
    $mailCfg = $cfg['mail'] ?? [];
    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (file_exists($autoload)) require_once $autoload;

    if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        // fallback
        $headers = 'From: ' . ($mailCfg['from'] ?? 'no-reply@example.com') . "\r\n";
        $headers .= "MIME-Version: 1.0\r\nContent-type: text/html; charset=UTF-8\r\n";
        return (bool) mail($to, $subject, $bodyHtml, $headers);
    }

    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        if (!empty($mailCfg['smtp_host'])) {
            $mail->isSMTP();
            $mail->Host = $mailCfg['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $mailCfg['smtp_user'] ?? '';
            $mail->Password = $mailCfg['smtp_pass'] ?? '';
            $mail->SMTPSecure = $mailCfg['smtp_secure'] ?? 'tls';
            $mail->Port = $mailCfg['smtp_port'] ?? 587;
            // optional debug if explicitly enabled in config
            if (!empty($mailCfg['debug'])) {
                $mail->SMTPDebug = 2;
                $mail->Debugoutput = function($str, $level) {
                    error_log("PHPMailer debug level {$level}: {$str}");
                };
            }
        }
        $mail->setFrom($mailCfg['from'] ?? 'no-reply@example.com', $mailCfg['from_name'] ?? '');
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $bodyHtml;
        if ($bodyText) $mail->AltBody = $bodyText;
        $mail->send();
        return true;
    } catch (Throwable $e) {
        // prefer PHPMailer's ErrorInfo if available
        $err = property_exists($mail ?? new stdClass(), 'ErrorInfo') ? ($mail->ErrorInfo ?? $e->getMessage()) : $e->getMessage();
        error_log('Mail error: ' . $err);
        return false;
    }
}

/**
 * Send verification email and store token hash + expiry
 */
function sendVerificationEmail(PDO $pdo, int $userId, string $email, string $monitor_code = ''): bool {
    $token = createToken(24);
    $hash = hashToken($token);
    $expires = (new DateTime('+24 hours'))->format('Y-m-d H:i:s');

    $stmt = $pdo->prepare("UPDATE users SET verification_token_hash = :h, verification_expires = :exp WHERE id = :id");
    $ok = $stmt->execute([':h'=>$hash, ':exp'=>$expires, ':id'=>$userId]);
    if (!$ok) return false;

    // build link from project root (dirname(baseUrl())) so it points to /api/verify.php
    $root = rtrim(dirname(baseUrl()), '/');
    $link = $root . '/api/verify.php?id=' . intval($userId) . '&token=' . urlencode($token);
    $subject = 'Please verify your CCSPS account';
    $body = "<p>Hello " . htmlspecialchars($monitor_code ?: '') . ",</p>"
          . "<p>Click to verify your account: <a href=\"{$link}\">Verify account</a><br/>This link expires in 24 hours.</p>";
    $sent = sendMailSMTP($email, $subject, $body);
    logActivity($pdo, $userId, 'send_verification', 'users', $userId, ['sent' => $sent ? 1 : 0]);
    return $sent;
}

/**
 * Send password reset email and store token hash + expiry
 */
function sendResetEmail(PDO $pdo, int $userId, string $email): bool {
    $token = createToken(20);
    $hash = hashToken($token);
    $expires = (new DateTime('+2 hours'))->format('Y-m-d H:i:s');

    $stmt = $pdo->prepare("UPDATE users SET reset_token_hash = :h, reset_expires = :exp WHERE id = :id");
    $ok = $stmt->execute([':h'=>$hash, ':exp'=>$expires, ':id'=>$userId]);
    if (!$ok) return false;

    // reset page lives under the public folder; build URL from project root
    $root = rtrim(dirname(baseUrl()), '/');
    $link = $root . '/public/reset_password.php?id=' . intval($userId) . '&token=' . urlencode($token);
    $subject = 'CCSPS password reset request';
    $body = "<p>If you requested a password reset, click the link below to set a new password (expires in 2 hours):</p>"
          . "<p><a href=\"{$link}\">Reset password</a></p>";
    $sent = sendMailSMTP($email, $subject, $body);
    logActivity($pdo, $userId, 'send_reset', 'users', $userId, ['sent' => $sent ? 1 : 0]);
    return $sent;
}