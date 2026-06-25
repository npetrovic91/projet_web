<?php
declare(strict_types=1);
namespace Nenad\Autosav\Core\Middleware;

use Nenad\Autosav\Core\Database\Database;

/**
 * Middleware de maintenance.
 *
 * Le dump SQL actuel ne contient pas de table dediee au mode maintenance. L'etat est donc lu dans
 * sav_parametres_application :
 * - application / mode_maintenance
 * - maintenance / message_public
 * - maintenance / roles_autorises
 * - maintenance / ips_autorisees
 */
class MaintenanceMiddleware
{
    public static function check(string $uri): void
    {
        if (
            $uri === '/'
            || $uri === '/login'
            || $uri === '/logout'
            || $uri === '/maintenance'
            || $uri === '/email/unsubscribe'
            || str_starts_with($uri, '/auth/')
        ) {
            return;
        }

        try {
            $state = self::readState();
        } catch (\Throwable) {
            return;
        }

        if (empty($state['active'])) {
            return;
        }

        $allowedIps = array_values(array_unique(array_merge(
            defined('MAINTENANCE_ALLOWED_IPS') ? MAINTENANCE_ALLOWED_IPS : [],
            $state['allowed_ips']
        )));
        if (function_exists('client_ip') && in_array(client_ip(), $allowedIps, true)) {
            return;
        }

        if (function_exists('is_authenticated') && is_authenticated()) {
            $allowedRoles = array_values(array_unique(array_map(
                static fn($role): string => mb_strtolower(trim((string) $role)),
                array_merge(defined('MAINTENANCE_ALLOWED_ROLES') ? MAINTENANCE_ALLOWED_ROLES : [], $state['allowed_roles'])
            )));
            $userRoles = array_map(
                static fn($role): string => mb_strtolower(trim((string) $role)),
                (array) ($_SESSION['user']['roles'] ?? $_SESSION['user_roles'] ?? [])
            );
            foreach ($allowedRoles as $role) {
                if (in_array($role, $userRoles, true)) {
                    return;
                }
            }
        }

        http_response_code(503);
        header('Retry-After: 3600');
        $message = $state['message'] ?: 'Application en maintenance. Revenez ultérieurement.';
        if (defined('MAINTENANCE_VIEW') && file_exists(MAINTENANCE_VIEW)) {
            include MAINTENANCE_VIEW;
        } else {
            echo '<!DOCTYPE html><html><head><title>Maintenance</title></head><body>';
            echo '<h1>Maintenance en cours</h1><p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p></body></html>';
        }
        exit;
    }

    private static function readState(): array
    {
        $rows = Database::getInstance()->fetchAll(
            "SELECT pap_domaine, pap_cle, pap_valeur_json
               FROM sav_parametres_application
              WHERE pap_supprime_le IS NULL
                AND ((pap_domaine = 'application' AND pap_cle = 'mode_maintenance')
                  OR (pap_domaine = 'maintenance' AND pap_cle IN ('message_public', 'roles_autorises', 'ips_autorisees')))
           ORDER BY pap_id DESC"
        );

        $values = [];
        foreach ($rows as $row) {
            $key = $row['pap_domaine'] . '.' . $row['pap_cle'];
            if (array_key_exists($key, $values)) {
                continue;
            }
            $decoded = json_decode((string) ($row['pap_valeur_json'] ?? ''), true);
            $values[$key] = is_array($decoded) && array_key_exists('valeur', $decoded) ? $decoded['valeur'] : null;
        }

        return [
            'active' => (bool) ($values['application.mode_maintenance'] ?? false),
            'message' => (string) ($values['maintenance.message_public'] ?? 'Application temporairement indisponible pour maintenance.'),
            'allowed_roles' => (array) ($values['maintenance.roles_autorises'] ?? ['super_administrateur']),
            'allowed_ips' => (array) ($values['maintenance.ips_autorisees'] ?? ['127.0.0.1', '::1']),
        ];
    }
}
