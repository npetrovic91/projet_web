<?php
declare(strict_types=1);

namespace Nenad\Autosav\Core\Theme\Support;

final class SecurityBridge
{
    private static bool $initialized = false;
    private static string $sessionKey = '_csrf_tokens';

    public static function init(): void
    {
        if (self::$initialized) {
            return;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION[self::$sessionKey])) {
            $_SESSION[self::$sessionKey] = [];
        }

        self::cleanOldTokens();
        self::$initialized = true;
    }

    public static function csrfToken(string $action = 'default'): string
    {
        self::init();

        $token = bin2hex(random_bytes(32));
        $_SESSION[self::$sessionKey][$action] = [
            'token' => $token,
            'time' => time()
        ];

        return $token;
    }

    public static function verifyCsrfToken(string $token, string $action = 'default', bool $deleteAfterCheck = false): bool
    {
        self::init();

        if (!isset($_SESSION[self::$sessionKey][$action])) {
            return false;
        }

        $stored = $_SESSION[self::$sessionKey][$action];

        if ((time() - $stored['time']) > 7200) {
            unset($_SESSION[self::$sessionKey][$action]);
            return false;
        }

        $valid = hash_equals($stored['token'], $token);

        if ($deleteAfterCheck && $valid) {
            unset($_SESSION[self::$sessionKey][$action]);
        }

        return $valid;
    }

    private static function cleanOldTokens(): void
    {
        if (!isset($_SESSION[self::$sessionKey]) || !is_array($_SESSION[self::$sessionKey])) {
            return;
        }

        $now = time();
        foreach ($_SESSION[self::$sessionKey] as $action => $data) {
            if (!isset($data['time']) || ($now - $data['time']) > 7200) {
                unset($_SESSION[self::$sessionKey][$action]);
            }
        }
    }

    public static function cleanHtml(string $html): string
    {
        $allowed = '<b><i><u><strong><em><span><br>';
        $cleaned = strip_tags($html, $allowed);
        $cleaned = preg_replace('/<(\w+)([^>]*)>/i', '<$1>', $cleaned);
        return $cleaned;
    }

    public static function sanitizeSessionValue($value): string
    {
        if (is_array($value)) {
            return '';
        }
        return filter_var((string)$value, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    }

    public static function checkUserLevel(int $requiredLevel): bool
    {
        $userLevel = (int)($_SESSION['user_level'] ?? $_SESSION['user']['level'] ?? 0);
        return $userLevel >= $requiredLevel;
    }
}