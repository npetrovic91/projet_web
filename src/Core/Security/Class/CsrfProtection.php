<?php
declare(strict_types=1);

namespace Nenad\Autosav\Core\Security\Class;

/**
 * AUTOSAV — Protection CSRF
 * Fichier : src/Core/Security/Class/CsrfProtection.php
 */
class CsrfProtection
{
    public static function ensureToken(): void
    {
        if (empty($_SESSION['csrf_token']) || empty($_SESSION['csrf_token_expiry'])
            || time() > $_SESSION['csrf_token_expiry']) {
            self::regenerate();
        }
    }

    public static function getToken(): string
    {
        self::ensureToken();
        return $_SESSION['csrf_token'];
    }

    public static function regenerate(): void
    {
        $_SESSION['csrf_token']        = bin2hex(random_bytes(TOKEN_BYTE_LENGTH));
        $_SESSION['csrf_token_expiry'] = time() + CSRF_TOKEN_EXPIRY;
    }

    public static function validate(): bool
    {
        $token = $_POST[CSRF_TOKEN_NAME]
               ?? $_SERVER['HTTP_' . str_replace('-', '_', strtoupper(CSRF_HEADER_NAME))]
               ?? '';

        if (empty($token) || empty($_SESSION['csrf_token'])) return false;
        if (time() > ($_SESSION['csrf_token_expiry'] ?? 0)) return false;

        $valid = hash_equals($_SESSION['csrf_token'], $token);
        // Régénérer après validation réussie
        if ($valid) self::regenerate();
        return $valid;
    }

    public function validateToken(string $token): bool
    {
        return self::validateTokenValue($token);
    }

    public static function validateTokenValue(string $token): bool
    {
        if ($token === '' || empty($_SESSION['csrf_token'])) {
            return false;
        }
        if (time() > ($_SESSION['csrf_token_expiry'] ?? 0)) {
            return false;
        }
        $valid = hash_equals($_SESSION['csrf_token'], $token);
        if ($valid) {
            self::regenerate();
        }
        return $valid;
    }
}
