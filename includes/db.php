<?php
$config = require __DIR__ . '/config.php';

$dsn = "mysql:host={$config['db']['host']};dbname={$config['db']['dbname']};charset={$config['db']['charset']}";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], $options);
} catch (PDOException $e) {

    // IMPORTANT: DO NOT OUTPUT ANYTHING (breaks Excel or JSON)
    error_log("DB connection failed: " . $e->getMessage());

    http_response_code(500);

    // Return a JSON error ONLY if not exporting excel
    if (!headers_sent()) {
        echo json_encode(["error" => "Database connection failed"]);
    }

    exit;
}