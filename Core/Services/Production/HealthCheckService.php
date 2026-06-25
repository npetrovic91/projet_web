<?php
declare(strict_types=1);

namespace Nenad\Autosav\Core\Services\Production;

use PDO;
use Throwable;

final class HealthCheckService
{
    public function __construct(private readonly ?PDO $pdo = null)
    {
    }

    /**
     * @return array<string,mixed>
     */
    public function run(): array
    {
        $checks = [
            'php' => $this->checkPhp(),
            'storage' => $this->checkStorage(),
            'logs' => $this->checkWritable(defined('LOGS_PATH') ? LOGS_PATH : dirname(__DIR__, 4) . '/storage/logs'),
            'cache' => $this->checkWritable(defined('CACHE_PATH') ? CACHE_PATH : dirname(__DIR__, 4) . '/storage/cache'),
            'uploads' => $this->checkWritable(defined('UPLOADS_PATH') ? UPLOADS_PATH : dirname(__DIR__, 4) . '/storage/uploads'),
            'database' => $this->checkDatabase(),
            'environment' => $this->checkEnvironment(),
        ];

        $status = 'SUCCESS';
        foreach ($checks as $check) {
            if (($check['status'] ?? 'NOTOK') !== 'SUCCESS') {
                $status = 'NOTOK';
                break;
            }
        }

        return [
            'status' => $status,
            'checked_at' => date('c'),
            'checks' => $checks,
        ];
    }

    /** @return array<string,mixed> */
    private function checkPhp(): array
    {
        return [
            'status' => version_compare(PHP_VERSION, '8.1.0', '>=') ? 'SUCCESS' : 'NOTOK',
            'version' => PHP_VERSION,
            'required' => '>=8.1',
        ];
    }

    /** @return array<string,mixed> */
    private function checkStorage(): array
    {
        $path = defined('STORAGE_PATH') ? STORAGE_PATH : dirname(__DIR__, 4) . '/storage';
        return $this->checkWritable($path);
    }

    /** @return array<string,mixed> */
    private function checkWritable(string $path): array
    {
        if (!is_dir($path)) {
            @mkdir($path, 0775, true);
        }
        return [
            'status' => is_dir($path) && is_writable($path) ? 'SUCCESS' : 'NOTOK',
            'path' => $path,
            'writable' => is_dir($path) && is_writable($path),
        ];
    }

    /** @return array<string,mixed> */
    private function checkDatabase(): array
    {
        if (!$this->pdo instanceof PDO) {
            return ['status' => 'NOTOK', 'message' => 'PDO non fourni au service de santé.'];
        }
        try {
            $value = $this->pdo->query('SELECT 1')->fetchColumn();
            return ['status' => ((int) $value === 1) ? 'SUCCESS' : 'NOTOK'];
        } catch (Throwable $e) {
            return ['status' => 'NOTOK', 'message' => $e->getMessage()];
        }
    }

    /** @return array<string,mixed> */
    private function checkEnvironment(): array
    {
        $required = ['APP_ENV', 'DB_HOST', 'DB_DATABASE', 'DB_USERNAME'];
        $missing = [];
        foreach ($required as $key) {
            if ((getenv($key) ?: '') === '') {
                $missing[] = $key;
            }
        }
        return [
            'status' => $missing === [] ? 'SUCCESS' : 'NOTOK',
            'missing' => $missing,
            'app_env' => defined('APP_ENV') ? APP_ENV : getenv('APP_ENV'),
        ];
    }
}
