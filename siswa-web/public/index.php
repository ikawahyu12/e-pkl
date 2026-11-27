<?php
$scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
$publicDirectory = rtrim(str_replace('\\', '/', dirname($scriptPath)), '/');

if ($publicDirectory === '.' || $publicDirectory === '/') {
    $publicDirectory = '';
}

if ($publicDirectory === '') {
    $sessionCookiePath = '/';
} else {
    $appDirectory = dirname($publicDirectory);
    $sessionCookiePath = rtrim($appDirectory, '/') . '/';
}

$isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== ''
    && strtolower((string) $_SERVER['HTTPS']) !== 'off';

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => $sessionCookiePath,
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);

session_start();

require_once __DIR__ . '/../app/config/config.php';
require_once APP_PATH . '/helpers/ui.php';
require_once APP_PATH . '/core/Controller.php';
require_once APP_PATH . '/core/App.php';

new App();
