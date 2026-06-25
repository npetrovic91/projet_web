<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Administration\Models;

use Nenad\Autosav\Core\Model\BaseModel;

/**
 * Supervision securite alignee sur le schema SQL francais.
 *
 * Tables utilisees :
 * - sav_tentatives_connexion
 * - sav_blocages_securite
 * - sav_utilisateurs
 * - sav_profils_utilisateurs
 * - sav_journaux_audit
 */
class SecurityMonitoringModel extends BaseModel
{
    protected string $table = 'sav_tentatives_connexion';
    protected string $colPrefix = 'tcn_';

    public function getSecurityStats(int $lastHours = 24): array
    {
        $db = $this->db();

        $totalAttempts = $db->fetch(
            "SELECT COUNT(*) AS cnt
               FROM sav_tentatives_connexion
              WHERE tcn_cree_le >= DATE_SUB(NOW(), INTERVAL :h HOUR)",
            ['h' => $lastHours]
        );

        $failedAttempts = $db->fetch(
            "SELECT COUNT(*) AS cnt
               FROM sav_tentatives_connexion
              WHERE tcn_succes = 0
                AND tcn_cree_le >= DATE_SUB(NOW(), INTERVAL :h HOUR)",
            ['h' => $lastHours]
        );

        $successAttempts = $db->fetch(
            "SELECT COUNT(*) AS cnt
               FROM sav_tentatives_connexion
              WHERE tcn_succes = 1
                AND tcn_cree_le >= DATE_SUB(NOW(), INTERVAL :h HOUR)",
            ['h' => $lastHours]
        );

        $activeIpBlocks = $db->fetch(
            "SELECT COUNT(*) AS cnt
               FROM sav_blocages_securite
              WHERE bse_adresse_ip IS NOT NULL
                AND bse_supprime_le IS NULL
                AND (bse_termine_le IS NULL OR bse_termine_le > NOW())"
        );

        $activeEmailBlocks = $db->fetch(
            "SELECT COUNT(*) AS cnt
               FROM sav_utilisateurs
              WHERE uti_est_verrouille = 1
                AND uti_supprime_le IS NULL
                AND (uti_verrouille_jusqua IS NULL OR uti_verrouille_jusqua > NOW())"
        );

        return [
            'total_attempts'      => (int) ($totalAttempts['cnt'] ?? 0),
            'failed_attempts'     => (int) ($failedAttempts['cnt'] ?? 0),
            'success_attempts'    => (int) ($successAttempts['cnt'] ?? 0),
            'active_ip_blocks'    => (int) ($activeIpBlocks['cnt'] ?? 0),
            'active_email_blocks' => (int) ($activeEmailBlocks['cnt'] ?? 0),
            'window_hours'        => $lastHours,
        ];
    }

    public function getTopFailedIps(int $limit = 10): array
    {
        $limit = max(1, min(100, $limit));
        return $this->db()->fetchAll(
            "SELECT COALESCE(INET6_NTOA(tcn_adresse_ip), '') AS lat_ip,
                    COUNT(*) AS failure_count,
                    MAX(tcn_cree_le) AS last_attempt,
                    GROUP_CONCAT(DISTINCT tcn_email_tente ORDER BY tcn_cree_le DESC SEPARATOR ', ') AS emails_tried
               FROM sav_tentatives_connexion
              WHERE tcn_succes = 0
                AND tcn_cree_le >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
           GROUP BY tcn_adresse_ip
           ORDER BY failure_count DESC
              LIMIT {$limit}"
        );
    }

