<?php
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(__DIR__));
}

require_once __DIR__ . '/auth.php';

function view_path(string $path) {
    return ROOT_DIR . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
}

// URL base for assets
$base_url = 'https://ccsps.cecoe.org';