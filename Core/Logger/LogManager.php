<?php
declare(strict_types=1);
namespace Nenad\Autosav\Core\Logger;

use Nenad\Autosav\Core\Logger\Class\Logger;
use Nenad\Autosav\Core\Logger\Class\LogLevel;

/**
 * AUTOSAV — Gestionnaire de logs multi-canaux
 */
class LogManager
{
    private static ?self $instance = null;

    /** @var Logger[] */
    private array $channels = [];

    private function __construct()
    {
        $minLevel = APP_DEBUG ? LogLevel::DEBUG : LogLevel::WARNING;

        $this->channels = [
            'application' => new Logger('application', LOGS_PATH . '/application.log', $minLevel),
            'security'    => new Logger('security',    LOGS_PATH . '/security.log',    LogLevel::DEBUG),
            'database'    => new Logger('database',    LOGS_PATH . '/database.log',    $minLevel),
            'audit'       => new Logger('audit',       LOGS_PATH . '/audit.log',       LogLevel::DEBUG),
            'error'       => new Logger('error',       LOGS_PATH . '/error.log',       LogLevel::DEBUG),
        ];
    }

    public static function getInstance(): self
    {
        self::$instance ??= new self();
        return self::$instance;
    }

    public function channel(string $name): Logger
    {
        return $this->channels[$name] ?? $this->channels['application'];
    }

    private function __clone() {}
}
