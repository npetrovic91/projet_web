<?php
declare(strict_types=1);

namespace Nenad\Autosav\Core\Router;

class RouterException extends \RuntimeException
{
    public function __construct(string $message = '', int $code = 404, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
