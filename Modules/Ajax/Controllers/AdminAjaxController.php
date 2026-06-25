<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Ajax\Controllers;

use Nenad\Autosav\Modules\Ajax\Services\AjaxResponseService;

class AdminAjaxController extends AjaxController
{
    public function stats(): void
    {
        $this->requireAjaxRole([ROLE_SUPERADMIN, 'ADMIN_SECURITE']);
        $db = db();
        AjaxResponseService::success('Statistiques chargées.', [
            'login_attempts_24h' => (int) ($db->fetch("SELECT COUNT(*) AS cnt FROM sav_tentatives_connexion WHERE tcn_cree_le >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")['cnt'] ?? 0),
            'blocked_ips' => (int) ($db->fetch("SELECT COUNT(*) AS cnt FROM sav_blocages_securite WHERE bse_adresse_ip IS NOT NULL AND bse_supprime_le IS NULL AND (bse_termine_le IS NULL OR bse_termine_le > NOW())")['cnt'] ?? 0),
            'blocked_emails' => (int) ($db->fetch("SELECT COUNT(*) AS cnt FROM sav_utilisateurs WHERE uti_est_verrouille = 1 AND uti_supprime_le IS NULL AND (uti_verrouille_jusqua IS NULL OR uti_verrouille_jusqua > NOW())")['cnt'] ?? 0),
        ]);
    }

    public function loginAttempts(): void
    {
        $this->requireAjaxRole([ROLE_SUPERADMIN, 'ADMIN_SECURITE']);
        AjaxResponseService::success('Tentatives chargées.', [
            'items' => db()->fetchAll(
                "SELECT tcn_id AS id,
                        COALESCE(INET6_NTOA(tcn_adresse_ip), '') AS ip,
                        COALESCE(tcn_email_tente, tcn_email_normalise, '') AS email,
                        tcn_succes AS success,
                        tcn_raison_echec AS failure_reason,
                        tcn_user_agent AS user_agent,
                        tcn_cree_le AS created_at
                   FROM sav_tentatives_connexion
               ORDER BY tcn_cree_le DESC
                  LIMIT 50"
            ),
        ]);
    }

    public function blockedIps(): void
    {
        $this->requireAjaxRole([ROLE_SUPERADMIN, 'ADMIN_SECURITE']);
        AjaxResponseService::success('IP bloquées chargées.', [
            'items' => db()->fetchAll(
                "SELECT bse_id AS id,
                        COALESCE(INET6_NTOA(bse_adresse_ip), '') AS ip,
                        bse_raison AS reason,
                        bse_niveau_blocage AS level,
                        bse_commence_le AS started_at,
                        bse_termine_le AS expires_at
                   FROM sav_blocages_securite
                  WHERE bse_adresse_ip IS NOT NULL
                    AND bse_supprime_le IS NULL
                    AND (bse_termine_le IS NULL OR bse_termine_le > NOW())
               ORDER BY bse_commence_le DESC
                  LIMIT 50"
            ),
        ]);
    }

    public function blockedEmails(): void
    {
        $this->requireAjaxRole([ROLE_SUPERADMIN, 'ADMIN_SECURITE']);
        AjaxResponseService::success('Comptes verrouillés chargés.', [
            'items' => db()->fetchAll(
                "SELECT uti_id AS id,
                        uti_email AS email,
                        uti_motif_verrouillage AS reason,
                        uti_echecs_connexion AS attempt_count,
                        uti_verrouille_jusqua AS expires_at,
                        uti_modifie_le AS updated_at
                   FROM sav_utilisateurs
                  WHERE uti_est_verrouille = 1
                    AND uti_supprime_le IS NULL
                    AND (uti_verrouille_jusqua IS NULL OR uti_verrouille_jusqua > NOW())
               ORDER BY uti_modifie_le DESC, uti_id DESC
                  LIMIT 50"
            ),
        ]);
    }
}
