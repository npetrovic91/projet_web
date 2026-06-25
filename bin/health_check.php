#!/usr/bin/env php
<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use Nenad\Autosav\Core\Database\Database;
use Nenad\Autosav\Core\Services\Production\HealthCheckService;

$pdo = null;
$hasDatabaseConfig = (getenv('DB_DATABASE') ?: getenv('DB_NAME') ?: '') !== ''
    && (getenv('DB_USERNAME') ?: getenv('DB_USER') ?: '') !== '';
if ($hasDatabaseConfig && extension_loaded('pdo_mysql')) {
    try {
        $pdo = Database::getInstance()->getPdo();
    } catch (Throwable) {
        $pdo = null;
    }
}

$result = (new HealthCheckService($pdo))->run();
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
exit(($result['status'] ?? 'NOTOK') === 'SUCCESS' ? 0 : 1);