    public function getAttempts(
        int $limit = 50,
        int $offset = 0,
        ?string $filterIp = null,
        ?string $filterEmail = null,
        ?int $onlyFailed = null
    ): array {
        $conditions = ['1=1'];
        $params = [];

        if ($filterIp !== null && trim($filterIp) !== '') {
            $conditions[] = 'COALESCE(INET6_NTOA(t.tcn_adresse_ip), \'\') LIKE :ip';
            $params['ip'] = '%' . trim($filterIp) . '%';
        }

        if ($filterEmail !== null && trim($filterEmail) !== '') {
            $conditions[] = '(t.tcn_email_tente LIKE :email OR t.tcn_email_normalise LIKE :email)';
            $params['email'] = '%' . trim($filterEmail) . '%';
        }

        if ($onlyFailed !== null) {
            $conditions[] = 't.tcn_succes = :success';
            $params['success'] = $onlyFailed === 0 ? 1 : 0;
        }

        $where = implode(' AND ', $conditions);
        $limit = max(1, min(200, $limit));
        $offset = max(0, $offset);

        return $this->db()->fetchAll(
            "SELECT t.tcn_id AS lat_id,
                    COALESCE(INET6_NTOA(t.tcn_adresse_ip), '') AS lat_ip,
                    COALESCE(t.tcn_email_tente, t.tcn_email_normalise, u.uti_email, '') AS lat_email,
                    t.tcn_utilisateur_id AS lat_user_id,
                    t.tcn_succes AS lat_success,
                    t.tcn_raison_echec AS lat_failure_reason,
                    t.tcn_user_agent AS lat_user_agent,
                    t.tcn_cree_le AS lat_created_at,
                    u.uti_email AS user_email,
                    p.pui_prenom AS user_firstname,
                    p.pui_nom AS user_lastname
               FROM sav_tentatives_connexion t
          LEFT JOIN sav_utilisateurs u ON u.uti_id = t.tcn_utilisateur_id
          LEFT JOIN sav_profils_utilisateurs p ON p.pui_utilisateur_id = u.uti_id AND p.pui_supprime_le IS NULL
              WHERE {$where}
           ORDER BY t.tcn_cree_le DESC
              LIMIT {$limit} OFFSET {$offset}",
            $params
        );
    }

    public function countAttempts(?string $filterIp = null, ?string $filterEmail = null): int
    {
        $conditions = ['1=1'];
        $params = [];

        if ($filterIp !== null && trim($filterIp) !== '') {
            $conditions[] = 'COALESCE(INET6_NTOA(tcn_adresse_ip), \'\') LIKE :ip';
            $params['ip'] = '%' . trim($filterIp) . '%';
        }

        if ($filterEmail !== null && trim($filterEmail) !== '') {
            $conditions[] = '(tcn_email_tente LIKE :email OR tcn_email_normalise LIKE :email)';
            $params['email'] = '%' . trim($filterEmail) . '%';
        }

        $row = $this->db()->fetch(
            'SELECT COUNT(*) AS cnt FROM sav_tentatives_connexion WHERE ' . implode(' AND ', $conditions),
            $params
        );

        return (int) ($row['cnt'] ?? 0);
    }

    public function getActiveIpBlocks(int $limit = 50): array
    {
        $limit = max(1, min(200, $limit));
        return $this->db()->fetchAll(
            "SELECT b.bse_id AS ibl_id,
                    COALESCE(INET6_NTOA(b.bse_adresse_ip), '') AS ibl_ip,
                    b.bse_raison AS ibl_reason,
                    CASE WHEN b.bse_cree_par_utilisateur_id IS NULL THEN 'auto' ELSE 'manual' END AS ibl_type,
                    b.bse_termine_le AS ibl_expires_at,
                    b.bse_commence_le AS ibl_created_at,
                    b.bse_niveau_blocage AS ibl_level,
                    u.uti_email AS user_email
               FROM sav_blocages_securite b
          LEFT JOIN sav_utilisateurs u ON u.uti_id = b.bse_utilisateur_id
              WHERE b.bse_adresse_ip IS NOT NULL
                AND b.bse_supprime_le IS NULL
                AND (b.bse_termine_le IS NULL OR b.bse_termine_le > NOW())
           ORDER BY b.bse_commence_le DESC
              LIMIT {$limit}"
        );
    }

