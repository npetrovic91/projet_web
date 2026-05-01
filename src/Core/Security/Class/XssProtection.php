<?php
declare(strict_types=1);

namespace Nenad\Autosav\Core\Security\Class;

/**
 * AUTOSAV — Protection XSS
 */
class XssProtection
{
    public static function clean(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map([self::class, 'clean'], $value);
        }
        if (is_string($value)) {
            return htmlspecialchars(strip_tags($value), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }
        return $value;
    }

    public static function cleanHtml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
