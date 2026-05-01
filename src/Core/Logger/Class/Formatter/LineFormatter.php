<?php
declare(strict_types=1);

namespace Nenad\Autosav\Core\Logger;

class LineFormatter implements FormatterInterface
{
    public function format(string $level, string $channel, string $message, array $context): string
    {
        $date       = date('Y-m-d H:i:s');
        $levelUpper = strtoupper($level);
        $ctx        = !empty($context) ? ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        return "[{$date}] {$channel}.{$levelUpper}: {$message}{$ctx}" . PHP_EOL;
    }
}
