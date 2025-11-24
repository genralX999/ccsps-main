<?php
require_once __DIR__ . '/../includes/init.php';
$user = currentUser($pdo);
if ($user) {
    if ($user['role'] === 'superadmin') {
        header('Location: ' . baseUrl() . '/superadmin_dashboard.php'); exit;
    } else {
        header('Location: ' . baseUrl() . '/user_dashboard.php'); exit;
    }
} else {
    header('Location: ' . baseUrl() . '/login.php'); exit;
}
