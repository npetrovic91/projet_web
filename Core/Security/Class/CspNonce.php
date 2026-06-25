<?php
declare(strict_types=1);

namespace Nenad\Autosav\Core\Security\Class;

/**
 * Gestion centralisée des nonces CSP par requête.
 */
final class CspNonce
{
    private static ?string $nonce = null;

    public static function value(): string
    {
        if (self::$nonce === null) {
            self::$nonce = rtrim(strtr(base64_encode(random_bytes(18)), '+/', '-_'), '=');
        }
        return self::$nonce;
    }

    public static function attribute(): string
    {
        return ' nonce="' . htmlspecialchars(self::value(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';
    }

    public static function policy(?string $basePolicy = null): string
    {
        $policy = trim((string)($basePolicy ?? (defined('CSP_POLICY') ? CSP_POLICY : "default-src 'self'")));
        $nonce = "'nonce-" . self::value() . "'";
        $allowUnsafe = defined('CSP_ALLOW_UNSAFE_INLINE') && CSP_ALLOW_UNSAFE_INLINE;
        $inline = $allowUnsafe ? " 'unsafe-inline'" : '';

        $directives = array_filter(array_map('trim', explode(';', $policy)));
        $hasScript = false;
        $hasStyle = false;
        foreach ($directives as &$directive) {
            if (str_starts_with($directive, 'script-src')) {
                $hasScript = true;
                if (!str_contains($directive, $nonce)) {
                    $directive .= ' ' . $nonce . $inline;
                }
            }
            if (str_starts_with($directive, 'style-src')) {
                $hasStyle = true;
                if (!str_contains($directive, $nonce)) {
                    $directive .= ' ' . $nonce . $inline;
                }
            }
        }
        unset($directive);
        if (!$hasScript) {
            $directives[] = "script-src 'self' {$nonce}{$inline}";
        }
        if (!$hasStyle) {
            $directives[] = "style-src 'self' {$nonce}{$inline}";
        }
        return implode('; ', $directives);
    }

    public static function sendHeaders(): void
    {
        if (headers_sent()) {
            return;
        }
        foreach ((array)(defined('SECURITY_HEADERS') ? SECURITY_HEADERS : []) as $name => $value) {
            if ($value === '') {
                continue;
            }
            if ($name === 'Strict-Transport-Security' && (!defined('APP_ENV') || APP_ENV !== 'production')) {
                continue;
            }
            header($name . ': ' . $value);
        }
        header('Content-Security-Policy: ' . self::policy());
    }
}
