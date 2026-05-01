<?php
declare(strict_types=1);

namespace Nenad\Autosav\Core\Logger;

interface FormatterInterface
{
    public function format(string $level, string $channel, string $message, array $context): string;
}
