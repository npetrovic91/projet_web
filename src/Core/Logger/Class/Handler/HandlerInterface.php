<?php
declare(strict_types=1);

namespace Nenad\Autosav\Core\Logger;

interface HandlerInterface
{
    public function handle(string $level, string $channel, string $message, array $context): void;
    public function isHandling(string $level): bool;
}
