<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/Support/SecurityBridge.php';
require_once __DIR__ . '/Support/Config.php';

use Nenad\Autosav\Core\Theme\Support\SecurityBridge;
use Nenad\Autosav\Core\Theme\Support\Config;

SecurityBridge::init();

if (is_file(__DIR__ . '/config.json')) {
    try {
        Config::load(__DIR__ . '/config.json');
    } catch (Exception $e) {
        // Configuration non chargée
    }
}

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

function jsonResponse(bool $success, string $message = '', array $data = []): void
{
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_THROW_ON_ERROR);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Méthode non autorisée');
}

$key = $_POST['key'] ?? '';
$value = $_POST['value'] ?? '';
$csrf = $_POST['csrf'] ?? '';

if (empty($key)) {
    jsonResponse(false, 'Clé manquante');
}

if (!SecurityBridge::verifyCsrfToken($csrf, 'sidebar_select')) {
    jsonResponse(false, 'Token CSRF invalide');
}

$allowedKeys = [
    'sidebar_marques',
    'sidebar_concessions',
    'app_locale',
];

if (!in_array($key, $allowedKeys, true)) {
    jsonResponse(false, 'Clé non autorisée');
}

$sanitizedValue = SecurityBridge::sanitizeSessionValue($value);
$_SESSION[$key] = $sanitizedValue;

jsonResponse(true, 'Valeur stockée en session', [
    'key' => $key,
    'value' => $sanitizedValue
]);