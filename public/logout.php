<?php
require_once __DIR__ . '/../includes/init.php';
if (isset($_SESSION['user_id'])) {
    logActivity($pdo, $_SESSION['user_id'], 'logout', null, null, []);
}
session_unset();
session_destroy();
header('Location: ' . baseUrl() . '/login.php');
exit;
