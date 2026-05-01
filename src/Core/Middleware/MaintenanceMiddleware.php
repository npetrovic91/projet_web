<?php
declare(strict_types=1);
namespace Nenad\Autosav\Core\Middleware;

/**
 * AUTOSAV — Middleware de maintenance
 * Vérifié AVANT toute autre logique sur chaque requête.
 */
class MaintenanceMiddleware
{
    public static function check(string $uri): void
    {
        // Exclure la page de maintenance elle-même
        if ($uri === '/maintenance' || $uri === '/auth/logout') return;

        // Lire le statut maintenance depuis la BDD (une seule requête légère)
        try {
            $pdo  = \Nenad\Autosav\Core\Database\Database::getInstance()->getPdo();
            $stmt = $pdo->prepare("SELECT mtn_is_active, mtn_message, mtn_allowed_roles, mtn_allowed_ips FROM sav_maintenance WHERE mtn_id = 1 LIMIT 1");
            $stmt->execute();
            $row  = $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\Throwable) {
            return; // En cas d'erreur BDD, ne pas bloquer
        }

        if (!$row || !(int)$row['mtn_is_active']) return;

        // Vérifier IP autorisée
        $allowedIps = array_merge(
            MAINTENANCE_ALLOWED_IPS,
            json_decode($row['mtn_allowed_ips'] ?? '[]', true) ?: []
        );
        if (in_array(client_ip(), $allowedIps, true)) return;

        // Vérifier rôle autorisé (si utilisateur connecté)
        if (is_authenticated()) {
            $allowedRoles = array_merge(
                MAINTENANCE_ALLOWED_ROLES,
                json_decode($row['mtn_allowed_roles'] ?? '[]', true) ?: []
            );
            $userRoles = $_SESSION['user_roles'] ?? [];
            foreach ($allowedRoles as $role) {
                if (in_array($role, $userRoles, true)) return;
            }
        }

        // Afficher la page de maintenance
        http_response_code(503);
        header('Retry-After: 3600');

        $message = $row['mtn_message'] ?? 'Application en maintenance. Revenez ultérieurement.';

        if (file_exists(MAINTENANCE_VIEW)) {
            include MAINTENANCE_VIEW;
        } else {
            echo "<!DOCTYPE html><html><head><title>Maintenance</title></head><body>";
            echo "<h1>🔧 Maintenance en cours</h1><p>" . htmlspecialchars($message) . "</p></body></html>";
        }
        exit;
    }
}
