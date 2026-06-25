#!/usr/bin/env php
<?php
declare(strict_types=1);

putenv('AUTOSAV_PREFLIGHT=1');
require dirname(__DIR__) . '/bootstrap.php';

$errors = [];
$warnings = [];

$requiredEnv = ['APP_ENV', 'APP_URL', 'DB_HOST', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD', 'ENCRYPTION_KEY', 'MAIL_HOST', 'MAIL_FROM_ADDRESS'];
foreach ($requiredEnv as $key) {
    if ((getenv($key) ?: '') === '') {
        $errors[] = "Variable d'environnement manquante : {$key}";
    }
}


$encryptionKey = getenv('ENCRYPTION_KEY') ?: '';
if (str_starts_with($encryptionKey, 'base64:')) {
    $decoded = base64_decode(substr($encryptionKey, 7), true);
    $keyLength = is_string($decoded) ? strlen($decoded) : 0;
} else {
    $keyLength = strlen($encryptionKey);
}
if ($keyLength < 32) {
    $errors[] = 'ENCRYPTION_KEY doit contenir au minimum 32 octets réels.';
}

if ((getenv('MAIL_PASSWORD') ?: '') === '' && (getenv('MAIL_FAIL_IF_UNCONFIGURED') ?: 'true') !== 'false') {
    $errors[] = 'MAIL_PASSWORD manquant alors que MAIL_FAIL_IF_UNCONFIGURED est actif.';
}
if ((getenv('MAIL_SANDBOX') ?: '') === 'true' && (getenv('MAIL_SANDBOX_TO') ?: '') === '') {
    $warnings[] = 'MAIL_SANDBOX est actif mais MAIL_SANDBOX_TO est vide.';
}

if (defined('APP_ENV') && APP_ENV === 'production' && defined('APP_DEBUG') && APP_DEBUG) {
    $errors[] = 'APP_DEBUG doit être false en production.';
}

foreach ([STORAGE_PATH, LOGS_PATH, CACHE_PATH, UPLOADS_PATH, EXPORTS_PATH, PRODUCTION_BACKUP_DIR] as $path) {
    if (!is_dir($path)) {
        @mkdir($path, 0775, true);
    }
    if (!is_dir($path) || !is_writable($path)) {
        $errors[] = "Répertoire non accessible en écriture : {$path}";
    }
}

if (!is_file(ROOT_PATH . '/public/index.php')) {
    $errors[] = 'public/index.php absent.';
}
if (!is_file(ROOT_PATH . '/public/.htaccess')) {
    $warnings[] = 'public/.htaccess absent ou non utilisé par le serveur web.';
}
if (
    defined('APP_ENV') && APP_ENV === 'production'
    && defined('PRODUCTION_HEALTH_PUBLIC') && !PRODUCTION_HEALTH_PUBLIC
    && defined('PRODUCTION_HEALTH_TOKEN_IS_PLACEHOLDER') && PRODUCTION_HEALTH_TOKEN_IS_PLACEHOLDER
) {
    $errors[] = 'HEALTHCHECK_TOKEN est vide ou laissé à sa valeur d\'exemple : à remplacer par un jeton long et aléatoire avant la mise en production (health.php le refusera de toute façon, mais la supervision restera inopérante tant qu\'il n\'est pas défini).';
}

$result = [
    'status' => $errors === [] ? 'SUCCESS' : 'NOTOK',
    'checked_at' => date('c'),
    'errors' => $errors,
    'warnings' => $warnings,
];

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
exit($errors === [] ? 0 : 1);