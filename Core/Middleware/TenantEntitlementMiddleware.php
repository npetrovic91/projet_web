<?php
declare(strict_types=1);

namespace Nenad\Autosav\Core\Middleware;

require_once dirname(__DIR__) . '/Helpers/AuthHelper.php';

use Nenad\Autosav\Core\Database\Database;

/**
 * Verifie le droit commercial d'utiliser la societe active.
 *
 * Les modules restent compatibles avec un catalogue progressivement renseigne :
 * un module absent de sav_modules est considere comme une fonctionnalite noyau.
 */
class TenantEntitlementMiddleware
{
    private const ACCESS_CACHE_TTL = 300;

    public static function checkControllerAccess(string $controller): void
    {
        if (
            !is_authenticated()
            || self::controllerIsExempt($controller)
            || has_role(defined('ROLE_SUPERADMIN') ? (string) ROLE_SUPERADMIN : 'super_administrateur')
        ) {
            return;
        }

        $userId = (int) ($_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? 0);
        $companyId = (int) ($_SESSION['active_company_id'] ?? $_SESSION['user']['active_company_id'] ?? 0);

        if ($userId <= 0 || $companyId <= 0 || !self::companyIsAccessible($userId, $companyId)) {
            self::deny('Societe active indisponible ou abonnement non valide.');
        }

        self::checkModuleAccess($controller, $companyId);
    }

    public static function companyIsAccessible(int $userId, int $companyId): bool
    {
        if ($userId <= 0 || $companyId <= 0) {
            return false;
        }

        $cacheKey = $userId . ':' . $companyId;
        $cache = $_SESSION['tenant_entitlement_cache'][$cacheKey] ?? null;
        if (
            is_array($cache)
            && isset($cache['expires_at'], $cache['allowed'])
            && (int) $cache['expires_at'] >= time()
        ) {
            return (bool) $cache['allowed'];
        }

        $allowed = (bool) Database::getInstance()->fetchColumn(
            "SELECT 1
               FROM sav_adhesions_utilisateurs_societes aus
               INNER JOIN sav_statuts st_aus
                       ON st_aus.sta_id = aus.aus_statut_id
                      AND st_aus.sta_domaine = 'general'
                      AND st_aus.sta_code = 'actif'
               INNER JOIN sav_societes s
                       ON s.soc_id = aus.aus_societe_id
                      AND s.soc_supprime_le IS NULL
                      AND s.soc_archive_le IS NULL
               INNER JOIN sav_statuts st_soc
                       ON st_soc.sta_id = s.soc_statut_id
                      AND st_soc.sta_domaine = 'general'
                      AND st_soc.sta_code = 'actif'
               INNER JOIN sav_espaces_applicatifs eap
                       ON eap.eap_societe_id = s.soc_id
                      AND eap.eap_supprime_le IS NULL
                      AND eap.eap_bloque_le IS NULL
               INNER JOIN sav_statuts st_eap
                       ON st_eap.sta_id = eap.eap_statut_id
                      AND st_eap.sta_domaine = 'general'
                      AND st_eap.sta_code = 'actif'
               INNER JOIN sav_abonnements_societes abo
                       ON abo.abo_id = eap.eap_abonnement_societe_id
                      AND abo.abo_societe_id = s.soc_id
                      AND abo.abo_supprime_le IS NULL
                      AND abo.abo_archive_le IS NULL
                      AND (abo.abo_debute_le IS NULL OR abo.abo_debute_le <= NOW())
                      AND (abo.abo_termine_le IS NULL OR abo.abo_termine_le >= NOW())
               INNER JOIN sav_statuts st_abo
                       ON st_abo.sta_id = abo.abo_statut_abonnement_id
                      AND st_abo.sta_domaine = 'abonnement'
                      AND st_abo.sta_code IN ('actif', 'essai')
               INNER JOIN sav_statuts st_pay
                       ON st_pay.sta_id = abo.abo_statut_paiement_id
                      AND st_pay.sta_domaine = 'paiement'
                      AND st_pay.sta_code = 'a_jour'
              WHERE aus.aus_utilisateur_id = :user_id
                AND aus.aus_societe_id = :company_id
                AND aus.aus_supprime_le IS NULL
                AND aus.aus_archive_le IS NULL
                AND (aus.aus_debute_le IS NULL OR aus.aus_debute_le <= NOW())
                AND (aus.aus_termine_le IS NULL OR aus.aus_termine_le >= NOW())
              LIMIT 1",
            ['user_id' => $userId, 'company_id' => $companyId]
        );

        $_SESSION['tenant_entitlement_cache'][$cacheKey] = [
            'allowed' => $allowed,
            'expires_at' => time() + self::ACCESS_CACHE_TTL,
        ];

        return $allowed;
    }

