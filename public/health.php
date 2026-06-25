<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use Nenad\Autosav\Core\Database\Database;
use Nenad\Autosav\Core\Services\Production\HealthCheckService;

$token = $_GET['token'] ?? ($_SERVER['HTTP_X_HEALTHCHECK_TOKEN'] ?? '');
$protected = defined('PRODUCTION_HEALTH_PUBLIC') ? !PRODUCTION_HEALTH_PUBLIC : true;
$tokenIsPlaceholder = defined('PRODUCTION_HEALTH_TOKEN_IS_PLACEHOLDER') && PRODUCTION_HEALTH_TOKEN_IS_PLACEHOLDER;
$expected = (defined('PRODUCTION_HEALTH_TOKEN') && !$tokenIsPlaceholder) ? (string) PRODUCTION_HEALTH_TOKEN : '';

if ($protected && ($expected === '' || !hash_equals($expected, (string) $token))) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'NOTOK', 'message' => 'Jeton healthcheck invalide.'], JSON_UNESCAPED_UNICODE);
    exit;
}

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
if (!$protected) {
    $checks = is_array($result['checks'] ?? null) ? $result['checks'] : [];
    $result = [
        'status' => (string) ($result['status'] ?? 'NOTOK'),
        'checked_at' => (string) ($result['checked_at'] ?? date('c')),
        'checks' => array_map(
            static fn(array $check): array => ['status' => (string) ($check['status'] ?? 'NOTOK')],
            $checks
        ),
    ];
}
http_response_code(($result['status'] ?? 'NOTOK') === 'SUCCESS' ? 200 : 503);
header('Content-Type: application/json; charset=utf-8');
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
