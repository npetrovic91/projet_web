<?php
declare(strict_types=1);
namespace Nenad\Autosav\Core\Logger\Class;

use Nenad\Autosav\Core\Logger\Class\Handler\FileHandler;

/**
 * AUTOSAV — Logger par canal
 */
class Logger
{
    private string $channel;
    private FileHandler $handler;
    private int $minLevel;

    public function __construct(string $channel, string $logFile, string $minLevel = LogLevel::DEBUG)
    {
        $this->channel  = $channel;
        $this->handler  = new FileHandler($logFile);
        $this->minLevel = LogLevel::LEVELS[$minLevel] ?? 7;
    }

    public function emergency(string $message, array $context = []): void { $this->log(LogLevel::EMERGENCY, $message, $context); }
    public function alert(string $message, array $context = []): void     { $this->log(LogLevel::ALERT,     $message, $context); }
    public function critical(string $message, array $context = []): void  { $this->log(LogLevel::CRITICAL,  $message, $context); }
    public function error(string $message, array $context = []): void     { $this->log(LogLevel::ERROR,     $message, $context); }
    public function warning(string $message, array $context = []): void   { $this->log(LogLevel::WARNING,   $message, $context); }
    public function notice(string $message, array $context = []): void    { $this->log(LogLevel::NOTICE,    $message, $context); }
    public function info(string $message, array $context = []): void      { $this->log(LogLevel::INFO,      $message, $context); }
    public function debug(string $message, array $context = []): void     { $this->log(LogLevel::DEBUG,     $message, $context); }

    private function log(string $level, string $message, array $context): void
    {
        if ((LogLevel::LEVELS[$level] ?? 99) > $this->minLevel) return;
        $this->handler->write("[{$this->channel}] {$level}", $message, $context);
    }
}
