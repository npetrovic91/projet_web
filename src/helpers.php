<?php
declare(strict_types=1);

use Nenad\Autosav\Core\Database\Database;
use Nenad\Autosav\Core\Logger\Class\Logger;
use Nenad\Autosav\Core\Logger\LogManager;
use Nenad\Autosav\Core\Security\Class\CsrfProtection;
use Nenad\Autosav\Core\Security\SecurityManager;

if (!function_exists('db')) {
    function db(): Database
    {
        return Database::getInstance();
    }
}

if (!function_exists('database')) {
    function database(): Database
    {
        return Database::getInstance();
    }
}

if (!function_exists('logger')) {
    function logger(string $channel = 'application'): Logger
    {
        return LogManager::getInstance()->channel($channel);
    }
}

if (!function_exists('security')) {
    function security(): SecurityManager
    {
        return SecurityManager::getInstance();
    }
}

if (!function_exists('client_ip')) {
    function client_ip(): string
    {
        $candidates = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        foreach ($candidates as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = trim(explode(',', (string) $_SERVER[$key])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return '0.0.0.0';
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        $base = defined('APP_URL') ? APP_URL : '';
        return rtrim($base, '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('session')) {
    function session(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }
}

if (!function_exists('is_authenticated')) {
    function is_authenticated(): bool
    {
        return !empty($_SESSION['authenticated']) && !empty($_SESSION['user_id']);
    }
}

if (!function_exists('has_role')) {
    function has_role(string $role): bool
    {
        return in_array($role, $_SESSION['user_roles'] ?? [], true);
    }
}

if (!function_exists('redirect')) {
    function redirect(string $path, int $code = 302): never
    {
        header('Location: ' . url($path), true, $code);
        exit;
    }
}

if (!function_exists('generate_uuid')) {
    function generate_uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        $field = defined('CSRF_FORM_FIELD') ? CSRF_FORM_FIELD : '_csrf_token';
        return '<input type="hidden" name="' . e($field) . '" value="' . e(CsrfProtection::getToken()) . '">';
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return CsrfProtection::getToken();
    }
}
