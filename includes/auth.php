<?php
// Simple session-based auth helpers
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

function currentUser(PDO $pdo) {
    if (!isset($_SESSION['user_id'])) return null;
    $stmt = $pdo->prepare("SELECT id, username, email, role, monitor_id_code FROM users WHERE id = :id");
    $stmt->execute([':id' => $_SESSION['user_id']]);
    return $stmt->fetch();
}

function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /login.php'); exit;
    }
}

function requireRole($role) {
    $user = currentUser($GLOBALS['pdo']);
    if (!$user || $user['role'] !== $role) {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }
}