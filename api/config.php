<?php
declare(strict_types=1);

$configPath = __DIR__ . '/config.local.php';

if (!is_file($configPath)) {
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, "API configuration not found. Copy api/config.local.php.example to api/config.local.php and set the database credentials.\n");
        exit(1);
    }
    http_response_code(503);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'API is not configured. Follow api/SETUP.md.']);
    exit;
}

$config = require $configPath;

if (!is_array($config) || !isset($config['db']) || !is_array($config['db'])) {
    throw new RuntimeException('The local API configuration is invalid.');
}

return $config;
