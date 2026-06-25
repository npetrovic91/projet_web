<?php
declare(strict_types=1);

namespace Nenad\Autosav\Core\Security\Class;

/**
 * Garde-fou pour l'exécution future des contrôles qualité SQL stockés.
 * Ces requêtes doivent rester en lecture seule et réservées au super-admin.
 */
final class SqlControlGuard
{
    public static function assertSuperAdminCanExecute(string $sql): void
    {
        if (!function_exists('has_role') || !has_role(defined('ROLE_SUPERADMIN') ? ROLE_SUPERADMIN : 'super_administrateur')) {
            throw new \RuntimeException('Exécution des contrôles SQL réservée au super-admin.');
        }
        self::assertReadOnly($sql);
    }

    public static function assertReadOnly(string $sql): void
    {
        $normalized = strtolower(trim(preg_replace('/\s+/', ' ', $sql) ?? $sql));
        if ($normalized === '' || !str_starts_with($normalized, 'select ')) {
            throw new \RuntimeException('Seules les requêtes SELECT de contrôle sont autorisées.');
        }
        if (preg_match('/\b(insert|update|delete|replace|drop|truncate|alter|create|grant|revoke|call|load|outfile|infile)\b/i', $normalized)) {
            throw new \RuntimeException('Requête de contrôle refusée : mot-clé dangereux détecté.');
        }
        if (str_contains($normalized, ';')) {
            throw new \RuntimeException('Une seule requête SELECT est autorisée.');
        }
    }
}
