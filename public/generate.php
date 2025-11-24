<?php
require_once __DIR__ . '/../includes/init.php';

// ---- CONFIGURE YOUR NEW SUPERADMIN ----
$username = "rootadmin";
$email = "rootadmin@example.com";
$password = "Admin@123";   // ← you can change this
$role = "superadmin";

// ---- DO NOT CHANGE BELOW ----
$password_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

$stmt = $pdo->prepare("
    INSERT INTO users (monitor_number, monitor_id_code, username, email, password_hash, role, status, is_active, created_at, updated_at)
    VALUES (:mon, :code, :user, :email, :pass, :role, 'approved', 1, NOW(), NOW())
");

$stmt->execute([
    ':mon'  => 9999,
    ':code' => 'CCSPM9999',
    ':user' => $username,
    ':email'=> $email,
    ':pass' => $password_hash,
    ':role' => $role
]);

echo "SUCCESS! New superadmin created.<br>";
echo "Username: $username<br>";
echo "Password: $password<br>";
echo "Use this to log in now.";
