<?php
declare(strict_types=1);

namespace Nenad\Autosav\Core\Logger;

class JsonFormatter implements FormatterInterface
{
    public function format(string $level, string $channel, string $message, array $context): string
    {
        return json_encode([
            'timestamp' => date('c'),
            'level'     => $level,
            'channel'   => $channel,
            'message'   => $message,
            'context'   => $context,
        ], JSON_UNESCAPED_UNICODE) . PHP_EOL;
    }
}
