<?php
declare(strict_types=1);
namespace Nenad\Autosav\Core\Logger\Class\Handler;

/**
 * AUTOSAV — Handler de log vers fichier
 */
class FileHandler
{
    private string $filePath;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
        // Créer le répertoire si nécessaire
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    public function write(string $level, string $message, array $context = []): void
    {
        $date    = date('Y-m-d H:i:s');
        $ctx     = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $line    = "[{$date}] [{$level}] {$message}{$ctx}" . PHP_EOL;
        file_put_contents($this->filePath, $line, FILE_APPEND | LOCK_EX);
    }
}