    public function getActiveEmailBlocks(int $limit = 50): array
    {
        $limit = max(1, min(200, $limit));
        return $this->db()->fetchAll(
            "SELECT u.uti_id AS ebl_id,
                    u.uti_email AS ebl_email,
                    u.uti_motif_verrouillage AS ebl_reason,
                    u.uti_echecs_connexion AS ebl_attempt_count,
                    u.uti_verrouille_jusqua AS ebl_expires_at,
                    u.uti_modifie_le AS ebl_created_at,
                    p.pui_prenom AS ebl_firstname,
                    p.pui_nom AS ebl_lastname
               FROM sav_utilisateurs u
          LEFT JOIN sav_profils_utilisateurs p ON p.pui_utilisateur_id = u.uti_id AND p.pui_supprime_le IS NULL
              WHERE u.uti_est_verrouille = 1
                AND u.uti_supprime_le IS NULL
                AND (u.uti_verrouille_jusqua IS NULL OR u.uti_verrouille_jusqua > NOW())
           ORDER BY u.uti_modifie_le DESC, u.uti_id DESC
              LIMIT {$limit}"
        );
    }

    public function getUnblockHistory(int $limit = 30, int $offset = 0): array
    {
        $limit = max(1, min(100, $limit));
        $offset = max(0, $offset);
        return $this->db()->fetchAll(
            "SELECT a.jau_id AS ubh_id,
                    a.jau_action AS ubh_type,
                    a.jau_raison AS ubh_reason,
                    a.jau_id_cible AS ubh_target_id,
                    a.jau_metadata_json AS ubh_metadata,
                    a.jau_cree_le AS ubh_created_at,
                    u.uti_email AS admin_email,
                    p.pui_prenom AS use_firstname,
                    p.pui_nom AS use_lastname
               FROM sav_journaux_audit a
          LEFT JOIN sav_utilisateurs u ON u.uti_id = a.jau_utilisateur_id
          LEFT JOIN sav_profils_utilisateurs p ON p.pui_utilisateur_id = u.uti_id AND p.pui_supprime_le IS NULL
              WHERE a.jau_action IN ('security.unblock_ip', 'security.unblock_user')
           ORDER BY a.jau_cree_le DESC
              LIMIT {$limit} OFFSET {$offset}"
        );
    }

    public function unblockIp(int $blockId, int $adminId, string $adminIp, string $reason): bool
    {
        $updated = $this->db()->execute(
            "UPDATE sav_blocages_securite
                SET bse_termine_le = NOW(),
                    bse_modifie_le = NOW(),
                    bse_modifie_par_utilisateur_id = :admin_id
              WHERE bse_id = :id
                AND bse_supprime_le IS NULL",
            ['admin_id' => $adminId, 'id' => $blockId]
        );

        $this->audit('security.unblock_ip', 'sav_blocages_securite', $blockId, $adminId, $adminIp, $reason);
        return $updated;
    }

    public function unblockUser(int $userId, int $adminId, string $adminIp, string $reason): bool
    {
        $updated = $this->db()->execute(
            "UPDATE sav_utilisateurs
                SET uti_est_verrouille = 0,
                    uti_verrouille_jusqua = NULL,
                    uti_motif_verrouillage = NULL,
                    uti_echecs_connexion = 0,
                    uti_modifie_le = NOW(),
                    uti_modifie_par_utilisateur_id = :admin_id
              WHERE uti_id = :id
                AND uti_supprime_le IS NULL",
            ['admin_id' => $adminId, 'id' => $userId]
        );

        $this->audit('security.unblock_user', 'sav_utilisateurs', $userId, $adminId, $adminIp, $reason);
        return $updated;
    }

    private function audit(string $action, string $table, int $targetId, int $adminId, string $adminIp, string $reason): void
    {
        $this->db()->execute(
            "INSERT INTO sav_journaux_audit
                (jau_utilisateur_id, jau_action, jau_table_cible, jau_id_cible, jau_raison, jau_adresse_ip, jau_metadata_json, jau_cree_le)
             VALUES
                (:user_id, :action, :table_cible, :target_id, :reason, INET6_ATON(:ip), :metadata, NOW())",
            [
                'user_id' => $adminId,
                'action' => $action,
                'table_cible' => $table,
                'target_id' => $targetId,
                'reason' => $reason,
                'ip' => $adminIp,
                'metadata' => json_encode(['source' => 'Administration/SecurityController'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]
        );
    }
}