    public static function clearSessionCache(?int $userId = null, ?int $companyId = null): void
    {
        if (!isset($_SESSION['tenant_entitlement_cache']) || !is_array($_SESSION['tenant_entitlement_cache'])) {
            return;
        }

        if (!$userId && !$companyId) {
            unset($_SESSION['tenant_entitlement_cache']);
            return;
        }

        foreach (array_keys($_SESSION['tenant_entitlement_cache']) as $key) {
            [$cachedUserId, $cachedCompanyId] = array_map('intval', explode(':', (string) $key, 2));
            if (($userId === null || $cachedUserId === $userId) && ($companyId === null || $cachedCompanyId === $companyId)) {
                unset($_SESSION['tenant_entitlement_cache'][$key]);
            }
        }
    }

    private static function checkModuleAccess(string $controller, int $companyId): void
    {
        $moduleCode = mb_strtolower((string) strtok($controller, '\\'));
        if ($moduleCode === '') {
            return;
        }

        $module = Database::getInstance()->fetch(
            "SELECT m.mod_id, m.mod_est_noyau
               FROM sav_modules m
               INNER JOIN sav_statuts st
                       ON st.sta_id = m.mod_statut_id
                      AND st.sta_domaine = 'general'
                      AND st.sta_code = 'actif'
              WHERE LOWER(m.mod_code) = :module_code
                AND m.mod_supprime_le IS NULL
                AND m.mod_archive_le IS NULL
              LIMIT 1",
            ['module_code' => $moduleCode]
        );

        if (!$module || (int) ($module['mod_est_noyau'] ?? 0) === 1) {
            return;
        }

        $active = Database::getInstance()->fetchColumn(
            "SELECT 1
               FROM sav_modules_societes mos
               INNER JOIN sav_statuts st
                       ON st.sta_id = mos.mos_statut_id
                      AND st.sta_domaine = 'general'
                      AND st.sta_code = 'actif'
              WHERE mos.mos_societe_id = :company_id
                AND mos.mos_module_id = :module_id
                AND mos.mos_supprime_le IS NULL
                AND mos.mos_archive_le IS NULL
                AND mos.mos_debute_le <= NOW()
                AND (mos.mos_termine_le IS NULL OR mos.mos_termine_le >= NOW())
              LIMIT 1",
            ['company_id' => $companyId, 'module_id' => (int) $module['mod_id']]
        );

        if (!$active) {
            self::deny('Module non active pour la societe courante.');
        }
    }

    private static function controllerIsExempt(string $controller): bool
    {
        return str_starts_with($controller, 'Auth\\');
    }

    private static function deny(string $message): never
    {
        http_response_code(403);
        if (
            ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest'
            || str_starts_with((string) ($_SERVER['REQUEST_URI'] ?? ''), '/ajax/')
        ) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode([
                'success' => false,
                'code' => 403,
                'message' => $message,
                'data' => null,
                'errors' => null,
            ]);
            exit;
        }

        echo '<h1>Acces refuse</h1><p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>';
        exit;
    }
}
